<?php
if (!defined('ABSPATH')) {
    exit;
}

if (class_exists('Tipax_Order_Service')) {
    return;
}

class Tipax_Order_Service
{
    private const ORDER_REGISTRATION_API_PATH = 'Orders/PluginOrder';

    private function get_shipping_service_id($shippingItems)
    {
        if (empty($shippingItems)) {
            return null;
        }

        $firstShip = reset($shippingItems);
        if (!$firstShip || !method_exists($firstShip, 'get_meta')) {
            return null;
        }

        $serviceId = $firstShip->get_meta('serviceId', true);
        if ($serviceId === '' || $serviceId === null) {
            return null;
        }

        return (int) $serviceId;
    }

    private function shipping_items_use_offline_pricing($shippingItems): bool
    {
        if (empty($shippingItems)) {
            return false;
        }

        $firstShip = reset($shippingItems);
        if (!$firstShip || !method_exists($firstShip, 'get_meta')) {
            return false;
        }

        return $firstShip->get_meta('tipax_offline_pricing', true) === 'yes';
    }

    private function validate_same_day_order_packages(
        array $packages,
        $base_service_id,
        $destination_city_id,
        $destination_address,
        $order,
        array $settings
    ): array {
        $origin_city_id = (int) $base_service_id === 7
            ? (int) $destination_city_id
            : 0;

        return tipax_get_same_day_eligibility(
            $packages,
            $origin_city_id,
            $destination_city_id,
            $settings,
            [
                'check_enabled' => false,
                'timestamp' => tipax_get_order_created_timestamp($order),
                'has_destination_address' => trim((string) $destination_address) !== '',
            ]
        );
    }

    private function order_has_tipax_registration(WC_Order $order): bool
    {
        foreach ($order->get_items() as $item) {
            $trackingCode = trim((string) $item->get_meta('tipax_tracking_code'));
            $tipaxOrderId = trim((string) $item->get_meta('tipax_order_id'));

            if ($trackingCode !== '' || $tipaxOrderId !== '') {
                return true;
            }
        }

        return false;
    }

    private function get_online_order_request_options($pluginSettings): array
    {
        return [
            'timeout' => tipax_get_tipax_request_timeout($pluginSettings),
            'return_transport_error' => true,
        ];
    }

    private function get_manual_retry_request_options(): array
    {
        return [
            'timeout' => 45,
            'return_transport_error' => true,
        ];
    }

    private function build_retry_base_result($order_id, $success = false, array $messages = [], array $extra = []): array
    {
        return array_merge([
            'success' => (bool) $success,
            'messages' => $messages,
            'order_id' => $order_id,
            'tracking_codes' => [],
            'tipax_order_id' => null,
            'http_status' => null,
        ], $extra);
    }

    private function ensure_access_token($request, array $settings = [])
    {
        $accessToken = get_option('woocommerce_tipax_shipping_method_accesstoken', null);
        if (!empty($accessToken)) {
            return $accessToken;
        }

        if (!class_exists('Tipax_Auth_Service')) {
            return null;
        }

        $auth = new Tipax_Auth_Service($request, $settings);
        if (!$auth->get_access_token()) {
            $auth->check_api_key(true);
        }

        return $auth->get_access_token();
    }

    private function parse_numeric_input($value): float
    {
        if (is_string($value)) {
            $value = convertToEnglishNumber($value);
            $value = str_replace(['٬', '٫', ','], ['', '.', ''], $value);
        }

        if (!is_scalar($value)) {
            return 0.0;
        }

        $value = trim((string) $value);
        return is_numeric($value) ? (float) $value : 0.0;
    }

    private function format_numeric_output($value, $decimals = 2): string
    {
        $formatted = number_format((float) $value, $decimals, '.', '');
        return rtrim(rtrim($formatted, '0'), '.');
    }

    private function first_non_empty_order_value(...$values): string
    {
        foreach ($values as $value) {
            if (!is_scalar($value)) {
                continue;
            }

            $value = trim((string) $value);
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function join_order_address_lines($line1, $line2): string
    {
        $parts = [];

        foreach ([$line1, $line2] as $part) {
            if (!is_scalar($part)) {
                continue;
            }

            $part = trim((string) $part);
            if ($part !== '') {
                $parts[] = $part;
            }
        }

        return implode(' ', $parts);
    }

    private function get_effective_order_recipient(WC_Order $order): array
    {
        $shipping_full_address = $this->join_order_address_lines(
            $order->get_shipping_address_1(),
            $order->get_shipping_address_2()
        );
        $billing_full_address = $this->join_order_address_lines(
            $order->get_billing_address_1(),
            $order->get_billing_address_2()
        );

        $first_name = $this->first_non_empty_order_value(
            $order->get_shipping_first_name(),
            $order->get_billing_first_name()
        );
        $last_name = $this->first_non_empty_order_value(
            $order->get_shipping_last_name(),
            $order->get_billing_last_name()
        );
        $location = function_exists('tipax_resolve_order_address_location')
            ? tipax_resolve_order_address_location($order, 'shipping')
            : [];
        $city = !empty($location['city_title'])
            ? (string) $location['city_title']
            : $this->first_non_empty_order_value($order->get_shipping_city(), $order->get_billing_city());
        $state = !empty($location['state_title'])
            ? (string) $location['state_title']
            : $this->first_non_empty_order_value($order->get_shipping_state(), $order->get_billing_state());

        return [
            'first_name' => $first_name,
            'last_name' => $last_name,
            'full_name' => trim($first_name . ' ' . $last_name),
            'address_1' => $this->first_non_empty_order_value(
                $shipping_full_address,
                $billing_full_address,
                $order->get_shipping_address_1(),
                $order->get_billing_address_1()
            ),
            'city' => $city,
            'state' => $state,
            'country' => $this->first_non_empty_order_value(
                $order->get_shipping_country(),
                $order->get_billing_country()
            ),
            'postcode' => $this->first_non_empty_order_value(
                $order->get_shipping_postcode(),
                $order->get_billing_postcode()
            ),
        ];
    }

    private function get_order_items_total_store(WC_Order $order): float
    {
        $items_total_store = 0.0;
        foreach ($order->get_items() as $line_item) {
            $items_total_store += (float) $line_item->get_total();
        }

        return $items_total_store;
    }

    private function get_order_metric_snapshot(
        WC_Order $order,
        $weight_unit,
        $dimension_unit,
        $avgWeight,
        $avgLength,
        $avgWidth,
        $avgHeight
    ): array {
        $unit_ratio = tipax_dim_ratio_to_cm($dimension_unit);
        $default_package_dimensions = tipax_normalize_package_dimensions($avgLength, $avgWidth, $avgHeight);
        $total_item_qty = 0;
        $total_weight_kg = 0.0;
        $total_volume_cm3 = 0.0;
        $single_item_snapshot = null;

        foreach ($order->get_items() as $item) {
            $qty = (int) $item->get_quantity();
            if ($qty <= 0) {
                continue;
            }

            $product = $item->get_product();
            if (!$product) {
                continue;
            }

            $rawW = is_numeric($product->get_weight()) ? (float) $product->get_weight() : null;
            $rawL = is_numeric($product->get_length()) ? (float) $product->get_length() : null;
            $rawH = is_numeric($product->get_height()) ? (float) $product->get_height() : null;
            $rawD = is_numeric($product->get_width()) ? (float) $product->get_width() : null;

            $item_weight_kg = $rawW !== null && $rawW > 0
                ? tipax_to_kg($rawW, $weight_unit)
                : (is_numeric($avgWeight) && (float) $avgWeight > 0 ? (float) $avgWeight : 6.0);

            if ($default_package_dimensions !== null) {
                $length_cm = $default_package_dimensions['length'];
                $width_cm = $default_package_dimensions['width'];
                $height_cm = $default_package_dimensions['height'];
            } else {
                $length_cm = is_numeric($avgLength) && (float) $avgLength > 0
                    ? (float) $avgLength
                    : ($rawL !== null && $rawL > 0 ? $rawL * $unit_ratio : 45.0);
                $width_cm = is_numeric($avgWidth) && (float) $avgWidth > 0
                    ? (float) $avgWidth
                    : ($rawD !== null && $rawD > 0 ? $rawD * $unit_ratio : 45.0);
                $height_cm = is_numeric($avgHeight) && (float) $avgHeight > 0
                    ? (float) $avgHeight
                    : ($rawH !== null && $rawH > 0 ? $rawH * $unit_ratio : 45.0);
            }

            if ($item_weight_kg <= 0 || $length_cm <= 0 || $width_cm <= 0 || $height_cm <= 0) {
                return [
                    'valid' => false,
                    'message' => 'ابعاد یا وزن محصولات برای ساخت بسته معتبر نیست.',
                ];
            }

            $total_item_qty += $qty;
            $total_weight_kg += $item_weight_kg * $qty;
            $total_volume_cm3 += ($length_cm * $width_cm * $height_cm) * $qty;

            if ($total_item_qty === $qty && $qty === 1) {
                $single_item_snapshot = [
                    'weight_kg' => $item_weight_kg,
                    'length_cm' => $length_cm,
                    'width_cm' => $width_cm,
                    'height_cm' => $height_cm,
                ];
            }
        }

        if ($total_item_qty <= 0 || $total_weight_kg <= 0 || $total_volume_cm3 <= 0) {
            return [
                'valid' => false,
                'message' => 'اطلاعات کافی برای محاسبه وزن و ابعاد سفارش وجود ندارد.',
            ];
        }

        if ($total_item_qty === 1 && is_array($single_item_snapshot)) {
            return [
                'valid' => true,
                'weight_kg' => $single_item_snapshot['weight_kg'],
                'length_cm' => $single_item_snapshot['length_cm'],
                'width_cm' => $single_item_snapshot['width_cm'],
                'height_cm' => $single_item_snapshot['height_cm'],
                'total_volume_cm3' => $single_item_snapshot['length_cm'] * $single_item_snapshot['width_cm'] * $single_item_snapshot['height_cm'],
                'total_item_qty' => $total_item_qty,
            ];
        }

        if ($default_package_dimensions !== null) {
            return [
                'valid' => true,
                'weight_kg' => $total_weight_kg,
                'length_cm' => $default_package_dimensions['length'],
                'width_cm' => $default_package_dimensions['width'],
                'height_cm' => $default_package_dimensions['height'],
                'total_volume_cm3' => $total_volume_cm3,
                'total_item_qty' => $total_item_qty,
            ];
        }

        $side_cm = pow($total_volume_cm3, 1 / 3);

        return [
            'valid' => true,
            'weight_kg' => $total_weight_kg,
            'length_cm' => $side_cm,
            'width_cm' => $side_cm,
            'height_cm' => $side_cm,
            'total_volume_cm3' => $total_volume_cm3,
            'total_item_qty' => $total_item_qty,
        ];
    }

    private function get_retry_order_context($order_id): array
    {
        $request = new Request();
        $order = wc_get_order($order_id);
        if (!$order || !$order->has_shipping_method('tipax_shipping_method')) {
            return [
                'success' => false,
                'messages' => ['سفارش معتبر نیست یا با روش ارسال تیپاکس ثبت نشده است.'],
            ];
        }

        $plugin_settings = get_option('woocommerce_tipax_shipping_method_settings', []);
        if (!is_array($plugin_settings)) {
            $plugin_settings = [];
        }

        $accessToken = $this->ensure_access_token($request, $plugin_settings);
        if (!$accessToken) {
            return [
                'success' => false,
                'messages' => ['توکن دسترسی تیپاکس در دسترس نیست. ابتدا اتصال افزونه را بررسی کنید.'],
            ];
        }

        $order_data = $order->get_data();
        $orderShippingGetItems = $order->get_items('shipping');
        $recipient = $this->get_effective_order_recipient($order);
        $serviceId = $this->get_shipping_service_id($orderShippingGetItems);

        $order_receiver_phone = $this->resolve_receiver_phone($order);
        $order_receiver_plaque = null;
        foreach ($order_data['meta_data'] as $meta) {
            if ($meta->key === 'plaque') {
                $order_receiver_plaque = $meta->value;
                break;
            }
        }

        $order_shipping_first_name = $recipient['first_name'];
        $order_shipping_last_name = $recipient['last_name'];
        $order_billing_phone = $this->first_non_empty_order_value($order_data['billing']['phone']);
        $order_shipping_postcode = $recipient['postcode'];
        $order_shipping_address_1 = $recipient['address_1'];
        $order_shipping_city = $recipient['city'];
        $order_shipping_country = $recipient['country'];
        $order_shipping_state = $recipient['state'];

        $states = $order_shipping_country !== '' ? WC()->countries->get_states($order_shipping_country) : [];
        $stateTextShipping = isset($states[$order_shipping_state]) ? $states[$order_shipping_state] : $order_shipping_state;
        $order_shipping_city_id = tipax_get_order_destination_city_id($order, $order_shipping_city, $stateTextShipping);
        if (empty($order_shipping_city_id)) {
            return [
                'success' => false,
                'messages' => ['شهر مقصد سفارش برای تیپاکس قابل تشخیص نیست.'],
            ];
        }

        $shipping_instance_id = null;
        foreach ($orderShippingGetItems as $shipping_item) {
            $shipping_instance_id = $shipping_item->get_instance_id();
            break;
        }

        $shipping_settings = get_option("woocommerce_tipax_shipping_method_" . $shipping_instance_id . "_settings", null);
        if ($shipping_settings === null) {
            $shipping_settings = $plugin_settings;
        }

        $settlement_context = tipax_get_order_settlement_context($order, $plugin_settings);

        $pickup_type = tipax_normalize_pickup_type($plugin_settings['pickup_type'] ?? 10);
        $averages = tipax_get_average_package_settings($plugin_settings);

        return [
            'success' => true,
            'request' => $request,
            'order' => $order,
            'order_id' => $order_id,
            'orderShippingGetItems' => $orderShippingGetItems,
            'serviceId' => $serviceId,
            'accessToken' => $accessToken,
            'plugin_settings' => $plugin_settings,
            'shipping_settings' => $shipping_settings,
            'origin_address_id' => get_option('tipax_shipping_address'),
            'order_receiver_phone' => $order_receiver_phone,
            'order_receiver_plaque' => $order_receiver_plaque,
            'order_shipping_first_name' => $order_shipping_first_name,
            'order_shipping_last_name' => $order_shipping_last_name,
            'order_billing_phone' => $order_billing_phone,
            'order_shipping_postcode' => $order_shipping_postcode,
            'order_shipping_address_1' => $order_shipping_address_1,
            'order_shipping_city' => $order_shipping_city,
            'order_shipping_city_id' => $order_shipping_city_id,
            'order_shipping_state' => $stateTextShipping,
            'order_shipping_country' => $order_shipping_country,
            'weight_unit' => get_option('woocommerce_weight_unit'),
            'dimension_unit' => get_option('woocommerce_dimension_unit'),
            'woocommerce_currency' => $order->get_currency(),
            'checkout_method' => (int) $settlement_context['payment_type'],
            'cod_api_amount' => (float) $settlement_context['cod_api_amount'],
            'cod_enabled' => (bool) $settlement_context['cod_enabled'],
            'api_shipping_amount' => (float) $settlement_context['api_shipping_amount'],
            'receiver_shipping_amount' => (float) $settlement_context['receiver_shipping_amount'],
            'pickup_type' => $pickup_type,
            'selected_packing' => tipax_get_selected_packing($plugin_settings),
            'avgWeight' => $averages['weight'],
            'avgLength' => $averages['length'],
            'avgWidth' => $averages['width'],
            'avgHeight' => $averages['height'],
        ];
    }

    private function resolve_effective_selected_packing(array $selected_packing, array $packing_catalog, array $metric_snapshot)
    {
        $packing_id = isset($selected_packing['id']) ? (string) $selected_packing['id'] : '';
        if ($packing_id === '' || tipax_is_no_packing_id($packing_id) || empty($packing_catalog['index'][$packing_id])) {
            return null;
        }

        $packing = $packing_catalog['index'][$packing_id];
        if (function_exists('tipax_packing_matches_weight_range') && !tipax_packing_matches_weight_range($packing, $metric_snapshot['weight_kg'])) {
            return null;
        }

        $length = isset($packing['length']) ? (float) $packing['length'] : 0.0;
        $width = isset($packing['width']) ? (float) $packing['width'] : 0.0;
        $height = isset($packing['height']) ? (float) $packing['height'] : 0.0;
        $maxLength = isset($packing['maxLength']) ? (float) $packing['maxLength'] : 0.0;
        $maxWidth = isset($packing['maxWidth']) ? (float) $packing['maxWidth'] : 0.0;
        $maxHeight = isset($packing['maxHeight']) ? (float) $packing['maxHeight'] : 0.0;
        $minLength = isset($packing['minLength']) ? (float) $packing['minLength'] : 0.0;
        $minWidth = isset($packing['minWidth']) ? (float) $packing['minWidth'] : 0.0;
        $minHeight = isset($packing['minHeight']) ? (float) $packing['minHeight'] : 0.0;

        if ($length <= 0 || $width <= 0 || $height <= 0) {
            return null;
        }

        return [
            'id' => (int) $packing['id'],
            'title' => (string) $packing['title'],
            'label' => (string) $packing['label'],
            'packType' => (int) $packing['packType'],
            'packageContentId' => isset($packing['packageContentId'])
                ? (int) $packing['packageContentId']
                : tipax_get_package_content_id_for_pack_type($packing['packType']),
            'length' => $length,
            'width' => $width,
            'height' => $height,
            'maxLength' => $maxLength,
            'maxWidth' => $maxWidth,
            'maxHeight' => $maxHeight,
            'minLength' => $minLength,
            'minWidth' => $minWidth,
            'minHeight' => $minHeight,
            'minWeight' => isset($packing['minWeight']) ? (float) $packing['minWeight'] : 0.0,
            'maxWeight' => isset($packing['maxWeight']) ? (float) $packing['maxWeight'] : 0.0,
        ];
    }

    private function normalize_retry_form_input(array $raw_input): array
    {
        $origin_address_id = isset($raw_input['origin_address_id']) ? sanitize_text_field(wp_unslash($raw_input['origin_address_id'])) : '';
        if ($origin_address_id === '') {
            return [
                'success' => false,
                'messages' => ['آدرس مبدا باید انتخاب شود.'],
            ];
        }

        $weight_kg = $this->parse_numeric_input($raw_input['weight'] ?? 0);
        if ($weight_kg <= 0) {
            return [
                'success' => false,
                'messages' => ['وزن مرسوله باید بیشتر از صفر باشد.'],
            ];
        }

        $packing_catalog = function_exists('tipax_get_admin_packing_catalog')
            ? tipax_get_admin_packing_catalog()
            : ['index' => [], 'options' => []];

        $packing_id = isset($raw_input['packing_id']) ? sanitize_text_field(wp_unslash($raw_input['packing_id'])) : '';
        $selected_packing = null;
        $packing_label = 'بدون بسته‌بندی';
        $length_cm = $this->parse_numeric_input($raw_input['length'] ?? 0);
        $width_cm = $this->parse_numeric_input($raw_input['width'] ?? 0);
        $height_cm = $this->parse_numeric_input($raw_input['height'] ?? 0);

        if ($packing_id !== '' && !tipax_is_no_packing_id($packing_id)) {
            if (empty($packing_catalog['index'][$packing_id])) {
                return [
                    'success' => false,
                    'messages' => ['بسته‌بندی انتخاب‌شده معتبر نیست.'],
                ];
            }

            $packing = $packing_catalog['index'][$packing_id];
            if (function_exists('tipax_packing_matches_weight_range') && !tipax_packing_matches_weight_range($packing, $weight_kg)) {
                return [
                    'success' => false,
                    'messages' => ['بسته‌بندی انتخاب‌شده با وزن فعلی مرسوله سازگار نیست.'],
                ];
            }

            $packing_length_cm = isset($packing['length']) ? (float) $packing['length'] : 0.0;
            $packing_width_cm = isset($packing['width']) ? (float) $packing['width'] : 0.0;
            $packing_height_cm = isset($packing['height']) ? (float) $packing['height'] : 0.0;
            $max_length_cm = isset($packing['maxLength']) ? (float) $packing['maxLength'] : 0.0;
            $max_width_cm = isset($packing['maxWidth']) ? (float) $packing['maxWidth'] : 0.0;
            $max_height_cm = isset($packing['maxHeight']) ? (float) $packing['maxHeight'] : 0.0;
            $min_length_cm = isset($packing['minLength']) ? (float) $packing['minLength'] : 0.0;
            $min_width_cm = isset($packing['minWidth']) ? (float) $packing['minWidth'] : 0.0;
            $min_height_cm = isset($packing['minHeight']) ? (float) $packing['minHeight'] : 0.0;

            if ($packing_length_cm <= 0 || $packing_width_cm <= 0 || $packing_height_cm <= 0) {
                return [
                    'success' => false,
                    'messages' => ['ابعاد بسته‌بندی انتخاب‌شده از تیپاکس دریافت نشد.'],
                ];
            }

            $selected_packing = [
                'id' => (int) $packing['id'],
                'title' => (string) $packing['title'],
                'packType' => (int) $packing['packType'],
                'packageContentId' => isset($packing['packageContentId'])
                    ? (int) $packing['packageContentId']
                    : tipax_get_package_content_id_for_pack_type($packing['packType']),
                'length' => $packing_length_cm,
                'width' => $packing_width_cm,
                'height' => $packing_height_cm,
                'maxLength' => $max_length_cm,
                'maxWidth' => $max_width_cm,
                'maxHeight' => $max_height_cm,
                'minLength' => $min_length_cm,
                'minWidth' => $min_width_cm,
                'minHeight' => $min_height_cm,
                'minWeight' => isset($packing['minWeight']) ? (float) $packing['minWeight'] : 0.0,
                'maxWeight' => isset($packing['maxWeight']) ? (float) $packing['maxWeight'] : 0.0,
            ];
            $packing_label = (string) $packing['label'];
            $length_cm = $packing_length_cm;
            $width_cm = $packing_width_cm;
            $height_cm = $packing_height_cm;
        } elseif ($length_cm <= 0 || $width_cm <= 0 || $height_cm <= 0) {
            return [
                'success' => false,
                'messages' => ['طول، عرض و ارتفاع واقعی مرسوله باید تکمیل شوند.'],
            ];
        }

        return [
            'success' => true,
            'data' => [
                'origin_address_id' => $origin_address_id,
                'weight_kg' => $weight_kg,
                'length_cm' => $length_cm,
                'width_cm' => $width_cm,
                'height_cm' => $height_cm,
                'selected_packing' => $selected_packing,
                'packing_label' => $packing_label,
            ],
        ];
    }

    private function build_manual_retry_packages(array $context, array $manual_input): array
    {
        $is_same_city = $this->same_city(
            $context['request'],
            $context['accessToken'],
            $manual_input['origin_address_id'],
            $context['order_shipping_city_id']
        );
        $base_service_id = $is_same_city ? 7 : 5;
        $serviceId = (int) ($context['serviceId'] ?? 0) === tipax_get_same_day_service_id()
            ? tipax_get_same_day_service_id()
            : $base_service_id;

        $total_weight_kg = (float) $manual_input['weight_kg'];
        $length_cm = (float) $manual_input['length_cm'];
        $width_cm = (float) $manual_input['width_cm'];
        $height_cm = (float) $manual_input['height_cm'];
        $total_volume_cm3 = $length_cm * $width_cm * $height_cm;
        $items_total_store = $this->get_order_items_total_store($context['order']);

        if ($total_weight_kg <= 0 || $length_cm <= 0 || $width_cm <= 0 || $height_cm <= 0 || $total_volume_cm3 <= 0) {
            return [
                'packages' => [],
                'serviceId' => $serviceId,
                'message' => 'اطلاعات وزن یا ابعاد برای ساخت بسته معتبر نیست.',
            ];
        }

        $MAX_DIM_CM = 200.0;
        $PER_ITEM_MAX_KG = tipax_get_package_weight_limit($manual_input['selected_packing'] ?? null);
        $CUBE_CM3 = $MAX_DIM_CM * $MAX_DIM_CM * $MAX_DIM_CM;

        $make_pkg = function ($weight_kg, $pkg_length, $pkg_width, $pkg_height, $value_store) use ($context, $manual_input) {
            $pkg = new stdClass;
            $pkg->originId = $manual_input['origin_address_id'];

            $pkg->destination = new stdClass;
            $pkg->destination->cityId = $context['order_shipping_city_id'];
            $pkg->destination->districtId = 0;
            $pkg->destination->fullAddress = $context['order_shipping_address_1'] ?? null;
            $pkg->destination->unit = null;
            $pkg->destination->no = $context['order_receiver_plaque'];
            $pkg->destination->floor = null;
            $pkg->destination->postalCode = $context['order_shipping_postcode'];

            $pkg->destination->beneficiary = new stdClass;
            $pkg->destination->beneficiary->fullName = trim($context['order_shipping_first_name'] . ' ' . $context['order_shipping_last_name']);
            $pkg->destination->beneficiary->mobile = $context['order_receiver_phone'];
            $pkg->destination->beneficiary->phone = $context['order_billing_phone'];

            $pkg->receiver = $pkg->destination->beneficiary;
            $pkg->weight = (float) $weight_kg;
            $pkg->length = (float) $pkg_length;
            $pkg->width = (float) $pkg_width;
            $pkg->height = (float) $pkg_height;
            $pkg->packageValue = tipax_store_value_to_api_value($value_store, $context['woocommerce_currency']);

            $pkg->description = '';
            $pkg->enableLabelPrivacy = true;
            $pkg->paymentType = $context['checkout_method'];
            $pkg->cod = 0;
            $pkg->pickupType = (int) $context['pickup_type'];
            $pkg->distributionType = 10;
            $pkg->parcelBookId = 0;
            $pkg->isUnusual = false;

            return $pkg;
        };

        $max_side = max($length_cm, $width_cm, $height_cm);
        $packages = [];
        $number_by_kg = (int) ceil($total_weight_kg / $PER_ITEM_MAX_KG);
        $number_by_dim = (int) ceil($max_side / $MAX_DIM_CM);
        $number_by_vol = (int) ceil($total_volume_cm3 / $CUBE_CM3);
        $package_count = max(1, max($number_by_kg, max($number_by_dim, $number_by_vol)));
        $weight_only_split = $package_count > 1 && $number_by_kg === $package_count && $number_by_dim <= 1 && $number_by_vol <= 1;

        $per_package_weight = $total_weight_kg / $package_count;
        $per_package_value = $items_total_store / $package_count;
        $remaining_volume = $total_volume_cm3;

        for ($i = 1; $i <= $package_count; $i++) {
            if ($package_count === 1 || $weight_only_split) {
                $packages[] = $make_pkg($per_package_weight, $length_cm, $width_cm, $height_cm, $per_package_value);
                continue;
            }

            if ($i < $package_count) {
                $pkg_length = $MAX_DIM_CM;
                $pkg_width = $MAX_DIM_CM;
                $pkg_height = $MAX_DIM_CM;
                $remaining_volume -= $CUBE_CM3;
                if ($remaining_volume < 0) {
                    $remaining_volume = 0;
                }
            } else {
                $side = $remaining_volume > 0 ? pow($remaining_volume, 1 / 3) : $MAX_DIM_CM;
                if ($side > $MAX_DIM_CM) {
                    $side = $MAX_DIM_CM;
                }
                $pkg_length = $side;
                $pkg_width = $side;
                $pkg_height = $side;
            }

            $packages[] = $make_pkg($per_package_weight, ceil($pkg_length), ceil($pkg_width), ceil($pkg_height), $per_package_value);
        }

        if (!empty($manual_input['selected_packing'])) {
            $packing_applied = tipax_apply_selected_packing_to_packages($packages, $manual_input['selected_packing']);
            if (!$packing_applied) {
                return [
                    'packages' => [],
                    'serviceId' => $serviceId,
                    'message' => tipax_get_package_packing_error('بسته‌بندی انتخاب‌شده با مشخصات مرسوله یا اطلاعات کاتالوگ تیپاکس سازگار نیست.'),
                ];
            }
        } else {
            $packages = tipax_prepare_default_packing_packages($packages);
            if (empty($packages)) {
                return [
                    'packages' => [],
                    'serviceId' => $serviceId,
                    'message' => tipax_get_package_packing_error('سگمنت معتبر بدون بسته‌بندی برای مرسوله پیدا نشد.'),
                ];
            }
        }

        if ($serviceId === tipax_get_same_day_service_id()) {
            $same_day_eligibility = $this->validate_same_day_order_packages(
                $packages,
                $base_service_id,
                $context['order_shipping_city_id'],
                $context['order_shipping_address_1'] ?? '',
                $context['order'],
                $context['plugin_settings']
            );
            if (empty($same_day_eligibility['eligible'])) {
                return [
                    'packages' => [],
                    'serviceId' => $serviceId,
                    'message' => $same_day_eligibility['message'] ?? 'شرایط سرویس همان روز برقرار نیست.',
                ];
            }
        }

        foreach ($packages as $pkg) {
            $pkg->serviceId = $serviceId;
        }
        $packages = tipax_apply_cod_to_packages($packages, $context['cod_api_amount'] ?? 0);

        return [
            'packages' => $packages,
            'serviceId' => $serviceId,
            'packageCount' => count($packages),
        ];
    }

    private function convert_order_packages_to_pricing_payload(array $packages): stdClass
    {
        $data = new stdClass;
        $data->packageInputs = [];

        foreach ($packages as $pkg) {
            if (!is_object($pkg) || !isset($pkg->destination) || !is_object($pkg->destination)) {
                continue;
            }

            $pricing_pkg = new stdClass;
            $pricing_pkg->origin = new stdClass;
            $pricing_pkg->origin->Id = $pkg->originId;
            $pricing_pkg->destination = new stdClass;
            $pricing_pkg->destination->cityId = $pkg->destination->cityId;
            $pricing_pkg->weight = (float) $pkg->weight;
            $pricing_pkg->length = (float) $pkg->length;
            $pricing_pkg->width = (float) $pkg->width;
            $pricing_pkg->height = (float) $pkg->height;
            $pricing_pkg->packageValue = (float) $pkg->packageValue;
            $pricing_pkg->distributionType = isset($pkg->distributionType) ? (int) $pkg->distributionType : 10;
            $pricing_pkg->pickupType = isset($pkg->pickupType) ? (int) $pkg->pickupType : 10;
            $pricing_pkg->paymentType = isset($pkg->paymentType) ? (int) $pkg->paymentType : 10;
            $pricing_pkg->packingId = isset($pkg->packingId) ? (int) $pkg->packingId : 0;
            $pricing_pkg->packageContentId = isset($pkg->packageContentId) ? (int) $pkg->packageContentId : 0;
            $pricing_pkg->packType = isset($pkg->packType) ? (int) $pkg->packType : 0;
            $pricing_pkg->serviceId = isset($pkg->serviceId) ? (int) $pkg->serviceId : 0;

            $data->packageInputs[] = $pricing_pkg;
        }

        return $data;
    }

    public function get_retry_order_modal_data($order_id): array
    {
        $context = $this->get_retry_order_context($order_id);
        if (empty($context['success'])) {
            return $this->build_retry_base_result($order_id, false, $context['messages'] ?? ['دریافت اطلاعات سفارش ممکن نبود.']);
        }

        $metric_snapshot = $this->get_order_metric_snapshot(
            $context['order'],
            $context['weight_unit'],
            $context['dimension_unit'],
            $context['avgWeight'],
            $context['avgLength'],
            $context['avgWidth'],
            $context['avgHeight']
        );

        if (empty($metric_snapshot['valid'])) {
            return $this->build_retry_base_result($order_id, false, [$metric_snapshot['message'] ?? 'محاسبه وزن و ابعاد سفارش انجام نشد.']);
        }

        $packing_catalog = function_exists('tipax_get_admin_packing_catalog')
            ? tipax_get_admin_packing_catalog()
            : ['index' => [], 'options' => []];

        $selected_packing = $this->resolve_effective_selected_packing($context['selected_packing'], $packing_catalog, $metric_snapshot);
        $show_dimensions = $selected_packing === null;

        $default_length = $metric_snapshot['length_cm'];
        $default_width = $metric_snapshot['width_cm'];
        $default_height = $metric_snapshot['height_cm'];

        $address_service = new Tipax_Address_Service($context['request'], $context['plugin_settings'], new Tipax_Auth_Service($context['request'], $context['plugin_settings']));
        $address_records = tipax_get_tipax_address_list($address_service->fetch_addresses($context['accessToken']));
        $address_options = [];
        $address_index = [];

        foreach ($address_records as $record) {
            if (!is_object($record)) {
                continue;
            }

            $origin_id = tipax_get_address_origin_id($record);
            if ($origin_id === '') {
                continue;
            }

            $title = trim((string) tipax_get_address_record_field($record, 'title', ''));
            $city = trim((string) tipax_get_address_record_field($record, 'cityTitle', ''));
            $full_address = trim((string) tipax_get_address_record_field($record, 'fullAddress', ''));

            $label_parts = array_values(array_filter([$title, $city, $full_address], static function ($value) {
                return $value !== '';
            }));
            $label = !empty($label_parts) ? implode(' | ', $label_parts) : ('شناسه مبدا ' . $origin_id);

            $address_options[] = [
                'value' => (string) $origin_id,
                'label' => $label,
            ];
            $address_index[(string) $origin_id] = $label;
        }

        if (empty($address_options)) {
            return $this->build_retry_base_result($order_id, false, ['هیچ آدرس مبدا قابل استفاده‌ای از تیپاکس دریافت نشد.']);
        }

        $selected_origin_id = (string) $context['origin_address_id'];
        if ($selected_origin_id === '' || !isset($address_index[$selected_origin_id])) {
            $selected_origin_id = (string) $address_options[0]['value'];
        }

        return $this->build_retry_base_result($order_id, true, [], [
            'data' => [
                'orderId' => $order_id,
                'orderLabel' => '#' . $order_id,
                'customerName' => trim($context['order_shipping_first_name'] . ' ' . $context['order_shipping_last_name']),
                'destinationText' => implode(' | ', array_values(array_filter([
                    $context['order_shipping_city'],
                    $context['order_shipping_address_1'],
                ], static function ($value) {
                    return trim((string) $value) !== '';
                }))),
                'originAddressId' => $selected_origin_id,
                'weight' => $this->format_numeric_output($metric_snapshot['weight_kg']),
                'length' => $this->format_numeric_output($default_length),
                'width' => $this->format_numeric_output($default_width),
                'height' => $this->format_numeric_output($default_height),
                'defaultLength' => $this->format_numeric_output($metric_snapshot['length_cm']),
                'defaultWidth' => $this->format_numeric_output($metric_snapshot['width_cm']),
                'defaultHeight' => $this->format_numeric_output($metric_snapshot['height_cm']),
                'packingId' => $selected_packing ? (string) $selected_packing['id'] : '',
                'showDimensions' => $show_dimensions,
                'addressOptions' => $address_options,
                'packingOptions' => $packing_catalog['options'],
                'packingIndex' => $packing_catalog['index'],
                'weightUnitLabel' => 'کیلوگرم',
                'dimensionUnitLabel' => 'سانتی‌متر',
            ],
        ]);
    }

    public function preview_retry_order_pricing($order_id, array $raw_input): array
    {
        $context = $this->get_retry_order_context($order_id);
        if (empty($context['success'])) {
            return $this->build_retry_base_result($order_id, false, $context['messages'] ?? ['اطلاعات سفارش برای استعلام قیمت آماده نیست.']);
        }

        $normalized = $this->normalize_retry_form_input($raw_input);
        if (empty($normalized['success'])) {
            return $this->build_retry_base_result($order_id, false, $normalized['messages'] ?? ['اطلاعات ورودی معتبر نیست.']);
        }

        $build = $this->build_manual_retry_packages($context, $normalized['data']);
        if (empty($build['packages'])) {
            return $this->build_retry_base_result($order_id, false, [$build['message'] ?? 'ساخت بسته برای استعلام قیمت انجام نشد.']);
        }

        $pricing_service = new Tipax_Pricing_Service($context['request']);
        $pricing_payload = $this->convert_order_packages_to_pricing_payload($build['packages']);
        $response = $pricing_service->price_with_origin_address_id(
            $pricing_payload,
            $context['accessToken'],
            $this->get_manual_retry_request_options()['timeout']
        );

        if (tipax_is_tipax_transport_error_response($response)) {
            return $this->build_retry_base_result($order_id, false, tipax_collect_tipax_response_messages($response, 'استعلام قیمت از تیپاکس با خطای ارتباطی مواجه شد.'));
        }

        if (is_wp_error($response)) {
            return $this->build_retry_base_result($order_id, false, [$response->get_error_message()]);
        }

        if (is_object($response) && isset($response->status)) {
            $http_status = (int) $response->status;
        } else {
            $http_status = null;
        }

        if (
            (is_object($response) && isset($response->errors) && is_array($response->errors)) ||
            (is_object($response) && isset($response->status) && (int) $response->status === 400) ||
            (is_string($response) && trim($response) !== '')
        ) {
            return $this->build_retry_base_result(
                $order_id,
                false,
                tipax_collect_tipax_response_messages($response, 'تیپاکس برای استعلام قیمت پاسخ معتبری نداد.'),
                ['http_status' => $http_status]
            );
        }

        $aggregated_rates = $pricing_service->aggregate_rates($response);
        if (empty($aggregated_rates)) {
            return $this->build_retry_base_result($order_id, false, ['هیچ نرخ معتبری از تیپاکس برای این سفارش برنگشت.'], ['http_status' => $http_status]);
        }

        $rate_rows = [];
        foreach ($aggregated_rates as $rate) {
            $normalized_price = $pricing_service->normalize_store_price($rate['finalPrice'], $context['woocommerce_currency']);
            $rate_rows[] = [
                'serviceId' => isset($rate['serviceId']) ? (int) $rate['serviceId'] : 0,
                'serviceTitle' => isset($rate['serviceTitle']) && $rate['serviceTitle'] !== '' ? (string) $rate['serviceTitle'] : 'سرویس تیپاکس',
                'cost' => $normalized_price['cost'],
                'currencyLabel' => $normalized_price['currency_label'],
            ];
        }

        usort($rate_rows, static function ($a, $b) {
            return $a['cost'] <=> $b['cost'];
        });

        return $this->build_retry_base_result($order_id, true, ['استعلام قیمت با موفقیت انجام شد.'], [
            'http_status' => $http_status,
            'rates' => $rate_rows,
            'package_count' => count($build['packages']),
            'input' => $normalized['data'],
        ]);
    }

    public function same_city($request, $accessToken, $originAddressesId, $destinationCityId): bool
    {
        $address_module = new Tipax_Address_Service($request);
        $resp = $address_module->fetch_addresses($accessToken);
        if (!is_array($resp)) {
            return false;
        }

        // Index by addressesId (NOT by id)
        $byAddressesId = [];
        $default = null;
        foreach ($resp as $addr) {
            if (isset($addr->addressesId)) {
                $byAddressesId[(int)$addr->addressesId] = $addr;
            }
            if (!empty($addr->isDefault)) {
                $default = $addr;
            }
        }

        // Fallback to default if origin not provided
        if (empty($originAddressesId) && $default) {
            $originAddressesId = (int)$default->addressesId;
        }

        if (!isset($byAddressesId[(int)$originAddressesId])) {
            return false;
        }

        $origin = $byAddressesId[(int)$originAddressesId];
        $originCity = $origin->address->cityETipaxId ?? null;
        return (isset($originCity) && (int)$originCity === (int)$destinationCityId);
    }

    public function build_packages_for_order(
        WC_Order $order,
        $request,
        $accessToken,
        $shppingAddress,               // addressesId (origin)
        $order_shipping_city_id,
        $order_shipping_address_1,
        $order_shipping_postcode,
        $order_receiver_plaque,
        $order_shipping_first_name,
        $order_shipping_last_name,
        $order_receiver_phone,
        $order_billing_phone,
        $woocommerce_currency,
        $weight_unit,
        $dimension_unit,
        $avgWeight,
        $avgLength,
        $avgWidth,
        $avgHeight,
        $checkout_method,
        $pickup_type = 10,
        $selected_packing = null,
        $cod_api_amount = 0
    ) {
        // Decide serviceId (same-city → 7, otherwise 5)
        $serviceId = $this->same_city($request, $accessToken, $shppingAddress, $order_shipping_city_id) ? 7 : 5;

        // Units
        $unit_ratio = tipax_dim_ratio_to_cm($dimension_unit);
        $has_selected_packing = is_array($selected_packing)
            && !empty($selected_packing['id'])
            && !tipax_is_no_packing_id($selected_packing['id']);
        $default_package_dimensions = !$has_selected_packing
            ? tipax_normalize_package_dimensions($avgLength, $avgWidth, $avgHeight)
            : null;
        $calculation_dimensions = $has_selected_packing
            ? tipax_normalize_package_dimensions(
                $selected_packing['length'] ?? 0,
                $selected_packing['width'] ?? 0,
                $selected_packing['height'] ?? 0
            )
            : $default_package_dimensions;

        // Totals
        $total_item_qty        = 0;
        $total_weight_kg       = 0.0;
        $total_volume_cm3      = 0.0;
        $items_total_store     = 0.0;

        // Single item snapshot (for precise single-item splitting)
        $single_item_snapshot = null;

        foreach ($order->get_items() as $li) {
            $items_total_store += (float)$li->get_total();
        }

        foreach ($order->get_items() as $it) {
            $qty     = (int)$it->get_quantity();
            if ($qty <= 0) continue;

            $product = $it->get_product();
            if (!$product) continue;

            $rawW = is_numeric($product->get_weight()) ? (float)$product->get_weight() : null;
            $rawL = is_numeric($product->get_length()) ? (float)$product->get_length() : null;
            $rawH = is_numeric($product->get_height()) ? (float)$product->get_height() : null;
            $rawD = is_numeric($product->get_width())  ? (float)$product->get_width()  : null;

            $item_weight_kg = $rawW !== null && $rawW > 0
                ? tipax_to_kg($rawW, $weight_unit)
                : (is_numeric($avgWeight) && (float) $avgWeight > 0 ? (float) $avgWeight : 6.0);

            if ($calculation_dimensions !== null) {
                $L_cm = $calculation_dimensions['length'];
                $W_cm = $calculation_dimensions['width'];
                $H_cm = $calculation_dimensions['height'];
            } else {
                $L_cm = is_numeric($avgLength) && (float) $avgLength > 0
                    ? (float) $avgLength
                    : ($rawL !== null && $rawL > 0 ? $rawL * $unit_ratio : 45.0);
                $W_cm = is_numeric($avgWidth) && (float) $avgWidth > 0
                    ? (float) $avgWidth
                    : ($rawD !== null && $rawD > 0 ? $rawD * $unit_ratio : 45.0);
                $H_cm = is_numeric($avgHeight) && (float) $avgHeight > 0
                    ? (float) $avgHeight
                    : ($rawH !== null && $rawH > 0 ? $rawH * $unit_ratio : 45.0);
            }

            if ($item_weight_kg <= 0 || $L_cm <= 0 || $W_cm <= 0 || $H_cm <= 0) {
                tipax_log("Tipax order: invalid product dimensions/weight detected.");
                return ['packages' => [], 'serviceId' => $serviceId];
            }

            $total_item_qty   += $qty;
            $total_weight_kg  += $item_weight_kg * $qty;
            $total_volume_cm3 += ($L_cm * $W_cm * $H_cm) * $qty;

            if ($total_item_qty === $qty && $qty === 1) {
                $single_item_snapshot = [
                    'weight_kg' => $item_weight_kg,
                    'L_cm'      => $L_cm,
                    'W_cm'      => $W_cm,
                    'H_cm'      => $H_cm,
                    'price'     => (float)$product->get_price(),
                ];
            }
        }

        // thresholds for "light"
        $threshold_store = 4000000.0; // Toman
        if (strtoupper($woocommerce_currency) === 'IRR') $threshold_store *= 10;

        // constants
        $MAX_DIM_CM      = 200.0;
        $PER_ITEM_MAX_KG = tipax_get_package_weight_limit($selected_packing);
        $CUBE_CM3        = $MAX_DIM_CM * $MAX_DIM_CM * $MAX_DIM_CM;

        // Build packages[]
        $packages = [];

        // Helper to make one package object (destination/receiver/values + meta)
        $make_pkg = function ($weight_kg, $side_cm, $value_store) use (
            $shppingAddress,
            $order_shipping_city_id,
            $order_shipping_address_1,
            $order_shipping_postcode,
            $order_receiver_plaque,
            $order_shipping_first_name,
            $order_shipping_last_name,
            $order_receiver_phone,
            $order_billing_phone,
            $woocommerce_currency,
            $checkout_method,
            $pickup_type
        ) {
            $pkg = new stdClass;
            $pkg->originId = $shppingAddress;

            $pkg->destination = new stdClass;
            $pkg->destination->cityId      = $order_shipping_city_id;
            $pkg->destination->districtId  = 0;
            $pkg->destination->fullAddress = $order_shipping_address_1 ?? null;
            $pkg->destination->unit        = null;
            $pkg->destination->no          = $order_receiver_plaque;
            $pkg->destination->floor       = null;
            $pkg->destination->postalCode  = $order_shipping_postcode;

            $pkg->destination->beneficiary = new stdClass;
            $pkg->destination->beneficiary->fullName = trim($order_shipping_first_name . ' ' . $order_shipping_last_name);
            $pkg->destination->beneficiary->mobile   = $order_receiver_phone;
            $pkg->destination->beneficiary->phone    = $order_billing_phone;

            $pkg->receiver = $pkg->destination->beneficiary;

            $pkg->weight = (float)$weight_kg;
            $pkg->length = (float)$side_cm;
            $pkg->width  = (float)$side_cm;
            $pkg->height = (float)$side_cm;

            $pkg->packageValue = tipax_store_value_to_api_value($value_store, $woocommerce_currency);

            $pkg->description         = "";
            $pkg->enableLabelPrivacy  = true;  // always true (confirmed)
            $pkg->paymentType         = (int) $checkout_method;
            $pkg->cod                 = 0;
            $pkg->pickupType          = (int)$pickup_type;
            $pkg->distributionType    = 10;
            $pkg->parcelBookId        = 0;
            $pkg->isUnusual           = false;

            return $pkg;
        };

        // Single item precise mode (keep item dimensions feel), but still split if limits exceeded
        if ($total_item_qty === 1 && $single_item_snapshot) {
            $w_kg    = $single_item_snapshot['weight_kg'];
            $L       = $single_item_snapshot['L_cm'];
            $W       = $single_item_snapshot['W_cm'];
            $H       = $single_item_snapshot['H_cm'];
            $maxSide = max($L, $W, $H);
            $vol     = $L * $W * $H;
            $price   = 0.0;

            // use order items_total_store as value for single item if price missing
            if (isset($single_item_snapshot['price'])) {
                $price = (float)$single_item_snapshot['price'];
            } else {
                $price = 0.0;
                foreach ($order->get_items() as $li) $price += (float)$li->get_total();
            }

            $n_by_kg  = (int)ceil($w_kg / $PER_ITEM_MAX_KG);
            $n_by_dim = (int)ceil(($maxSide > 0 ? $maxSide / $MAX_DIM_CM : 0));
            $n_by_vol = (int)ceil($vol / $CUBE_CM3);

            $num_pkgs = max(1, max($n_by_kg, max($n_by_dim, $n_by_vol)));
            $weight_only_split = $num_pkgs > 1 && $n_by_kg === $num_pkgs && $n_by_dim <= 1 && $n_by_vol <= 1;

            $pi_weight = $w_kg / $num_pkgs;
            $pi_value  = $price / $num_pkgs;
            $remaining_vol = $vol;

            for ($i = 1; $i <= $num_pkgs; $i++) {
                if ($weight_only_split) {
                    $side = $maxSide;
                    $pkg_value = $pi_value;
                    $pkg_w = $pi_weight;
                } elseif ($i < $num_pkgs) {
                    $side      = $MAX_DIM_CM;
                    $pkg_value = $pi_value;
                    $pkg_w     = $pi_weight;
                    $remaining_vol -= $CUBE_CM3;
                    if ($remaining_vol < 0) $remaining_vol = 0;
                } else {
                    $side = ($remaining_vol > 0) ? pow($remaining_vol, 1/3) : $MAX_DIM_CM;
                    if ($side > $MAX_DIM_CM) $side = $MAX_DIM_CM;
                    $pkg_value = $pi_value;
                    $pkg_w     = $pi_weight;
                }

                $pkg = $make_pkg($pkg_w, ceil($side), $pkg_value);
                $packages[] = $pkg;
            }
        } else {
            // Aggregate multi-item / multi-qty into minimal number of packages
            if ($total_weight_kg <= 0 || $total_volume_cm3 <= 0) {
                tipax_log("Tipax order: invalid cart totals.");
                return ['packages' => [], 'serviceId' => $serviceId];
            }

            $number_by_kg  = (int)ceil($total_weight_kg / $PER_ITEM_MAX_KG);
            $number_by_vol = (int)ceil($total_volume_cm3 / $CUBE_CM3);
            $package_count = max(1, max($number_by_kg, $number_by_vol));
            $weight_only_split = $package_count > 1 && $number_by_kg === $package_count && $number_by_vol <= 1;

            $pi_weight = $total_weight_kg  / $package_count;
            $pi_value  = $items_total_store / $package_count;

            $remaining_vol = $total_volume_cm3;
            $weight_split_side = pow($total_volume_cm3 / $package_count, 1 / 3);

            for ($i = 1; $i <= $package_count; $i++) {
                if ($weight_only_split) {
                    $side = $weight_split_side;
                    $pkg_w = $pi_weight;
                    $pkg_value = $pi_value;
                } elseif ($i < $package_count) {
                    $side      = $MAX_DIM_CM;
                    $pkg_w     = $pi_weight;
                    $pkg_value = $pi_value;
                    $remaining_vol -= $CUBE_CM3;
                    if ($remaining_vol < 0) $remaining_vol = 0;
                } else {
                    $side = ($remaining_vol > 0) ? pow($remaining_vol, 1/3) : $MAX_DIM_CM;
                    if ($side > $MAX_DIM_CM) $side = $MAX_DIM_CM;
                    $pkg_w     = $pi_weight;
                    $pkg_value = $pi_value;
                }

                // Guard against floating-point divisions exceeding the active packing limit.
                if ($pkg_w > $PER_ITEM_MAX_KG) {
                    $parts = (int)ceil($pkg_w / $PER_ITEM_MAX_KG);
                    $w_each = $pkg_w / $parts;
                    $v_each = $pkg_value / $parts;
                    for ($k = 0; $k < $parts; $k++) {
                        $packages[] = $make_pkg($w_each, ceil($side), $v_each);
                    }
                } else {
                    $packages[] = $make_pkg($pkg_w, ceil($side), $pkg_value);
                }
            }
        }

        if ($has_selected_packing && !tipax_apply_selected_packing_to_packages($packages, $selected_packing)) {
            $packing_error = tipax_get_package_packing_error('بسته‌بندی انتخاب‌شده با مشخصات مرسوله سازگار نیست.');
            tipax_log('Tipax order: ' . $packing_error);
            return ['packages' => [], 'serviceId' => $serviceId, 'message' => $packing_error];
        }

        if (!$has_selected_packing) {
            if ($default_package_dimensions !== null) {
                $packages = tipax_apply_package_dimensions($packages, $default_package_dimensions);
            }
            $packages = tipax_prepare_default_packing_packages($packages);
            if (empty($packages)) {
                $packing_error = tipax_get_package_packing_error('سگمنت معتبر بدون بسته‌بندی برای مرسوله پیدا نشد.');
                tipax_log('Tipax order: ' . $packing_error);
                return ['packages' => [], 'serviceId' => $serviceId, 'message' => $packing_error];
            }
        }

        // Apply serviceId to all packages (same as pricing rule)
        foreach ($packages as $pkg) {
            $pkg->serviceId = $serviceId;
        }
        $packages = tipax_apply_cod_to_packages($packages, $cod_api_amount);

        return ['packages' => $packages, 'serviceId' => $serviceId];
    }

    public function handle_thankyou_order($order_id)
    {
        $order   = wc_get_order($order_id);
        if (!$order || !$order->has_shipping_method('tipax_shipping_method')) return;

        $has_tipax_registration = $this->order_has_tipax_registration($order);
        $registration_state = (string) $order->get_meta('_tipax_registration_state', true);
        if (
            !$has_tipax_registration
            && in_array($registration_state, ['offline_pending', 'timeout_pending'], true)
        ) {
            return;
        }

        $plugin_settings = get_option('woocommerce_tipax_shipping_method_settings', []);
        if (
            tipax_is_checkout_offline_registration_enabled($plugin_settings)
            && !$has_tipax_registration
        ) {
            tipax_mark_order_tipax_registration_state(
                $order,
                'offline_pending',
                '',
                'checkout_offline_mode'
            );
            return;
        }

        $request = new Request();

        $accessToken = get_option('woocommerce_tipax_shipping_method_accesstoken', null);

        $order_data = $order->get_data();
        $orderShippingGetItems = $order->get_items('shipping');
        $recipient = $this->get_effective_order_recipient($order);

        // Extract serviceId saved in shipping item meta (used for label text decisions elsewhere)
        $serviceId = $this->get_shipping_service_id($orderShippingGetItems);
        $usesOfflinePricing = $this->shipping_items_use_offline_pricing($orderShippingGetItems);

        if ($usesOfflinePricing && !$has_tipax_registration) {
            tipax_mark_order_tipax_registration_state(
                $order,
                'offline_pending',
                '',
                'pricing_timeout'
            );
            return;
        }

        // Receiver/Address
        $order_receiver_phone = $this->resolve_receiver_phone($order);
        $order_receiver_plaque  = null;
        foreach ($order_data["meta_data"] as $meta) {
            if ($meta->key == "plaque") $order_receiver_plaque = $meta->value;
        }

        $order_shipping_first_name = $recipient['first_name'];
        $order_shipping_last_name  = $recipient['last_name'];
        $order_billing_phone       = $this->first_non_empty_order_value($order_data['billing']['phone']);
        $order_shipping_postcode   = postalcodeValidate($recipient['postcode']);
        $order_shipping_address_1  = $recipient['address_1'];
        $order_shipping_city       = $recipient['city'];
        $order_shipping_country    = $recipient['country'];
        $order_shipping_state      = $recipient['state'];

        $states = $order_shipping_country !== '' ? WC()->countries->get_states($order_shipping_country) : [];
        $stateTextShipping = isset($states[$order_shipping_state]) ? $states[$order_shipping_state] : $order_shipping_state;

        // Store info
        $storeRawCountry   = get_option('woocommerce_default_country'); // e.g. IR:THR
        $weight_unit       = get_option('woocommerce_weight_unit');
        $dimension_unit    = get_option('woocommerce_dimension_unit');
        $woocommerce_currency = $order->get_currency();

        // Country/state split
        $parts = explode(':', (string)$storeRawCountry);
        $storeCountry = $parts[0] ?? '';
        $storeState   = $parts[1] ?? '';
        $storeCity    = get_option('woocommerce_store_city');

        $storeStateReal = WC()->countries->get_states($storeCountry)[$storeState] ?? $storeState;

        // City codes
        $order_shipping_city_id = tipax_get_order_destination_city_id($order, $order_shipping_city, $stateTextShipping);
        $origin_city_id         = get_city_code($storeCity, $storeStateReal, '', false);

        // Settings
        $shipping_instance_id = null;
        foreach ($orderShippingGetItems as $item_id => $item) {
            $shipping_instance_id = $item->get_instance_id();
            break;
        }
        $settings = get_option("woocommerce_tipax_shipping_method_" . $shipping_instance_id . "_settings", null);
        if ($settings == null) {
            $settings = get_option("woocommerce_tipax_shipping_method_settings", null);
        }

        $addressMethod = 3; // confirmed
        $shppingAddress = $settings != null ? get_option("tipax_shipping_address") : null; // addressesId
        $settlement_context = tipax_get_order_settlement_context($order, $plugin_settings);
        $checkout_method = (int) $settlement_context['payment_type'];
        $pickup_type = tipax_normalize_pickup_type($plugin_settings['pickup_type'] ?? 10);
        $averages = tipax_get_average_package_settings($plugin_settings);
        $avgWeight = $averages['weight'];
        $avgLength = $averages['length'];
        $avgWidth = $averages['width'];
        $avgHeight = $averages['height'];
        $selected_packing = tipax_get_selected_packing($plugin_settings);

        // Required params
        if ($addressMethod != 3 || empty($order_shipping_city_id) || empty($shppingAddress)) {
            return; // cannot proceed
        }

        // Build packages with aggregation logic
        $build = $this->build_packages_for_order(
            $order, $request, $accessToken,
            $shppingAddress,
            $order_shipping_city_id,
            $order_shipping_address_1,
            $order_shipping_postcode,
            $order_receiver_plaque,
            $order_shipping_first_name,
            $order_shipping_last_name,
            $order_receiver_phone,
            $order_billing_phone,
            $woocommerce_currency,
            $weight_unit,
            $dimension_unit,
            $avgWeight,
            $avgLength,
            $avgWidth,
            $avgHeight,
            $checkout_method,
            $pickup_type,
            $selected_packing,
            $settlement_context['cod_api_amount']
        );

        if (empty($build['packages'])) {
            wc_print_notice(
                esc_html($build['message'] ?? __('There was a problem building Tipax packages.', 'delivery-service-tipax-shipping')),
                'error'
            );
            return;
        }

        if ((int) $serviceId === tipax_get_same_day_service_id()) {
            $same_day_eligibility = $this->validate_same_day_order_packages(
                $build['packages'],
                $build['serviceId'] ?? 0,
                $order_shipping_city_id,
                $order_shipping_address_1,
                $order,
                $plugin_settings
            );
            if (empty($same_day_eligibility['eligible'])) {
                wc_print_notice(
                    esc_html($same_day_eligibility['message'] ?? 'شرایط سرویس همان روز برقرار نیست.'),
                    'error'
                );
                return;
            }
        }

        // Apply serviceId (already applied inside builder, but keep compatible with your saved meta)
        if (!empty($serviceId)) {
            foreach ($build['packages'] as $p) { $p->serviceId = (int)$serviceId; }
        }

        $data = new stdClass;
        $data->packages = $build['packages'];

        // Submit
        $this->register_order(self::ORDER_REGISTRATION_API_PATH, $order, $request, $data, $accessToken, $order_id);
    }

    public function register_order($apiPath, $order, Request $request, stdClass $data, $accessToken, $order_id)
    {
        $plugin_settings = get_option('woocommerce_tipax_shipping_method_settings', []);
        $request_options = $this->get_online_order_request_options($plugin_settings);

        $prepared_packages = tipax_apply_same_day_location_to_packages(
            isset($data->packages) && is_array($data->packages) ? $data->packages : [],
            $order
        );
        if (is_wp_error($prepared_packages)) {
            tipax_mark_order_tipax_registration_state(
                $order,
                'api_failed',
                $prepared_packages->get_error_message(),
                'same_day_location_missing'
            );
            wc_print_notice($prepared_packages->get_error_message(), 'error');
            return;
        }
        $data->packages = $prepared_packages;

        foreach ($order->get_items() as $item) { $firstItem = $item; break; }

        echo '<ul>';

        $tipax_code = isset($firstItem)
            ? ($firstItem->get_meta("tipax_tracking_code") . $firstItem->get_meta("tipax_order_id"))
            : '';

        if (!$tipax_code) {
            // anti-dup cookie
            $tipaxOrderCookie = tipaxPluginCookie("tipaxOrder_" . $order_id);
    if ($tipaxOrderCookie) {
        tipax_clear_order_tipax_registration_state($order);
        $tipaxOrderInfo = json_decode(base64_decode($tipaxOrderCookie), false);
        $trackingCodes  = isset($tipaxOrderInfo->trackingCodes) && is_array($tipaxOrderInfo->trackingCodes)
            ? tipax_normalize_tracking_codes($tipaxOrderInfo->trackingCodes)
            : [];
        $orderId        = $tipaxOrderInfo->orderId ?? null;

        $totalCodes = count($trackingCodes);
        $i = 0;

        foreach ($order->get_items() as $item_id => $item) {
            $product   = $item->get_product();
            $quantity  = (int)$item->get_quantity();
            $assigned  = [];

            if ($totalCodes <= 1) {
                // Only one tracking code returned → assign same code to all items
                $assigned = $trackingCodes ? [$trackingCodes[0]] : [];
            } else {
                // Distribute sequentially
                for ($j = 0; $j < $quantity; $j++) {
                    if ($i < $totalCodes && !empty($trackingCodes[$i])) {
                        $assigned[] = $trackingCodes[$i];
                        $i++;
                    }
                }
            }

            $codes_str = implode(' ', $assigned);
            if ($codes_str !== '') {
                wc_add_order_item_meta($item_id, 'tipax_tracking_code', $codes_str);
            }
            if ($orderId) {
                wc_add_order_item_meta($item_id, 'tipax_order_id', $orderId);
            }

            echo '<li><strong>' . __('Tipax order information for ', 'delivery-service-tipax-shipping') . ' : ' . esc_html($product->get_name()) . '</strong></li>';
            echo '<li>' . __('Tipax Tracking Code', 'delivery-service-tipax-shipping') . ' : ' . esc_html($codes_str !== '' ? $codes_str : '-') . '</li>';
            echo '<li>' . __('Tipax Order Id', 'delivery-service-tipax-shipping') . ' : ' . esc_html($orderId) . '</li>';
            echo '<br>';
        }
    } else {
                $response = $this->submit_order_request($apiPath, $request, $data, $accessToken, $request_options);

                if (tipax_is_tipax_transport_error_response($response)) {
                    $timeout_message = tipax_extract_tipax_response_message($response, 'Tipax request timed out.');
                    tipax_mark_order_tipax_registration_state(
                        $order,
                        'offline_pending',
                        $timeout_message,
                        'order_timeout'
                    );

                    wc_print_notice('تیپاکس در مهلت تعیین‌شده پاسخ نداد. سفارش به‌صورت آفلاین ذخیره شد و از منوی سفارشات تیپاکس قابل ثبت است.', 'notice');

                    echo '</ul>';
                    return;
                }

                if (is_string($response) && trim($response) !== '') {
                    $api_error_message = trim($response);
                    tipax_mark_order_tipax_registration_state(
                        $order,
                        'api_failed',
                        $api_error_message,
                        'order_api_error'
                    );
                    wc_print_notice($api_error_message, 'error');
                    echo '</ul>';
                    return;
                }

                if (is_object($response) && isset($response->errors)) {
                    $api_error_messages = [];
                    wc_print_notice(__('There was a problem in creating tipax order', 'delivery-service-tipax-shipping'), 'error');
                    wc_print_notice(__('Please contact us for fixing the error', 'delivery-service-tipax-shipping'), 'error');
                    foreach ($response->errors as $error) {
                        if (!empty($error->errorMessage)) {
                            $api_error_messages[] = (string) $error->errorMessage;
                            wc_print_notice($error->errorMessage, 'error');
                        }
                    }
                    tipax_mark_order_tipax_registration_state(
                        $order,
                        'api_failed',
                        implode("\n", $api_error_messages),
                        'order_api_error'
                    );
                    echo '</ul>';
                    return;
                }
                if (isset($response->status) && (int)$response->status == 401) {
                    $accessToken = get_option('woocommerce_tipax_shipping_method_accesstoken', null);
                    $response = $this->submit_order_request($apiPath, $request, $data, $accessToken, $request_options);
                }

                if (tipax_is_tipax_transport_error_response($response)) {
                    $timeout_message = tipax_extract_tipax_response_message($response, 'Tipax request timed out.');
                    tipax_mark_order_tipax_registration_state(
                        $order,
                        'offline_pending',
                        $timeout_message,
                        'order_timeout'
                    );

                    wc_print_notice('تیپاکس در مهلت تعیین‌شده پاسخ نداد. سفارش به‌صورت آفلاین ذخیره شد و از منوی سفارشات تیپاکس قابل ثبت است.', 'notice');

                    echo '</ul>';
                    return;
                }

                // cache response for 7 days
                if (isset($response->trackingCodes) && is_array($response->trackingCodes)) {
                    $response->trackingCodes = tipax_normalize_tracking_codes($response->trackingCodes);
                }
                $expiration = current_time('timestamp') + (DAY_IN_SECONDS * 7);
                tipaxPluginCookie("tipaxOrder_" . $order_id, base64_encode(json_encode($response)), $expiration);

                if (isset($response->trackingCodes) && is_array($response->trackingCodes)) {
                    tipax_clear_order_tipax_registration_state($order);
                    if (!empty($response->trackingCodes) && class_exists('Tipax_Hub_Metrics')) { Tipax_Hub_Metrics::record_order($order); }
                    wc_print_notice(__('Tipax order shipping created successfully', 'delivery-service-tipax-shipping'), 'notice');

                    $i = 0;
    $trackingCodes = isset($response->trackingCodes) && is_array($response->trackingCodes)
        ? tipax_normalize_tracking_codes($response->trackingCodes)
        : [];
    $orderId = $response->orderId ?? null;

    $totalCodes = count($trackingCodes);
    $i = 0;

    foreach ($order->get_items() as $item_id => $item) {
        $product  = $item->get_product();
        $quantity = (int)$item->get_quantity();
        $assigned = [];

        if ($totalCodes <= 1) {
            // Only one code → apply same one to all items
            $assigned = $trackingCodes ? [$trackingCodes[0]] : [];
        } else {
            for ($j = 0; $j < $quantity; $j++) {
                if ($i < $totalCodes && !empty($trackingCodes[$i])) {
                    $assigned[] = $trackingCodes[$i];
                    $i++;
                }
            }
        }

        $codes_str = implode(' ', $assigned);
        if ($codes_str !== '') {
            wc_add_order_item_meta($item_id, 'tipax_tracking_code', $codes_str);
        }
        if ($orderId) {
            wc_add_order_item_meta($item_id, 'tipax_order_id', $orderId);
        }

        echo '<li><strong>' . __('Tipax order information for ', 'delivery-service-tipax-shipping') . ' : ' . esc_html($product->get_name()) . '</strong></li>';
        echo '<li>' . __('Tipax Tracking Code', 'delivery-service-tipax-shipping') . ' : ' . esc_html($codes_str !== '' ? $codes_str : '-') . '</li>';
        echo '<li>' . __('Tipax Order Id', 'delivery-service-tipax-shipping') . ' : ' . esc_html($orderId) . '</li>';
        echo '<br>';
    }

                }
            }
        } else {
    tipax_clear_order_tipax_registration_state($order);
    wc_print_notice(__('Tipax order shipping created successfully', 'delivery-service-tipax-shipping'), 'notice');

    $shown = [];
    foreach ($order->get_items() as $item_id => $item) {
        $product  = $item->get_product();
        $codesStr = tipax_normalize_tracking_codes_string($item->get_meta("tipax_tracking_code"));

        // Avoid duplicate lines if codes already shown
        if (in_array($codesStr, $shown, true)) {
            continue;
        }
        $shown[] = $codesStr;

        echo '<li><strong>' . __('Tipax order information for ', 'delivery-service-tipax-shipping') . ' : ' . esc_html($product->get_name()) . '</strong></li>';
        echo '<li>' . __('Tipax Tracking Code', 'delivery-service-tipax-shipping') . ' : ' . esc_html($codesStr !== '' ? $codesStr : '-') . '</li>';
        echo '<li>' . __('Tipax Order Id', 'delivery-service-tipax-shipping') . ' : ' . esc_html($item->get_meta("tipax_order_id")) . '</li>';
        echo '<br>';
    }

        }

        echo '</ul>';
    }

    public function submit_order_request($apiPath, Request $request, stdClass $data, $accessToken, array $request_options = [])
    {
        $order_api = new Tipax_Order_Api($request);
        return $order_api->submit($apiPath, $data, $accessToken, $request_options);
    }

    public function retry_order_registration($order_id, array $manual_input = [])
    {
        if (!empty($manual_input)) {
            $context = $this->get_retry_order_context($order_id);
            if (empty($context['success'])) {
                return $this->build_retry_base_result($order_id, false, $context['messages'] ?? ['اطلاعات سفارش برای ثبت مجدد آماده نیست.']);
            }

            $normalized = $this->normalize_retry_form_input($manual_input);
            if (empty($normalized['success'])) {
                return $this->build_retry_base_result($order_id, false, $normalized['messages'] ?? ['اطلاعات ورودی معتبر نیست.']);
            }

            $build = $this->build_manual_retry_packages($context, $normalized['data']);
            if (empty($build['packages'])) {
                return $this->build_retry_base_result($order_id, false, [$build['message'] ?? 'ساخت بسته برای ثبت مجدد انجام نشد.']);
            }

            $data = new stdClass;
            $data->packages = $build['packages'];

            return $this->register_order_after_failed(
                self::ORDER_REGISTRATION_API_PATH,
                $context['order'],
                $context['request'],
                $data,
                $context['accessToken'],
                $order_id,
                $this->get_manual_retry_request_options()
            );
        }

        $request = new Request();
        $order   = wc_get_order($order_id);
        if (!$order || !$order->has_shipping_method('tipax_shipping_method')) {
            return [
                'success' => false,
                'messages' => ['Order not using Tipax shipping.'],
                'order_id' => $order_id,
                'tracking_codes' => [],
                'tipax_order_id' => null,
                'http_status' => null,
            ];
        }

        $accessToken = get_option('woocommerce_tipax_shipping_method_accesstoken', null);

        $order_data = $order->get_data();
        $orderShippingGetItems = $order->get_items('shipping');
        $recipient = $this->get_effective_order_recipient($order);

        // Extract serviceId saved in shipping meta
        $serviceId = $this->get_shipping_service_id($orderShippingGetItems);

        // Receiver/Address
        $order_receiver_phone = $this->resolve_receiver_phone($order);
        $order_receiver_plaque  = null;
        foreach ($order_data["meta_data"] as $meta) {
            if ($meta->key == "plaque") $order_receiver_plaque = $meta->value;
        }

        $order_shipping_first_name = $recipient['first_name'];
        $order_shipping_last_name  = $recipient['last_name'];
        $order_billing_phone       = $this->first_non_empty_order_value($order_data['billing']['phone']);
        $order_shipping_postcode   = $recipient['postcode'];
        $order_shipping_address_1  = $recipient['address_1'];
        $order_shipping_city       = $recipient['city'];
        $order_shipping_country    = $recipient['country'];
        $order_shipping_state      = $recipient['state'];

        $states = $order_shipping_country !== '' ? WC()->countries->get_states($order_shipping_country) : [];
        $stateTextShipping = isset($states[$order_shipping_state]) ? $states[$order_shipping_state] : $order_shipping_state;

        // Store info
        $storeRawCountry   = get_option('woocommerce_default_country');
        $weight_unit       = get_option('woocommerce_weight_unit');
        $dimension_unit    = get_option('woocommerce_dimension_unit');
        $woocommerce_currency = $order->get_currency();

        // Country/state split
        $parts = explode(':', (string)$storeRawCountry);
        $storeCountry = $parts[0] ?? '';
        $storeState   = $parts[1] ?? '';
        $storeCity    = get_option('woocommerce_store_city');

        $storeStateReal = WC()->countries->get_states($storeCountry)[$storeState] ?? $storeState;

        // City codes
        $order_shipping_city_id = tipax_get_order_destination_city_id($order, $order_shipping_city, $stateTextShipping);

        // Settings
        $shipping_instance_id = null;
        foreach ($orderShippingGetItems as $item_id => $item) { $shipping_instance_id = $item->get_instance_id(); break; }
        $settings = get_option("woocommerce_tipax_shipping_method_" . $shipping_instance_id . "_settings", null);
        if ($settings == null) {
            $settings = get_option("woocommerce_tipax_shipping_method_settings", null);
        }

        $addressMethod = 3;
        $plugin_settings = get_option('woocommerce_tipax_shipping_method_settings', []);
        $shppingAddress = $settings != null ? get_option("tipax_shipping_address") : null; // addressesId
        $settlement_context = tipax_get_order_settlement_context($order, $plugin_settings);
        $checkout_method = (int) $settlement_context['payment_type'];
        $pickup_type = tipax_normalize_pickup_type($plugin_settings['pickup_type'] ?? 10);
        $averages = tipax_get_average_package_settings($plugin_settings);
        $avgWeight = $averages['weight'];
        $avgLength = $averages['length'];
        $avgWidth = $averages['width'];
        $avgHeight = $averages['height'];
        $selected_packing = tipax_get_selected_packing($plugin_settings);

        // Required params
        if ($addressMethod != 3 || empty($order_shipping_city_id) || empty($shppingAddress)) {
            return [
                'success' => false,
                'messages' => ['پارامترهای آدرس/شهر نامعتبر است.'],
                'order_id' => $order_id,
                'tracking_codes' => [],
                'tipax_order_id' => null,
                'http_status' => null,
            ];
        }

        // Build packages
        $build = $this->build_packages_for_order(
            $order, $request, $accessToken,
            $shppingAddress,
            $order_shipping_city_id,
            $order_shipping_address_1,
            $order_shipping_postcode,
            $order_receiver_plaque,
            $order_shipping_first_name,
            $order_shipping_last_name,
            $order_receiver_phone,
            $order_billing_phone,
            $woocommerce_currency,
            $weight_unit,
            $dimension_unit,
            $avgWeight,
            $avgLength,
            $avgWidth,
            $avgHeight,
            $checkout_method,
            $pickup_type,
            $selected_packing,
            $settlement_context['cod_api_amount']
        );

        if (empty($build['packages'])) {
            return [
                'success' => false,
                'messages' => [$build['message'] ?? 'ساخت پکیج‌ها با خطا مواجه شد.'],
                'order_id' => $order_id,
                'tracking_codes' => [],
                'tipax_order_id' => null,
                'http_status' => null,
            ];
        }

        if ((int) $serviceId === tipax_get_same_day_service_id()) {
            $same_day_eligibility = $this->validate_same_day_order_packages(
                $build['packages'],
                $build['serviceId'] ?? 0,
                $order_shipping_city_id,
                $order_shipping_address_1,
                $order,
                $plugin_settings
            );
            if (empty($same_day_eligibility['eligible'])) {
                return [
                    'success' => false,
                    'messages' => [$same_day_eligibility['message'] ?? 'شرایط سرویس همان روز برقرار نیست.'],
                    'order_id' => $order_id,
                    'tracking_codes' => [],
                    'tipax_order_id' => null,
                    'http_status' => null,
                ];
            }
        }

        // Apply serviceId from shipping meta if present
        if (!empty($serviceId)) {
            foreach ($build['packages'] as $p) { $p->serviceId = (int)$serviceId; }
        }

        $data = new stdClass;
        $data->packages = $build['packages'];

        return $this->register_order_after_failed(
            self::ORDER_REGISTRATION_API_PATH,
            $order,
            $request,
            $data,
            $accessToken,
            $order_id,
            $this->get_manual_retry_request_options()
        );
    }

    public function register_order_after_failed($apiPath, $order, Request $request, stdClass $data, $accessToken, $order_id, array $request_options = [])
    {
        $result = [
            'success'         => false,
            'messages'        => [],
            'order_id'        => $order_id,
            'tracking_codes'  => [],
            'tipax_order_id'  => null,
            'http_status'     => null,
        ];

        $prepared_packages = tipax_apply_same_day_location_to_packages(
            isset($data->packages) && is_array($data->packages) ? $data->packages : [],
            $order
        );
        if (is_wp_error($prepared_packages)) {
            $result['messages'][] = $prepared_packages->get_error_message();
            return $result;
        }
        $data->packages = $prepared_packages;

        // Helper (no arrow functions)
        $extract_error_text = function ($resp) {
            if (is_wp_error($resp)) return $resp->get_error_message();
            if (is_object($resp)) {
                if (!empty($resp->body) && is_string($resp->body)) return $resp->body;
                if (!empty($resp->errors) && is_array($resp->errors)) {
                    $msgs = [];
                    foreach ($resp->errors as $e) {
                        if (!empty($e->errorMessage)) $msgs[] = $e->errorMessage;
                    }
                    return implode("\n", $msgs);
                }
                if (!empty($resp->message)) return $resp->message;
            }
            if (is_string($resp)) return $resp;
            return 'خطای نامشخص از تیپاکس.';
        };

        // already registered?
        $firstItem = null;
        foreach ($order->get_items() as $it) { $firstItem = $it; break; }
        $existing_tipax_code = $firstItem
            ? ($firstItem->get_meta("tipax_tracking_code") . $firstItem->get_meta("tipax_order_id"))
            : '';

        if ($existing_tipax_code) {
            tipax_clear_order_tipax_registration_state($order);
            $result['success']  = true;
            $result['messages'][] = 'سفارش قبلاً در تیپاکس ثبت شده است.';
            foreach ($order->get_items() as $item_id => $item) {
                $codes = tipax_normalize_tracking_codes_string($item->get_meta("tipax_tracking_code"));
                if ($codes !== '') $result['tracking_codes'][] = $codes;
                if (!$result['tipax_order_id']) {
                    $oid = $item->get_meta("tipax_order_id");
                    if (!empty($oid)) $result['tipax_order_id'] = $oid;
                }
            }
            return $result;
        }

        // cookie reuse
        $tipaxOrderCookie = tipaxPluginCookie("tipaxOrder_" . $order_id);
        if ($tipaxOrderCookie) {
            $tipaxOrderInfo = json_decode(base64_decode($tipaxOrderCookie), false);

            if (!empty($tipaxOrderInfo->trackingCodes)) {
                $i = 0;
                foreach ($order->get_items() as $item_id => $item) {
                    $quantity  = (int)$item->get_quantity();
                    $codes_str = '';

                    if ($quantity === 1) {
                        $code = tipax_normalize_tracking_code($tipaxOrderInfo->trackingCodes[$i] ?? '');
                        if ($code !== '') {
                            wc_add_order_item_meta($item_id, 'tipax_tracking_code', $code);
                            $result['tracking_codes'][] = $code;
                        }
                    } else {
                        for ($j = 0; $j < $quantity; $j++) {
                            $code = tipax_normalize_tracking_code($tipaxOrderInfo->trackingCodes[$j] ?? '');
                            if ($code !== '') {
                                $codes_str .= $code . ' ';
                                $result['tracking_codes'][] = $code;
                            }
                        }
                        if ($codes_str !== '') {
                            wc_add_order_item_meta($item_id, 'tipax_tracking_code', trim($codes_str));
                        }
                    }

                    if (!empty($tipaxOrderInfo->orderId)) {
                        wc_add_order_item_meta($item_id, 'tipax_order_id', $tipaxOrderInfo->orderId);
                        $result['tipax_order_id'] = $tipaxOrderInfo->orderId;
                    }

                    $i++;
                }

                $result['success']    = true;
                $result['messages'][] = 'سفارش با موفقیت در تیپاکس ثبت شد (از کش/کوکی).';
                tipax_clear_order_tipax_registration_state($order);
                return $result;
            }
            // else: fall through and re-post
        }

        // API post
        $response = $this->submit_order_request($apiPath, $request, $data, $accessToken, $request_options);
        if (is_object($response) && isset($response->status) && (int)$response->status === 401) {
            $accessToken = get_option('woocommerce_tipax_shipping_method_accesstoken', null);
            $response    = $this->submit_order_request($apiPath, $request, $data, $accessToken, $request_options);
        }

        if (tipax_is_tipax_transport_error_response($response)) {
            $timeout_message = tipax_extract_tipax_response_message($response, 'Tipax request timed out.');
            tipax_mark_order_tipax_registration_state($order, 'timeout_pending', $timeout_message, 'order_timeout');
            $result['messages'][] = $timeout_message;
            return $result;
        }

        if (is_object($response) && isset($response->status)) {
            $result['http_status'] = (int)$response->status;
        }
        if (is_wp_error($response)) {
            $result['messages'][] = $response->get_error_message();
            return $result;
        }

        if (is_object($response) && isset($response->errors) && is_array($response->errors)) {
            foreach ($response->errors as $error) {
                if (!empty($error->errorMessage)) $result['messages'][] = $error->errorMessage;
            }
            if (empty($result['messages'])) $result['messages'][] = 'خطای نامشخص از تیپاکس.';
            return $result;
        }

        if (is_object($response) && isset($response->status) && (int)$response->status === 400) {
            $result['messages'][] = $extract_error_text($response);
            return $result;
        }

        // success
        if (is_object($response) && !empty($response->trackingCodes) && is_array($response->trackingCodes)) {
            if (class_exists('Tipax_Hub_Metrics')) { Tipax_Hub_Metrics::record_order($order); }
            $response->trackingCodes = tipax_normalize_tracking_codes($response->trackingCodes);
            $expiration = current_time('timestamp') + (DAY_IN_SECONDS * 7);
            tipaxPluginCookie("tipaxOrder_" . $order_id, base64_encode(json_encode($response)), $expiration);

            $i = 0;
            foreach ($order->get_items() as $item_id => $item) {
                $quantity  = (int)$item->get_quantity();
                $codes_str = '';

                if ($quantity === 1) {
                    $code = tipax_normalize_tracking_code($response->trackingCodes[$i] ?? '');
                    if ($code !== '') {
                        wc_add_order_item_meta($item_id, 'tipax_tracking_code', $code);
                        $result['tracking_codes'][] = $code;
                    }
                } else {
                    for ($j = 0; $j < $quantity; $j++) {
                        $code = tipax_normalize_tracking_code($response->trackingCodes[$j] ?? '');
                        if ($code !== '') {
                            $codes_str .= $code . ' ';
                            $result['tracking_codes'][] = $code;
                        }
                    }
                    if ($codes_str !== '') {
                        wc_add_order_item_meta($item_id, 'tipax_tracking_code', trim($codes_str));
                    }
                }

                if (!empty($response->orderId)) {
                    wc_add_order_item_meta($item_id, 'tipax_order_id', $response->orderId);
                    $result['tipax_order_id'] = $response->orderId;
                }

                $i++;
            }

            $result['success']    = true;
            $result['messages'][] = 'سفارش با موفقیت در تیپاکس ثبت شد.';
            tipax_clear_order_tipax_registration_state($order);
            return $result;
        }

        $result['messages'][] = $response;
        return $result;
    }

    public function resolve_receiver_phone(WC_Order $order)
    {
        // If Digits merges -> always billing_phone
        if (function_exists('digits_wc_merge_billing_phone') && digits_wc_merge_billing_phone()) {
            return $order->get_billing_phone();
        }

        // If billing phone exists -> use it
        if ($order->get_billing_phone()) {
            return $order->get_billing_phone();
        }

        // Otherwise -> custom phone_field
        $custom = $order->get_meta('phone');
        if ($custom) return $custom;

        // fallback
        return $order->get_billing_phone();
    }

}


if (!class_exists('Tipax_Shipment_Module')) {
    class_alias('Tipax_Order_Service', 'Tipax_Shipment_Module');
}
