<?php
if (!defined('ABSPATH')) {
    exit;
}

class Tipax_Same_Day_Map
{
    private const NONCE_ACTION = 'tipax_same_day_location';
    private const FRAME_NONCE_ACTION = 'tipax_same_day_map_frame';
    private const STORE_API_NAMESPACE = 'tipax-same-day-location';
    private const MAP_TOKEN_TTL = 3600;
    private const MAP_IMAGE_CACHE_TTL = 86400;
    private const MAP_IMAGE_CACHE_LIMIT = 250;
    private const MAP_MIN_ZOOM = 11;
    private const MAP_MAX_ZOOM = 18;

    public function __construct()
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('woocommerce_after_order_notes', [$this, 'render_classic_checkout']);
        add_filter('render_block_woocommerce/checkout-actions-block', [$this, 'render_block_checkout'], 20, 1);

        add_action('wp_ajax_tipax_save_same_day_location', [$this, 'save_location_ajax']);
        add_action('wp_ajax_nopriv_tipax_save_same_day_location', [$this, 'save_location_ajax']);
        add_action('wp_ajax_tipax_same_day_map_frame', [$this, 'render_map_frame']);
        add_action('wp_ajax_nopriv_tipax_same_day_map_frame', [$this, 'render_map_frame']);
        add_action('wp_ajax_tipax_same_day_static_map', [$this, 'serve_static_map']);
        add_action('wp_ajax_nopriv_tipax_same_day_static_map', [$this, 'serve_static_map']);

        add_action('woocommerce_after_checkout_validation', [$this, 'validate_classic_checkout'], 20, 2);
        add_action('woocommerce_checkout_create_order', [$this, 'store_classic_order_location'], 20, 2);
        add_action('woocommerce_checkout_update_order_meta', [$this, 'store_classic_order_location_fallback'], 20, 1);
        add_action('woocommerce_store_api_checkout_update_order_from_request', [$this, 'handle_store_api_order'], 20, 2);
        add_action('woocommerce_store_api_checkout_order_processed', [$this, 'validate_processed_store_api_order'], 5, 1);
        if (did_action('woocommerce_blocks_loaded')) {
            $this->register_store_api_schema();
        } else {
            add_action('woocommerce_blocks_loaded', [$this, 'register_store_api_schema']);
        }
    }

    public function enqueue_assets()
    {
        // Custom checkout builders are not always reported by is_checkout().
        if (!tipax_is_same_day_enabled() || is_admin()) {
            return;
        }

        $script_path = TSM_DIR . 'assets/frontend/js/same-day-map.js';
        $style_path = TSM_DIR . 'assets/frontend/css/same-day-map.css';
        $script_version = TSM_VERSION . (file_exists($script_path) ? '.' . filemtime($script_path) : '');
        $style_version = TSM_VERSION . (file_exists($style_path) ? '.' . filemtime($style_path) : '');

        wp_enqueue_style(
            'tipax-same-day-map',
            TSM_ASSETS_URL . 'frontend/css/same-day-map.css',
            [],
            $style_version
        );
        wp_enqueue_script(
            'tipax-same-day-map',
            TSM_ASSETS_URL . 'frontend/js/same-day-map.js',
            ['jquery', 'wp-data'],
            $script_version,
            true
        );

        wp_localize_script('tipax-same-day-map', 'tipaxSameDayMap', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce(self::NONCE_ACTION),
            'frameUrl' => add_query_arg(
                [
                    'action' => 'tipax_same_day_map_frame',
                    'nonce' => wp_create_nonce(self::FRAME_NONCE_ACTION),
                ],
                admin_url('admin-ajax.php')
            ),
            'rateId' => 'tipax_shipping_method' . tipax_get_same_day_service_id(),
            'serverSelected' => tipax_checkout_uses_same_day_rate(),
            'location' => tipax_get_same_day_session_location(),
            'messages' => [
                'title' => 'موقعیت دقیق گیرنده',
                'description' => 'برای ارسال همان روز، محل تحویل را روی نقشه مشخص کنید.',
                'required' => 'انتخاب موقعیت روی نقشه برای سرویس همان روز الزامی است.',
                'selected' => 'موقعیت گیرنده ثبت شد.',
                'openMap' => 'انتخاب موقعیت روی نقشه',
                'editMap' => 'ویرایش موقعیت',
                'confirm' => 'تأیید موقعیت',
                'close' => 'بستن',
                'saving' => 'در حال ثبت موقعیت...',
                'saveError' => 'ثبت موقعیت ناموفق بود. دوباره نقطه را روی نقشه انتخاب کنید.',
                'mapError' => 'بارگذاری نقشه نشان ناموفق بود. صفحه را تازه‌سازی کنید.',
                'geolocation' => 'موقعیت فعلی من',
                'geolocationError' => 'دسترسی به موقعیت فعلی ممکن نبود. نقطه را روی نقشه انتخاب کنید.',
                'outsideTehran' => 'موقعیت گیرنده برای سرویس همان روز باید در محدوده تهران باشد.',
                'coordinates' => 'مختصات انتخاب‌شده',
                'phoneRequired' => 'شماره موبایل برای سرویس همان روز الزامی است.',
            ],
        ]);
    }

    public function render_classic_checkout()
    {
        if (tipax_is_same_day_enabled()) {
            echo $this->get_checkout_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
    }

    public function render_block_checkout($block_content)
    {
        if (!tipax_is_same_day_enabled() || is_admin()) {
            return $block_content;
        }

        return $this->get_checkout_markup() . $block_content;
    }

    private function get_checkout_markup()
    {
        $location = tipax_get_same_day_session_location();
        $latitude = $location['latitude'] ?? '';
        $longitude = $location['longitude'] ?? '';
        $address = $location['address'] ?? '';

        ob_start();
        ?>
        <section class="tipax-same-day-location" data-tipax-same-day-location hidden dir="rtl" aria-live="polite">
            <div class="tipax-same-day-location__summary">
                <span class="tipax-same-day-location__icon" aria-hidden="true">⌖</span>
                <div class="tipax-same-day-location__content">
                    <span class="tipax-same-day-location__eyebrow">ارسال همان روز</span>
                    <h3>موقعیت دقیق گیرنده</h3>
                    <p data-tipax-location-status>برای ارسال همان روز، محل تحویل را روی نقشه مشخص کنید.</p>
                </div>
                <button type="button" class="tipax-same-day-location__open" data-tipax-open-map>
                    <span data-tipax-open-map-label>انتخاب موقعیت روی نقشه</span>
                </button>
            </div>
            <input type="hidden" name="tipax_same_day_latitude" data-tipax-latitude value="<?php echo esc_attr($latitude); ?>">
            <input type="hidden" name="tipax_same_day_longitude" data-tipax-longitude value="<?php echo esc_attr($longitude); ?>">
            <input type="hidden" name="tipax_same_day_map_address" data-tipax-map-address value="<?php echo esc_attr($address); ?>">
            <dialog class="tipax-same-day-modal" data-tipax-map-modal aria-labelledby="tipax-same-day-modal-title">
                <div class="tipax-same-day-modal__dialog">
                    <div class="tipax-same-day-modal__header">
                        <div>
                            <span class="tipax-same-day-location__eyebrow">ارسال همان روز</span>
                            <h3 id="tipax-same-day-modal-title">انتخاب موقعیت گیرنده</h3>
                            <p>نقشه را جابه‌جا کنید تا نشانگر روی محل دقیق تحویل قرار بگیرد.</p>
                        </div>
                        <button type="button" class="tipax-same-day-modal__close" data-tipax-close-map aria-label="بستن">×</button>
                    </div>
                    <div class="tipax-same-day-location__map-shell" data-tipax-map-shell>
                        <div class="tipax-same-day-location__loading">در حال بارگذاری نقشه نشان...</div>
                    </div>
                    <div class="tipax-same-day-modal__footer">
                        <button type="button" class="tipax-same-day-location__locate" data-tipax-geolocate>
                            <span aria-hidden="true">◎</span>
                            موقعیت فعلی من
                        </button>
                        <div class="tipax-same-day-location__result" data-tipax-location-result>
                            <span class="tipax-same-day-location__result-dot" aria-hidden="true"></span>
                            <span data-tipax-location-status>نقطه دقیق تحویل را روی نقشه انتخاب کنید.</span>
                        </div>
                        <button type="button" class="tipax-same-day-modal__confirm" data-tipax-confirm-map disabled>تأیید موقعیت</button>
                    </div>
                </div>
            </dialog>
        </section>
        <?php
        return (string) ob_get_clean();
    }

    public function save_location_ajax()
    {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');

        if (!tipax_is_same_day_enabled()) {
            wp_send_json_error(['message' => 'سرویس همان روز غیرفعال است.'], 403);
        }

        if (!empty($_POST['clear'])) {
            if (function_exists('WC') && WC() && WC()->session) {
                WC()->session->__unset('tipax_same_day_location');
            }
            wp_send_json_success(['cleared' => true]);
        }

        $validated = tipax_validate_same_day_location(
            isset($_POST['latitude']) ? wp_unslash($_POST['latitude']) : null,
            isset($_POST['longitude']) ? wp_unslash($_POST['longitude']) : null
        );
        if (is_wp_error($validated)) {
            wp_send_json_error(['message' => $validated->get_error_message()], 400);
        }

        $validated['address'] = '';
        $stored = tipax_set_same_day_session_location($validated);
        if (is_wp_error($stored) || $stored === false) {
            wp_send_json_error(['message' => 'ذخیره موقعیت در نشست ووکامرس ممکن نبود.'], 500);
        }

        wp_send_json_success([
            'latitude' => $stored['latitude'],
            'longitude' => $stored['longitude'],
            'address' => $stored['address'],
        ]);
    }

    public function validate_classic_checkout($data, $errors)
    {
        if (!tipax_is_same_day_enabled() || !tipax_checkout_uses_same_day_rate()) {
            return;
        }

        if ($this->get_classic_checkout_mobile($data) === '') {
            $errors->add(
                'tipax_same_day_phone_required',
                'شماره موبایل برای سرویس همان روز الزامی است.'
            );
        }

        $location = tipax_get_same_day_location_from_request();
        $validated = tipax_validate_same_day_location(
            $location['latitude'] ?? null,
            $location['longitude'] ?? null
        );
        if (is_wp_error($validated)) {
            $errors->add($validated->get_error_code(), $validated->get_error_message());
            return;
        }

        $validated['address'] = sanitize_text_field((string) ($location['address'] ?? ''));
        tipax_set_same_day_session_location($validated);
    }

    public function store_classic_order_location($order, $data)
    {
        if (!$order instanceof WC_Order || !tipax_checkout_uses_same_day_rate()) {
            return;
        }

        $location = tipax_get_same_day_location_from_request();
        if (!empty($location)) {
            tipax_store_same_day_location_on_order($order, $location);
        }
    }

    public function store_classic_order_location_fallback($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order || !tipax_order_uses_same_day_service($order)) {
            return;
        }

        $location = tipax_get_same_day_location_from_request();
        if (!empty($location) && !is_wp_error(tipax_store_same_day_location_on_order($order, $location))) {
            $order->save();
        }
    }

    public function handle_store_api_order($order, $request)
    {
        if (!$order instanceof WC_Order || !$request instanceof WP_REST_Request) {
            return;
        }

        $location = tipax_get_same_day_location_from_request($request);
        if (!empty($location)) {
            tipax_set_same_day_session_location($location);
        }

        if (!tipax_order_uses_same_day_service($order)) {
            return;
        }

        if (!$this->order_has_recipient_mobile($order)) {
            if (strtoupper($request->get_method()) === 'POST') {
                $this->throw_store_api_error(new WP_Error(
                    'tipax_same_day_phone_required',
                    'شماره موبایل برای سرویس همان روز الزامی است.'
                ));
            }
            return;
        }

        $validated = tipax_validate_same_day_location(
            $location['latitude'] ?? null,
            $location['longitude'] ?? null
        );
        if (is_wp_error($validated)) {
            if (strtoupper($request->get_method()) === 'POST') {
                $this->throw_store_api_error($validated);
            }
            return;
        }

        $validated['address'] = sanitize_text_field((string) ($location['address'] ?? ''));
        tipax_store_same_day_location_on_order($order, $validated);
        $order->save();
    }

    public function validate_processed_store_api_order($order)
    {
        if (!$order instanceof WC_Order || !tipax_order_uses_same_day_service($order)) {
            return;
        }

        if (!$this->order_has_recipient_mobile($order)) {
            $this->throw_store_api_error(new WP_Error(
                'tipax_same_day_phone_required',
                'شماره موبایل برای سرویس همان روز الزامی است.'
            ));
        }

        $location = tipax_get_order_same_day_location($order);
        if (is_wp_error($location)) {
            $session_location = tipax_get_same_day_session_location();
            if (!empty($session_location)) {
                $stored = tipax_store_same_day_location_on_order($order, $session_location);
                if (!is_wp_error($stored)) {
                    $order->save();
                    return;
                }
            }
            $this->throw_store_api_error($location);
        }
    }

    private function throw_store_api_error(WP_Error $error)
    {
        $class = 'Automattic\\WooCommerce\\StoreApi\\Exceptions\\RouteException';
        if (class_exists($class)) {
            throw new $class($error->get_error_code(), $error->get_error_message(), 400);
        }

        if (function_exists('wc_add_notice')) {
            wc_add_notice($error->get_error_message(), 'error');
        }
    }

    private function get_classic_checkout_mobile($data)
    {
        $candidates = [
            $data['billing_phone'] ?? '',
            $data['shipping_phone'] ?? '',
            isset($_POST['phone_field']) ? wp_unslash($_POST['phone_field']) : '',
        ];

        foreach ($candidates as $candidate) {
            $mobile = trim(sanitize_text_field((string) $candidate));
            if ($mobile !== '') {
                return $mobile;
            }
        }

        return '';
    }

    private function order_has_recipient_mobile(WC_Order $order)
    {
        $candidates = [$order->get_billing_phone()];
        if (is_callable([$order, 'get_shipping_phone'])) {
            $candidates[] = $order->get_shipping_phone();
        }
        $candidates[] = $order->get_meta('phone', true);

        foreach ($candidates as $candidate) {
            if (trim((string) $candidate) !== '') {
                return true;
            }
        }

        return false;
    }

    public function register_store_api_schema()
    {
        if (!function_exists('woocommerce_store_api_register_endpoint_data')) {
            return;
        }

        $schema_class = 'Automattic\\WooCommerce\\StoreApi\\Schemas\\V1\\CheckoutSchema';
        if (!class_exists($schema_class)) {
            return;
        }

        woocommerce_store_api_register_endpoint_data([
            'endpoint' => $schema_class::IDENTIFIER,
            'namespace' => self::STORE_API_NAMESPACE,
            'data_callback' => [$this, 'get_store_api_location_data'],
            'schema_callback' => [$this, 'get_store_api_location_schema'],
            'schema_type' => ARRAY_A,
        ]);
    }

    public function get_store_api_location_data()
    {
        return tipax_get_same_day_session_location();
    }

    public function get_store_api_location_schema()
    {
        return [
            'latitude' => [
                'description' => 'Tipax Same Day destination latitude.',
                'type' => 'number',
                'context' => ['view', 'edit'],
                'readonly' => false,
                'optional' => true,
            ],
            'longitude' => [
                'description' => 'Tipax Same Day destination longitude.',
                'type' => 'number',
                'context' => ['view', 'edit'],
                'readonly' => false,
                'optional' => true,
            ],
            'address' => [
                'description' => 'Neshan reverse-geocoded destination address.',
                'type' => 'string',
                'context' => ['view', 'edit'],
                'readonly' => false,
                'optional' => true,
            ],
        ];
    }

    public function render_map_frame()
    {
        $nonce = isset($_GET['nonce']) ? sanitize_text_field(wp_unslash($_GET['nonce'])) : '';
        if (!tipax_is_same_day_enabled() || !wp_verify_nonce($nonce, self::FRAME_NONCE_ACTION)) {
            status_header(403);
            exit;
        }

        nocache_headers();
        header('Content-Type: text/html; charset=' . get_option('blog_charset', 'UTF-8'));
        header('X-Robots-Tag: noindex, nofollow', true);
        header('X-Frame-Options: SAMEORIGIN', true);
        header("Content-Security-Policy: default-src 'none'; img-src 'self' data:; style-src 'unsafe-inline'; script-src 'unsafe-inline'; frame-ancestors 'self'; base-uri 'none'; form-action 'none'", true);

        $map_configured = tipax_get_neshan_static_map_api_key() !== '';
        $initial_latitude = tipax_normalize_same_day_coordinate($_GET['latitude'] ?? null);
        $initial_longitude = tipax_normalize_same_day_coordinate($_GET['longitude'] ?? null);
        $initial_location = (
            $initial_latitude !== null
            && $initial_longitude !== null
            && tipax_same_day_location_is_in_tehran_area($initial_latitude, $initial_longitude)
        ) ? [
            'latitude' => $initial_latitude,
            'longitude' => $initial_longitude,
        ] : null;
        $map_proxy_url = $map_configured
            ? add_query_arg([
                'action' => 'tipax_same_day_static_map',
                'token' => $this->create_map_proxy_token(),
            ], admin_url('admin-ajax.php'))
            : '';
        ?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
    <title>انتخاب موقعیت گیرنده</title>
    <style>
        *{box-sizing:border-box}
        html,body,#tipax-neshan-map{width:100%;height:100%;margin:0;overflow:hidden;background:#e6eee9}
        body{font-family:Tahoma,sans-serif;user-select:none}
        #tipax-neshan-map{position:relative;cursor:grab;touch-action:none}
        #tipax-neshan-map.is-dragging{cursor:grabbing}
        .tipax-map-image{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;pointer-events:none;-webkit-user-drag:none;will-change:transform;transition:opacity .18s ease}
        .tipax-map-loading{position:absolute;inset:0;z-index:700;display:none;place-items:center;pointer-events:none;background:rgba(238,243,239,.22)}
        .tipax-map-loading:after{content:"";width:24px;height:24px;border:3px solid rgba(8,123,82,.2);border-top-color:#087b52;border-radius:50%;animation:tipax-spin .7s linear infinite}
        #tipax-neshan-map.is-loading .tipax-map-loading{display:grid}
        @keyframes tipax-spin{to{transform:rotate(360deg)}}
        .tipax-map-pin{position:absolute;z-index:900;top:50%;left:50%;width:34px;height:42px;transform:translate(-50%,-100%);pointer-events:none;filter:drop-shadow(0 6px 5px rgba(20,61,44,.25))}
        .tipax-map-pin:before{content:"";position:absolute;top:0;left:3px;width:28px;height:28px;border:3px solid #fff;border-radius:50% 50% 50% 0;background:#087b52;transform:rotate(-45deg);box-sizing:border-box}
        .tipax-map-pin:after{content:"";position:absolute;top:9px;left:13px;width:8px;height:8px;border-radius:50%;background:#fff}
        .tipax-map-shadow{position:absolute;z-index:899;top:50%;left:50%;width:18px;height:6px;border-radius:50%;background:rgba(0,0,0,.2);transform:translate(-50%,3px);pointer-events:none}
        .tipax-map-controls{position:absolute;z-index:950;top:12px;left:12px;display:grid;gap:6px}
        .tipax-map-controls button{width:36px;height:36px;padding:0;border:1px solid rgba(16,54,41,.16);border-radius:10px;background:#fff;color:#173c2f;font:700 22px/1 Tahoma,sans-serif;cursor:pointer;box-shadow:0 5px 16px rgba(17,55,42,.12)}
        .tipax-map-controls button:focus-visible{outline:3px solid rgba(8,123,82,.3);outline-offset:2px}
        .tipax-map-brand{position:absolute;z-index:950;right:9px;bottom:8px;padding:4px 7px;border-radius:7px;background:rgba(255,255,255,.9);color:#315447;font-size:9px;box-shadow:0 2px 8px rgba(17,55,42,.08)}
        .tipax-map-error{position:absolute;inset:0;z-index:1000;display:none;place-items:center;padding:24px;text-align:center;color:#7a241d;background:#fff4f2;font-size:13px;line-height:1.8}
    </style>
</head>
<body>
    <div id="tipax-neshan-map" role="application" aria-label="نقشه انتخاب موقعیت گیرنده">
        <img class="tipax-map-image" id="tipax-map-image" alt="" draggable="false">
        <div class="tipax-map-loading" aria-hidden="true"></div>
        <div class="tipax-map-shadow" aria-hidden="true"></div>
        <div class="tipax-map-pin" aria-hidden="true"></div>
        <div class="tipax-map-controls" aria-label="کنترل بزرگنمایی نقشه">
            <button type="button" id="tipax-map-zoom-in" aria-label="بزرگنمایی">+</button>
            <button type="button" id="tipax-map-zoom-out" aria-label="کوچک‌نمایی">−</button>
        </div>
        <span class="tipax-map-brand">نقشه نشان</span>
    </div>
    <div class="tipax-map-error" id="tipax-map-error">بارگذاری نقشه نشان ناموفق بود.</div>
    <script>
    (function () {
        'use strict';
        var targetOrigin = window.location.origin;
        var proxyUrl = <?php echo wp_json_encode($map_proxy_url); ?>;
        var configured = <?php echo $map_configured ? 'true' : 'false'; ?>;
        var viewport = document.getElementById('tipax-neshan-map');
        var mapImage = document.getElementById('tipax-map-image');
        var errorBox = document.getElementById('tipax-map-error');
        var initialLocation = <?php echo wp_json_encode($initial_location); ?>;
        var center = initialLocation || { latitude: 35.6997, longitude: 51.3381 };
        var zoom = initialLocation ? 16 : 14;
        var minZoom = <?php echo (int) self::MAP_MIN_ZOOM; ?>;
        var maxZoom = <?php echo (int) self::MAP_MAX_ZOOM; ?>;
        var requestSequence = 0;
        var ready = false;
        var dragState = null;
        var resizeTimer = 0;
        var pendingMapUrl = '';
        var loadedMapUrl = '';

        try {
            if (document.referrer) {
                targetOrigin = new URL(document.referrer).origin;
            }
        } catch (error) {
            targetOrigin = window.location.origin;
        }

        function notify(type, data) {
            var payload = data || {};
            payload.source = 'tipax-same-day-map';
            payload.type = type;
            window.parent.postMessage(payload, targetOrigin);
        }

        function clamp(value, minimum, maximum) {
            return Math.min(maximum, Math.max(minimum, value));
        }

        function normalizeCenter(location) {
            return {
                latitude: clamp(Number(location.latitude), 35.3, 36.1),
                longitude: clamp(Number(location.longitude), 50.8, 52.0)
            };
        }

        function worldPoint(location, level) {
            var size = 256 * Math.pow(2, level);
            var latitude = clamp(location.latitude, -85.05112878, 85.05112878);
            var sinLatitude = Math.sin(latitude * Math.PI / 180);
            return {
                x: (location.longitude + 180) / 360 * size,
                y: (0.5 - Math.log((1 + sinLatitude) / (1 - sinLatitude)) / (4 * Math.PI)) * size,
                size: size
            };
        }

        function locationFromWorld(point) {
            var x = ((point.x % point.size) + point.size) % point.size;
            var longitude = x / point.size * 360 - 180;
            var mercator = Math.PI - 2 * Math.PI * point.y / point.size;
            var latitude = 180 / Math.PI * Math.atan(Math.sinh(mercator));
            return normalizeCenter({ latitude: latitude, longitude: longitude });
        }

        function shiftedCenter(start, deltaX, deltaY) {
            var point = worldPoint(start, zoom);
            point.x += deltaX;
            point.y += deltaY;
            return locationFromWorld(point);
        }

        function mapUrl() {
            var url = new URL(proxyUrl, window.location.href);
            url.searchParams.set('latitude', center.latitude.toFixed(7));
            url.searchParams.set('longitude', center.longitude.toFixed(7));
            url.searchParams.set('zoom', String(zoom));
            url.searchParams.set('width', String(clamp(Math.round(viewport.clientWidth || 640), 320, 1200)));
            url.searchParams.set('height', String(clamp(Math.round(viewport.clientHeight || 360), 220, 720)));
            return url.toString();
        }

        function showError() {
            errorBox.style.display = 'grid';
            viewport.classList.remove('is-loading');
            notify('error');
        }

        function loadMap(commit) {
            if (!configured || !proxyUrl) {
                errorBox.textContent = 'پیکربندی امن نقشه روی سرور کامل نشده است.';
                showError();
                return;
            }

            var nextMapUrl = mapUrl();
            if (nextMapUrl === pendingMapUrl) {
                return;
            }
            if (nextMapUrl === loadedMapUrl && mapImage.complete && mapImage.naturalWidth > 0) {
                if (commit) {
                    notify('location', center);
                }
                return;
            }

            var sequence = ++requestSequence;
            var preload = new Image();
            pendingMapUrl = nextMapUrl;
            viewport.classList.add('is-loading');
            errorBox.style.display = 'none';
            preload.onload = function () {
                if (sequence !== requestSequence) {
                    return;
                }
                pendingMapUrl = '';
                loadedMapUrl = preload.src;
                mapImage.src = preload.src;
                mapImage.style.transform = 'translate3d(0,0,0)';
                viewport.classList.remove('is-loading', 'is-dragging');
                if (!ready) {
                    ready = true;
                    notify('ready', center);
                }
                if (commit) {
                    notify('location', center);
                }
            };
            preload.onerror = function () {
                if (sequence === requestSequence) {
                    pendingMapUrl = '';
                    showError();
                }
            };
            preload.src = nextMapUrl;
        }

        function changeZoom(delta) {
            var nextZoom = clamp(zoom + delta, minZoom, maxZoom);
            if (nextZoom === zoom) {
                return;
            }
            zoom = nextZoom;
            loadMap(true);
        }

        viewport.addEventListener('pointerdown', function (event) {
            if (event.button !== 0 || event.target.closest('.tipax-map-controls, .tipax-map-brand')) {
                return;
            }
            viewport.setPointerCapture(event.pointerId);
            dragState = {
                pointerId: event.pointerId,
                startX: event.clientX,
                startY: event.clientY,
                center: { latitude: center.latitude, longitude: center.longitude }
            };
            viewport.classList.add('is-dragging');
        });

        viewport.addEventListener('pointermove', function (event) {
            if (!dragState || dragState.pointerId !== event.pointerId) {
                return;
            }
            var deltaX = event.clientX - dragState.startX;
            var deltaY = event.clientY - dragState.startY;
            mapImage.style.transform = 'translate3d(' + deltaX + 'px,' + deltaY + 'px,0)';
        });

        viewport.addEventListener('pointerup', function (event) {
            if (!dragState || dragState.pointerId !== event.pointerId) {
                return;
            }
            var deltaX = event.clientX - dragState.startX;
            var deltaY = event.clientY - dragState.startY;
            var moved = Math.sqrt(deltaX * deltaX + deltaY * deltaY) > 5;
            if (moved) {
                center = shiftedCenter(dragState.center, -deltaX, -deltaY);
            } else {
                var rect = viewport.getBoundingClientRect();
                center = shiftedCenter(
                    dragState.center,
                    event.clientX - rect.left - rect.width / 2,
                    event.clientY - rect.top - rect.height / 2
                );
            }
            dragState = null;
            loadMap(true);
        });

        viewport.addEventListener('pointercancel', function () {
            dragState = null;
            mapImage.style.transform = 'translate3d(0,0,0)';
            viewport.classList.remove('is-dragging');
        });

        document.getElementById('tipax-map-zoom-in').addEventListener('click', function () {
            changeZoom(1);
        });
        document.getElementById('tipax-map-zoom-out').addEventListener('click', function () {
            changeZoom(-1);
        });

        window.addEventListener('message', function (event) {
            if (event.origin !== targetOrigin || !event.data || event.data.source !== 'tipax-same-day-checkout') {
                return;
            }
            if (event.data.type === 'set-location') {
                var latitude = Number(event.data.latitude);
                var longitude = Number(event.data.longitude);
                if (isFinite(latitude) && isFinite(longitude)) {
                    center = normalizeCenter({ latitude: latitude, longitude: longitude });
                    zoom = Math.max(zoom, 16);
                    loadMap(Boolean(event.data.commit));
                }
            }
            if (event.data.type === 'invalidate-size') {
                window.clearTimeout(resizeTimer);
                resizeTimer = window.setTimeout(function () { loadMap(false); }, 100);
            }
        });

        if (window.ResizeObserver) {
            new ResizeObserver(function () {
                if (!ready) {
                    return;
                }
                window.clearTimeout(resizeTimer);
                resizeTimer = window.setTimeout(function () { loadMap(false); }, 180);
            }).observe(viewport);
        }

        loadMap(false);
    }());
    </script>
</body>
</html>
        <?php
        exit;
    }

    public function serve_static_map()
    {
        $token = isset($_GET['token']) ? sanitize_text_field(wp_unslash($_GET['token'])) : '';
        if (!tipax_is_same_day_enabled() || !$this->verify_map_proxy_token($token)) {
            $this->send_static_map_error(403);
        }

        if (!$this->consume_map_rate_limit($token)) {
            $this->send_static_map_error(429);
        }

        $latitude = tipax_normalize_same_day_coordinate($_GET['latitude'] ?? null);
        $longitude = tipax_normalize_same_day_coordinate($_GET['longitude'] ?? null);
        $zoom = isset($_GET['zoom']) ? (int) $_GET['zoom'] : 14;
        $width = isset($_GET['width']) ? (int) $_GET['width'] : 640;
        $height = isset($_GET['height']) ? (int) $_GET['height'] : 360;

        if (
            $latitude === null
            || $longitude === null
            || !tipax_same_day_location_is_in_tehran_area($latitude, $longitude)
            || $zoom < self::MAP_MIN_ZOOM
            || $zoom > self::MAP_MAX_ZOOM
            || $width < 320
            || $width > 1200
            || $height < 220
            || $height > 720
        ) {
            $this->send_static_map_error(400);
        }

        $cache_file = $this->get_static_map_cache_file($latitude, $longitude, $zoom, $width, $height);
        $cached_body = $this->read_cached_static_map($cache_file);
        if ($cached_body !== null) {
            $this->send_static_map_response($cached_body);
        }

        $api_key = tipax_get_neshan_static_map_api_key();
        if ($api_key === '') {
            $this->send_static_map_error(503);
        }

        $url = add_query_arg([
            'type' => 'day',
            'zoom' => $zoom,
            'width' => $width,
            'height' => $height,
            'latitude' => number_format($latitude, 7, '.', ''),
            'longitude' => number_format($longitude, 7, '.', ''),
            'key' => $api_key,
        ], 'https://api.neshan.org/v5/static');
        $response = wp_remote_get($url, [
            'timeout' => 12,
            'redirection' => 0,
            'sslverify' => true,
            'headers' => ['Accept' => 'image/png'],
        ]);
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            $this->send_static_map_error(502);
        }

        $body = wp_remote_retrieve_body($response);
        $content_type = strtolower((string) wp_remote_retrieve_header($response, 'content-type'));
        if (!is_string($body) || strlen($body) < 8 || substr($body, 0, 8) !== "\x89PNG\r\n\x1a\n" || strpos($content_type, 'image/png') !== 0) {
            $this->send_static_map_error(502);
        }

        $this->store_cached_static_map($cache_file, $body);
        $this->send_static_map_response($body);
    }

    private function get_static_map_cache_file($latitude, $longitude, $zoom, $width, $height)
    {
        $uploads = wp_upload_dir(null, false);
        if (!empty($uploads['error']) || empty($uploads['basedir'])) {
            return '';
        }

        $directory = trailingslashit($uploads['basedir']) . 'tipax-map-cache';
        if (!is_dir($directory) && !wp_mkdir_p($directory)) {
            return '';
        }

        $cache_key = implode('|', [
            number_format((float) $latitude, 7, '.', ''),
            number_format((float) $longitude, 7, '.', ''),
            (int) $zoom,
            (int) $width,
            (int) $height,
            'day',
        ]);

        return trailingslashit($directory) . hash('sha256', $cache_key) . '.png';
    }

    private function read_cached_static_map($cache_file)
    {
        if (
            $cache_file === ''
            || !is_file($cache_file)
            || (time() - (int) @filemtime($cache_file)) > self::MAP_IMAGE_CACHE_TTL
        ) {
            return null;
        }

        $body = @file_get_contents($cache_file);
        if (!is_string($body) || strlen($body) < 8 || substr($body, 0, 8) !== "\x89PNG\r\n\x1a\n") {
            return null;
        }

        return $body;
    }

    private function store_cached_static_map($cache_file, $body)
    {
        if ($cache_file === '' || !is_string($body)) {
            return;
        }

        $temporary_file = $cache_file . '.' . wp_generate_password(8, false, false) . '.tmp';
        if (@file_put_contents($temporary_file, $body, LOCK_EX) === false) {
            return;
        }

        if (!@rename($temporary_file, $cache_file)) {
            @unlink($temporary_file);
            return;
        }

        $files = glob(dirname($cache_file) . DIRECTORY_SEPARATOR . '*.png');
        if (!is_array($files) || count($files) <= self::MAP_IMAGE_CACHE_LIMIT) {
            return;
        }

        usort($files, static function ($left, $right) {
            return (int) @filemtime($left) <=> (int) @filemtime($right);
        });
        foreach (array_slice($files, 0, count($files) - self::MAP_IMAGE_CACHE_LIMIT) as $old_file) {
            @unlink($old_file);
        }
    }

    private function send_static_map_response($body)
    {
        status_header(200);
        header('Content-Type: image/png');
        header('Content-Length: ' . strlen($body));
        header('Cache-Control: private, max-age=3600');
        header('X-Content-Type-Options: nosniff');
        echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        exit;
    }

    private function create_map_proxy_token()
    {
        $expires = time() + self::MAP_TOKEN_TTL;
        $random = wp_generate_password(20, false, false);
        $payload = $expires . '|' . $random;
        $signature = hash_hmac('sha256', $payload, wp_salt('nonce'));
        return $expires . '.' . $random . '.' . $signature;
    }

    private function verify_map_proxy_token($token)
    {
        $parts = explode('.', (string) $token);
        if (count($parts) !== 3 || !ctype_digit($parts[0]) || !preg_match('/^[A-Za-z0-9]{20}$/', $parts[1])) {
            return false;
        }

        $expires = (int) $parts[0];
        if ($expires < time() || $expires > time() + self::MAP_TOKEN_TTL + 60) {
            return false;
        }

        $expected = hash_hmac('sha256', $parts[0] . '|' . $parts[1], wp_salt('nonce'));
        return hash_equals($expected, $parts[2]);
    }

    private function consume_map_rate_limit($token)
    {
        $bucket = (string) floor(time() / 300);
        $key = 'tipax_map_rl_' . substr(hash_hmac('sha256', (string) $token . '|' . $bucket, wp_salt('auth')), 0, 32);
        $count = (int) get_transient($key);
        if ($count >= 150) {
            return false;
        }

        set_transient($key, $count + 1, 6 * MINUTE_IN_SECONDS);
        return true;
    }

    private function send_static_map_error($status)
    {
        status_header((int) $status);
        header('Content-Type: text/plain; charset=UTF-8');
        header('Cache-Control: no-store, max-age=0');
        header('X-Content-Type-Options: nosniff');
        echo 'Map unavailable.';
        exit;
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

        $elementor_data = get_post_meta($page_id, '_elementor_data', true);
        return is_string($elementor_data)
            && (
                strpos($elementor_data, 'woocommerce_checkout') !== false
                || strpos($elementor_data, 'woocommerce-checkout') !== false
            );
    }
}

new Tipax_Same_Day_Map();
