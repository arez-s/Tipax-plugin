<?php
if (!defined('ABSPATH')) {
    exit;
}

if (class_exists('Tipax_City_Service')) {
    return;
}

class Tipax_City_Service
{
    const DISABLED_OPTION = 'tipax_disabled_city_ids';
    const LAST_SYNC_OPTION = 'tipax_cities_last_sync';
    const SYNC_LOCK_TRANSIENT = 'tipax_cities_sync_lock';

    private $request;
    private $auth;
    private $database;
    private $table_name;
    private $admin_state_groups;

    public function __construct($request = null, $auth = null, $database = null)
    {
        global $wpdb;

        $this->request = $request ?: new Request();
        $this->auth = $auth ?: new Tipax_Auth_Service($this->request);
        $this->database = $database ?: $wpdb;
        $this->table_name = $this->database->prefix . 'tipax_cities';
    }

    public function table_exists()
    {
        $found = $this->database->get_var(
            $this->database->prepare('SHOW TABLES LIKE %s', $this->table_name)
        );

        return $found === $this->table_name;
    }

    public function get_last_sync()
    {
        $last_sync = get_option(self::LAST_SYNC_OPTION, []);
        return is_array($last_sync) ? $last_sync : [];
    }

    public function get_summary()
    {
        if (!$this->table_exists()) {
            return [
                'total' => 0,
                'states' => 0,
                'enabled' => 0,
                'disabled' => 0,
            ];
        }

        $total = 0;
        $states = 0;
        foreach ($this->get_admin_state_groups() as $group) {
            if (!$group['hidden']) {
                $total += $group['cityCount'];
                $states++;
            }
        }
        $disabled_ids = tipax_get_disabled_city_ids();
        $disabled = $this->count_existing_city_ids($disabled_ids, $this->get_hidden_state_titles());

        return [
            'total' => $total,
            'states' => $states,
            'enabled' => max(0, $total - $disabled),
            'disabled' => $disabled,
        ];
    }

    public function get_states()
    {
        if (!$this->table_exists()) {
            return [];
        }

        $states = [];
        foreach ($this->get_admin_state_groups() as $group) {
            if (!$group['hidden']) {
                $states[] = [
                    'stateId' => $group['stateId'],
                    'stateTitle' => $group['stateTitle'],
                    'cityCount' => $group['cityCount'],
                ];
            }
        }

        usort($states, static function ($first, $second) {
            return strcmp($first['stateTitle'], $second['stateTitle']);
        });

        return $states;
    }

    private function get_admin_state_groups()
    {
        if (is_array($this->admin_state_groups)) {
            return $this->admin_state_groups;
        }

        $canonical_titles = [];
        if (function_exists('tipax_get_iran_state_labels')) {
            foreach (tipax_get_iran_state_labels() as $title) {
                $canonical_titles[tipax_normalize_state_lookup_text($title)] = $title;
            }
        }

        $rows = $this->database->get_results(
            "SELECT MIN(stateId) AS stateId, stateTitle, COUNT(*) AS cityCount
             FROM {$this->table_name}
             GROUP BY stateTitle
             ORDER BY stateTitle ASC",
            ARRAY_A
        );

        $groups = [];
        foreach (is_array($rows) ? $rows : [] as $row) {
            $raw_title = (string) ($row['stateTitle'] ?? '');
            $key = tipax_normalize_state_lookup_text($raw_title);
            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'stateId' => absint($row['stateId'] ?? 0),
                    'stateTitle' => $canonical_titles[$key] ?? $raw_title,
                    'cityCount' => 0,
                    'titles' => [],
                    'hidden' => in_array(strtolower($key), ['rwprovince', 'ستادی', 'استانستادی'], true),
                ];
            }
            $groups[$key]['cityCount'] += (int) ($row['cityCount'] ?? 0);
            $groups[$key]['titles'][] = $raw_title;
            $state_id = absint($row['stateId'] ?? 0);
            if ($state_id > 0 && ($groups[$key]['stateId'] === 0 || $state_id < $groups[$key]['stateId'])) {
                $groups[$key]['stateId'] = $state_id;
            }
        }

        $this->admin_state_groups = $groups;
        return $groups;
    }

    private function get_hidden_state_titles()
    {
        $titles = [];
        foreach ($this->get_admin_state_groups() as $group) {
            if ($group['hidden']) {
                $titles = array_merge($titles, $group['titles']);
            }
        }
        return array_values(array_unique($titles));
    }

    public function get_cities(array $args = [])
    {
        $args = array_merge([
            'search' => '',
            'state_title' => '',
            'state_id' => 0,
            'status' => 'all',
            'page' => 1,
            'per_page' => 50,
        ], $args);

        if (!$this->table_exists()) {
            return [
                'items' => [],
                'total' => 0,
                'pages' => 0,
                'page' => 1,
                'per_page' => 50,
            ];
        }

        $page = max(1, absint($args['page']));
        $per_page = max(10, min(100, absint($args['per_page'])));
        $status = in_array($args['status'], ['all', 'enabled', 'disabled'], true) ? $args['status'] : 'all';
        $where = ['1=1'];
        $params = [];
        $state_groups = $this->get_admin_state_groups();
        $hidden_titles = $this->get_hidden_state_titles();
        if (!empty($hidden_titles)) {
            $where[] = 'stateTitle NOT IN (' . implode(', ', array_fill(0, count($hidden_titles), '%s')) . ')';
            $params = array_merge($params, $hidden_titles);
        }

        $search = trim((string) $args['search']);
        if ($search !== '') {
            $like = '%' . $this->database->esc_like($search) . '%';
            $search_condition = '(cityTitle LIKE %s OR stateTitle LIKE %s OR CAST(cityId AS CHAR) LIKE %s';
            array_push($params, $like, $like, $like);
            $search_key = tipax_normalize_state_lookup_text($search);
            $matching_state_titles = [];
            foreach ($state_groups as $group) {
                if (!$group['hidden'] && $search_key !== ''
                    && strpos(tipax_normalize_state_lookup_text($group['stateTitle']), $search_key) !== false) {
                    $matching_state_titles = array_merge($matching_state_titles, $group['titles']);
                }
            }
            if (!empty($matching_state_titles)) {
                $search_condition .= ' OR stateTitle IN (' . implode(', ', array_fill(0, count($matching_state_titles), '%s')) . ')';
                $params = array_merge($params, $matching_state_titles);
            }
            $where[] = $search_condition . ')';
        }

        $state_title = trim((string) $args['state_title']);
        $state_id = absint($args['state_id']);
        if ($state_title !== '') {
            $state_key = tipax_normalize_state_lookup_text($state_title);
            $state_titles = isset($state_groups[$state_key]) ? $state_groups[$state_key]['titles'] : [$state_title];
            $where[] = 'stateTitle IN (' . implode(', ', array_fill(0, count($state_titles), '%s')) . ')';
            $params = array_merge($params, $state_titles);
        } elseif ($state_id > 0) {
            $where[] = 'stateId = %d';
            $params[] = $state_id;
        }

        $disabled_ids = tipax_get_disabled_city_ids();
        if ($status === 'disabled') {
            if (empty($disabled_ids)) {
                $where[] = '1=0';
            } else {
                $where[] = 'cityId IN (' . implode(', ', array_fill(0, count($disabled_ids), '%d')) . ')';
                $params = array_merge($params, $disabled_ids);
            }
        } elseif ($status === 'enabled' && !empty($disabled_ids)) {
            $where[] = 'cityId NOT IN (' . implode(', ', array_fill(0, count($disabled_ids), '%d')) . ')';
            $params = array_merge($params, $disabled_ids);
        }

        $where_sql = implode(' AND ', $where);
        $count_sql = "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where_sql}";
        if (!empty($params)) {
            $count_sql = $this->database->prepare($count_sql, $params);
        }
        $total = (int) $this->database->get_var($count_sql);
        $pages = $total > 0 ? (int) ceil($total / $per_page) : 0;
        if ($pages > 0 && $page > $pages) {
            $page = $pages;
        }
        $offset = ($page - 1) * $per_page;

        // Sort by the displayed province title before pagination, so aliases stay together.
        $state_sort_cases = [];
        $state_sort_params = [];
        foreach ($state_groups as $group) {
            if ($group['hidden'] || (count($group['titles']) === 1 && $group['titles'][0] === $group['stateTitle'])) {
                continue;
            }
            $placeholders = implode(', ', array_fill(0, count($group['titles']), '%s'));
            $state_sort_cases[] = "WHEN stateTitle IN ({$placeholders}) THEN %s";
            $state_sort_params = array_merge($state_sort_params, $group['titles'], [$group['stateTitle']]);
        }
        $state_sort = empty($state_sort_cases)
            ? 'stateTitle'
            : 'CASE ' . implode(' ', $state_sort_cases) . ' ELSE stateTitle END';
        $list_sql = "SELECT id, cityId, cityTitle, stateId, stateTitle
                     FROM {$this->table_name}
                     WHERE {$where_sql}
                     ORDER BY {$state_sort} ASC, cityTitle ASC, cityId ASC
                     LIMIT %d OFFSET %d";
        $list_params = array_merge($params, $state_sort_params, [$per_page, $offset]);
        $list_sql = $this->database->prepare($list_sql, $list_params);
        $rows = $this->database->get_results($list_sql, ARRAY_A);
        $disabled_lookup = array_fill_keys($disabled_ids, true);
        $items = [];

        foreach (is_array($rows) ? $rows : [] as $row) {
            $city_id = absint($row['cityId'] ?? 0);
            $state_key = tipax_normalize_state_lookup_text($row['stateTitle'] ?? '');
            $items[] = [
                'id' => absint($row['id'] ?? 0),
                'city_id' => $city_id,
                'city_title' => (string) ($row['cityTitle'] ?? ''),
                'state_id' => absint($row['stateId'] ?? 0),
                'state_title' => $state_groups[$state_key]['stateTitle'] ?? (string) ($row['stateTitle'] ?? ''),
                'enabled' => !isset($disabled_lookup[$city_id]),
            ];
        }

        return [
            'items' => $items,
            'total' => $total,
            'pages' => $pages,
            'page' => $page,
            'per_page' => $per_page,
        ];
    }

    public function set_city_enabled($city_id, $enabled)
    {
        $city_id = absint($city_id);
        if (function_exists('tipax_resolve_city_id_alias')) {
            $city_id = tipax_resolve_city_id_alias($city_id);
        }
        if ($city_id <= 0 || !$this->city_exists($city_id)) {
            return [
                'success' => false,
                'message' => 'شهر انتخاب‌شده در جدول شهرهای تیپاکس پیدا نشد.',
            ];
        }

        $disabled_ids = tipax_get_disabled_city_ids();
        $lookup = array_fill_keys($disabled_ids, true);
        if ($enabled) {
            unset($lookup[$city_id]);
        } else {
            $lookup[$city_id] = true;
        }

        $saved_ids = array_map('absint', array_keys($lookup));
        sort($saved_ids, SORT_NUMERIC);
        update_option(self::DISABLED_OPTION, $saved_ids, false);

        return [
            'success' => true,
            'enabled' => (bool) $enabled,
            'message' => $enabled
                ? 'ارسال تیپاکس برای این شهر فعال شد.'
                : 'ارسال تیپاکس برای این شهر غیرفعال شد.',
        ];
    }

    public function sync_from_api()
    {
        if (function_exists('wp_raise_memory_limit')) {
            wp_raise_memory_limit('admin');
        }
        if (function_exists('set_time_limit')) {
            @set_time_limit(180);
        }

        if (get_transient(self::SYNC_LOCK_TRANSIENT)) {
            return [
                'success' => false,
                'message' => 'بروزرسانی شهرها در حال اجرا است. چند لحظه دیگر دوباره تلاش کنید.',
            ];
        }

        set_transient(self::SYNC_LOCK_TRANSIENT, 1, 5 * MINUTE_IN_SECONDS);

        try {
            if (!$this->table_exists()) {
                return [
                    'success' => false,
                    'message' => 'جدول شهرهای تیپاکس در دیتابیس وجود ندارد. افزونه را یک‌بار غیرفعال و دوباره فعال کنید.',
                ];
            }

            $access_token = $this->ensure_access_token();
            if (!$access_token) {
                return [
                    'success' => false,
                    'message' => 'توکن دسترسی تیپاکس در دسترس نیست. ابتدا اتصال افزونه را بررسی کنید.',
                ];
            }

            $response = $this->fetch_remote_cities($access_token);
            $records = $this->extract_response_records($response);

            if (empty($records) && !tipax_is_tipax_transport_error_response($response)) {
                $this->auth->check_api_key(true);
                $access_token = $this->auth->get_access_token();
                if ($access_token) {
                    $response = $this->fetch_remote_cities($access_token);
                    $records = $this->extract_response_records($response);
                }
            }

            if (tipax_is_tipax_transport_error_response($response)) {
                return [
                    'success' => false,
                    'message' => tipax_extract_tipax_response_message($response, 'ارتباط با سرویس شهرهای تیپاکس برقرار نشد.'),
                ];
            }

            $normalized = [];
            foreach ($records as $record) {
                $city = $this->normalize_remote_city($record);
                if ($city !== null) {
                    $normalized[$city['cityId']] = $city;
                }
            }

            $remote_aliases = [];
            if (function_exists('tipax_deduplicate_city_records_by_identity')) {
                $deduplicated = tipax_deduplicate_city_records_by_identity($normalized);
                $normalized = $deduplicated['records'];
                $remote_aliases = $deduplicated['aliases'];
            }

            if (empty($normalized)) {
                return [
                    'success' => false,
                    'message' => tipax_extract_tipax_response_message($response, 'هیچ شهر معتبری از سرویس تیپاکس دریافت نشد.'),
                ];
            }

            $added = 0;
            $updated = 0;
            $unchanged = 0;
            $replaced = 0;
            $failed = 0;
            $existing_lookup = $this->get_existing_city_lookup();
            if ($existing_lookup === null) {
                return [
                    'success' => false,
                    'message' => 'خواندن شهرهای فعلی از دیتابیس ناموفق بود.',
                ];
            }

            $replacement_aliases = function_exists('tipax_find_replaced_city_ids')
                ? tipax_find_replaced_city_ids($existing_lookup, $normalized)
                : [];
            if (function_exists('tipax_get_known_city_id_replacements')) {
                foreach (tipax_get_known_city_id_replacements() as $legacy_id => $current_id) {
                    $legacy_id = absint($legacy_id);
                    $current_id = absint($current_id);
                    if (
                        isset($existing_lookup[$legacy_id], $normalized[$current_id])
                        && tipax_location_titles_match(
                            $existing_lookup[$legacy_id]['stateTitle'],
                            $normalized[$current_id]['stateTitle']
                        )
                    ) {
                        $replacement_aliases[$legacy_id] = $current_id;
                    }
                }
            }
            $replacement_aliases = array_replace($remote_aliases, $replacement_aliases);
            $available_city_ids = array_fill_keys(array_keys($existing_lookup), true);

            $new_cities = [];
            foreach ($normalized as $city) {
                $city_id = (int) $city['cityId'];
                if (!isset($existing_lookup[$city_id])) {
                    $new_cities[] = $city;
                    continue;
                }

                $existing = $existing_lookup[$city_id];
                if ($this->city_data_matches($existing, $city)) {
                    $unchanged++;
                    continue;
                }

                $result = $this->database->update(
                    $this->table_name,
                    [
                        'cityTitle' => $city['cityTitle'],
                        'stateId' => $city['stateId'],
                        'stateTitle' => $city['stateTitle'],
                    ],
                    ['id' => $existing['id']],
                    ['%s', '%d', '%s'],
                    ['%d']
                );
                if ($result === false) {
                    $failed++;
                } else {
                    $updated++;
                }
            }

            foreach (array_chunk($new_cities, 100) as $city_batch) {
                $result = $this->insert_city_batch($city_batch);
                if ($result === false) {
                    $failed += count($city_batch);
                } else {
                    $added += count($city_batch);
                    foreach ($city_batch as $city) {
                        $available_city_ids[(int) $city['cityId']] = true;
                    }
                }
            }

            $applied_aliases = [];
            foreach ($replacement_aliases as $legacy_id => $current_id) {
                $legacy_id = absint($legacy_id);
                $current_id = absint($current_id);
                if ($legacy_id <= 0 || $current_id <= 0 || !isset($available_city_ids[$current_id])) {
                    continue;
                }

                if (isset($existing_lookup[$legacy_id])) {
                    $deleted = $this->database->delete(
                        $this->table_name,
                        ['id' => (int) $existing_lookup[$legacy_id]['id']],
                        ['%d']
                    );
                    if ($deleted === false) {
                        $failed++;
                        continue;
                    }
                    $replaced++;
                    unset($available_city_ids[$legacy_id]);
                }

                $applied_aliases[$legacy_id] = $current_id;
            }

            if (function_exists('tipax_update_city_id_aliases')) {
                tipax_update_city_id_aliases($applied_aliases, array_keys($normalized));
            }
            $this->migrate_disabled_city_ids($applied_aliases);

            delete_transient('tipax_cities');
            $this->admin_state_groups = null;
            $sync_data = [
                'timestamp' => time(),
                'received' => count($normalized),
                'added' => $added,
                'updated' => $updated,
                'unchanged' => $unchanged,
                'replaced' => $replaced,
                'failed' => $failed,
            ];
            update_option(self::LAST_SYNC_OPTION, $sync_data, false);
            do_action('tipax_cities_synchronized', $sync_data);

            $has_saved_rows = ($added + $updated + $unchanged + $replaced) > 0;

            return [
                'success' => $failed === 0 || $has_saved_rows,
                'partial' => $failed > 0,
                'message' => $failed === 0
                    ? sprintf('لیست شهرها بروزرسانی شد: %1$d شهر دریافت شد، %2$d شهر جدید، %3$d شهر تغییرکرده، %4$d شناسه قدیمی جایگزین و %5$d شهر بدون تغییر بود.', count($normalized), $added, $updated, $replaced, $unchanged)
                    : sprintf('بروزرسانی با خطای جزئی انجام شد. دریافت: %1$d، جدید: %2$d، تغییرکرده: %3$d، جایگزین‌شده: %4$d، بدون تغییر: %5$d، خطا: %6$d.', count($normalized), $added, $updated, $replaced, $unchanged, $failed),
                'stats' => $sync_data,
            ];
        } catch (Throwable $exception) {
            return [
                'success' => false,
                'message' => 'خطای داخلی هنگام بروزرسانی شهرها: ' . $exception->getMessage(),
            ];
        } finally {
            delete_transient(self::SYNC_LOCK_TRANSIENT);
        }
    }

    private function ensure_access_token()
    {
        $access_token = $this->auth->get_access_token();
        if (!$access_token) {
            $this->auth->check_api_key(true);
            $access_token = $this->auth->get_access_token();
        }

        return $access_token;
    }

    private function fetch_remote_cities($access_token)
    {
        return $this->request->apiRequest(
            'Cities/plusstate',
            [],
            'GET',
            $access_token,
            [
                'timeout' => 60,
                'return_transport_error' => true,
            ]
        );
    }

    private function get_existing_city_lookup()
    {
        $rows = $this->database->get_results(
            "SELECT id, cityId, cityTitle, stateId, stateTitle FROM {$this->table_name}",
            ARRAY_A
        );
        if (!is_array($rows)) {
            return null;
        }

        $lookup = [];
        foreach ($rows as $row) {
            $city_id = absint($row['cityId'] ?? 0);
            if ($city_id <= 0 || isset($lookup[$city_id])) {
                continue;
            }

            $lookup[$city_id] = [
                'id' => absint($row['id'] ?? 0),
                'cityId' => $city_id,
                'cityTitle' => (string) ($row['cityTitle'] ?? ''),
                'stateId' => absint($row['stateId'] ?? 0),
                'stateTitle' => (string) ($row['stateTitle'] ?? ''),
            ];
        }

        return $lookup;
    }

    private function city_data_matches(array $existing, array $remote)
    {
        return (string) $existing['cityTitle'] === (string) $remote['cityTitle']
            && (int) $existing['stateId'] === (int) $remote['stateId']
            && (string) $existing['stateTitle'] === (string) $remote['stateTitle'];
    }

    private function migrate_disabled_city_ids(array $aliases)
    {
        if (empty($aliases)) {
            return;
        }

        $disabled_lookup = array_fill_keys(tipax_get_disabled_city_ids(), true);
        $changed = false;
        foreach ($aliases as $legacy_id => $current_id) {
            $legacy_id = absint($legacy_id);
            $current_id = function_exists('tipax_resolve_city_id_alias')
                ? tipax_resolve_city_id_alias($current_id)
                : absint($current_id);
            if ($legacy_id <= 0 || $current_id <= 0 || !isset($disabled_lookup[$legacy_id])) {
                continue;
            }

            unset($disabled_lookup[$legacy_id]);
            $disabled_lookup[$current_id] = true;
            $changed = true;
        }

        if ($changed) {
            $disabled_ids = array_map('absint', array_keys($disabled_lookup));
            sort($disabled_ids, SORT_NUMERIC);
            update_option(self::DISABLED_OPTION, $disabled_ids, false);
        }
    }

    private function insert_city_batch(array $cities)
    {
        if (empty($cities)) {
            return 0;
        }

        $placeholders = [];
        $values = [];
        foreach ($cities as $city) {
            $placeholders[] = '(%d, %s, %d, %s)';
            array_push(
                $values,
                (int) $city['cityId'],
                (string) $city['cityTitle'],
                (int) $city['stateId'],
                (string) $city['stateTitle']
            );
        }

        $query = "INSERT INTO {$this->table_name} (cityId, cityTitle, stateId, stateTitle) VALUES "
            . implode(', ', $placeholders);
        $query = $this->database->prepare($query, $values);

        return $this->database->query($query);
    }

    private function extract_response_records($response)
    {
        if (is_array($response)) {
            return $response;
        }

        if (!is_object($response)) {
            return [];
        }

        foreach (['result', 'data', 'items'] as $property) {
            if (isset($response->{$property}) && is_array($response->{$property})) {
                return $response->{$property};
            }
        }

        return [];
    }

    private function normalize_remote_city($record)
    {
        if (is_object($record)) {
            $record = get_object_vars($record);
        }
        if (!is_array($record)) {
            return null;
        }

        $state = [];
        if (isset($record['state'])) {
            $state = is_object($record['state']) ? get_object_vars($record['state']) : (array) $record['state'];
        }

        $city_id = absint($record['id'] ?? ($record['cityId'] ?? 0));
        $city_title = sanitize_text_field((string) ($record['title'] ?? ($record['cityTitle'] ?? '')));
        $raw_state_id = $record['stateId'] ?? ($state['id'] ?? null);
        $state_id = is_numeric($raw_state_id) ? absint($raw_state_id) : 0;
        $state_title = sanitize_text_field((string) ($record['stateTitle'] ?? ($state['title'] ?? '')));

        if ($city_id <= 0 || $city_title === '') {
            return null;
        }

        if ($state_title === '') {
            $state_title = 'استان نامشخص';
        }

        return [
            'cityId' => $city_id,
            'cityTitle' => $city_title,
            'stateId' => $state_id,
            'stateTitle' => $state_title,
        ];
    }

    private function city_exists($city_id)
    {
        if (!$this->table_exists()) {
            return false;
        }

        return $this->database->get_var(
            $this->database->prepare(
                "SELECT cityId FROM {$this->table_name} WHERE cityId = %d LIMIT 1",
                absint($city_id)
            )
        ) !== null;
    }

    private function count_existing_city_ids(array $city_ids, array $hidden_state_titles = [])
    {
        if (empty($city_ids)) {
            return 0;
        }

        $placeholders = implode(', ', array_fill(0, count($city_ids), '%d'));
        $query = "SELECT COUNT(DISTINCT cityId) FROM {$this->table_name} WHERE cityId IN ({$placeholders})";
        $params = $city_ids;
        if (!empty($hidden_state_titles)) {
            $query .= ' AND stateTitle NOT IN (' . implode(', ', array_fill(0, count($hidden_state_titles), '%s')) . ')';
            $params = array_merge($params, $hidden_state_titles);
        }
        $query = $this->database->prepare($query, $params);

        return (int) $this->database->get_var($query);
    }
}

function tipax_get_disabled_city_ids()
{
    $city_ids = get_option(Tipax_City_Service::DISABLED_OPTION, []);
    if (!is_array($city_ids)) {
        return [];
    }

    $normalized = [];
    foreach ($city_ids as $city_id) {
        $city_id = absint($city_id);
        if ($city_id > 0) {
            $normalized[$city_id] = $city_id;
        }
    }

    return array_values($normalized);
}

function tipax_is_city_shipping_enabled($city_id)
{
    $city_id = absint($city_id);
    if (function_exists('tipax_resolve_city_id_alias')) {
        $city_id = tipax_resolve_city_id_alias($city_id);
    }
    if ($city_id <= 0) {
        return true;
    }

    return !in_array($city_id, tipax_get_disabled_city_ids(), true);
}
