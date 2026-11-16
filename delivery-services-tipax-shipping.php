<?php
/**
 * The plugin bootstrap file.
 *
 * @since             1.0.0
 * @package           Delivery_Services_Tipax_Shipping
 *
 * @wordpress-plugin
 * Plugin Name:       Delivery Service Tipax Shipping
 * Description:       اتصال فروشگاه ووکامرس به تیپاکس برای استعلام هزینه حمل، ثبت و رهگیری مرسوله‌ها و چاپ برچسب ارسال.
 * Version:           3.13.6
 * Plugin URI:        https://tipaxplugin.ir/
 * Update URI:        https://tipaxplugin.ir
 * Requires at least: 5.8
 * Tested up to:      7.0
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * WC requires at least: 7.1
 * WC tested up to:   10.9
 * Author:            علیرضا صباحی
 * Text Domain:       delivery-service-tipax-shipping
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 * Domain Path:       /languages/
 */

if (!defined('ABSPATH')) {
    exit;
}

define('TIPAX_PLUGIN_FILE', __FILE__);

add_action('before_woocommerce_init', static function () {
    if (class_exists('Automattic\\WooCommerce\\Utilities\\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables',
            TIPAX_PLUGIN_FILE,
            true
        );
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'cart_checkout_blocks',
            TIPAX_PLUGIN_FILE,
            true
        );
    }
});

require_once __DIR__ . '/tipax-woocommerce.php';

return Tipax_Plugin::instance(TIPAX_PLUGIN_FILE);
