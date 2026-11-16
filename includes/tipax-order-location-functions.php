<?php
if (!defined('ABSPATH')) {
    exit;
}

function tipax_get_iran_state_labels()
{
    return [
        'EAZ' => 'آذربایجان شرقی',
        'WAZ' => 'آذربایجان غربی',
        'ADL' => 'اردبیل',
        'ESF' => 'اصفهان',
        'ABZ' => 'البرز',
        'ILM' => 'ایلام',
        'BHR' => 'بوشهر',
        'THR' => 'تهران',
        'CHB' => 'چهارمحال و بختیاری',
        'SKH' => 'خراسان جنوبی',
        'RKH' => 'خراسان رضوی',
        'NKH' => 'خراسان شمالی',
        'KHZ' => 'خوزستان',
        'ZJN' => 'زنجان',
        'SMN' => 'سمنان',
        'SBN' => 'سیستان و بلوچستان',
        'FRS' => 'فارس',
        'GZN' => 'قزوین',
        'QHM' => 'قم',
        'KRD' => 'کردستان',
        'KRN' => 'کرمان',
        'KRH' => 'کرمانشاه',
        'KBD' => 'کهگیلویه و بویراحمد',
        'GLS' => 'گلستان',
        'GIL' => 'گیلان',
        'LRS' => 'لرستان',
        'MZN' => 'مازندران',
        'MKZ' => 'مرکزی',
        'HRZ' => 'هرمزگان',
        'HDN' => 'همدان',
        'YZD' => 'یزد',
    ];
}

function tipax_sort_iranian_woocommerce_states($states)
{
    if (!is_array($states) || !isset($states['IR']) || !is_array($states['IR'])) {
        return $states;
    }

    $iran_states = $states['IR'];
    $sorted_states = [];
    foreach (tipax_get_iran_state_labels() as $state_code => $fallback_title) {
        if (array_key_exists($state_code, $iran_states)) {
            $sorted_states[$state_code] = $iran_states[$state_code];
        }
    }

    // Preserve custom provinces added by other extensions after the standard list.
    foreach ($iran_states as $state_code => $state_title) {
        if (!array_key_exists($state_code, $sorted_states)) {
            $sorted_states[$state_code] = $state_title;
        }
    }

    $states['IR'] = $sorted_states;
    return $states;
}

function tipax_resolve_state_title_for_display($state_value, $country_code = '')
{
    $state_value = is_scalar($state_value) ? trim((string) $state_value) : '';
    if ($state_value === '') {
        return '';
    }

    $state_code = strtoupper($state_value);
    $countries = [];
    $country_code = is_scalar($country_code) ? strtoupper(trim((string) $country_code)) : '';
    if ($country_code !== '') {
        $countries[] = $country_code;
    }
    if (!in_array('IR', $countries, true)) {
        $countries[] = 'IR';
    }

    if (function_exists('WC') && WC() && isset(WC()->countries)) {
        foreach ($countries as $candidate_country) {
            $states = WC()->countries->get_states($candidate_country);
            if (!is_array($states)) {
                continue;
            }

            foreach ($states as $candidate_code => $candidate_title) {
                if (strtoupper((string) $candidate_code) === $state_code) {
                    return (string) $candidate_title;
                }
            }
        }
    }

    $iran_states = tipax_get_iran_state_labels();
    return isset($iran_states[$state_code]) ? $iran_states[$state_code] : $state_value;
}

function tipax_order_uses_tipax_shipping_for_location($order)
{
    if (!is_object($order)) {
        return false;
    }

    if (is_callable([$order, 'has_shipping_method']) && $order->has_shipping_method('tipax_shipping_method')) {
        return true;
    }

    if (!is_callable([$order, 'get_shipping_methods'])) {
        return false;
    }

    foreach ((array) $order->get_shipping_methods() as $shipping_item) {
        if (
            is_object($shipping_item)
            && is_callable([$shipping_item, 'get_method_id'])
            && $shipping_item->get_method_id() === 'tipax_shipping_method'
        ) {
            return true;
        }
    }

    return false;
}

function tipax_should_resolve_order_address_location($order, $address_type = 'shipping')
{
    $address_type = $address_type === 'billing' ? 'billing' : 'shipping';
    $country = strtoupper(tipax_get_order_address_field_value($order, $address_type, 'country'));
    if ($country !== '' && $country !== 'IR') {
        return false;
    }

    $state = strtoupper(tipax_get_order_address_field_value($order, $address_type, 'state'));
    if (isset(tipax_get_iran_state_labels()[$state])) {
        return true;
    }

    return tipax_order_uses_tipax_shipping_for_location($order);
}

function tipax_get_order_address_field_value($order, $address_type, $field)
{
    if (!is_object($order)) {
        return '';
    }

    $address_type = $address_type === 'billing' ? 'billing' : 'shipping';
    $method = 'get_' . $address_type . '_' . $field;
    $value = is_callable([$order, $method]) ? $order->{$method}() : '';
    $value = is_scalar($value) ? trim((string) $value) : '';

    if ($value === '' && $address_type === 'shipping') {
        $billing_method = 'get_billing_' . $field;
        $billing_value = is_callable([$order, $billing_method]) ? $order->{$billing_method}() : '';
        $value = is_scalar($billing_value) ? trim((string) $billing_value) : '';
    }

    return $value;
}

function tipax_get_tipax_city_title_for_display($city_id, $state_title)
{
    if (!is_numeric($city_id) || !function_exists('tipax_get_city_rows_for_state')) {
        return '';
    }

    if (function_exists('tipax_resolve_city_id_alias')) {
        $city_id = tipax_resolve_city_id_alias($city_id);
    }

    foreach (tipax_get_city_rows_for_state($state_title) as $city) {
        if (is_object($city) && (int) ($city->cityId ?? 0) === (int) $city_id) {
            return trim((string) ($city->cityTitle ?? ''));
        }
    }

    return '';
}

function tipax_find_city_title_in_address($address, $state_title)
{
    if (!function_exists('tipax_get_city_rows_for_state')) {
        return '';
    }

    $address_key = function_exists('tipax_normalize_location_lookup_text')
        ? tipax_normalize_location_lookup_text($address)
        : trim((string) $address);
    if ($address_key === '') {
        return '';
    }

    $matches = [];
    foreach (tipax_get_city_rows_for_state($state_title) as $city) {
        if (!is_object($city)) {
            continue;
        }

        $title = trim((string) ($city->cityTitle ?? ''));
        $title_key = function_exists('tipax_normalize_location_lookup_text')
            ? tipax_normalize_location_lookup_text($title)
            : $title;
        if ($title_key === '' || mb_strlen($title_key, 'UTF-8') < 2) {
            continue;
        }

        if (mb_strpos($address_key, $title_key, 0, 'UTF-8') !== false) {
            $matches[$title] = mb_strlen($title_key, 'UTF-8');
        }
    }

    if (empty($matches)) {
        return '';
    }

    arsort($matches, SORT_NUMERIC);
    return (string) array_key_first($matches);
}

function tipax_extract_city_title_from_address($address)
{
    $address = is_scalar($address) ? trim((string) $address) : '';
    if ($address === '') {
        return '';
    }

    foreach (['/شهرستان\s+([^،,\r\n]+)/u', '/شهر\s+([^،,\r\n]+)/u'] as $pattern) {
        if (preg_match($pattern, $address, $matches) && !empty($matches[1])) {
            $title = trim((string) $matches[1]);
            if ($title !== '' && !is_numeric($title)) {
                return $title;
            }
        }
    }

    return '';
}

function tipax_resolve_order_address_location($order, $address_type = 'shipping')
{
    $address_type = $address_type === 'billing' ? 'billing' : 'shipping';
    $raw_country = tipax_get_order_address_field_value($order, $address_type, 'country');
    $raw_state = tipax_get_order_address_field_value($order, $address_type, 'state');
    $raw_city = tipax_get_order_address_field_value($order, $address_type, 'city');

    $stored_state_title = '';
    $stored_city_title = '';
    $stored_city_id = 0;
    if ($address_type === 'shipping' && is_object($order) && is_callable([$order, 'get_meta'])) {
        $stored_state_title = trim((string) $order->get_meta('_tipax_destination_state_title', true));
        $stored_city_title = trim((string) $order->get_meta('_tipax_destination_city_title', true));
        $stored_city_id = (int) $order->get_meta('_tipax_destination_city_id', true);
    }

    $state_title = tipax_resolve_state_title_for_display(
        $stored_state_title !== '' ? $stored_state_title : $raw_state,
        $raw_country
    );

    $address = trim(implode(' ', array_filter([
        tipax_get_order_address_field_value($order, $address_type, 'address_1'),
        tipax_get_order_address_field_value($order, $address_type, 'address_2'),
    ])));

    $city_title = '';
    if ($stored_city_title !== '' && !is_numeric($stored_city_title)) {
        $city_title = $stored_city_title;
    } elseif ($raw_city !== '' && !is_numeric($raw_city)) {
        $city_title = $raw_city;
    }

    if ($city_title === '' && is_numeric($raw_city) && function_exists('amdsp_get_city_name')) {
        $candidate = amdsp_get_city_name($raw_city);
        if (is_scalar($candidate) && trim((string) $candidate) !== '' && !is_numeric($candidate)) {
            $city_title = trim((string) $candidate);
        }
    }

    if (
        $city_title === ''
        && is_numeric($raw_city)
        && function_exists('tipax_get_external_city_title_from_transients')
    ) {
        $city_title = tipax_get_external_city_title_from_transients($raw_city, $state_title);
    }

    if ($city_title === '' && $stored_city_id > 0) {
        $city_title = tipax_get_tipax_city_title_for_display($stored_city_id, $state_title);
    }

    if ($city_title === '') {
        $city_title = tipax_find_city_title_in_address($address, $state_title);
    }

    if ($city_title === '') {
        $city_title = tipax_extract_city_title_from_address($address);
    }

    // A third-party city ID may coincidentally equal an unrelated Tipax ID.
    // Treat the raw number as a Tipax ID only after all title-based sources fail.
    if ($city_title === '' && is_numeric($raw_city)) {
        $city_title = tipax_get_tipax_city_title_for_display($raw_city, $state_title);
    }

    return [
        'country_code' => $raw_country,
        'state_value' => $raw_state,
        'state_title' => $state_title,
        'city_value' => $raw_city,
        'city_title' => $city_title,
        'address' => $address,
    ];
}

function tipax_store_resolved_order_destination_location($order)
{
    if (!is_object($order) || !is_callable([$order, 'update_meta_data'])) {
        return false;
    }

    $location = tipax_resolve_order_address_location($order, 'shipping');
    $changed = false;
    if ($location['state_title'] !== '') {
        $order->update_meta_data('_tipax_destination_state_title', $location['state_title']);
        $changed = true;
    }
    if ($location['city_title'] !== '') {
        $order->update_meta_data('_tipax_destination_city_title', $location['city_title']);
        $changed = true;
    }

    if (
        $location['city_value'] !== ''
        && $location['state_title'] !== ''
        && function_exists('get_city_code')
    ) {
        $city_id = get_city_code(
            $location['city_value'],
            $location['state_title'],
            $location['city_title'],
            false
        );
        if ($city_id !== null) {
            $order->update_meta_data('_tipax_destination_city_id', (int) $city_id);
            $changed = true;
        }
    }

    return $changed;
}

function tipax_filter_order_formatted_address_location($address, $order, $address_type)
{
    if (!is_array($address) || !tipax_should_resolve_order_address_location($order, $address_type)) {
        return $address;
    }

    $location = tipax_resolve_order_address_location($order, $address_type);
    if ($location['state_title'] !== '') {
        $address['state'] = $location['state_title'];
    }
    if ($location['city_title'] !== '') {
        $address['city'] = $location['city_title'];
    }

    return $address;
}

function tipax_filter_order_formatted_shipping_address($address, $order)
{
    return tipax_filter_order_formatted_address_location($address, $order, 'shipping');
}

function tipax_filter_order_formatted_billing_address($address, $order)
{
    return tipax_filter_order_formatted_address_location($address, $order, 'billing');
}

if (function_exists('add_filter')) {
    add_filter('woocommerce_states', 'tipax_sort_iranian_woocommerce_states', PHP_INT_MAX);
    add_filter('woocommerce_order_formatted_shipping_address', 'tipax_filter_order_formatted_shipping_address', 20, 2);
    add_filter('woocommerce_order_formatted_billing_address', 'tipax_filter_order_formatted_billing_address', 20, 2);
}
