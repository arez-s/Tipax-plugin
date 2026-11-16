<?php
if (!defined('ABSPATH')) {
    exit;
}

function tipax_normalize_location_lookup_text($value)
{
    if (!is_scalar($value)) {
        return '';
    }

    $value = html_entity_decode((string) $value, ENT_QUOTES, 'UTF-8');
    $value = str_replace(
        ['ي', 'ى', 'ك', 'ة', 'ۀ', "\xE2\x80\x8C", "\xC2\xA0"],
        ['ی', 'ی', 'ک', 'ه', 'ه', ' ', ' '],
        $value
    );
    $value = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}]/u', '', $value);
    $value = preg_replace('/[^\p{L}\p{N}]+/u', '', $value);

    return is_string($value) ? $value : '';
}

function tipax_normalize_state_lookup_text($state_title)
{
    $key = tipax_normalize_location_lookup_text($state_title);
    // This known province spelling is an alias; city names must keep their exact letters.
    $aliases = [
        'کهکیلویهوبویراحمد' => 'کهگیلویهوبویراحمد',
        'کهکیلویه' => 'کهگیلویه',
    ];

    return $aliases[$key] ?? $key;
}

function tipax_get_city_lookup_keys($city_title)
{
    $normalized = tipax_normalize_location_lookup_text($city_title);
    if ($normalized === '') {
        return [];
    }

    $keys = [$normalized => $normalized];
    // "شهر" is optional, but it must not be stripped from "شهرک".
    if (preg_match('/^شهر(?!ک)(.+)$/u', $normalized, $matches) && !empty($matches[1])) {
        $keys[$matches[1]] = $matches[1];
    }

    return array_values($keys);
}

function tipax_location_titles_match($first, $second, $city_title = false)
{
    $first_keys = $city_title ? tipax_get_city_lookup_keys($first) : [tipax_normalize_state_lookup_text($first)];
    $second_keys = $city_title ? tipax_get_city_lookup_keys($second) : [tipax_normalize_state_lookup_text($second)];
    $first_keys = array_values(array_filter($first_keys));
    $second_keys = array_values(array_filter($second_keys));

    return !empty($first_keys) && !empty($second_keys) && !empty(array_intersect($first_keys, $second_keys));
}

function tipax_get_city_identity_key($city_title, $state_title)
{
    $city_key = tipax_normalize_location_lookup_text($city_title);
    $state_key = tipax_normalize_state_lookup_text($state_title);
    if ($city_key === '' || $state_key === '') {
        return '';
    }

    return $state_key . '|' . $city_key;
}

function tipax_get_known_city_id_replacements()
{
    $replacements = [
        1217 => 3076, // ایوان غرب / ایوان، ایلام
    ];

    return function_exists('apply_filters')
        ? apply_filters('tipax_known_city_id_replacements', $replacements)
        : $replacements;
}

function tipax_deduplicate_city_records_by_identity(array $records)
{
    $current = [];
    $identity_ids = [];
    $aliases = [];

    foreach ($records as $record) {
        if (!is_array($record)) {
            continue;
        }

        $city_id = isset($record['cityId']) && is_numeric($record['cityId']) ? (int) $record['cityId'] : 0;
        $identity = tipax_get_city_identity_key($record['cityTitle'] ?? '', $record['stateTitle'] ?? '');
        if ($city_id <= 0 || $identity === '') {
            continue;
        }

        if (!isset($identity_ids[$identity])) {
            $identity_ids[$identity] = $city_id;
            $current[$city_id] = $record;
            continue;
        }

        $existing_id = (int) $identity_ids[$identity];
        $current_id = max($existing_id, $city_id);
        $legacy_id = min($existing_id, $city_id);
        $aliases[$legacy_id] = $current_id;
        if ($current_id !== $existing_id) {
            unset($current[$existing_id]);
            $current[$current_id] = $record;
            $identity_ids[$identity] = $current_id;
        }
    }

    return [
        'records' => $current,
        'aliases' => $aliases,
    ];
}

function tipax_find_replaced_city_ids(array $existing_records, array $current_records)
{
    $current_ids = [];
    $current_by_identity = [];
    foreach ($current_records as $record) {
        if (!is_array($record)) {
            continue;
        }

        $city_id = isset($record['cityId']) && is_numeric($record['cityId']) ? (int) $record['cityId'] : 0;
        $identity = tipax_get_city_identity_key($record['cityTitle'] ?? '', $record['stateTitle'] ?? '');
        if ($city_id > 0) {
            $current_ids[$city_id] = true;
        }
        if ($city_id > 0 && $identity !== '') {
            $current_by_identity[$identity] = $city_id;
        }
    }

    $replacements = [];
    foreach ($existing_records as $record) {
        if (!is_array($record)) {
            continue;
        }

        $legacy_id = isset($record['cityId']) && is_numeric($record['cityId']) ? (int) $record['cityId'] : 0;
        if ($legacy_id <= 0 || isset($current_ids[$legacy_id])) {
            continue;
        }

        $identity = tipax_get_city_identity_key($record['cityTitle'] ?? '', $record['stateTitle'] ?? '');
        $current_id = $identity !== '' && isset($current_by_identity[$identity])
            ? (int) $current_by_identity[$identity]
            : 0;
        if ($current_id > 0 && $current_id !== $legacy_id) {
            $replacements[$legacy_id] = $current_id;
        }
    }

    return $replacements;
}

function tipax_resolve_city_id_alias($city_id, $aliases = null)
{
    $city_id = is_numeric($city_id) ? (int) $city_id : 0;
    if ($city_id <= 0) {
        return 0;
    }

    if (!is_array($aliases)) {
        $aliases = function_exists('get_option') ? get_option('tipax_city_id_aliases', []) : [];
    }
    if (!is_array($aliases)) {
        return $city_id;
    }

    $original_id = $city_id;
    $visited = [];
    while (isset($aliases[$city_id]) && is_numeric($aliases[$city_id])) {
        if (isset($visited[$city_id])) {
            return $original_id;
        }
        $visited[$city_id] = true;
        $next_id = (int) $aliases[$city_id];
        if ($next_id <= 0 || $next_id === $city_id) {
            break;
        }
        $city_id = $next_id;
    }

    return $city_id;
}

function tipax_update_city_id_aliases(array $new_aliases, array $current_city_ids = [])
{
    $aliases = function_exists('get_option') ? get_option('tipax_city_id_aliases', []) : [];
    $aliases = is_array($aliases) ? $aliases : [];

    foreach ($current_city_ids as $city_id) {
        $city_id = is_numeric($city_id) ? (int) $city_id : 0;
        if ($city_id > 0) {
            unset($aliases[$city_id]);
        }
    }

    foreach ($new_aliases as $legacy_id => $current_id) {
        $legacy_id = is_numeric($legacy_id) ? (int) $legacy_id : 0;
        $current_id = is_numeric($current_id) ? (int) $current_id : 0;
        if ($legacy_id > 0 && $current_id > 0 && $legacy_id !== $current_id) {
            $aliases[$legacy_id] = $current_id;
        }
    }

    $normalized = [];
    foreach ($aliases as $legacy_id => $current_id) {
        $legacy_id = is_numeric($legacy_id) ? (int) $legacy_id : 0;
        $resolved_id = tipax_resolve_city_id_alias($current_id, $aliases);
        if ($legacy_id > 0 && $resolved_id > 0 && $legacy_id !== $resolved_id) {
            $normalized[$legacy_id] = $resolved_id;
        }
    }
    ksort($normalized, SORT_NUMERIC);

    if (function_exists('update_option')) {
        update_option('tipax_city_id_aliases', $normalized, false);
    }

    return $normalized;
}

function tipax_get_safe_industrial_city_lookup_keys($city_title)
{
    $normalized = tipax_normalize_location_lookup_text($city_title);
    if ($normalized === '') {
        return [];
    }

    $keys = array_fill_keys(tipax_get_city_lookup_keys($normalized), true);
    $without_industrial = str_replace('صنعتی', '', $normalized);
    if ($without_industrial !== $normalized && $without_industrial !== '') {
        foreach (tipax_get_city_lookup_keys($without_industrial) as $key) {
            $keys[$key] = true;
        }
    }

    return array_keys($keys);
}

function tipax_city_titles_match_safe_industrial_alias($first, $second)
{
    $first_normalized = tipax_normalize_location_lookup_text($first);
    $second_normalized = tipax_normalize_location_lookup_text($second);
    if ($first_normalized === '' || $second_normalized === '') {
        return false;
    }

    $first_without_industrial = str_replace('صنعتی', '', $first_normalized);
    $second_without_industrial = str_replace('صنعتی', '', $second_normalized);

    // Restrict automatic mapping to township names; "industrial city X" is not necessarily city X.
    if (strpos($first_normalized, 'شهرک') !== 0 || strpos($second_normalized, 'شهرک') !== 0) {
        return false;
    }

    // The relaxed rule is allowed only when "صنعتی" explains the difference.
    if ($first_without_industrial === $first_normalized && $second_without_industrial === $second_normalized) {
        return false;
    }

    return !empty(array_intersect(
        tipax_get_safe_industrial_city_lookup_keys($first),
        tipax_get_safe_industrial_city_lookup_keys($second)
    ));
}

function tipax_get_safe_qualified_city_lookup_keys($city_title, $state_title)
{
    if (!is_scalar($city_title)) {
        return [];
    }

    $value = html_entity_decode((string) $city_title, ENT_QUOTES, 'UTF-8');
    $value = str_replace(
        ['ي', 'ى', 'ك', 'ة', 'ۀ', "\xE2\x80\x8C", "\xC2\xA0"],
        ['ی', 'ی', 'ک', 'ه', 'ه', ' ', ' '],
        $value
    );
    $keys = array_fill_keys(tipax_get_city_lookup_keys($value), true);

    if (!preg_match('/^(.*?)\s*([-–—])?\s*\((.*?)\)\s*$/u', trim($value), $matches)) {
        return array_keys($keys);
    }

    $outside = trim((string) ($matches[1] ?? ''));
    $has_parent_separator = !empty($matches[2]);
    $inside = trim((string) ($matches[3] ?? ''));
    if ($outside !== '' && !$has_parent_separator) {
        foreach (tipax_get_city_lookup_keys($outside) as $key) {
            $keys[$key] = true;
        }
    }

    // "Province-(City)" is safe; arbitrary alternate names inside parentheses are not.
    if ($inside !== '' && tipax_location_titles_match($outside, $state_title)) {
        foreach (tipax_get_city_lookup_keys($inside) as $key) {
            $keys[$key] = true;
        }
    }

    return array_keys($keys);
}

function tipax_city_titles_match_safe_qualified_alias($first, $second, $state_title)
{
    if (!is_scalar($first) || !is_scalar($second)) {
        return false;
    }

    if (strpos((string) $first, '(') === false && strpos((string) $second, '(') === false) {
        return false;
    }

    return !empty(array_intersect(
        tipax_get_safe_qualified_city_lookup_keys($first, $state_title),
        tipax_get_safe_qualified_city_lookup_keys($second, $state_title)
    ));
}
