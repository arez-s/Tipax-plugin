<?php
if (!defined('ABSPATH')) {
    exit;
}

if (class_exists('Tipax_Financial_Service')) {
    return;
}

class Tipax_Financial_Service
{
    private const MAX_DOCUMENTS = 100;

    private $request;
    private $auth;

    public function __construct($request = null, $auth = null)
    {
        $this->request = $request ?: new Request();
        $this->auth = $auth ?: new Tipax_Auth_Service($this->request);
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

    private function normalize_date($value)
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

    private function normalize_document($document)
    {
        if (is_object($document)) {
            $document = get_object_vars($document);
        }

        if (!is_array($document)) {
            return null;
        }

        return [
            'id' => isset($document['id']) ? absint($document['id']) : 0,
            'debt' => isset($document['debt']) && is_numeric($document['debt']) ? (float) $document['debt'] : 0.0,
            'credit' => isset($document['credit']) && is_numeric($document['credit']) ? (float) $document['credit'] : 0.0,
            'user_desc' => isset($document['userDesc']) && is_scalar($document['userDesc']) ? trim((string) $document['userDesc']) : '',
            'description' => isset($document['description']) && is_scalar($document['description']) ? trim((string) $document['description']) : '',
            'insert_date' => isset($document['insertDate']) && is_scalar($document['insertDate']) ? trim((string) $document['insertDate']) : '',
            'insert_date_display' => $this->normalize_date($document['insertDate'] ?? ''),
            'effective_date' => isset($document['effectiveDate']) && is_scalar($document['effectiveDate']) ? trim((string) $document['effectiveDate']) : '',
            'effective_date_display' => $this->normalize_date($document['effectiveDate'] ?? ''),
        ];
    }

    private function sort_documents(array &$documents)
    {
        usort($documents, function ($left, $right) {
            $leftTime = !empty($left['insert_date']) ? strtotime((string) $left['insert_date']) : 0;
            $rightTime = !empty($right['insert_date']) ? strtotime((string) $right['insert_date']) : 0;

            if ($leftTime === $rightTime) {
                return ($right['id'] ?? 0) <=> ($left['id'] ?? 0);
            }

            return $rightTime <=> $leftTime;
        });
    }

    public function get_documents($limit = self::MAX_DOCUMENTS)
    {
        $limit = max(1, min(self::MAX_DOCUMENTS, absint($limit)));
        $accessToken = $this->ensure_access_token();

        if (!$accessToken) {
            return [
                'success' => false,
                'message' => __('Tipax access token is not available.', 'delivery-service-tipax-shipping'),
                'documents' => [],
                'summary' => [],
            ];
        }

        $response = $this->request->apiRequest(
            'Financial/GetDocuments',
            [],
            'GET',
            $accessToken,
            [
                'timeout' => 45,
                'return_transport_error' => true,
            ]
        );

        if (function_exists('tipax_is_tipax_transport_error_response') && tipax_is_tipax_transport_error_response($response)) {
            return [
                'success' => false,
                'message' => tipax_extract_tipax_response_message($response, __('Could not retrieve financial documents from Tipax.', 'delivery-service-tipax-shipping')),
                'documents' => [],
                'summary' => [],
            ];
        }

        $documents = [];
        if (is_array($response)) {
            $documents = $response;
        } elseif (is_object($response) && isset($response->result) && is_array($response->result)) {
            $documents = $response->result;
        } elseif (is_object($response) && isset($response->data) && is_array($response->data)) {
            $documents = $response->data;
        }

        if (!is_array($documents)) {
            return [
                'success' => false,
                'message' => tipax_extract_tipax_response_message($response, __('Invalid response received from Tipax financial API.', 'delivery-service-tipax-shipping')),
                'documents' => [],
                'summary' => [],
            ];
        }

        $normalized = [];
        foreach ($documents as $document) {
            $item = $this->normalize_document($document);
            if ($item !== null) {
                $normalized[] = $item;
            }
        }

        $this->sort_documents($normalized);
        $normalized = array_slice($normalized, 0, $limit);

        $summary = [
            'count' => count($normalized),
            'debt_total' => 0.0,
            'credit_total' => 0.0,
            'net_total' => 0.0,
        ];

        foreach ($normalized as $document) {
            $summary['debt_total'] += $document['debt'];
            $summary['credit_total'] += $document['credit'];
        }

        $summary['net_total'] = $summary['credit_total'] - $summary['debt_total'];

        return [
            'success' => true,
            'message' => '',
            'documents' => $normalized,
            'summary' => $summary,
        ];
    }
}

if (!class_exists('Tipax_Financial_Module')) {
    class_alias('Tipax_Financial_Service', 'Tipax_Financial_Module');
}
