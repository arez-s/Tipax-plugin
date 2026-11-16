<?php
if (!defined('ABSPATH')) {
    exit;
}

function tipax_normalize_settlement_amount($value)
{
    if (!is_scalar($value)) {
        return 0.0;
    }

    $value = str_replace([',', '٬', ' '], ['', '', ''], (string) $value);
    if ($value === '' || !is_numeric($value)) {
        return 0.0;
    }

    return max(0.0, (float) $value);
}

function tipax_get_settlement_config($settings = null)
{
    if (!is_array($settings)) {
        $settings = get_option('woocommerce_tipax_shipping_method_settings', []);
    }

    $legacy_method = isset($settings['checkout_method']) ? (int) $settings['checkout_method'] : 20;
    $has_new_settings = isset($settings['settlement_payer']);

    if ($has_new_settings) {
        $payer = $settings['settlement_payer'] === 'sender' ? 'sender' : 'receiver';
        $sender_method = isset($settings['sender_payment_method'])
            ? sanitize_key((string) $settings['sender_payment_method'])
            : 'wallet';
        $receiver_charge_mode = isset($settings['receiver_charge_mode'])
            ? sanitize_key((string) $settings['receiver_charge_mode'])
            : 'none';
        $show_postpay_amount = ($settings['show_postpay_amount'] ?? 'yes') === 'yes';
        $cod_enabled = ($settings['cod_enabled'] ?? 'no') === 'yes';
    } else {
        $payer = in_array($legacy_method, [20, 30], true) ? 'receiver' : 'sender';
        $sender_method = $legacy_method === 10 ? 'credit' : 'wallet';
        $receiver_charge_mode = 'none';
        $show_postpay_amount = true;
        $cod_enabled = $legacy_method === 30;

        // Keep the previous customer-facing rates until the merchant saves the new form.
        if ($legacy_method === 10) {
            $receiver_charge_mode = 'tipax';
        } elseif ($legacy_method === 100) {
            $sender_method = 'wallet';
            $receiver_charge_mode = 'fixed';
        }
    }

    // Normalize the old key saved by versions 3.8.0-3.8.2.
    if ($sender_method === 'cash_postpay') {
        $sender_method = 'cash';
    }
    if (!in_array($sender_method, ['credit', 'wallet', 'cash'], true)) {
        $sender_method = 'wallet';
    }
    if (!in_array($receiver_charge_mode, ['none', 'tipax', 'fixed'], true)) {
        $receiver_charge_mode = 'none';
    }

    $payment_types = [
        'credit' => 10,
        'wallet' => 50,
        'cash' => 100,
    ];
    $payment_type = $payer === 'receiver' ? 20 : $payment_types[$sender_method];

    return [
        'payer' => $payer,
        'sender_method' => $sender_method,
        'payment_type' => $payment_type,
        'show_postpay_amount' => $show_postpay_amount,
        'receiver_charge_mode' => $receiver_charge_mode,
        'inner_receiver_amount' => tipax_normalize_settlement_amount($settings['inner_flat_price'] ?? 0),
        'outer_receiver_amount' => tipax_normalize_settlement_amount($settings['outer_flat_price'] ?? 0),
        'inner_free_shipping_min' => tipax_normalize_settlement_amount($settings['inner_min_cart_free_shipping'] ?? 0),
        'outer_free_shipping_min' => tipax_normalize_settlement_amount($settings['outer_min_cart_free_shipping'] ?? 0),
        'cod_enabled' => $cod_enabled,
    ];
}

function tipax_calculate_rate_settlement($api_amount, $service_id, array $config, $cart_subtotal = 0)
{
    $api_amount = tipax_normalize_settlement_amount($api_amount);
    $cart_subtotal = tipax_normalize_settlement_amount($cart_subtotal);
    $is_inner_city = in_array((int) $service_id, [7, 12], true);
    $rate_cost = 0.0;
    $receiver_shipping_amount = 0.0;
    $is_free = false;

    if ($config['payer'] === 'receiver') {
        $receiver_shipping_amount = $api_amount;
    } else {
        // Sender-paid methods always settle the complete Tipax rate with Tipax.
        if ($config['receiver_charge_mode'] === 'tipax') {
            $rate_cost = $api_amount;
            $receiver_shipping_amount = $api_amount;
        } elseif ($config['receiver_charge_mode'] === 'fixed') {
            $fixed_amount = $is_inner_city
                ? $config['inner_receiver_amount']
                : $config['outer_receiver_amount'];
            $free_minimum = $is_inner_city
                ? $config['inner_free_shipping_min']
                : $config['outer_free_shipping_min'];

            $is_free = $free_minimum > 0 && $cart_subtotal >= $free_minimum;
            $rate_cost = $is_free ? 0.0 : $fixed_amount;
            $receiver_shipping_amount = $rate_cost;
        }
    }

    return [
        'rate_cost' => $rate_cost,
        'api_amount' => $api_amount,
        'receiver_shipping_amount' => $receiver_shipping_amount,
        'is_inner_city' => $is_inner_city,
        'is_free' => $is_free,
    ];
}

function tipax_get_order_settlement_context($order, $settings = null)
{
    $config = tipax_get_settlement_config($settings);
    $payment_type = (int) $config['payment_type'];
    $cod_enabled = (bool) $config['cod_enabled'];
    $receiver_shipping_amount = 0.0;
    $api_shipping_amount = 0.0;
    $has_order_payment_type = false;
    $has_order_cod_enabled = false;
    $has_order_receiver_amount = false;
    $has_order_api_amount = false;
    $uses_tipax_cod = (
        $order
        && is_callable([$order, 'get_payment_method'])
        && $order->get_payment_method() === 'tipax_cod'
    );
    $snapshot_version = 0;

    if ($order && is_callable([$order, 'get_meta'])) {
        $snapshot_version = (int) $order->get_meta('_tipax_settlement_snapshot_version', true);
        $order_payment_type = (int) $order->get_meta('_tipax_payment_type', true);
        if (in_array($order_payment_type, [10, 20, 50, 100], true)) {
            $payment_type = $order_payment_type;
            $has_order_payment_type = true;
        }

        $order_cod_enabled = (string) $order->get_meta('_tipax_cod_enabled', true);
        if (in_array($order_cod_enabled, ['yes', 'no'], true)) {
            $cod_enabled = $order_cod_enabled === 'yes';
            $has_order_cod_enabled = true;
        }

        $stored_receiver_amount = $order->get_meta('_tipax_receiver_shipping_amount', true);
        if ($stored_receiver_amount !== '') {
            $receiver_shipping_amount = tipax_normalize_settlement_amount($stored_receiver_amount);
            $has_order_receiver_amount = true;
        }
        $stored_api_amount = $order->get_meta('_tipax_api_shipping_amount', true);
        if ($stored_api_amount !== '') {
            $api_shipping_amount = tipax_normalize_settlement_amount($stored_api_amount);
            $has_order_api_amount = true;
        }
    }

    if ($order && is_callable([$order, 'get_shipping_methods'])) {
        foreach ($order->get_shipping_methods() as $shipping_item) {
            if (!is_callable([$shipping_item, 'get_method_id']) || $shipping_item->get_method_id() !== 'tipax_shipping_method') {
                continue;
            }

            $stored_payment_type = (int) $shipping_item->get_meta('_tipax_payment_type', true);
            if (!$has_order_payment_type && in_array($stored_payment_type, [10, 20, 50, 100], true)) {
                $payment_type = $stored_payment_type;
            }

            $stored_cod_enabled = (string) $shipping_item->get_meta('_tipax_cod_enabled', true);
            if (!$has_order_cod_enabled && in_array($stored_cod_enabled, ['yes', 'no'], true)) {
                $cod_enabled = $stored_cod_enabled === 'yes';
            }

            $item_receiver_amount = $shipping_item->get_meta('_tipax_receiver_shipping_amount', true);
            if (!$has_order_receiver_amount && $item_receiver_amount !== '') {
                $receiver_shipping_amount = tipax_normalize_settlement_amount($item_receiver_amount);
            }
            $item_api_amount = $shipping_item->get_meta('_tipax_api_shipping_amount', true);
            if (!$has_order_api_amount && $item_api_amount !== '') {
                $api_shipping_amount = tipax_normalize_settlement_amount($item_api_amount);
            }
            break;
        }
    }

    $cod_store_amount = 0.0;
    if ($uses_tipax_cod) {
        $cod_enabled = true;
        // Repair legacy/stale rate snapshots with the currently configured freight payer.
        if ($snapshot_version < 2) {
            $payment_type = (int) $config['payment_type'];
        }
        // The WooCommerce total already contains any configured customer shipping charge.
        $cod_store_amount = max(0.0, (float) $order->get_total());
    }

    $currency = $order && is_callable([$order, 'get_currency'])
        ? $order->get_currency()
        : get_woocommerce_currency();

    return [
        'payment_type' => $payment_type,
        'cod_enabled' => $cod_enabled,
        'cod_store_amount' => $cod_store_amount,
        'cod_api_amount' => tipax_store_value_to_api_value($cod_store_amount, $currency),
        'receiver_shipping_amount' => $receiver_shipping_amount,
        'api_shipping_amount' => $api_shipping_amount,
        'currency' => $currency,
    ];
}

function tipax_store_order_settlement_snapshot($order, $settings = null)
{
    if (!$order || !is_callable([$order, 'update_meta_data'])) {
        return false;
    }

    $config = tipax_get_settlement_config($settings);
    $payment_type = (int) $config['payment_type'];
    $cod_enabled = $config['cod_enabled'] ? 'yes' : 'no';
    $settlement_meta = [
        '_tipax_receiver_shipping_amount' => 0,
        '_tipax_api_shipping_amount' => 0,
        '_tipax_settlement_payer' => $config['payer'],
        '_tipax_sender_payment_method' => $config['sender_method'],
    ];

    foreach ($order->get_shipping_methods() as $shipping_item) {
        if ($shipping_item->get_method_id() !== 'tipax_shipping_method') {
            continue;
        }

        $item_payment_type = (int) $shipping_item->get_meta('_tipax_payment_type', true);
        if (in_array($item_payment_type, [10, 20, 50, 100], true)) {
            $payment_type = $item_payment_type;
        }

        $item_cod_enabled = (string) $shipping_item->get_meta('_tipax_cod_enabled', true);
        if (in_array($item_cod_enabled, ['yes', 'no'], true)) {
            $cod_enabled = $item_cod_enabled;
        }

        foreach (array_keys($settlement_meta) as $meta_key) {
            $item_value = $shipping_item->get_meta($meta_key, true);
            if ($item_value !== '') {
                $settlement_meta[$meta_key] = $item_value;
            }
        }
        break;
    }

    if (
        is_callable([$order, 'get_payment_method'])
        && $order->get_payment_method() === 'tipax_cod'
        && $config['cod_enabled']
    ) {
        // Current settings are authoritative at Checkout, not a cached shipping-rate snapshot.
        $payment_type = (int) $config['payment_type'];
        $cod_enabled = 'yes';
        $settlement_meta['_tipax_settlement_payer'] = $config['payer'];
        $settlement_meta['_tipax_sender_payment_method'] = $config['sender_method'];
    }

    $order->update_meta_data('_tipax_payment_type', $payment_type);
    $order->update_meta_data('_tipax_cod_enabled', $cod_enabled);
    $order->update_meta_data('_tipax_settlement_snapshot_version', 2);
    foreach ($settlement_meta as $meta_key => $meta_value) {
        $order->update_meta_data($meta_key, $meta_value);
    }
    return true;
}

function tipax_apply_cod_to_packages(array $packages, $cod_api_amount)
{
    $cod_api_amount = tipax_normalize_settlement_amount($cod_api_amount);
    $package_count = count($packages);
    if ($package_count === 0) {
        return $packages;
    }

    $total_weight = 0.0;
    foreach ($packages as $package) {
        if (is_object($package)) {
            $total_weight += max(0.0, (float) ($package->packageValue ?? 0));
        }
    }

    $allocated = 0.0;
    $position = 0;
    foreach ($packages as $package) {
        $position++;
        if (!is_object($package)) {
            continue;
        }

        if ($position === $package_count) {
            $package->cod = round(max(0.0, $cod_api_amount - $allocated), 2);
            $package->packageValue = max(
                tipax_normalize_settlement_amount($package->packageValue ?? 0),
                $package->cod
            );
            continue;
        }

        $ratio = $total_weight > 0
            ? max(0.0, (float) ($package->packageValue ?? 0)) / $total_weight
            : 1 / $package_count;
        $package_cod = round($cod_api_amount * $ratio, 2);
        $package->cod = $package_cod;
        $package->packageValue = max(
            tipax_normalize_settlement_amount($package->packageValue ?? 0),
            $package_cod
        );
        $allocated += $package_cod;
    }

    return $packages;
}

function tipax_apply_cod_gateway_policy(array $available_gateways, $cod_enabled, $chosen_methods)
{
    $is_tipax = false;
    if (is_array($chosen_methods)) {
        foreach ($chosen_methods as $chosen_method) {
            if (strpos((string) $chosen_method, 'tipax_shipping_method') !== false) {
                $is_tipax = true;
                break;
            }
        }
    }

    if ($cod_enabled && $is_tipax) {
        foreach (array_keys($available_gateways) as $gateway_id) {
            if ($gateway_id !== 'tipax_cod') {
                unset($available_gateways[$gateway_id]);
            }
        }
    } else {
        unset($available_gateways['tipax_cod']);
    }

    return $available_gateways;
}

function tipax_get_postpay_shipping_message()
{
    return 'پرداخت هزینه حمل درب منزل صورت می‌گیرد';
}

function tipax_is_postpay_shipping_rate($rate)
{
    if (!is_object($rate) || !is_callable([$rate, 'get_method_id']) || !is_callable([$rate, 'get_cost'])) {
        return false;
    }

    if ((string) $rate->get_method_id() !== 'tipax_shipping_method' || (float) $rate->get_cost() > 0) {
        return false;
    }

    $payer = '';
    if (is_callable([$rate, 'get_meta_data'])) {
        $meta_data = $rate->get_meta_data();
        if (is_array($meta_data) && isset($meta_data['_tipax_settlement_payer'])) {
            $payer = (string) $meta_data['_tipax_settlement_payer'];
        }
    }
    if ($payer === '') {
        $config = tipax_get_settlement_config();
        $payer = (string) ($config['payer'] ?? '');
    }

    return $payer === 'receiver';
}

function tipax_filter_postpay_shipping_label($label, $rate)
{
    if (!tipax_is_postpay_shipping_rate($rate)) {
        return $label;
    }

    $rate_label = is_callable([$rate, 'get_label']) ? (string) $rate->get_label() : '';
    if ($rate_label === '') {
        $rate_label = wp_strip_all_tags((string) $label);
    }

    return esc_html($rate_label)
        . ' <span class="tipax-postpay-shipping-message">'
        . esc_html(tipax_get_postpay_shipping_message())
        . '</span>';
}
add_filter('woocommerce_cart_shipping_method_full_label', 'tipax_filter_postpay_shipping_label', 100, 2);

function tipax_enqueue_postpay_shipping_label_script()
{
    if (is_admin() || (!is_checkout() && !is_cart())) {
        return;
    }

    $config = tipax_get_settlement_config();
    if (($config['payer'] ?? '') !== 'receiver') {
        return;
    }

    $script_path = TSM_DIR . 'assets/frontend/js/postpay-shipping-label.js';
    $style_path = TSM_DIR . 'assets/frontend/css/postpay-shipping-label.css';
    $version = defined('TSM_VERSION') ? TSM_VERSION : null;
    $style_version = $version;
    if (file_exists($script_path)) {
        $version = (string) $version . '.' . filemtime($script_path);
    }
    if (file_exists($style_path)) {
        $style_version = (string) $style_version . '.' . filemtime($style_path);
    }

    wp_enqueue_style(
        'tipax-postpay-shipping-label',
        TSM_ASSETS_URL . 'frontend/css/postpay-shipping-label.css',
        [],
        $style_version
    );

    wp_enqueue_script(
        'tipax-postpay-shipping-label',
        TSM_ASSETS_URL . 'frontend/js/postpay-shipping-label.js',
        [],
        $version,
        true
    );
    wp_localize_script('tipax-postpay-shipping-label', 'tipaxPostpayShipping', [
        'message' => tipax_get_postpay_shipping_message(),
        'rateIdFragment' => 'tipax_shipping_method',
    ]);
}
add_action('wp_enqueue_scripts', 'tipax_enqueue_postpay_shipping_label_script', 30);
