<?php
if (!defined('ABSPATH')) {
    exit;
}

if (class_exists('Tipax_City_Mapping_Service')) {
    return;
}

class Tipax_City_Mapping_Service
{
    const SOURCE_TAPIN = 'tapin';
    const LAST_REPORT_OPTION = 'tipax_tapin_city_mapping_report';
    const SYNC_LOCK_TRANSIENT = 'tipax_tapin_city_mapping_sync_lock';
    const DAILY_SYNC_HOOK = 'tipax_daily_city_mapping_sync';

    private $database;
    private $table_name;
    private static $tapin_catalog = null;

    public function __construct($database = null)
    {
        global $wpdb;

        $this->database = $database ?: $wpdb;
        $this->table_name = $this->database->prefix . 'tipax_city_mappings';
    }

    public static function maybe_schedule_sync()
    {
        if (!wp_next_scheduled(self::DAILY_SYNC_HOOK)) {
            wp_schedule_event(time() + 5 * MINUTE_IN_SECONDS, 'daily', self::DAILY_SYNC_HOOK);
        }

        $report = get_option(self::LAST_REPORT_OPTION, []);
        $last_sync = is_array($report) ? absint($report['timestamp'] ?? 0) : 0;
        if ($last_sync <= 0 && !wp_next_scheduled(self::DAILY_SYNC_HOOK . '_single')) {
            wp_schedule_single_event(time() + MINUTE_IN_SECONDS, self::DAILY_SYNC_HOOK . '_single');
        }
    }

    public static function schedule_immediate_sync()
    {
        if (!wp_next_scheduled(self::DAILY_SYNC_HOOK . '_single')) {
            wp_schedule_single_event(time() + MINUTE_IN_SECONDS, self::DAILY_SYNC_HOOK . '_single');
        }
    }

    public function table_exists()
    {
        $found = $this->database->get_var(
            $this->database->prepare('SHOW TABLES LIKE %s', $this->table_name)
        );

        return $found === $this->table_name;
    }

    public function get_last_report()
    {
        $report = get_option(self::LAST_REPORT_OPTION, []);
        return is_array($report) ? $report : [];
    }

    public function resolve_manual_city_id($external_city_id, $state_name)
    {
        return $this->resolve_stored_city_id($external_city_id, $state_name, true);
    }

    public function resolve_city_id($external_city_id, $state_name)
    {
        $contexts = $this->get_tapin_contexts_for_city($external_city_id, $state_name);
        if (empty($contexts)) {
            return null;
        }

        $resolved_ids = [];
        foreach ($contexts as $context) {
            $stored = $this->get_mapping($context);
            if (
                is_array($stored)
                && ($stored['matchType'] ?? '') !== 'manual'
                && $this->mapping_is_valid($stored, $context['state_title'])
            ) {
                $resolved_id = tipax_find_city_id_by_tipax_id($stored['tipaxCityId'], $context['state_title']);
                if ($resolved_id !== null) {
                    $resolved_ids[$resolved_id] = $resolved_id;
                }
                continue;
            }

            $analysis = $this->analyze_context($context);
            if ($analysis['tipax_city_id'] > 0 && in_array($analysis['match_type'], ['exact', 'industrial_alias', 'qualified_alias'], true)) {
                if (in_array($analysis['match_type'], ['industrial_alias', 'qualified_alias'], true)) {
                    $this->save_mapping($context, $analysis);
                }
                $resolved_ids[$analysis['tipax_city_id']] = $analysis['tipax_city_id'];
            }
        }

        return count($resolved_ids) === 1 ? reset($resolved_ids) : null;
    }

    public function sync_mappings()
    {
        if (get_transient(self::SYNC_LOCK_TRANSIENT)) {
            return [
                'success' => false,
                'message' => 'همگام‌سازی نگاشت شهرهای تاپین در حال اجرا است.',
            ];
        }

        set_transient(self::SYNC_LOCK_TRANSIENT, 1, 10 * MINUTE_IN_SECONDS);

        try {
            if (!$this->table_exists()) {
                return [
                    'success' => false,
                    'message' => 'جدول نگاشت شهرهای تاپین و تیپاکس وجود ندارد.',
                ];
            }

            self::$tapin_catalog = null;
            $catalog = $this->get_tapin_catalog();
            if (empty($catalog['available'])) {
                return [
                    'success' => false,
                    'message' => 'داده شهرهای تاپین در transientهای وردپرس پیدا نشد.',
                    'report' => [
                        'timestamp' => time(),
                        'source_available' => false,
                    ],
                ];
            }

            $existing_manual = $this->get_manual_mapping_lookup();
            $rows = [];
            $mapped_tipax_ids = [];
            $tapin_unmatched = [];
            $tapin_ambiguous = [];
            $industrial_aliases = [];
            $qualified_aliases = [];
            $counts = [
                'tapin_total' => count($catalog['items']),
                'exact' => 0,
                'industrial_alias' => 0,
                'qualified_alias' => 0,
                'manual' => 0,
                'ambiguous' => 0,
                'unmatched' => 0,
            ];

            foreach ($catalog['items'] as $context) {
                $key = $this->context_key($context);
                $manual = $existing_manual[$key] ?? null;
                if (is_array($manual) && $this->mapping_is_valid($manual, $context['state_title'])) {
                    $manual_city_id = tipax_find_city_id_by_tipax_id($manual['tipaxCityId'], $context['state_title']);
                    $analysis = [
                        'match_type' => 'manual',
                        'tipax_city_id' => (int) $manual_city_id,
                        'tipax_city_title' => $this->get_tipax_city_title((int) $manual_city_id, $context['state_title']),
                        'candidates' => [(int) $manual_city_id],
                    ];
                } else {
                    $analysis = $this->analyze_context($context);
                }

                $counts[$analysis['match_type']]++;
                if ($analysis['tipax_city_id'] > 0) {
                    $mapped_tipax_ids[$analysis['tipax_city_id']] = true;
                }

                if ($analysis['match_type'] === 'industrial_alias') {
                    $industrial_aliases[] = $this->build_report_item($context, $analysis);
                } elseif ($analysis['match_type'] === 'qualified_alias') {
                    $qualified_aliases[] = $this->build_report_item($context, $analysis);
                } elseif ($analysis['match_type'] === 'ambiguous') {
                    $tapin_ambiguous[] = $this->build_report_item($context, $analysis);
                } elseif ($analysis['match_type'] === 'unmatched') {
                    $tapin_unmatched[] = $this->build_report_item($context, $analysis);
                }

                // Exact names need no persistent mapping; missing and ambiguous cities belong only in the report.
                if (in_array($analysis['match_type'], ['industrial_alias', 'qualified_alias'], true)) {
                    $rows[] = $this->build_mapping_row($context, $analysis);
                }
            }

            $tipax_unmatched = [];
            foreach ($this->get_all_tipax_cities() as $tipax_city) {
                $tipax_city_id = absint($tipax_city['cityId'] ?? 0);
                if ($tipax_city_id <= 0 || isset($mapped_tipax_ids[$tipax_city_id])) {
                    continue;
                }

                $tipax_unmatched[] = [
                    'tipax_city_id' => $tipax_city_id,
                    'tipax_city_title' => (string) ($tipax_city['cityTitle'] ?? ''),
                    'state_id' => absint($tipax_city['stateId'] ?? 0),
                    'state_title' => (string) ($tipax_city['stateTitle'] ?? ''),
                ];
            }

            if (!$this->replace_automatic_rows($rows)) {
                return [
                    'success' => false,
                    'message' => 'ذخیره نگاشت شهرهای تاپین در دیتابیس ناموفق بود.',
                ];
            }

            $report = [
                'timestamp' => time(),
                'source_available' => true,
                'counts' => array_merge($counts, [
                    'tapin_mapped' => $counts['exact'] + $counts['industrial_alias'] + $counts['qualified_alias'] + $counts['manual'],
                    'tipax_total' => count($this->get_all_tipax_cities()),
                    'tipax_unmatched' => count($tipax_unmatched),
                ]),
                'tapin_unmatched' => $tapin_unmatched,
                'tapin_ambiguous' => $tapin_ambiguous,
                'industrial_aliases' => $industrial_aliases,
                'qualified_aliases' => $qualified_aliases,
                'tipax_unmatched' => $tipax_unmatched,
            ];
            update_option(self::LAST_REPORT_OPTION, $report, false);

            if (function_exists('tipax_log')) {
                tipax_log(sprintf(
                    'Tapin city mapping synced. total=%d mapped=%d ambiguous=%d unmatched=%d reverse_unmatched=%d',
                    $counts['tapin_total'],
                    $report['counts']['tapin_mapped'],
                    $counts['ambiguous'],
                    $counts['unmatched'],
                    count($tipax_unmatched)
                ));
            }

            return [
                'success' => true,
                'message' => 'نگاشت شهرهای تاپین و تیپاکس بروزرسانی شد.',
                'report' => $report,
            ];
        } catch (Throwable $exception) {
            return [
                'success' => false,
                'message' => 'خطای داخلی نگاشت شهرها: ' . $exception->getMessage(),
            ];
        } finally {
            delete_transient(self::SYNC_LOCK_TRANSIENT);
        }
    }

    private function resolve_stored_city_id($external_city_id, $state_name, $manual_only)
    {
        if (!$this->table_exists()) {
            return null;
        }

        $resolved_ids = [];
        foreach ($this->get_tapin_contexts_for_city($external_city_id, $state_name) as $context) {
            $mapping = $this->get_mapping($context);
            if (!is_array($mapping)) {
                continue;
            }
            if ($manual_only && ($mapping['matchType'] ?? '') !== 'manual') {
                continue;
            }
            if (!$this->mapping_is_valid($mapping, $context['state_title'])) {
                continue;
            }

            $tipax_city_id = tipax_find_city_id_by_tipax_id($mapping['tipaxCityId'], $context['state_title']);
            if ($tipax_city_id !== null) {
                $resolved_ids[$tipax_city_id] = $tipax_city_id;
            }
        }

        return count($resolved_ids) === 1 ? reset($resolved_ids) : null;
    }

    private function analyze_context(array $context)
    {
        $exact_candidates = $this->get_exact_candidates($context['city_title'], $context['state_title']);
        if (count($exact_candidates) === 1) {
            $candidate = reset($exact_candidates);
            return [
                'match_type' => 'exact',
                'tipax_city_id' => (int) $candidate['cityId'],
                'tipax_city_title' => (string) $candidate['cityTitle'],
                'candidates' => [(int) $candidate['cityId']],
            ];
        }
        if (count($exact_candidates) > 1) {
            return $this->build_ambiguous_analysis($exact_candidates);
        }

        $alias_candidates = $this->get_industrial_alias_candidates($context['city_title'], $context['state_title']);
        if (count($alias_candidates) === 1) {
            $candidate = reset($alias_candidates);
            return [
                'match_type' => 'industrial_alias',
                'tipax_city_id' => (int) $candidate['cityId'],
                'tipax_city_title' => (string) $candidate['cityTitle'],
                'candidates' => [(int) $candidate['cityId']],
            ];
        }
        if (count($alias_candidates) > 1) {
            return $this->build_ambiguous_analysis($alias_candidates);
        }

        $qualified_candidates = $this->get_qualified_alias_candidates($context['city_title'], $context['state_title']);
        if (count($qualified_candidates) === 1) {
            $candidate = reset($qualified_candidates);
            return [
                'match_type' => 'qualified_alias',
                'tipax_city_id' => (int) $candidate['cityId'],
                'tipax_city_title' => (string) $candidate['cityTitle'],
                'candidates' => [(int) $candidate['cityId']],
            ];
        }
        if (count($qualified_candidates) > 1) {
            return $this->build_ambiguous_analysis($qualified_candidates);
        }

        return [
            'match_type' => 'unmatched',
            'tipax_city_id' => 0,
            'tipax_city_title' => '',
            'candidates' => [],
        ];
    }

    private function build_ambiguous_analysis(array $candidates)
    {
        return [
            'match_type' => 'ambiguous',
            'tipax_city_id' => 0,
            'tipax_city_title' => '',
            'candidates' => array_values(array_map('intval', array_keys($candidates))),
        ];
    }

    private function get_exact_candidates($city_title, $state_title)
    {
        $matches = [];
        foreach (tipax_get_city_rows_for_state($state_title) as $city) {
            if (!is_object($city) || !tipax_location_titles_match($city->cityTitle ?? '', $city_title, true)) {
                continue;
            }

            $city_id = absint($city->cityId ?? 0);
            if ($city_id > 0) {
                $matches[$city_id] = [
                    'cityId' => $city_id,
                    'cityTitle' => (string) ($city->cityTitle ?? ''),
                ];
            }
        }

        return $matches;
    }

    private function get_industrial_alias_candidates($city_title, $state_title)
    {
        $matches = [];
        foreach (tipax_get_city_rows_for_state($state_title) as $city) {
            if (!is_object($city) || !tipax_city_titles_match_safe_industrial_alias($city->cityTitle ?? '', $city_title)) {
                continue;
            }

            $city_id = absint($city->cityId ?? 0);
            if ($city_id > 0) {
                $matches[$city_id] = [
                    'cityId' => $city_id,
                    'cityTitle' => (string) ($city->cityTitle ?? ''),
                ];
            }
        }

        return $matches;
    }

    private function get_qualified_alias_candidates($city_title, $state_title)
    {
        $matches = [];
        foreach (tipax_get_city_rows_for_state($state_title) as $city) {
            if (!is_object($city) || !tipax_city_titles_match_safe_qualified_alias($city->cityTitle ?? '', $city_title, $state_title)) {
                continue;
            }

            $city_id = absint($city->cityId ?? 0);
            if ($city_id > 0) {
                $matches[$city_id] = [
                    'cityId' => $city_id,
                    'cityTitle' => (string) ($city->cityTitle ?? ''),
                ];
            }
        }

        return $matches;
    }

    private function get_tapin_catalog()
    {
        if (self::$tapin_catalog !== null) {
            return self::$tapin_catalog;
        }

        $states = get_transient('pws_tapin_states');
        if (!is_array($states) || empty($states)) {
            self::$tapin_catalog = ['available' => false, 'items' => []];
            return self::$tapin_catalog;
        }

        $items = [];
        foreach ($this->normalize_external_collection($states, ['id', 'stateId'], ['title', 'name', 'stateTitle']) as $state) {
            $cities = get_transient('pws_tapin_cities_' . $state['id']);
            if (!is_array($cities)) {
                continue;
            }

            foreach ($this->normalize_external_collection($cities, ['id', 'cityId'], ['title', 'name', 'cityTitle']) as $city) {
                $items[] = [
                    'source' => self::SOURCE_TAPIN,
                    'state_id' => $state['id'],
                    'state_title' => $state['title'],
                    'city_id' => $city['id'],
                    'city_title' => $city['title'],
                ];
            }
        }

        self::$tapin_catalog = [
            'available' => !empty($items),
            'items' => $items,
        ];
        return self::$tapin_catalog;
    }

    private function get_tapin_contexts_for_city($external_city_id, $state_name)
    {
        $external_city_id = $this->normalize_external_id($external_city_id);
        if ($external_city_id === '' || trim((string) $state_name) === '') {
            return [];
        }

        $contexts = [];
        foreach ($this->get_tapin_catalog()['items'] ?? [] as $context) {
            if (
                $context['city_id'] === $external_city_id
                && tipax_location_titles_match($context['state_title'], $state_name)
            ) {
                $contexts[] = $context;
            }
        }

        return $contexts;
    }

    private function normalize_external_collection(array $records, array $id_fields, array $title_fields)
    {
        $normalized = [];
        foreach ($records as $record_key => $record) {
            $record_array = is_object($record) ? get_object_vars($record) : (is_array($record) ? $record : []);
            $raw_id = $record_key;
            $raw_title = is_scalar($record) ? $record : '';

            foreach ($id_fields as $field) {
                if (isset($record_array[$field]) && is_scalar($record_array[$field])) {
                    $raw_id = $record_array[$field];
                    break;
                }
            }
            foreach ($title_fields as $field) {
                if (isset($record_array[$field]) && is_scalar($record_array[$field])) {
                    $raw_title = $record_array[$field];
                    break;
                }
            }

            $id = $this->normalize_external_id($raw_id);
            $title = sanitize_text_field((string) $raw_title);
            if ($id !== '' && $title !== '') {
                $normalized[] = ['id' => $id, 'title' => $title];
            }
        }

        return $normalized;
    }

    private function normalize_external_id($value)
    {
        if (!is_scalar($value)) {
            return '';
        }

        $value = preg_replace('/[^A-Za-z0-9_-]/', '', trim((string) $value));
        return is_string($value) ? substr($value, 0, 64) : '';
    }

    private function get_mapping(array $context)
    {
        if (!$this->table_exists()) {
            return null;
        }

        $row = $this->database->get_row(
            $this->database->prepare(
                "SELECT * FROM {$this->table_name} WHERE source = %s AND externalStateId = %s AND externalCityId = %s LIMIT 1",
                $context['source'],
                $context['state_id'],
                $context['city_id']
            ),
            ARRAY_A
        );

        return is_array($row) ? $row : null;
    }

    private function get_manual_mapping_lookup()
    {
        $rows = $this->database->get_results(
            "SELECT * FROM {$this->table_name} WHERE source = 'tapin' AND matchType = 'manual'",
            ARRAY_A
        );
        $lookup = [];
        foreach (is_array($rows) ? $rows : [] as $row) {
            $lookup[$this->mapping_row_key($row)] = $row;
        }

        return $lookup;
    }

    private function mapping_is_valid(array $mapping, $state_title)
    {
        $tipax_city_id = absint($mapping['tipaxCityId'] ?? 0);
        return $tipax_city_id > 0 && tipax_find_city_id_by_tipax_id($tipax_city_id, $state_title) !== null;
    }

    private function save_mapping(array $context, array $analysis)
    {
        $row = $this->build_mapping_row($context, $analysis);
        return $this->database->replace(
            $this->table_name,
            $row,
            ['%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s']
        ) !== false;
    }

    private function replace_automatic_rows(array $rows)
    {
        $this->database->query('START TRANSACTION');
        try {
            $deleted = $this->database->query(
                "DELETE FROM {$this->table_name} WHERE source = 'tapin' AND matchType <> 'manual'"
            );
            if ($deleted === false) {
                throw new RuntimeException((string) $this->database->last_error);
            }

            foreach (array_chunk($rows, 100) as $chunk) {
                if (!$this->insert_mapping_batch($chunk)) {
                    throw new RuntimeException((string) $this->database->last_error);
                }
            }

            $this->database->query('COMMIT');
            return true;
        } catch (Throwable $exception) {
            $this->database->query('ROLLBACK');
            return false;
        }
    }

    private function insert_mapping_batch(array $rows)
    {
        if (empty($rows)) {
            return true;
        }

        $placeholders = [];
        $values = [];
        foreach ($rows as $row) {
            $placeholders[] = '(%s, %s, %s, %s, %s, %d, %s, %s, %s)';
            array_push(
                $values,
                $row['source'],
                $row['externalStateId'],
                $row['externalCityId'],
                $row['externalStateTitle'],
                $row['externalCityTitle'],
                $row['tipaxCityId'],
                $row['matchType'],
                $row['candidateTipaxIds'],
                $row['updatedAt']
            );
        }

        $sql = "INSERT INTO {$this->table_name}
            (source, externalStateId, externalCityId, externalStateTitle, externalCityTitle, tipaxCityId, matchType, candidateTipaxIds, updatedAt)
            VALUES " . implode(', ', $placeholders);
        $sql = $this->database->prepare($sql, $values);

        return $this->database->query($sql) !== false;
    }

    private function build_mapping_row(array $context, array $analysis)
    {
        return [
            'source' => $context['source'],
            'externalStateId' => $context['state_id'],
            'externalCityId' => $context['city_id'],
            'externalStateTitle' => $context['state_title'],
            'externalCityTitle' => $context['city_title'],
            'tipaxCityId' => absint($analysis['tipax_city_id'] ?? 0),
            'matchType' => sanitize_key($analysis['match_type'] ?? 'unmatched'),
            'candidateTipaxIds' => implode(',', array_map('absint', $analysis['candidates'] ?? [])),
            'updatedAt' => current_time('mysql', true),
        ];
    }

    private function build_report_item(array $context, array $analysis)
    {
        return [
            'tapin_state_id' => $context['state_id'],
            'tapin_state_title' => $context['state_title'],
            'tapin_city_id' => $context['city_id'],
            'tapin_city_title' => $context['city_title'],
            'tipax_city_id' => absint($analysis['tipax_city_id'] ?? 0),
            'tipax_city_title' => (string) ($analysis['tipax_city_title'] ?? ''),
            'candidate_tipax_ids' => array_map('absint', $analysis['candidates'] ?? []),
        ];
    }

    private function get_all_tipax_cities()
    {
        static $rows = null;
        if ($rows === null) {
            $city_table = $this->database->prefix . 'tipax_cities';
            $result = $this->database->get_results(
                "SELECT cityId, cityTitle, stateId, stateTitle FROM {$city_table} ORDER BY stateTitle, cityTitle",
                ARRAY_A
            );
            $rows = is_array($result) ? $result : [];
        }

        return $rows;
    }

    private function get_tipax_city_title($tipax_city_id, $state_title)
    {
        if (function_exists('tipax_resolve_city_id_alias')) {
            $tipax_city_id = tipax_resolve_city_id_alias($tipax_city_id);
        }
        foreach (tipax_get_city_rows_for_state($state_title) as $city) {
            if (is_object($city) && (int) ($city->cityId ?? 0) === (int) $tipax_city_id) {
                return (string) ($city->cityTitle ?? '');
            }
        }

        return '';
    }

    private function context_key(array $context)
    {
        return $context['source'] . '|' . $context['state_id'] . '|' . $context['city_id'];
    }

    private function mapping_row_key(array $row)
    {
        return (string) ($row['source'] ?? '') . '|'
            . (string) ($row['externalStateId'] ?? '') . '|'
            . (string) ($row['externalCityId'] ?? '');
    }
}

add_action(Tipax_City_Mapping_Service::DAILY_SYNC_HOOK, static function () {
    (new Tipax_City_Mapping_Service())->sync_mappings();
});
add_action(Tipax_City_Mapping_Service::DAILY_SYNC_HOOK . '_single', static function () {
    (new Tipax_City_Mapping_Service())->sync_mappings();
});
add_action('wp_loaded', ['Tipax_City_Mapping_Service', 'maybe_schedule_sync']);
add_action('tipax_cities_synchronized', ['Tipax_City_Mapping_Service', 'schedule_immediate_sync'], 10, 0);
