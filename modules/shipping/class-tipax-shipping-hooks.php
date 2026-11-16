<?php
if (!defined('ABSPATH')) {
    exit;
}

function tipax_should_manage_checkout_cities($settings = null, $tipax_is_only_method = false)
{
    if (!is_array($settings)) {
        $settings = get_option('woocommerce_tipax_shipping_method_settings', []);
    }

    if (array_key_exists('cities', $settings)) {
        return $settings['cities'] === 'yes';
    }

    // Preserve the old default until the merchant saves this option explicitly.
    return (bool) $tipax_is_only_method;
}

class ActiveShippingMethods
{
    public function get_active_zones()
    {
        return class_exists('WC_Shipping_Zones') ? WC_Shipping_Zones::get_zones() : [];
    }

    public function get_zones_active_methods()
    {
        $active_methods = [];
        foreach ($this->get_active_zones() as $zone) {
            foreach (($zone['shipping_methods'] ?? []) as $method) {
                if (isset($method->enabled) && $method->enabled === 'yes') {
                    $active_methods[] = $method;
                }
            }
        }

        if (class_exists('WC_Shipping_Zones')) {
            $rest_of_world = WC_Shipping_Zones::get_zone(0);
            if (is_object($rest_of_world) && is_callable([$rest_of_world, 'get_shipping_methods'])) {
                foreach ($rest_of_world->get_shipping_methods(true) as $method) {
                    if (isset($method->enabled) && $method->enabled === 'yes') {
                        $active_methods[] = $method;
                    }
                }
            }
        }

        return $active_methods;
    }

    public function is_tipax_alone()
    {
        $methods = $this->get_zones_active_methods();
        return count($methods) === 1 && isset($methods[0]->id) && $methods[0]->id === 'tipax_shipping_method';
    }

    public function check_for_only_tipax()
    {
        $settings = get_option('woocommerce_tipax_shipping_method_settings', []);
        return function_exists('tipax_should_manage_checkout_cities')
            ? tipax_should_manage_checkout_cities($settings, $this->is_tipax_alone())
            : (isset($settings['cities']) && $settings['cities'] === 'yes');
    }
}

class TipaxLoadCities
{
    public function __construct()
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_get_tipax_cities', [$this, 'get_cities']);
        add_action('wp_ajax_nopriv_get_tipax_cities', [$this, 'get_cities']);
        add_filter('woocommerce_checkout_fields', [$this, 'custom_modify_woocommerce_city_field'], 20);
    }

    public function types()
    {
        $types = ['billing'];
        if (!function_exists('wc_ship_to_billing_address_only') || !wc_ship_to_billing_address_only()) {
            $types[] = 'shipping';
        }

        return $types;
    }

    public function custom_modify_woocommerce_city_field($fields)
    {
        if (!(new ActiveShippingMethods())->check_for_only_tipax()) {
            return $fields;
        }

        foreach ($this->types() as $type) {
            if (isset($fields[$type][$type . '_state'])) {
                $fields[$type][$type . '_state']['placeholder'] = __('Please select your state.', 'delivery-service-tipax-shipping');
            }

            $key = $type . '_city';
            $city_field = isset($fields[$type][$key]) && is_array($fields[$type][$key])
                ? $fields[$type][$key]
                : [];
            $city_field['type'] = 'select';
            $city_field['label'] = $city_field['label'] ?? __('City', 'woocommerce');
            $city_field['required'] = true;
            $city_field['options'] = [
                '' => __('Select Your City.', 'delivery-service-tipax-shipping'),
            ];
            $current_city = $this->get_customer_address_value($type, 'city');
            if ($current_city !== '') {
                $state_value = $this->get_customer_address_value($type, 'state');
                $state_title = function_exists('tipax_resolve_state_title_for_display')
                    ? tipax_resolve_state_title_for_display($state_value, 'IR')
                    : $state_value;
                $current_city_title = is_numeric($current_city) && function_exists('tipax_get_tipax_city_title_for_display')
                    ? tipax_get_tipax_city_title_for_display($current_city, $state_title)
                    : $current_city;
                $city_field['options'][(string) $current_city] = $current_city_title !== ''
                    ? $current_city_title
                    : (string) $current_city;
            }
            $city_field['class'] = array_values(array_unique(array_merge(
                isset($city_field['class']) && is_array($city_field['class']) ? $city_field['class'] : [],
                ['form-row-wide', 'address-field', 'update_totals_on_change']
            )));
            $city_field['input_class'] = array_values(array_unique(array_merge(
                isset($city_field['input_class']) && is_array($city_field['input_class']) ? $city_field['input_class'] : [],
                ['tipax-city-select']
            )));
            $fields[$type][$key] = $city_field;
        }

        return $fields;
    }

    public function enqueue_scripts()
    {
        if (!$this->is_checkout_surface()) {
            return;
        }

        $manages_cities = (new ActiveShippingMethods())->check_for_only_tipax();
        $script_path = TSM_DIR . 'assets/frontend/js/cities.js';
        $script_version = TSM_VERSION . (file_exists($script_path) ? '.' . filemtime($script_path) : '');

        wp_register_script(
            'tsmCheckout',
            TSM_ASSETS_URL . 'frontend/js/cities.js',
            ['jquery', 'wp-data'],
            $script_version,
            true
        );
        wp_localize_script('tsmCheckout', 'tsm_settings', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tsm_settings'),
            'types' => $this->types(),
            'manage_cities' => (bool) $manages_cities,
            'state_labels' => function_exists('tipax_get_iran_state_labels') ? tipax_get_iran_state_labels() : [],
            'messages' => [
                'select_city' => 'یک شهر را انتخاب کنید',
                'loading' => 'در حال بارگذاری لیست شهرها...',
                'empty' => 'شهری برای استان انتخاب‌شده یافت نشد',
                'error' => 'دریافت فهرست شهرها ناموفق بود',
            ],
        ]);
        wp_enqueue_script('tsmCheckout');

        if ($manages_cities) {
            $style_path = TSM_DIR . 'assets/frontend/css/cities.css';
            $style_version = TSM_VERSION . (file_exists($style_path) ? '.' . filemtime($style_path) : '');
            wp_enqueue_style('tsmCheckoutCities', TSM_ASSETS_URL . 'frontend/css/cities.css', [], $style_version);
        }
    }

    public function get_cities()
    {
        check_ajax_referer('tsm_settings', 'security');

        $state_value = isset($_POST['state']) ? sanitize_text_field(wp_unslash($_POST['state'])) : '';
        $posted_state_title = isset($_POST['state_title']) ? sanitize_text_field(wp_unslash($_POST['state_title'])) : '';
        $state_title = $posted_state_title !== ''
            ? $posted_state_title
            : (function_exists('tipax_resolve_state_title_for_display')
                ? tipax_resolve_state_title_for_display($state_value, 'IR')
                : $state_value);

        $result = [];
        foreach ($this->get_distinct_cities($state_title) as $city) {
            $city_id = absint($city->cityId ?? 0);
            $city_title = trim((string) ($city->cityTitle ?? ''));
            if ($city_id <= 0 || $city_title === '') {
                continue;
            }

            $result[] = [
                'id' => $city_id,
                'title' => $city_title,
            ];
        }

        wp_send_json_success([
            'state' => $state_value,
            'state_title' => $state_title,
            'cities' => $result,
        ]);
    }

    private function get_distinct_cities($state)
    {
        $cities = function_exists('tipax_get_city_rows_for_state')
            ? tipax_get_city_rows_for_state($state)
            : [];

        if (!is_array($cities)) {
            return [];
        }

        usort($cities, static function ($first, $second) {
            return strcmp((string) ($first->cityTitle ?? ''), (string) ($second->cityTitle ?? ''));
        });

        return $cities;
    }

    private function get_customer_address_value($type, $field)
    {
        $key = $type . '_' . $field;
        if (isset($_POST[$key]) && is_scalar($_POST[$key])) {
            return sanitize_text_field(wp_unslash($_POST[$key]));
        }

        if (!function_exists('WC') || !WC() || !WC()->customer) {
            return '';
        }

        $getter = 'get_' . $type . '_' . $field;
        return is_callable([WC()->customer, $getter]) ? trim((string) WC()->customer->{$getter}()) : '';
    }

    private function is_checkout_surface()
    {
        if (function_exists('is_checkout') && is_checkout()) {
            return true;
        }

        $page_id = function_exists('get_queried_object_id') ? (int) get_queried_object_id() : 0;
        $checkout_page_id = (int) get_option('woocommerce_checkout_page_id', 0);
        if ($page_id > 0 && $page_id === $checkout_page_id) {
            return true;
        }

        $post = $page_id > 0 ? get_post($page_id) : null;
        if (!$post || !isset($post->post_content)) {
            return false;
        }

        $content = (string) $post->post_content;
        if (
            (function_exists('has_shortcode') && has_shortcode($content, 'woocommerce_checkout'))
            || (function_exists('has_block') && has_block('woocommerce/checkout', $post))
        ) {
            return true;
        }

        $elementor_data = function_exists('get_post_meta')
            ? get_post_meta($page_id, '_elementor_data', true)
            : '';
        if (!is_string($elementor_data) || $elementor_data === '') {
            return false;
        }

        return strpos($elementor_data, 'woocommerce_checkout') !== false
            || strpos($elementor_data, 'woocommerce-checkout') !== false;
    }
}

new TipaxLoadCities();
