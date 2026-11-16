<?php
/** Local Tipax-only totals; optional daily snapshots never run during checkout. */
if (!defined('ABSPATH')) { exit; }

class Tipax_Hub_Metrics
{
    const QUOTES = 'tipax_usage_quotes_total';
    const ORDERS = 'tipax_usage_orders_total';
    const STATE = 'tipax_usage_sync_state';
    const HOOK = 'tipax_hub_metrics_sync';
    const LOCK = 'tipax_usage_sync_lock';

    public function __construct()
    {
        add_action('init', [$this, 'schedule']);
        add_action(self::HOOK, [$this, 'flush']);
        add_action('update_option_woocommerce_tipax_shipping_method_settings', [$this, 'settings_changed'], 10, 2);
        add_action('add_option_woocommerce_tipax_shipping_method_settings', [$this, 'settings_added'], 10, 2);
    }

    public static function consent()
    {
        $settings = get_option('woocommerce_tipax_shipping_method_settings', []);
        return is_array($settings) && ($settings['hub_usage_metrics'] ?? 'no') === 'yes';
    }

    public static function totals()
    {
        return ['quotes_total' => max(0, (int) get_option(self::QUOTES, 0)),
            'orders_total' => max(0, (int) get_option(self::ORDERS, 0))];
    }

    public static function increment($type)
    {
        if (!in_array($type, ['quotes', 'orders'], true)) { return; }
        try {
            global $wpdb;
            $key = $type === 'quotes' ? self::QUOTES : self::ORDERS;
            // One atomic write. No customer, request payload or order ID is stored here.
            $wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->options} (option_name, option_value, autoload)
                VALUES (%s, '1', 'no') ON DUPLICATE KEY UPDATE option_value = CAST(option_value AS UNSIGNED) + 1", $key));
            wp_cache_delete($key, 'options');
            wp_cache_delete('notoptions', 'options');
        } catch (Throwable $exception) { /* Counting must not interrupt shipping. */ }
    }

    public static function record_order($order)
    {
        try {
            if (!is_object($order) || !method_exists($order, 'get_items')
                || !method_exists($order, 'save_meta_data') || $order->get_meta('_tipax_usage_counted', true) === 'yes') { return; }
            $isTipax = false;
            foreach ($order->get_items('shipping') as $shipping) {
                if (method_exists($shipping, 'get_method_id') && $shipping->get_method_id() === 'tipax_shipping_method') { $isTipax = true; break; }
            }
            if (!$isTipax) { return; }
            $order->update_meta_data('_tipax_usage_counted', 'yes');
            $order->save_meta_data();
            self::increment('orders');
        } catch (Throwable $exception) { /* Successful order registration stays successful. */ }
    }

    private function state()
    {
        $state = get_option(self::STATE, []);
        if (!is_array($state) || ($state['site_url'] ?? '') !== Tipax_Hub_Client::site_url()) {
            $state = ['site_url' => Tipax_Hub_Client::site_url(), 'counter_epoch' => wp_generate_uuid4(),
                'baseline' => self::totals(), 'baseline_at' => gmdate('c'), 'last_attempt' => 0];
            update_option(self::STATE, $state, false);
        }
        return $state;
    }

    public function settings_changed($old, $new)
    {
        $wasEnabled = is_array($old) && ($old['hub_usage_metrics'] ?? 'no') === 'yes';
        $enabled = is_array($new) && ($new['hub_usage_metrics'] ?? 'no') === 'yes';
        if (!$enabled || !$wasEnabled) {
            delete_option(self::STATE);
            wp_clear_scheduled_hook(self::HOOK);
        }
        if ($enabled) { $this->state(); }
        $this->schedule();
    }

    public function settings_added($option, $value)
    {
        $this->settings_changed([], $value);
        Tipax_Hub_Client::instance()->settings_changed([], $value);
    }

    public function schedule()
    {
        if (!Tipax_Hub_Client::enabled('metrics')) { wp_clear_scheduled_hook(self::HOOK); return; }
        $state = $this->state();
        if (!wp_next_scheduled(self::HOOK)) {
            $first = strtotime($state['baseline_at']) + DAY_IN_SECONDS;
            wp_schedule_event(max(time() + MINUTE_IN_SECONDS, $first, (int) $state['last_attempt'] + DAY_IN_SECONDS), 'daily', self::HOOK);
        }
    }

    public function flush()
    {
        if (!Tipax_Hub_Client::enabled('metrics') || !Tipax_Hub_Client::service_ready()) { return; }
        $lock = time() . ':' . wp_generate_uuid4();
        $existing = (string) get_option(self::LOCK, '');
        if ($existing !== '' && (int) $existing < time() - 300) { $this->unlock($existing); }
        if (!add_option(self::LOCK, $lock, '', false)) { return; }
        try {
            wp_cache_delete(self::STATE, 'options');
            $state = $this->state();
            if ((int) $state['last_attempt'] > time() - DAY_IN_SECONDS) { return; }
            if (empty($state['pending'])) {
                $state['pending'] = array_merge(self::totals(), [
                    'snapshot_id' => wp_generate_uuid4(), 'counter_epoch' => $state['counter_epoch'],
                    'captured_at' => gmdate('c'), 'period_started_at' => $state['baseline_at'],
                    'baseline_quotes' => $state['baseline']['quotes_total'], 'baseline_orders' => $state['baseline']['orders_total'],
                    'metrics_consent' => true,
                ]);
            }
            $state['last_attempt'] = time();
            update_option(self::STATE, $state, false);
            if (get_option(self::STATE) !== $state || !Tipax_Hub_Client::enabled('metrics')) { return; }
            $data = Tipax_Hub_Client::response_data(Tipax_Hub_Client::instance()->request('POST', '/api/v1/metrics/snapshot', $state['pending'], true));
            if (!$data || empty($data['accepted']) || ($data['snapshot_id'] ?? '') !== $state['pending']['snapshot_id']) { return; }
            wp_cache_delete(self::STATE, 'options');
            $current = get_option(self::STATE, []);
            if (!self::consent() || ($current['counter_epoch'] ?? '') !== $state['counter_epoch']) { return; }
            $state['baseline'] = ['quotes_total' => $state['pending']['quotes_total'], 'orders_total' => $state['pending']['orders_total']];
            $state['baseline_at'] = $state['pending']['captured_at'];
            unset($state['pending']);
            update_option(self::STATE, $state, false);
        } catch (Throwable $exception) { /* Keep the same pending snapshot for the next daily attempt. */ }
        finally { $this->unlock($lock); }
    }

    private function unlock($lock)
    {
        global $wpdb;
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s", self::LOCK, $lock));
        wp_cache_delete(self::LOCK, 'options');
    }
}
