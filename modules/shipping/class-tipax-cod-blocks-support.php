<?php
if (!defined('ABSPATH')) {
    exit;
}

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class Tipax_COD_Blocks_Support extends AbstractPaymentMethodType
{
    protected $name = 'tipax_cod';

    public function initialize()
    {
        $this->settings = get_option('woocommerce_tipax_cod_settings', []);
    }

    public function is_active()
    {
        $plugin_settings = get_option('woocommerce_tipax_shipping_method_settings', []);
        $cod_enabled = function_exists('tipax_get_settlement_config')
            ? (bool) tipax_get_settlement_config($plugin_settings)['cod_enabled']
            : ($plugin_settings['cod_enabled'] ?? 'no') === 'yes';

        return $cod_enabled && $this->get_setting('enabled', 'yes') === 'yes';
    }

    public function get_payment_method_script_handles()
    {
        $handle = 'tipax-cod-blocks';
        $script_path = TSM_DIR . 'assets/frontend/js/tipax-cod-blocks.js';
        $version = TSM_VERSION . (file_exists($script_path) ? '.' . filemtime($script_path) : '');

        wp_register_script(
            $handle,
            TSM_ASSETS_URL . 'frontend/js/tipax-cod-blocks.js',
            ['wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-html-entities'],
            $version,
            true
        );

        return [$handle];
    }

    public function get_payment_method_data()
    {
        return [
            'title' => $this->get_setting('title', 'پرداخت نقدی تیپاکس'),
            'description' => 'مبلغ سفارش هنگام تحویل مرسوله توسط تیپاکس دریافت می‌شود.',
            'supports' => ['products'],
        ];
    }
}
