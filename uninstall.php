<?php
/**
 * Tipax shipping Uninstall
 *
 * Uninstalling Tipax shipping deletes tables, and options.
 *
 * @package           Tipax_Shipping
 * @version           3.11.0
 * @since             1.1.1
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

if (!defined('TSM_DIR')) {
    define('TSM_DIR', plugin_dir_path(__FILE__));
}

require_once TSM_DIR . 'modules/hub/class-tipax-hub-assets.php';
Tipax_Hub_Assets::clear();
foreach (['tipax_hub_preferences', 'tipax_hub_identity', 'tipax_hub_retry', 'tipax_hub_sync_lock', 'tipax_hub_registration_lock',
    'tipax_hub_last_heartbeat', 'tipax_hub_last_success', 'tipax_hub_reported_version',
    'tipax_hub_updates_enabled', 'tipax_hub_direct_release', 'tipax_hub_required_update', 'tipax_hub_update_gate_state', 'tipax_hub_update_schedule_version', 'tipax_usage_quotes_total', 'tipax_usage_orders_total',
    'tipax_usage_sync_state', 'tipax_usage_sync_lock'] as $tipax_hub_option) {
    delete_option($tipax_hub_option);
}
delete_transient('tipax_hub_update_throttle');
wp_clear_scheduled_hook('tipax_hub_background_sync');
wp_clear_scheduled_hook('tipax_hub_lifecycle_sync');
wp_clear_scheduled_hook('tipax_hub_update_check');
wp_clear_scheduled_hook('tipax_hub_metrics_sync');

deletePossibleOptions();
deleteTipaxCitiesTable();
deleteTipaxCityMappingsTable();
deleteActivatedFile();

// Clear any cached data that has been removed.
wp_cache_flush();

function deletePossibleOptions()
{
    delete_option("tipax_cities_db_version");
    delete_option("tipax_cities_imported");
    delete_option("activated_tipax_shipping");
    delete_option("woocommerce_tipax_shipping_method_settings");
    delete_option("woocommerce_tipax_shipping_method_accesstoken");
    delete_option("tipax_has_address");
    delete_option("tipax_test_success");
    delete_option("tipax_shipping_address");
    delete_option("tipax_tracking_last_sync_report");
    delete_option("tipax_tracking_endpoint_version");
    delete_option("tipax_tracking_codes_lowercase_migration");
    delete_option("tipax_disabled_city_ids");
    delete_option("tipax_cities_last_sync");
    delete_option("tipax_city_mappings_db_version");
    delete_option("tipax_tapin_city_mapping_report");
    delete_transient( 'tipax_addresses' );
    delete_transient( 'addresses_loaded' );
    delete_transient( 'tipax_cities' );
    delete_transient( 'tipax_cities_sync_lock' );
    delete_transient( 'tipax_tapin_city_mapping_sync_lock' );
    delete_transient( 'tipax_packing_prices' );
    wp_clear_scheduled_hook('tipax_daily_tracking_sync');
    wp_clear_scheduled_hook('tipax_daily_city_mapping_sync');
    wp_clear_scheduled_hook('tipax_daily_city_mapping_sync_single');
}

function deleteTipaxCitiesTable(){
    global $wpdb;
    $table_name = $wpdb->prefix . 'tipax_cities';

    if($wpdb->get_var( "show tables like '$table_name'" ) == $table_name) {
        $sql = "Drop TABLE IF EXISTS $table_name;";
        $wpdb->query($sql);
        $is_error = empty( $wpdb->last_error );
        return $is_error;
    }
}

function deleteActivatedFile(){
    if (file_exists( TSM_DIR . '.activated' )) {
        unlink(TSM_DIR . '.activated');
    }
}

function deleteTipaxCityMappingsTable(){
    global $wpdb;
    $table_name = $wpdb->prefix . 'tipax_city_mappings';

    if($wpdb->get_var( "show tables like '$table_name'" ) == $table_name) {
        $sql = "Drop TABLE IF EXISTS $table_name;";
        $wpdb->query($sql);
        return empty($wpdb->last_error);
    }
}
