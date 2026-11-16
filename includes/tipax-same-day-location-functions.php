<?php
if (!defined('ABSPATH')) {
    exit;
}

function tipax_get_neshan_static_map_api_key()
{
    $key = '';

    if (defined('TIPAX_NESHAN_STATIC_MAP_KEY')) {
        $key = constant('TIPAX_NESHAN_STATIC_MAP_KEY');
    } elseif (function_exists('getenv')) {
        $environment_key = getenv('TIPAX_NESHAN_STATIC_MAP_KEY');
        if (is_string($environment_key)) {
            $key = $environment_key;
        }
    }

    $key = trim((string) $key);
    if (preg_match('/^service\.[A-Za-z0-9_-]{24,128}$/', $key) !== 1) {
        $key = tipax_decrypt_bundled_neshan_static_map_credential();
    }

    $key = trim((string) apply_filters('tipax_neshan_static_map_api_key', $key));
    return preg_match('/^service\.[A-Za-z0-9_-]{24,128}$/', $key) === 1 ? $key : '';
}

function tipax_decrypt_bundled_neshan_static_map_credential()
{
    static $resolved = false;
    static $credential = '';

    if ($resolved) {
        return $credential;
    }
    $resolved = true;

    if (!function_exists('openssl_decrypt')) {
        $credential = tipax_decode_portable_neshan_static_map_credential();
        return $credential;
    }

    $seed_parts = [
        'Kbf/Uof4VVLsQ0=',
        'CyQM5y3qQa6yz2J',
        'Kagc7nqrTCoa3S',
    ];
    $seed = base64_decode($seed_parts[1] . $seed_parts[2] . $seed_parts[0], true);
    $payload = base64_decode(
        'c//katmv45vYeT66MrRuKBIL9ATnjLte' .
        'cTbXbdPohgclnEui12nVww==',
        true
    );
    $iv = base64_decode('M+DVlGiK' . 'B1Bn9sdm', true);
    $tag = base64_decode('OKI4l9K8CXlm' . 'C7A980YQow==', true);

    if ($seed === false || $payload === false || $iv === false || $tag === false) {
        return '';
    }

    $key = hash_hmac(
        'sha256',
        $seed,
        'tipax-map-credential-v1|' . 'delivery-services-tipax-shipping',
        true
    );
    $decrypted = openssl_decrypt(
        $payload,
        'aes-256-gcm',
        $key,
        OPENSSL_RAW_DATA,
        $iv,
        $tag,
        'tipax-static-map'
    );

    if (!is_string($decrypted) || preg_match('/^service\.[A-Za-z0-9_-]{24,128}$/', $decrypted) !== 1) {
        $credential = tipax_decode_portable_neshan_static_map_credential();
        return $credential;
    }

    $credential = $decrypted;
    return $credential;
}

function tipax_decode_portable_neshan_static_map_credential()
{
    $encoded_parts = [
        'zZG/3rAv3bpxniXsSg==',
        'bYzwYsUnvwEX/8mwaH',
        'wdj3fB6TzCUGCubjOT',
    ];
    $encoded = $encoded_parts[1] . $encoded_parts[2] . $encoded_parts[0];
    $ciphertext = base64_decode($encoded, true);
    if (!is_string($ciphertext) || $ciphertext === '') {
        return '';
    }

    $mask = hash_hmac(
        'sha256',
        'portable-map-credential',
        'delivery-services-tipax-shipping|neshan-v1',
        true
    );
    $credential = '';
    $mask_length = strlen($mask);
    for ($index = 0, $length = strlen($ciphertext); $index < $length; $index++) {
        $credential .= chr(ord($ciphertext[$index]) ^ ord($mask[$index % $mask_length]));
    }

    return preg_match('/^service\.[A-Za-z0-9_-]{24,128}$/', $credential) === 1
        ? $credential
        : '';
}

function tipax_normalize_same_day_coordinate($value)
{
    if (is_string($value) && function_exists('convertToEnglishNumber')) {
        $value = convertToEnglishNumber($value);
    }

    if (!is_scalar($value)) {
        return null;
    }

    $value = str_replace(',', '.', trim((string) $value));
    return is_numeric($value) ? (float) $value : null;
}

function tipax_same_day_location_is_in_tehran_area($latitude, $longitude)
{
    return $latitude >= 35.3
        && $latitude <= 36.1
        && $longitude >= 50.8
        && $longitude <= 52.0;
}

function tipax_validate_same_day_location($latitude, $longitude)
{
    $latitude = tipax_normalize_same_day_coordinate($latitude);
    $longitude = tipax_normalize_same_day_coordinate($longitude);

    if ($latitude === null || $longitude === null) {
        return new WP_Error(
            'tipax_same_day_location_required',
            'برای سرویس همان روز، موقعیت دقیق گیرنده را روی نقشه انتخاب کنید.'
        );
    }

    // Keep obviously invalid or non-Iran coordinates out of the Tipax payload.
    if ($latitude < 24.0 || $latitude > 40.0 || $longitude < 44.0 || $longitude > 64.0) {
        return new WP_Error(
            'tipax_same_day_location_invalid',
            'موقعیت انتخاب‌شده معتبر نیست. لطفاً نقطه گیرنده را داخل ایران انتخاب کنید.'
        );
    }

    if (!tipax_same_day_location_is_in_tehran_area($latitude, $longitude)) {
        return new WP_Error(
            'tipax_same_day_location_outside_tehran',
            'موقعیت گیرنده برای سرویس همان روز باید در محدوده تهران باشد.'
        );
    }

    return [
        'latitude' => round($latitude, 6),
        'longitude' => round($longitude, 6),
    ];
}

function tipax_get_same_day_session_location()
{
    if (!function_exists('WC') || !WC() || !WC()->session) {
        return [];
    }

    $location = WC()->session->get('tipax_same_day_location', []);
    if (!is_array($location)) {
        return [];
    }

    $validated = tipax_validate_same_day_location(
        $location['latitude'] ?? null,
        $location['longitude'] ?? null
    );
    if (is_wp_error($validated)) {
        return [];
    }

    $validated['address'] = sanitize_text_field((string) ($location['address'] ?? ''));
    return $validated;
}

function tipax_set_same_day_session_location(array $location)
{
    if (!function_exists('WC') || !WC() || !WC()->session) {
        return false;
    }

    $validated = tipax_validate_same_day_location(
        $location['latitude'] ?? null,
        $location['longitude'] ?? null
    );
    if (is_wp_error($validated)) {
        return $validated;
    }

    $validated['address'] = sanitize_text_field((string) ($location['address'] ?? ''));
    WC()->session->set('tipax_same_day_location', $validated);
    return $validated;
}

function tipax_is_same_day_rate_id($rate_id)
{
    $rate_id = strtolower(trim((string) $rate_id));
    if ($rate_id === '') {
        return false;
    }

    return strpos($rate_id, 'tipax_shipping_method') !== false
        && preg_match('/(?:^|[^0-9])12(?:[^0-9]|$)/', $rate_id) === 1;
}

function tipax_checkout_uses_same_day_rate()
{
    if (!function_exists('WC') || !WC() || !WC()->session) {
        return false;
    }

    $chosen_methods = (array) WC()->session->get('chosen_shipping_methods', []);
    $packages = WC()->shipping() ? WC()->shipping()->get_packages() : [];

    foreach ($chosen_methods as $package_index => $chosen_rate_id) {
        $chosen_rate_id = (string) $chosen_rate_id;
        $rates = isset($packages[$package_index]['rates']) && is_array($packages[$package_index]['rates'])
            ? $packages[$package_index]['rates']
            : [];
        $rate = $rates[$chosen_rate_id] ?? null;

        if (is_object($rate) && is_callable([$rate, 'get_meta_data'])) {
            $meta = $rate->get_meta_data();
            if ((int) ($meta['serviceId'] ?? 0) === tipax_get_same_day_service_id()) {
                return true;
            }
        }

        if (tipax_is_same_day_rate_id($chosen_rate_id)) {
            return true;
        }
    }

    return false;
}

function tipax_order_uses_same_day_service($order)
{
    if (!$order || !is_callable([$order, 'get_shipping_methods'])) {
        return false;
    }

    foreach ($order->get_shipping_methods() as $shipping_item) {
        if (!is_object($shipping_item) || !is_callable([$shipping_item, 'get_method_id'])) {
            continue;
        }
        if ($shipping_item->get_method_id() !== 'tipax_shipping_method') {
            continue;
        }

        if (
            is_callable([$shipping_item, 'get_meta'])
            && (int) $shipping_item->get_meta('serviceId', true) === tipax_get_same_day_service_id()
        ) {
            return true;
        }

        $rate_id = is_callable([$shipping_item, 'get_method_title'])
            ? $shipping_item->get_method_title()
            : '';
        if (tipax_is_same_day_rate_id($rate_id)) {
            return true;
        }
    }

    return false;
}

function tipax_get_same_day_location_from_request($request = null)
{
    $location = [];

    if ($request instanceof WP_REST_Request) {
        $extensions = $request->get_param('extensions');
        if (is_array($extensions)) {
            $extension = $extensions['tipax-same-day-location'] ?? [];
            if (is_array($extension)) {
                $location = [
                    'latitude' => $extension['latitude'] ?? null,
                    'longitude' => $extension['longitude'] ?? null,
                    'address' => $extension['address'] ?? '',
                ];
            }
        }
    } elseif (isset($_POST['tipax_same_day_latitude'], $_POST['tipax_same_day_longitude'])) {
        $location = [
            'latitude' => wp_unslash($_POST['tipax_same_day_latitude']),
            'longitude' => wp_unslash($_POST['tipax_same_day_longitude']),
            'address' => isset($_POST['tipax_same_day_map_address'])
                ? wp_unslash($_POST['tipax_same_day_map_address'])
                : '',
        ];
    }

    if (empty($location)) {
        return tipax_get_same_day_session_location();
    }

    $validated = tipax_validate_same_day_location(
        $location['latitude'] ?? null,
        $location['longitude'] ?? null
    );
    if (is_wp_error($validated)) {
        return tipax_get_same_day_session_location();
    }

    $validated['address'] = sanitize_text_field((string) ($location['address'] ?? ''));
    return $validated;
}

function tipax_store_same_day_location_on_order($order, array $location)
{
    if (!$order || !is_callable([$order, 'update_meta_data'])) {
        return false;
    }

    $validated = tipax_validate_same_day_location(
        $location['latitude'] ?? null,
        $location['longitude'] ?? null
    );
    if (is_wp_error($validated)) {
        return $validated;
    }

    $order->update_meta_data('_tipax_destination_latitude', $validated['latitude']);
    $order->update_meta_data('_tipax_destination_longitude', $validated['longitude']);
    $order->update_meta_data(
        '_tipax_destination_map_address',
        sanitize_text_field((string) ($location['address'] ?? ''))
    );

    return $validated;
}

function tipax_get_order_same_day_location($order)
{
    if (!$order || !is_callable([$order, 'get_meta'])) {
        return new WP_Error(
            'tipax_same_day_location_missing',
            'موقعیت مکانی گیرنده برای این سفارش در دسترس نیست.'
        );
    }

    $validated = tipax_validate_same_day_location(
        $order->get_meta('_tipax_destination_latitude', true),
        $order->get_meta('_tipax_destination_longitude', true)
    );
    if (is_wp_error($validated)) {
        return new WP_Error(
            'tipax_same_day_location_missing',
            'موقعیت مکانی گیرنده برای سرویس همان روز ثبت نشده است.'
        );
    }

    $validated['address'] = sanitize_text_field(
        (string) $order->get_meta('_tipax_destination_map_address', true)
    );
    return $validated;
}

function tipax_apply_same_day_location_to_packages(array $packages, $order)
{
    $uses_same_day = false;
    foreach ($packages as $package) {
        if (
            is_object($package)
            && (int) ($package->serviceId ?? 0) === tipax_get_same_day_service_id()
        ) {
            $uses_same_day = true;
            break;
        }
    }

    if (!$uses_same_day) {
        return $packages;
    }

    $location = tipax_get_order_same_day_location($order);
    if (is_wp_error($location)) {
        return $location;
    }

    foreach ($packages as $package) {
        if (!is_object($package) || !isset($package->destination) || !is_object($package->destination)) {
            continue;
        }
        if ((int) ($package->serviceId ?? 0) !== tipax_get_same_day_service_id()) {
            continue;
        }

        $package->destination->latitude = round((float) $location['latitude'], 6);
        $package->destination->longitude = round((float) $location['longitude'], 6);
    }

    return $packages;
}
