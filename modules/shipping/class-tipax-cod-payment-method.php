<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_Gateway_Tipax_COD extends WC_Payment_Gateway
{
    public function __construct()
    {
        $this->id = 'tipax_cod';
        $this->method_title = 'Tipax COD';
        $this->method_description = 'پرداخت نقدی مخصوص ارسال با تیپاکس';
        $this->has_fields = false;
        $this->supports = ['products'];
        $this->enabled = 'yes';
        $this->title = 'پرداخت نقدی تیپاکس';

        $this->init_form_fields();
        $this->init_settings();

        $this->enabled = $this->get_option('enabled');
        $cod_enabled = false;
        if (function_exists('tipax_get_settlement_config')) {
            $settlement = tipax_get_settlement_config();
            $cod_enabled = $settlement['cod_enabled'];
        } else {
            $plugin_settings = get_option('woocommerce_tipax_shipping_method_settings', []);
            $cod_enabled = ($plugin_settings['cod_enabled'] ?? 'no') === 'yes'
                || (int) ($plugin_settings['checkout_method'] ?? 0) === 30;
        }
        if ($cod_enabled) {
            $this->enabled = 'yes';
        }
        $this->title   = $this->get_option('title');

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
    }

    public function init_form_fields()
    {
        $this->form_fields = [
            'enabled' => [
                'title'   => 'فعال‌سازی',
                'type'    => 'checkbox',
                'label'   => 'فعال کردن پرداخت نقدی تیپاکس',
                'default' => 'yes'
            ],
            'title' => [
                'title'       => 'عنوان',
                'type'        => 'text',
                'description' => 'عنوان روش پرداخت در هنگام پرداخت.',
                'default'     => 'پرداخت نقدی تیپاکس',
                'desc_tip'    => true,
            ]
        ];
    }


    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            wc_add_notice('سفارش برای پرداخت در محل تیپاکس پیدا نشد.', 'error');
            return ['result' => 'failure'];
        }

        if ($order->get_total() > 0) {
            $order->update_status('on-hold', 'مبلغ سفارش هنگام تحویل توسط تیپاکس از گیرنده دریافت می‌شود.');
        } else {
            $order->payment_complete();
        }

        wc_reduce_stock_levels($order_id);
        if (WC()->cart) {
            WC()->cart->empty_cart();
        }

        return [
            'result'   => 'success',
            'redirect' => $this->get_return_url($order)
        ];
    }
}
