<?php
if (!defined('ABSPATH')) {
    exit;
}

class Tipax_Order_Query
{
    private const SHIPPING_METHOD_ID = 'tipax_shipping_method';

    public function get_orders(array $args = [])
    {
        global $wpdb;

        $args = wp_parse_args($args, [
            'view' => 'tipax',
            'statuses' => ['processing', 'completed', 'on-hold', 'pending'],
            'page' => 1,
            'per_page' => 10,
            'from' => '',
            'to' => '',
            'return' => 'objects',
        ]);

        $view = $args['view'] === 'other' ? 'other' : 'tipax';
        $statuses = $this->normalize_statuses($args['statuses']);
        $page = max(1, absint($args['page']));
        $per_page = max(1, min(100, absint($args['per_page'])));
        $is_hpos = $this->is_hpos_enabled();
        $schema = $this->get_order_schema($is_hpos);
        $method_id = (string) apply_filters('tipax_orders_shipping_method_id', self::SHIPPING_METHOD_ID);

        $status_placeholders = implode(', ', array_fill(0, count($statuses), '%s'));
        $where = [
            "orders.{$schema['type_column']} = %s",
            "orders.{$schema['status_column']} IN ({$status_placeholders})",
        ];
        $parameters = array_merge(['shop_order'], $statuses);

        $from = $this->format_date_boundary($args['from'], false, $is_hpos);
        $to = $this->format_date_boundary($args['to'], true, $is_hpos);
        if ($from !== '') {
            $where[] = "orders.{$schema['date_column']} >= %s";
            $parameters[] = $from;
        }
        if ($to !== '') {
            $where[] = "orders.{$schema['date_column']} < %s";
            $parameters[] = $to;
        }

        $shipping_exists = "EXISTS (
            SELECT 1
            FROM {$wpdb->prefix}woocommerce_order_items shipping_items
            INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta shipping_meta
                ON shipping_meta.order_item_id = shipping_items.order_item_id
            WHERE shipping_items.order_id = orders.{$schema['id_column']}
                AND shipping_items.order_item_type = %s
                AND shipping_meta.meta_key = %s
                AND (shipping_meta.meta_value = %s OR shipping_meta.meta_value LIKE %s)
        )";
        $where[] = $view === 'other' ? "NOT {$shipping_exists}" : $shipping_exists;
        $parameters[] = 'shipping';
        $parameters[] = 'method_id';
        $parameters[] = $method_id;
        $parameters[] = $wpdb->esc_like($method_id) . ':%';

        $where_sql = implode("\n AND ", $where);
        $count_sql = "SELECT COUNT(*) FROM {$schema['table']} orders WHERE {$where_sql}";
        $total = (int) $wpdb->get_var($wpdb->prepare($count_sql, $parameters));
        $max_num_pages = $total > 0 ? (int) ceil($total / $per_page) : 0;

        if ($max_num_pages > 0) {
            $page = min($page, $max_num_pages);
        } else {
            $page = 1;
        }

        $offset = ($page - 1) * $per_page;
        $ids_sql = "SELECT orders.{$schema['id_column']}
            FROM {$schema['table']} orders
            WHERE {$where_sql}
            ORDER BY orders.{$schema['date_column']} DESC, orders.{$schema['id_column']} DESC
            LIMIT %d OFFSET %d";
        $id_parameters = array_merge($parameters, [$per_page, $offset]);
        $order_ids = array_map('absint', (array) $wpdb->get_col($wpdb->prepare($ids_sql, $id_parameters)));

        $orders = $order_ids;
        if ($args['return'] !== 'ids') {
            $orders = [];
            foreach ($order_ids as $order_id) {
                $order = wc_get_order($order_id);
                if ($order instanceof WC_Order) {
                    $orders[] = $order;
                }
            }
        }

        return [
            'orders' => $orders,
            'order_ids' => $order_ids,
            'total' => $total,
            'max_num_pages' => $max_num_pages,
            'page' => $page,
            'view' => $view,
            'storage' => $is_hpos ? 'hpos' : 'legacy',
        ];
    }

    private function normalize_statuses($statuses)
    {
        $statuses = is_array($statuses) ? $statuses : [$statuses];
        $normalized = [];

        foreach ($statuses as $status) {
            $status = sanitize_key((string) $status);
            if ($status === '') {
                continue;
            }
            $normalized[] = strpos($status, 'wc-') === 0 ? $status : 'wc-' . $status;
        }

        return !empty($normalized) ? array_values(array_unique($normalized)) : ['wc-processing'];
    }

    private function is_hpos_enabled()
    {
        return class_exists('Automattic\\WooCommerce\\Utilities\\OrderUtil')
            && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
    }

    private function get_order_schema($is_hpos)
    {
        global $wpdb;

        if ($is_hpos) {
            return [
                'table' => $wpdb->prefix . 'wc_orders',
                'id_column' => 'id',
                'type_column' => 'type',
                'status_column' => 'status',
                'date_column' => 'date_created_gmt',
            ];
        }

        return [
            'table' => $wpdb->posts,
            'id_column' => 'ID',
            'type_column' => 'post_type',
            'status_column' => 'post_status',
            'date_column' => 'post_date',
        ];
    }

    private function format_date_boundary($value, $exclusive_end, $use_gmt)
    {
        $value = is_scalar($value) ? trim((string) $value) : '';
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return '';
        }

        $timezone = $this->get_site_timezone();
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, $timezone);
        if (!$date || $date->format('Y-m-d') !== $value) {
            return '';
        }

        if ($exclusive_end) {
            $date = $date->modify('+1 day');
        }
        if ($use_gmt) {
            $date = $date->setTimezone(new DateTimeZone('UTC'));
        }

        return $date->format('Y-m-d H:i:s');
    }

    private function get_site_timezone()
    {
        if (function_exists('wp_timezone')) {
            return wp_timezone();
        }

        $timezone_string = (string) get_option('timezone_string', '');
        if ($timezone_string !== '') {
            try {
                return new DateTimeZone($timezone_string);
            } catch (Exception $exception) {
                // Fall through to the numeric WordPress offset.
            }
        }

        $offset = (float) get_option('gmt_offset', 0);
        $absolute_offset = abs($offset);
        $hours = (int) floor($absolute_offset);
        $minutes = (int) round(($absolute_offset - $hours) * 60);
        $timezone_name = sprintf('%s%02d:%02d', $offset < 0 ? '-' : '+', $hours, $minutes);

        return new DateTimeZone($timezone_name);
    }
}

