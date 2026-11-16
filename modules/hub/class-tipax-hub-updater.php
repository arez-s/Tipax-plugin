<?php
/** Only shipped in the standalone distribution, never in the directory build. */
if (!defined('ABSPATH')) {
    exit;
}

class Tipax_Hub_Updater
{
    const HOOK = 'tipax_hub_update_check';
    const OPTION = 'tipax_hub_updates_enabled';
    const CACHE = 'tipax_hub_direct_release';
    const POLICY = 'tipax_hub_required_update';
    const GATE_STATE = 'tipax_hub_update_gate_state';
    const SLUG = 'delivery-services-tipax-shipping';

    public static function register()
    {
        add_action('init', [__CLASS__, 'schedule']);
        add_action(self::HOOK, [__CLASS__, 'refresh']);
        add_filter('update_plugins_tipaxplugin.ir', [__CLASS__, 'update'], 10, 4);
        add_filter('plugins_api', [__CLASS__, 'information'], 10, 3);
        add_filter('upgrader_pre_download', [__CLASS__, 'verified_download'], 10, 4);
        add_filter('http_request_args', [__CLASS__, 'request_args'], 10, 2);
        add_action('admin_notices', [__CLASS__, 'required_notice']);
        add_filter('woocommerce_package_rates', [__CLASS__, 'filter_rates'], PHP_INT_MAX);
    }

    public static function enabled()
    {
        return !(defined('TIPAX_HUB_DISABLED') && TIPAX_HUB_DISABLED)
            && Tipax_Hub_Client::registered()
            && (get_option(self::OPTION, 'yes') === 'yes' || self::requires_update());
    }

    public static function schedule()
    {
        self::sync_gate_state();
        if (self::enabled()) {
            // Upgrade the old daily job once; normal loads only read local state.
            if (get_option('tipax_hub_update_schedule_version') !== '2') {
                wp_clear_scheduled_hook(self::HOOK);
                delete_transient('tipax_hub_update_throttle');
                update_option('tipax_hub_update_schedule_version', '2', false);
            }
            if (!wp_next_scheduled(self::HOOK)) {
                wp_schedule_event(time() + MINUTE_IN_SECONDS, 'hourly', self::HOOK);
            }
        }
    }

    public static function request_args($args, $url)
    {
        // Even core ZIP downloads must not attach WordPress's site-URL user agent.
        if (preg_match('~^https://tipaxplugin\.ir/(?:api/v1/releases/latest(?:\?|$)|releases/[a-zA-Z0-9._-]+\.zip$)~', $url)) {
            $args['user-agent'] = 'Tipax-Plugin-Updater';
            $args['redirection'] = 0;
            $args['sslverify'] = true;
        }
        return $args;
    }

    public static function refresh()
    {
        if (!self::enabled() || get_transient('tipax_hub_update_throttle')) {
            return;
        }
        // A failed check is also throttled; cached metadata remains available.
        set_transient('tipax_hub_update_throttle', 1, HOUR_IN_SECONDS);
        $response = wp_safe_remote_get(Tipax_Hub_Client::BASE_URL . '/api/v1/releases/latest?channel=direct', [
            'timeout' => 5, 'redirection' => 0, 'sslverify' => true,
            'limit_response_size' => 65536, 'user-agent' => 'Tipax-Plugin-Updater',
            'headers' => ['Accept' => 'application/json'],
        ]);
        $release = Tipax_Hub_Client::response_data($response);
        if (!self::valid($release)) {
            return;
        }
        update_option(self::CACHE, $release, false);
        self::accept_policy($release);
        // Let WordPress rebuild its update result from the fresh local metadata.
        delete_site_transient('update_plugins');
    }

    public static function accept_policy(array $release)
    {
        if (!self::valid($release) || !is_array($release['required_update'] ?? null)) { return; }
        $policy = $release['required_update'];
        if (!is_bool($policy['require_latest'] ?? null) || !is_string($policy['minimum_version'] ?? null)) { return; }
        if ($policy['require_latest'] && (!preg_match('/^\d+\.\d+\.\d+$/', $policy['minimum_version'])
            || $policy['minimum_version'] !== $release['version'])) { return; }
        if (!$policy['require_latest'] && $policy['minimum_version'] !== '') { return; }
        $policy = ['require_latest' => $policy['require_latest'], 'minimum_version' => $policy['minimum_version']];
        if ($policy !== get_option(self::POLICY, [])) {
            update_option(self::POLICY, $policy, false);
            self::sync_gate_state();
        }
    }

    private static function sync_gate_state()
    {
        $state = self::requires_update() ? 'blocked' : 'ready';
        if (get_option(self::GATE_STATE, 'ready') !== $state) {
            update_option(self::GATE_STATE, $state, false);
            // Also runs after installing the required version: old sessions
            // must not retain an empty Tipax rate list after service resumes.
            if (class_exists('WC_Cache_Helper')) { WC_Cache_Helper::get_transient_version('shipping', true); }
        }
    }

    public static function requires_update()
    {
        $policy = get_option(self::POLICY, []);
        return is_array($policy) && ($policy['require_latest'] ?? false) === true
            && is_string($policy['minimum_version'] ?? null)
            && preg_match('/^\d+\.\d+\.\d+$/', $policy['minimum_version'])
            && version_compare(TSM_VERSION, $policy['minimum_version'], '<');
    }

    public static function blocked_response()
    {
        return (object) ['isSuccess' => false, 'statusCode' => 426, 'error_code' => 'tipax_update_required',
            'message' => 'برای ادامهٔ استفاده از سرویس تیپاکس، افزونه را به آخرین نسخه به‌روزرسانی کنید.'];
    }

    public static function required_notice()
    {
        if (!self::requires_update() || !current_user_can('update_plugins')) { return; }
        $policy = get_option(self::POLICY, []);
        echo '<div class="notice notice-error"><p>برای ادامهٔ استفاده از سرویس تیپاکس، افزونه را به نسخهٔ '
            . esc_html($policy['minimum_version']) . ' به‌روزرسانی کنید. <a href="'
            . esc_url(admin_url('update-core.php')) . '">به‌روزرسانی افزونه</a></p></div>';
    }

    public static function filter_rates($rates)
    {
        if (!self::requires_update() || !is_array($rates)) { return $rates; }
        foreach ($rates as $key => $rate) {
            if (is_object($rate) && method_exists($rate, 'get_method_id') && $rate->get_method_id() === 'tipax_shipping_method') {
                unset($rates[$key]);
            }
        }
        return $rates;
    }

    private static function valid($release)
    {
        return is_array($release) && ($release['channel'] ?? '') === 'direct'
            && ($release['slug'] ?? '') === self::SLUG
            && preg_match('/^\d+\.\d+(?:\.\d+)?(?:[-+][a-zA-Z0-9.-]+)?$/', $release['version'] ?? '')
            && preg_match('~^https://tipaxplugin\.ir/releases/[a-zA-Z0-9._-]+\.zip$~', $release['download_url'] ?? '')
            && preg_match('/^[a-f0-9]{64}$/', $release['package_sha256'] ?? '');
    }

    private static function release()
    {
        if (!self::enabled()) {
            return false;
        }
        $release = get_option(self::CACHE, []);
        return self::valid($release) ? $release : false;
    }

    public static function update($update, $plugin_data, $plugin_file, $locales)
    {
        if ($plugin_file !== plugin_basename(TIPAX_PLUGIN_FILE)) {
            return $update;
        }
        // No network requests during a page load or checkout: cron owns refresh.
        $release = self::release();
        if (!$release) {
            return false;
        }
        return [
            'id' => Tipax_Hub_Client::BASE_URL,
            'slug' => self::SLUG, 'version' => $release['version'],
            'url' => Tipax_Hub_Client::BASE_URL,
            'package' => $release['download_url'],
            'tested' => (string) ($release['tested'] ?? ''),
            'requires_php' => (string) ($release['requires_php'] ?? '7.4'),
        ];
    }

    public static function information($result, $action, $args)
    {
        if ($action !== 'plugin_information' || !is_object($args) || ($args->slug ?? '') !== self::SLUG) {
            return $result;
        }
        $release = self::release();
        if (!$release) {
            return $result;
        }
        return (object) [
            'name' => 'Tipax Shipping', 'slug' => self::SLUG, 'version' => $release['version'],
            'author' => 'علیرضا صباحی', 'homepage' => Tipax_Hub_Client::BASE_URL,
            'download_link' => $release['download_url'],
            'requires' => (string) ($release['requires'] ?? '5.8'),
            'tested' => (string) ($release['tested'] ?? ''),
            'requires_php' => (string) ($release['requires_php'] ?? '7.4'),
            'sections' => ['description' => 'اتصال فروشگاه ووکامرس به تیپاکس برای استعلام هزینه حمل، ثبت و رهگیری مرسوله‌ها و چاپ برچسب ارسال.',
                'changelog' => nl2br(esc_html((string) ($release['release_notes'] ?? '')))],
        ];
    }

    public static function verified_download($reply, $package, $upgrader, $hook_extra = [])
    {
        $release = self::release();
        if (!$release || $package !== $release['download_url'] || $reply !== false) {
            return $reply;
        }
        if (!function_exists('download_url')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        $file = download_url($package, 120);
        if (is_wp_error($file)) {
            return $file;
        }
        if (!hash_equals($release['package_sha256'], (string) hash_file('sha256', $file))) {
            wp_delete_file($file);
            return new WP_Error('tipax_hub_package_hash', 'بستهٔ به‌روزرسانی با امضای محتوای هاب مطابقت ندارد. دوباره تلاش کنید.');
        }
        return $file;
    }

    public static function save_preferences(array $posted)
    {
        $enabled = isset($posted['tipax_hub_updates']) && wp_unslash($posted['tipax_hub_updates']) === 'yes';
        update_option(self::OPTION, $enabled ? 'yes' : 'no', false);
        if (!$enabled) {
            wp_clear_scheduled_hook(self::HOOK);
            delete_option(self::CACHE);
            delete_site_transient('update_plugins');
        } else {
            self::schedule();
        }
    }

    public static function fields()
    {
        echo '<tr><th>به‌روزرسانی مستقیم</th><td><label><input type="checkbox" name="tipax_hub_updates" value="yes" ' . checked(self::enabled(), true, false) . '> بررسی ساعتی به‌روزرسانی از tipaxplugin.ir</label>';
        echo '<p class="description">این نسخه خارج از مخزن وردپرس توزیع می‌شود. بررسی نسخه و دریافت بسته از هاب انجام می‌شود و به ارسال آمار وابسته نیست. درخواست بررسی نسخه شامل دامنه، شناسهٔ نصب یا آمار سایت نیست. نصب نسخه از ابزار استاندارد به‌روزرسانی وردپرس انجام می‌شود.</p></td></tr>';
    }
}
