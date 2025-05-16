<?php
/**
 * Tipax shipping Uninstall
 *
 * Uninstalling Tipax shipping deletes tables, and options.
 *
 * @package           Tipax_Shipping
 * @version           1.0.0
 * @since             1.1.1
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

deletePossibleOptions();
deleteTipaxCitiesTable();
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
    delete_transient( 'tipax_addresses' );
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