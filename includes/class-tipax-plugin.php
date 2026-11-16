<?php
if (!defined('ABSPATH')) {
    exit;
}

class Tipax_Plugin
{
    private static $instance = null;
    private $plugin_file;
    private $bootstrapped = false;

    public static function instance($plugin_file = null)
    {
        if (self::$instance === null) {
            self::$instance = new self($plugin_file);
        }

        return self::$instance;
    }

    private function __construct($plugin_file = null)
    {
        $this->plugin_file = $plugin_file ?: (defined('TIPAX_PLUGIN_FILE') ? TIPAX_PLUGIN_FILE : TSM_DIR . 'delivery-services-tipax-shipping.php');
        $this->define_constants();
        Tipax_Hub_Client::instance();
        new Tipax_Hub_Metrics();
        if (class_exists('Tipax_Hub_Updater', false)) {
            Tipax_Hub_Updater::register();
        }
        $this->add_settings_link();
        $this->register_cod_gateway_loader();
        $this->init();
    }

    public function define_constants()
    {
        if (!defined('TSM_DIR')) {
            define('TSM_DIR', plugin_dir_path($this->plugin_file));
        }
        if (!defined('TSM_URL')) {
            define('TSM_URL', plugin_dir_url($this->plugin_file));
        }
        if (!defined('TSM_ASSETS_URL')) {
            define('TSM_ASSETS_URL', TSM_URL . 'assets/');
        }
        if (!defined('TSM_Languages')) {
            define('TSM_Languages', dirname(plugin_basename($this->plugin_file)) . '/languages');
        }
        if (!defined('TSM_VERSION')) {
            define('TSM_VERSION', '3.13.6');
        }
        if (!defined('TSM_CITIES_DB_VERSION')) {
            define('TSM_CITIES_DB_VERSION', '1.0.1');
        }
        if (!defined('TSM_CITY_MAPPINGS_DB_VERSION')) {
            define('TSM_CITY_MAPPINGS_DB_VERSION', '1.0.0');
        }
        if (!defined('TSM_SETTLEMENT_SCHEMA_VERSION')) {
            define('TSM_SETTLEMENT_SCHEMA_VERSION', '2.0.0');
        }
    }

    public function init()
    {
        register_activation_hook($this->plugin_file, [$this, 'activation']);
        register_deactivation_hook($this->plugin_file, [$this, 'deactivation']);
        add_action('wp_ajax_tipax_refresh_cities', [$this, 'handle_city_refresh_ajax']);
        add_action('wp_ajax_tipax_toggle_city', [$this, 'handle_city_toggle_ajax']);
        add_action('plugins_loaded', [$this, 'bootstrap'], 20);
        $this->check_version_of_tipax_cities_table();
        $this->check_tipax_city_mappings_table();
    }

    public function handle_city_refresh_ajax()
    {
        $this->dispatch_city_ajax('ajax_refresh_cities');
    }

    public function handle_city_toggle_ajax()
    {
        $this->dispatch_city_ajax('ajax_toggle_city');
    }

    private function dispatch_city_ajax($method)
    {
        if (!class_exists('Tipax_City_Admin')) {
            $this->bootstrap();
        }

        if (!class_exists('Tipax_City_Admin') || !is_callable([Tipax_City_Admin::instance(), $method])) {
            wp_send_json_error([
                'message' => 'ماژول مدیریت شهرهای تیپاکس در درخواست AJAX بارگذاری نشد.',
            ], 503);
        }

        call_user_func([Tipax_City_Admin::instance(), $method]);
    }

    public function bootstrap()
    {
        if ($this->bootstrapped) {
            return;
        }

        if (!Tipax_Hub_Client::service_ready()) {
            return;
        }

        $this->bootstrapped = (bool) $this->check_prerequisites();
        if ($this->bootstrapped) {
            $this->maybe_upgrade_settlement_cache();
        }
    }

    private function maybe_upgrade_settlement_cache()
    {
        $stored_version = (string) get_option('tipax_settlement_schema_version', '');
        if ($stored_version === TSM_SETTLEMENT_SCHEMA_VERSION) {
            return;
        }

        if (class_exists('WC_Cache_Helper')) {
            WC_Cache_Helper::get_transient_version('shipping', true);
        }
        update_option('tipax_settlement_schema_version', TSM_SETTLEMENT_SCHEMA_VERSION, false);
    }

    public function add_settings_link()
    {
        add_filter('plugin_action_links_' . plugin_basename($this->plugin_file), [$this, 'plugin_add_settings_link']);
    }

    public function plugin_add_settings_link($links)
    {
        $settings_link = '<a href="admin.php?page=wc-settings&tab=shipping&section=tipax_shipping_method">' . __('Settings', 'delivery-service-tipax-shipping') . '</a>';
        $guide_link = '<a href="' . esc_url(TSM_URL . 'docs/INSTALLATION-FA.html') . '" target="_blank" rel="noopener noreferrer">راهنمای نصب فارسی</a>';
        array_unshift($links, $settings_link, $guide_link);
        return $links;
    }

    private function register_cod_gateway_loader()
    {
        add_action('plugins_loaded', [$this, 'register_cod_gateway'], 30);
        add_action('woocommerce_blocks_loaded', [$this, 'register_cod_blocks_support']);
    }

    public function register_cod_gateway()
    {
        if (!Tipax_Hub_Client::service_ready()) {
            return;
        }
        if (!class_exists('WC_Payment_Gateway')) {
            return;
        }

        require_once TSM_DIR . 'modules/shipping/class-tipax-cod-payment-method.php';
        add_filter('woocommerce_payment_gateways', [$this, 'add_cod_gateway']);
    }

    public function add_cod_gateway($methods)
    {
        if (!in_array('WC_Gateway_Tipax_COD', $methods, true)) {
            $methods[] = 'WC_Gateway_Tipax_COD';
        }

        return $methods;
    }

    public function register_cod_blocks_support()
    {
        if (!Tipax_Hub_Client::service_ready()) {
            return;
        }
        if (!class_exists('Automattic\\WooCommerce\\Blocks\\Payments\\Integrations\\AbstractPaymentMethodType')) {
            return;
        }

        require_once TSM_DIR . 'modules/shipping/class-tipax-cod-blocks-support.php';
        add_action(
            'woocommerce_blocks_payment_method_type_registration',
            [$this, 'register_cod_blocks_payment_method']
        );
    }

    public function register_cod_blocks_payment_method($payment_method_registry)
    {
        if (class_exists('Tipax_COD_Blocks_Support')) {
            $payment_method_registry->register(new Tipax_COD_Blocks_Support());
        }
    }

    public function activation()
    {
        Tipax_Hub_Client::instance()->activate(true);
        $this->add_option_file_activated();
        $this->create_tipax_cities_table();
        $this->create_tipax_city_mappings_table();
        $this->import_data_to_tipax_cities_table();
        add_rewrite_endpoint('tipax-tracking', EP_ROOT | EP_PAGES);
        update_option('tipax_tracking_endpoint_version', TSM_VERSION, false);
        if (!wp_next_scheduled('tipax_daily_tracking_sync')) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'tipax_daily_tracking_sync');
        }
        if (!wp_next_scheduled('tipax_daily_city_mapping_sync')) {
            wp_schedule_event(time() + 5 * MINUTE_IN_SECONDS, 'daily', 'tipax_daily_city_mapping_sync');
        }
        flush_rewrite_rules();
    }

    public function add_option_file_activated()
    {
        $activated_before = get_option('activated_tipax_shipping');

        if (!$activated_before) {
            add_option('activated_tipax_shipping', 'true');
        } else {
            update_option('activated_tipax_shipping', 'true');
        }

        if (!file_exists(TSM_DIR . '.activated')) {
            @file_put_contents(TSM_DIR . '.activated', '');
        }
    }

    public function create_tipax_cities_table()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'tipax_cities';

        if ($wpdb->get_var("show tables like '$table_name'") != $table_name) {
            $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(9) NOT NULL AUTO_INCREMENT,
            stateTitle varchar(191) NOT NULL,
            cityTitle varchar(191) NOT NULL,
            cityId int(11) UNSIGNED NOT NULL,
            stateId int(11) UNSIGNED NOT NULL,
            UNIQUE KEY id (id)) $charset_collate;";

            require_once ABSPATH . 'wp-admin/includes/upgrade.php';

            add_option('tipax_cities_db_version', TSM_CITIES_DB_VERSION);

            dbDelta($sql);
            return empty($wpdb->last_error);
        }

        return true;
    }

    public function import_data_to_tipax_cities_table()
    {
        global $wpdb;
        require TSM_DIR . 'includes/data/cities.php';
        $table_name = $wpdb->prefix . 'tipax_cities';
        $imported_before = get_option('tipax_cities_imported');

        if (($wpdb->get_var("show tables like '$table_name'") == $table_name) && $imported_before == false) {
            foreach ($city_codes as $city) {
                $wpdb->insert(
                    $table_name,
                    [
                        'cityId' => $city['cityId'],
                        'cityTitle' => $city['cityTitle'],
                        'stateId' => $city['stateId'],
                        'stateTitle' => $city['stateTitle'],
                    ]
                );
            }
            add_option('tipax_cities_imported', 'true');
        }
    }

    public function create_tipax_city_mappings_table()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'tipax_city_mappings';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            source varchar(32) NOT NULL,
            externalStateId varchar(64) NOT NULL,
            externalCityId varchar(64) NOT NULL,
            externalStateTitle varchar(191) NOT NULL DEFAULT '',
            externalCityTitle varchar(191) NOT NULL DEFAULT '',
            tipaxCityId int(11) UNSIGNED NOT NULL DEFAULT 0,
            matchType varchar(32) NOT NULL DEFAULT 'industrial_alias',
            candidateTipaxIds text NULL,
            updatedAt datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY source_external_city (source, externalStateId, externalCityId),
            KEY tipax_city_id (tipaxCityId)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        $exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table_name)) === $table_name;
        if ($exists) {
            update_option('tipax_city_mappings_db_version', TSM_CITY_MAPPINGS_DB_VERSION, false);
        }

        return $exists;
    }

    public function check_tipax_city_mappings_table()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'tipax_city_mappings';
        $current_version = (string) get_option('tipax_city_mappings_db_version', '');
        $table_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table_name)) === $table_name;
        if (!$table_exists || version_compare($current_version, TSM_CITY_MAPPINGS_DB_VERSION, '<')) {
            $this->create_tipax_city_mappings_table();
        }
    }

    public function check_version_of_tipax_cities_table()
    {
        $currentDbVersion = get_option('tipax_cities_db_version');
        if ($currentDbVersion && version_compare($currentDbVersion, TSM_CITIES_DB_VERSION) == -1) {
            if (function_exists('tipax_log')) {
                tipax_log('Tipax-Shipping: Upgrading TipaxCitiesDB from ' . $currentDbVersion . ' to ' . TSM_CITIES_DB_VERSION);
            }
            $this->delete_tipax_cities_table();

            delete_option('tipax_cities_db_version');
            delete_option('tipax_cities_imported');

            $this->create_tipax_cities_table();
            $this->import_data_to_tipax_cities_table();

            if (function_exists('tipax_log')) {
                tipax_log('Tipax-Shipping: TipaxCitiesDB Upgraded from ' . $currentDbVersion . ' to ' . TSM_CITIES_DB_VERSION);
            }
        }
    }

    public function delete_tipax_cities_table()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'tipax_cities';

        if ($wpdb->get_var("show tables like '$table_name'") == $table_name) {
            $sql = "Drop TABLE IF EXISTS $table_name;";
            $wpdb->query($sql);
            return empty($wpdb->last_error);
        }

        return true;
    }

    public function deactivation()
    {
        Tipax_Hub_Client::instance()->deactivate();
        wp_clear_scheduled_hook('tipax_daily_tracking_sync');
        wp_clear_scheduled_hook('tipax_daily_city_mapping_sync');
        wp_clear_scheduled_hook('tipax_daily_city_mapping_sync_single');
        flush_rewrite_rules();
        $this->update_option_file_deactivated();
    }

    public function update_option_file_deactivated()
    {
        update_option('activated_tipax_shipping', 'false');
        if (file_exists(TSM_DIR . '.activated')) {
            @unlink(TSM_DIR . '.activated');
        }
    }

    private function is_woocommerce_active()
    {
        if (class_exists('WooCommerce') || defined('WC_VERSION') || defined('WC_ABSPATH')) {
            return true;
        }

        $active_plugins = (array) apply_filters('active_plugins', get_option('active_plugins', []));
        if (in_array('woocommerce/woocommerce.php', $active_plugins, true)) {
            return true;
        }

        if (is_multisite()) {
            $network_active_plugins = (array) get_site_option('active_sitewide_plugins', []);
            if (isset($network_active_plugins['woocommerce/woocommerce.php'])) {
                return true;
            }
        }

        return false;
    }

    private function check_prerequisites()
    {
        require_once TSM_DIR . 'includes/class-tipax-message.php';
        $message = new Message();

        $settings = get_option(Tipax_Settings::SETTINGS_OPTION, null);

        if ((!isset($settings['apiKey']) || $settings['apiKey'] == null) || (!isset($settings['username']) || $settings['username'] == null) || (!isset($settings['password']) || $settings['password'] == null)) {
            $current_url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';

            if (strpos($current_url, 'ion=tipax_shipping_method') !== false) {
                global $pagenow;
                $admin_pages = ['edit.php', 'plugins.php', 'admin.php'];
                if (in_array($pagenow, $admin_pages, true)) {
                    $message->default(__('Complete tipax initial settings in woocommerce shipping', 'delivery-service-tipax-shipping'), 0, '<a href="admin.php?page=wc-settings&tab=shipping&section=tipax_shipping_method" style="font-size: 14px;font-weight: bold;background: #ffca0a;text-decoration: none;padding: 4px 6px;line-height: 27px;border-radius: 9px;">' . __('Settings', 'delivery-service-tipax-shipping') . '</a>');
                }
            }
        }

        if (!$this->is_woocommerce_active()) {
            return $message->default(__('Tipax shipping: Woocomemrce is not installed,please install and active it first', 'delivery-service-tipax-shipping'));
        }

        $loader = new Tipax_Loader();
        $loader->load();

        return true;
    }
}
