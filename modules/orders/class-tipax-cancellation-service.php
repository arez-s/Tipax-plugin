<?php
if (!defined('ABSPATH')) {
    exit;
}

class Tipax_Cancellation_Service
{
    public const CANCELLED_STATUS_ID = 34;

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

    public function get_order_tracking_codes($order)
    {
        if (!$order instanceof WC_Order) {
            $order = wc_get_order($order);
        }
        if (!$order) {
            return [];
        }

        $codes = [];
        foreach ($order->get_items() as $item) {
            foreach (tipax_normalize_tracking_codes($item->get_meta('tipax_tracking_code')) as $code) {
                $codes[$code] = $code;
            }
        }

        foreach (tipax_normalize_tracking_codes($order->get_meta('_tipax_tracking_codes', true)) as $code) {
            $codes[$code] = $code;
        }

        $primaryCode = tipax_normalize_tracking_code($order->get_meta('_tipax_tracking_primary_code', true));
        if ($primaryCode !== '') {
            $codes[$primaryCode] = $primaryCode;
        }

        return array_values($codes);
    }

    public function get_cancelled_tracking_codes($order)
    {
        if (!$order instanceof WC_Order) {
            $order = wc_get_order($order);
        }

        if (!$order) {
            return [];
        }

        if (self::is_cancelled_status_id($order->get_meta('_tipax_saved_status_id', true))) {
            return $this->get_order_tracking_codes($order);
        }

        return tipax_normalize_tracking_codes($order->get_meta('_tipax_cancelled_tracking_codes', true));
    }

    public static function is_cancelled_status_id($statusId)
    {
        return (int) $statusId === self::CANCELLED_STATUS_ID;
    }

    public function cancel_tracking_code($trackingCode, $accessToken = null)
    {
        $trackingCode = tipax_normalize_tracking_code($trackingCode);
        if ($trackingCode === '') {
            return [
                'success' => false,
                'tracking_code' => '',
                'message' => 'کد رهگیری معتبر نیست.',
                'response' => null,
            ];
        }

        $accessToken = $accessToken ?: $this->ensure_access_token();
        if (!$accessToken) {
            return [
                'success' => false,
                'tracking_code' => $trackingCode,
                'message' => 'توکن دسترسی تیپاکس در دسترس نیست.',
                'response' => null,
            ];
        }

        $response = $this->request->apiRequest(
            'Parcels/Cancel/' . rawurlencode($trackingCode),
            [],
            'PUT',
            $accessToken,
            [
                'timeout' => 45,
                'return_transport_error' => true,
                'content_type' => 'application/json',
            ]
        );

        $success = is_object($response)
            && isset($response->tipax_request_success)
            && $response->tipax_request_success === true;

        return [
            'success' => $success,
            'tracking_code' => $trackingCode,
            'message' => $success
                ? 'مرسوله با موفقیت ابطال شد.'
                : tipax_extract_tipax_response_message($response, 'ابطال مرسوله در تیپاکس ناموفق بود.'),
            'response' => $response,
        ];
    }

    public function cancel_order($order)
    {
        if (!$order instanceof WC_Order) {
            $order = wc_get_order($order);
        }
        if (!$order) {
            return $this->build_order_error('invalid_order', 'سفارش یافت نشد.');
        }
        if (!$order->has_shipping_method('tipax_shipping_method')) {
            return $this->build_order_error('invalid_shipping_method', 'روش ارسال این سفارش تیپاکس نیست.');
        }

        $allCodes = $this->get_order_tracking_codes($order);
        if (empty($allCodes)) {
            return $this->build_order_error('missing_tracking_code', 'این سفارش کد رهگیری تیپاکس ندارد.');
        }

        $alreadyCancelled = $this->get_cancelled_tracking_codes($order);
        $pendingCodes = array_values(array_diff($allCodes, $alreadyCancelled));
        if (empty($pendingCodes)) {
            return [
                'success' => true,
                'partial' => false,
                'already_cancelled' => true,
                'message' => 'همه مرسوله‌های این سفارش قبلاً ابطال شده‌اند.',
                'cancelled_codes' => $alreadyCancelled,
                'failed' => [],
                'status' => 'ابطال شده',
            ];
        }

        $accessToken = $this->ensure_access_token();
        if (!$accessToken) {
            return $this->build_order_error('missing_access_token', 'توکن دسترسی تیپاکس در دسترس نیست.');
        }

        $cancelledNow = [];
        $failed = [];
        foreach ($pendingCodes as $trackingCode) {
            $result = $this->cancel_tracking_code($trackingCode, $accessToken);
            if ($result['success']) {
                $cancelledNow[] = $trackingCode;
            } else {
                $failed[$trackingCode] = $result['message'];
            }
        }

        $cancelledCodes = array_values(array_unique(array_merge($alreadyCancelled, $cancelledNow)));
        $remainingCodes = array_values(array_diff($allCodes, $cancelledCodes));
        $status = empty($remainingCodes) ? 'ابطال شده' : (!empty($cancelledCodes) ? 'ابطال جزئی' : 'ابطال ناموفق');
        $success = empty($failed) && !empty($cancelledNow);
        $partial = !empty($cancelledNow) && !empty($failed);

        if (!empty($cancelledNow)) {
            $order->update_meta_data('_tipax_cancelled_tracking_codes', $cancelledCodes);
            $order->update_meta_data('_tipax_cancelled_at', current_time('mysql'));
            $order->update_meta_data('_tipax_saved_status', $status);
        }

        $order->update_meta_data('_tipax_cancellation_last_result', [
            'cancelled_codes' => $cancelledNow,
            'failed' => $failed,
            'status' => $status,
            'checked_at' => current_time('mysql'),
        ]);

        $noteParts = [];
        if (!empty($cancelledNow)) {
            $noteParts[] = 'کدهای ابطال‌شده: ' . implode('، ', $cancelledNow);
        }
        if (!empty($failed)) {
            $noteParts[] = 'کدهای ناموفق: ' . implode('، ', array_keys($failed));
        }
        if (!empty($noteParts)) {
            $order->add_order_note('نتیجه ابطال مرسولات تیپاکس - ' . implode(' | ', $noteParts));
        }
        $order->save();

        if ($success) {
            $message = count($cancelledNow) > 1
                ? count($cancelledNow) . ' مرسوله با موفقیت ابطال شدند.'
                : 'مرسوله با موفقیت ابطال شد.';
        } elseif ($partial) {
            $message = count($cancelledNow) . ' مرسوله ابطال شد و ابطال ' . count($failed) . ' مرسوله ناموفق بود.';
        } else {
            $message = !empty($failed)
                ? reset($failed)
                : 'ابطال مرسولات سفارش ناموفق بود.';
        }

        return [
            'success' => $success,
            'partial' => $partial,
            'already_cancelled' => false,
            'message' => $message,
            'cancelled_codes' => $cancelledCodes,
            'cancelled_now' => $cancelledNow,
            'failed' => $failed,
            'remaining_codes' => $remainingCodes,
            'status' => $status,
        ];
    }

    private function build_order_error($errorCode, $message)
    {
        return [
            'success' => false,
            'partial' => false,
            'already_cancelled' => false,
            'error_code' => $errorCode,
            'message' => $message,
            'cancelled_codes' => [],
            'failed' => [],
            'status' => '',
        ];
    }
}
