<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://sabahi.ir
 * @since             1.0.0
 * @package           Delivery_Services_Tipax_Shipping
 *
 * @wordpress-plugin
 * Plugin Name:       Delivery Service Tipax Shipping
 * Description:       Delivery Service Tipax Shipping Method for wocommerce
 * Version:           1.6.0
 * Requires at least: 5.2
 * Requires PHP:      7.4
 * Author:            Alireza Sabahi
 * Author URI:        https://sabahi.ir/
 * Text Domain:       delivery-services-tipax-shipping
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages/
 */


if (!defined('ABSPATH')) {
    exit;
}


class TipaxShipping
{
    public function __construct()
    {
        $this->defineConstants();
        $this->addSettingsLink();
        $this->init();
    }

    public function defineConstants()
    {
        define('TSM_DIR', plugin_dir_path(__FILE__));
        define('TSM_URL', plugin_dir_url(__FILE__));
        define('TSM_ASSETS_URL', TSM_URL.'/assets/');
        define('TSM_Languages', dirname(plugin_basename( __FILE__ )).'/languages');
        define('TSM_VERSION', "1.1.2");
        define('TSM_CITIES_DB_VERSION', "1.0.1");
    }
    
    public function init()
    {
        $this->localization();

        $this->checkPrerequisites();

        $this->CheckVersionOfTipaxCitiesTable();
        //activation
        register_activation_hook(__FILE__, [$this, 'activation']);

        //deactivation
        register_deactivation_hook(__FILE__, [$this, 'deactivation']);
    }

    public function addSettingsLink()
    {
        add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array( $this, 'plugin_add_settings_link' ) );
    }

    public function plugin_add_settings_link( $links ) {
        $settings_link = '<a href="admin.php?page=wc-settings&tab=shipping&section=tipax_shipping_method">' . __( 'Settings',"tipax-shipping" ) . '</a>';
        array_unshift( $links, $settings_link );
        return $links;
    }

    private function localization()
    {
        $plugin_lan_path = TSM_Languages; /* Relative to WP_PLUGIN_DIR */
        $loaded_plugin_textdomain = load_plugin_textdomain( 'tipax-shipping', false, $plugin_lan_path );
    }

    public function activation()
    {
        $this->addOptionFileActivated();
        $this->createTipaxCitiesTable();
        $this->importDataToTipaxCitiesTable();
    }

    public function addOptionFileActivated(){
        $activated_before = get_option("activated_tipax_shipping");

        if ( ! empty( $_SERVER['SCRIPT_NAME'] ) && false !== strpos( $_SERVER['SCRIPT_NAME'], '/wp-admin/plugins.php' ) ) {
            if(!$activated_before){
                add_option( 'activated_tipax_shipping', "true" );
            }else{
                update_option("activated_tipax_shipping","true");
            }
            file_put_contents( TSM_DIR . '.activated', '' );
        }
    }

    public function createTipaxCitiesTable() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'tipax_cities';

        if($wpdb->get_var( "show tables like '$table_name'" ) != $table_name) {
            $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(9) NOT NULL AUTO_INCREMENT,
            stateTitle varchar(191) NOT NULL,
            cityTitle varchar(191) NOT NULL,
            cityId int(11) UNSIGNED NOT NULL,
            stateId int(11) UNSIGNED NOT NULL,
            UNIQUE KEY id (id)) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

            add_option("tipax_cities_db_version", TSM_CITIES_DB_VERSION);

            dbDelta($sql);
            $is_error = empty( $wpdb->last_error );
            return $is_error;
        }
    }

    public function importDataToTipaxCitiesTable()
    {
        global $wpdb;
        require TSM_DIR . 'includes/data/cities.php';
        $table_name = $wpdb->prefix . 'tipax_cities';
        $imported_before = get_option("tipax_cities_imported");

        if(($wpdb->get_var( "show tables like '$table_name'" ) == $table_name) && $imported_before == false) {
            foreach ($city_codes as $city) {
                $wpdb->insert(
                    $table_name,
                    array(
                        'cityId' => $city["cityId"],
                        'cityTitle' => $city["cityTitle"],
                        'stateId' => $city["stateId"],
                        'stateTitle' => $city["stateTitle"],
                    )
                );
            }
            add_option("tipax_cities_imported","true");
        }
    }

    public function CheckVersionOfTipaxCitiesTable()
    {
        $currentDbVersion = get_option("tipax_cities_db_version");
        if($currentDbVersion){
            if(version_compare($currentDbVersion,TSM_CITIES_DB_VERSION) == -1){
                error_log("Tipax-Shipping: Upgrading TipaxCitiesDB from ".$currentDbVersion." to ".TSM_CITIES_DB_VERSION);
                $this->deleteTipaxCitiesTable();

                delete_option("tipax_cities_db_version");
                delete_option("tipax_cities_imported");

                $this->createTipaxCitiesTable();
                $this->importDataToTipaxCitiesTable();

                error_log("Tipax-Shipping: TipaxCitiesDB Upgraded from ".$currentDbVersion." to ".TSM_CITIES_DB_VERSION);
            }
        }
    }

    public function deleteTipaxCitiesTable(){
        global $wpdb;
        $table_name = $wpdb->prefix . 'tipax_cities';

        if($wpdb->get_var( "show tables like '$table_name'" ) == $table_name) {
            $sql = "Drop TABLE IF EXISTS $table_name;";
            $wpdb->query($sql);
            $is_error = empty( $wpdb->last_error );
            return $is_error;
        }
    }

    public function deactivation()
    {
        $this->updateOptionFileDeactivated();
    }

    public function updateOptionFileDeactivated()
    {
        if ( ! empty( $_SERVER['SCRIPT_NAME'] ) && false !== strpos( $_SERVER['SCRIPT_NAME'], '/wp-admin/plugins.php' ) ) {
            update_option( 'activated_tipax_shipping', "false" );
            if (file_exists( TSM_DIR . '.activated' )) {
                unlink(TSM_DIR . '.activated');
            }
        }
    }
    
    private function checkPrerequisites() 
    {
        if ( ! file_exists( TSM_DIR . '.activated' ) ) {
            return false;
        }

        require_once TSM_DIR . "includes/classes/Message.php";
        $message = new Message();

        $settings = get_option('woocommerce_tipax_shipping_method_settings', null);



        //check if tipax initial settings in woocommerce shipping is completed or not
        if ((!isset($settings['apiKey']) || $settings['apiKey'] == null) || (!isset($settings['username']) || $settings['username'] == null) || (!isset($settings['password']) || $settings['password'] == null)) {
            $current_url = $_SERVER["REQUEST_URI"];

            if(strpos($current_url, "ion=tipax_shipping_method") !== false){
                global $pagenow;
                $admin_pages = [ 'edit.php', 'plugins.php','admin.php' ];
                if ( in_array( $pagenow, $admin_pages ) ) {
                    $message->default(__('Complete tipax initial settings in woocommerce shipping','tipax-shipping'),0,'<a href="admin.php?page=wc-settings&tab=shipping&section=tipax_shipping_method" style="font-size: 14px;font-weight: bold;background: #ffca0a;text-decoration: none;padding: 4px 6px;line-height: 27px;border-radius: 9px;">' . __( 'Settings',"tipax-shipping" ) . '</a>');
                }
            }
        }

        //check if woocommerce is installed or not
        if (
            !in_array(
            'woocommerce/woocommerce.php',apply_filters('active_plugins', get_option('active_plugins')))
        ) {
            return $message->default(__('Tipax shipping: Woocomemrce is not installed,please install and active it first', 'tipax-shipping'));
        }

        require_once TSM_DIR . 'includes/classes/Request.php';
        require_once TSM_DIR . 'includes/Helpers.php';
        require_once TSM_DIR . 'includes/Shipping.php';
        require_once TSM_DIR . 'includes/Invoice.php';
        require_once TSM_DIR . 'includes/class-methods.php';
        require_once TSM_DIR . 'includes/class-checkout.php';
        require_once TSM_DIR . 'includes/wc-order.php';
        require_once TSM_DIR . 'includes/tipax-menu.php';
    }
}

return new TipaxShipping;