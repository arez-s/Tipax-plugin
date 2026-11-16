<?php
/** One-time, site-bound service acceptance and minimal lifecycle reporting. */
if (!defined('ABSPATH')) {
    exit;
}

class Tipax_Hub_Client
{
    const BASE_URL = 'https://tipaxplugin.ir';
    const CONSENT_VERSION = '2.0';
    const PREFERENCES = 'tipax_hub_preferences';
    const IDENTITY = 'tipax_hub_identity';
    const REGISTRATION_LOCK = 'tipax_hub_registration_lock';
    const SYNC_HOOK = 'tipax_hub_background_sync';
    const EVENT_HOOK = 'tipax_hub_lifecycle_sync';
    private static $instance;
    private $stopping = false;

    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('init', [$this, 'maybe_schedule']);
        add_action(self::SYNC_HOOK, [$this, 'sync']);
        add_action(self::EVENT_HOOK, [$this, 'sync']);
        add_action('upgrader_process_complete', [$this, 'upgraded'], 10, 2);
        add_action('admin_post_tipax_hub_save', [$this, 'save']);
        add_action('admin_init', [$this, 'privacy_text']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue']);
        add_action('admin_footer', [$this, 'popup']);
        add_action('wp_ajax_tipax_hub_forget_media', ['Tipax_Hub_Assets', 'forget_from_browser']);
        add_action('update_option_woocommerce_tipax_shipping_method_settings', [$this,'settings_changed'],10,2);
    }

    public static function site_url()
    {
        return untrailingslashit(home_url('/'));
    }

    public static function requires_acceptance()
    {
        return class_exists('Tipax_Hub_Updater', false);
    }

    public static function accepted()
    {
        $preferences = get_option(self::PREFERENCES, []);
        // The choice survives ordinary updates and page loads. An explicit
        // reactivation starts a new active period and asks for the choice again.
        return is_array($preferences) && !empty($preferences['service_accepted'])
            && ($preferences['site_url'] ?? '') === self::site_url()
            && in_array(($preferences['consent_version'] ?? ''), ['1.0', self::CONSENT_VERSION], true);
    }

    public static function enabled($feature)
    {
        if (defined('TIPAX_HUB_DISABLED') && TIPAX_HUB_DISABLED) {
            return false;
        }
        if ($feature === 'metrics') {
            $preferences = get_option(self::PREFERENCES, []);
            return class_exists('Tipax_Hub_Metrics') && Tipax_Hub_Metrics::consent()
                && self::enabled('status') && ($preferences['consent_version'] ?? '') === self::CONSENT_VERSION;
        }
        if (self::requires_acceptance()) {
            return in_array($feature, ['status', 'media'], true) && self::accepted();
        }
        $preferences = get_option(self::PREFERENCES, []);
        return is_array($preferences) && ($preferences[$feature] ?? 'no') === 'yes'
            && ($preferences['site_url'] ?? '') === self::site_url()
            && in_array(($preferences['consent_version'] ?? ''), ['1.0', self::CONSENT_VERSION], true);
    }

    public static function service_ready()
    {
        return self::registered() && !(class_exists('Tipax_Hub_Updater', false) && Tipax_Hub_Updater::requires_update());
    }

    public static function registered()
    {
        if (!self::requires_acceptance()) {
            return true;
        }
        $identity = get_option(self::IDENTITY, []);
        return self::accepted() && is_array($identity)
            && ($identity['site_url'] ?? '') === self::site_url()
            && !empty($identity['install_id']) && !empty($identity['secret'])
            && ($identity['registered'] ?? true) !== false;
    }

    public function maybe_schedule()
    {
        if ((self::enabled('status') || self::enabled('media')) && !wp_next_scheduled(self::SYNC_HOOK)) {
            wp_schedule_event(time() + MINUTE_IN_SECONDS, 'hourly', self::SYNC_HOOK);
        }
        if (self::enabled('status') && get_option('tipax_hub_reported_version', '') !== TSM_VERSION
            && !wp_next_scheduled(self::EVENT_HOOK)) {
            $retry = get_option('tipax_hub_retry', []);
            wp_schedule_single_event(max(time() + MINUTE_IN_SECONDS, (int) ($retry['after'] ?? 0)), self::EVENT_HOOK);
        }
    }

    public function activate($new_activation = false)
    {
        $this->stopping = false;
        if ($new_activation && self::requires_acceptance()) {
            delete_option(self::PREFERENCES);
            wp_clear_scheduled_hook(self::SYNC_HOOK);
            wp_clear_scheduled_hook(self::EVENT_HOOK);
            wp_clear_scheduled_hook('tipax_hub_update_check');
        }
        delete_option('tipax_hub_last_heartbeat');
        delete_option('tipax_hub_retry');
        $this->maybe_schedule();
        if (self::enabled('status') && !wp_next_scheduled(self::EVENT_HOOK)) {
            wp_schedule_single_event(time() + 10, self::EVENT_HOOK);
        }
    }

    public function upgraded($upgrader, $options)
    {
        if (!self::enabled('status') || ($options['action'] ?? '') !== 'update' || ($options['type'] ?? '') !== 'plugin') {
            return;
        }
        $plugins = isset($options['plugins']) ? (array) $options['plugins'] : [(string) ($options['plugin'] ?? '')];
        if (in_array(plugin_basename(TIPAX_PLUGIN_FILE), $plugins, true)) {
            delete_option('tipax_hub_last_heartbeat');
            delete_option('tipax_hub_retry');
            if (!wp_next_scheduled(self::EVENT_HOOK)) {
                wp_schedule_single_event(time() + MINUTE_IN_SECONDS, self::EVENT_HOOK);
            }
        }
    }

    private function metadata()
    {
        $data = ['plugin_version' => TSM_VERSION, 'wp_version' => get_bloginfo('version'),
            'wc_version' => defined('WC_VERSION') ? WC_VERSION : '', 'php_version' => PHP_VERSION];
        $preferences = get_option(self::PREFERENCES, []);
        if (($preferences['consent_version'] ?? '') === self::CONSENT_VERSION) {
            $settings = get_option('woocommerce_tipax_shipping_method_settings', []);
            $data['tipax_username'] = sanitize_text_field(is_scalar($settings['username'] ?? null) ? (string) $settings['username'] : '');
            $data['consent_version'] = self::CONSENT_VERSION;
        }
        return $data;
    }

    private function identity()
    {
        $identity = get_option(self::IDENTITY, []);
        return is_array($identity) && ($identity['site_url'] ?? '') === self::site_url() ? $identity : [];
    }

    public function settings_changed($old, $new)
    {
        if (($old['username'] ?? '') !== ($new['username'] ?? '') && self::enabled('status')) {
            delete_option('tipax_hub_last_heartbeat');
            if (!wp_next_scheduled(self::EVENT_HOOK)) { wp_schedule_single_event(time()+MINUTE_IN_SECONDS,self::EVENT_HOOK); }
        }
    }

    public function request($method, $path, array $payload = [], $signed = false, $blocking = true, array $extra_headers = [])
    {
        // Image permission must not authorize status or optional usage snapshots.
        $is_status = $path === '/api/v1/installs' || strpos($path, '/heartbeat') !== false;
        if (($signed || $is_status) ? !self::enabled('status') : !self::enabled('media')) {
            return new WP_Error('tipax_hub_consent_required', 'Hub acceptance is required.');
        }
        if ($path === '/api/v1/metrics/batch') {
            return new WP_Error('tipax_hub_metrics_disabled', 'Usage metrics are not included in this release.');
        }
        if ($path === '/api/v1/metrics/snapshot' && (!self::enabled('metrics') || !$signed)) {
            return new WP_Error('tipax_hub_metrics_consent_required', 'Optional usage consent is required.');
        }
        $body = $method === 'GET' ? '' : wp_json_encode($payload, JSON_UNESCAPED_SLASHES);
        if (!is_string($body)) {
            return new WP_Error('tipax_hub_invalid_body', 'Unable to encode the hub request.');
        }
        $headers = array_merge(['Accept' => 'application/json', 'Content-Type' => 'application/json'], $extra_headers);
        if ($signed) {
            $identity = $this->identity();
            if (empty($identity['install_id']) || empty($identity['secret'])) {
                return new WP_Error('tipax_hub_identity_missing', 'Hub registration is pending.');
            }
            $timestamp = (string) time();
            $nonce = bin2hex(random_bytes(16));
            $canonical = implode("\n", [$timestamp, $nonce, $method, $path, hash('sha256', $body)]);
            $headers['X-Tipax-Install'] = $identity['install_id'];
            $headers['X-Tipax-Timestamp'] = $timestamp;
            $headers['X-Tipax-Nonce'] = $nonce;
            $headers['X-Tipax-Signature'] = 'v1=' . base64_encode(hash_hmac('sha256', $canonical, $identity['secret'], true));
        }
        $args = [
            'method' => $method, 'headers' => $headers, 'timeout' => $blocking ? 5 : 1,
            'blocking' => $blocking, 'redirection' => 0, 'sslverify' => true,
            'limit_response_size' => 262144, 'user-agent' => 'Tipax-Plugin-Hub',
        ];
        if ($method !== 'GET') {
            $args['body'] = $body;
            $args['data_format'] = 'body';
        }
        return wp_safe_remote_request(self::BASE_URL . $path, $args);
    }

    public static function response_data($response)
    {
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) < 200 || wp_remote_retrieve_response_code($response) >= 300) {
            return false;
        }
        $json = json_decode(wp_remote_retrieve_body($response), true);
        return is_array($json) && !empty($json['ok']) && is_array($json['data'] ?? null) ? $json['data'] : false;
    }

    private function register()
    {
        if (!self::enabled('status')) {
            return false;
        }
        $identity = $this->identity();
        if (!empty($identity['install_id']) && !empty($identity['secret']) && ($identity['registered'] ?? true) !== false) {
            return true;
        }
        // Both the admin acknowledgement and cron can reach registration. Serialize
        // them and keep pending credentials through timeouts and failed responses.
        $lock = time() . ':' . wp_generate_uuid4();
        $existingLock = (string) get_option(self::REGISTRATION_LOCK, '');
        if ($existingLock !== '' && (int) $existingLock < time() - MINUTE_IN_SECONDS) {
            $this->release_lock($existingLock, self::REGISTRATION_LOCK);
        }
        if (!add_option(self::REGISTRATION_LOCK, $lock, '', false)) {
            return false;
        }
        try {
            wp_cache_delete(self::IDENTITY, 'options');
            $identity = $this->identity();
            if (!empty($identity['install_id']) && !empty($identity['secret']) && ($identity['registered'] ?? true) !== false) {
                return true;
            }
            if (empty($identity['install_id']) || empty($identity['secret'])) {
                $identity = [
                    'install_id' => wp_generate_uuid4(), 'secret' => bin2hex(random_bytes(32)),
                    'site_url' => self::site_url(), 'registered' => false,
                ];
                update_option(self::IDENTITY, $identity, false);
            }
            if ($this->identity() !== $identity) {
                return false;
            }
            $payload = array_merge($this->metadata(), [
                'install_id' => $identity['install_id'], 'site_url' => self::site_url(),
                'status_consent' => true, 'consent_version' => self::CONSENT_VERSION,
                'site_secret' => $identity['secret'],
            ]);
            $data = self::response_data($this->request('POST', '/api/v1/installs', $payload));
            if (!$data || ($data['install_id'] ?? '') !== $identity['install_id']
                || !is_string($data['site_secret'] ?? null) || !hash_equals($identity['secret'], $data['site_secret'])) {
                return false;
            }
            $identity['registered'] = true;
            update_option(self::IDENTITY, $identity, false);
            return $this->identity() === $identity;
        } finally {
            $this->release_lock($lock, self::REGISTRATION_LOCK);
        }
    }

    public function heartbeat($status = 'active', $blocking = true)
    {
        $identity = $this->identity();
        if (empty($identity['install_id'])) {
            return false;
        }
        $payload = array_merge($this->metadata(), ['status' => $status === 'inactive' ? 'inactive' : 'active']);
        $response = $this->request('POST', '/api/v1/installs/' . $identity['install_id'] . '/heartbeat', $payload, true, $blocking);
        if ($blocking && !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 401) {
            $error = json_decode(wp_remote_retrieve_body($response), true);
            if (($error['error']['code'] ?? '') === 'unknown_install') {
                delete_option(self::IDENTITY);
            }
        }
        if (!$blocking) {
            return !is_wp_error($response);
        }
        $data = self::response_data($response);
        return is_array($data) && ($data['status'] ?? '') === $payload['status'];
    }

    private function confirm_active()
    {
        if (!self::enabled('status')) {
            return false;
        }
        $confirmed = false;
        try {
            $confirmed = $this->register() && $this->heartbeat('active');
        } catch (Throwable $exception) {
            // Keep the accepted choice; retry a failed lifecycle notification.
        }
        if ($confirmed) {
            update_option('tipax_hub_last_heartbeat', time(), false);
            update_option('tipax_hub_reported_version', TSM_VERSION, false);
            delete_option('tipax_hub_retry');
            wp_clear_scheduled_hook(self::EVENT_HOOK);
            return true;
        }
        $retry = get_option('tipax_hub_retry', []);
        $failures = min(6, (int) ($retry['failures'] ?? 0) + 1);
        $retryAt = time() + MINUTE_IN_SECONDS * (2 ** ($failures - 1));
        update_option('tipax_hub_retry', ['failures' => $failures, 'after' => $retryAt], false);
        wp_clear_scheduled_hook(self::EVENT_HOOK);
        wp_schedule_single_event($retryAt, self::EVENT_HOOK);
        return false;
    }

    public function sync()
    {
        if ($this->stopping || !(self::enabled('status') || self::enabled('media'))) {
            return;
        }
        $retry = get_option('tipax_hub_retry', []);
        if ((int) ($retry['after'] ?? 0) > time()) {
            return;
        }
        $lock = time() . ':' . wp_generate_uuid4();
        $existing = (string) get_option('tipax_hub_sync_lock', '');
        if ($existing !== '' && (int) $existing < time() - 300) {
            $this->release_lock($existing);
        }
        if (!add_option('tipax_hub_sync_lock', $lock, '', false)) {
            return;
        }
        $ok = $status_ok = true;
        try {
            if (self::enabled('status')) {
                $identity = $this->identity();
                if (empty($identity['install_id']) || empty($identity['secret']) || ($identity['registered'] ?? true) === false
                    || (int) get_option('tipax_hub_last_heartbeat', 0) < time() - DAY_IN_SECONDS
                    || get_option('tipax_hub_reported_version', '') !== TSM_VERSION) {
                    $ok = $status_ok = $this->confirm_active();
                }
            }
            if (self::enabled('media') && (int) get_option('tipax_hub_last_config', 0) < time() - HOUR_IN_SECONDS) {
                $ok = Tipax_Hub_Assets::sync($this) && $ok;
            }
            if ($ok) {
                delete_option('tipax_hub_retry');
                update_option('tipax_hub_last_success', time(), false);
            } elseif ($status_ok) {
                $failures = min(6, (int) ($retry['failures'] ?? 0) + 1);
                update_option('tipax_hub_retry', [
                    'failures' => $failures, 'after' => time() + min(DAY_IN_SECONDS, HOUR_IN_SECONDS * (2 ** $failures)),
                ], false);
            }
        } catch (Throwable $exception) {
            if ($status_ok) {
                update_option('tipax_hub_retry', ['failures' => 1, 'after' => time() + 6 * HOUR_IN_SECONDS], false);
            }
        } finally {
            $this->release_lock($lock);
        }
    }

    private function release_lock($lock, $option = 'tipax_hub_sync_lock')
    {
        global $wpdb;
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s", $option, $lock));
        wp_cache_delete($option, 'options');
    }

    public function deactivate()
    {
        $this->stopping = true;
        if (self::enabled('status')) {
            try {
                $this->heartbeat('inactive', false);
            } catch (Throwable $exception) {
                // Best effort: local plugin deactivation must always succeed.
            }
        }
        delete_option('tipax_hub_last_heartbeat');
        wp_clear_scheduled_hook(self::SYNC_HOOK);
        wp_clear_scheduled_hook(self::EVENT_HOOK);
        wp_clear_scheduled_hook('tipax_hub_update_check');
        wp_clear_scheduled_hook('tipax_hub_metrics_sync');
    }

    public function enqueue($hook)
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        $preferences = get_option(self::PREFERENCES, []);
        if ((self::requires_acceptance() && (!self::accepted() || ($preferences['consent_version'] ?? '') !== self::CONSENT_VERSION)) || strpos((string) ($_GET['page'] ?? ''), 'tipax-') === 0) {
            wp_enqueue_style('tipax-hub', TSM_ASSETS_URL . 'admin/css/tipax-hub.css', [], TSM_VERSION);
            wp_enqueue_script('tipax-hub', TSM_ASSETS_URL . 'admin/js/tipax-hub.js', [], TSM_VERSION, true);
            if (self::enabled('media')) {
                wp_localize_script('tipax-hub', 'tipaxHubMedia', [
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('tipax_hub_media'),
                ]);
            }
        }
    }

    public function save()
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to manage these settings.', 'delivery-service-tipax-shipping'), '', ['response' => 403]);
        }
        check_admin_referer('tipax_hub_save');
        if (self::requires_acceptance()) {
            if (isset($_POST['tipax_hub_reject']) && wp_unslash($_POST['tipax_hub_reject']) === 'yes') {
                $plugin = plugin_basename(TIPAX_PLUGIN_FILE);
                if (!current_user_can('activate_plugins')) {
                    wp_die(esc_html__('You do not have permission to deactivate this plugin.', 'delivery-service-tipax-shipping'), '', ['response' => 403]);
                }
                if (!function_exists('deactivate_plugins')) {
                    require_once ABSPATH . 'wp-admin/includes/plugin.php';
                }
                $network_wide = is_multisite() && is_plugin_active_for_network($plugin);
                if ($network_wide && !current_user_can('manage_network_plugins')) {
                    wp_die(esc_html__('You do not have permission to deactivate this plugin.', 'delivery-service-tipax-shipping'), '', ['response' => 403]);
                }
                // Use WordPress's actual deactivation so all normal cleanup
                // hooks run. No status is sent when consent was never granted.
                deactivate_plugins($plugin, false, $network_wide);
                delete_option(self::PREFERENCES);
                wp_safe_redirect($network_wide ? network_admin_url('plugins.php?deactivate=true') : admin_url('plugins.php?deactivate=true'));
                exit;
            } elseif (isset($_POST['tipax_hub_accept']) && wp_unslash($_POST['tipax_hub_accept']) === 'yes') {
                // The explicitly labelled button is the one-time acceptance;
                // there is deliberately no preselected checkbox or later toggle.
                update_option(self::PREFERENCES, [
                    'service_accepted' => true, 'site_url' => self::site_url(),
                    'consent_version' => self::CONSENT_VERSION, 'consented_at' => gmdate('c'),
                ], false);
                delete_option('tipax_hub_retry');
                $this->activate();
                // This is an explicit admin choice, not checkout: confirm the
                // active state now, including when the installation already exists.
                $this->confirm_active();
            } elseif (isset($_POST['tipax_hub_retry_registration']) && self::accepted()) {
                $this->activate();
                $this->confirm_active();
            }
        } else {
            $this->save_preferences($_POST);
        }
        wp_safe_redirect(admin_url('plugins.php'));
        exit;
    }

    public function save_preferences(array $posted)
    {
        if (self::requires_acceptance()) {
            return;
        }
        update_option(self::PREFERENCES, [
            'status' => isset($posted['tipax_hub_status']) && wp_unslash($posted['tipax_hub_status']) === 'yes' ? 'yes' : 'no',
            'media' => isset($posted['tipax_hub_media']) && wp_unslash($posted['tipax_hub_media']) === 'yes' ? 'yes' : 'no',
            'site_url' => self::site_url(), 'consent_version' => self::CONSENT_VERSION,
        ], false);
        if (!self::enabled('media')) {
            Tipax_Hub_Assets::clear();
        }
        wp_clear_scheduled_hook(self::SYNC_HOOK);
        wp_clear_scheduled_hook(self::EVENT_HOOK);
        delete_option('tipax_hub_last_heartbeat');
        $this->maybe_schedule();
    }

    private function disclosure()
    {
        return '<p>برای تجربهٔ بهتر و ارائه خدمات با بهترین کیفیت، اطلاعات نسخه، فعال و غیر فعال بودن افزونه تیپاکس، نام دامنه، نام کاربری تیپاکس، نسخه وردپرس و ووکامرس و نسخه PHP به <strong>تیپاکس</strong> ارسال می‌شود.</p>';
    }

    private function acceptance_form()
    {
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="tipax_hub_save">';
        wp_nonce_field('tipax_hub_save');
        echo '<button class="button button-primary button-hero" type="submit" name="tipax_hub_accept" value="yes">متوجه شدم</button><button class="button-link tipax-hub-deactivate" type="submit" name="tipax_hub_reject" value="yes">افزونه غیر فعال شود</button></form>';
    }

    public function popup()
    {
        $preferences = get_option(self::PREFERENCES, []);
        if (!self::requires_acceptance() || (self::accepted() && ($preferences['consent_version'] ?? '') === self::CONSENT_VERSION) || !current_user_can('manage_options')) {
            return;
        }
        echo '<div class="tipax-hub-overlay" data-tipax-hub-modal data-site="' . esc_attr(substr(hash('sha256', self::site_url()), 0, 16)) . '"><section class="tipax-hub-dialog" role="dialog" aria-modal="true" aria-labelledby="tipax-hub-dialog-title"><h2 id="tipax-hub-dialog-title">شروع سرویس تیپاکس</h2>';
        echo $this->disclosure();
        $this->acceptance_form();
        echo '</section></div>';
    }

    public function fields()
    {
        if (self::requires_acceptance()) {
            return;
        }
        echo '<tr><th>وضعیت اختیاری</th><td><label><input type="checkbox" name="tipax_hub_status" value="yes" ' . checked(self::enabled('status'), true, false) . '> اجازهٔ ارسال آدرس سایت، نسخهٔ افزونه، وردپرس، ووکامرس و PHP و فعال/غیرفعال بودن به tipaxplugin.ir</label><p>دادهٔ مشتری ارسال نمی‌شود؛ همگام‌سازی تعدادها تیک اختیاری جداگانه در تنظیمات دارد.</p></td></tr>';
        echo '<tr><th>تصاویر راهنما</th><td><label><input type="checkbox" name="tipax_hub_media" value="yes" ' . checked(self::enabled('media'), true, false) . '> دریافت تصاویر راهنما از هاب و ذخیره روی این سایت</label></td></tr>';
    }

    public function privacy_text()
    {
        if (function_exists('wp_add_privacy_policy_content')) {
            wp_add_privacy_policy_content('Tipax Plugin Hub', wp_kses_post(
                '<p>هاب افزونهٔ تیپاکس پس از پذیرش متن سرویس، آدرس سایت، شناسهٔ تصادفی نصب، نام کاربری تیپاکس، نسخهٔ افزونه، وردپرس، ووکامرس و PHP و وضعیت فعال/غیرفعال را در tipaxplugin.ir ثبت می‌کند. فقط در صورت انتخاب تیک اختیاری تنظیمات، تعداد تجمعی استعلام‌های قیمت و سفارش‌های تیپاکس روزی یک‌بار همگام می‌شوند. اطلاعات مشتری، جزئیات سفارش، رمز و توکن تیپاکس ارسال نمی‌شوند. شمارنده‌ها محلی هستند. با برداشتن تیک، ارسال آماری متوقف می‌شود و با غیرفعال‌کردن افزونه ارسال‌های آینده متوقف می‌شوند. سیاست سرویس: https://tipaxplugin.ir/privacy</p>'
            ));
        }
    }
}
