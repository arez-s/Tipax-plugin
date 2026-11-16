<?php
if (!defined('ABSPATH')) {
    exit;
}

if (class_exists('Tipax_Ticketing_Service')) {
    return;
}

class Tipax_Ticketing_Service
{
    private const DEPARTMENTS = [
        422 => 'پشتیبانی فنی ای‌تیپاکس',
        423 => 'فروش و امور مشتریان ای‌تیپاکس',
        424 => 'پشتیبانی مالی و اعتباری ای‌تیپاکس',
        822 => 'پشتیبانی افزونه وردپرس',
    ];

    private const PRIORITIES = [
        1 => 'مهم',
        2 => 'عادی',
        3 => 'کم‌اهمیت',
    ];

    private $request;
    private $auth;

    public function __construct($request = null, $auth = null)
    {
        $this->request = $request ?: new Request();
        $this->auth = $auth ?: new Tipax_Auth_Service($this->request);
    }

    public static function get_departments()
    {
        return self::DEPARTMENTS;
    }

    public static function get_priorities()
    {
        return self::PRIORITIES;
    }

    private function ensure_access_token()
    {
        $accessToken = $this->auth->get_access_token();
        if (!$accessToken) {
            $this->auth->check_api_key(true);
            $accessToken = $this->auth->get_access_token();
        }

        return $accessToken;
    }

    private function is_error_response($response)
    {
        if (function_exists('tipax_is_tipax_transport_error_response') && tipax_is_tipax_transport_error_response($response)) {
            return true;
        }

        if (is_object($response) && !empty($response->errors) && is_array($response->errors)) {
            return true;
        }

        return false;
    }

    private function format_date($value)
    {
        if (!is_scalar($value)) {
            return '';
        }

        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $timestamp = strtotime($value);
        return $timestamp ? wp_date('Y/m/d H:i', $timestamp) : $value;
    }

    private function normalize_ticket($ticket)
    {
        if (is_object($ticket)) {
            $ticket = get_object_vars($ticket);
        }

        if (!is_array($ticket)) {
            return null;
        }

        return [
            'id' => isset($ticket['id']) ? absint($ticket['id']) : 0,
            'subject' => isset($ticket['subject']) && is_scalar($ticket['subject']) ? trim((string) $ticket['subject']) : '',
            'description' => isset($ticket['description']) && is_scalar($ticket['description']) ? trim((string) $ticket['description']) : '',
            'creator_name' => isset($ticket['creatorName']) && is_scalar($ticket['creatorName']) ? trim((string) $ticket['creatorName']) : '',
            'customer_name' => isset($ticket['customerName']) && is_scalar($ticket['customerName']) ? trim((string) $ticket['customerName']) : '',
            'department' => isset($ticket['department']) && is_scalar($ticket['department']) ? trim((string) $ticket['department']) : '',
            'priority' => isset($ticket['priority']) && is_scalar($ticket['priority']) ? trim((string) $ticket['priority']) : '',
            'status_name' => isset($ticket['statusName']) && is_scalar($ticket['statusName']) ? trim((string) $ticket['statusName']) : '',
            'created_on' => isset($ticket['createdOn']) && is_scalar($ticket['createdOn']) ? trim((string) $ticket['createdOn']) : '',
            'created_on_display' => $this->format_date($ticket['createdOn'] ?? ''),
            'created_on_jalali' => isset($ticket['createdOnJalali']) && is_scalar($ticket['createdOnJalali']) ? trim((string) $ticket['createdOnJalali']) : '',
        ];
    }

    private function extract_created_ticket_id($response)
    {
        if (is_object($response)) {
            if (!empty($response->id)) {
                return absint($response->id);
            }

            if (!empty($response->result) && is_object($response->result) && !empty($response->result->id)) {
                return absint($response->result->id);
            }
        }

        if (is_array($response) && !empty($response['id'])) {
            return absint($response['id']);
        }

        return 0;
    }

    public function create_ticket($departmentId, $priorityId, $subject, $description)
    {
        $departmentId = absint($departmentId);
        $priorityId = absint($priorityId);

        if (!isset(self::DEPARTMENTS[$departmentId])) {
            return [
                'success' => false,
                'message' => 'دپارتمان انتخاب‌شده معتبر نیست.',
                'ticket_id' => 0,
                'response' => null,
            ];
        }

        if (!isset(self::PRIORITIES[$priorityId])) {
            return [
                'success' => false,
                'message' => 'اولویت انتخاب‌شده معتبر نیست.',
                'ticket_id' => 0,
                'response' => null,
            ];
        }

        $accessToken = $this->ensure_access_token();
        if (!$accessToken) {
            return [
                'success' => false,
                'message' => __('Tipax access token is not available.', 'delivery-service-tipax-shipping'),
                'ticket_id' => 0,
                'response' => null,
            ];
        }

        $payload = new stdClass();
        $payload->departmentId = $departmentId;
        $payload->priorityId = $priorityId;
        $payload->subject = (string) $subject;
        $payload->description = (string) $description;

        $response = $this->request->apiRequest(
            'Ticketing',
            $payload,
            'POST',
            $accessToken,
            [
                'timeout' => 45,
                'return_transport_error' => true,
            ]
        );

        if ($this->is_error_response($response)) {
            return [
                'success' => false,
                'message' => tipax_extract_tipax_response_message($response, __('Ticket could not be created in Tipax.', 'delivery-service-tipax-shipping')),
                'ticket_id' => 0,
                'response' => $response,
            ];
        }

        return [
            'success' => true,
            'message' => __('Ticket created successfully.', 'delivery-service-tipax-shipping'),
            'ticket_id' => $this->extract_created_ticket_id($response),
            'response' => $response,
        ];
    }

    public function get_tickets()
    {
        $accessToken = $this->ensure_access_token();
        if (!$accessToken) {
            return [
                'success' => false,
                'message' => __('Tipax access token is not available.', 'delivery-service-tipax-shipping'),
                'total_count' => 0,
                'tickets' => [],
            ];
        }

        $response = $this->request->apiRequest(
            'Ticketing',
            [],
            'GET',
            $accessToken,
            [
                'timeout' => 45,
                'return_transport_error' => true,
            ]
        );

        if ($this->is_error_response($response)) {
            return [
                'success' => false,
                'message' => tipax_extract_tipax_response_message($response, __('Could not retrieve Tipax tickets.', 'delivery-service-tipax-shipping')),
                'total_count' => 0,
                'tickets' => [],
            ];
        }

        $totalCount = 0;
        $rawTickets = [];

        if (is_object($response) && isset($response->result) && is_array($response->result)) {
            $rawTickets = $response->result;
            $totalCount = isset($response->totalCount) ? absint($response->totalCount) : count($rawTickets);
        } elseif (is_array($response)) {
            $rawTickets = $response;
            $totalCount = count($rawTickets);
        }

        $tickets = [];
        foreach ($rawTickets as $ticket) {
            $normalized = $this->normalize_ticket($ticket);
            if ($normalized !== null) {
                $tickets[] = $normalized;
            }
        }

        return [
            'success' => true,
            'message' => '',
            'total_count' => $totalCount,
            'tickets' => $tickets,
        ];
    }

    public function get_ticket_detail($ticketId)
    {
        $ticketId = absint($ticketId);
        if ($ticketId <= 0) {
            return [
                'success' => false,
                'message' => __('Invalid ticket ID.', 'delivery-service-tipax-shipping'),
                'ticket' => null,
            ];
        }

        $accessToken = $this->ensure_access_token();
        if (!$accessToken) {
            return [
                'success' => false,
                'message' => __('Tipax access token is not available.', 'delivery-service-tipax-shipping'),
                'ticket' => null,
            ];
        }

        $response = $this->request->apiRequest(
            'Ticketing/' . $ticketId,
            [],
            'GET',
            $accessToken,
            [
                'timeout' => 45,
                'return_transport_error' => true,
            ]
        );

        if ($this->is_error_response($response)) {
            return [
                'success' => false,
                'message' => tipax_extract_tipax_response_message($response, __('Could not retrieve the selected ticket.', 'delivery-service-tipax-shipping')),
                'ticket' => null,
            ];
        }

        $ticket = $this->normalize_ticket($response);
        if ($ticket === null) {
            return [
                'success' => false,
                'message' => __('Invalid ticket detail response received from Tipax.', 'delivery-service-tipax-shipping'),
                'ticket' => null,
            ];
        }

        return [
            'success' => true,
            'message' => '',
            'ticket' => $ticket,
        ];
    }
}

if (!class_exists('Tipax_Ticketing_Module')) {
    class_alias('Tipax_Ticketing_Service', 'Tipax_Ticketing_Module');
}
