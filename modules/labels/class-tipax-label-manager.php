<?php
if (!defined('ABSPATH')) {
    exit;
}

class Tipax_Label_Manager
{
    private const PRINT_ACTION = 'tipax_print_labels';
    private const BULK_ACTION = 'tipax_print_labels';
    private const COLUMN_KEY = 'tipax_label';
    private const MAX_BULK_ORDERS = 100;

    private static $instance = null;
    private $barcode_generator = null;

    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        remove_action('add_meta_boxes', 'tipax_barcode_order_meta_box');

        add_action('admin_post_' . self::PRINT_ACTION, [$this, 'handle_print_request']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('add_meta_boxes', [$this, 'register_order_meta_box'], 30, 2);

        add_filter('manage_woocommerce_page_wc-orders_columns', [$this, 'add_orders_column'], 30);
        add_action('manage_woocommerce_page_wc-orders_custom_column', [$this, 'render_hpos_order_column'], 30, 2);
        add_filter('manage_edit-shop_order_columns', [$this, 'add_orders_column'], 30);
        add_action('manage_shop_order_posts_custom_column', [$this, 'render_legacy_order_column'], 30, 2);

        add_filter('bulk_actions-woocommerce_page_wc-orders', [$this, 'add_bulk_action']);
        add_filter('handle_bulk_actions-woocommerce_page_wc-orders', [$this, 'handle_bulk_action'], 10, 3);
        add_filter('bulk_actions-edit-shop_order', [$this, 'add_bulk_action']);
        add_filter('handle_bulk_actions-edit-shop_order', [$this, 'handle_bulk_action'], 10, 3);
    }

    private function current_user_can_print()
    {
        return current_user_can('manage_woocommerce')
            || current_user_can('edit_shop_orders')
            || current_user_can('manage_options');
    }

    private function normalize_order($order)
    {
        if ($order instanceof WC_Order) {
            return $order;
        }

        if ($order instanceof WP_Post) {
            return wc_get_order($order->ID);
        }

        if (is_numeric($order)) {
            return wc_get_order(absint($order));
        }

        return false;
    }

    public function is_tipax_order($order)
    {
        $order = $this->normalize_order($order);
        if (!$order) {
            return false;
        }

        if (method_exists($order, 'has_shipping_method') && $order->has_shipping_method('tipax_shipping_method')) {
            return true;
        }

        foreach ($order->get_shipping_methods() as $shipping_item) {
            if (method_exists($shipping_item, 'get_method_id') && $shipping_item->get_method_id() === 'tipax_shipping_method') {
                return true;
            }
        }

        return false;
    }

    public function get_order_tracking_codes($order)
    {
        $order = $this->normalize_order($order);
        if (!$order) {
            return [];
        }

        $codes = [];
        foreach ($order->get_items() as $item) {
            foreach (tipax_normalize_tracking_codes($item->get_meta('tipax_tracking_code')) as $code) {
                $codes[$code] = $code;
            }
        }

        $snapshot_codes = $order->get_meta('_tipax_tracking_codes', true);
        foreach (tipax_normalize_tracking_codes($snapshot_codes) as $code) {
            $codes[$code] = $code;
        }

        $primary_code = tipax_normalize_tracking_code($order->get_meta('_tipax_tracking_primary_code', true));
        if ($primary_code !== '') {
            $codes[$primary_code] = $primary_code;
        }

        return array_values($codes);
    }

    public function get_order_cancelled_tracking_codes($order)
    {
        $order = $this->normalize_order($order);
        if (!$order) {
            return [];
        }

        if (Tipax_Cancellation_Service::is_cancelled_status_id($order->get_meta('_tipax_saved_status_id', true))) {
            return $this->get_order_tracking_codes($order);
        }

        return tipax_normalize_tracking_codes($order->get_meta('_tipax_cancelled_tracking_codes', true));
    }

    public function get_order_printable_tracking_codes($order)
    {
        return array_values(array_diff(
            $this->get_order_tracking_codes($order),
            $this->get_order_cancelled_tracking_codes($order)
        ));
    }

    public function order_has_printable_labels($order)
    {
        return $this->is_tipax_order($order) && !empty($this->get_order_printable_tracking_codes($order));
    }

    private function sanitize_order_ids($order_ids)
    {
        if (!is_array($order_ids)) {
            $order_ids = preg_split('/[\s,]+/', (string) $order_ids);
        }

        return array_slice(
            array_values(array_unique(array_filter(array_map('absint', $order_ids)))),
            0,
            self::MAX_BULK_ORDERS
        );
    }

    public function get_print_url($order_ids, $auto_print = true)
    {
        $order_ids = $this->sanitize_order_ids($order_ids);
        if (empty($order_ids)) {
            return '';
        }

        $url = add_query_arg([
            'action' => self::PRINT_ACTION,
            'order_ids' => implode(',', $order_ids),
            'autoprint' => $auto_print ? '1' : '0',
        ], admin_url('admin-post.php'));

        return add_query_arg('_wpnonce', wp_create_nonce(self::PRINT_ACTION), $url);
    }

    public function add_orders_column($columns)
    {
        if (!is_array($columns) || isset($columns[self::COLUMN_KEY])) {
            return $columns;
        }

        $result = [];
        $inserted = false;
        foreach ($columns as $key => $label) {
            if (!$inserted && in_array($key, ['wc_actions', 'order_actions'], true)) {
                $result[self::COLUMN_KEY] = 'لیبل تیپاکس';
                $inserted = true;
            }
            $result[$key] = $label;
        }

        if (!$inserted) {
            $result[self::COLUMN_KEY] = 'لیبل تیپاکس';
        }

        return $result;
    }

    public function render_hpos_order_column($column, $order)
    {
        if ($column !== self::COLUMN_KEY) {
            return;
        }

        $this->render_order_column_content($order);
    }

    public function render_legacy_order_column($column, $post_id = 0)
    {
        if ($column !== self::COLUMN_KEY) {
            return;
        }

        $this->render_order_column_content($post_id);
    }

    private function render_order_column_content($order)
    {
        $order = $this->normalize_order($order);
        if (!$order || !$this->is_tipax_order($order)) {
            echo '<span class="tipax-label-not-applicable" aria-label="سفارش غیر تیپاکس">—</span>';
            return;
        }

        $allCodes = $this->get_order_tracking_codes($order);
        $codes = $this->get_order_printable_tracking_codes($order);
        if (empty($codes)) {
            echo !empty($allCodes)
                ? '<span class="tipax-label-cancelled">مرسوله ابطال شده</span>'
                : '<span class="tipax-label-missing">بدون کد رهگیری</span>';
            return;
        }

        $url = $this->get_print_url([$order->get_id()]);
        echo '<a class="button tipax-label-print-button" href="' . esc_url($url) . '" target="_blank" rel="noopener">';
        echo '<span class="dashicons dashicons-printer" aria-hidden="true"></span>';
        echo '<span>چاپ ' . esc_html(count($codes)) . ' لیبل</span>';
        echo '</a>';
    }

    public function add_bulk_action($actions)
    {
        if (!is_array($actions)) {
            $actions = [];
        }

        $actions[self::BULK_ACTION] = 'چاپ لیبل‌های تیپاکس';
        return $actions;
    }

    public function handle_bulk_action($redirect_to, $action, $order_ids)
    {
        if ($action !== self::BULK_ACTION) {
            return $redirect_to;
        }

        $print_url = $this->get_print_url($order_ids);
        return $print_url !== '' ? $print_url : $redirect_to;
    }

    private function get_order_screen_id()
    {
        if (
            class_exists('Automattic\WooCommerce\Utilities\OrderUtil')
            && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()
            && function_exists('wc_get_page_screen_id')
        ) {
            return wc_get_page_screen_id('shop-order');
        }

        return 'shop_order';
    }

    private function resolve_current_order($post_or_order = null)
    {
        $order = $this->normalize_order($post_or_order);
        if ($order) {
            return $order;
        }

        $order_id = 0;
        if (isset($_GET['id'])) {
            $order_id = absint($_GET['id']);
        } elseif (isset($_GET['post'])) {
            $order_id = absint($_GET['post']);
        }

        return $order_id ? wc_get_order($order_id) : false;
    }

    public function register_order_meta_box($post_type = '', $post_or_order = null)
    {
        $order = $this->resolve_current_order($post_or_order);
        if (!$order || !$this->is_tipax_order($order)) {
            return;
        }

        add_meta_box(
            'tipax-barcode-box',
            'لیبل ارسال تیپاکس',
            [$this, 'render_order_meta_box'],
            $this->get_order_screen_id(),
            'side',
            'high'
        );
    }

    public function render_order_meta_box($post_or_order)
    {
        $order = $this->normalize_order($post_or_order);
        if (!$order || !$this->is_tipax_order($order)) {
            return;
        }

        $allCodes = $this->get_order_tracking_codes($order);
        $codes = $this->get_order_printable_tracking_codes($order);
        if (empty($codes)) {
            echo '<div class="tipax-label-meta-empty">';
            echo '<span class="dashicons dashicons-info-outline" aria-hidden="true"></span>';
            echo '<p>' . (!empty($allCodes)
                ? 'مرسوله‌های این سفارش ابطال شده‌اند و لیبلی برای چاپ باقی نمانده است.'
                : 'هنوز کد رهگیری تیپاکس برای این سفارش ثبت نشده است؛ پس از ثبت مرسوله، امکان چاپ لیبل فعال می‌شود.') . '</p>';
            echo '</div>';
            return;
        }

        echo '<div class="tipax-label-meta-box">';
        echo '<div class="tipax-label-meta-head"><strong>' . esc_html(count($codes)) . ' لیبل آماده چاپ</strong><span>۱۰ × ۱۶ سانتی‌متر</span></div>';
        echo '<div class="tipax-label-meta-barcodes">';
        foreach (array_slice($codes, 0, 3) as $code) {
            $barcode_url = $this->generate_barcode_data_uri($code);
            echo '<div class="tipax-label-meta-barcode">';
            if ($barcode_url !== '') {
                echo '<img src="' . esc_attr($barcode_url) . '" alt="' . esc_attr($code) . '">';
            }
            echo '<code dir="ltr">' . esc_html($code) . '</code>';
            echo '</div>';
        }
        echo '</div>';

        if (count($codes) > 3) {
            echo '<p class="tipax-label-meta-more">و ' . esc_html(count($codes) - 3) . ' لیبل دیگر</p>';
        }

        echo '<div class="tipax-label-meta-actions">';
        echo '<a class="button" href="' . esc_url($this->get_print_url([$order->get_id()], false)) . '" target="_blank" rel="noopener">پیش‌نمایش</a>';
        echo '<a class="button button-primary" href="' . esc_url($this->get_print_url([$order->get_id()], true)) . '" target="_blank" rel="noopener">چاپ لیبل</a>';
        echo '</div>';
        echo '</div>';
    }

    private function is_label_admin_screen()
    {
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
        if ($page === 'tipax-orders' || $page === 'wc-orders') {
            return true;
        }

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen) {
            return false;
        }

        return in_array($screen->id, ['shop_order', 'edit-shop_order', 'woocommerce_page_wc-orders'], true);
    }

    public function enqueue_admin_assets()
    {
        if (!$this->is_label_admin_screen()) {
            return;
        }

        $style_path = TSM_DIR . 'assets/admin/css/tipax-label-admin.css';
        $script_path = TSM_DIR . 'assets/admin/js/tipax-label-admin.js';
        $style_version = file_exists($style_path) ? TSM_VERSION . '.' . filemtime($style_path) : TSM_VERSION;
        $script_version = file_exists($script_path) ? TSM_VERSION . '.' . filemtime($script_path) : TSM_VERSION;

        wp_enqueue_style(
            'tipax-label-admin',
            TSM_ASSETS_URL . 'admin/css/tipax-label-admin.css',
            ['dashicons'],
            $style_version
        );
        wp_enqueue_script(
            'tipax-label-admin',
            TSM_ASSETS_URL . 'admin/js/tipax-label-admin.js',
            [],
            $script_version,
            true
        );
        wp_localize_script('tipax-label-admin', 'tipaxLabelAdmin', [
            'printUrl' => admin_url('admin-post.php'),
            'action' => self::PRINT_ACTION,
            'nonce' => wp_create_nonce(self::PRINT_ACTION),
            'noSelection' => 'حداقل یک سفارش دارای کد رهگیری را انتخاب کنید.',
        ]);
    }

    private function load_barcode_generator()
    {
        if ($this->barcode_generator !== null) {
            return $this->barcode_generator;
        }

        $autoload_path = TSM_DIR . 'includes/vendor/autoload.php';
        if (file_exists($autoload_path)) {
            require_once $autoload_path;
        }

        if (!class_exists('Picqer\\Barcode\\BarcodeGeneratorSVG')) {
            $this->barcode_generator = false;
            return false;
        }

        $this->barcode_generator = new \Picqer\Barcode\BarcodeGeneratorSVG();
        return $this->barcode_generator;
    }

    public function generate_barcode_data_uri($tracking_code)
    {
        $tracking_code = tipax_normalize_tracking_code($tracking_code);
        $generator = $this->load_barcode_generator();
        if ($tracking_code === '' || !$generator) {
            return '';
        }

        try {
            $svg = $generator->getBarcode(
                $tracking_code,
                $generator::TYPE_CODE_128,
                1.7,
                70
            );
        } catch (\Throwable $exception) {
            return '';
        }

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    private function get_state_label($country_code, $state_code)
    {
        if (function_exists('tipax_resolve_state_title_for_display')) {
            return tipax_resolve_state_title_for_display($state_code, $country_code);
        }

        if ($state_code === '') {
            return '';
        }

        if (function_exists('WC') && WC() && isset(WC()->countries)) {
            $states = WC()->countries->get_states($country_code);
            if (is_array($states) && isset($states[$state_code])) {
                return (string) $states[$state_code];
            }
        }

        return $state_code;
    }

    private function join_address_parts(array $parts)
    {
        $parts = array_values(array_filter(array_map(static function ($part) {
            return is_scalar($part) ? trim((string) $part) : '';
        }, $parts), static function ($part) {
            return $part !== '';
        }));

        return implode('، ', $parts);
    }

    private function get_origin_field($origin, array $fields)
    {
        if (!is_object($origin) || !function_exists('tipax_get_address_record_field')) {
            return '';
        }

        foreach ($fields as $field) {
            $value = tipax_get_address_record_field($origin, $field, '');
            if (is_scalar($value) && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return '';
    }

    private function get_sender_data(array $settings)
    {
        $default_location = (string) get_option('woocommerce_default_country', '');
        $location_parts = array_pad(explode(':', $default_location, 2), 2, '');
        $country_code = $location_parts[0];
        $state_code = $location_parts[1];
        $state = $this->get_state_label($country_code, $state_code);
        $city = (string) get_option('woocommerce_store_city', '');
        $address = $this->join_address_parts([
            $state,
            $city,
            get_option('woocommerce_store_address', ''),
            get_option('woocommerce_store_address_2', ''),
        ]);

        $origin = get_transient('tipax_addresses');
        if (is_object($origin)) {
            $origin_address = $this->get_origin_field($origin, ['fullAddress']);
            if ($origin_address === '') {
                $origin_address = $this->join_address_parts([
                    $this->get_origin_field($origin, ['stateTitle']),
                    $this->get_origin_field($origin, ['cityTitle']),
                    $this->get_origin_field($origin, ['mainStreet1']),
                    $this->get_origin_field($origin, ['sideStreet1']),
                    $this->get_origin_field($origin, ['mainAlley1']),
                    $this->get_origin_field($origin, ['sideAlley1']),
                    $this->get_origin_field($origin, ['otherAddress']),
                ]);
            }

            if ($origin_address !== '') {
                $address = $origin_address;
            }
        }

        $phone = trim((string) ($settings['phone'] ?? ''));
        if ($phone === '') {
            $phone = $this->get_origin_field($origin, ['mobile', 'phone']);
        }

        $postcode = $this->get_origin_field($origin, ['postalCode', 'postCode']);
        if ($postcode === '') {
            $postcode = trim((string) get_option('woocommerce_store_postcode', ''));
        }

        return [
            'name' => trim((string) ($settings['name'] ?? '')) ?: get_bloginfo('name'),
            'phone' => $phone,
            'address' => $address !== '' ? $address : 'درج نشده',
            'postcode' => $postcode,
        ];
    }

    private function get_receiver_data(WC_Order $order)
    {
        $location = function_exists('tipax_resolve_order_address_location')
            ? tipax_resolve_order_address_location($order, 'shipping')
            : [];
        $country_code = (string) ($order->get_shipping_country() ?: $order->get_billing_country());
        $state_code = (string) ($order->get_shipping_state() ?: $order->get_billing_state());
        $state = !empty($location['state_title'])
            ? (string) $location['state_title']
            : $this->get_state_label($country_code, $state_code);
        $city = !empty($location['city_title'])
            ? (string) $location['city_title']
            : (string) ($order->get_shipping_city() ?: $order->get_billing_city());
        $address = $this->join_address_parts([
            $state,
            $city,
            $order->get_shipping_address_1() ?: $order->get_billing_address_1(),
            $order->get_shipping_address_2() ?: $order->get_billing_address_2(),
        ]);
        $name = trim(
            (string) ($order->get_shipping_first_name() ?: $order->get_billing_first_name())
            . ' '
            . (string) ($order->get_shipping_last_name() ?: $order->get_billing_last_name())
        );

        return [
            'name' => $name !== '' ? $name : 'درج نشده',
            'phone' => trim((string) $order->get_billing_phone()),
            'address' => $address !== '' ? $address : 'درج نشده',
            'postcode' => trim((string) ($order->get_shipping_postcode() ?: $order->get_billing_postcode())),
        ];
    }

    private function get_store_logo(array $settings)
    {
        $logo_url = !empty($settings['logo_url']) ? esc_url_raw($settings['logo_url']) : '';
        if ($logo_url !== '') {
            return $logo_url;
        }

        $custom_logo_id = (int) get_theme_mod('custom_logo');
        if ($custom_logo_id) {
            $logo_url = wp_get_attachment_image_url($custom_logo_id, 'full');
        }

        return is_string($logo_url) ? $logo_url : '';
    }

    private function get_shipping_title(WC_Order $order)
    {
        foreach ($order->get_shipping_methods() as $shipping_item) {
            if ($shipping_item->get_method_id() === 'tipax_shipping_method') {
                return trim((string) $shipping_item->get_method_title());
            }
        }

        return 'تیپاکس';
    }

    private function build_labels_for_order(WC_Order $order)
    {
        if (!$this->is_tipax_order($order)) {
            return [];
        }

        $codes = $this->get_order_printable_tracking_codes($order);
        if (empty($codes)) {
            return [];
        }

        $settings = get_option('woocommerce_tipax_shipping_method_settings', []);
        $settings = is_array($settings) ? $settings : [];
        $label_layout = tipax_normalize_label_layout($settings['label_layout'] ?? 'full');
        $sender = $this->get_sender_data($settings);
        $receiver = $this->get_receiver_data($order);
        $logo = $this->get_store_logo($settings);
        $date_created = $order->get_date_created();
        $order_date = $date_created ? wc_format_datetime($date_created, 'Y/m/d H:i') : '';
        $shipping_title = $this->get_shipping_title($order);
        $labels = [];
        $total_codes = count($codes);

        foreach ($codes as $index => $code) {
            $labels[] = [
                'order_id' => $order->get_id(),
                'order_number' => $order->get_order_number(),
                'order_date' => $order_date,
                'layout' => $label_layout,
                'sender' => $sender,
                'receiver' => $receiver,
                'logo' => $logo,
                'store_name' => $sender['name'],
                'tracking_code' => $code,
                'barcode_url' => $this->generate_barcode_data_uri($code),
                'package_number' => $index + 1,
                'package_total' => $total_codes,
                'shipping_title' => $shipping_title !== '' ? $shipping_title : 'تیپاکس',
            ];
        }

        return $labels;
    }

    public function handle_print_request()
    {
        if (!$this->current_user_can_print()) {
            wp_die('شما اجازه چاپ لیبل سفارشات را ندارید.', 'دسترسی غیرمجاز', ['response' => 403]);
        }

        check_admin_referer(self::PRINT_ACTION);
        $order_ids = isset($_GET['order_ids']) ? $this->sanitize_order_ids(wp_unslash($_GET['order_ids'])) : [];
        if (empty($order_ids)) {
            wp_die('هیچ سفارشی برای چاپ انتخاب نشده است.', 'چاپ لیبل تیپاکس', ['response' => 400]);
        }

        $labels = [];
        $skipped_orders = 0;
        foreach ($order_ids as $order_id) {
            $order = wc_get_order($order_id);
            $order_labels = $order ? $this->build_labels_for_order($order) : [];
            if (empty($order_labels)) {
                $skipped_orders++;
                continue;
            }
            $labels = array_merge($labels, $order_labels);
        }

        if (empty($labels)) {
            wp_die(
                'سفارش‌های انتخاب‌شده تیپاکسی نیستند یا هنوز کد رهگیری ندارند.',
                'لیبلی برای چاپ وجود ندارد',
                ['response' => 404]
            );
        }

        $auto_print = !isset($_GET['autoprint']) || sanitize_key(wp_unslash($_GET['autoprint'])) !== '0';
        $this->render_print_document($labels, $auto_print, $skipped_orders);
        exit;
    }

    private function render_print_document(array $labels, $auto_print, $skipped_orders)
    {
        if (function_exists('nocache_headers')) {
            nocache_headers();
        }
        header('Content-Type: text/html; charset=' . get_bloginfo('charset'));
        $font_url = TSM_ASSETS_URL . 'BNazanin.TTF';
        $document_layout = tipax_normalize_label_layout($labels[0]['layout'] ?? 'full');
        $document_layout_label = tipax_get_label_layout_label($document_layout);
        ?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="<?php echo esc_attr(get_bloginfo('charset')); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>چاپ لیبل تیپاکس</title>
    <style>
        @page { size: 160mm 100mm; margin: 0; }
        @font-face {
            font-family: "TipaxLabel";
            src: url("<?php echo esc_url($font_url); ?>") format("truetype");
            font-display: swap;
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; min-height: 100%; }
        body {
            background:
                radial-gradient(circle at 15% 10%, rgba(13, 122, 89, .14), transparent 32%),
                #e9eee9;
            color: #101814;
            font-family: "TipaxLabel", Tahoma, sans-serif;
            direction: rtl;
        }
        .tipax-print-toolbar {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            padding: 12px 22px;
            background: rgba(8, 39, 29, .96);
            color: #fff;
            box-shadow: 0 10px 30px rgba(8, 39, 29, .18);
        }
        .tipax-print-toolbar strong { display: block; font-size: 16px; }
        .tipax-print-toolbar span { color: #acd2c0; font-size: 12px; }
        .tipax-print-toolbar-actions { display: flex; gap: 8px; }
        .tipax-print-toolbar button {
            min-height: 38px;
            padding: 0 18px;
            border: 1px solid rgba(255,255,255,.2);
            border-radius: 999px;
            background: transparent;
            color: #fff;
            cursor: pointer;
            font: inherit;
        }
        .tipax-print-toolbar button.primary {
            border-color: #fff;
            background: #fff;
            color: #0b513b;
            font-weight: 700;
        }
        .tipax-label-stage {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 16px;
            padding: 24px;
        }
        .tipax-shipping-label {
            position: relative;
            display: flex;
            flex-direction: column;
            width: 160mm;
            height: 100mm;
            padding: 5mm;
            overflow: hidden;
            border: .35mm solid #101814;
            background: #fff;
            break-after: page;
            page-break-after: always;
            box-shadow: 0 18px 50px rgba(8, 39, 29, .16);
        }
        .tipax-shipping-label:last-child {
            break-after: auto;
            page-break-after: auto;
        }
        .tipax-label-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 5mm;
            min-height: 15mm;
            padding-bottom: 3mm;
            border-bottom: .35mm solid #101814;
        }
        .tipax-label-brand {
            display: flex;
            align-items: center;
            gap: 3mm;
            min-width: 0;
        }
        .tipax-label-logo,
        .tipax-label-logo-fallback {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 13mm;
            height: 13mm;
            flex: 0 0 13mm;
            border: .3mm solid #173027;
            border-radius: 50%;
            object-fit: contain;
            padding: 1mm;
        }
        .tipax-label-logo-fallback {
            background: #e8f3ed;
            color: #0d7a59;
            font-size: 8mm;
            font-weight: 900;
        }
        .tipax-label-brand-name {
            overflow: hidden;
            font-size: 6mm;
            font-weight: 900;
            line-height: 1.1;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .tipax-label-order-meta {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 2mm;
            font-family: Tahoma, sans-serif;
            font-size: 2.8mm;
        }
        .tipax-label-order-meta span {
            padding: 1.2mm 2.2mm;
            border: .25mm solid #c7d4cd;
            border-radius: 99px;
            background: #f5f8f6;
        }
        .tipax-label-content {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 54mm;
            flex: 1;
            gap: 5mm;
            min-height: 0;
            padding: 3mm 0 1.5mm;
        }
        .tipax-label-info {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-width: 0;
        }
        .tipax-label-block {
            padding: 0 0 1.6mm;
            border-bottom: .25mm dashed #c4cec8;
        }
        .tipax-label-block:last-child { border-bottom: 0; padding-bottom: 0; }
        .tipax-label-title {
            display: block;
            margin-bottom: .5mm;
            color: #64736b;
            font-size: 3mm;
            font-weight: 700;
        }
        .tipax-label-text {
            font-size: 4.1mm;
            font-weight: 900;
            line-height: 1.45;
        }
        .tipax-label-subtext {
            margin-top: .4mm;
            font-family: Tahoma, sans-serif;
            font-size: 3.1mm;
            line-height: 1.35;
        }
        .tipax-label-postcode {
            display: block;
            min-height: 4.4mm;
            direction: ltr;
            unicode-bidi: isolate;
            font-family: Tahoma, sans-serif;
            font-size: 3.7mm;
            line-height: 1.15;
            letter-spacing: .4mm;
        }
        .tipax-label-postcode-block {
            padding-bottom: 1.2mm !important;
        }
        .tipax-label-postcode-block .tipax-label-title {
            margin-bottom: .35mm;
        }
        .tipax-label-barcode-panel {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-width: 0;
            padding-right: 5mm;
            border-right: .3mm solid #c4cec8;
        }
        .tipax-label-barcode-kicker {
            margin-bottom: 2mm;
            color: #0d7a59;
            font-size: 3mm;
            font-weight: 900;
        }
        .tipax-label-barcode-image {
            display: block;
            width: 100%;
            max-height: 29mm;
            object-fit: fill;
        }
        .tipax-label-barcode-error {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 27mm;
            border: .3mm dashed #8b392e;
            color: #8b392e;
            font-size: 3mm;
        }
        .tipax-label-code {
            margin-top: 2mm;
            direction: ltr;
            unicode-bidi: isolate;
            font-family: Tahoma, sans-serif;
            font-size: 4mm;
            font-weight: 900;
            letter-spacing: .35mm;
        }
        .tipax-label-package {
            margin-top: 2mm;
            color: #64736b;
            font-size: 3mm;
        }
        .tipax-label-layout-without_sender .tipax-label-info {
            justify-content: center;
            gap: 7mm;
        }
        .tipax-label-layout-without_sender .tipax-label-text {
            font-size: 4.7mm;
        }
        .tipax-label-layout-barcode_only {
            align-items: center;
            justify-content: center;
            padding: 8mm;
        }
        .tipax-label-barcode-only {
            display: flex;
            flex: 1;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: 0;
            border: .35mm solid #d0d8d3;
        }
        .tipax-label-barcode-only-kicker {
            margin-bottom: 5mm;
            color: #0d7a59;
            font-family: Tahoma, sans-serif;
            font-size: 4mm;
            font-weight: 900;
            letter-spacing: .8mm;
        }
        .tipax-label-barcode-only-image {
            display: block;
            width: 130mm;
            height: 48mm;
            max-width: 100%;
            object-fit: fill;
        }
        .tipax-label-barcode-only-error {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 130mm;
            max-width: 100%;
            height: 48mm;
            border: .35mm dashed #8b392e;
            color: #8b392e;
            font-size: 4mm;
        }
        .tipax-label-barcode-only-code {
            margin-top: 5mm;
            direction: ltr;
            unicode-bidi: isolate;
            font-family: Tahoma, sans-serif;
            font-size: 7mm;
            font-weight: 900;
            letter-spacing: 1mm;
        }
        .tipax-label-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 4mm;
            padding-top: 2.5mm;
            border-top: .35mm solid #101814;
            color: #4d5d54;
            font-size: 3mm;
        }
        .tipax-label-footer strong { color: #0d7a59; }
        @media print {
            html, body { width: 160mm; background: #fff; }
            .tipax-print-toolbar { display: none !important; }
            .tipax-label-stage { display: block; padding: 0; }
            .tipax-shipping-label {
                margin: 0;
                border: .35mm solid #101814;
                box-shadow: none;
            }
        }
        @media screen and (max-width: 760px) {
            .tipax-print-toolbar { align-items: flex-start; padding: 12px; }
            .tipax-print-toolbar-actions { flex-direction: column; }
            .tipax-label-stage {
                align-items: flex-start;
                overflow-x: auto;
                padding: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="tipax-print-toolbar">
        <div>
            <strong><?php echo esc_html(count($labels)); ?> لیبل تیپاکس آماده چاپ است</strong>
            <span>
                طرح: <?php echo esc_html($document_layout_label); ?> | اندازه هر لیبل ۱۰ × ۱۶ سانتی‌متر است.
                <?php if ($skipped_orders > 0) : ?>
                    <?php echo esc_html($skipped_orders); ?> سفارش غیرقابل چاپ نادیده گرفته شد.
                <?php endif; ?>
            </span>
        </div>
        <div class="tipax-print-toolbar-actions">
            <button type="button" onclick="if (window.opener) { window.close(); } else { window.history.back(); }">بازگشت / بستن</button>
            <button type="button" class="primary" onclick="window.print()">چاپ لیبل‌ها</button>
        </div>
    </div>

    <main class="tipax-label-stage">
        <?php foreach ($labels as $label) : ?>
            <?php $layout = tipax_normalize_label_layout($label['layout'] ?? 'full'); ?>
            <section class="tipax-shipping-label tipax-label-layout-<?php echo esc_attr($layout); ?>">
                <?php if ($layout === 'barcode_only') : ?>
                    <div class="tipax-label-barcode-only">
                        <span class="tipax-label-barcode-only-kicker">TIPAX TRACKING</span>
                        <?php if ($label['barcode_url'] !== '') : ?>
                            <img class="tipax-label-barcode-only-image" src="<?php echo esc_attr($label['barcode_url']); ?>" alt="<?php echo esc_attr($label['tracking_code']); ?>">
                        <?php else : ?>
                            <div class="tipax-label-barcode-only-error">ساخت بارکد انجام نشد</div>
                        <?php endif; ?>
                        <div class="tipax-label-barcode-only-code"><?php echo esc_html($label['tracking_code']); ?></div>
                    </div>
                <?php else : ?>
                    <header class="tipax-label-header">
                        <div class="tipax-label-brand">
                            <?php if ($label['logo'] !== '') : ?>
                                <img class="tipax-label-logo" src="<?php echo esc_url($label['logo']); ?>" alt="">
                            <?php else : ?>
                                <span class="tipax-label-logo-fallback">ت</span>
                            <?php endif; ?>
                            <div class="tipax-label-brand-name">
                                <?php echo esc_html($layout === 'without_sender' ? $label['receiver']['name'] : $label['store_name']); ?>
                            </div>
                        </div>
                        <div class="tipax-label-order-meta">
                            <span>سفارش #<?php echo esc_html($label['order_number']); ?></span>
                            <span><?php echo esc_html($label['shipping_title']); ?></span>
                        </div>
                    </header>

                    <div class="tipax-label-content">
                        <div class="tipax-label-info">
                            <?php if ($layout !== 'without_sender') : ?>
                                <div class="tipax-label-block">
                                    <span class="tipax-label-title">فرستنده</span>
                                    <div class="tipax-label-text"><?php echo esc_html($label['sender']['name']); ?></div>
                                    <div class="tipax-label-subtext">
                                        <?php echo esc_html($label['sender']['address']); ?>
                                        <?php if ($layout === 'full' && $label['sender']['phone'] !== '') : ?>
                                            | تلفن: <span dir="ltr"><?php echo esc_html($label['sender']['phone']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="tipax-label-block">
                                <span class="tipax-label-title">گیرنده</span>
                                <div class="tipax-label-text"><?php echo esc_html($label['receiver']['address']); ?></div>
                                <div class="tipax-label-subtext">
                                    <?php echo esc_html($label['receiver']['name']); ?>
                                    <?php if ($label['receiver']['phone'] !== '') : ?>
                                        | تلفن: <span dir="ltr"><?php echo esc_html($label['receiver']['phone']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="tipax-label-block tipax-label-postcode-block">
                                <span class="tipax-label-title">کد پستی گیرنده</span>
                                <div class="tipax-label-text tipax-label-postcode">
                                    <?php echo esc_html($label['receiver']['postcode'] !== '' ? $label['receiver']['postcode'] : 'درج نشده'); ?>
                                </div>
                            </div>
                        </div>

                        <aside class="tipax-label-barcode-panel">
                            <span class="tipax-label-barcode-kicker">TIPAX TRACKING</span>
                            <?php if ($label['barcode_url'] !== '') : ?>
                                <img class="tipax-label-barcode-image" src="<?php echo esc_attr($label['barcode_url']); ?>" alt="<?php echo esc_attr($label['tracking_code']); ?>">
                            <?php else : ?>
                                <div class="tipax-label-barcode-error">ساخت بارکد انجام نشد</div>
                            <?php endif; ?>
                            <div class="tipax-label-code"><?php echo esc_html($label['tracking_code']); ?></div>
                            <div class="tipax-label-package">
                                مرسوله <?php echo esc_html($label['package_number']); ?> از <?php echo esc_html($label['package_total']); ?>
                            </div>
                        </aside>
                    </div>

                    <footer class="tipax-label-footer">
                        <span>تاریخ ثبت: <?php echo esc_html($label['order_date'] !== '' ? $label['order_date'] : '—'); ?></span>
                        <strong>این سفارش برای ارسال با تیپاکس ثبت شده است</strong>
                    </footer>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>
    </main>

    <?php if ($auto_print) : ?>
        <script>
            window.addEventListener('load', function () {
                var images = Array.prototype.slice.call(document.images || []);
                Promise.all(images.map(function (image) {
                    if (image.complete) {
                        return Promise.resolve();
                    }
                    return new Promise(function (resolve) {
                        image.addEventListener('load', resolve, { once: true });
                        image.addEventListener('error', resolve, { once: true });
                    });
                })).then(function () {
                    window.setTimeout(function () {
                        window.focus();
                        window.print();
                    }, 250);
                });
            });
        </script>
    <?php endif; ?>
</body>
</html>
        <?php
    }
}

function tipax_label_manager()
{
    return Tipax_Label_Manager::instance();
}

tipax_label_manager();
