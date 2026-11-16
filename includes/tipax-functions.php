<?php
if (!defined('ABSPATH')) {
    exit;
}

function tipax_text($key)
{
    static $texts = [
        'financial_report' => '\u06af\u0632\u0627\u0631\u0634 \u0645\u0627\u0644\u06cc',
        'support' => '\u067e\u0634\u062a\u06cc\u0628\u0627\u0646\u06cc',
        'orders_title' => '\u0633\u0641\u0627\u0631\u0634\u0627\u062a \u062a\u06cc\u067e\u0627\u06a9\u0633',
        'orders_description' => '\u0633\u0641\u0627\u0631\u0634\u200c\u0647\u0627\u06cc \u06f1\u06f0 \u0631\u0648\u0632 \u0627\u062e\u06cc\u0631 \u06a9\u0647 \u0631\u0648\u0634 \u0627\u0631\u0633\u0627\u0644 \u062a\u06cc\u067e\u0627\u06a9\u0633 \u062f\u0627\u0631\u0646\u062f\u060c \u0628\u0631\u0627\u06cc \u062b\u0628\u062a \u0645\u062c\u062f\u062f \u06cc\u0627 \u0628\u0631\u0648\u0632\u0631\u0633\u0627\u0646\u06cc \u0648\u0636\u0639\u06cc\u062a \u0646\u0645\u0627\u06cc\u0634 \u062f\u0627\u062f\u0647 \u0645\u06cc\u200c\u0634\u0648\u0646\u062f.',
        'orders_count' => '\u0633\u0641\u0627\u0631\u0634',
        'orders_current_page' => '\u0635\u0641\u062d\u0647 \u0641\u0639\u0644\u06cc',
        'orders_batch_page' => '\u0628\u0631\u0648\u0632\u0631\u0633\u0627\u0646\u06cc \u062f\u0633\u062a\u0647\u200c\u0627\u06cc \u0627\u06cc\u0646 \u0635\u0641\u062d\u0647',
        'orders_batch_auto' => '\u0627\u062c\u0631\u0627\u06cc \u0628\u0686 \u062e\u0648\u062f\u06a9\u0627\u0631',
        'orders_batch_auto_suffix' => '\u062a\u0627\u06cc\u06cc',
        'orders_cron_info' => '\u0647\u0631 \u06f2\u06f4 \u0633\u0627\u0639\u062a \u0628\u0647\u200c\u0635\u0648\u0631\u062a \u062e\u0648\u062f\u06a9\u0627\u0631 \u0628\u0631\u0648\u0632\u0631\u0633\u0627\u0646\u06cc \u0645\u06cc\u200c\u0634\u0648\u062f.',
        'orders_last_run' => '\u0622\u062e\u0631\u06cc\u0646 \u0627\u062c\u0631\u0627',
        'orders_processed' => '\u0628\u0631\u0631\u0633\u06cc',
        'orders_updated' => '\u0645\u0648\u0641\u0642',
        'orders_skipped' => '\u067e\u0627\u06cc\u0627\u0646\u200c\u06cc\u0627\u0641\u062a\u0647',
        'orders_failed' => '\u062e\u0637\u0627',
        'batch_running' => '\u062f\u0631 \u062d\u0627\u0644 \u0627\u062c\u0631\u0627...',
        'batch_error' => '\u0628\u0631\u0648\u0632\u0631\u0633\u0627\u0646\u06cc \u062f\u0633\u062a\u0647\u200c\u0627\u06cc \u0628\u0627 \u062e\u0637\u0627 \u0645\u0648\u0627\u062c\u0647 \u0634\u062f.',
        'batch_success' => '\u0639\u0645\u0644\u06cc\u0627\u062a \u0628\u0627 \u0645\u0648\u0641\u0642\u06cc\u062a \u0627\u0646\u062c\u0627\u0645 \u0634\u062f.',
        'batch_no_orders' => '\u062f\u0631 \u0627\u06cc\u0646 \u0635\u0641\u062d\u0647 \u0633\u0641\u0627\u0631\u0634\u06cc \u0628\u0627 \u06a9\u062f \u0631\u0647\u06af\u06cc\u0631\u06cc \u0628\u0631\u0627\u06cc \u0628\u0631\u0648\u0632\u0631\u0633\u0627\u0646\u06cc \u062f\u0633\u062a\u0647\u200c\u0627\u06cc \u0648\u062c\u0648\u062f \u0646\u062f\u0627\u0631\u062f.',
        'logging_label' => '\u062b\u0628\u062a \u0644\u0627\u06af',
        'logging_toggle' => '\u062b\u0628\u062a \u0631\u062e\u062f\u0627\u062f\u0647\u0627 \u062f\u0631 \u0641\u0627\u06cc\u0644 \u0644\u0627\u06af \u0648 \u0646\u0645\u0627\u06cc\u0634 \u0645\u0646\u0648\u06cc \u0644\u0627\u06af',
        'logging_description' => '\u0627\u06af\u0631 \u063a\u06cc\u0631\u0641\u0639\u0627\u0644 \u0628\u0627\u0634\u062f\u060c \u0627\u0641\u0632\u0648\u0646\u0647 \u0647\u06cc\u0686 \u0644\u0627\u06af \u062c\u062f\u06cc\u062f\u06cc \u062b\u0628\u062a \u0646\u0645\u06cc\u200c\u06a9\u0646\u062f \u0648 \u0645\u0646\u0648\u06cc \u0644\u0627\u06af \u0647\u0645 \u0645\u062e\u0641\u06cc \u0645\u06cc\u200c\u0634\u0648\u062f.',
        'logging_disabled_notice' => '\u062b\u0628\u062a \u0644\u0627\u06af \u062f\u0631 \u062a\u0646\u0638\u06cc\u0645\u0627\u062a \u0627\u0641\u0632\u0648\u0646\u0647 \u063a\u06cc\u0631\u0641\u0639\u0627\u0644 \u0627\u0633\u062a.',
        'icon_financial' => '\ud83d\udcb9',
        'icon_support' => '\ud83c\udfab',
    ];

    if (!isset($texts[$key])) {
        return '';
    }

    $decoded = json_decode('"' . $texts[$key] . '"', true);
    return is_string($decoded) ? $decoded : '';
}

function tipax_normalize_tracking_code($tracking_code)
{
    if (!is_scalar($tracking_code)) {
        return '';
    }

    $tracking_code = strtolower(trim((string) $tracking_code));
    $tracking_code = preg_replace('/[^a-z0-9\-]/', '', $tracking_code);

    return is_string($tracking_code) ? $tracking_code : '';
}

function tipax_normalize_tracking_codes($tracking_codes)
{
    if (!is_array($tracking_codes)) {
        if (!is_scalar($tracking_codes)) {
            return [];
        }

        $tracking_codes = preg_split('/[\s,،]+/u', trim((string) $tracking_codes));
    }

    $normalized_codes = [];
    foreach ($tracking_codes as $tracking_code) {
        $tracking_code = tipax_normalize_tracking_code($tracking_code);
        if ($tracking_code !== '') {
            $normalized_codes[$tracking_code] = $tracking_code;
        }
    }

    return array_values($normalized_codes);
}

function tipax_normalize_tracking_codes_string($tracking_codes)
{
    return implode(' ', tipax_normalize_tracking_codes($tracking_codes));
}

function tipax_get_label_layout_options()
{
    return [
        'full' => 'لیبل عادی (تمام اطلاعات)',
        'without_sender' => 'لیبل بدون فرستنده',
        'barcode_only' => 'لیبل فقط بارکد',
        'without_sender_phone' => 'لیبل بدون شماره فرستنده',
    ];
}

function tipax_normalize_label_layout($layout)
{
    $layout = is_scalar($layout) ? sanitize_key((string) $layout) : '';
    return array_key_exists($layout, tipax_get_label_layout_options()) ? $layout : 'full';
}

function tipax_get_label_layout_label($layout)
{
    $options = tipax_get_label_layout_options();
    $layout = tipax_normalize_label_layout($layout);
    return $options[$layout];
}

function tipax_migrate_tracking_codes_to_lowercase()
{
    $migration_version = '1';
    if (get_option('tipax_tracking_codes_lowercase_migration', '') === $migration_version) {
        return;
    }

    global $wpdb;

    $queries = [
        $wpdb->prepare(
            "UPDATE {$wpdb->prefix}woocommerce_order_itemmeta SET meta_value = LOWER(meta_value) WHERE meta_key = %s",
            'tipax_tracking_code'
        ),
        $wpdb->prepare(
            "UPDATE {$wpdb->postmeta} SET meta_value = LOWER(meta_value) WHERE meta_key IN (%s, %s)",
            '_tipax_tracking_primary_code',
            '_tipax_tracking_codes'
        ),
    ];

    $hpos_meta_table = $wpdb->prefix . 'wc_orders_meta';
    if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $hpos_meta_table)) === $hpos_meta_table) {
        $queries[] = $wpdb->prepare(
            "UPDATE {$hpos_meta_table} SET meta_value = LOWER(meta_value) WHERE meta_key IN (%s, %s)",
            '_tipax_tracking_primary_code',
            '_tipax_tracking_codes'
        );
    }

    foreach ($queries as $query) {
        if ($wpdb->query($query) === false) {
            return;
        }
    }

    update_option('tipax_tracking_codes_lowercase_migration', $migration_version, false);
}

add_action('init', 'tipax_migrate_tracking_codes_to_lowercase', 1);

function tipax_move_logs_tab_last(array $tabs)
{
    if (!array_key_exists('tipax-logs', $tabs)) {
        return $tabs;
    }

    $logs_tab = $tabs['tipax-logs'];
    unset($tabs['tipax-logs']);
    $tabs['tipax-logs'] = $logs_tab;

    return $tabs;
}

function tipax_move_logs_submenu_last($menu_slug)
{
    global $submenu;

    if (empty($submenu[$menu_slug]) || !is_array($submenu[$menu_slug])) {
        return;
    }

    $log_items = [];
    $ordered_items = [];

    foreach ($submenu[$menu_slug] as $submenu_item) {
        if (!empty($submenu_item[2]) && $submenu_item[2] === 'tipax-logs') {
            $log_items[] = $submenu_item;
            continue;
        }

        $ordered_items[] = $submenu_item;
    }

    if (!empty($log_items)) {
        $submenu[$menu_slug] = array_merge($ordered_items, $log_items);
    }
}



function postalcodeValidate($string){

    if (strlen($string) != 10)
    {
        return null;
    }

    if (preg_match("/^([0-9])\\1*$/",$string))
    {
        return null;
    }

    if (preg_match("/^([0-9])\\1*$/",substr($string,0, 4)))
    {
        return null;
    }

    if (preg_match("[02]",$string))
    {
        return null;
    }

    if(!preg_match("/^(\d{5}-?\d{5})$/", $string)){
        return null;
    }

    if (substr($string,4,1) == "5")
    {
        return null;
    }

    if(!preg_match("/\b(?!(\d)\1{3})[13-9]{4}[1346-9]-?[013-9]{5}\b/", $string)){
        return null;
    }

    return convertToEnglishNumber($string);
}

function tipax_get_city_rows_for_state($state_name)
{
    global $wpdb;

    static $rows_by_state = [];
    $state_key = tipax_normalize_state_lookup_text($state_name);
    if ($state_key === '') {
        return [];
    }

    if (isset($rows_by_state[$state_key])) {
        return $rows_by_state[$state_key];
    }

    $table = $wpdb->prefix . 'tipax_cities';
    $database_states = $wpdb->get_col("SELECT DISTINCT stateTitle FROM {$table}");
    $matched_states = [];

    foreach ((array) $database_states as $database_state) {
        if (tipax_location_titles_match($database_state, $state_name)) {
            $matched_states[] = (string) $database_state;
        }
    }

    if (empty($matched_states)) {
        $rows_by_state[$state_key] = [];
        return [];
    }

    $matched_states = array_values(array_unique($matched_states));
    $state_placeholders = implode(', ', array_fill(0, count($matched_states), '%s'));
    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT cityId, cityTitle, stateTitle FROM {$table} WHERE stateTitle IN ({$state_placeholders}) ORDER BY cityId ASC",
            $matched_states
        )
    );

    // Equivalent province spellings must contribute all their cities, once per Tipax ID.
    $cities_by_id = [];
    foreach (is_array($rows) ? $rows : [] as $city) {
        if (is_object($city) && isset($city->cityId) && (int) $city->cityId > 0) {
            $cities_by_id[(int) $city->cityId] = $city;
        }
    }
    $rows_by_state[$state_key] = array_values($cities_by_id);

    return $rows_by_state[$state_key];
}

function tipax_find_city_id_by_title($city_title, $state_name)
{
    $matches = [];
    foreach (tipax_get_city_rows_for_state($state_name) as $city) {
        if (is_object($city) && tipax_location_titles_match($city->cityTitle ?? '', $city_title, true)) {
            $city_id = (int) ($city->cityId ?? 0);
            if ($city_id > 0) {
                $matches[$city_id] = $city_id;
            }
        }
    }

    return count($matches) === 1 ? reset($matches) : null;
}

function tipax_find_city_id_by_tipax_id($city_id, $state_name)
{
    $city_id = absint($city_id);
    if (function_exists('tipax_resolve_city_id_alias')) {
        $city_id = tipax_resolve_city_id_alias($city_id);
    }
    if ($city_id <= 0) {
        return null;
    }

    foreach (tipax_get_city_rows_for_state($state_name) as $city) {
        if (is_object($city) && (int) ($city->cityId ?? 0) === $city_id) {
            return $city_id;
        }
    }

    return null;
}

function tipax_get_external_city_title_from_transients($city_value, $state_name)
{
    if (!is_numeric($city_value)) {
        return '';
    }

    $sources = [
        ['states' => 'pws_states', 'cities' => 'pws_cities_'],
        ['states' => 'pws_tapin_states', 'cities' => 'pws_tapin_cities_'],
    ];

    foreach ($sources as $source) {
        $states = get_transient($source['states']);
        if (!is_array($states)) {
            continue;
        }

        $state_id = null;
        foreach ($states as $candidate_id => $candidate_title) {
            if (tipax_location_titles_match($candidate_title, $state_name)) {
                $state_id = $candidate_id;
                break;
            }
        }

        if ($state_id === null) {
            continue;
        }

        $cities = get_transient($source['cities'] . $state_id);
        if (!is_array($cities)) {
            continue;
        }

        foreach ($cities as $candidate_id => $candidate_title) {
            if ((string) $candidate_id === (string) $city_value && is_scalar($candidate_title)) {
                return trim((string) $candidate_title);
            }
        }
    }

    return '';
}

function tipax_get_checkout_city_context()
{
    if (!function_exists('WC') || !WC() || !WC()->session) {
        return [];
    }

    $context = WC()->session->get('tipax_checkout_city_context', []);
    return is_array($context) ? $context : [];
}

function get_city_code($cityName, $stateName, $cityTitle = '', $use_checkout_context = true)
{
    if ($cityName === null || $cityName === '') {
        return null;
    }

    $title_candidates = [];
    $has_authoritative_title = false;
    $lookup_state_name = is_scalar($stateName) ? trim((string) $stateName) : '';

    if (is_scalar($cityTitle) && trim((string) $cityTitle) !== '') {
        $title_candidates[] = trim((string) $cityTitle);
        $has_authoritative_title = true;
    }

    $checkout_context = [];
    if ($use_checkout_context) {
        $checkout_context = tipax_get_checkout_city_context();
        if (
            isset($checkout_context['city_value']) &&
            (string) $checkout_context['city_value'] === (string) $cityName
        ) {
            if (!empty($checkout_context['city_title'])) {
                $title_candidates[] = (string) $checkout_context['city_title'];
                $has_authoritative_title = true;
            }

            if (!empty($checkout_context['state_title'])) {
                $lookup_state_name = (string) $checkout_context['state_title'];
            }
        }
    }

    if ($lookup_state_name === '') {
        return null;
    }

    if (!is_numeric($cityName)) {
        $title_candidates[] = trim((string) $cityName);
    } else {
        if (function_exists('amdsp_get_city_name')) {
            $resolved_title = amdsp_get_city_name($cityName);
            if (is_scalar($resolved_title) && trim((string) $resolved_title) !== '') {
                $title_candidates[] = trim((string) $resolved_title);
                $has_authoritative_title = true;
            }
        }

        $transient_title = tipax_get_external_city_title_from_transients($cityName, $lookup_state_name);
        if ($transient_title !== '') {
            $title_candidates[] = $transient_title;
            $has_authoritative_title = true;
        }
    }

    $checked_titles = [];

    foreach ($title_candidates as $title_candidate) {
        $title_key = tipax_normalize_location_lookup_text($title_candidate);
        if ($title_key === '' || isset($checked_titles[$title_key])) {
            continue;
        }

        $checked_titles[$title_key] = true;
        $city_id = tipax_find_city_id_by_title($title_candidate, $lookup_state_name);
        if ($city_id !== null) {
            return $city_id;
        }
    }

    if (is_numeric($cityName) && class_exists('Tipax_City_Mapping_Service')) {
        $mapped_city_id = (new Tipax_City_Mapping_Service())->resolve_manual_city_id($cityName, $lookup_state_name);
        if ($mapped_city_id !== null) {
            return $mapped_city_id;
        }
    }

    if (is_numeric($cityName) && class_exists('Tipax_City_Mapping_Service')) {
        $mapped_city_id = (new Tipax_City_Mapping_Service())->resolve_city_id($cityName, $lookup_state_name);
        if ($mapped_city_id !== null) {
            return $mapped_city_id;
        }
    }

    if (is_numeric($cityName) && !$has_authoritative_title) {
        return tipax_find_city_id_by_tipax_id($cityName, $lookup_state_name);
    }

    return null;
}

function tipax_get_order_destination_city_id($order, $city_name, $state_name)
{
    $resolved_city_title = '';
    if (function_exists('tipax_resolve_order_address_location') && is_object($order)) {
        $location = tipax_resolve_order_address_location($order, 'shipping');
        if (!empty($location['state_title'])) {
            $state_name = $location['state_title'];
        }
        if (!empty($location['city_title'])) {
            $resolved_city_title = $location['city_title'];
        }
    }

    if (is_object($order) && is_callable([$order, 'get_meta'])) {
        $stored_state_title = (string) $order->get_meta('_tipax_destination_state_title', true);
        if ($stored_state_title !== '') {
            $state_name = $stored_state_title;
        }

        $stored_city_id = absint($order->get_meta('_tipax_destination_city_id', true));
        if ($stored_city_id > 0) {
            $verified_city_id = tipax_find_city_id_by_tipax_id($stored_city_id, $state_name);
            if ($verified_city_id !== null) {
                return $verified_city_id;
            }
        }

        $stored_city_title = (string) $order->get_meta('_tipax_destination_city_title', true);
        if ($stored_city_title !== '') {
            return get_city_code($city_name, $state_name, $stored_city_title, false);
        }
    }

    return get_city_code($city_name, $state_name, $resolved_city_title, false);
}

function tipax_pick_value($productVal, $settingsVal, $absoluteDefault)
{
    if ($productVal !== null && $productVal > 0) {
        return (float) $productVal;
    }

    if ($settingsVal !== null && $settingsVal > 0) {
        return (float) $settingsVal;
    }

    return (float) $absoluteDefault;
}

function tipax_to_kg($val, $weight_unit)
{
    $value = (float) $val;
    if ($value <= 0) {
        return 0.0;
    }

    switch (strtolower((string) $weight_unit)) {
        case 'kg':
            return $value;
        case 'g':
            return $value / 1000.0;
        case 'lbs':
        case 'lb':
            return $value * 0.45359237;
        case 'oz':
            return $value * 0.028349523125;
        default:
            return $value;
    }
}

function tipax_dim_ratio_to_cm($dimension_unit)
{
    switch (strtolower((string) $dimension_unit)) {
        case 'm':
            return 100.0;
        case 'cm':
            return 1.0;
        case 'mm':
            return 0.1;
        case 'in':
            return 2.54;
        case 'yd':
            return 91.44;
        default:
            return 1.0;
    }
}

function tipax_get_numeric_setting($settings, $key, $default = null)
{
    return (isset($settings[$key]) && is_numeric($settings[$key])) ? (float) $settings[$key] : $default;
}

function tipax_get_average_package_settings($settings)
{
    return [
        'weight' => tipax_get_numeric_setting($settings, 'avg_weight'),
        'length' => tipax_get_numeric_setting($settings, 'avg_length'),
        'width'  => tipax_get_numeric_setting($settings, 'avg_width'),
        'height' => tipax_get_numeric_setting($settings, 'avg_height'),
    ];
}

function tipax_get_selected_packing($settings)
{
    $saved_pack_type = isset($settings['packing_packtype']) ? (int) $settings['packing_packtype'] : 0;
    $selected_packing = [
        'id'        => isset($settings['packing_id']) ? (int) $settings['packing_id'] : 0,
        'packType'  => $saved_pack_type,
        // Content IDs are deterministic per pack type; ignore stale values saved by older releases.
        'packageContentId' => tipax_get_package_content_id_for_pack_type($saved_pack_type),
        'length'    => tipax_get_numeric_setting($settings, 'packing_length', 0.0),
        'width'     => tipax_get_numeric_setting($settings, 'packing_width', 0.0),
        'height'    => tipax_get_numeric_setting($settings, 'packing_height', 0.0),
        'maxLength' => tipax_get_numeric_setting($settings, 'packing_max_length', 0.0),
        'maxWidth'  => tipax_get_numeric_setting($settings, 'packing_max_width', 0.0),
        'maxHeight' => tipax_get_numeric_setting($settings, 'packing_max_height', 0.0),
        'minLength' => tipax_get_numeric_setting($settings, 'packing_min_length', 0.0),
        'minWidth'  => tipax_get_numeric_setting($settings, 'packing_min_width', 0.0),
        'minHeight' => tipax_get_numeric_setting($settings, 'packing_min_height', 0.0),
        'minWeight' => tipax_get_numeric_setting($settings, 'packing_min_weight', 0.0),
        'maxWeight' => tipax_get_numeric_setting($settings, 'packing_max_weight', 0.0),
    ];

    if (tipax_is_no_packing_id($selected_packing['id'])) {
        $selected_packing['id'] = 0;
        $selected_packing['packType'] = 0;
        $selected_packing['packageContentId'] = 9;
        return $selected_packing;
    }

    $packing_id = (string) $selected_packing['id'];
    if ($packing_id !== '0' && function_exists('tipax_get_admin_packing_catalog')) {
        $catalog = tipax_get_admin_packing_catalog();
        if (!empty($catalog['index'][$packing_id])) {
            // The API catalog is authoritative and repairs stale saved packType metadata.
            $selected_packing = array_merge($selected_packing, $catalog['index'][$packing_id]);
        }
    }

    if (!empty($selected_packing['id'])) {
        $payload_dimensions = tipax_get_selected_packing_payload_dimensions($selected_packing);
        $selected_packing['length'] = $payload_dimensions['length'];
        $selected_packing['width'] = $payload_dimensions['width'];
        $selected_packing['height'] = $payload_dimensions['height'];
    }

    return $selected_packing;
}

function tipax_get_api_credentials($settings)
{
    return [
        'username' => isset($settings['username']) ? $settings['username'] : null,
        'password' => isset($settings['password']) ? $settings['password'] : null,
        'apiKey' => isset($settings['apiKey']) ? $settings['apiKey'] : null,
    ];
}

function tipax_normalize_pickup_type($pickup_type)
{
    return in_array((string) $pickup_type, ['10', '20'], true) ? $pickup_type : 10;
}

function tipax_get_offline_fallback_settings($settings = null)
{
    if (!is_array($settings)) {
        $settings = get_option('woocommerce_tipax_shipping_method_settings', []);
    }

    $enabled = isset($settings['offline_fallback_enabled']) && $settings['offline_fallback_enabled'] === 'yes';
    $timeout = isset($settings['offline_fallback_timeout']) && is_numeric($settings['offline_fallback_timeout'])
        ? (int) $settings['offline_fallback_timeout']
        : 10;

    $timeout = max(1, min(120, $timeout));

    return [
        'enabled' => $enabled,
        'timeout' => $timeout,
    ];
}

function tipax_is_offline_fallback_enabled($settings = null)
{
    $offline_settings = tipax_get_offline_fallback_settings($settings);
    return !empty($offline_settings['enabled']);
}

function tipax_is_checkout_offline_registration_enabled($settings = null)
{
    return tipax_is_offline_fallback_enabled($settings);
}

function tipax_get_tipax_request_timeout($settings = null, $default = 10)
{
    $offline_settings = tipax_get_offline_fallback_settings($settings);
    $timeout = isset($offline_settings['timeout']) ? (int) $offline_settings['timeout'] : (int) $default;

    if ($timeout <= 0) {
        $timeout = (int) $default;
    }

    return max(1, min(120, $timeout));
}

function tipax_is_tipax_timeout_response($response)
{
    if (!is_object($response)) {
        return false;
    }

    if (!empty($response->tipax_is_timeout)) {
        return true;
    }

    if (!empty($response->tipax_error_type) && $response->tipax_error_type === 'timeout') {
        return true;
    }

    $message = tipax_extract_tipax_response_message($response, '');
    if ($message === '') {
        return false;
    }

    $message = strtolower($message);
    return strpos($message, 'timed out') !== false || strpos($message, 'curl error 28') !== false;
}

function tipax_is_tipax_transport_error_response($response)
{
    return is_object($response) && !empty($response->tipax_error_type);
}

function tipax_mark_order_tipax_registration_state($order_or_id, $state, $message = '', $reason = '')
{
    $order = $order_or_id instanceof WC_Order ? $order_or_id : wc_get_order($order_or_id);
    if (!$order) {
        return false;
    }

    $order->update_meta_data('_tipax_registration_state', sanitize_key($state));
    $order->update_meta_data('_tipax_registration_updated_at', current_time('mysql'));

    if ($message !== '') {
        $order->update_meta_data('_tipax_registration_message', sanitize_textarea_field($message));
    } else {
        $order->delete_meta_data('_tipax_registration_message');
    }

    if ($reason !== '') {
        $order->update_meta_data('_tipax_registration_reason', sanitize_key($reason));
    } else {
        $order->delete_meta_data('_tipax_registration_reason');
    }

    $order->save();
    return true;
}

function tipax_clear_order_tipax_registration_state($order_or_id)
{
    $order = $order_or_id instanceof WC_Order ? $order_or_id : wc_get_order($order_or_id);
    if (!$order) {
        return false;
    }

    $order->delete_meta_data('_tipax_registration_state');
    $order->delete_meta_data('_tipax_registration_message');
    $order->delete_meta_data('_tipax_registration_reason');
    $order->delete_meta_data('_tipax_registration_updated_at');
    $order->save();

    return true;
}

function tipax_get_order_tipax_registration_state_label($state, $reason = '')
{
    if ($state === 'offline_pending') {
        if ($reason === 'checkout_offline_mode') {
            return 'آفلاین - آماده ثبت دستی';
        }

        if ($reason === 'order_timeout') {
            return 'آفلاین - عدم پاسخ تیپاکس';
        }

        if ($reason === 'pricing_timeout') {
            return 'آفلاین - استعلام قیمت ناموفق';
        }

        return 'آفلاین - در انتظار ثبت تیپاکس';
    }

    if ($state === 'timeout_pending') {
        return 'ثبت نشده - نیازمند پیگیری';
    }

    if ($state === 'api_failed') {
        return 'ثبت نشده - خطای تیپاکس';
    }

    return '';
}

function tipax_store_value_to_api_value($value, $woocommerce_currency)
{
    return (strtoupper((string) $woocommerce_currency) === 'IRT') ? (float) ($value * 10.0) : (float) $value;
}

function tipax_format_admin_packing_label($title, $length, $width, $height)
{
    $label = (string) $title;

    if ($length > 0 && $width > 0 && $height > 0) {
        $label .= ' (' . $length . '×' . $width . '×' . $height . ' cm)';
    }

    return $label;
}


function convertToEnglishNumber($string) {
    $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $arabic = ['٩', '٨', '٧', '٦', '٥', '٤', '٣', '٢', '١','٠'];

    $num = range(0, 9);
    $convertedPersianNums = str_replace($persian, $num, $string);
    $englishNumbersOnly = str_replace($arabic, $num, $convertedPersianNums);

    return $englishNumbersOnly;
}


function tipaxPluginCookie( $key, $value = false, $expiration = false ) {
    if ( $value ) {
        // Set a cookie
        return setcookie( $key, $value, $expiration, COOKIEPATH, COOKIE_DOMAIN );
    }
    return isset( $_COOKIE[ $key ] ) ? $_COOKIE[ $key ] : false;
}



/**
 * Shpping File
 * This class is for adding actions of shipping to woocommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

//init the tipax shipping class
add_action('woocommerce_shipping_init', 'tipax_shipping_method_init');
//add tipax shipping method to shipping methods
add_filter('woocommerce_shipping_methods', 'add_tipax_shipping_method');
//add receiver phone field to checkout page
add_action('woocommerce_before_checkout_billing_form', 'tipax_add_receiver_phone_field');
//add receiver phone value and plaque to order
add_action('woocommerce_checkout_update_order_meta', 'update_order_meta');
add_action('woocommerce_checkout_update_order_review', 'tipax_capture_checkout_city_context', 5);
add_action('woocommerce_store_api_checkout_update_order_from_request', 'tipax_store_api_update_order_meta', 10, 2);
//validation for receiver phone field in checkout page
add_action('woocommerce_checkout_process', 'validate_fields' );



/**
 * init the tipax shipping class 
 */
function tipax_require_shipping_method_class()
{
    if (!class_exists('WC_Shipping_Method')) {
        $abstract_path = '';

        if (defined('WC_ABSPATH')) {
            $abstract_path = WC_ABSPATH . 'includes/abstracts/abstract-wc-shipping-method.php';
        } elseif (defined('WP_PLUGIN_DIR')) {
            $abstract_path = WP_PLUGIN_DIR . '/woocommerce/includes/abstracts/abstract-wc-shipping-method.php';
        }

        if ($abstract_path && file_exists($abstract_path)) {
            require_once $abstract_path;
        }
    }

    if (!class_exists('WC_Shipping_Method')) {
        return false;
    }

    if (!class_exists('WC_Tipax_Shipping_Method')) {
        require_once TSM_DIR . 'modules/shipping/class-tipax-shipping-method.php';
    }

    return class_exists('WC_Tipax_Shipping_Method');
}

function tipax_shipping_method_init()
{
    tipax_require_shipping_method_class();
}

/**
 * add tipax shipping method to shipping methods
 * @param $methods
 * @return array
 */
function add_tipax_shipping_method($methods)
{
    $methods["tipax_shipping_method"] = 'WC_Tipax_Shipping_Method';
    return $methods;
}


if (function_exists('digits_wc_merge_billing_phone')) {
    if(digits_wc_merge_billing_phone()){
        // Force billing_phone to be required at checkout
        add_filter('woocommerce_checkout_fields', function ($fields) {
            if (isset($fields['billing']['billing_phone'])) {
                $fields['billing']['billing_phone']['required'] = true;
                // Fix the label too → remove "(اختیاری)"
                $fields['billing']['billing_phone']['label'] = __('شماره موبایل', 'delivery-service-tipax-shipping');
            }
            return $fields;
        }, 20);
    }
}



/**
 * add receiver phone field to checkout page
 * @param $checkout
 */
/**
 * Add receiver phone field OR enforce Digits field
 */
function tipax_add_receiver_phone_field($checkout)
{
    // if WooCommerce billing phone exists → DO NOT SHOW custom field
    if ( isset( $checkout->checkout_fields['billing']['billing_phone'] ) ) {
        return;
    }

    // Check if Digits plugin is active
    if (function_exists('digits_wc_merge_billing_phone')) {
        if(digits_wc_merge_billing_phone()){
        return; // don't add custom field
        } else {
            if ( isset( $checkout->checkout_fields['billing']['billing_phone'] ) ) {
                return; // don't add custom field
            } else {
                /*echo "<pre>";
                print_r($checkout);
                die();*/
            
                /*echo "<pre>";
                print_r($checkout->checkout_fields);*/
            
                /*echo "<pre>";
                foreach ($checkout->checkout_fields as $fields){
                    foreach ($fields as $key=>$field) {
                        $customField[] = $key;
                    }
                }
            
                print_r($customField);
                die();*/
            
                echo '<div id="custom_checkout_field">';
            
                woocommerce_form_field('phone_field', array(
            
                    'type' => 'text',
            
                    'class' => array(
            
                        'my-field-class form-row-wide'
            
                    ) ,
            
                    'label' => __('Phone','delivery-service-tipax-shipping') ,
                    'required'=>true,
            
                    'placeholder' => __('Enter the phone number of receiver','delivery-service-tipax-shipping') ,
            
                ) ,
            
                    $checkout->get_value('phone_field'));
            
                echo '</div>';
            }
        }
    } else {
        if ( isset( $checkout->checkout_fields['billing']['billing_phone'] ) ) {
            return; // don't add custom field
        } else {
            echo '<div id="custom_checkout_field">';
        
            woocommerce_form_field('phone_field', array(
        
                'type' => 'text',
        
                'class' => array(
        
                    'my-field-class form-row-wide'
        
                ) ,
        
                'label' => __('Phone','delivery-service-tipax-shipping') ,
                'required'=>true,
        
                'placeholder' => __('Enter the phone number of receiver','delivery-service-tipax-shipping') ,
        
            ) ,
        
                $checkout->get_value('phone_field'));
        
            echo '</div>';
        }
    }
}



/**
 * add receiver phone value to order
 * add receiver plaque value to order
 * @param $order_id
 */
function tipax_capture_checkout_city_context($post_data)
{
    if (!function_exists('WC') || !WC() || !WC()->session) {
        return;
    }

    $checkout_data = [];
    parse_str((string) $post_data, $checkout_data);
    $address_type = !empty($checkout_data['ship_to_different_address']) ? 'shipping' : 'billing';
    $city_value = sanitize_text_field(wp_unslash($checkout_data[$address_type . '_city'] ?? ''));
    $city_title = sanitize_text_field(wp_unslash($checkout_data['tipax_' . $address_type . '_city_title'] ?? ''));
    $state_value = sanitize_text_field(wp_unslash($checkout_data[$address_type . '_state'] ?? ''));
    $state_title = sanitize_text_field(wp_unslash($checkout_data['tipax_' . $address_type . '_state_title'] ?? ''));

    if ($city_title === '' && $city_value !== '' && !is_numeric($city_value)) {
        $city_title = $city_value;
    }

    WC()->session->set('tipax_checkout_city_context', [
        'address_type' => $address_type,
        'city_value' => $city_value,
        'city_title' => $city_title,
        'state_value' => $state_value,
        'state_title' => $state_title,
    ]);
}

function update_order_meta($order_id){
    $order = wc_get_order($order_id);
    $order_changed = false;

    if (!empty($_POST['phone_field'])) {
        $phone = sanitize_text_field(wp_unslash($_POST['phone_field']));
        if ($order) {
            $order->update_meta_data('phone', $phone);
            $order_changed = true;
        } else {
            update_post_meta($order_id, 'phone', $phone);
        }
    }

    if ($order && $order->has_shipping_method('tipax_shipping_method')) {
        if (tipax_store_order_settlement_snapshot($order)) {
            $order_changed = true;
        }

        $context = tipax_get_checkout_city_context();
        $posted_address_type = !empty($_POST['ship_to_different_address']) ? 'shipping' : 'billing';
        $posted_city_title = sanitize_text_field(wp_unslash($_POST['tipax_' . $posted_address_type . '_city_title'] ?? ''));
        $recipient_uses_shipping = $order->get_shipping_country() !== '' || $order->get_shipping_city() !== '';
        $city_value = $recipient_uses_shipping ? $order->get_shipping_city() : $order->get_billing_city();
        $state_code = $recipient_uses_shipping ? $order->get_shipping_state() : $order->get_billing_state();
        $country_code = $recipient_uses_shipping ? $order->get_shipping_country() : $order->get_billing_country();
        $states = $country_code !== '' && function_exists('WC') && WC() ? WC()->countries->get_states($country_code) : [];
        $state_title = isset($states[$state_code]) ? $states[$state_code] : ($context['state_title'] ?? $state_code);
        $city_title = $posted_city_title !== '' ? $posted_city_title : (isset($context['city_title']) ? (string) $context['city_title'] : '');
        $city_id = get_city_code($city_value, $state_title, $city_title);

        if ($city_id !== null) {
            $order->update_meta_data('_tipax_destination_city_id', (int) $city_id);
            $order_changed = true;
        }

        if ($city_title !== '') {
            $order->update_meta_data('_tipax_destination_city_title', $city_title);
            $order_changed = true;
        }

        if ($state_title !== '') {
            $order->update_meta_data('_tipax_destination_state_title', $state_title);
            $order_changed = true;
        }
    }

    if ($order && $order_changed) {
        $order->save();
    }
}

function tipax_store_api_update_order_meta($order, $request)
{
    if (!$order instanceof WC_Order || !$order->has_shipping_method('tipax_shipping_method')) {
        return;
    }

    tipax_store_order_settlement_snapshot($order);
    if (function_exists('tipax_store_resolved_order_destination_location')) {
        tipax_store_resolved_order_destination_location($order);
    }
    $order->save();
}


/**
 * validation for receiver phone field in checkout page
 * validation for receiver plaque field in checkout page
 */
function validate_fields() {

    // Digits active + merge => require billing phone
    if (function_exists('digits_wc_merge_billing_phone') && digits_wc_merge_billing_phone()) {
        if (empty($_POST['billing_phone'])) {
            wc_add_notice(__('Please enter your Phone Number','delivery-service-tipax-shipping'), 'error');
        }
        return;
    }

    // Billing phone exists in checkout fields?
    $billing_phone_exists = isset($_POST['billing_phone']) && $_POST['billing_phone'] !== '';

    if ($billing_phone_exists) {
        if (empty($_POST['billing_phone'])) {
            wc_add_notice(__('Please enter your Phone Number','delivery-service-tipax-shipping'), 'error');
        }
        return;
    }

    // Otherwise → must fill custom phone field
    if (empty($_POST['phone_field'])) {
        wc_add_notice(__('Please enter your Phone Number','delivery-service-tipax-shipping'), 'error');
    }
}



/**
 * Thin compatibility layer for Tipax shipment hooks.
 * Real shipment/order registration logic lives in Tipax_Order_Service.
 */

add_action('woocommerce_before_thankyou', 'get_tipax_tracking_code', 2000, 1);

function tipax_same_city_bool($request, $accessToken, $originAddressesId, $destinationCityId): bool
{
    $shipment_module = new Tipax_Order_Service();
    return $shipment_module->same_city($request, $accessToken, $originAddressesId, $destinationCityId);
}

function tipax_build_packages_for_order(
    WC_Order $order,
    $request,
    $accessToken,
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
    $pickup_type = 10,
    $selected_packing = null,
    $cod_api_amount = 0
) {
    $shipment_module = new Tipax_Order_Service();
    return $shipment_module->build_packages_for_order(
        $order,
        $request,
        $accessToken,
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
        $cod_api_amount
    );
}

function get_tipax_tracking_code($order_id)
{
    $shipment_module = new Tipax_Order_Service();
    return $shipment_module->handle_thankyou_order($order_id);
}

function storeTipaxOrder($apiPath, $order, Request $request, stdClass $data, $accessToken, $order_id)
{
    $shipment_module = new Tipax_Order_Service();
    return $shipment_module->register_order($apiPath, $order, $request, $data, $accessToken, $order_id);
}

function get_tipax_tracking_code_after_order_failed($order_id)
{
    $shipment_module = new Tipax_Order_Service();
    return $shipment_module->retry_order_registration($order_id);
}

function storeTipaxOrderAfterFailed($apiPath, $order, Request $request, stdClass $data, $accessToken, $order_id)
{
    $shipment_module = new Tipax_Order_Service();
    return $shipment_module->register_order_after_failed($apiPath, $order, $request, $data, $accessToken, $order_id);
}

function tipax_resolve_receiver_phone(WC_Order $order)
{
    $shipment_module = new Tipax_Order_Service();
    return $shipment_module->resolve_receiver_phone($order);
}



// Register main menu for Tipax
add_action('admin_menu', 'register_tipax_shipping_menu');

function register_tipax_shipping_menu() {
    add_menu_page(
        __('Tipax Shipping', 'delivery-service-tipax-shipping'),
        __('Tipax Shipping', 'delivery-service-tipax-shipping'),
        'manage_options',
        'tipax-api-config',
        'tipax_api_config_page',
        'dashicons-admin-generic',
        56
    );
    
    add_submenu_page(
    'tipax-api-config',
        __('Test Connection', 'delivery-service-tipax-shipping'),
        __('Test Connection', 'delivery-service-tipax-shipping'),
        'manage_options',
        'tipax-test-connection',
        'tipax_test_connection_page'
    );
    
    add_submenu_page(
    'tipax-api-config',
        __('Addresses', 'delivery-service-tipax-shipping'),
        __('Addresses', 'delivery-service-tipax-shipping'),
        'manage_options',
        'tipax-addresses',
        'tipax_addresses_page'
    );

    add_submenu_page(
    'tipax-api-config',
        'شهرها',
        'شهرها',
        'manage_options',
        'tipax-cities',
        'tipax_cities_page'
    );
    
    add_submenu_page(
    'tipax-api-config',
        __('Orders', 'delivery-service-tipax-shipping'),
        __('Orders', 'delivery-service-tipax-shipping'),
        'manage_options',
        'tipax-orders',
        'tipax_orders_page_v2'
    );
    
    add_submenu_page(
    'tipax-api-config',
        __('Wallet', 'delivery-service-tipax-shipping'),
        __('Wallet', 'delivery-service-tipax-shipping'),
        'manage_options',
        'tipax-wallet',
        'tipax_wallet'
    );

    add_submenu_page(
    'tipax-api-config',
        tipax_text('financial_report'),
        tipax_text('financial_report'),
        'manage_options',
        'tipax-financial',
        'tipax_financial_page'
    );

    add_submenu_page(
    'tipax-api-config',
        tipax_text('support'),
        tipax_text('support'),
        'manage_options',
        'tipax-support',
        'tipax_support_page'
    );
    
    add_submenu_page(
    'tipax-api-config',
        __('Settings', 'delivery-service-tipax-shipping'),
        __('Settings', 'delivery-service-tipax-shipping'),
        'manage_options',
        'tipax-settings',
        'tipax_settings_page'
    );

    add_submenu_page(
    'tipax-api-config',
        'آموزش',
        'آموزش',
        'manage_options',
        'tipax-tutorial',
        'tipax_tutorial_page'
    );
    
    if (tipax_is_logging_enabled()) {
        add_submenu_page(
        'tipax-api-config',
            __('Logs', 'delivery-service-tipax-shipping'),
            __('Logs', 'delivery-service-tipax-shipping'),
            'manage_options',
            'tipax-logs',
            'tipax_logs_page'
        );
    }

    global $submenu;
    if (!empty($submenu['tipax-api-config']) && is_array($submenu['tipax-api-config'])) {
        foreach ($submenu['tipax-api-config'] as &$tipax_submenu_item) {
            if (empty($tipax_submenu_item[2])) {
                continue;
            }

            if ($tipax_submenu_item[2] === 'tipax-financial') {
                $tipax_submenu_item[0] = tipax_text('financial_report');
                $tipax_submenu_item[3] = tipax_text('financial_report');
            } elseif ($tipax_submenu_item[2] === 'tipax-support') {
                $tipax_submenu_item[0] = tipax_text('support');
                $tipax_submenu_item[3] = tipax_text('support');
            }
        }
        unset($tipax_submenu_item);
    }

    tipax_move_logs_submenu_last('tipax-api-config');
}

function tipax_api_config_page() {
    tipax_admin_tabs('tipax-api-config');

    if (!tipax_require_shipping_method_class()) {
        echo '<div class="notice notice-error"><p>' . esc_html__('WooCommerce shipping classes are not available.', 'delivery-service-tipax-shipping') . '</p></div>';
        return;
    }

    // Initialize the WC_Tipax_Shipping_Method class to access settings
    $shipping_method = new WC_Tipax_Shipping_Method();

    // Save settings if the form is submitted (this triggers WooCommerce's settings API)
    if ( isset( $_POST['woocommerce_tipax_shipping_method_username'], $_POST['woocommerce_tipax_shipping_method_password'], $_POST['woocommerce_tipax_shipping_method_apiKey'] ) ) {
        if (!current_user_can('manage_woocommerce') && !current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to manage these settings.', 'delivery-service-tipax-shipping'));
        }
        check_admin_referer('tipax_save_api_config', '_tipax_api_config_nonce');
        // Save settings to WooCommerce's options table
        $shipping_method->settings['username'] = sanitize_text_field( $_POST['woocommerce_tipax_shipping_method_username'] );
        $shipping_method->settings['password'] = sanitize_text_field( $_POST['woocommerce_tipax_shipping_method_password'] );
        $shipping_method->settings['apiKey']  = sanitize_text_field( $_POST['woocommerce_tipax_shipping_method_apiKey'] );
        
        // Ensure 'enabled' is saved as 'yes' if checkbox is checked, otherwise 'no'
        $shipping_method->settings['enabled']  = isset( $_POST['woocommerce_tipax_shipping_method_enabled'] ) ? 'yes' : 'no';  
        $shipping_method->settings['hub_usage_metrics'] = isset($_POST['woocommerce_tipax_shipping_method_hub_usage_metrics']) ? 'yes' : 'no';

        // Save settings using WooCommerce's process_admin_options method
        $shipping_method->process_admin_options();

        echo '<div class="updated"><p>' . __( 'Settings saved.', 'delivery-service-tipax-shipping' ) . '</p></div>';
    }
    // Retrieve saved settings from the class
    $tipax_username = isset($shipping_method->settings['username']) ? $shipping_method->settings['username'] : '';
    $tipax_password = isset($shipping_method->settings['password']) ? $shipping_method->settings['password'] : '';
    $tipax_api_key  = isset($shipping_method->settings['apiKey']) ? $shipping_method->settings['apiKey'] : '';
    $tipax_enabled  = $shipping_method->get_option('enabled');  // Use get_option to get 'enabled' properly
    $tipax_usage_metrics = $shipping_method->get_option('hub_usage_metrics', 'no');
    $tipax_usage_field = $shipping_method->form_fields['hub_usage_metrics'];

    ?>
    <form method="post" action="" class="tipax-config-form">
        <?php wp_nonce_field('tipax_save_api_config', '_tipax_api_config_nonce'); ?>
        <div class="tipax-card-head">
            <div class="tipax-card-head-left">
                <h2><?php esc_html_e('API Configuration', 'delivery-service-tipax-shipping'); ?></h2>
                <p><?php esc_html_e('اطلاعات اتصال API تیپاکس را وارد و ذخیره کنید.', 'delivery-service-tipax-shipping'); ?></p>
            </div>
            <div class="tipax-toggle-pill">
                <span class="tipax-status-dot"></span>
                <span class="tipax-toggle-label"><?php echo $tipax_enabled === 'yes' ? esc_html__('فعال', 'delivery-service-tipax-shipping') : esc_html__('غیرفعال', 'delivery-service-tipax-shipping'); ?></span>
                <label class="tipax-switch" for="enabled">
                    <input type="checkbox" name="woocommerce_tipax_shipping_method_enabled" id="enabled" value="1" <?php checked($tipax_enabled, 'yes'); ?> />
                    <span class="tipax-switch-track"></span>
                </label>
            </div>
        </div>

        <div class="tipax-api-credentials-info" role="note">
            <p><?php esc_html_e('برای استفاده از خدمات تیپاکس، لطفاً نام کاربری و رمز عبور حساب ای‌تیپاکس خود را وارد کنید.', 'delivery-service-tipax-shipping'); ?></p>
            <p><?php esc_html_e('در صورتی که هنوز حساب کاربری ندارید، ابتدا از طریق وب‌سایت ای‌تیپاکس ثبت‌نام کنید:', 'delivery-service-tipax-shipping'); ?><br />
                <a href="https://etipaxco.com/" target="_blank" rel="noopener noreferrer"><span aria-hidden="true">🔗</span> <bdi dir="ltr">https://etipaxco.com/</bdi></a>
            </p>
        </div>

        <div class="tipax-section-label"><span><?php esc_html_e('اطلاعات حساب کاربری', 'delivery-service-tipax-shipping'); ?></span></div>

        <div class="tipax-field">
            <label class="tipax-field-label" for="tipaxusername">
                <?php esc_html_e('Username', 'delivery-service-tipax-shipping'); ?><span class="tipax-required">*</span>
                <span><?php esc_html_e('Username of your Tipax account', 'delivery-service-tipax-shipping'); ?></span>
            </label>
            <div class="tipax-field-control">
                <svg class="tipax-field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 7L2 7"/></svg>
                <input type="text" name="woocommerce_tipax_shipping_method_username" id="tipaxusername" value="<?php echo esc_attr($tipax_username); ?>" placeholder="example@email.com" required />
            </div>
        </div>

        <div class="tipax-field">
            <label class="tipax-field-label" for="tipax-password-input">
                <?php esc_html_e('Password', 'delivery-service-tipax-shipping'); ?><span class="tipax-required">*</span>
                <span><?php esc_html_e('Password of your Tipax account', 'delivery-service-tipax-shipping'); ?></span>
            </label>
            <div class="tipax-field-control tipax-has-inline-action tipax-has-password-action">
                <svg class="tipax-field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                <input type="password" name="woocommerce_tipax_shipping_method_password" id="tipax-password-input" value="<?php echo esc_attr($tipax_password); ?>" placeholder="••••••••" required />
                <button type="button" class="tipax-inline-action tipax-password-toggle" data-target="tipax-password-input">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    <span><?php esc_html_e('نمایش', 'delivery-service-tipax-shipping'); ?></span>
                </button>
            </div>
        </div>

        <div class="tipax-section-label"><span><?php esc_html_e('کلید API', 'delivery-service-tipax-shipping'); ?></span></div>

        <div class="tipax-field">
            <label class="tipax-field-label" for="apiKey">
                <?php esc_html_e('API Key', 'delivery-service-tipax-shipping'); ?><span class="tipax-required">*</span>
                <span><?php esc_html_e('This Tipax API Key is located in your profile page', 'delivery-service-tipax-shipping'); ?></span>
            </label>
            <div class="tipax-field-control tipax-has-inline-action tipax-has-api-action">
                <svg class="tipax-field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m21 2-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0 3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>
                <input type="text" name="woocommerce_tipax_shipping_method_apiKey" id="apiKey" value="<?php echo esc_attr($tipax_api_key); ?>" placeholder="<?php esc_attr_e('کلید API را وارد کنید', 'delivery-service-tipax-shipping'); ?>" required />
                <div class="tipax-api-actions">
                    <button type="button" class="tipax-inline-action tipax-copy-key" data-target="apiKey">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                        <span><?php esc_html_e('کپی', 'delivery-service-tipax-shipping'); ?></span>
                    </button>
                </div>
            </div>
        </div>

        <div class="tipax-field">
            <div class="tipax-field-label"><?php echo esc_html($tipax_usage_field['title']); ?></div>
            <div class="tipax-field-control">
                <label class="tipax-usage-option" for="tipax-hub-usage-metrics">
                    <input type="checkbox" name="woocommerce_tipax_shipping_method_hub_usage_metrics" id="tipax-hub-usage-metrics" value="yes" <?php checked($tipax_usage_metrics, 'yes'); ?> />
                    <span><?php echo esc_html($tipax_usage_field['label']); ?></span>
                </label>
                <p class="tipax-usage-description"><?php echo esc_html($tipax_usage_field['description']); ?></p>
            </div>
        </div>

        <div class="tipax-card-foot">
            <div class="tipax-foot-hint"><?php esc_html_e('فیلدهای دارای', 'delivery-service-tipax-shipping'); ?> <strong>*</strong> <?php esc_html_e('اجباری هستند', 'delivery-service-tipax-shipping'); ?></div>
            <button type="submit" class="tipax-save-button">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                <?php esc_html_e('ذخیره تنظیمات', 'delivery-service-tipax-shipping'); ?>
            </button>
        </div>
    </form>
    <div id="tipax-admin-toast" class="tipax-admin-toast" aria-live="polite"></div>
    <?php
}

add_action('wp_ajax_test_connection', 'handle_test_connection_ajax');

function handle_test_connection_ajax() {
    $shipping_method_v2 = new WC_Tipax_V2();
    
    $response_message = $shipping_method_v2->checkApiKey();

    echo $response_message;
    
    wp_die();
}

function tipax_test_connection_page() {
    tipax_admin_tabs('tipax-test-connection'); // Optional tab

    echo '<div class="tipax-action-panel tipax-diagnostics-panel">';
    echo '<div class="tipax-action-icon">' . tipax_admin_icon('search') . '</div>';
    echo '<div class="tipax-action-content">';
    echo '<h2>'.__( 'Test API & Environment', 'delivery-service-tipax-shipping' ).'</h2>';
    echo '<p>'.__('Click the button below to run a full diagnostics test.', 'delivery-service-tipax-shipping' ).'</p>';
    echo '</div>';
    echo '<button id="tipax_run_diagnostics" class="button button-primary tipax-action-button">'.__( 'Run Diagnostics', 'delivery-service-tipax-shipping' ).'</button>';
    echo '</div>';
    echo '<div id="tipax_diagnostics_results" class="tipax-result-area"><div class="tipax-empty-state">' . tipax_admin_icon('search') . '<p>برای شروع، تست اتصال و محیط را اجرا کنید.</p></div></div>';

    ?>
    <script>
        jQuery(document).ready(function($) {
            $('#tipax_run_diagnostics').on('click', function() {
                $('#tipax_diagnostics_results').html('<div class="tipax-loading-state"><span></span><p><?php echo esc_js(__('Testing in progress...', 'delivery-service-tipax-shipping')); ?></p></div>');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'tipax_run_diagnostics'
                    },
                    success: function(response) {
                        $('#tipax_diagnostics_results').html(response);
                    },
                    error: function() {
                        $('#tipax_diagnostics_results').html('<div class="notice notice-error"><p><?php echo esc_js(__('Something went wrong while testing.', 'delivery-service-tipax-shipping')); ?></p></div>');
                    }
                });
            });
        });
    </script>
    <?php
}

add_action('wp_ajax_tipax_run_diagnostics', 'tipax_run_diagnostics');

function tipax_run_diagnostics() {
    $results = [];

    // 1. Tipax Shipping Method Enabled Check
    if (!tipax_require_shipping_method_class()) {
        $results[] = [
            'name' => __('Tipax Shipping Method Status', 'delivery-service-tipax-shipping'),
            'status' => __('Error', 'delivery-service-tipax-shipping'),
            'message' => __('WooCommerce shipping classes are not available.', 'delivery-service-tipax-shipping')
        ];
    } else {
    try {
        $shipping_method = new WC_Tipax_Shipping_Method(); // Make sure this class is available and autoloaded
    
        $enabled_status = isset($shipping_method->settings['enabled']) ? $shipping_method->settings['enabled'] : 'no';
    
        $results[] = [
            'name' => __('Tipax Shipping Method Status', 'delivery-service-tipax-shipping'),
            'status' => ($enabled_status === 'yes') ? __('✅ Enabled', 'delivery-service-tipax-shipping') : __('❌ Disabled', 'delivery-service-tipax-shipping'),
            'message' => ($enabled_status === 'yes') ? __('Shipping method is currently active', 'delivery-service-tipax-shipping') : __('Shipping method is currently inactive', 'delivery-service-tipax-shipping')
        ];
    } catch (Throwable $e) {
        $results[] = [
            'name' => __('Tipax Shipping Method Status', 'delivery-service-tipax-shipping'),
            'status' => __('❌ Error', 'delivery-service-tipax-shipping'),
            'message' => sprintf(__('Could not read settings: %s', 'delivery-service-tipax-shipping'), $e->getMessage())
        ];
    }
    }


    // 2. Ping
    $ping = @fsockopen("omapi.tipax.ir", 443, $errno, $errstr, 5);
    $results[] = [
        'name' => __('Ping Tipax Server (443)', 'delivery-service-tipax-shipping'),
        'status' => $ping ? __('✅ Success', 'delivery-service-tipax-shipping') : __('❌ Failed', 'delivery-service-tipax-shipping'),
        'message' => $ping ? __('Connected successfully', 'delivery-service-tipax-shipping') : sprintf(__('Error: %1$s (%2$d)', 'delivery-service-tipax-shipping'), $errstr, $errno)
    ];
    if ($ping) fclose($ping);

    // 3. SSL Handshake
    $ch = curl_init("https://omapi.tipax.ir");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_exec($ch);
    $ssl_error = curl_error($ch);
    $results[] = [
        'name' => __('TLS/SSL Handshake', 'delivery-service-tipax-shipping'),
        'status' => empty($ssl_error) ? __('✅ Success', 'delivery-service-tipax-shipping') : __('❌ Failed', 'delivery-service-tipax-shipping'),
        'message' => empty($ssl_error) ? __('Handshake completed', 'delivery-service-tipax-shipping') : $ssl_error
    ];
    curl_close($ch);

    // 4. WordPress
    $wp_version = get_bloginfo('version');
    $results[] = [
        'name' => __('WordPress Version', 'delivery-service-tipax-shipping'),
        'status' => version_compare($wp_version, '5.8', '>=') ? __('✅ OK', 'delivery-service-tipax-shipping') : __('❌ Too old', 'delivery-service-tipax-shipping'),
        'message' => sprintf(__('Current version: %s', 'delivery-service-tipax-shipping'), $wp_version)
    ];

    // 5. WooCommerce
    if (class_exists('WooCommerce')) {
        $wc_version = WC()->version;
        $results[] = [
            'name' => __('WooCommerce Version', 'delivery-service-tipax-shipping'),
            'status' => version_compare($wc_version, '6.0', '>=') ? __('✅ OK', 'delivery-service-tipax-shipping') : __('❌ Too old', 'delivery-service-tipax-shipping'),
            'message' => sprintf(__('Current version: %s', 'delivery-service-tipax-shipping'), $wc_version)
        ];
    } else {
        $results[] = [
            'name' => __('WooCommerce Check', 'delivery-service-tipax-shipping'),
            'status' => __('❌ Not Installed', 'delivery-service-tipax-shipping'),
            'message' => __('WooCommerce plugin is not active.', 'delivery-service-tipax-shipping')
        ];
    }
    
    // 6. PHP
    if (version_compare(PHP_VERSION, '7.4', '>=')) {
        $results[] = [
            'name' => __('PHP Version', 'delivery-service-tipax-shipping'),
            'status' => __('✅ OK', 'delivery-service-tipax-shipping'),
            'message' => sprintf(__('PHP version is %s', 'delivery-service-tipax-shipping'), PHP_VERSION)
        ];
    } else {
        $results[] = [
            'name' => __('PHP Version', 'delivery-service-tipax-shipping'),
            'status' => __('❌ Too Old', 'delivery-service-tipax-shipping'),
            'message' => sprintf(__('Current PHP version is %s. Please upgrade to 7.4 or higher.', 'delivery-service-tipax-shipping'), PHP_VERSION)
        ];
    }

    // 7. curl
    if (!function_exists('curl_version')) {
        $results[] = [
            'name' => __('PHP cURL Extension', 'delivery-service-tipax-shipping'),
            'status' => __('❌ Not Installed', 'delivery-service-tipax-shipping'),
            'message' => __('Your server is missing the cURL extension. API requests may fail.', 'delivery-service-tipax-shipping')
        ];
    } else {
        $results[] = [
            'name' => __('PHP cURL Extension', 'delivery-service-tipax-shipping'),
            'status' => __('✅ Installed', 'delivery-service-tipax-shipping'),
            'message' => __('cURL is available and used by WordPress for HTTP requests.', 'delivery-service-tipax-shipping')
        ];
    }
    
    // 8. Tipax Addresses Fetch Test
    try {
        delete_transient('tipax_addresses');
        delete_transient('addresses_loaded');

        $components = tipax_get_address_service_components();
        $addresses_response = $components['service']->fetch_addresses();
        $address_records = tipax_get_tipax_address_list($addresses_response);
        $address_rows = tipax_collect_address_rows($addresses_response);

        if (empty($address_rows)) {
            $components['auth']->check_api_key(true);
            $addresses_response = $components['service']->fetch_addresses();
            $address_records = tipax_get_tipax_address_list($addresses_response);
            $address_rows = tipax_collect_address_rows($addresses_response);
        }

        if (!empty($address_rows)) {
            update_option('tipax_has_address', 'yes');

            $first_row = $address_rows[0];
            $example_address = trim((string) ($first_row['full_address'] ?? ''));
            if ($example_address === '') {
                $example_address = (string) ($first_row['title'] ?? 'N/A');
            }

            $results[] = [
                'name' => __('Tipax Address Fetch', 'delivery-service-tipax-shipping'),
                'status' => __('✅ Success', 'delivery-service-tipax-shipping'),
                'message' => sprintf(
                    __('Loaded %1$d address(es). Example: %2$s', 'delivery-service-tipax-shipping'),
                    count($address_records),
                    $example_address !== '' ? $example_address : 'N/A'
                )
            ];
        } else {
            update_option('tipax_has_address', 'no');

            $results[] = [
                'name' => __('Tipax Address Fetch', 'delivery-service-tipax-shipping'),
                'status' => __('❌ Failed', 'delivery-service-tipax-shipping'),
                'message' => tipax_extract_tipax_response_message(
                    $addresses_response,
                    __('No addresses returned from API or response malformed.', 'delivery-service-tipax-shipping')
                )
            ];
        }
    } catch (Throwable $e) {
        update_option('tipax_has_address', 'no');

        $results[] = [
            'name' => __('Tipax Address Fetch', 'delivery-service-tipax-shipping'),
            'status' => __('❌ Error', 'delivery-service-tipax-shipping'),
            'message' => $e->getMessage()
        ];
    }

    // 9. API Check
    try {
        $shipping_method = new WC_Tipax_V2();
        $api_response = $shipping_method->checkApiKey();
        $ok = (stripos($api_response, 'موفق') !== false || stripos($api_response, 'Success') !== false);
        update_option('tipax_test_success', $ok ? 'yes' : 'no');
        $results[] = [
            'name' => __('Tipax API Key Validation', 'delivery-service-tipax-shipping'),
            'status' => $ok ? __('✅ Success', 'delivery-service-tipax-shipping') : __('❌ Failed', 'delivery-service-tipax-shipping'),
            'message' => $api_response
        ];
    } catch (Exception $e) {
        $results[] = [
            'name' => __('Tipax API Key Validation', 'delivery-service-tipax-shipping'),
            'status' => __('❌ Error', 'delivery-service-tipax-shipping'),
            'message' => $e->getMessage()
        ];
        update_option('tipax_test_success', 'no');
    }

    // Output results as table HTML
    echo '<table class="widefat striped tipax-data-table tipax-diagnostics-table">';
    echo '<thead><tr><th>'.__( 'Check', 'delivery-service-tipax-shipping' ).'</th><th>'.__( 'Status', 'delivery-service-tipax-shipping' ).'</th><th>'.__( 'Message', 'delivery-service-tipax-shipping' ).'</th></tr></thead><tbody>';
    foreach ($results as $row) {
        echo '<tr>';
        echo '<td>' . tipax_admin_icon('admin-tools') . esc_html($row['name']) . '</td>';
        echo '<td>' . tipax_admin_diagnostic_status($row['status']) . '</td>';
        echo '<td>' . esc_html($row['message']) . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';

    wp_die();
}

add_action('wp_ajax_test_connection_ajax', function() {
    $shipping_method = new WC_Tipax_V2();
    $result = $shipping_method->checkApiKey();

    if (stripos($result, 'موفق') !== false || stripos($result, 'Success') !== false) {
        update_option('tipax_test_success', 'yes');
        wp_send_json(['success' => true, 'message' => 'اتصال موفق بود.']);
    } else {
        update_option('tipax_test_success', 'no');
        wp_send_json(['success' => false, 'message' => 'اتصال موفق نبود.']);
    }

    wp_die();
});

function tipax_admin_icon($name) {
    return '<span class="dashicons dashicons-' . esc_attr(sanitize_key($name)) . ' tipax-icon" aria-hidden="true"></span>';
}

function tipax_admin_diagnostic_status($status) {
    $is_error = strpos($status, '❌') !== false;
    $label = trim(str_replace(['✅', '❌', "\u{FE0F}"], '', (string) $status));

    return '<span class="tipax-diagnostic-status' . ($is_error ? ' is-error' : '') . '">'
        . tipax_admin_icon($is_error ? 'dismiss' : 'yes-alt')
        . esc_html($label) . '</span>';
}

function tipax_get_admin_tabs() {
    $tabs = [
        'tipax-api-config' => [
            'label' => __('Connection', 'delivery-service-tipax-shipping'),
            'icon'  => 'admin-plugins',
        ],
        'tipax-test-connection' => [
            'label' => __('Test Connection', 'delivery-service-tipax-shipping'),
            'icon'  => 'search',
        ],
        'tipax-addresses' => [
            'label' => __('Addresses', 'delivery-service-tipax-shipping'),
            'icon'  => 'location',
        ],
        'tipax-cities' => [
            'label' => 'شهرها',
            'icon'  => 'building',
        ],
        'tipax-orders' => [
            'label' => __('Orders', 'delivery-service-tipax-shipping'),
            'icon'  => 'archive',
        ],
        'tipax-wallet' => [
            'label' => __('Wallet', 'delivery-service-tipax-shipping'),
            'icon'  => 'money-alt',
        ],
        'tipax-settings' => [
            'label' => __('Settings', 'delivery-service-tipax-shipping'),
            'icon'  => 'admin-generic',
        ],
        'tipax-tutorial' => [
            'label' => 'آموزش',
            'icon'  => 'video-alt3',
        ],
        'tipax-logs' => [
            'label' => __('Logs', 'delivery-service-tipax-shipping'),
            'icon'  => 'media-text',
        ],
    ];
    if (!tipax_is_logging_enabled()) {
        unset($tabs['tipax-logs']);
    }

    return $tabs;
}

function tipax_is_admin_page() {
    if (!is_admin() || !isset($_GET['page'])) {
        return false;
    }

    $page = sanitize_key(wp_unslash($_GET['page']));
    if ($page === 'tipax-financial' || $page === 'tipax-support') {
        return true;
    }

    return array_key_exists($page, tipax_get_admin_tabs());
}

function tipax_is_wc_shipping_settings_page() {
    if (!is_admin() || !isset($_GET['page'], $_GET['tab'])) {
        return false;
    }

    $page = sanitize_key(wp_unslash($_GET['page']));
    $tab = sanitize_key(wp_unslash($_GET['tab']));
    $section = isset($_GET['section']) ? sanitize_key(wp_unslash($_GET['section'])) : '';

    return $page === 'wc-settings' && $tab === 'shipping' && $section === 'tipax_shipping_method';
}

add_filter('admin_body_class', function ($classes) {
    if (tipax_is_admin_page()) {
        $classes .= ' tipax-admin-page';
    }

    return $classes;
});

function tipax_suppress_external_admin_notices() {
    if (!tipax_is_admin_page()) {
        return;
    }

    remove_all_actions('admin_notices');
    remove_all_actions('all_admin_notices');
    remove_all_actions('network_admin_notices');
    remove_all_actions('user_admin_notices');
}
add_action('current_screen', 'tipax_suppress_external_admin_notices', 999);

function tipax_close_admin_frame() {
    global $tipax_admin_frame_open;

    if (empty($tipax_admin_frame_open)) {
        return;
    }

    echo '</section></div>';
    $tipax_admin_frame_open = false;
}

function tipax_admin_tabs($current = 'connection') {
    global $tipax_admin_frame_open;

    $tabs = tipax_get_admin_tabs();
    $tabs['tipax-financial'] = [
        'label' => tipax_text('financial_report'),
        'icon'  => 'chart-bar',
    ];

    $tabs['tipax-support'] = [
        'label' => tipax_text('support'),
        'icon'  => 'sos',
    ];

    $tabs['tipax-financial']['label'] = tipax_text('financial_report');
    $tabs['tipax-financial']['icon'] = 'chart-bar';
    $tabs['tipax-support']['label'] = tipax_text('support');
    $tabs['tipax-support']['icon'] = 'sos';
    $tabs = tipax_move_logs_tab_last($tabs);

    if ($current === 'connection') {
        $current = 'tipax-api-config';
    }

    if (empty($tabs[$current])) {
        $current = 'tipax-api-config';
    }

    if (empty($tipax_admin_frame_open)) {
        $tipax_admin_frame_open = true;
        add_action('admin_footer', 'tipax_close_admin_frame', 1);
    }

    echo '<div class="tipax-admin-bg" aria-hidden="true">';
    echo '<img src="' . esc_url(TSM_ASSETS_URL . 'admin/images/tipax-courier-service.svg') . '" alt="" />';
    echo '</div>';

    echo '<div class="wrap tipax-admin-wrap" dir="rtl">';
    echo '<div class="tipax-admin-brand">';
    echo '<div class="tipax-admin-logo" aria-hidden="true">';
    echo '<svg viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">';
    echo '<path d="M14 3L4 8v6c0 5.25 4.27 10.16 10 11.33C19.73 24.16 24 19.25 24 14V8L14 3z" fill="rgba(255,255,255,0.15)" stroke="rgba(255,255,255,0.6)" stroke-width="1.5"/>';
    echo '<path d="M10 14l3 3 5-5" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>';
    echo '</svg>';
    echo '</div>';
    echo '<div>';
    echo '<h1>' . esc_html__('Tipax Shipping', 'delivery-service-tipax-shipping') . '</h1>';
    echo '<p>' . esc_html__('Advanced WooCommerce shipping management for Tipax', 'delivery-service-tipax-shipping') . '</p>';
    echo '</div>';
    echo '<div class="tipax-admin-status"><span class="tipax-admin-status-dot"></span><span>' . esc_html($tabs[$current]['label']) . '</span></div>';
    echo '</div>';

    Tipax_Hub_Assets::render('notice_banner');
    echo '<nav class="tipax-admin-nav" aria-label="' . esc_attr__('Tipax admin tabs', 'delivery-service-tipax-shipping') . '">';
    foreach ($tabs as $slug => $tab) {
        $class = ($slug === $current) ? ' is-active' : '';
        echo '<a class="' . esc_attr(trim($class)) . '" href="' . esc_url(admin_url('admin.php?page=' . $slug)) . '"' . ($slug === $current ? ' aria-current="page"' : '') . '>';
        echo '<span class="tipax-admin-nav-icon" aria-hidden="true">' . tipax_admin_icon($tab['icon']) . '</span>';
        echo '<span>' . esc_html($tab['label']) . '</span>';
        echo '</a>';
    }
    echo '</nav>';
    echo '<section class="tipax-admin-card">';
    $hub_slots = ['tipax-settings' => 'settings_banner', 'tipax-support' => 'support_card',
        'tipax-tutorial' => 'tutorial_cover', 'tipax-api-config' => 'dashboard_hero'];
    if (isset($hub_slots[$current])) {
        Tipax_Hub_Assets::render($hub_slots[$current]);
    }
}

function tipax_tutorial_page()
{
    $documentation_url = 'https://document.tipaxapi.org/#plugin-guide';
    $video_url = 'https://document.tipaxapi.org/video/plugin-v3-video.mp4';

    tipax_admin_tabs('tipax-tutorial');

    echo '<div class="tipax-tutorial">';
    echo '<div class="tipax-tutorial-hero">';
    echo '<div class="tipax-tutorial-heading">';
    echo '<span class="tipax-tutorial-kicker">راهنمای افزونه ای‌تیپاکس</span>';
    echo '<h2>آموزش نصب و راه‌اندازی افزونه</h2>';
    echo '<p>در این ویدیو مراحل نصب، اتصال افزونه، تنظیم آدرس مبدا و آماده‌سازی روش ارسال تیپاکس در ووکامرس توضیح داده شده است.</p>';
    echo '</div>';
    echo '<a class="tipax-tutorial-docs" href="' . esc_url($documentation_url) . '" target="_blank" rel="noopener noreferrer">';
    echo tipax_admin_icon('book') . '<span>مشاهده مستندات کامل</span><span class="tipax-tutorial-docs-arrow" aria-hidden="true">' . tipax_admin_icon('external') . '</span>';
    echo '</a>';
    echo '</div>';

    echo '<div class="tipax-tutorial-video-shell">';
    echo '<video class="tipax-tutorial-video" controls preload="metadata" playsinline>';
    echo '<source src="' . esc_url($video_url) . '" type="video/mp4">';
    echo 'مرورگر شما امکان پخش این ویدیو را ندارد. <a href="' . esc_url($video_url) . '" target="_blank" rel="noopener noreferrer">مشاهده مستقیم ویدیو</a>';
    echo '</video>';
    echo '</div>';

    echo '<div class="tipax-tutorial-footer">';
    echo '<div>' . tipax_admin_icon('controls-play') . '<strong>آموزش ویدیویی نسخه ۳ افزونه</strong></div>';
    echo '<a href="' . esc_url($video_url) . '" target="_blank" rel="noopener noreferrer">باز کردن ویدیو در صفحه جدید</a>';
    echo '</div>';
    echo '</div>';
}


function tipax_is_connection_validated() {
    $username = get_option('woocommerce_tipax_shipping_method_username');
    $password = get_option('woocommerce_tipax_shipping_method_password');
    $apiKey = get_option('woocommerce_tipax_shipping_method_apiKey');
    $test_success = get_option('tipax_test_success', false);
    return $test_success === 'yes';
}

function tipax_get_packing_prices($force_refresh = false) {
    $customer_module = new Tipax_Customer_Service();
    return $customer_module->get_packing_prices($force_refresh);
}

function tipax_wallet() {
    tipax_admin_tabs('tipax-wallet');
    if (!tipax_is_connection_validated()) {
        echo '<div class="notice notice-error"><p>برای دسترسی به این بخش ابتدا باید <a href="' . admin_url('admin.php?page=tipax-api-config') . '">تنظیمات اتصال</a> را کامل کرده و اتصال را <a href="' . admin_url('admin.php?page=tipax-test-connection') . '">آزمایش</a> کنید.</p></div>';
        return;
    }
    if (get_option('tipax_has_address') !== 'yes') {
        echo '<div class="notice notice-warning"><p>برای استفاده از این بخش باید حداقل یک آدرس ثبت شده در حساب تیپاکس شما باشد. لطفاً به <a href="' . admin_url('admin.php?page=tipax-addresses') . '">بخش آدرس‌ها</a> مراجعه کنید.</p></div>';
        return;
    }
    $remaining_assets = get_wallet_remaining_asset();
    echo '<div class="tipax-action-panel tipax-wallet-panel">';
    echo '<div class="tipax-action-icon">' . tipax_admin_icon('money-alt') . '</div>';
    echo '<div class="tipax-action-content">';
    echo '<h2>کیف پول تیپاکس</h2>';
    echo '<p>موجودی کیف پول و شارژ حساب تیپاکس برای پرداخت‌های مبتنی بر کیف پول.</p>';
    echo '</div>';
    echo '<div class="tipax-stats"><span><strong>' . esc_html($remaining_assets) . '</strong> ریال</span></div>';
    echo '</div>';

    echo '<form method="post" action="" class="tipax-inline-form">';
    echo '<label for="recharge_amount">' . tipax_admin_icon('money-alt') . ' مقدار شارژ به ریال</label>';
    echo '<input type="number" id="recharge_amount" name="recharge_amount" required placeholder="مثلاً 1000000">';
    echo '<input type="submit" class="button button-primary" name="submit_recharge" value="شارژ کیف پول">';
    echo '</form>';

    if (isset($_POST['submit_recharge'])) {
        recharge_wallet($_POST['recharge_amount']);
    }
}

function get_wallet_remaining_asset() {
    $customer_module = new Tipax_Customer_Service();
    $response = $customer_module->get_wallet_remaining_asset();
    if(is_numeric($response)){
        return number_format($response);
    } else {
        return 'ERROR';
    }
}

function recharge_wallet($amount) {
    if(is_numeric($amount) && $amount > 1000){
        $customer_module = new Tipax_Customer_Service();
        $response = $customer_module->recharge_wallet($amount);
        header("Location: ".$response->paymentURL);
    } else {
        echo 'مقدار شارژ معتبر نیست.';
    }
}

/**
 * Ensure WP Media Uploader is available on Tipax Settings page
 */
add_action('admin_enqueue_scripts', function ($hook) {
    $tipax_admin_style_version = TSM_VERSION;
    $tipax_admin_style_path = TSM_DIR . 'assets/admin/css/tipax-admin.css';
    $tipax_admin_script_version = TSM_VERSION;
    $tipax_admin_script_path = TSM_DIR . 'assets/admin/js/tipax-admin.js';

    if (file_exists($tipax_admin_style_path)) {
        $tipax_admin_style_version .= '.' . filemtime($tipax_admin_style_path);
    }
    if (file_exists($tipax_admin_script_path)) {
        $tipax_admin_script_version .= '.' . filemtime($tipax_admin_script_path);
    }

    if (tipax_is_admin_page() || tipax_is_wc_shipping_settings_page()) {
        wp_enqueue_style(
            'tipax-admin',
            TSM_ASSETS_URL . 'admin/css/tipax-admin.css',
            ['dashicons'],
            $tipax_admin_style_version
        );
    }

    if (tipax_is_admin_page()) {
        wp_enqueue_script(
            'tipax-admin',
            TSM_ASSETS_URL . 'admin/js/tipax-admin.js',
            [],
            $tipax_admin_script_version,
            true
        );
    }

    if (isset($_GET['page']) && $_GET['page'] === 'tipax-settings') {
        wp_enqueue_media(); // ✅ This loads wp.media
    }
});


function tipax_settings_page() {
    tipax_admin_tabs('tipax-settings');

    if (!tipax_is_connection_validated()) {
        echo '<div class="notice notice-error"><p>برای دسترسی به این بخش ابتدا باید <a href="' . admin_url('admin.php?page=tipax-api-config') . '">تنظیمات اتصال</a> را کامل کرده و اتصال را <a href="' . admin_url('admin.php?page=tipax-test-connection') . '">آزمایش</a> کنید.</p></div>';
        return;
    }

    if (get_option('tipax_has_address') !== 'yes') {
        echo '<div class="notice notice-warning"><p>برای استفاده از این بخش باید حداقل یک آدرس ثبت شده در حساب تیپاکس شما باشد. لطفاً به <a href="' . admin_url('admin.php?page=tipax-addresses') . '">بخش آدرس‌ها</a> مراجعه کنید.</p></div>';
        return;
    }

    $option_key = 'woocommerce_tipax_shipping_method_settings';
    $packing_catalog = tipax_get_admin_packing_catalog();
    $packing_index = isset($packing_catalog['index']) && is_array($packing_catalog['index'])
        ? $packing_catalog['index']
        : [];
    $packing_options = isset($packing_catalog['options']) && is_array($packing_catalog['options'])
        ? $packing_catalog['options']
        : [];
    $has_packing_list = !empty($packing_index);

    // 1) Handle Save
    if (isset($_POST['tipax_save_settings'])) {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to manage these settings.', 'delivery-service-tipax-shipping'), '', ['response' => 403]);
        }
        check_admin_referer('tipax_save_settings');
        Tipax_Hub_Client::instance()->save_preferences($_POST);
        $settings = get_option($option_key, []);

        $settings['cities'] = isset($_POST['woocommerce_tipax_shipping_method_cities']) ? 'yes' : 'no';
        $settings['show_product_table'] = isset($_POST['tipax_show_product_table']) ? 'yes' : 'no';
        $settings['logging_enabled'] = isset($_POST['tipax_logging_enabled']) ? 'yes' : 'no';
        $settings['logo_url'] = esc_url_raw($_POST['tipax_logo_url'] ?? '');
        $settings['label_layout'] = tipax_normalize_label_layout(
            isset($_POST['tipax_label_layout']) ? wp_unslash($_POST['tipax_label_layout']) : 'full'
        );
        $settings['phone'] = sanitize_text_field($_POST['woocommerce_tipax_shipping_method_phone'] ?? '');
        $settings['name'] = sanitize_text_field($_POST['woocommerce_tipax_shipping_method_name'] ?? '');
        $posted_settlement_payer = sanitize_key($_POST['woocommerce_tipax_shipping_method_settlement_payer'] ?? 'receiver');
        $settings['settlement_payer'] = $posted_settlement_payer === 'sender' ? 'sender' : 'receiver';

        $posted_sender_method = sanitize_key($_POST['woocommerce_tipax_shipping_method_sender_payment_method'] ?? 'wallet');
        if ($posted_sender_method === 'cash_postpay') {
            $posted_sender_method = 'cash';
        }
        $settings['sender_payment_method'] = in_array($posted_sender_method, ['credit', 'wallet', 'cash'], true)
            ? $posted_sender_method
            : 'wallet';

        $posted_receiver_charge_mode = sanitize_key($_POST['woocommerce_tipax_shipping_method_receiver_charge_mode'] ?? 'none');
        $settings['receiver_charge_mode'] = in_array($posted_receiver_charge_mode, ['none', 'tipax', 'fixed'], true)
            ? $posted_receiver_charge_mode
            : 'none';
        $settings['show_postpay_amount'] = isset($_POST['woocommerce_tipax_shipping_method_show_postpay_amount']) ? 'yes' : 'no';
        $settings['cod_enabled'] = isset($_POST['woocommerce_tipax_shipping_method_cod_enabled']) ? 'yes' : 'no';
        $settings['same_day_enabled'] = isset($_POST['woocommerce_tipax_shipping_method_same_day_enabled']) ? 'yes' : 'no';
        unset($settings['sender_contribution_amount']);
        $settings['collection_time'] = '1';
        $posted_pickup_type = sanitize_text_field($_POST['woocommerce_tipax_shipping_method_pickup_type'] ?? '10');
        $settings['pickup_type'] = tipax_normalize_pickup_type($posted_pickup_type);
        $settings['offline_fallback_enabled'] = isset($_POST['woocommerce_tipax_shipping_method_offline_fallback_enabled']) ? 'yes' : 'no';
        $posted_timeout = isset($_POST['woocommerce_tipax_shipping_method_offline_fallback_timeout'])
            ? absint($_POST['woocommerce_tipax_shipping_method_offline_fallback_timeout'])
            : tipax_get_tipax_request_timeout($settings);
        $settings['offline_fallback_timeout'] = max(1, min(120, $posted_timeout));
        $settings['inner_flat_price'] = tipax_normalize_settlement_amount($_POST['woocommerce_tipax_shipping_method_inner_flat_price'] ?? 0);
        $settings['outer_flat_price'] = tipax_normalize_settlement_amount($_POST['woocommerce_tipax_shipping_method_outer_flat_price'] ?? 0);
        $settings['inner_min_cart_free_shipping'] = tipax_normalize_settlement_amount($_POST['woocommerce_tipax_shipping_method_inner_min_cart_free_shipping'] ?? 0);
        $settings['outer_min_cart_free_shipping'] = tipax_normalize_settlement_amount($_POST['woocommerce_tipax_shipping_method_outer_min_cart_free_shipping'] ?? 0);

        // Retained for compatibility with shipping instances and older integrations.
        $settings['checkout_method'] = (string) tipax_get_settlement_config($settings)['payment_type'];

        // New: packing selection (from Tipax API)
        if ($has_packing_list && array_key_exists('woocommerce_tipax_shipping_method_packing_id', $_POST)) {
            $posted_packing_id = sanitize_text_field($_POST['woocommerce_tipax_shipping_method_packing_id']);
            if (tipax_is_no_packing_id($posted_packing_id)) {
                $posted_packing_id = '';
            }
            if ($posted_packing_id !== '' && isset($packing_index[$posted_packing_id])) {
                $packing = $packing_index[$posted_packing_id];

                $packType = isset($packing['packType']) && is_numeric($packing['packType']) ? (int) $packing['packType'] : null;
                $length = isset($packing['length']) && is_numeric($packing['length']) ? (float) $packing['length'] : null;
                $width = isset($packing['width']) && is_numeric($packing['width']) ? (float) $packing['width'] : null;
                $height = isset($packing['height']) && is_numeric($packing['height']) ? (float) $packing['height'] : null;
                $maxLength = isset($packing['maxLength']) && is_numeric($packing['maxLength']) ? (float) $packing['maxLength'] : null;
                $maxWidth  = isset($packing['maxWidth']) && is_numeric($packing['maxWidth']) ? (float) $packing['maxWidth'] : null;
                $maxHeight = isset($packing['maxHeight']) && is_numeric($packing['maxHeight']) ? (float) $packing['maxHeight'] : null;
                $minLength = isset($packing['minLength']) && is_numeric($packing['minLength']) ? (float) $packing['minLength'] : 0.0;
                $minWidth  = isset($packing['minWidth']) && is_numeric($packing['minWidth']) ? (float) $packing['minWidth'] : 0.0;
                $minHeight = isset($packing['minHeight']) && is_numeric($packing['minHeight']) ? (float) $packing['minHeight'] : 0.0;
                $minWeight = isset($packing['minWeight']) && is_numeric($packing['minWeight']) ? (float) $packing['minWeight'] : null;
                $maxWeight = isset($packing['maxWeight']) && is_numeric($packing['maxWeight']) ? (float) $packing['maxWeight'] : null;

                if (
                    $packType !== null && $packType > 0 &&
                    $length !== null && $length > 0 &&
                    $width !== null && $width > 0 &&
                    $height !== null && $height > 0
                ) {
                    $settings['packing_id'] = (int) $packing['id'];
                    $settings['packing_title'] = sanitize_text_field($packing['title'] ?? '');
                    $settings['packing_packtype'] = $packType;
                    $settings['packing_package_content_id'] = isset($packing['packageContentId'])
                        ? (int) $packing['packageContentId']
                        : tipax_get_package_content_id_for_pack_type($packType);
                    $settings['packing_length'] = $length;
                    $settings['packing_width'] = $width;
                    $settings['packing_height'] = $height;
                    $settings['packing_max_length'] = $maxLength;
                    $settings['packing_max_width']  = $maxWidth;
                    $settings['packing_max_height'] = $maxHeight;
                    $settings['packing_min_length'] = $minLength;
                    $settings['packing_min_width'] = $minWidth;
                    $settings['packing_min_height'] = $minHeight;
                    $settings['packing_min_weight'] = $minWeight;
                    $settings['packing_max_weight'] = $maxWeight;
                } else {
                    $settings['packing_id'] = '';
                    $settings['packing_title'] = '';
                    $settings['packing_packtype'] = '';
                    $settings['packing_package_content_id'] = '';
                    $settings['packing_length'] = '';
                    $settings['packing_width'] = '';
                    $settings['packing_height'] = '';
                    $settings['packing_max_length'] = '';
                    $settings['packing_max_width']  = '';
                    $settings['packing_max_height'] = '';
                    $settings['packing_min_length'] = '';
                    $settings['packing_min_width'] = '';
                    $settings['packing_min_height'] = '';
                    $settings['packing_min_weight'] = '';
                    $settings['packing_max_weight'] = '';
                }
            } else {
                $settings['packing_id'] = '';
                $settings['packing_title'] = '';
                $settings['packing_packtype'] = '';
                $settings['packing_package_content_id'] = '';
                $settings['packing_length'] = '';
                $settings['packing_width'] = '';
                $settings['packing_height'] = '';
                $settings['packing_max_length'] = '';
                $settings['packing_max_width']  = '';
                $settings['packing_max_height'] = '';
                $settings['packing_min_length'] = '';
                $settings['packing_min_width'] = '';
                $settings['packing_min_height'] = '';
                $settings['packing_min_weight'] = '';
                $settings['packing_max_weight'] = '';
            }
        }

        $settings['avg_weight'] = isset($_POST['woocommerce_tipax_shipping_method_avg_weight']) && is_numeric($_POST['woocommerce_tipax_shipping_method_avg_weight'])
            ? (float) $_POST['woocommerce_tipax_shipping_method_avg_weight'] : '';
        $settings['avg_length'] = isset($_POST['woocommerce_tipax_shipping_method_avg_length']) && is_numeric($_POST['woocommerce_tipax_shipping_method_avg_length'])
            ? (float) $_POST['woocommerce_tipax_shipping_method_avg_length'] : '';
        $settings['avg_width']  = isset($_POST['woocommerce_tipax_shipping_method_avg_width']) && is_numeric($_POST['woocommerce_tipax_shipping_method_avg_width'])
            ? (float) $_POST['woocommerce_tipax_shipping_method_avg_width'] : '';
        $settings['avg_height'] = isset($_POST['woocommerce_tipax_shipping_method_avg_height']) && is_numeric($_POST['woocommerce_tipax_shipping_method_avg_height'])
            ? (float) $_POST['woocommerce_tipax_shipping_method_avg_height'] : '';

        update_option($option_key, $settings);
        if (class_exists('WC_Cache_Helper')) {
            WC_Cache_Helper::get_transient_version('shipping', true);
        }
        if (function_exists('WC') && WC() && WC()->session) {
            $package_count = WC()->cart ? count(WC()->cart->get_shipping_packages()) : 1;
            for ($package_index = 0; $package_index < max(1, $package_count); $package_index++) {
                WC()->session->__unset('shipping_for_package_' . $package_index);
            }
        }
        echo '<div class="notice notice-success"><p>تنظیمات ذخیره شد.</p></div>';
    }

    // 2) Get saved values
    $settings = get_option($option_key, []);
    $cities_enabled = tipax_should_manage_checkout_cities($settings);
    $phone = esc_attr($settings['phone'] ?? '');
    $name = esc_attr($settings['name'] ?? '');
    $settlement_config = tipax_get_settlement_config($settings);
    $settlement_payer = $settlement_config['payer'];
    $sender_payment_method = $settlement_config['sender_method'];
    $receiver_charge_mode = $settlement_config['receiver_charge_mode'];
    $show_postpay_amount = $settlement_config['show_postpay_amount'];
    $cod_enabled = $settlement_config['cod_enabled'];
    $same_day_enabled = tipax_is_same_day_enabled($settings);
    $pickup_type = $settings['pickup_type'] ?? '10';
    $offline_fallback_enabled = ($settings['offline_fallback_enabled'] ?? 'no') === 'yes';
    $offline_fallback_timeout = tipax_get_tipax_request_timeout($settings);
    $inner_flat_price = esc_attr($settings['inner_flat_price'] ?? '');
    $outer_flat_price = esc_attr($settings['outer_flat_price'] ?? '');
    $inner_min_cart_free = esc_attr($settings['inner_min_cart_free_shipping'] ?? '');
    $outer_min_cart_free = esc_attr($settings['outer_min_cart_free_shipping'] ?? '');
    $show_product_table = ($settings['show_product_table'] ?? 'yes') === 'yes';
    $logging_enabled = tipax_is_logging_enabled($settings);
    $label_layout = tipax_normalize_label_layout($settings['label_layout'] ?? 'full');
    $label_layout_options = tipax_get_label_layout_options();

    // New: load packing + averages
    $packing_id    = esc_attr($settings['packing_id'] ?? '');
    $packing_title = esc_attr($settings['packing_title'] ?? '');
    $avg_weight = esc_attr($settings['avg_weight'] ?? '');
    $avg_length = esc_attr($settings['avg_length'] ?? '');
    $avg_width  = esc_attr($settings['avg_width']  ?? '');
    $avg_height = esc_attr($settings['avg_height'] ?? '');

    echo '<h2>تنظیمات</h2>';
    echo '<form method="post" action="">';
    wp_nonce_field('tipax_save_settings');
    echo '<table class="form-table">';
    Tipax_Hub_Client::instance()->fields();

    // 3) Render the city-list ownership setting.
    echo '<tr>';
    echo '<th scope="row"><label for="woocommerce_tipax_shipping_method_cities">شهرهای تیپاکس</label></th>';
    echo '<td>';
    echo '<label>';
    echo '<input type="checkbox" name="woocommerce_tipax_shipping_method_cities" id="woocommerce_tipax_shipping_method_cities" value="1" '
        . checked($cities_enabled, true, false)
        . ' > فقط شهرهای پشتیبانی‌شده تیپاکس';
    echo '</label>';
    echo '<p class="description">روشن: فهرست شهرهای تیپاکس در همه نسخه‌های Checkout نمایش داده می‌شود. خاموش: فیلد شهر قالب یا افزونه حمل‌ونقل دیگر حفظ می‌شود و تیپاکس شهر را هنگام استعلام به شناسه خودش تبدیل می‌کند.</p>';
    echo '</td>';
    echo '</tr>';
// --- New: Logo upload ---
$logo_url = esc_url($settings['logo_url'] ?? '');
echo '<tr>';
echo '<th scope="row"><label for="tipax_logo_url">لوگوی چاپ تیپاکس</label></th>';
echo '<td>';
echo '<input type="hidden" name="tipax_logo_url" id="tipax_logo_url" value="' . $logo_url . '" />';
echo '<img id="tipax_logo_preview" src="' . ($logo_url ?: 'https://via.placeholder.com/150x50?text=No+Logo') . '" style="max-width:150px;display:block;margin-bottom:6px;border:1px solid #ccc;padding:2px;" />';
echo '<button type="button" class="button" id="tipax_logo_upload_btn">انتخاب لوگو</button> ';
echo '<button type="button" class="button" id="tipax_logo_remove_btn">حذف</button>';
echo '<p class="description">این لوگو در چاپ فاکتور و برچسب تیپاکس نمایش داده می‌شود.</p>';
echo '</td>';
echo '</tr>';

echo '<tr>';
echo '<th scope="row"><label for="tipax_label_layout">نحوه نمایش لیبل</label></th>';
echo '<td>';
echo '<select name="tipax_label_layout" id="tipax_label_layout">';
foreach ($label_layout_options as $layout_value => $layout_label) {
    echo '<option value="' . esc_attr($layout_value) . '" ' . selected($label_layout, $layout_value, false) . '>' . esc_html($layout_label) . '</option>';
}
echo '</select>';
echo '<p class="description">طرح انتخاب‌شده در چاپ تکی، چندتایی و لیبل سفارشات ووکامرس استفاده می‌شود.</p>';
echo '</td>';
echo '</tr>';

    // phone
    echo '<tr>';
    echo '<th scope="row"><label for="woocommerce_tipax_shipping_method_phone">تلفن فرستنده</label></th>';
    echo '<td><input type="text" name="woocommerce_tipax_shipping_method_phone" id="woocommerce_tipax_shipping_method_phone" class="regular-text" value="' . $phone . '" /></td>';
    echo '</tr>';

    // name
    echo '<tr>';
    echo '<th scope="row"><label for="woocommerce_tipax_shipping_method_name">نام کامل فرستنده</label></th>';
    echo '<td><input type="text" name="woocommerce_tipax_shipping_method_name" id="woocommerce_tipax_shipping_method_name" class="regular-text" value="' . $name . '" /></td>';
    echo '</tr>';

    // Settlement flow
    echo '<tr>';
    echo '<th scope="row"><label for="woocommerce_tipax_shipping_method_settlement_payer">مدل پرداخت کرایه</label></th>';
    echo '<td><select name="woocommerce_tipax_shipping_method_settlement_payer" id="woocommerce_tipax_shipping_method_settlement_payer">';
    echo '<option value="receiver" ' . selected($settlement_payer, 'receiver', false) . '>پس‌کرایه (پرداخت توسط گیرنده)</option>';
    echo '<option value="sender" ' . selected($settlement_payer, 'sender', false) . '>پرداخت از سمت فرستنده</option>';
    echo '</select><p class="description">مشخص کنید کرایه تیپاکس از گیرنده دریافت شود یا تسویه آن از حساب فرستنده انجام شود.</p></td>';
    echo '</tr>';

    echo '<tr class="tipax-settlement-postpay" style="display: none;">';
    echo '<th scope="row"><label for="woocommerce_tipax_shipping_method_show_postpay_amount">نمایش مبلغ پس‌کرایه</label></th>';
    echo '<td><label><input type="checkbox" name="woocommerce_tipax_shipping_method_show_postpay_amount" id="woocommerce_tipax_shipping_method_show_postpay_amount" value="1" ' . checked($show_postpay_amount, true, false) . '> نمایش مبلغ دریافتی از تیپاکس در عنوان روش ارسال</label>';
    echo '<p class="description">این مبلغ فقط برای اطلاع مشتری نمایش داده می‌شود و به جمع پرداخت آنلاین فروشگاه اضافه نمی‌شود.</p></td>';
    echo '</tr>';

    echo '<tr class="tipax-settlement-sender-method" style="display: none;">';
    echo '<th scope="row"><label for="woocommerce_tipax_shipping_method_sender_payment_method">روش پرداخت فرستنده</label></th>';
    echo '<td><select name="woocommerce_tipax_shipping_method_sender_payment_method" id="woocommerce_tipax_shipping_method_sender_payment_method">';
    echo '<option value="credit" ' . selected($sender_payment_method, 'credit', false) . '>اعتباری</option>';
    echo '<option value="wallet" ' . selected($sender_payment_method, 'wallet', false) . '>کیف پول</option>';
    echo '<option value="cash" ' . selected($sender_payment_method, 'cash', false) . '>نقدی</option>';
    echo '</select></td>';
    echo '</tr>';

    echo '<tr class="tipax-settlement-receiver-charge" style="display: none;">';
    echo '<th scope="row"><label for="woocommerce_tipax_shipping_method_receiver_charge_mode">مبلغ دریافتی از گیرنده</label></th>';
    echo '<td><select name="woocommerce_tipax_shipping_method_receiver_charge_mode" id="woocommerce_tipax_shipping_method_receiver_charge_mode">';
    echo '<option value="none" ' . selected($receiver_charge_mode, 'none', false) . '>رایگان / بدون دریافت مبلغ</option>';
    echo '<option value="fixed" ' . selected($receiver_charge_mode, 'fixed', false) . '>مبلغ ثابت بر اساس مسیر</option>';
    echo '<option value="tipax" ' . selected($receiver_charge_mode, 'tipax', false) . '>مبلغ محاسبه‌شده توسط تیپاکس</option>';
    echo '</select><p class="description">در همه روش‌های پرداخت فرستنده تعیین کنید چه مبلغی در تسویه‌حساب ووکامرس از مشتری دریافت شود. کل کرایه اعلام‌شده تیپاکس مستقل از این مبلغ، توسط فرستنده به تیپاکس پرداخت می‌شود.</p></td>';
    echo '</tr>';

    echo '<tr class="tipax-settlement-route-amount" style="display: none;">';
    echo '<th scope="row"><label for="woocommerce_tipax_shipping_method_inner_flat_price">مبلغ درون‌شهری</label></th>';
    echo '<td><input type="number" min="0" step="any" name="woocommerce_tipax_shipping_method_inner_flat_price" id="woocommerce_tipax_shipping_method_inner_flat_price" class="regular-text" value="' . $inner_flat_price . '" /> <span class="description">بر اساس واحد پول ووکامرس</span></td>';
    echo '</tr>';

    echo '<tr class="tipax-settlement-route-amount" style="display: none;">';
    echo '<th scope="row"><label for="woocommerce_tipax_shipping_method_outer_flat_price">مبلغ بین‌شهری</label></th>';
    echo '<td><input type="number" min="0" step="any" name="woocommerce_tipax_shipping_method_outer_flat_price" id="woocommerce_tipax_shipping_method_outer_flat_price" class="regular-text" value="' . $outer_flat_price . '" /> <span class="description">بر اساس واحد پول ووکامرس</span></td>';
    echo '</tr>';

    echo '<tr class="tipax-settlement-route-amount" style="display: none;">';
    echo '<th scope="row"><label for="woocommerce_tipax_shipping_method_inner_min_cart_free_shipping">حداقل سبد برای رایگان شدن درون‌شهری</label></th>';
    echo '<td><input type="number" min="0" step="any" name="woocommerce_tipax_shipping_method_inner_min_cart_free_shipping" id="woocommerce_tipax_shipping_method_inner_min_cart_free_shipping" class="regular-text" value="' . $inner_min_cart_free . '" /><p class="description">صفر یعنی شرط ارسال رایگان اعمال نشود.</p></td>';
    echo '</tr>';

    echo '<tr class="tipax-settlement-route-amount" style="display: none;">';
    echo '<th scope="row"><label for="woocommerce_tipax_shipping_method_outer_min_cart_free_shipping">حداقل سبد برای رایگان شدن بین‌شهری</label></th>';
    echo '<td><input type="number" min="0" step="any" name="woocommerce_tipax_shipping_method_outer_min_cart_free_shipping" id="woocommerce_tipax_shipping_method_outer_min_cart_free_shipping" class="regular-text" value="' . $outer_min_cart_free . '" /><p class="description">صفر یعنی شرط ارسال رایگان اعمال نشود.</p></td>';
    echo '</tr>';

    echo '<tr>';
    echo '<th scope="row"><label for="woocommerce_tipax_shipping_method_cod_enabled">پرداخت در محل تیپاکس (COD)</label></th>';
    echo '<td><label><input type="checkbox" name="woocommerce_tipax_shipping_method_cod_enabled" id="woocommerce_tipax_shipping_method_cod_enabled" value="1" ' . checked($cod_enabled, true, false) . '> فعال‌سازی درگاه پرداخت نقدی تیپاکس</label>';
    echo '<p class="description">در صورت انتخاب این درگاه توسط مشتری، مبلغ سفارش برای دریافت از گیرنده در درخواست ثبت مرسوله ارسال می‌شود.</p></td>';
    echo '</tr>';

    echo '<tr>';
    echo '<th scope="row"><label for="woocommerce_tipax_shipping_method_same_day_enabled">سرویس همان روز (Same Day)</label></th>';
    echo '<td>';
    echo '<label><input type="checkbox" name="woocommerce_tipax_shipping_method_same_day_enabled" id="woocommerce_tipax_shipping_method_same_day_enabled" value="1" ' . checked($same_day_enabled, true, false) . '> فعال‌سازی سرویس همان روز</label>';
    echo '<p class="description">برای سفارش‌های تهران به تهران که پیش از ساعت ۱۳:۰۰ به وقت ایران ثبت شوند و شرایط وزن، ابعاد و بسته‌بندی را داشته باشند، قیمت همان روز در کنار اکسپرس درون‌شهری نمایش داده می‌شود. پس از انتخاب این سرویس، تعیین موقعیت دقیق گیرنده روی نقشه نشان الزامی است.</p>';
    echo '<p class="description">حداکثر وزن ۱۲ کیلوگرم، حداکثر ابعاد ۳۷ × ۳۷ × ۱۵ سانتی‌متر و نوع بسته‌بندی فقط پاکت یا بسته است.</p>';
    if (function_exists('tipax_get_neshan_static_map_api_key') && tipax_get_neshan_static_map_api_key() !== '') {
        echo '<p class="description" style="color:#0a7a4f"><strong>پیکربندی امن نقشه نشان روی سرور فعال است.</strong></p>';
    } else {
        echo '<p class="description" style="color:#b45309"><strong>کلید نقشه استاتیک نشان روی سرور تنظیم نشده است؛ تا زمان تنظیم، نرخ همان روز نمایش داده نمی‌شود.</strong></p>';
    }
    echo '</td>';
    echo '</tr>';
    // --- New: بسته‌بندی (select from Tipax API) ---
    echo '<tr>';
    echo '<th scope="row"><label for="woocommerce_tipax_shipping_method_packing_id">بسته‌بندی</label></th>';
    echo '<td><select name="woocommerce_tipax_shipping_method_packing_id" id="woocommerce_tipax_shipping_method_packing_id"' . ($has_packing_list ? '' : ' disabled="disabled"') . '>';
    echo '<option value="">' . esc_html__('پیش‌فرض (بدون بسته بندی)', 'delivery-service-tipax-shipping') . '</option>';
    if ($has_packing_list) {
        foreach ($packing_options as $option) {
            if (!is_array($option) || !array_key_exists('value', $option)) {
                continue;
            }

            $value = (string) $option['value'];
            if ($value === '') {
                continue;
            }

            $label = isset($option['label']) ? (string) $option['label'] : $value;
            echo '<option value="' . esc_attr($value) . '" ' . selected($packing_id, $value, false) . '>' . esc_html($label) . '</option>';
        }
    } elseif (!empty($packing_id) && !empty($packing_title)) {
        echo '<option value="' . esc_attr($packing_id) . '" selected="selected">' . esc_html($packing_title) . '</option>';
    }
    echo '</select>';
    if ($has_packing_list) {
        echo '<p id="tipax_packing_dimension_hint" class="description">در حالت پیش‌فرض، ابعاد زیر به‌عنوان ابعاد واقعی مرسوله استفاده می‌شوند و سگمنت معتبر بدون بسته‌بندی بر اساس وزن، ابعاد و ارزش مرسوله انتخاب می‌شود.</p>';
    } else {
        echo '<p class="description">لیست بسته‌بندی از تیپاکس دریافت نشد. لطفاً اتصال را بررسی کنید یا کمی بعد دوباره تلاش کنید.</p>';
    }
    echo '</td>';
    echo '</tr>';
    // --- New: averages (Persian labels) ---
    // متوسط وزن محصول (کیلوگرم)
    echo '<tr>';
    echo '<th scope="row"><label for="woocommerce_tipax_shipping_method_avg_weight">متوسط وزن محصول (کیلوگرم)</label></th>';
    echo '<td><input type="number" step="0.01" min="0" name="woocommerce_tipax_shipping_method_avg_weight" id="woocommerce_tipax_shipping_method_avg_weight" class="regular-text" value="' . $avg_weight . '" placeholder="مثلاً 0.5" /></td>';
    echo '</tr>';

    // Default package length (centimeters)
    echo '<tr class="tipax-default-dimension-row">';
    echo '<th scope="row"><label for="woocommerce_tipax_shipping_method_avg_length">طول بسته در حالت پیش‌فرض (سانتی‌متر)</label></th>';
    echo '<td><input type="number" step="0.1" min="0" name="woocommerce_tipax_shipping_method_avg_length" id="woocommerce_tipax_shipping_method_avg_length" class="regular-text" value="' . $avg_length . '" placeholder="مثلاً 20" /></td>';
    echo '</tr>';

    // Default package width (centimeters)
    echo '<tr class="tipax-default-dimension-row">';
    echo '<th scope="row"><label for="woocommerce_tipax_shipping_method_avg_width">عرض بسته در حالت پیش‌فرض (سانتی‌متر)</label></th>';
    echo '<td><input type="number" step="0.1" min="0" name="woocommerce_tipax_shipping_method_avg_width" id="woocommerce_tipax_shipping_method_avg_width" class="regular-text" value="' . $avg_width . '" placeholder="مثلاً 15" /></td>';
    echo '</tr>';

    // Default package height (centimeters)
    echo '<tr class="tipax-default-dimension-row">';
    echo '<th scope="row"><label for="woocommerce_tipax_shipping_method_avg_height">ارتفاع بسته در حالت پیش‌فرض (سانتی‌متر)</label></th>';
    echo '<td><input type="number" step="0.1" min="0" name="woocommerce_tipax_shipping_method_avg_height" id="woocommerce_tipax_shipping_method_avg_height" class="regular-text" value="' . $avg_height . '" placeholder="مثلاً 10" /></td>';
    echo '</tr>';

    // pickup type
    $pickup_types = [
        '10' => 'جمع آوری در محل مشتری',
        '20' => 'جمع آوری در نمایندگی',
    ];
    echo '<tr>';
    echo '<th scope="row"><label for="woocommerce_tipax_shipping_method_pickup_type">نوع جمع‌آوری</label></th>';
    echo '<td><select name="woocommerce_tipax_shipping_method_pickup_type" id="woocommerce_tipax_shipping_method_pickup_type">';
    foreach ($pickup_types as $value => $label) {
        echo '<option value="' . esc_attr($value) . '" ' . selected($pickup_type, $value, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select></td>';
    echo '</tr>';
    echo '<tr>';
    echo '<th scope="row"><label for="woocommerce_tipax_shipping_method_offline_fallback_enabled">نحوه ثبت سفارش در Checkout</label></th>';
    echo '<td>';
    echo '<label><input type="checkbox" name="woocommerce_tipax_shipping_method_offline_fallback_enabled" id="woocommerce_tipax_shipping_method_offline_fallback_enabled" value="1" ' . checked($offline_fallback_enabled, true, false) . '> ثبت سفارش‌های تیپاکس در Checkout به‌صورت کاملاً آفلاین</label>';
    echo '<p class="description"><strong>روشن:</strong> در Checkout هیچ درخواستی برای ثبت سفارش به تیپاکس ارسال نمی‌شود. سفارش در حالت آفلاین ذخیره می‌شود و باید از منوی سفارشات افزونه با دکمه «ثبت در تیپاکس» به‌صورت دستی ثبت شود.</p>';
    echo '<p class="description"><strong>خاموش:</strong> سفارش در Checkout به‌صورت آنلاین ثبت می‌شود. اگر تیپاکس تا پایان مهلت تعیین‌شده پاسخ ندهد، سفارش آفلاین ذخیره می‌شود تا بعداً از منوی سفارشات ثبت شود.</p>';
    echo '<p class="description">این گزینه روی ثبت دستی و ثبت مجدد از مدیریت سفارش‌ها اثر ندارد؛ این عملیات همیشه به‌صورت آنلاین انجام می‌شوند.</p>';
    echo '</td>';
    echo '</tr>';
    echo '<tr id="tipax_offline_fallback_timeout_row">';
    echo '<th scope="row"><label for="woocommerce_tipax_shipping_method_offline_fallback_timeout">مهلت ثبت آنلاین در Checkout (ثانیه)</label></th>';
    echo '<td><input type="number" min="1" max="120" step="1" name="woocommerce_tipax_shipping_method_offline_fallback_timeout" id="woocommerce_tipax_shipping_method_offline_fallback_timeout" class="small-text" value="' . esc_attr($offline_fallback_timeout) . '" />';
    echo '<p class="description">فقط در حالت آنلاین استفاده می‌شود. اگر تیپاکس در این مدت پاسخ ندهد، سفارش برای ثبت دستی در حالت آفلاین قرار می‌گیرد.</p></td>';
    echo '</tr>';
// --- New: Show product table ---
echo '<tr>';
echo '<th scope="row"><label for="tipax_show_product_table">نمایش جدول محصولات در چاپ لیبل سفارش</label></th>';
echo '<td>';
echo '<label><input type="checkbox" name="tipax_show_product_table" id="tipax_show_product_table" value="1" ' . checked($show_product_table, true, false) . '> نمایش جزئیات محصولات در فاکتور چاپی</label>';
echo '</td>';
echo '</tr>';

echo '<tr>';
echo '<th scope="row"><label for="tipax_logging_enabled">' . esc_html(tipax_text('logging_label')) . '</label></th>';
echo '<td>';
echo '<label><input type="checkbox" name="tipax_logging_enabled" id="tipax_logging_enabled" value="1" ' . checked($logging_enabled, true, false) . '> ' . esc_html(tipax_text('logging_toggle')) . '</label>';
echo '<p class="description">' . esc_html(tipax_text('logging_description')) . '</p>';
echo '</td>';
echo '</tr>';
    echo '</table>';
    submit_button('ذخیره تنظیمات', 'primary', 'tipax_save_settings');
    echo '</form>';

    echo '<section class="tipax-shortcode-guide" aria-labelledby="tipax-shortcode-guide-title">';
    echo '<div class="tipax-shortcode-guide-intro">';
    echo '<span class="tipax-shortcode-guide-icon" aria-hidden="true">[ / ]</span>';
    echo '<div>';
    echo '<h3 id="tipax-shortcode-guide-title">ساخت صفحه رهگیری مرسولات</h3>';
    echo '<p>با شورت‌کد زیر یک فرم رهگیری عمومی در سایت نمایش دهید تا مشتریان با وارد کردن کد رهگیری یا کد قرارداد، آخرین وضعیت مرسوله تیپاکس را مشاهده کنند.</p>';
    echo '</div>';
    echo '</div>';
    echo '<div class="tipax-shortcode-guide-content">';
    echo '<div class="tipax-shortcode-code" dir="ltr"><code>[tipax_tracking]</code></div>';
    echo '<ol>';
    echo '<li>یک برگه جدید برای رهگیری مرسولات بسازید.</li>';
    echo '<li>در ویرایشگر وردپرس از بلوک «شورت‌کد» و در المنتور از ابزارک «Shortcode» استفاده کنید.</li>';
    echo '<li>کد بالا را وارد کنید و برگه را منتشر کنید.</li>';
    echo '<li>کد کاملاً عددی به‌عنوان قرارداد و کد دارای حروف به‌عنوان کد رهگیری شناسایی می‌شود.</li>';
    echo '</ol>';
    echo '<p class="tipax-shortcode-guide-note">شورت‌کد را داخل ابزارک «ویرایشگر متن» المنتور قرار ندهید؛ در آن حالت ممکن است به‌صورت متن ساده نمایش داده شود.</p>';
    echo '</div>';
    echo '</section>';

    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const settlementPayer = document.getElementById('woocommerce_tipax_shipping_method_settlement_payer');
        const senderPaymentMethod = document.getElementById('woocommerce_tipax_shipping_method_sender_payment_method');
        const receiverChargeMode = document.getElementById('woocommerce_tipax_shipping_method_receiver_charge_mode');
        const offlineFallbackCheckbox = document.getElementById('woocommerce_tipax_shipping_method_offline_fallback_enabled');
        const offlineTimeoutRow = document.getElementById('tipax_offline_fallback_timeout_row');
        const packingSelect = document.getElementById('woocommerce_tipax_shipping_method_packing_id');
        const avgWeightInput = document.getElementById('woocommerce_tipax_shipping_method_avg_weight');
        const packingDimensionHint = document.getElementById('tipax_packing_dimension_hint');
        const defaultDimensionRows = document.querySelectorAll('.tipax-default-dimension-row');
        const packingCatalogIndex = <?php echo wp_json_encode($packing_index); ?>;
        const packingCatalogOptions = <?php echo wp_json_encode($packing_options); ?>;
        const defaultPackingDimensionHint = 'در حالت پیش‌فرض، ابعاد زیر به‌عنوان ابعاد واقعی مرسوله استفاده می‌شوند و تیپاکس با توجه به وزن، ابعاد و ارزش مرسوله یکی از سگمنت‌های بدون بسته‌بندی ۵۰۷، ۵۰۴ یا ۵۲۱ را انتخاب می‌کند.';

        function setSettlementRows(selector, visible) {
            document.querySelectorAll(selector).forEach(el => {
                el.style.display = visible ? 'table-row' : 'none';
            });
        }

        function toggleSettlementFields() {
            if (!settlementPayer || !senderPaymentMethod || !receiverChargeMode) {
                return;
            }

            const senderPays = settlementPayer.value === 'sender';
            setSettlementRows('.tipax-settlement-postpay', !senderPays);
            setSettlementRows('.tipax-settlement-sender-method', senderPays);
            setSettlementRows('.tipax-settlement-receiver-charge', senderPays);
            setSettlementRows('.tipax-settlement-route-amount', senderPays && receiverChargeMode.value === 'fixed');
        }

        function toggleOfflineTimeoutRow() {
            if (!offlineFallbackCheckbox || !offlineTimeoutRow) {
                return;
            }

            offlineTimeoutRow.style.display = offlineFallbackCheckbox.checked ? 'none' : 'table-row';
            const timeoutInput = offlineTimeoutRow.querySelector('input');
            if (timeoutInput) {
                timeoutInput.disabled = offlineFallbackCheckbox.checked;
            }
        }

        function parseWeightValue(value) {
            if (value === undefined || value === null || value === '') {
                return 0;
            }

            const normalized = String(value).replace(',', '.').trim();
            const parsed = parseFloat(normalized);
            return Number.isFinite(parsed) ? parsed : 0;
        }

        function packingMatchesWeight(packing, weight) {
            if (!packing || !weight || weight <= 0) {
                return true;
            }

            const minWeight = parseWeightValue(packing.minWeight);
            const maxWeight = parseWeightValue(packing.maxWeight);

            if (minWeight > 0 && weight < minWeight) {
                return false;
            }

            if (maxWeight > 0 && weight > maxWeight) {
                return false;
            }

            return true;
        }

        function syncPackingOptions() {
            if (!packingSelect || !avgWeightInput || packingSelect.disabled) {
                return;
            }

            const currentValue = String(packingSelect.value || '');
            const weight = parseWeightValue(avgWeightInput.value);
            let hasSelectedOption = false;

            packingSelect.innerHTML = '';

            (packingCatalogOptions || []).forEach(function(option) {
                if (!option || option.value === undefined) {
                    return;
                }

                const value = String(option.value);
                const packing = value !== '' ? packingCatalogIndex[value] : null;
                if (value !== '' && !packingMatchesWeight(packing, weight)) {
                    return;
                }

                const optionElement = document.createElement('option');
                optionElement.value = value;
                optionElement.textContent = option.label !== undefined ? option.label : value;
                if (value === currentValue) {
                    optionElement.selected = true;
                    hasSelectedOption = true;
                }
                packingSelect.appendChild(optionElement);
            });

            if (!hasSelectedOption) {
                packingSelect.value = '';
            }

            syncPackingDimensionMode();
        }

        function syncPackingDimensionMode() {
            if (!packingSelect) {
                return;
            }

            const packingId = String(packingSelect.value || '');
            const selectedPacking = packingId !== '' ? packingCatalogIndex[packingId] : null;
            const usesCatalogDimensions = !!selectedPacking;

            defaultDimensionRows.forEach(function(row) {
                row.style.display = usesCatalogDimensions ? 'none' : 'table-row';
            });

            if (!packingDimensionHint) {
                return;
            }

            if (!usesCatalogDimensions) {
                packingDimensionHint.textContent = defaultPackingDimensionHint;
                return;
            }

            const dimensions = [selectedPacking.length, selectedPacking.width, selectedPacking.height]
                .map(function(value) { return parseWeightValue(value); });
            packingDimensionHint.textContent = dimensions.every(function(value) { return value > 0; })
                ? 'ابعاد این بسته از کاتالوگ تیپاکس به‌صورت خودکار و غیرقابل ویرایش اعمال می‌شود: ' + dimensions.join(' × ') + ' سانتی‌متر.'
                : 'ابعاد این بسته به‌صورت خودکار از کاتالوگ تیپاکس اعمال می‌شود و قابل ویرایش دستی نیست.';
        }

        if (settlementPayer) {
            settlementPayer.addEventListener('change', toggleSettlementFields);
        }
        if (senderPaymentMethod) {
            senderPaymentMethod.addEventListener('change', toggleSettlementFields);
        }
        if (receiverChargeMode) {
            receiverChargeMode.addEventListener('change', toggleSettlementFields);
        }
        if (offlineFallbackCheckbox) {
            offlineFallbackCheckbox.addEventListener('change', toggleOfflineTimeoutRow);
        }
        if (avgWeightInput) {
            avgWeightInput.addEventListener('input', syncPackingOptions);
            avgWeightInput.addEventListener('change', syncPackingOptions);
        }
        if (packingSelect) {
            packingSelect.addEventListener('change', syncPackingDimensionMode);
        }
        toggleSettlementFields();
        toggleOfflineTimeoutRow();
        syncPackingOptions();
        syncPackingDimensionMode();
    });
    // --- Tipax logo upload ---
const logoBtn = document.getElementById('tipax_logo_upload_btn');
const removeBtn = document.getElementById('tipax_logo_remove_btn');
const logoInput = document.getElementById('tipax_logo_url');
const preview = document.getElementById('tipax_logo_preview');
let frame;

if (logoBtn) {
    logoBtn.addEventListener('click', function(e) {
        e.preventDefault();
        if (frame) { frame.open(); return; }
        frame = wp.media({
            title: 'انتخاب لوگو',
            button: { text: 'استفاده از این لوگو' },
            multiple: false
        });
        frame.on('select', function() {
            const attachment = frame.state().get('selection').first().toJSON();
            logoInput.value = attachment.url;
            preview.src = attachment.url;
        });
        frame.open();
    });
}

if (removeBtn) {
    removeBtn.addEventListener('click', function(e) {
        e.preventDefault();
        logoInput.value = '';
        preview.src = 'https://via.placeholder.com/150x50?text=No+Logo';
    });
}

    </script>
    <?php
}





function tipax_addresses_page_legacy() {
    tipax_admin_tabs('tipax-addresses');

    if (!tipax_is_connection_validated()) {
        echo '<div class="notice notice-error"><p>برای دسترسی به این بخش ابتدا باید <a href="' . admin_url('admin.php?page=tipax-api-config') . '">تنظیمات اتصال</a> را کامل کرده و اتصال را <a href="' . admin_url('admin.php?page=tipax-test-connection') . '">آزمایش</a> کنید.</p></div>';
        return;
    }

    echo '<div class="tipax-action-panel tipax-address-panel">';
    echo '<div class="tipax-action-icon">' . tipax_admin_icon('location') . '</div>';
    echo '<div class="tipax-action-content">';
    echo '<h2>آدرس‌های ذخیره شده از تیپاکس</h2>';
    echo '<p>آدرس‌های پیش‌فرض حساب تیپاکس را دریافت و برای محاسبه نرخ و ثبت سفارش استفاده کنید.</p>';
    echo '</div>';
    echo '<button id="update_tipax_addresses" class="button button-secondary tipax-action-button">بروزرسانی آدرس‌ها از تیپاکس</button>';
    echo '</div>';
    echo '<div id="tipax_addresses_result" class="tipax-result-area"><div class="tipax-empty-state">' . tipax_admin_icon('location') . '<p>در حال آماده‌سازی لیست آدرس‌ها...</p></div></div>';

    ?>
    <script>
        jQuery(document).ready(function($) {
            function loadAddresses() {
                $('#tipax_addresses_result').html('<div class="tipax-loading-state"><span></span><p>در حال بارگیری آدرس‌ها...</p></div>');
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'tipax_reload_addresses'
                    },
                    success: function(response) {
                        $('#tipax_addresses_result').html(response);
                    },
                    error: function() {
                        $('#tipax_addresses_result').html('<div class="notice notice-error"><p>خطا در بارگیری آدرس‌ها</p></div>');
                    }
                });
            }

            // Load addresses initially
            loadAddresses();

            // Reload when button is clicked
            $('#update_tipax_addresses').on('click', function() {
                loadAddresses();
            });
        });
    </script>
    <?php
}


add_action('wp_ajax_tipax_reload_addresses_legacy', 'tipax_reload_addresses_legacy');

function tipax_reload_addresses_legacy() {
    delete_transient('tipax_addresses');
    try {
        $shipping_method = new WC_Tipax_V2();
    
        // Ensure the object is initialized (in case init() is needed)
        if (method_exists($shipping_method, 'init')) {
            $shipping_method->init();
        }
    
        // Clear transient first
        delete_transient('tipax_addresses');
    
        $addresses_objects = $shipping_method->loadAddresses();
    } catch (Throwable $e) {
        echo '<div class="notice notice-error"><p>خطای داخلی در دریافت آدرس‌ها: ' . esc_html($e->getMessage()) . '</p></div>';
        wp_die();
    }

    if (!$addresses_objects || empty($addresses_objects->address)) {
        echo '<div class="notice notice-warning"><p>هیچ آدرس پیش‌فرضی یافت نشد. لطفاً در پنل ای‌تیپاکس، یک آدرس ایجاد و آن را پیش‌فرض کنید.</p></div>';
        wp_die();
    }

    $addresses = is_array($addresses_objects->address) ? $addresses_objects->address : [$addresses_objects->address];

    echo '<table class="widefat striped tipax-data-table tipax-addresses-table">';
    echo '<thead><tr><th>آدرس کامل</th></tr></thead><tbody>';

    foreach ($addresses as $address) {
        echo '<tr><td>' . esc_html($address->fullAddress) . '</td></tr>';
    }

    echo '</tbody></table>';

    wp_die();
}

function tipax_get_address_service_components()
{
    $request = new Request();
    $settings = get_option('woocommerce_tipax_shipping_method_settings', []);
    if (!is_array($settings)) {
        $settings = [];
    }

    $auth = new Tipax_Auth_Service($request, $settings);
    if (!$auth->get_access_token()) {
        $auth->check_api_key(true);
    }

    return [
        'request' => $request,
        'settings' => $settings,
        'auth' => $auth,
        'service' => new Tipax_Address_Service($request, $settings, $auth),
    ];
}

function tipax_get_tipax_address_list($response)
{
    if (is_array($response)) {
        return $response;
    }

    if (is_object($response)) {
        if (isset($response->result) && is_array($response->result)) {
            return $response->result;
        }

        if (isset($response->data) && is_array($response->data)) {
            return $response->data;
        }
    }

    return [];
}

function tipax_extract_tipax_response_message($response, $default = 'خطای نامشخص از تیپاکس.')
{
    if (is_wp_error($response)) {
        return $response->get_error_message();
    }

    if (is_object($response)) {
        if (!empty($response->body) && is_string($response->body)) {
            return $response->body;
        }

        if (!empty($response->errors) && is_array($response->errors)) {
            $messages = [];
            foreach ($response->errors as $error) {
                if (is_object($error)) {
                    if (!empty($error->errorMessage)) {
                        $messages[] = $error->errorMessage;
                    } elseif (!empty($error->message)) {
                        $messages[] = $error->message;
                    }
                } elseif (is_string($error) && $error !== '') {
                    $messages[] = $error;
                }
            }

            if (!empty($messages)) {
                return implode("\n", $messages);
            }
        }

        if (!empty($response->Message)) {
            return $response->Message;
        }

        if (!empty($response->message)) {
            return $response->message;
        }
    }

    if (is_string($response) && $response !== '') {
        return $response;
    }

    return $default;
}

function tipax_get_address_record_details($record)
{
    if (!is_object($record)) {
        return [];
    }

    if (isset($record->address)) {
        if (is_array($record->address)) {
            return array_values(array_filter($record->address, 'is_object'));
        }

        if (is_object($record->address)) {
            return [$record->address];
        }
    }

    return [$record];
}

function tipax_get_address_record_field($record, $field, $default = '')
{
    if (!is_object($record)) {
        return $default;
    }

    if (isset($record->{$field}) && $record->{$field} !== '') {
        return $record->{$field};
    }

    foreach (tipax_get_address_record_details($record) as $detail) {
        if (isset($detail->{$field}) && $detail->{$field} !== '') {
            return $detail->{$field};
        }
    }

    return $default;
}

function tipax_get_address_detail_field($record, $field, $default = '')
{
    foreach (tipax_get_address_record_details($record) as $detail) {
        if (is_object($detail) && isset($detail->{$field}) && $detail->{$field} !== '') {
            return $detail->{$field};
        }
    }

    return $default;
}

function tipax_get_address_origin_id($record)
{
    if (!is_object($record)) {
        return '';
    }

    if (isset($record->addressesId) && $record->addressesId !== '') {
        return (string) $record->addressesId;
    }

    $detail_origin_id = tipax_get_address_detail_field($record, 'addressesId', '');
    if ($detail_origin_id !== '') {
        return (string) $detail_origin_id;
    }

    $detail_id = tipax_get_address_detail_field($record, 'id', '');
    if ($detail_id !== '') {
        return (string) $detail_id;
    }

    return '';
}

function tipax_get_admin_address_city_rows()
{
    global $wpdb;

    $table = $wpdb->prefix . 'tipax_cities';
    $exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
    if ($exists !== $table) {
        return [];
    }

    $rows = $wpdb->get_results("SELECT cityId, cityTitle, stateTitle FROM {$table} ORDER BY stateTitle ASC, cityTitle ASC");
    return is_array($rows) ? $rows : [];
}

function tipax_collect_address_rows($response)
{
    $rows = [];
    foreach (tipax_get_tipax_address_list($response) as $record) {
        if (!Tipax_Address_Service::is_sender_address_record($record)) {
            continue;
        }

        $details = tipax_get_address_record_details($record);
        if (empty($details)) {
            continue;
        }

        foreach ($details as $detail) {
            $address_parts = [];
            foreach (['mainStreet1', 'sideStreet1', 'mainAlley1', 'sideAlley1', 'otherAddress'] as $field) {
                $value = '';
                if (is_object($detail) && isset($detail->{$field}) && $detail->{$field} !== '') {
                    $value = $detail->{$field};
                } elseif (isset($record->{$field}) && $record->{$field} !== '') {
                    $value = $record->{$field};
                }

                if ($value !== '') {
                    $address_parts[] = $value;
                }
            }

            $city_value = tipax_get_address_record_field($record, 'cityTitle', '');
            if ($city_value === '') {
                $city_id = tipax_get_address_record_field($record, 'cityId', '');
                $city_value = $city_id !== '' ? '#' . $city_id : '-';
            }

            $rows[] = [
                'record_id' => (string) tipax_get_address_record_field($record, 'id', ''),
                'origin_id' => tipax_get_address_origin_id($record),
                'title' => (string) tipax_get_address_record_field($record, 'title', '-'),
                'city' => (string) $city_value,
                'full_address' => (string) tipax_get_address_record_field($record, 'fullAddress', !empty($address_parts) ? implode('، ', $address_parts) : '-'),
                'is_default' => !empty($record->isDefault) || (is_object($detail) && !empty($detail->isDefault)),
            ];
        }
    }

    return $rows;
}

function tipax_extract_created_origin_address_id($response, Tipax_Address_Service $address_service, $payload = [])
{
    $candidates = [];
    $record_id = null;

    if (is_object($response)) {
        $candidates[] = $response;

        if (isset($response->result)) {
            if (is_array($response->result)) {
                $candidates = array_merge($candidates, $response->result);
            } elseif (is_object($response->result)) {
                $candidates[] = $response->result;
            }
        }

        if (isset($response->data)) {
            if (is_array($response->data)) {
                $candidates = array_merge($candidates, $response->data);
            } elseif (is_object($response->data)) {
                $candidates[] = $response->data;
            }
        }
    } elseif (is_array($response)) {
        $candidates = $response;
    }

    foreach ($candidates as $candidate) {
        if (!is_object($candidate)) {
            continue;
        }

        $origin_id = tipax_get_address_origin_id($candidate);
        if ($origin_id !== '') {
            return (string) $origin_id;
        }

        $candidate_id = isset($candidate->id) && $candidate->id !== '' ? (string) $candidate->id : '';
        if ($record_id === null && $candidate_id !== '') {
            $record_id = (string) $candidate_id;
        }
    }

    $all_addresses = tipax_get_tipax_address_list($address_service->fetch_addresses());
    foreach ($all_addresses as $address_record) {
        if (!is_object($address_record)) {
            continue;
        }

        $candidate_origin_id = tipax_get_address_origin_id($address_record);
        $candidate_record_id = isset($address_record->id) && $address_record->id !== '' ? (string) $address_record->id : '';

        if ($record_id !== null && $candidate_record_id !== '' && (string) $candidate_record_id === $record_id) {
            return (string) ($candidate_origin_id !== '' ? $candidate_origin_id : $candidate_record_id);
        }

        $candidate_title = trim((string) tipax_get_address_record_field($address_record, 'title', ''));
        $candidate_city_id = (string) tipax_get_address_record_field($address_record, 'cityETipaxId', '');
        if ($candidate_city_id === '') {
            $candidate_city_id = (string) tipax_get_address_record_field($address_record, 'cityId', '');
        }
        $candidate_main_street = trim((string) tipax_get_address_record_field($address_record, 'mainStreet1', ''));
        $candidate_no = trim((string) tipax_get_address_record_field($address_record, 'no', ''));
        if ($candidate_no === '') {
            $candidate_no = trim((string) tipax_get_address_record_field($address_record, 'plaque', ''));
        }

        if (
            $candidate_title !== '' &&
            $candidate_title === trim((string) ($payload['title'] ?? '')) &&
            $candidate_city_id === (string) ($payload['cityId'] ?? '') &&
            $candidate_main_street === trim((string) ($payload['mainStreet1'] ?? '')) &&
            $candidate_no === trim((string) ($payload['no'] ?? ''))
        ) {
            return (string) ($candidate_origin_id !== '' ? $candidate_origin_id : $candidate_record_id);
        }
    }

    return null;
}

function tipax_find_address_record_by_origin_id($response, $origin_id)
{
    foreach (tipax_get_tipax_address_list($response) as $record) {
        if (!Tipax_Address_Service::is_sender_address_record($record)) {
            continue;
        }

        $candidate_origin_id = tipax_get_address_origin_id($record);
        $candidate_record_id = isset($record->id) && $record->id !== '' ? (string) $record->id : '';
        if (
            ($candidate_origin_id !== '' && $candidate_origin_id === (string) $origin_id) ||
            ($candidate_record_id !== '' && $candidate_record_id === (string) $origin_id)
        ) {
            return $record;
        }
    }

    return null;
}

function tipax_store_selected_origin_address($origin_id, $selected_record = null)
{
    if (get_option('tipax_shipping_address') !== false) {
        update_option('tipax_shipping_address', $origin_id, 'yes');
    } else {
        add_option('tipax_shipping_address', $origin_id, '', 'yes');
    }

    update_option('tipax_has_address', 'yes');
    delete_transient('addresses_loaded');

    if ($selected_record !== null) {
        set_transient('tipax_addresses', $selected_record, 12 * HOUR_IN_SECONDS);
    } else {
        delete_transient('tipax_addresses');
    }
}

function tipax_render_address_rows_table($rows)
{
    if (empty($rows)) {
        echo '<div class="notice notice-warning"><p>هیچ آدرسی از تیپاکس دریافت نشد.</p></div>';
        return;
    }

    $selected_origin_id = get_option('tipax_shipping_address');

    echo '<table class="widefat striped tipax-data-table tipax-addresses-table">';
    echo '<thead><tr><th>عنوان</th><th>شهر</th><th>وضعیت</th><th>شناسه مبدا</th><th>آدرس کامل</th><th>عملیات</th></tr></thead><tbody>';

    foreach ($rows as $row) {
        $status_badges = [];
        $row_origin_id = $row['origin_id'] !== '' ? $row['origin_id'] : $row['record_id'];
        if (!empty($row['is_default'])) {
            $status_badges[] = '<span class="tipax-address-badge">پیش‌فرض</span>';
        }
        if ($row_origin_id !== '' && (string) $row_origin_id === (string) $selected_origin_id) {
            $status_badges[] = '<span class="tipax-address-badge is-selected">انتخاب‌شده</span>';
        }

        echo '<tr>';
        echo '<td>' . esc_html($row['title']) . '</td>';
        echo '<td>' . esc_html($row['city']) . '</td>';
        echo '<td>' . (!empty($status_badges) ? implode(' ', $status_badges) : '<span class="tipax-text-muted">-</span>') . '</td>';
        echo '<td style="direction:ltr;text-align:left;">' . esc_html($row_origin_id) . '</td>';
        echo '<td>' . esc_html($row['full_address']) . '</td>';
        echo '<td>';
        if ($row_origin_id !== '') {
            if ((string) $row_origin_id === (string) $selected_origin_id) {
                echo '<span class="button button-secondary tipax-address-select-button is-current" aria-disabled="true">مبدای فعلی سایت</span>';
            } else {
                echo '<button type="button" class="button button-secondary tipax-address-select-button" data-tipax-origin-id="' . esc_attr($row_origin_id) . '">انتخاب به‌عنوان مبدا سایت</button>';
            }
        } else {
            echo '<span class="tipax-text-muted">-</span>';
        }
        echo '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
}

function tipax_addresses_page() {
    tipax_admin_tabs('tipax-addresses');

    if (!tipax_is_connection_validated()) {
        echo '<div class="notice notice-error"><p>برای دسترسی به این بخش ابتدا باید <a href="' . admin_url('admin.php?page=tipax-api-config') . '">تنظیمات اتصال</a> را کامل کرده و اتصال را <a href="' . admin_url('admin.php?page=tipax-test-connection') . '">آزمایش</a> کنید.</p></div>';
        return;
    }

    $city_rows = tipax_get_admin_address_city_rows();
    ?>
    <div class="tipax-action-panel tipax-address-panel">
        <div class="tipax-action-icon"><?php echo tipax_admin_icon('location'); ?></div>
        <div class="tipax-action-content">
            <h2>ثبت و مدیریت آدرس‌های تیپاکس</h2>
            <p>آدرس جدید را مستقیم در تیپاکس بسازید. بعد از ثبت موفق، شناسه مبدا ذخیره می‌شود تا محاسبه نرخ و ثبت سفارش با همان آدرس کار کند.</p>
        </div>
        <div class="tipax-address-actions">
            <button id="tipax_open_create_address_modal" class="button button-primary tipax-action-button" type="button">افزودن آدرس جدید</button>
            <button id="update_tipax_addresses" class="button button-secondary tipax-action-button" type="button">بروزرسانی لیست آدرس‌ها</button>
        </div>
    </div>

    <div id="tipax_create_address_result"></div>

    <div id="tipax_address_modal" class="tipax-modal" hidden>
        <div class="tipax-modal-backdrop" data-tipax-close-address-modal></div>
        <div class="tipax-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="tipax-address-modal-title">
            <div class="tipax-modal-head">
                <div class="tipax-modal-copy">
                    <h3 id="tipax-address-modal-title">ثبت آدرس جدید</h3>
                    <p>فقط فیلدهای ضروری و آدرس متنی را تکمیل کنید. بقیه موارد اختیاری هستند.</p>
                </div>
                <button type="button" class="tipax-modal-close" data-tipax-close-address-modal aria-label="بستن">×</button>
            </div>

            <form id="tipax_create_address_form" class="tipax-modal-form">
                <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('tipax_create_address')); ?>">
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="tipax_address_title">عنوان آدرس</label></th>
                        <td>
                            <input type="text" id="tipax_address_title" name="title" required>
                            <p class="description">فیلد اجباری.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="tipax_address_city_id">شهر</label></th>
                        <td>
                            <?php if (!empty($city_rows)) : ?>
                                <select id="tipax_address_city_id" name="cityId" required>
                                    <option value="">یک شهر انتخاب کنید</option>
                                    <?php
                                    $current_state = null;
                                    foreach ($city_rows as $city_row) {
                                        $state_title = (string) $city_row->stateTitle;
                                        if ($current_state !== $state_title) {
                                            if ($current_state !== null) {
                                                echo '</optgroup>';
                                            }
                                            $current_state = $state_title;
                                            echo '<optgroup label="' . esc_attr($current_state) . '">';
                                        }
                                        echo '<option value="' . esc_attr($city_row->cityId) . '">' . esc_html($city_row->cityTitle) . '</option>';
                                    }
                                    if ($current_state !== null) {
                                        echo '</optgroup>';
                                    }
                                    ?>
                                </select>
                            <?php else : ?>
                                <input type="number" id="tipax_address_city_id" name="cityId" min="1" required>
                                <p class="description">اگر لیست شهرها در دسترس نیست، شناسه شهر تیپاکس را دستی وارد کنید.</p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="tipax_address_main_street1">خیابان اصلی</label></th>
                        <td>
                            <input type="text" id="tipax_address_main_street1" name="mainStreet1" required>
                            <p class="description">فیلد اجباری.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="tipax_address_no">پلاک</label></th>
                        <td>
                            <input type="text" id="tipax_address_no" name="no" required>
                            <p class="description">فیلد اجباری.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="tipax_address_side_street1">خیابان فرعی</label></th>
                        <td><input type="text" id="tipax_address_side_street1" name="sideStreet1"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="tipax_address_main_alley1">کوچه اصلی</label></th>
                        <td><input type="text" id="tipax_address_main_alley1" name="mainAlley1"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="tipax_address_side_alley1">کوچه فرعی</label></th>
                        <td><input type="text" id="tipax_address_side_alley1" name="sideAlley1"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="tipax_address_other_address">توضیحات تکمیلی</label></th>
                        <td><textarea id="tipax_address_other_address" name="otherAddress" rows="3"></textarea></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="tipax_address_postal_code">کد پستی</label></th>
                        <td><input type="text" id="tipax_address_postal_code" name="postalCode"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="tipax_address_unit">واحد</label></th>
                        <td>
                            <input type="text" id="tipax_address_unit" name="unit" required>
                            <p class="description">فیلد اجباری.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="tipax_address_floor">طبقه</label></th>
                        <td>
                            <input type="text" id="tipax_address_floor" name="floor" required>
                            <p class="description">فیلد اجباری.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="tipax_address_is_default">پیش‌فرض</label></th>
                        <td>
                            <div class="tipax-modal-switch-row">
                                <label class="tipax-switch" for="tipax_address_is_default">
                                    <input type="checkbox" id="tipax_address_is_default" name="isDefault" value="1">
                                    <span class="tipax-switch-track"></span>
                                </label>
                                <div class="tipax-modal-switch-copy">
                                    <label for="tipax_address_is_default">این آدرس به‌عنوان آدرس پیش‌فرض تیپاکس ثبت شود</label>
                                </div>
                            </div>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="submit" class="button button-primary" id="tipax_create_address_submit">ثبت آدرس در تیپاکس</button>
                </p>
            </form>
        </div>
    </div>

    <div id="tipax_addresses_result" class="tipax-result-area"><div class="tipax-empty-state"><?php echo tipax_admin_icon('location'); ?><p>در حال آماده‌سازی لیست آدرس‌ها...</p></div></div>

    <script>
        jQuery(document).ready(function($) {
            var addressModal = $('#tipax_address_modal');
            if (addressModal.length && !addressModal.parent().is('body')) {
                addressModal.appendTo('body');
            }

            function openAddressModal() {
                addressModal.removeAttr('hidden');
                $('body').addClass('tipax-modal-open');
            }

            function closeAddressModal() {
                addressModal.attr('hidden', 'hidden');
                $('body').removeClass('tipax-modal-open');
            }

            function renderCreateAddressMessage(type, message) {
                $('#tipax_create_address_result').html('<div class="notice notice-' + type + '"><p>' + message + '</p></div>');
            }

            function loadAddresses() {
                $('#tipax_addresses_result').html('<div class="tipax-loading-state"><span></span><p>در حال بارگیری آدرس‌ها...</p></div>');
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'tipax_reload_addresses'
                    },
                    success: function(response) {
                        $('#tipax_addresses_result').html(response);
                    },
                    error: function() {
                        $('#tipax_addresses_result').html('<div class="notice notice-error"><p>خطا در بارگیری آدرس‌ها</p></div>');
                    }
                });
            }

            loadAddresses();

            $('#update_tipax_addresses').on('click', function() {
                loadAddresses();
            });

            $(document).on('click', '.tipax-address-select-button[data-tipax-origin-id]', function() {
                var button = $(this);
                var originId = button.data('tipax-origin-id');
                if (!originId) {
                    return;
                }

                button.prop('disabled', true).text('در حال انتخاب...');
                renderCreateAddressMessage('warning', 'در حال ثبت مبدا انتخاب‌شده برای سایت...');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'tipax_select_origin_address',
                        nonce: '<?php echo esc_js(wp_create_nonce('tipax_select_origin_address')); ?>',
                        originAddressId: originId
                    },
                    success: function(response) {
                        if (response && response.success) {
                            renderCreateAddressMessage('success', response.data.message);
                            loadAddresses();
                        } else {
                            var message = response && response.data && response.data.message ? response.data.message : 'خطا در انتخاب آدرس مبدا.';
                            renderCreateAddressMessage('error', message);
                        }
                    },
                    error: function() {
                        renderCreateAddressMessage('error', 'خطا در ارتباط با سرور هنگام انتخاب آدرس مبدا.');
                    },
                    complete: function() {
                        button.prop('disabled', false).text('انتخاب به‌عنوان مبدا سایت');
                    }
                });
            });

            $('#tipax_open_create_address_modal').on('click', function() {
                openAddressModal();
            });

            $(document).on('click', '[data-tipax-close-address-modal]', function() {
                closeAddressModal();
            });

            $(document).on('keydown', function(event) {
                if (event.key === 'Escape' && !addressModal.is('[hidden]')) {
                    closeAddressModal();
                }
            });

            $('#tipax_create_address_form').on('submit', function(event) {
                event.preventDefault();

                if (typeof this.reportValidity === 'function' && !this.reportValidity()) {
                    return;
                }

                var submitButton = $('#tipax_create_address_submit');
                submitButton.prop('disabled', true).text('در حال ثبت آدرس...');
                renderCreateAddressMessage('warning', 'در حال ارسال اطلاعات آدرس به تیپاکس...');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    data: $(this).serialize() + '&action=tipax_create_address',
                    success: function(response) {
                        if (response && response.success) {
                            renderCreateAddressMessage('success', response.data.message);
                            $('#tipax_create_address_form')[0].reset();
                            closeAddressModal();
                            loadAddresses();
                        } else {
                            var message = response && response.data && response.data.message ? response.data.message : 'خطا در ایجاد آدرس.';
                            renderCreateAddressMessage('error', message);
                        }
                    },
                    error: function() {
                        renderCreateAddressMessage('error', 'خطا در ارتباط با سرور هنگام ثبت آدرس.');
                    },
                    complete: function() {
                        submitButton.prop('disabled', false).text('ثبت آدرس در تیپاکس');
                    }
                });
            });
        });
    </script>
    <?php
}

add_action('wp_ajax_tipax_reload_addresses', 'tipax_reload_addresses');
add_action('wp_ajax_tipax_create_address', 'tipax_create_address');
add_action('wp_ajax_tipax_select_origin_address', 'tipax_select_origin_address');

function tipax_reload_addresses() {
    delete_transient('tipax_addresses');
    delete_transient('addresses_loaded');

    try {
        $components = tipax_get_address_service_components();
        $response = $components['service']->fetch_addresses();
        $rows = tipax_collect_address_rows($response);
        if (empty($rows)) {
            $components['auth']->check_api_key(true);
            $response = $components['service']->fetch_addresses();
            $rows = tipax_collect_address_rows($response);
        }
    } catch (Throwable $e) {
        echo '<div class="notice notice-error"><p>خطای داخلی در دریافت آدرس‌ها: ' . esc_html($e->getMessage()) . '</p></div>';
        wp_die();
    }

    if (empty($rows)) {
        echo '<div class="notice notice-warning"><p>هنوز آدرسی از تیپاکس برنگشته است. می‌توانید از فرم بالا آدرس جدید بسازید یا لیست را دوباره بروزرسانی کنید.</p></div>';
        wp_die();
    }

    tipax_render_address_rows_table($rows);
    wp_die();
}

function tipax_select_origin_address()
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'دسترسی غیرمجاز است.']);
    }

    check_ajax_referer('tipax_select_origin_address', 'nonce');

    $origin_address_id = sanitize_text_field(wp_unslash($_POST['originAddressId'] ?? ''));
    if ($origin_address_id === '') {
        wp_send_json_error(['message' => 'شناسه مبدا معتبر نیست.']);
    }

    try {
        $components = tipax_get_address_service_components();
        $response = $components['service']->fetch_addresses();
        $selected_record = tipax_find_address_record_by_origin_id($response, $origin_address_id);

        if ($selected_record === null) {
            $components['auth']->check_api_key(true);
            $response = $components['service']->fetch_addresses();
            $selected_record = tipax_find_address_record_by_origin_id($response, $origin_address_id);
        }

        if ($selected_record === null) {
            wp_send_json_error(['message' => 'آدرس انتخاب‌شده در لیست فعلی تیپاکس پیدا نشد.']);
        }

        $stored_origin_id = tipax_get_address_origin_id($selected_record);
        if ($stored_origin_id === '') {
            $stored_origin_id = $origin_address_id;
        }

        tipax_store_selected_origin_address($stored_origin_id, $selected_record);

        wp_send_json_success([
            'message' => 'مبدا سایت با موفقیت تغییر کرد.',
            'originAddressId' => $stored_origin_id,
        ]);
    } catch (Throwable $e) {
        wp_send_json_error(['message' => 'خطای داخلی هنگام انتخاب آدرس مبدا: ' . $e->getMessage()]);
    }
}

function tipax_create_address()
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'دسترسی غیرمجاز است.']);
    }

    check_ajax_referer('tipax_create_address', 'nonce');

    $title = sanitize_text_field(wp_unslash($_POST['title'] ?? ''));
    $city_id = absint(wp_unslash($_POST['cityId'] ?? 0));
    $main_street = sanitize_text_field(wp_unslash($_POST['mainStreet1'] ?? ''));
    $no = sanitize_text_field(wp_unslash($_POST['no'] ?? ''));
    $unit = sanitize_text_field(wp_unslash($_POST['unit'] ?? ''));
    $floor = sanitize_text_field(wp_unslash($_POST['floor'] ?? ''));

    if ($unit === '' || $floor === '') {
        wp_send_json_error(['message' => 'Please fill in all required fields.']);
    }

    if ($title === '' || $city_id <= 0 || $main_street === '' || $no === '') {
        wp_send_json_error(['message' => 'فیلدهای اجباری title، cityId، mainStreet1 و no باید تکمیل شوند.']);
    }

    try {
        $components = tipax_get_address_service_components();
        $auth = $components['auth'];
        $address_service = $components['service'];

        if (!$auth->get_access_token()) {
            wp_send_json_error(['message' => 'توکن دسترسی تیپاکس در دسترس نیست. اتصال را دوباره آزمایش کنید.']);
        }

        $payload = [
            'title' => $title,
            'cityId' => $city_id,
            'districtId' => 0,
            'mainStreet1' => $main_street,
            'sideStreet1' => sanitize_text_field(wp_unslash($_POST['sideStreet1'] ?? '')),
            'mainAlley1' => sanitize_text_field(wp_unslash($_POST['mainAlley1'] ?? '')),
            'sideAlley1' => sanitize_text_field(wp_unslash($_POST['sideAlley1'] ?? '')),
            'otherAddress' => sanitize_textarea_field(wp_unslash($_POST['otherAddress'] ?? '')),
            'latitude' => '',
            'longitude' => '',
            'postalCode' => sanitize_text_field(wp_unslash($_POST['postalCode'] ?? '')),
            'unit' => $unit,
            'floor' => $floor,
            'no' => $no,
            'isDefault' => !empty($_POST['isDefault']),
        ];

        $response = $address_service->create_address($payload, $auth->get_access_token());

        if (!is_object($response) && !is_array($response)) {
            $auth->check_api_key(true);
            $response = $address_service->create_address($payload, $auth->get_access_token());
        }

        if (is_object($response) && isset($response->errors)) {
            wp_send_json_error(['message' => tipax_extract_tipax_response_message($response, 'تیپاکس آدرس را ایجاد نکرد.')]);
        }

        if (!is_object($response) && !is_array($response)) {
            wp_send_json_error(['message' => tipax_extract_tipax_response_message($response, 'پاسخ معتبری از تیپاکس دریافت نشد.')]);
        }

        $origin_address_id = tipax_extract_created_origin_address_id($response, $address_service, $payload);
        if ($origin_address_id === null || $origin_address_id === '') {
            $auth->check_api_key(true);
            $origin_address_id = tipax_extract_created_origin_address_id($response, $address_service, $payload);
        }
        if ($origin_address_id === null || $origin_address_id === '') {
            wp_send_json_error(['message' => 'آدرس در تیپاکس ایجاد شد اما شناسه مبدا از پاسخ یا لیست آدرس‌ها قابل تشخیص نبود.']);
        }

        $selected_record = is_object($response) ? $response : null;
        if ($selected_record === null || tipax_get_address_origin_id($selected_record) !== (string) $origin_address_id) {
            $selected_record = tipax_find_address_record_by_origin_id($address_service->fetch_addresses(), $origin_address_id);
        }
        if ($selected_record === null) {
            $auth->check_api_key(true);
            $selected_record = tipax_find_address_record_by_origin_id($address_service->fetch_addresses(), $origin_address_id);
        }

        tipax_store_selected_origin_address($origin_address_id, $selected_record);

        wp_send_json_success([
            'message' => 'آدرس با موفقیت ایجاد شد و شناسه مبدا ' . $origin_address_id . ' ذخیره شد.',
            'originAddressId' => $origin_address_id,
        ]);
    } catch (Throwable $e) {
        wp_send_json_error(['message' => 'خطای داخلی هنگام ثبت آدرس: ' . $e->getMessage()]);
    }
}

function tipax_logs_page() {
    if (!tipax_is_logging_enabled()) {
        wp_die(esc_html(tipax_text('logging_disabled_notice')), '', ['response' => 403]);
    }

    tipax_admin_tabs('tipax-logs');
    if (!tipax_is_connection_validated()) {
        echo '<div class="notice notice-error"><p>برای دسترسی به این بخش ابتدا باید <a href="' . admin_url('admin.php?page=tipax-api-config') . '">تنظیمات اتصال</a> را کامل کرده و اتصال را <a href="' . admin_url('admin.php?page=tipax-test-connection') . '">آزمایش</a> کنید.</p></div>';
        return;
    }

    if (get_option('tipax_has_address') !== 'yes') {
        echo '<div class="notice notice-warning"><p>برای استفاده از این بخش باید حداقل یک آدرس ثبت شده در حساب تیپاکس شما باشد. لطفاً به <a href="' . admin_url('admin.php?page=tipax-addresses') . '">بخش آدرس‌ها</a> مراجعه کنید.</p></div>';
        return;
    }

    echo '<div class="tipax-action-panel tipax-log-panel">';
    echo '<div class="tipax-action-icon">' . tipax_admin_icon('media-text') . '</div>';
    echo '<div class="tipax-action-content">';
    echo '<h2>Tipax Log File</h2>';
    echo '<p>لاگ‌های افزونه را برای بررسی API، نرخ‌دهی، ثبت سفارش و خطاهای احتمالی مشاهده یا پاک کنید.</p>';
    echo '</div>';
    echo '<button id="clear_tipax_log" class="button button-secondary tipax-action-button">' . tipax_admin_icon('trash') . ' پاک‌سازی لاگ</button>';
    echo '</div>';

    echo '<pre id="tipax_log_output" class="tipax-log-console">در حال بارگذاری...</pre>';

    ?>
    <script>
        jQuery(document).ready(function($) {
            function loadLog() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'tipax_load_log'
                    },
                    success: function(response) {
                        $('#tipax_log_output').text(response);
                    },
                    error: function() {
                        $('#tipax_log_output').text('خطا در بارگیری لاگ.');
                    }
                });
            }

            $('#clear_tipax_log').on('click', function() {
                if (!confirm('آیا مطمئن هستید که می‌خواهید لاگ را پاک کنید؟')) return;

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'tipax_clear_log'
                    },
                    success: function(response) {
                        $('#tipax_log_output').text('');
                    },
                    error: function() {
                        $('#tipax_log_output').text('خطا در پاک کردن لاگ.');
                    }
                });
            });

            loadLog();
        });
    </script>
    <?php
}


add_action('wp_ajax_tipax_load_log', function() {
    if (!tipax_is_logging_enabled()) {
        wp_die(esc_html(tipax_text('logging_disabled_notice')), '', ['response' => 403]);
    }

    $log_module = new Tipax_Report_Log_Service();
    $contents = $log_module->get_log_contents();
    if ($contents === false) {
        echo "فایلی با این مسیر یافت نشد.";
        wp_die();
    }

    echo esc_textarea($contents ?: 'فایل لاگ خالی است.');
    wp_die();
});

add_action('wp_ajax_tipax_clear_log', function() {
    if (!tipax_is_logging_enabled()) {
        wp_die(esc_html(tipax_text('logging_disabled_notice')), '', ['response' => 403]);
    }

    $log_module = new Tipax_Report_Log_Service();
    if (file_exists($log_module->get_log_file_path())) {
        $log_module->clear_logs();
        echo 'Log cleared.';
    } else {
        echo 'Log file not found.';
    }

    wp_die();
});

function tipax_get_admin_packing_catalog($force_refresh = false)
{
    $packing_items = tipax_get_packing_prices($force_refresh);
    $catalog = [
        'index' => [],
        'options' => [
            [
                'value' => '',
                'label' => __('پیش‌فرض (بدون بسته‌بندی)', 'delivery-service-tipax-shipping'),
            ],
        ],
    ];

    if (!is_array($packing_items)) {
        return $catalog;
    }

    foreach ($packing_items as $item) {
        if (!is_object($item) || !isset($item->id) || !isset($item->title)) {
            continue;
        }

        $title = (string) $item->title;
        if (function_exists('mb_stripos')) {
            if (mb_stripos($title, 'نایلون حبابدار') !== false) {
                continue;
            }
        } elseif (stripos($title, 'نایلون حبابدار') !== false) {
            continue;
        }

        $id = (string) $item->id;
        $length = isset($item->length) && is_numeric($item->length) ? (float) $item->length : 0.0;
        $width = isset($item->width) && is_numeric($item->width) ? (float) $item->width : 0.0;
        $height = isset($item->height) && is_numeric($item->height) ? (float) $item->height : 0.0;
        $max_length = isset($item->maxLength) && is_numeric($item->maxLength) ? (float) $item->maxLength : 0.0;
        $max_width = isset($item->maxWidth) && is_numeric($item->maxWidth) ? (float) $item->maxWidth : 0.0;
        $max_height = isset($item->maxHeight) && is_numeric($item->maxHeight) ? (float) $item->maxHeight : 0.0;
        $min_length = isset($item->minLength) && is_numeric($item->minLength) ? (float) $item->minLength : 0.0;
        $min_width = isset($item->minWidth) && is_numeric($item->minWidth) ? (float) $item->minWidth : 0.0;
        $min_height = isset($item->minHeight) && is_numeric($item->minHeight) ? (float) $item->minHeight : 0.0;
        $min_weight = isset($item->minWeight) && is_numeric($item->minWeight) ? (float) $item->minWeight : 0.0;
        $max_weight = isset($item->maxWeight) && is_numeric($item->maxWeight) ? (float) $item->maxWeight : 0.0;

        $pack_type = isset($item->packType) && is_numeric($item->packType) ? (int) $item->packType : 0;
        $payload_dimensions = tipax_get_selected_packing_payload_dimensions([
            'title' => $title,
            'packType' => $pack_type,
            'length' => $length,
            'width' => $width,
            'height' => $height,
            'minLength' => $min_length,
            'minWidth' => $min_width,
            'minHeight' => $min_height,
            'maxLength' => $max_length,
            'maxWidth' => $max_width,
            'maxHeight' => $max_height,
        ]);
        $length = $payload_dimensions['length'];
        $width = $payload_dimensions['width'];
        $height = $payload_dimensions['height'];
        $label = tipax_format_admin_packing_label($title, $length, $width, $height);

        $catalog['index'][$id] = [
            'id' => (int) $item->id,
            'title' => $title,
            'label' => $label,
            'packType' => $pack_type,
            'packageContentId' => tipax_get_package_content_id_for_pack_type($pack_type),
            'length' => $length,
            'width' => $width,
            'height' => $height,
            'maxLength' => $max_length,
            'maxWidth' => $max_width,
            'maxHeight' => $max_height,
            'minLength' => $min_length,
            'minWidth' => $min_width,
            'minHeight' => $min_height,
            'minWeight' => $min_weight,
            'maxWeight' => $max_weight,
        ];
        if (!tipax_is_no_packing_id($id)) {
            $catalog['options'][] = [
                'value' => $id,
                'label' => $label,
            ];
        }
    }

    return $catalog;
}

function tipax_collect_tipax_response_messages($response, $default = 'خطای نامشخص از تیپاکس.')
{
    $messages = [];

    if (is_wp_error($response)) {
        $messages[] = $response->get_error_message();
    } elseif (is_object($response) && !empty($response->errors) && is_array($response->errors)) {
        foreach ($response->errors as $error) {
            if (is_object($error)) {
                if (!empty($error->errorMessage)) {
                    $messages[] = $error->errorMessage;
                } elseif (!empty($error->message)) {
                    $messages[] = $error->message;
                }
            } elseif (is_string($error) && $error !== '') {
                $messages[] = $error;
            }
        }
    }

    if (empty($messages)) {
        $message = tipax_extract_tipax_response_message($response, $default);
        $parts = preg_split('/\r\n|\r|\n/', (string) $message);
        foreach ($parts as $part) {
            $part = trim((string) $part);
            if ($part !== '') {
                $messages[] = $part;
            }
        }
    }

    $messages = array_values(array_unique(array_filter(array_map('trim', $messages))));
    return !empty($messages) ? $messages : [$default];
}

function tipax_render_order_retry_result_panel($args = [])
{
    $args = wp_parse_args($args, [
        'type' => 'info',
        'badge' => '',
        'title' => '',
        'messages' => [],
        'meta' => [],
        'rates' => [],
        'tracking_codes' => [],
        'tipax_order_id' => '',
        'show_reload' => false,
    ]);

    $type = in_array($args['type'], ['success', 'error', 'info'], true) ? $args['type'] : 'info';

    ob_start();
    ?>
    <div class="tipax-order-retry-result is-<?php echo esc_attr($type); ?>">
        <div class="tipax-order-retry-result-head">
            <?php if ($args['badge'] !== '') : ?>
                <span class="tipax-order-retry-result-badge"><?php echo esc_html($args['badge']); ?></span>
            <?php endif; ?>
            <?php if ($args['title'] !== '') : ?>
                <h4><?php echo esc_html($args['title']); ?></h4>
            <?php endif; ?>
        </div>

        <?php if (!empty($args['meta'])) : ?>
            <div class="tipax-order-retry-result-meta">
                <?php foreach ($args['meta'] as $label => $value) : ?>
                    <?php if ($value === '' || $value === null) { continue; } ?>
                    <span><strong><?php echo esc_html($label); ?>:</strong> <?php echo esc_html($value); ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($args['messages'])) : ?>
            <ul class="tipax-order-retry-result-messages">
                <?php foreach ($args['messages'] as $message) : ?>
                    <li><?php echo esc_html($message); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if (!empty($args['rates'])) : ?>
            <div class="tipax-order-retry-rate-table-wrap">
                <table class="tipax-order-retry-rate-table">
                    <thead>
                    <tr>
                        <th><?php esc_html_e('سرویس', 'delivery-service-tipax-shipping'); ?></th>
                        <th><?php esc_html_e('شناسه سرویس', 'delivery-service-tipax-shipping'); ?></th>
                        <th><?php esc_html_e('مبلغ', 'delivery-service-tipax-shipping'); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($args['rates'] as $rate) : ?>
                        <tr>
                            <td><?php echo esc_html($rate['serviceTitle'] ?? '-'); ?></td>
                            <td><?php echo esc_html($rate['serviceId'] ?? '-'); ?></td>
                            <td><?php echo esc_html(number_format((float) ($rate['cost'] ?? 0))); ?> <?php echo esc_html($rate['currencyLabel'] ?? ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if (!empty($args['tracking_codes'])) : ?>
            <div class="tipax-order-retry-tracking-codes">
                <?php foreach ($args['tracking_codes'] as $tracking_code) : ?>
                    <?php if ($tracking_code === '') { continue; } ?>
                    <code><?php echo esc_html($tracking_code); ?></code>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($args['tipax_order_id'])) : ?>
            <div class="tipax-order-retry-order-id">
                <strong><?php esc_html_e('شناسه سفارش تیپاکس:', 'delivery-service-tipax-shipping'); ?></strong>
                <code><?php echo esc_html($args['tipax_order_id']); ?></code>
            </div>
        <?php endif; ?>

        <?php if (!empty($args['show_reload'])) : ?>
            <div class="tipax-order-retry-result-actions">
                <button type="button" class="button button-primary tipax-reload-orders-page"><?php esc_html_e('بروزرسانی لیست سفارشات', 'delivery-service-tipax-shipping'); ?></button>
            </div>
        <?php endif; ?>
    </div>
    <?php

    return trim(ob_get_clean());
}

function tipax_orders_page_legacy() {
    tipax_admin_tabs('tipax-orders');

    $paged    = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
    $per_page = 10;
    $after = date('Y-m-d H:i:s', strtotime('-10 days'));

    $args = [
        'status'     => ['processing', 'completed', 'on-hold', 'pending'],
        'limit'      => $per_page,
        'paged'      => $paged,
        'orderby'    => 'date',
        'order'      => 'DESC',
        'date_query' => [
            [
                'after'     => $after,
                'inclusive' => true,
            ],
        ],
        'shipping_method' => 'tipax_shipping_method',
    ];

    $query = new WC_Order_Query($args);
    $tipax_orders = $query->get_orders();

    $args['limit'] = -1;
    $args['paged'] = '';
    $count_query   = new WC_Order_Query($args);
    $total_orders  = count($count_query->get_orders());
    $total_pages   = ceil($total_orders / $per_page);
    $default_tracking_batch_size = (int) apply_filters('tipax_tracking_batch_size', 20);
    $last_tracking_sync_report = get_option('tipax_tracking_last_sync_report', []);

    echo '<div class="tipax-action-panel tipax-orders-panel">';
    echo '<div class="tipax-action-icon">' . tipax_admin_icon('archive') . '</div>';
    echo '<div class="tipax-action-content">';
    echo '<h2>سفارشات تیپاکس</h2>';
    echo '<p>سفارش‌های ۱۰ روز اخیر که روش ارسال تیپاکس دارند، برای ثبت مجدد یا بروزرسانی وضعیت نمایش داده می‌شوند.</p>';
    echo '</div>';
    echo '<div class="tipax-stats">';
    echo '<span><strong>' . esc_html($total_orders) . '</strong> سفارش</span>';
    echo '<span><strong>' . esc_html($paged) . '</strong> صفحه فعلی</span>';
    echo '</div>';
    echo '</div>';
    echo '<div class="tipax-orders-toolbar">';
    echo '<div class="tipax-orders-toolbar-actions">';
    echo '<button type="button" class="button button-primary tipax-batch-update-page">' . esc_html(tipax_text('orders_batch_page')) . '</button>';
    echo '<button type="button" class="button tipax-run-due-batch" data-limit="' . esc_attr($default_tracking_batch_size) . '">' . esc_html(tipax_text('orders_batch_auto')) . ' ' . esc_html($default_tracking_batch_size) . ' ' . esc_html(tipax_text('orders_batch_auto_suffix')) . '</button>';
    echo '</div>';
    echo '<div class="tipax-orders-toolbar-meta">';
    echo '<span>' . esc_html(tipax_text('orders_cron_info')) . '</span>';

    if (!empty($last_tracking_sync_report['ran_at'])) {
        $last_sync_timestamp = strtotime((string) $last_tracking_sync_report['ran_at']);
        $last_sync_display = $last_sync_timestamp ? wp_date('Y/m/d H:i', $last_sync_timestamp) : (string) $last_tracking_sync_report['ran_at'];
        echo '<span>' . esc_html(tipax_text('orders_last_run')) . ': <strong>' . esc_html($last_sync_display) . '</strong></span>';
        echo '<span>' . esc_html(tipax_text('orders_processed')) . ': <strong>' . esc_html((int) ($last_tracking_sync_report['processed'] ?? 0)) . '</strong></span>';
        echo '<span>' . esc_html(tipax_text('orders_updated')) . ': <strong>' . esc_html((int) ($last_tracking_sync_report['updated'] ?? 0)) . '</strong></span>';
        echo '<span>' . esc_html(tipax_text('orders_skipped')) . ': <strong>' . esc_html((int) ($last_tracking_sync_report['skipped'] ?? 0)) . '</strong></span>';
        echo '<span>' . esc_html(tipax_text('orders_failed')) . ': <strong>' . esc_html((int) ($last_tracking_sync_report['failed'] ?? 0)) . '</strong></span>';
    }

    echo '</div>';
    echo '</div>';
    echo '<div id="tipax-batch-update-feedback" class="tipax-batch-feedback" hidden></div>';

    echo '<table class="widefat striped tipax-data-table tipax-orders-table"><thead><tr>
        <th>شماره سفارش</th>
        <th>تاریخ</th>
        <th>نام مشتری</th>
        <th>محصولات</th>
        <th>کد پیگیری</th>
        <th>کد قرارداد</th>
        <th>وضعیت</th>
        <th>اقدامات</th>
    </tr></thead><tbody>';

    if (empty($tipax_orders)) {
        echo '<tr><td colspan="8">سفارشی یافت نشد.</td></tr>';
    }

    foreach ($tipax_orders as $order) {
        $first_item    = current($order->get_items());
        $tracking_code = $first_item ? tipax_normalize_tracking_codes_string($first_item->get_meta('tipax_tracking_code')) : '';
        $saved_status = $order->get_meta('_tipax_saved_status', true);
        $saved_contract = $order->get_meta('_tipax_saved_contract', true);
        $registration_state = $order->get_meta('_tipax_registration_state', true);
        $registration_reason = $order->get_meta('_tipax_registration_reason', true);
        $registration_label = tipax_get_order_tipax_registration_state_label($registration_state, $registration_reason);
        
        $billing_name  = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        
        $product_titles = [];
        foreach ($order->get_items() as $item) {
            $product_titles[] = $item->get_name() . ' × ' . $item->get_quantity();
        }
        $products_string = implode(', ', $product_titles);

        echo '<tr>';
        echo '<td>#' . $order->get_id() . '</td>';
        echo '<td>' . esc_html($order->get_date_created()->date_i18n('Y-m-d H:i')) . '</td>'; // ✅ show order date
        echo '<td>' . esc_html($billing_name) . '</td>';
        echo '<td>' . esc_html($products_string) . '</td>';
        echo '<td>' . esc_html($tracking_code) . '</td>';
        echo '<td>' . esc_html($saved_contract) . '</td>';
        echo '<td>' . esc_html(empty($tracking_code) && $registration_label !== '' ? $registration_label : ($saved_status ?: 'نامشخص')) . '</td>';
        echo '<td>';
        if (empty($tracking_code)) {
            $action_label = $registration_state === 'offline_pending'
                ? 'ثبت در تیپاکس'
                : ($registration_label !== '' ? 'ثبت / تعیین تکلیف در تیپاکس' : 'ثبت مجدد در تیپاکس');
            echo '<button class="button tipax-register-order" data-order-id="' . esc_attr($order->get_id()) . '">' . esc_html($action_label) . '</button>';
        } else {
            echo '<button class="button tipax-update-status" data-order-id="' . esc_attr($order->get_id()) . '">بروزرسانی وضعیت</button>';
        }
        echo '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';

    // ✅ Pagination buttons
    echo '<div class="tipax-pagination">';
    for ($i = 1; $i <= $total_pages; $i++) {
        echo '<a href="?page=tipax-orders&paged=' . $i . '" class="button' . ($i === $paged ? ' button-primary' : '') . '">' . $i . '</a> ';
    }
    echo '</div>';

    // JS part unchanged...
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.tipax-update-status').click(function(e) {
            e.preventDefault();
            let button = $(this);
            let orderId = button.data('order-id');

            button.prop('disabled', true).text('در حال بروزرسانی...');

            $.post(ajaxurl, {
                action: 'tipax_update_order_status',
                order_id: orderId,
                _ajax_nonce: '<?php echo wp_create_nonce('tipax_update_order'); ?>'
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    button.text('خطا').prop('disabled', false);
                    alert(response.data);
                }
            });
        });

        $('.tipax-register-order').click(function(e) {
            e.preventDefault();
            let button = $(this);
            let orderId = button.data('order-id');

            button.prop('disabled', true).text('در حال ثبت...');

            $.post(ajaxurl, {
                action: 'tipax_register_order',
                order_id: orderId,
                _ajax_nonce: '<?php echo wp_create_nonce('tipax_register_order'); ?>'
            }, function(response) {
                if (response.success) {
                    button.text('ثبت شد');
                    location.reload();
                } else {
                    button.text('خطا').prop('disabled', false);
                    alert(response.data);
                }
            });
        });
    });
    </script>
    <?php
}

function tipax_orders_page()
{
    tipax_orders_page_v2();
}

function tipax_parse_orders_filter_date($value)
{
    if (!is_scalar($value)) {
        return '';
    }

    $value = sanitize_text_field(wp_unslash((string) $value));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return '';
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date instanceof DateTimeImmutable && $date->format('Y-m-d') === $value ? $value : '';
}

function tipax_get_orders_view()
{
    $requested_view = isset($_GET['tipax_order_view'])
        ? sanitize_key(wp_unslash($_GET['tipax_order_view']))
        : '';

    return $requested_view === 'other' ? 'other' : 'tipax';
}

function tipax_get_orders_date_filter($order_view = 'tipax')
{
    $order_view = $order_view === 'other' ? 'other' : 'tipax';
    $now = current_time('timestamp');
    $default_from = date('Y-m-d', $now - (10 * DAY_IN_SECONDS));
    $default_to = date('Y-m-d', $now);
    $requested_from = isset($_GET['tipax_date_from']) ? tipax_parse_orders_filter_date($_GET['tipax_date_from']) : '';
    $requested_to = isset($_GET['tipax_date_to']) ? tipax_parse_orders_filter_date($_GET['tipax_date_to']) : '';
    $requested_scope = isset($_GET['tipax_date_scope']) ? sanitize_key(wp_unslash($_GET['tipax_date_scope'])) : '';
    $has_custom_range = $requested_from !== '' || $requested_to !== '';
    $show_all = $requested_scope === 'all' && !$has_custom_range;

    if ($requested_from !== '' && $requested_to !== '' && strcmp($requested_from, $requested_to) > 0) {
        $temporary_date = $requested_from;
        $requested_from = $requested_to;
        $requested_to = $temporary_date;
    }

    $from = $has_custom_range ? $requested_from : ($show_all ? '' : $default_from);
    $to = $has_custom_range ? $requested_to : ($show_all ? '' : $default_to);
    $scope = $show_all ? 'all' : ($has_custom_range ? 'custom' : 'default');
    $date_created = '';
    $url_args = [];

    if (!$show_all) {
        if ($from !== '' && $to !== '') {
            $date_created = $from . '...' . $to;
        } elseif ($from !== '') {
            $date_created = '>=' . $from;
        } elseif ($to !== '') {
            $date_created = '<=' . $to;
        }
    }

    if ($scope === 'custom') {
        if ($from !== '') {
            $url_args['tipax_date_from'] = $from;
        }
        if ($to !== '') {
            $url_args['tipax_date_to'] = $to;
        }
    } elseif ($scope === 'all') {
        $url_args['tipax_date_scope'] = 'all';
    }

    if ($order_view === 'other') {
        $url_args['tipax_order_view'] = 'other';
    }

    if ($scope === 'all') {
        $summary = 'همه سفارشات';
        $description = $order_view === 'other'
            ? 'همه سفارش‌هایی که روش ارسال آن‌ها تیپاکس نیست نمایش داده می‌شوند.'
            : 'همه سفارش‌هایی که روش ارسال تیپاکس دارند نمایش داده می‌شوند.';
    } elseif ($scope === 'custom' && $from !== '' && $to !== '') {
        $summary = 'از ' . $from . ' تا ' . $to;
        $description = $order_view === 'other'
            ? 'سفارش‌های غیرتیپاکسی در بازه انتخاب‌شده نمایش داده می‌شوند.'
            : 'سفارش‌های دارای روش ارسال تیپاکس در بازه انتخاب‌شده نمایش داده می‌شوند.';
    } elseif ($scope === 'custom' && $from !== '') {
        $summary = 'از ' . $from;
        $description = $order_view === 'other'
            ? 'سفارش‌های غیرتیپاکسی از تاریخ انتخاب‌شده به بعد نمایش داده می‌شوند.'
            : 'سفارش‌های دارای روش ارسال تیپاکس از تاریخ انتخاب‌شده به بعد نمایش داده می‌شوند.';
    } elseif ($scope === 'custom' && $to !== '') {
        $summary = 'تا ' . $to;
        $description = $order_view === 'other'
            ? 'سفارش‌های غیرتیپاکسی تا تاریخ انتخاب‌شده نمایش داده می‌شوند.'
            : 'سفارش‌های دارای روش ارسال تیپاکس تا تاریخ انتخاب‌شده نمایش داده می‌شوند.';
    } else {
        $summary = '۱۰ روز اخیر';
        $description = $order_view === 'other'
            ? 'سفارش‌های ۱۰ روز اخیر که روش ارسال آن‌ها تیپاکس نیست نمایش داده می‌شوند.'
            : 'به‌صورت پیش‌فرض فقط سفارش‌های تیپاکسی ۱۰ روز اخیر نمایش داده می‌شوند؛ با فیلتر تاریخ می‌توانید سفارش‌های قدیمی‌تر را هم ببینید.';
    }

    return [
        'scope' => $scope,
        'from' => $from,
        'to' => $to,
        'date_created' => $date_created,
        'url_args' => $url_args,
        'summary' => $summary,
        'description' => $description,
    ];
}

function tipax_render_orders_date_filter(array $date_filter, $order_view = 'tipax')
{
    $order_view = $order_view === 'other' ? 'other' : 'tipax';
    $view_args = $order_view === 'other' ? ['tipax_order_view' => 'other'] : [];
    $default_url = add_query_arg(array_merge([
        'page' => 'tipax-orders',
    ], $view_args), admin_url('admin.php'));
    $all_url = add_query_arg(array_merge([
        'page' => 'tipax-orders',
        'tipax_date_scope' => 'all',
    ], $view_args), admin_url('admin.php'));
    ?>
    <form class="tipax-orders-date-filter" method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>">
        <input type="hidden" name="page" value="tipax-orders">
        <?php if ($order_view === 'other') : ?>
            <input type="hidden" name="tipax_order_view" value="other">
        <?php endif; ?>
        <div class="tipax-orders-date-filter-copy">
            <span>فیلتر تاریخ سفارشات</span>
            <strong><?php echo esc_html($date_filter['summary']); ?></strong>
        </div>
        <div class="tipax-orders-date-fields">
            <label for="tipax_date_from">
                <span>از تاریخ</span>
                <input type="date" id="tipax_date_from" name="tipax_date_from" value="<?php echo esc_attr($date_filter['from']); ?>" dir="ltr">
            </label>
            <label for="tipax_date_to">
                <span>تا تاریخ</span>
                <input type="date" id="tipax_date_to" name="tipax_date_to" value="<?php echo esc_attr($date_filter['to']); ?>" dir="ltr">
            </label>
        </div>
        <div class="tipax-orders-date-actions">
            <button type="submit" class="button button-primary">اعمال فیلتر</button>
            <a href="<?php echo esc_url($default_url); ?>" class="button<?php echo $date_filter['scope'] === 'default' ? ' is-active' : ''; ?>">۱۰ روز اخیر</a>
            <a href="<?php echo esc_url($all_url); ?>" class="button<?php echo $date_filter['scope'] === 'all' ? ' is-active' : ''; ?>">همه تاریخ‌ها</a>
        </div>
    </form>
    <?php
}

function tipax_orders_page_v2()
{
    tipax_admin_tabs('tipax-orders');

    $paged = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
    $per_page = 10;
    $order_view = tipax_get_orders_view();
    $is_other_view = $order_view === 'other';
    $date_filter = tipax_get_orders_date_filter($order_view);
    $order_query = new Tipax_Order_Query();
    $query_result = $order_query->get_orders([
        'view' => $order_view,
        'statuses' => ['processing', 'completed', 'on-hold', 'pending'],
        'page' => $paged,
        'per_page' => $per_page,
        'from' => $date_filter['from'],
        'to' => $date_filter['to'],
    ]);
    $listed_orders = array_values(array_filter($query_result['orders'], static function ($order) use ($is_other_view) {
        if (!$order instanceof WC_Order) {
            return false;
        }

        $has_tipax_method = $order->has_shipping_method('tipax_shipping_method');
        return $is_other_view ? !$has_tipax_method : $has_tipax_method;
    }));
    $total_orders = (int) $query_result['total'];
    $total_pages = (int) $query_result['max_num_pages'];
    $paged = (int) $query_result['page'];
    $default_tracking_batch_size = (int) apply_filters('tipax_tracking_batch_size', 20);
    $last_tracking_sync_report = get_option('tipax_tracking_last_sync_report', []);
    $label_manager = function_exists('tipax_label_manager') ? tipax_label_manager() : null;
    $switch_url_args = array_merge(['page' => 'tipax-orders'], $date_filter['url_args']);
    unset($switch_url_args['tipax_order_view'], $switch_url_args['paged']);
    if (!$is_other_view) {
        $switch_url_args['tipax_order_view'] = 'other';
    }
    $switch_url = add_query_arg($switch_url_args, admin_url('admin.php'));
    $switch_label = $is_other_view ? 'بازگشت به سفارشات تیپاکس' : 'مشاهده سفارشات غیرتیپاکسی';

    echo '<div class="tipax-orders-toolbar">';
    echo '<div class="tipax-orders-toolbar-actions">';
    if (!$is_other_view) {
        echo '<button type="button" class="button button-primary tipax-batch-update-page">' . esc_html(tipax_text('orders_batch_page')) . '</button>';
        echo '<button type="button" class="button tipax-run-due-batch" data-limit="' . esc_attr($default_tracking_batch_size) . '">' . esc_html(tipax_text('orders_batch_auto')) . ' ' . esc_html($default_tracking_batch_size) . ' ' . esc_html(tipax_text('orders_batch_auto_suffix')) . '</button>';
        echo '<button type="button" class="button tipax-print-selected-labels" data-tipax-print-selected-labels data-selected-count="0" disabled>چاپ لیبل‌های انتخاب‌شده</button>';
    }
    echo '<a class="button tipax-orders-view-switch' . ($is_other_view ? ' is-return' : '') . '" href="' . esc_url($switch_url) . '">' . esc_html($switch_label) . '</a>';
    echo '</div>';
    echo '<div class="tipax-orders-toolbar-meta">';
    if ($is_other_view) {
        echo '<span>این نما فقط برای مشاهده سفارش‌هایی است که روش ارسال آن‌ها تیپاکس نیست.</span>';
    } else {
        echo '<span>' . esc_html(tipax_text('orders_cron_info')) . '</span>';
    }

    if (!$is_other_view && !empty($last_tracking_sync_report['ran_at'])) {
        $last_sync_timestamp = strtotime((string) $last_tracking_sync_report['ran_at']);
        $last_sync_display = $last_sync_timestamp ? wp_date('Y/m/d H:i', $last_sync_timestamp) : (string) $last_tracking_sync_report['ran_at'];
        echo '<span>' . esc_html(tipax_text('orders_last_run')) . ': <strong>' . esc_html($last_sync_display) . '</strong></span>';
        echo '<span>' . esc_html(tipax_text('orders_processed')) . ': <strong>' . esc_html((int) ($last_tracking_sync_report['processed'] ?? 0)) . '</strong></span>';
        echo '<span>' . esc_html(tipax_text('orders_updated')) . ': <strong>' . esc_html((int) ($last_tracking_sync_report['updated'] ?? 0)) . '</strong></span>';
        echo '<span>' . esc_html(tipax_text('orders_skipped')) . ': <strong>' . esc_html((int) ($last_tracking_sync_report['skipped'] ?? 0)) . '</strong></span>';
        echo '<span>' . esc_html(tipax_text('orders_failed')) . ': <strong>' . esc_html((int) ($last_tracking_sync_report['failed'] ?? 0)) . '</strong></span>';
    }

    echo '</div>';
    echo '</div>';
    if (!$is_other_view) {
        echo '<div id="tipax-batch-update-feedback" class="tipax-batch-feedback" hidden></div>';
    }

    echo '<div class="tipax-action-panel tipax-orders-panel' . ($is_other_view ? ' is-other-orders' : '') . '">';
    echo '<div class="tipax-action-icon">' . tipax_admin_icon($is_other_view ? 'media-text' : 'archive') . '</div>';
    echo '<div class="tipax-action-content">';
    echo '<h2>' . ($is_other_view ? 'سایر سفارشات ووکامرس' : 'سفارشات تیپاکس') . '</h2>';
    echo '<p>' . esc_html($date_filter['description']) . '</p>';
    echo '</div>';
    echo '<div class="tipax-stats">';
    echo '<span><strong>' . esc_html($total_orders) . '</strong> سفارش</span>';
    echo '<span><strong>' . esc_html($paged) . '</strong> صفحه فعلی</span>';
    echo '<span><strong>' . esc_html($date_filter['summary']) . '</strong> بازه نمایش</span>';
    echo '</div>';
    echo '</div>';

    tipax_render_orders_date_filter($date_filter, $order_view);

    $selection_header = $is_other_view
        ? '<span aria-hidden="true">—</span>'
        : '<input type="checkbox" data-tipax-label-select-all aria-label="انتخاب همه سفارش‌های قابل چاپ">';
    echo '<table class="widefat striped tipax-data-table tipax-orders-table"><thead><tr>
        <th class="tipax-label-select-column">' . $selection_header . '</th>
        <th>شماره سفارش</th>
        <th>تاریخ</th>
        <th>نام مشتری</th>
        <th>محصولات</th>
        <th>روش ارسال</th>
        <th>کد پیگیری</th>
        <th>کد قرارداد</th>
        <th>وضعیت</th>
        <th>اقدامات</th>
    </tr></thead><tbody>';

    if (empty($listed_orders)) {
        $empty_message = $is_other_view
            ? 'سفارش غیرتیپاکسی در این بازه یافت نشد.'
            : 'سفارش تیپاکسی در این بازه یافت نشد.';
        echo '<tr><td colspan="10">' . esc_html($empty_message) . '</td></tr>';
    }

    foreach ($listed_orders as $order) {
        $saved_status = $order->get_meta('_tipax_saved_status', true);
        $saved_status_id = (int) $order->get_meta('_tipax_saved_status_id', true);
        $is_cancelled_status = Tipax_Cancellation_Service::is_cancelled_status_id($saved_status_id);
        $label_codes = !$is_other_view && $label_manager
            ? $label_manager->get_order_tracking_codes($order)
            : [];
        $cancelled_codes = !$is_other_view && $label_manager
            ? $label_manager->get_order_cancelled_tracking_codes($order)
            : [];
        $cancellable_codes = $is_cancelled_status
            ? []
            : array_values(array_diff($label_codes, $cancelled_codes));
        $is_fully_cancelled = $is_cancelled_status || (!empty($label_codes) && empty($cancellable_codes));
        $tracking_code = implode('، ', $label_codes);
        $status_update_allowed = Tipax_Tracking_Service::should_update_status_id($saved_status_id);
        $saved_contract = $order->get_meta('_tipax_saved_contract', true);
        $registration_state = $order->get_meta('_tipax_registration_state', true);
        $registration_reason = $order->get_meta('_tipax_registration_reason', true);
        $registration_label = tipax_get_order_tipax_registration_state_label($registration_state, $registration_reason);
        $billing_name = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
        if ($billing_name === '') {
            $billing_name = trim($order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name());
        }

        $product_titles = [];
        foreach ($order->get_items() as $item) {
            $product_titles[] = $item->get_name() . ' × ' . $item->get_quantity();
        }
        $products_string = implode(', ', $product_titles);
        $shipping_titles = [];
        foreach ($order->get_shipping_methods() as $shipping_item) {
            $shipping_title = trim((string) $shipping_item->get_method_title());
            if ($shipping_title !== '') {
                $shipping_titles[] = $shipping_title;
            }
        }
        $shipping_title = !empty($shipping_titles) ? implode('، ', array_unique($shipping_titles)) : 'بدون روش ارسال';
        $has_printable_label = !$is_other_view && !empty($cancellable_codes);
        $label_print_url = $has_printable_label ? $label_manager->get_print_url([$order->get_id()]) : '';
        $date_created = $order->get_date_created();
        $order_date = $date_created ? $date_created->date_i18n('Y-m-d H:i') : '—';
        $edit_order_url = method_exists($order, 'get_edit_order_url') ? (string) $order->get_edit_order_url() : '';
        $native_status = wc_get_order_status_name($order->get_status());

        echo '<tr data-tipax-order-row="' . esc_attr($order->get_id()) . '" class="' . ($is_other_view ? 'tipax-order-row-other' : 'tipax-order-row-tipax') . '">';
        echo '<td class="tipax-label-select-column">';
        if ($is_other_view) {
            echo '<span aria-hidden="true">—</span>';
        } else {
            echo '<input type="checkbox" value="' . esc_attr($order->get_id()) . '" data-tipax-label-order aria-label="انتخاب سفارش ' . esc_attr($order->get_id()) . '"'
                . ($has_printable_label ? '' : ' disabled title="این سفارش کد رهگیری ندارد"') . '>';
        }
        echo '</td>';
        echo '<td>';
        if ($edit_order_url !== '') {
            echo '<a href="' . esc_url($edit_order_url) . '">#' . esc_html($order->get_order_number()) . '</a>';
        } else {
            echo '#' . esc_html($order->get_order_number());
        }
        echo '</td>';
        echo '<td>' . esc_html($order_date) . '</td>';
        echo '<td>' . esc_html($billing_name !== '' ? $billing_name : '—') . '</td>';
        echo '<td>' . esc_html($products_string) . '</td>';
        echo '<td>' . esc_html($shipping_title) . '</td>';
        echo '<td>' . esc_html(!$is_other_view && $tracking_code !== '' ? $tracking_code : '—') . '</td>';
        echo '<td>' . esc_html(!$is_other_view && $saved_contract !== '' ? $saved_contract : '—') . '</td>';
        $display_status = $is_other_view
            ? $native_status
            : ($is_fully_cancelled
                ? 'ابطال شده'
                : (empty($tracking_code) && $registration_label !== '' ? $registration_label : ($saved_status ?: $native_status)));
        echo '<td><span>' . esc_html($display_status) . '</span></td>';
        echo '<td>';
        if ($is_other_view) {
            if ($edit_order_url !== '') {
                echo '<a class="button" href="' . esc_url($edit_order_url) . '">مشاهده سفارش</a>';
            }
        } elseif ($is_fully_cancelled) {
            echo '<span class="tipax-cancelled-order-badge">ابطال شده</span>';
        } elseif (empty($tracking_code)) {
            $action_label = $registration_state === 'offline_pending'
                ? 'ثبت در تیپاکس'
                : ($registration_label !== '' ? 'ثبت / تعیین تکلیف در تیپاکس' : 'ثبت مجدد در تیپاکس');
            echo '<button class="button tipax-register-order" data-order-id="' . esc_attr($order->get_id()) . '">' . esc_html($action_label) . '</button>';
        } else {
            if ($status_update_allowed) {
                echo '<button class="button tipax-update-status" data-order-id="' . esc_attr($order->get_id()) . '">بروزرسانی وضعیت</button>';
            } else {
                echo '<span class="tipax-tracking-stopped-badge">رهگیری پایان‌یافته</span>';
            }
            $cancelLabel = !empty($cancelled_codes) ? 'ابطال مرسولات باقی‌مانده' : 'ابطال مرسوله';
            echo '<button type="button" class="button tipax-cancel-order" data-order-id="' . esc_attr($order->get_id()) . '" data-tracking-count="' . esc_attr(count($cancellable_codes)) . '">' . esc_html($cancelLabel) . '</button>';
        }
        if ($has_printable_label) {
            $label_count_text = count($cancellable_codes) > 1 ? 'چاپ ' . count($cancellable_codes) . ' لیبل' : 'چاپ لیبل';
            echo '<a class="button tipax-label-row-action" href="' . esc_url($label_print_url) . '" target="_blank" rel="noopener">' . esc_html($label_count_text) . '</a>';
        }
        echo '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
    echo '<div class="tipax-pagination">';
    $pagination_args = array_merge([
        'page' => 'tipax-orders',
    ], $date_filter['url_args']);
    for ($i = 1; $i <= $total_pages; $i++) {
        $pagination_url = add_query_arg(
            array_merge($pagination_args, ['paged' => $i]),
            admin_url('admin.php')
        );
        echo '<a href="' . esc_url($pagination_url) . '" class="button' . ($i === $paged ? ' button-primary' : '') . '">' . $i . '</a> ';
    }
    echo '</div>';

    ?>
    <div id="tipax_order_retry_modal" class="tipax-modal" hidden>
        <div class="tipax-modal-backdrop" data-tipax-close-order-retry-modal></div>
        <div class="tipax-modal-dialog tipax-order-retry-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="tipax-order-retry-title">
            <div class="tipax-modal-head">
                <div class="tipax-modal-copy">
                    <h3 id="tipax-order-retry-title">ثبت مجدد سفارش در تیپاکس</h3>
                    <p>قبل از ثبت مجدد، مبدا، وزن، بسته‌بندی و در صورت نیاز ابعاد را بررسی کنید. استعلام قیمت و ثبت نهایی هر دو با همین مقادیر انجام می‌شوند.</p>
                </div>
                <button type="button" class="tipax-modal-close" data-tipax-close-order-retry-modal aria-label="بستن">×</button>
            </div>

            <div class="tipax-order-retry-summary">
                <span><strong>سفارش:</strong> <span id="tipax_retry_order_label">-</span></span>
                <span><strong>مشتری:</strong> <span id="tipax_retry_customer_name">-</span></span>
                <span><strong>مقصد:</strong> <span id="tipax_retry_destination_text">-</span></span>
            </div>

            <form id="tipax_order_retry_form" class="tipax-modal-form">
                <input type="hidden" id="tipax_retry_order_id" name="order_id" value="">

                <div class="tipax-order-retry-grid">
                    <div class="tipax-order-retry-field is-full">
                        <label for="tipax_retry_origin_address_id">آدرس مبدا</label>
                        <select id="tipax_retry_origin_address_id" name="origin_address_id" required></select>
                    </div>

                    <div class="tipax-order-retry-field">
                        <label for="tipax_retry_weight">وزن (کیلوگرم)</label>
                        <input type="number" step="0.01" min="0.01" id="tipax_retry_weight" name="weight" required inputmode="decimal">
                    </div>

                    <div class="tipax-order-retry-field">
                        <label for="tipax_retry_packing_id">نوع بسته‌بندی</label>
                        <select id="tipax_retry_packing_id" name="packing_id"></select>
                        <p id="tipax_retry_packing_hint" class="description">در حالت بدون بسته‌بندی، ابعاد واقعی مرسوله را وارد کنید؛ سگمنت معتبر بر اساس وزن، ابعاد و ارزش مرسوله انتخاب می‌شود.</p>
                    </div>

                    <div id="tipax_retry_dimensions" class="tipax-order-retry-dimensions is-full">
                        <div class="tipax-order-retry-grid tipax-order-retry-grid--compact">
                            <div class="tipax-order-retry-field">
                                <label for="tipax_retry_length">طول (سانتی‌متر)</label>
                                <input type="number" step="0.1" min="0.1" id="tipax_retry_length" name="length" inputmode="decimal">
                            </div>
                            <div class="tipax-order-retry-field">
                                <label for="tipax_retry_width">عرض (سانتی‌متر)</label>
                                <input type="number" step="0.1" min="0.1" id="tipax_retry_width" name="width" inputmode="decimal">
                            </div>
                            <div class="tipax-order-retry-field">
                                <label for="tipax_retry_height">ارتفاع (سانتی‌متر)</label>
                                <input type="number" step="0.1" min="0.1" id="tipax_retry_height" name="height" inputmode="decimal">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tipax-order-retry-actions">
                    <button type="button" class="button" id="tipax_retry_preview_pricing">استعلام قیمت</button>
                    <button type="submit" class="button button-primary" id="tipax_retry_submit_order">ثبت در تیپاکس</button>
                </div>
            </form>

            <div id="tipax_order_retry_result" class="tipax-order-retry-result-area">
                <div class="tipax-order-retry-placeholder">
                    <p>پس از بارگذاری اطلاعات سفارش، در صورت نیاز مقادیر را اصلاح کنید و قبل از ثبت، استعلام قیمت بگیرید.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        const orderRetryModal = $('#tipax_order_retry_modal');
        if (orderRetryModal.length && !orderRetryModal.parent().is('body')) {
            orderRetryModal.appendTo('body');
        }
        const orderRetryForm = $('#tipax_order_retry_form');
        const orderRetryResult = $('#tipax_order_retry_result');
        const previewButton = $('#tipax_retry_preview_pricing');
        const submitButton = $('#tipax_retry_submit_order');
        const packingSelect = $('#tipax_retry_packing_id');
        const dimensionsWrap = $('#tipax_retry_dimensions');
        const lengthInput = $('#tipax_retry_length');
        const widthInput = $('#tipax_retry_width');
        const heightInput = $('#tipax_retry_height');
        const packingHint = $('#tipax_retry_packing_hint');
        let activeRetryButton = null;
        let allPackingOptions = [];
        let currentPackingIndex = {};
        let usingCatalogDimensions = false;

        function setRetryResult(html) {
            orderRetryResult.html(html);
        }

        function setRetryPlaceholder(message) {
            setRetryResult('<div class="tipax-order-retry-placeholder"><p>' + message + '</p></div>');
        }

        function setRetryLoading(message) {
            setRetryResult('<div class="tipax-order-retry-loading"><span></span><p>' + message + '</p></div>');
        }

        function restoreActiveButton() {
            if (!activeRetryButton || !activeRetryButton.length) {
                return;
            }

            activeRetryButton.prop('disabled', false).text(activeRetryButton.data('default-label'));
            activeRetryButton = null;
        }

        function resetRetryButtons() {
            previewButton.prop('disabled', false).text('استعلام قیمت');
            submitButton.prop('disabled', false).text('ثبت در تیپاکس');
        }

        function openRetryModal() {
            orderRetryModal.removeAttr('hidden');
            $('body').addClass('tipax-modal-open');
        }

        function closeRetryModal() {
            orderRetryModal.attr('hidden', 'hidden');
            $('body').removeClass('tipax-modal-open');
            orderRetryForm.get(0).reset();
            orderRetryForm
                .removeData('default-length')
                .removeData('default-width')
                .removeData('default-height')
                .removeData('manual-length')
                .removeData('manual-width')
                .removeData('manual-height');
            allPackingOptions = [];
            currentPackingIndex = {};
            usingCatalogDimensions = false;
            resetRetryButtons();
            restoreActiveButton();
            setRetryPlaceholder('پس از بارگذاری اطلاعات سفارش، در صورت نیاز مقادیر را اصلاح کنید و قبل از ثبت، استعلام قیمت بگیرید.');
        }

        function fillSelect($select, options, selectedValue) {
            $select.empty();
            $.each(options || [], function(index, option) {
                const value = option && option.value !== undefined ? String(option.value) : '';
                const label = option && option.label !== undefined ? option.label : value;
                $select.append($('<option></option>').val(value).text(label));
            });

            if (selectedValue !== undefined && selectedValue !== null) {
                $select.val(String(selectedValue));
            }
        }

        function parseWeightValue(value) {
            if (value === undefined || value === null || value === '') {
                return 0;
            }

            const normalized = String(value).replace(',', '.').trim();
            const parsed = parseFloat(normalized);
            return Number.isFinite(parsed) ? parsed : 0;
        }

        function packingMatchesWeight(packing, weight) {
            if (!packing || !weight || weight <= 0) {
                return true;
            }

            const minWeight = parseWeightValue(packing.minWeight);
            const maxWeight = parseWeightValue(packing.maxWeight);

            if (minWeight > 0 && weight < minWeight) {
                return false;
            }

            if (maxWeight > 0 && weight > maxWeight) {
                return false;
            }

            return true;
        }

        function refreshRetryPackingOptions(preferredValue) {
            const currentValue = preferredValue !== undefined ? String(preferredValue || '') : String(packingSelect.val() || '');
            const weight = parseWeightValue($('#tipax_retry_weight').val());
            const filteredOptions = [];
            let hasPreferredValue = false;

            $.each(allPackingOptions || [], function(index, option) {
                const value = option && option.value !== undefined ? String(option.value) : '';
                const packing = value !== '' ? currentPackingIndex[value] : null;

                if (value !== '' && !packingMatchesWeight(packing, weight)) {
                    return;
                }

                filteredOptions.push(option);
                if (value === currentValue) {
                    hasPreferredValue = true;
                }
            });

            fillSelect(packingSelect, filteredOptions, hasPreferredValue ? currentValue : '');
        }

        function updateDimensionsVisibility() {
            const packingId = packingSelect.val();
            const selectedPacking = packingId && currentPackingIndex[packingId] ? currentPackingIndex[packingId] : null;
            const showDimensions = !selectedPacking;

            lengthInput.prop('required', showDimensions);
            widthInput.prop('required', showDimensions);
            heightInput.prop('required', showDimensions);

            if (showDimensions) {
                if (usingCatalogDimensions) {
                    lengthInput.val(orderRetryForm.data('manual-length') || orderRetryForm.data('default-length') || '');
                    widthInput.val(orderRetryForm.data('manual-width') || orderRetryForm.data('default-width') || '');
                    heightInput.val(orderRetryForm.data('manual-height') || orderRetryForm.data('default-height') || '');
                }

                usingCatalogDimensions = false;
                dimensionsWrap.removeAttr('hidden');
                packingHint.text('در حالت بدون بسته‌بندی، ابعاد واقعی مرسوله را وارد کنید؛ سگمنت معتبر بر اساس وزن، ابعاد و ارزش مرسوله انتخاب می‌شود.');
                return;
            }

            if (!usingCatalogDimensions) {
                orderRetryForm.data('manual-length', lengthInput.val());
                orderRetryForm.data('manual-width', widthInput.val());
                orderRetryForm.data('manual-height', heightInput.val());
            }

            const packingLength = parseWeightValue(selectedPacking.length);
            const packingWidth = parseWeightValue(selectedPacking.width);
            const packingHeight = parseWeightValue(selectedPacking.height);

            lengthInput.val(packingLength > 0 ? packingLength : '');
            widthInput.val(packingWidth > 0 ? packingWidth : '');
            heightInput.val(packingHeight > 0 ? packingHeight : '');
            usingCatalogDimensions = true;
            dimensionsWrap.attr('hidden', 'hidden');
            packingHint.text(
                'ابعاد این بسته از کاتالوگ تیپاکس به‌صورت خودکار و غیرقابل ویرایش اعمال می‌شود: ' +
                packingLength + ' × ' + packingWidth + ' × ' + packingHeight + ' سانتی‌متر.'
            );
        }

        function collectRetryData() {
            return {
                origin_address_id: $('#tipax_retry_origin_address_id').val(),
                weight: $('#tipax_retry_weight').val(),
                packing_id: packingSelect.val(),
                length: lengthInput.val(),
                width: widthInput.val(),
                height: heightInput.val()
            };
        }

        function validateRetryForm() {
            updateDimensionsVisibility();
            const formElement = orderRetryForm.get(0);
            if (!formElement) {
                return false;
            }

            return typeof formElement.reportValidity !== 'function' ? true : formElement.reportValidity();
        }

        function handleRetryError(response, fallbackMessage) {
            if (response && response.data && response.data.html) {
                setRetryResult(response.data.html);
                return;
            }

            setRetryResult('<div class="tipax-order-retry-result is-error"><div class="tipax-order-retry-result-head"><h4>' + fallbackMessage + '</h4></div></div>');
        }

        function setBatchFeedback(message, isError) {
            const feedback = $('#tipax-batch-update-feedback');
            if (!feedback.length) {
                return;
            }

            feedback
                .removeAttr('hidden')
                .removeClass('is-error is-success')
                .addClass(isError ? 'is-error' : 'is-success')
                .text(message);
        }

        function withBatchRequest(button, requestData) {
            const originalText = button.data('original-text') || button.text();
            button.data('original-text', originalText);
            button.prop('disabled', true).text("<?php echo esc_js(tipax_text('batch_running')); ?>");

            $.post(ajaxurl, requestData)
                .done(function(response) {
                    if (!response || !response.success) {
                        const errorMessage = response && response.data && response.data.message
                            ? response.data.message
                            : "<?php echo esc_js(tipax_text('batch_error')); ?>";

                        setBatchFeedback(errorMessage, true);
                        button.prop('disabled', false).text(originalText);
                        return;
                    }

                    const message = response && response.data && response.data.message
                        ? response.data.message
                        : "<?php echo esc_js(tipax_text('batch_success')); ?>";

                    setBatchFeedback(message, false);
                    window.setTimeout(function() {
                        location.reload();
                    }, 900);
                })
                .fail(function(xhr) {
                    const responseMessage = xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message
                        ? xhr.responseJSON.data.message
                        : "<?php echo esc_js(tipax_text('batch_error')); ?>";

                    setBatchFeedback(responseMessage, true);
                    button.prop('disabled', false).text(originalText);
                });
        }

        $('.tipax-batch-update-page').on('click', function() {
            const button = $(this);
            const orderIds = $('.tipax-update-status').map(function() {
                return $(this).data('order-id');
            }).get();

            if (!orderIds.length) {
                setBatchFeedback("<?php echo esc_js(tipax_text('batch_no_orders')); ?>", true);
                return;
            }

            withBatchRequest(button, {
                action: 'tipax_batch_update_order_statuses',
                mode: 'page',
                order_ids: orderIds,
                _ajax_nonce: '<?php echo wp_create_nonce('tipax_batch_update_order_statuses'); ?>'
            });
        });

        $('.tipax-run-due-batch').on('click', function() {
            const button = $(this);
            withBatchRequest(button, {
                action: 'tipax_batch_update_order_statuses',
                mode: 'due',
                limit: button.data('limit') || 20,
                _ajax_nonce: '<?php echo wp_create_nonce('tipax_batch_update_order_statuses'); ?>'
            });
        });

        $('.tipax-update-status').click(function(e) {
            e.preventDefault();
            const button = $(this);
            const orderId = button.data('order-id');

            button.prop('disabled', true).text('در حال بروزرسانی...');

            $.post(ajaxurl, {
                action: 'tipax_update_order_status',
                order_id: orderId,
                _ajax_nonce: '<?php echo wp_create_nonce('tipax_update_order'); ?>'
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    button.text('خطا').prop('disabled', false);
                    alert(response.data);
                }
            });
        });

        $('.tipax-cancel-order').click(function(e) {
            e.preventDefault();
            const button = $(this);
            const orderId = button.data('order-id');
            const trackingCount = parseInt(button.data('tracking-count'), 10) || 1;
            const defaultLabel = button.text();
            const confirmationMessage = trackingCount > 1
                ? 'این عملیات ' + trackingCount + ' مرسوله ثبت‌شده این سفارش را در تیپاکس ابطال می‌کند. آیا مطمئن هستید؟'
                : 'این مرسوله در تیپاکس ابطال خواهد شد. آیا مطمئن هستید؟';

            if (!window.confirm(confirmationMessage)) {
                return;
            }

            button.prop('disabled', true).text('در حال ابطال...');

            $.post(ajaxurl, {
                action: 'tipax_cancel_order',
                order_id: orderId,
                _ajax_nonce: '<?php echo wp_create_nonce('tipax_cancel_order'); ?>'
            }).done(function(response) {
                const data = response && response.data ? response.data : {};
                const message = data.message || 'ابطال مرسوله در تیپاکس ناموفق بود.';

                window.alert(message);
                if (response && (response.success || data.partial || data.cancelled_count > 0 || data.already_cancelled)) {
                    location.reload();
                    return;
                }

                button.prop('disabled', false).text(defaultLabel);
            }).fail(function(xhr) {
                const data = xhr && xhr.responseJSON && xhr.responseJSON.data ? xhr.responseJSON.data : {};
                window.alert(data.message || 'خطا در ارتباط با سرور هنگام ابطال مرسوله.');
                button.prop('disabled', false).text(defaultLabel);
            });
        });

        $('.tipax-register-order').click(function(e) {
            e.preventDefault();
            activeRetryButton = $(this);
            activeRetryButton.data('default-label', activeRetryButton.text());
            activeRetryButton.prop('disabled', true).text('در حال آماده‌سازی...');

            const orderId = activeRetryButton.data('order-id');
            $('#tipax_retry_order_id').val(orderId);
            $('#tipax_retry_order_label').text('#' + orderId);
            $('#tipax_retry_customer_name').text('-');
            $('#tipax_retry_destination_text').text('-');
            resetRetryButtons();
            setRetryLoading('در حال بارگذاری اطلاعات سفارش...');
            openRetryModal();

            $.post(ajaxurl, {
                action: 'tipax_get_order_retry_modal_data',
                order_id: orderId,
                _ajax_nonce: '<?php echo wp_create_nonce('tipax_get_order_retry_modal_data'); ?>'
            }).done(function(response) {
                if (!response || !response.success || !response.data) {
                    handleRetryError(response, 'بارگذاری اطلاعات سفارش انجام نشد.');
                    return;
                }

                const data = response.data;
                allPackingOptions = data.packingOptions || [];
                currentPackingIndex = data.packingIndex || {};
                $('#tipax_retry_order_id').val(data.orderId || orderId);
                $('#tipax_retry_order_label').text(data.orderLabel || ('#' + orderId));
                $('#tipax_retry_customer_name').text(data.customerName || '-');
                $('#tipax_retry_destination_text').text(data.destinationText || '-');
                fillSelect($('#tipax_retry_origin_address_id'), data.addressOptions || [], data.originAddressId || '');
                $('#tipax_retry_weight').val(data.weight || '');
                refreshRetryPackingOptions(data.packingId || '');
                lengthInput.val(data.length || '');
                widthInput.val(data.width || '');
                heightInput.val(data.height || '');
                orderRetryForm.data('default-length', data.defaultLength || data.length || '');
                orderRetryForm.data('default-width', data.defaultWidth || data.width || '');
                orderRetryForm.data('default-height', data.defaultHeight || data.height || '');
                updateDimensionsVisibility();
                setRetryPlaceholder('در صورت نیاز اطلاعات را اصلاح کنید. می‌توانید قبل از ثبت نهایی، استعلام قیمت بگیرید.');
                restoreActiveButton();
            }).fail(function() {
                handleRetryError(null, 'بارگذاری اطلاعات سفارش انجام نشد.');
                restoreActiveButton();
            });
        });

        packingSelect.on('change', function() {
            updateDimensionsVisibility();
        });

        $('#tipax_retry_weight').on('input change', function() {
            refreshRetryPackingOptions();
            updateDimensionsVisibility();
        });

        previewButton.on('click', function() {
            if (!validateRetryForm()) {
                return;
            }

            const retryData = collectRetryData();
            previewButton.prop('disabled', true).text('در حال استعلام...');
            submitButton.prop('disabled', true);
            setRetryLoading('در حال استعلام قیمت از تیپاکس...');

            $.post(ajaxurl, {
                action: 'tipax_preview_order_retry_pricing',
                order_id: $('#tipax_retry_order_id').val(),
                retry_data: JSON.stringify(retryData),
                _ajax_nonce: '<?php echo wp_create_nonce('tipax_preview_order_retry_pricing'); ?>'
            }).done(function(response) {
                if (response && response.data && response.data.html) {
                    setRetryResult(response.data.html);
                } else {
                    handleRetryError(response, 'استعلام قیمت از تیپاکس ناموفق بود.');
                }
            }).fail(function() {
                handleRetryError(null, 'استعلام قیمت از تیپاکس ناموفق بود.');
            }).always(function() {
                previewButton.prop('disabled', false).text('استعلام قیمت');
                submitButton.prop('disabled', false).text('ثبت در تیپاکس');
            });
        });

        orderRetryForm.on('submit', function(event) {
            event.preventDefault();

            if (!validateRetryForm()) {
                return;
            }

            const retryData = collectRetryData();
            submitButton.prop('disabled', true).text('در حال ثبت...');
            previewButton.prop('disabled', true);
            setRetryLoading('در حال ثبت مجدد سفارش در تیپاکس...');

            $.post(ajaxurl, {
                action: 'tipax_register_order',
                order_id: $('#tipax_retry_order_id').val(),
                retry_data: JSON.stringify(retryData),
                _ajax_nonce: '<?php echo wp_create_nonce('tipax_register_order'); ?>'
            }).done(function(response) {
                if (response && response.data && response.data.html) {
                    setRetryResult(response.data.html);
                } else {
                    handleRetryError(response, 'ثبت سفارش در تیپاکس ناموفق بود.');
                }

                if (response && response.success) {
                    previewButton.prop('disabled', true);
                    submitButton.prop('disabled', true).text('ثبت شد');
                } else {
                    previewButton.prop('disabled', false);
                    submitButton.prop('disabled', false).text('ثبت در تیپاکس');
                }
            }).fail(function() {
                handleRetryError(null, 'ثبت سفارش در تیپاکس ناموفق بود.');
                previewButton.prop('disabled', false);
                submitButton.prop('disabled', false).text('ثبت در تیپاکس');
            });
        });

        $(document).on('click', '[data-tipax-close-order-retry-modal]', function() {
            closeRetryModal();
        });

        $(document).on('keydown', function(event) {
            if (event.key === 'Escape' && !orderRetryModal.is('[hidden]')) {
                closeRetryModal();
            }
        });

        $(document).on('click', '.tipax-reload-orders-page', function() {
            location.reload();
        });
    });
    </script>
    <?php
}

function tipax_update_order_status($order_id) {
    $tracking_module = new Tipax_Tracking_Service();
    $result = $tracking_module->update_order_status($order_id, false);

    if (!$result['success']) {
        if (isset($result['error_code']) && $result['error_code'] === 'invalid_order') {
            return;
        }

        if (isset($result['error_code']) && $result['error_code'] === 'missing_tracking_code') {
            echo '<div class="notice notice-error"><p>کد پیگیری برای این سفارش یافت نشد.</p></div>';
            return;
        }

        echo '<div class="notice notice-error"><p>دریافت وضعیت سفارش از تیپاکس با خطا مواجه شد.</p></div>';
        return;
    }

    if (!empty($result['skipped'])) {
        echo '<div class="notice notice-info"><p>' . esc_html($result['message']) . '</p></div>';
        return;
    }

    echo '<div class="notice notice-success"><p>وضعیت سفارش بروزرسانی شد: ' . esc_html($result['status']) . '</p></div>';
}
add_action('wp_ajax_tipax_update_order_status', 'tipax_ajax_update_order_status');

function tipax_ajax_update_order_status() {
    check_ajax_referer('tipax_update_order');

    $order_id = absint($_POST['order_id'] ?? 0);
    if (!$order_id) {
        wp_send_json_error('شناسه سفارش نامعتبر است.');
    }

    $order = wc_get_order($order_id);
    if (!$order) {
        wp_send_json_error('سفارش یافت نشد.');
    }

    $tracking_module = new Tipax_Tracking_Service();
    $result = $tracking_module->update_order_status($order_id, true);

    if (!$result['success']) {
        if (isset($result['error_code']) && $result['error_code'] === 'missing_tracking_code') {
            wp_send_json_error('کد پیگیری موجود نیست.');
        }

        wp_send_json_error('پاسخ نامعتبر از تیپاکس.');
    }

    if (!empty($result['skipped'])) {
        wp_send_json_success([
            'skipped' => true,
            'status' => $result['status'],
            'status_id' => $result['status_id'],
            'contract' => $result['contract'],
            'message' => $result['message'],
        ]);
    }

    wp_send_json_success([
        'status' => $result['status'],
        'status_id' => $result['status_id'],
        'contract' => $result['contract'],
    ]);
}

add_action('wp_ajax_tipax_cancel_order', 'tipax_ajax_cancel_order');

function tipax_ajax_cancel_order()
{
    check_ajax_referer('tipax_cancel_order');

    if (
        !current_user_can('manage_woocommerce')
        && !current_user_can('edit_shop_orders')
        && !current_user_can('manage_options')
    ) {
        wp_send_json_error(['message' => 'شما اجازه ابطال مرسوله را ندارید.']);
    }

    $orderId = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
    $order = $orderId > 0 ? wc_get_order($orderId) : false;
    if (!$order || !$order->has_shipping_method('tipax_shipping_method')) {
        wp_send_json_error(['message' => 'سفارش معتبر نیست یا روش ارسال آن تیپاکس نیست.']);
    }

    $service = new Tipax_Cancellation_Service();
    $result = $service->cancel_order($order);
    $response = [
        'message' => $result['message'],
        'status' => $result['status'],
        'partial' => !empty($result['partial']),
        'already_cancelled' => !empty($result['already_cancelled']),
        'cancelled_count' => isset($result['cancelled_now']) ? count($result['cancelled_now']) : 0,
        'failed_count' => !empty($result['failed']) ? count($result['failed']) : 0,
        'cancelled_codes' => $result['cancelled_codes'] ?? [],
        'failed' => $result['failed'] ?? [],
    ];

    if ($result['success']) {
        wp_send_json_success($response);
    }

    wp_send_json_error($response);
}

add_action('wp_ajax_tipax_register_order', 'tipax_ajax_register_order');

function tipax_ajax_register_order() {
    check_ajax_referer('tipax_register_order');

    $order_id = absint($_POST['order_id'] ?? 0);
    if (!$order_id) {
        wp_send_json_error('شناسه سفارش نامعتبر است.');
    }

    $order = wc_get_order($order_id);
    if (!$order || !$order->has_shipping_method('tipax_shipping_method')) {
        wp_send_json_error('سفارش معتبر نیست یا مربوط به تیپاکس نیست.');
    }

    unset($_COOKIE["tipaxOrder_".$order_id]);
    $shipment_module = new Tipax_Order_Service();
    $result = $shipment_module->retry_order_registration($order_id);

    if ($result['success']) {
        wp_send_json_success(implode("\n", $result['messages']));
    } else {
        wp_send_json_error(implode("\n", $result['messages']));
    }
}

add_action('wp_ajax_tipax_get_order_retry_modal_data', 'tipax_ajax_get_order_retry_modal_data');

function tipax_ajax_get_order_retry_modal_data()
{
    check_ajax_referer('tipax_get_order_retry_modal_data');

    $order_id = absint($_POST['order_id'] ?? 0);
    if (!$order_id) {
        wp_send_json_error([
            'html' => tipax_render_order_retry_result_panel([
                'type' => 'error',
                'badge' => 'ثبت مجدد',
                'title' => 'شناسه سفارش معتبر نیست',
                'messages' => ['شناسه سفارش نامعتبر است.'],
            ]),
        ]);
    }

    $shipment_module = new Tipax_Order_Service();
    $result = $shipment_module->get_retry_order_modal_data($order_id);

    if (empty($result['success']) || empty($result['data'])) {
        wp_send_json_error([
            'html' => tipax_render_order_retry_result_panel([
                'type' => 'error',
                'badge' => 'ثبت مجدد',
                'title' => 'بارگذاری اطلاعات سفارش انجام نشد',
                'messages' => $result['messages'] ?? ['بارگذاری اطلاعات سفارش انجام نشد.'],
            ]),
        ]);
    }

    wp_send_json_success($result['data']);
}

add_action('wp_ajax_tipax_preview_order_retry_pricing', 'tipax_ajax_preview_order_retry_pricing');

function tipax_ajax_preview_order_retry_pricing()
{
    check_ajax_referer('tipax_preview_order_retry_pricing');

    $order_id = absint($_POST['order_id'] ?? 0);
    $retry_data = isset($_POST['retry_data']) ? json_decode(wp_unslash($_POST['retry_data']), true) : [];
    if (!$order_id || !is_array($retry_data)) {
        wp_send_json_error([
            'html' => tipax_render_order_retry_result_panel([
                'type' => 'error',
                'badge' => 'استعلام قیمت',
                'title' => 'درخواست استعلام معتبر نیست',
                'messages' => ['اطلاعات ارسالی برای استعلام قیمت معتبر نیست.'],
            ]),
        ]);
    }

    $shipment_module = new Tipax_Order_Service();
    $result = $shipment_module->preview_retry_order_pricing($order_id, $retry_data);

    if (empty($result['success'])) {
        $meta = [];
        if (!empty($result['http_status'])) {
            $meta['کد پاسخ'] = 'HTTP ' . (int) $result['http_status'];
        }

        wp_send_json_error([
            'html' => tipax_render_order_retry_result_panel([
                'type' => 'error',
                'badge' => 'استعلام قیمت',
                'title' => 'استعلام قیمت از تیپاکس ناموفق بود',
                'messages' => $result['messages'] ?? ['استعلام قیمت از تیپاکس ناموفق بود.'],
                'meta' => $meta,
            ]),
        ]);
    }

    $input = $result['input'] ?? [];
    $dimensions = '';
    if (!empty($input['length_cm']) && !empty($input['width_cm']) && !empty($input['height_cm'])) {
        $dimensions = $input['length_cm'] . ' × ' . $input['width_cm'] . ' × ' . $input['height_cm'] . ' cm';
    }

    wp_send_json_success([
        'html' => tipax_render_order_retry_result_panel([
            'type' => 'success',
            'badge' => 'استعلام قیمت',
            'title' => 'نرخ‌های دریافتی از تیپاکس',
            'messages' => $result['messages'] ?? ['استعلام قیمت با موفقیت انجام شد.'],
            'meta' => [
                'وزن' => !empty($input['weight_kg']) ? $input['weight_kg'] . ' kg' : '',
                'بسته‌بندی' => $input['packing_label'] ?? '',
                'ابعاد' => $dimensions,
                'تعداد بسته' => !empty($result['package_count']) ? (string) $result['package_count'] : '',
            ],
            'rates' => $result['rates'] ?? [],
        ]),
    ]);
}

remove_action('wp_ajax_tipax_register_order', 'tipax_ajax_register_order');
add_action('wp_ajax_tipax_register_order', 'tipax_ajax_register_order_v2');

function tipax_ajax_register_order_v2()
{
    check_ajax_referer('tipax_register_order');

    $order_id = absint($_POST['order_id'] ?? 0);
    $retry_data = isset($_POST['retry_data']) ? json_decode(wp_unslash($_POST['retry_data']), true) : [];
    if (!$order_id) {
        wp_send_json_error([
            'html' => tipax_render_order_retry_result_panel([
                'type' => 'error',
                'badge' => 'ثبت در تیپاکس',
                'title' => 'شناسه سفارش معتبر نیست',
                'messages' => ['شناسه سفارش نامعتبر است.'],
            ]),
        ]);
    }

    $order = wc_get_order($order_id);
    if (!$order || !$order->has_shipping_method('tipax_shipping_method')) {
        wp_send_json_error([
            'html' => tipax_render_order_retry_result_panel([
                'type' => 'error',
                'badge' => 'ثبت در تیپاکس',
                'title' => 'سفارش قابل ثبت نیست',
                'messages' => ['سفارش معتبر نیست یا مربوط به تیپاکس نیست.'],
            ]),
        ]);
    }

    unset($_COOKIE['tipaxOrder_' . $order_id]);
    $shipment_module = new Tipax_Order_Service();
    $result = $shipment_module->retry_order_registration($order_id, is_array($retry_data) ? $retry_data : []);

    if (empty($result['success'])) {
        $meta = [];
        if (!empty($result['http_status'])) {
            $meta['کد پاسخ'] = 'HTTP ' . (int) $result['http_status'];
        }

        wp_send_json_error([
            'html' => tipax_render_order_retry_result_panel([
                'type' => 'error',
                'badge' => 'ثبت در تیپاکس',
                'title' => 'ثبت سفارش در تیپاکس ناموفق بود',
                'messages' => $result['messages'] ?? ['ثبت سفارش در تیپاکس ناموفق بود.'],
                'meta' => $meta,
            ]),
        ]);
    }

    wp_send_json_success([
        'html' => tipax_render_order_retry_result_panel([
            'type' => 'success',
            'badge' => 'ثبت در تیپاکس',
            'title' => 'سفارش با موفقیت در تیپاکس ثبت شد',
            'messages' => $result['messages'] ?? ['ثبت سفارش در تیپاکس با موفقیت انجام شد.'],
            'tracking_codes' => $result['tracking_codes'] ?? [],
            'tipax_order_id' => $result['tipax_order_id'] ?? '',
            'show_reload' => true,
        ]),
    ]);
}



if ( ! class_exists('tipax_shipping_orders_common') ) {
    class tipax_shipping_orders_common {
        private static $hpos_enabled = null;
        function is_wc_hpos_enabled() {
            if ( is_null(self::$hpos_enabled) ) {
                if ( class_exists('Automattic\WooCommerce\Utilities\OrderUtil') ) {
                    self::$hpos_enabled = Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
                } else {
                    self::$hpos_enabled = false;
                }
            }
            return self::$hpos_enabled;
        }
    }
}

function tipax_barcode_order_meta_box() {
    $instance = new tipax_shipping_orders_common();
    $screen = $instance->is_wc_hpos_enabled() ? wc_get_page_screen_id('shop-order') : 'shop_order';
    add_meta_box(
        'tipax-barcode-box',
        __('Tipax Barcodes','delivery-service-tipax-shipping'),
        'tipax_shipping_barcode_meta_box',
        $screen,
        'side',
        'default'
    );
}

function tipax_shipping_barcode_meta_box($order) {
    $desired_shipping_method = 'tipax_shipping_method';

    // Normalize WC_Order
    if (!is_a($order, 'WC_Order')) {
        if (isset($order->ID)) {
            $order = wc_get_order($order->ID);
        } elseif (isset($_GET['post'])) {
            $order = wc_get_order(absint($_GET['post']));
        }
        if (!is_a($order, 'WC_Order')) {
            echo esc_html__('Order type is not WooCommerce.', 'delivery-service-tipax-shipping');
            return;
        }
    }

    // Ensure Tipax shipping
    $is_tipax = false;
    foreach ($order->get_shipping_methods() as $shipping_item) {
        if ($shipping_item->get_method_id() === $desired_shipping_method) {
            $is_tipax = true; break;
        }
    }
    if (!$is_tipax) {
        echo esc_html__('Tipax is not shipping method for this order.','delivery-service-tipax-shipping');
        return;
    }

    // Your working Picqer include
    require_once 'vendor/autoload.php';
    if (!class_exists('Picqer\\Barcode\\BarcodeGeneratorPNG')) {
        echo esc_html__('Barcode generator not found.','delivery-service-tipax-shipping');
        return;
    }

    $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();

    // Collect barcodes and preview
    $has_barcode   = false;
    $barcode_data  = []; // [ [code => '...', img => 'data:...'], ... ]
    $preview_html  = '';

    foreach ($order->get_items() as $item) {
        $codes_raw = tipax_normalize_tracking_codes_string($item->get_meta('tipax_tracking_code'));
        if ($codes_raw === '') { continue; }
        $codes = preg_split('/\s+/', trim($codes_raw));
        if (empty($codes)) { continue; }

        foreach ($codes as $code) {
            $code = trim($code);
            if ($code === '') { continue; }
            $has_barcode = true;

            try {
                $png = $generator->getBarcode($code, $generator::TYPE_CODE_128, 2, 80);
                $img = 'data:image/png;base64,' . base64_encode($png);
            } catch (\Exception $e) {
                // Skip a faulty code
                continue;
            }

            $barcode_data[] = ['code' => $code, 'img' => $img];
            $preview_html  .= '<div style="margin-bottom:10px;text-align:center;">'
                            . '<img style="max-width:100%" src="' . esc_attr($img) . '" alt="' . esc_attr($code) . '"><br>'
                            . esc_html($code)
                            . '</div>';
        }
    }

    if (!$has_barcode) {
        echo esc_html__('There is no barcodes.','delivery-service-tipax-shipping');
        return;
    }

        $settings = get_option('woocommerce_tipax_shipping_method_settings', []);
        $show_products = ($settings['show_product_table'] ?? 'yes') === 'yes';
        $custom_logo_url = $settings['logo_url'] ?? '';
        $sender_phone = $settings['phone'] ?? '';
        $sender_name  = $settings['name'] ?? get_bloginfo('name');
        
        $custom_logo_url = $settings['logo_url'] ?? '';
    // Build order + party info for printing
    $order_data = [
        'order_id'   => $order->get_id(),
        'order_date' => $order->get_date_created() ? wc_format_datetime($order->get_date_created(), 'Y/m/d') : '',
        'products'   => array_values(array_map(function($it){
            return ['name' => $it->get_name(), 'qty' => (int)$it->get_quantity()];
        }, $order->get_items())),
        'sender' => [
            'name'     => get_bloginfo('name'),
            'phone'    => (string) ($sender_phone ?: ''),
            'address'  => trim((get_option('woocommerce_store_address') ?: '') . ' ' . (get_option('woocommerce_store_address_2') ?: '') . ', ' . (get_option('woocommerce_store_city') ?: '')),
            'postcode' => (string) get_option('woocommerce_store_postcode'),
            'display_products' => $show_products,
        ],
        'receiver' => [
            'name'     => trim(
                (($order->get_shipping_first_name() ?: $order->get_billing_first_name()) ?: '')
                . ' ' .
                (($order->get_shipping_last_name() ?: $order->get_billing_last_name()) ?: '')
            ),
            'phone'    => (string) $order->get_billing_phone(),
            'address'  => trim(
                (($order->get_shipping_address_1() ?: $order->get_billing_address_1()) ?: '')
                . ' ' .
                (($order->get_shipping_address_2() ?: $order->get_billing_address_2()) ?: '')
            ),
            'postcode' => (string) ($order->get_shipping_postcode() ?: $order->get_billing_postcode()),
        ],
        'barcodes' => $barcode_data,
'logo' => $custom_logo_url ?: (wp_get_attachment_image_url(get_theme_mod('custom_logo'),'full') ?: includes_url('images/w-logo-blue.png')),

        'font_url' => esc_url(plugins_url('../assets/BNazanin.TTF', __FILE__)), // optional
    ];

    // Render preview + Print button (use inline onclick; return false)
    echo '<div id="printable-content" style="text-align:center">' . $preview_html . '</div>';
    echo '<button type="button" class="button button-primary" style="margin-top:10px;" onclick="return tipax_print_label();">'
        . esc_html__('Print Barcodes', 'delivery-service-tipax-shipping') . '</button>';

    // Print function (global, inline, returns false to block form submit)
    ?>
    <script>
    (function(){
        // Data from PHP
        window._tipaxPrintData = <?php echo wp_json_encode($order_data); ?>;

        // Global print function used by inline onclick
        window.tipax_print_label = function(){
            try {
                var d = window._tipaxPrintData || {};
var css = ''
  + '@page { size: A5 portrait; margin: 0; }'
  + '@font-face { font-family: "BNazanin"; src: url("' + (d.font_url || '') + '") format("truetype"); }'
  + 'body{margin:0 auto;font-family:"BNazanin",Tahoma,sans-serif;direction:rtl;background:#fff;padding:0;}'
  + '.invoice{width:148mm;height:210mm;box-sizing:border-box;padding:6mm;border:1px solid #ccc;page-break-after:always;overflow:hidden;}'
  + 'header{display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #ccc;padding-bottom:2mm;margin-bottom:3mm;}'
  + 'header img{height:30px;}'
  + '.barcode{text-align:center;margin:5mm 0;}'
  + '.barcode img{height:55px;margin-bottom:1mm;}'
  + '.barcode p{font-size:15px;font-weight:bold;letter-spacing:1px;}'
  + '.info{display:flex;flex-direction:column;gap:4mm;margin-top:1mm;}'
  + '.info-box{width:calc(100% - 20px);border:1px solid #ddd;padding:3mm;border-radius:4px;background:#fafafa;}'
  + '.info-box h3{font-size:15px;margin:0 0 2mm;border-bottom:1px solid #ddd;padding-bottom:1mm;}'
  + '.info-box p{margin:0 0 1mm;font-size:14px;line-height:1.4;}'
  + 'table{width:100%;border-collapse:collapse;margin-top:3mm;font-size:14px;}'
  + 'th,td{border:2px solid #ccc;padding:2px 4px;text-align:center;}'
  + 'th{background:#f2f2f2;}'
  + '.barcode{font-family:Tahoma,sans-serif;}'
  + '@media print{.invoice{page-break-after:always;width:148mm;height:210mm;margin:0 auto;}}';


                var rows = '';
                (d.products||[]).forEach(function(p){
                    rows += '<tr><td>'+ esc(p.name) +'</td><td>'+ String(p.qty) +'</td></tr>';
                });
                
                var tableHTML = '';
                if (d.sender.display_products) {
                    tableHTML = '<table><thead><tr><th>نام محصول</th><th>تعداد</th></tr></thead><tbody>'+ rows +'</tbody></table>';
                }

                var blocks = '';
                (d.barcodes||[]).forEach(function(b){
                    var code = String(b.code||'');
                    var img  = String(b.img||'');
                    blocks += ''
                    + '<div class="invoice">'
                    + '  <header>'
                    + '    <img src="'+ escAttr(d.logo||'') +'" alt="لوگو">'
                    + '    <div><p>شماره فاکتور: '+ esc(String(d.order_id||'')) +'</p><p>تاریخ: '+ esc(String(d.order_date||'')) +'</p></div>'
                    + '  </header>'
                    + '  <div class="barcode">'
                    + '    <img src="'+ escAttr(img) +'" alt="barcode"><p class="barcode">'+ esc(code) +'</p>'
                    + '  </div>'
                    + '  <div class="info">'
                    + '    <div class="info-box">'
                    + '      <h3>فرستنده</h3>'
                    + '      <p>'+ esc(d.sender && d.sender.name || '') +'</p>'
                    + '      <p>تلفن: '+ esc(d.sender && d.sender.phone || '') +'</p>'
                    + '      <p>آدرس: '+ esc(d.sender && d.sender.address || '') +'</p>'
                    + '      <p>کدپستی: '+ esc(d.sender && d.sender.postcode || '') +'</p>'
                    + '    </div>'
                    + '    <div class="info-box">'
                    + '      <h3>گیرنده</h3>'
                    + '      <p>'+ esc(d.receiver && d.receiver.name || '') +'</p>'
                    + '      <p>تلفن: '+ esc(d.receiver && d.receiver.phone || '') +'</p>'
                    + '      <p>آدرس: '+ esc(d.receiver && d.receiver.address || '') +'</p>'
                    + '      <p>کدپستی: '+ esc(d.receiver && d.receiver.postcode || '') +'</p>'
                    + '    </div>'
                    + '  </div>'
                    + tableHTML
                    + '</div>';
                });

                var w = window.open('', '_blank');
                if (!w) { alert('Popup blocked. Please allow popups and try again.'); return false; }
                w.document.open();
                w.document.write('<html lang="fa"><head><meta charset="UTF-8"><title>چاپ برچسب تیپاکس</title><style>'+ css +'</style></head><body>'+ blocks +'</body></html>');
                w.document.close();
                w.onload = function(){ try { w.focus(); w.print(); w.close(); } catch(e){} };
            } catch(err) {
                console.error(err);
            }
            // block any default form submission
            return false;
        };

        // Safe HTML escaping helpers
        function esc(s){ s = String(s||''); return s.replace(/[&<>"]/g, function(m){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[m]}); }
        function escAttr(s){ return esc(s).replace(/"/g,'&quot;'); }
    })();
    </script>
    <?php
}



// Direct-distribution updates live in the optional hub updater module. The
// WordPress.org build omits that module and uses WordPress's normal updater.

add_filter('woocommerce_available_payment_gateways', 'tipax_filter_cod_payment_gateway');

function tipax_filter_cod_payment_gateway($available_gateways) {
    if (is_admin() && !wp_doing_ajax()) {
        return $available_gateways;
    }

    $settlement = tipax_get_settlement_config();
    $chosen_methods = WC()->session ? WC()->session->get('chosen_shipping_methods') : [];
    return tipax_apply_cod_gateway_policy(
        $available_gateways,
        $settlement['cod_enabled'],
        $chosen_methods
    );
}

