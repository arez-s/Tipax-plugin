<?php
if (!defined('ABSPATH')) {
    exit;
}

if (class_exists('Tipax_Tracking_Service')) {
    return;
}

class Tipax_Tracking_Service
{
    private const DEFAULT_BATCH_SIZE = 20;
    private const DEFAULT_LOOKBACK_DAYS = 120;
    private const DEFAULT_STALE_INTERVAL = DAY_IN_SECONDS;
    private const UPDATABLE_STATUS_IDS = [6, 59, 88, 119, 129, 309];

    private $request;
    private $auth;

    public function __construct($request = null, $auth = null)
    {
        $this->request = $request ?: new Request();
        $this->auth = $auth ?: new Tipax_Auth_Service($this->request);
    }

    public static function get_updatable_status_ids()
    {
        return self::UPDATABLE_STATUS_IDS;
    }

    public static function is_updatable_status_id($statusId)
    {
        return in_array((int) $statusId, self::UPDATABLE_STATUS_IDS, true);
    }

    public static function should_update_status_id($statusId)
    {
        $statusId = (int) $statusId;

        // Orders created before status IDs were stored need one lookup to establish their state.
        return $statusId <= 0 || self::is_updatable_status_id($statusId);
    }

    private function normalize_tracking_code($trackingCode)
    {
        return tipax_normalize_tracking_code($trackingCode);
    }

    private function normalize_contract_code($contractCode)
    {
        if (!is_scalar($contractCode)) {
            return '';
        }

        $contractCode = strtr(trim((string) $contractCode), [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        return preg_match('/^[0-9]+$/D', $contractCode) ? $contractCode : '';
    }

    private function split_tracking_codes($trackingCodeRaw)
    {
        if (!is_scalar($trackingCodeRaw)) {
            return [];
        }

        $parts = preg_split('/[\s,،]+/u', trim((string) $trackingCodeRaw));
        if (!is_array($parts)) {
            return [];
        }

        $codes = [];
        foreach ($parts as $part) {
            $code = $this->normalize_tracking_code($part);
            if ($code !== '') {
                $codes[$code] = $code;
            }
        }

        return array_values($codes);
    }

    private function normalize_timestamp($value)
    {
        if ($value === null || $value === '') {
            return 0;
        }

        if (is_numeric($value)) {
            $timestamp = (int) $value;
            if ($timestamp > 9999999999) {
                $timestamp = (int) round($timestamp / 1000);
            }

            return $timestamp > 0 ? $timestamp : 0;
        }

        if (!is_scalar($value)) {
            return 0;
        }

        $timestamp = strtotime((string) $value);
        return $timestamp ? $timestamp : 0;
    }

    private function format_display_date($value)
    {
        $timestamp = $this->normalize_timestamp($value);
        if ($timestamp > 0) {
            return wp_date('Y/m/d H:i', $timestamp);
        }

        return is_scalar($value) ? trim((string) $value) : '';
    }

    private function extract_node_scalar(array $node, array $keys)
    {
        if (empty($node)) {
            return '';
        }

        $normalized_keys = array_map('strtolower', $keys);
        foreach ($node as $nodeKey => $value) {
            if (!is_scalar($value)) {
                continue;
            }

            if (in_array(strtolower((string) $nodeKey), $normalized_keys, true)) {
                $value = trim((string) $value);
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return '';
    }

    private function extract_scalar_from_data($data, array $keys, $depth = 0)
    {
        if ($depth > 6) {
            return '';
        }

        if (is_object($data)) {
            $data = get_object_vars($data);
        }

        if (!is_array($data)) {
            return '';
        }

        $direct = $this->extract_node_scalar($data, $keys);
        if ($direct !== '') {
            return $direct;
        }

        foreach ($data as $value) {
            if (!is_array($value) && !is_object($value)) {
                continue;
            }

            $found = $this->extract_scalar_from_data($value, $keys, $depth + 1);
            if ($found !== '') {
                return $found;
            }
        }

        return '';
    }

    private function extract_scalar_from_named_node($data, array $nodeKeys, array $valueKeys, $depth = 0)
    {
        if ($depth > 6) {
            return '';
        }

        if (is_object($data)) {
            $data = get_object_vars($data);
        }

        if (!is_array($data)) {
            return '';
        }

        $normalizedNodeKeys = array_map('strtolower', $nodeKeys);
        foreach ($data as $key => $value) {
            if (!in_array(strtolower((string) $key), $normalizedNodeKeys, true)) {
                continue;
            }

            if (is_scalar($value)) {
                $value = trim((string) $value);
                if ($value !== '') {
                    return $value;
                }
                continue;
            }

            $node = is_object($value) ? get_object_vars($value) : $value;
            if (is_array($node)) {
                $found = $this->extract_node_scalar($node, $valueKeys);
                if ($found !== '') {
                    return $found;
                }
            }
        }

        foreach ($data as $value) {
            if (!is_array($value) && !is_object($value)) {
                continue;
            }

            $found = $this->extract_scalar_from_named_node($value, $nodeKeys, $valueKeys, $depth + 1);
            if ($found !== '') {
                return $found;
            }
        }

        return '';
    }

    private function append_timeline_record(array &$records, array $record)
    {
        $title = isset($record['title']) ? trim((string) $record['title']) : '';
        $description = isset($record['description']) ? trim((string) $record['description']) : '';
        $location = isset($record['location']) ? trim((string) $record['location']) : '';
        $date_raw = isset($record['date_raw']) ? $record['date_raw'] : '';

        if ($title === '') {
            return;
        }

        $key = md5(
            wp_json_encode([
                $title,
                $description,
                $location,
                is_scalar($date_raw) ? (string) $date_raw : '',
            ])
        );

        if (!isset($records[$key])) {
            $records[$key] = $record;
        }
    }

    private function walk_timeline_nodes($data, array &$records, $depth = 0)
    {
        if ($depth > 6) {
            return;
        }

        if (is_object($data)) {
            $data = get_object_vars($data);
        }

        if (!is_array($data) || empty($data)) {
            return;
        }

        $title = $this->extract_node_scalar($data, [
            'status',
            'statusTitle',
            'currentStatus',
            'shipmentStatus',
            'processTitle',
            'eventTitle',
            'actionTitle',
            'title',
            'state',
        ]);
        $description = $this->extract_node_scalar($data, [
            'description',
            'detail',
            'details',
            'message',
            'text',
            'note',
            'comment',
        ]);
        $location = $this->extract_node_scalar($data, [
            'location',
            'branch',
            'branchName',
            'city',
            'cityTitle',
            'hubName',
            'stationName',
            'officeName',
            'destinationCityTitle',
            'sourceCityTitle',
        ]);
        $dateRaw = $this->extract_node_scalar($data, [
            'date',
            'trackingDate',
            'statusDate',
            'createDate',
            'createdAt',
            'dateTime',
            'eventDate',
            'operationDate',
            'insertDate',
            'updateDate',
            'updatedAt',
            'persianDate',
        ]);

        if ($title !== '' && ($dateRaw !== '' || $description !== '' || $location !== '')) {
            $timestamp = $this->normalize_timestamp($dateRaw);
            $this->append_timeline_record($records, [
                'title' => $title,
                'description' => $description,
                'location' => $location,
                'date_raw' => $dateRaw,
                'date' => $this->format_display_date($dateRaw),
                'timestamp' => $timestamp,
            ]);
        }

        foreach ($data as $value) {
            if (is_array($value) || is_object($value)) {
                $this->walk_timeline_nodes($value, $records, $depth + 1);
            }
        }
    }

    private function extract_timeline_records($contractResponse, $trackingResponse)
    {
        $records = [];

        if ($contractResponse !== null) {
            $this->walk_timeline_nodes($contractResponse, $records);
        }

        if (empty($records)) {
            $this->walk_timeline_nodes($trackingResponse, $records);
        }

        $records = array_values($records);

        usort($records, function ($left, $right) {
            $leftTs = isset($left['timestamp']) ? (int) $left['timestamp'] : 0;
            $rightTs = isset($right['timestamp']) ? (int) $right['timestamp'] : 0;

            if ($leftTs === $rightTs) {
                return 0;
            }

            return $leftTs < $rightTs ? 1 : -1;
        });

        return $records;
    }

    private function get_tracking_items($response)
    {
        if (tipax_is_tipax_transport_error_response($response)) {
            return [];
        }

        if (is_object($response)) {
            foreach (['result', 'data', 'items'] as $property) {
                if (isset($response->{$property})) {
                    return $this->get_tracking_items($response->{$property});
                }
            }

            return [$response];
        }

        if (!is_array($response) || empty($response)) {
            return [];
        }

        foreach (['result', 'data', 'items'] as $key) {
            if (array_key_exists($key, $response)) {
                return $this->get_tracking_items($response[$key]);
            }
        }

        $keys = array_keys($response);
        $isList = $keys === range(0, count($response) - 1);
        if (!$isList) {
            return [$response];
        }

        return array_values(array_filter($response, function ($item) {
            return is_array($item) || is_object($item);
        }));
    }

    private function get_first_tracking_item($response)
    {
        $items = $this->get_tracking_items($response);
        return empty($items) ? null : reset($items);
    }

    private function find_tracking_item_by_code($response, $trackingCode)
    {
        $trackingCode = $this->normalize_tracking_code($trackingCode);
        foreach ($this->get_tracking_items($response) as $item) {
            $itemCode = $this->normalize_tracking_code(
                $this->extract_scalar_from_data($item, ['trackingCode', 'barcode', 'parcelCode'])
            );
            if ($itemCode !== '' && $itemCode === $trackingCode) {
                return $item;
            }
        }

        return null;
    }

    private function extract_status_from_response_item($item)
    {
        return $this->extract_scalar_from_data($item, [
            'status',
            'statusTitle',
            'currentStatus',
            'shipmentStatus',
            'title',
            'state',
        ]);
    }

    private function normalize_status_id($value)
    {
        if (!is_scalar($value)) {
            return 0;
        }

        $value = trim((string) $value);
        if ($value === '' || !preg_match('/^\d+$/', $value)) {
            return 0;
        }

        return (int) $value;
    }

    private function extract_status_id_from_response_item($item)
    {
        $statusId = $this->extract_scalar_from_data($item, [
            'statusId',
            'shipmentStatusId',
            'currentStatusId',
            'stateId',
        ]);
        $statusId = $this->normalize_status_id($statusId);
        if ($statusId > 0) {
            return $statusId;
        }

        if (is_object($item)) {
            $item = get_object_vars($item);
        }

        if (!is_array($item)) {
            return 0;
        }

        foreach (['status', 'currentStatus', 'shipmentStatus', 'state'] as $statusKey) {
            if (!isset($item[$statusKey]) || (!is_array($item[$statusKey]) && !is_object($item[$statusKey]))) {
                continue;
            }

            $statusId = $this->normalize_status_id(
                $this->extract_scalar_from_data($item[$statusKey], ['id', 'statusId'])
            );
            if ($statusId > 0) {
                return $statusId;
            }
        }

        return 0;
    }

    private function extract_contract_code_from_response_item($item)
    {
        return $this->extract_scalar_from_data($item, [
            'jetContractCode',
            'contractCode',
            'contractNo',
            'contractNumber',
        ]);
    }

    private function is_valid_tracking_response($response)
    {
        if (tipax_is_tipax_transport_error_response($response)) {
            return false;
        }

        $firstItem = $this->get_first_tracking_item($response);
        return is_object($firstItem) || is_array($firstItem);
    }

    private function build_tracking_error_result($trackingCode, $errorCode, $message, $response = null)
    {
        return [
            'success' => false,
            'error_code' => $errorCode,
            'message' => $message,
            'tracking_code' => $trackingCode,
            'response' => $response,
        ];
    }

    private function mark_tracking_attempt($order_id, $message = '')
    {
        $order = wc_get_order($order_id);
        if ($order) {
            $order->update_meta_data('_tipax_tracking_last_checked', current_time('mysql'));

            if ($message !== '') {
                $order->update_meta_data('_tipax_tracking_last_error', sanitize_textarea_field($message));
            } else {
                $order->delete_meta_data('_tipax_tracking_last_error');
            }

            $order->save_meta_data();
            return;
        }

        update_post_meta($order_id, '_tipax_tracking_last_checked', current_time('mysql'));
        if ($message !== '') {
            update_post_meta($order_id, '_tipax_tracking_last_error', sanitize_textarea_field($message));
        } else {
            delete_post_meta($order_id, '_tipax_tracking_last_error');
        }
    }

    public function get_order_tracking_codes($order, $firstCodeOnly = false)
    {
        if (!$order) {
            return [];
        }

        $codes = [];
        foreach ($order->get_items() as $item) {
            foreach ($this->split_tracking_codes($item->get_meta('tipax_tracking_code')) as $code) {
                $codes[$code] = $code;
            }
        }

        $cancelledCodes = tipax_normalize_tracking_codes($order->get_meta('_tipax_cancelled_tracking_codes', true));
        $codes = array_values(array_diff(array_values($codes), $cancelledCodes));

        return $firstCodeOnly ? array_slice($codes, 0, 1) : $codes;
    }

    public function get_order_tracking_code($order, $firstCodeOnly = false)
    {
        $codes = $this->get_order_tracking_codes($order, $firstCodeOnly);
        return isset($codes[0]) ? $codes[0] : null;
    }

    public function get_order_tipax_order_id($order)
    {
        if (!$order) {
            return '';
        }

        foreach ($order->get_items() as $item) {
            $orderId = trim((string) $item->get_meta('tipax_order_id'));
            if ($orderId !== '') {
                return $orderId;
            }
        }

        return '';
    }

    public function sync_order_tracking_snapshot($orderOrId)
    {
        $order = $orderOrId instanceof WC_Order ? $orderOrId : wc_get_order($orderOrId);
        if (!$order) {
            return [
                'codes' => [],
                'primary_code' => '',
                'tipax_order_id' => '',
            ];
        }

        $codes = $this->get_order_tracking_codes($order);
        $primaryCode = isset($codes[0]) ? $codes[0] : '';
        $tipaxOrderId = $this->get_order_tipax_order_id($order);

        if ($primaryCode !== '') {
            $order->update_meta_data('_tipax_tracking_primary_code', $primaryCode);
        } else {
            $order->delete_meta_data('_tipax_tracking_primary_code');
        }

        if (!empty($codes)) {
            $order->update_meta_data('_tipax_tracking_codes', $codes);
        } else {
            $order->delete_meta_data('_tipax_tracking_codes');
        }

        if ($tipaxOrderId !== '') {
            $order->update_meta_data('_tipax_order_reference', $tipaxOrderId);
        } else {
            $order->delete_meta_data('_tipax_order_reference');
        }
        $order->save_meta_data();

        return [
            'codes' => $codes,
            'primary_code' => $primaryCode,
            'tipax_order_id' => $tipaxOrderId,
        ];
    }

    public function ensure_access_token()
    {
        $token = $this->auth->get_access_token();
        if (!empty($token)) {
            return $token;
        }

        if (method_exists($this->auth, 'request_token')) {
            $tokenResult = $this->auth->request_token();
            if (!empty($tokenResult['success']) && !empty($tokenResult['token'])) {
                return $tokenResult['token'];
            }
        }

        return $this->auth->get_access_token();
    }

    public function track_by_tracking_code($trackingCode, $accessToken = null)
    {
        $trackingCode = $this->normalize_tracking_code($trackingCode);
        if ($trackingCode === '') {
            return null;
        }

        $token = $accessToken ?: $this->ensure_access_token();
        if (empty($token)) {
            return null;
        }

        return $this->request->apiRequest(
            'Tracking/ByTrackingCode',
            [$trackingCode],
            'POST',
            $token,
            [
                'timeout' => 45,
                'return_transport_error' => true,
                'content_type' => 'application/json',
            ]
        );
    }

    public function track_by_contract_code($contract, $accessToken = null)
    {
        $contract = $this->normalize_contract_code($contract);
        if ($contract === '') {
            return null;
        }

        $token = $accessToken ?: $this->ensure_access_token();
        if (empty($token)) {
            return null;
        }

        return $this->request->apiRequest(
            'Tracking/ByContractCode/' . rawurlencode($contract),
            [],
            'GET',
            $token,
            [
                'timeout' => 45,
                'return_transport_error' => true,
            ]
        );
    }

    public function build_tracking_view_data($trackingCode, $trackingResponse, $contractResponse = null, array $extra = [])
    {
        $trackingItem = $this->get_first_tracking_item($trackingResponse);
        $status = $trackingItem ? $this->extract_status_from_response_item($trackingItem) : '';
        if ($status === '') {
            $status = $this->extract_scalar_from_data($trackingResponse, ['status', 'statusTitle', 'currentStatus']);
        }
        $statusId = $trackingItem ? $this->extract_status_id_from_response_item($trackingItem) : 0;

        $contractCode = $trackingItem ? $this->extract_contract_code_from_response_item($trackingItem) : '';
        if ($contractCode === '') {
            $contractCode = $this->extract_scalar_from_data($contractResponse, [
                'jetContractCode',
                'contractCode',
                'contractNo',
                'contractNumber',
            ]);
        }

        $timeline = $this->extract_timeline_records($contractResponse, $trackingResponse);
        if (empty($timeline) && $status !== '') {
            $timeline[] = [
                'title' => $status,
                'description' => 'آخرین وضعیت دریافت‌شده از تیپاکس',
                'location' => '',
                'date_raw' => '',
                'date' => '',
                'timestamp' => 0,
            ];
        }

        $lastEventDateRaw = '';
        if (!empty($timeline[0]['date_raw'])) {
            $lastEventDateRaw = $timeline[0]['date_raw'];
        } else {
            $lastEventDateRaw = $this->extract_scalar_from_data($contractResponse ?: $trackingResponse, [
                'date',
                'trackingDate',
                'statusDate',
                'eventDate',
                'updateDate',
                'updatedAt',
            ]);
        }

        $receiver = $this->extract_scalar_from_named_node(
            [$trackingResponse, $contractResponse],
            ['receivers', 'receiver', 'recipient'],
            ['fullName', 'name', 'receiverFullName', 'receiverName']
        );
        if ($receiver === '') {
            $receiver = $this->extract_scalar_from_data([$trackingResponse, $contractResponse], [
                'receiverFullName',
                'receiverName',
                'recipientName',
                'customerName',
            ]);
        }

        $sender = $this->extract_scalar_from_named_node(
            [$trackingResponse, $contractResponse],
            ['sender'],
            ['fullName', 'name', 'senderFullName', 'senderName']
        );
        if ($sender === '') {
            $sender = $this->extract_scalar_from_data([$trackingResponse, $contractResponse], [
                'senderFullName',
                'senderName',
            ]);
        }

        $origin = $this->extract_scalar_from_data([$trackingResponse, $contractResponse], [
            'originCity',
            'originCityTitle',
            'sourceCityTitle',
            'fromCity',
            'senderCity',
        ]);
        if ($origin === '') {
            $origin = $this->extract_scalar_from_data([$trackingResponse, $contractResponse], ['origin']);
        }

        $destination = $this->extract_scalar_from_data([$trackingResponse, $contractResponse], [
            'destinationCity',
            'destinationCityTitle',
            'receiverCity',
            'toCity',
        ]);
        if ($destination === '') {
            $destination = $this->extract_scalar_from_data([$trackingResponse, $contractResponse], ['destination']);
        }

        return array_merge([
            'tracking_code' => $trackingCode,
            'status' => $status !== '' ? $status : 'نامشخص',
            'status_id' => $statusId,
            'contract' => $contractCode,
            'checked_at' => current_time('mysql'),
            'checked_at_display' => wp_date('Y/m/d H:i', current_time('timestamp')),
            'last_event_at' => $lastEventDateRaw,
            'last_event_at_display' => $this->format_display_date($lastEventDateRaw),
            'origin' => $origin,
            'destination' => $destination,
            'receiver' => $receiver,
            'sender' => $sender,
            'timeline' => $timeline,
        ], $extra);
    }

    public function lookup_tracking_code($trackingCode, $accessToken = null)
    {
        $trackingCode = $this->normalize_tracking_code($trackingCode);
        if ($trackingCode === '') {
            return $this->build_tracking_error_result($trackingCode, 'invalid_tracking_code', 'کد رهگیری معتبر نیست.');
        }

        $token = $accessToken ?: $this->ensure_access_token();
        if (empty($token)) {
            return $this->build_tracking_error_result($trackingCode, 'missing_access_token', 'توکن دسترسی تیپاکس در دسترس نیست.');
        }

        $response = $this->track_by_tracking_code($trackingCode, $token);
        if (!$this->is_valid_tracking_response($response)) {
            return $this->build_tracking_error_result(
                $trackingCode,
                'invalid_response',
                tipax_extract_tipax_response_message($response, 'پاسخ معتبری از تیپاکس برای این کد رهگیری دریافت نشد.'),
                $response
            );
        }

        $trackingItem = $this->get_first_tracking_item($response);
        $contract = $this->extract_contract_code_from_response_item($trackingItem);
        $contractResponse = $contract !== '' ? $this->track_by_contract_code($contract, $token) : null;
        $contractItem = $contractResponse !== null
            ? $this->find_tracking_item_by_code($contractResponse, $trackingCode)
            : null;
        $viewData = $this->build_tracking_view_data(
            $trackingCode,
            $response,
            $contractItem !== null ? $contractItem : $contractResponse,
            ['lookup_type' => 'tracking']
        );

        return [
            'success' => true,
            'lookup_type' => 'tracking',
            'tracking_code' => $trackingCode,
            'status' => $viewData['status'],
            'status_id' => $viewData['status_id'],
            'contract' => $viewData['contract'],
            'response' => $response,
            'contract_response' => $contractResponse,
            'view' => $viewData,
            'views' => [$viewData],
        ];
    }

    public function lookup_contract_code($contractCode, $accessToken = null)
    {
        $contractCode = $this->normalize_contract_code($contractCode);
        if ($contractCode === '') {
            return $this->build_tracking_error_result('', 'invalid_contract_code', 'کد قرارداد معتبر نیست.');
        }

        $token = $accessToken ?: $this->ensure_access_token();
        if (empty($token)) {
            return $this->build_tracking_error_result('', 'missing_access_token', 'توکن دسترسی تیپاکس در دسترس نیست.');
        }

        $response = $this->track_by_contract_code($contractCode, $token);
        $items = $this->get_tracking_items($response);
        if (empty($items)) {
            return $this->build_tracking_error_result(
                '',
                'invalid_contract_response',
                tipax_extract_tipax_response_message($response, 'پاسخ معتبری از تیپاکس برای این کد قرارداد دریافت نشد.'),
                $response
            );
        }

        $views = [];
        foreach ($items as $item) {
            $trackingCode = $this->normalize_tracking_code(
                $this->extract_scalar_from_data($item, ['trackingCode', 'barcode', 'parcelCode'])
            );
            $views[] = $this->build_tracking_view_data($trackingCode, $item, $item, [
                'contract' => $contractCode,
                'lookup_type' => 'contract',
            ]);
        }

        $viewData = reset($views);

        return [
            'success' => true,
            'lookup_type' => 'contract',
            'tracking_code' => isset($viewData['tracking_code']) ? $viewData['tracking_code'] : '',
            'status' => isset($viewData['status']) ? $viewData['status'] : 'نامشخص',
            'status_id' => isset($viewData['status_id']) ? $viewData['status_id'] : 0,
            'contract' => $contractCode,
            'response' => $response,
            'contract_response' => $response,
            'view' => $viewData,
            'views' => $views,
        ];
    }

    public function lookup_tracking_input($lookupInput, $accessToken = null)
    {
        $contractCode = $this->normalize_contract_code($lookupInput);
        if ($contractCode !== '') {
            return $this->lookup_contract_code($contractCode, $accessToken);
        }

        $trackingCode = $this->normalize_tracking_code($lookupInput);
        if ($trackingCode === '') {
            return $this->build_tracking_error_result('', 'invalid_lookup_input', 'کد رهگیری یا کد قرارداد معتبر نیست.');
        }

        return $this->lookup_tracking_code($trackingCode, $accessToken);
    }

    public function update_order_status($order_id, $firstCodeOnly = false)
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return [
                'success' => false,
                'error_code' => 'invalid_order',
                'message' => 'Order not found.',
            ];
        }

        $savedStatusId = (int) $order->get_meta('_tipax_saved_status_id', true);
        if (!self::should_update_status_id($savedStatusId)) {
            return [
                'success' => true,
                'skipped' => true,
                'updated' => false,
                'error_code' => 'status_not_updatable',
                'message' => 'رهگیری این سفارش پایان یافته و نیازی به بروزرسانی مجدد ندارد.',
                'order_id' => $order_id,
                'status_id' => $savedStatusId,
                'status' => (string) $order->get_meta('_tipax_saved_status', true),
                'contract' => (string) $order->get_meta('_tipax_saved_contract', true),
            ];
        }

        $this->sync_order_tracking_snapshot($order);
        $trackingCode = $this->get_order_tracking_code($order, $firstCodeOnly);
        if (!$trackingCode) {
            $this->mark_tracking_attempt($order_id, 'کد رهگیری برای این سفارش یافت نشد.');
            return [
                'success' => false,
                'error_code' => 'missing_tracking_code',
                'message' => 'Tracking code not found.',
            ];
        }

        $result = $this->lookup_tracking_code($trackingCode);
        if (!$result['success']) {
            $this->mark_tracking_attempt($order_id, $result['message']);
            $result['order_id'] = $order_id;
            return $result;
        }

        $order->update_meta_data('_tipax_saved_status', $result['status']);
        $order->update_meta_data('_tipax_saved_contract', $result['contract']);
        $order->update_meta_data('_tipax_tracking_last_view', $result['view']);
        if (!empty($result['status_id'])) {
            $order->update_meta_data('_tipax_saved_status_id', (int) $result['status_id']);
        }

        if ($result['contract_response'] !== null) {
            $order->update_meta_data('_tipax_contract_tracking_data', $result['contract_response']);
        } else {
            $order->delete_meta_data('_tipax_contract_tracking_data');
        }
        $order->save_meta_data();
        $this->mark_tracking_attempt($order_id);

        $result['order_id'] = $order_id;
        return $result;
    }

    public function update_orders_statuses(array $orderIds, $firstCodeOnly = true)
    {
        $orderIds = array_values(array_unique(array_filter(array_map('absint', $orderIds))));
        $summary = [
            'success' => true,
            'processed' => 0,
            'updated' => 0,
            'skipped' => 0,
            'failed' => 0,
            'results' => [],
        ];

        foreach ($orderIds as $orderId) {
            $summary['processed']++;
            $result = $this->update_order_status($orderId, $firstCodeOnly);
            $summary['results'][$orderId] = $result;

            if (!empty($result['skipped'])) {
                $summary['skipped']++;
                continue;
            }

            if (!empty($result['success'])) {
                $summary['updated']++;
                continue;
            }

            $summary['failed']++;
            $summary['success'] = false;
        }

        return $summary;
    }

    public function get_due_trackable_order_ids($limit = self::DEFAULT_BATCH_SIZE, $staleInterval = self::DEFAULT_STALE_INTERVAL, $lookbackDays = self::DEFAULT_LOOKBACK_DAYS)
    {
        $limit = max(1, absint($limit));
        $staleInterval = max(HOUR_IN_SECONDS, absint($staleInterval));
        $lookbackDays = max(7, absint($lookbackDays));

        $batchQueryLimit = min(max($limit * 3, 30), 100);
        $checkedBefore = current_time('timestamp') - $staleInterval;
        $after = date('Y-m-d', strtotime('-' . $lookbackDays . ' days', current_time('timestamp')));
        $page = 1;
        $matchedOrderIds = [];
        $tipaxOrderQuery = new Tipax_Order_Query();

        while ($page <= 10 && count($matchedOrderIds) < $limit) {
            $queryResult = $tipaxOrderQuery->get_orders([
                'view' => 'tipax',
                'statuses' => ['processing', 'completed', 'on-hold', 'pending'],
                'page' => $page,
                'per_page' => $batchQueryLimit,
                'from' => $after,
            ]);

            $orders = $queryResult['orders'];
            if (empty($orders)) {
                break;
            }

            foreach ($orders as $order) {
                $savedStatusId = (int) $order->get_meta('_tipax_saved_status_id', true);
                if (!self::should_update_status_id($savedStatusId)) {
                    continue;
                }

                $snapshot = $this->sync_order_tracking_snapshot($order);
                if (empty($snapshot['primary_code'])) {
                    continue;
                }

                $lastChecked = $order->get_meta('_tipax_tracking_last_checked', true);
                $lastCheckedTs = $this->normalize_timestamp($lastChecked);
                if ($lastCheckedTs > 0 && $lastCheckedTs > $checkedBefore) {
                    continue;
                }

                $matchedOrderIds[] = $order->get_id();
                if (count($matchedOrderIds) >= $limit) {
                    break;
                }
            }

            if ($page >= (int) $queryResult['max_num_pages']) {
                break;
            }

            $page++;
        }

        return $matchedOrderIds;
    }

    public function run_scheduled_updates($batchSize = self::DEFAULT_BATCH_SIZE, $maxBatches = 1)
    {
        $batchSize = max(1, absint($batchSize));
        $maxBatches = max(1, absint($maxBatches));

        $summary = [
            'success' => true,
            'processed' => 0,
            'updated' => 0,
            'skipped' => 0,
            'failed' => 0,
            'batches' => 0,
            'results' => [],
            'order_ids' => [],
        ];

        for ($batch = 0; $batch < $maxBatches; $batch++) {
            $orderIds = $this->get_due_trackable_order_ids($batchSize);
            if (empty($orderIds)) {
                break;
            }

            $batchResult = $this->update_orders_statuses($orderIds, true);
            $summary['batches']++;
            $summary['processed'] += $batchResult['processed'];
            $summary['updated'] += $batchResult['updated'];
            $summary['skipped'] += $batchResult['skipped'];
            $summary['failed'] += $batchResult['failed'];
            $summary['success'] = $summary['success'] && $batchResult['success'];
            $summary['order_ids'] = array_values(array_unique(array_merge($summary['order_ids'], $orderIds)));
            $summary['results'] = array_replace($summary['results'], $batchResult['results']);

            if (count($orderIds) < $batchSize) {
                break;
            }
        }

        return $summary;
    }
}

if (!class_exists('Tipax_Tracking_Module')) {
    class_alias('Tipax_Tracking_Service', 'Tipax_Tracking_Module');
}
