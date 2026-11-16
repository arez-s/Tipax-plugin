<?php
if (!defined('ABSPATH')) {
    exit;
}

function tipax_get_default_packing_max_weight()
{
    // ID 521 is the largest "no packing required" segment returned by PackingPrices.
    return 100.0;
}

function tipax_set_package_packing_error($message = null)
{
    static $error = '';

    if (func_num_args() > 0) {
        $error = trim((string) $message);
    }

    return $error;
}

function tipax_get_package_packing_error($default = '')
{
    $error = tipax_set_package_packing_error();
    return $error !== '' ? $error : (string) $default;
}

function tipax_is_no_packing_id($packing_id)
{
    return in_array((int) $packing_id, [507, 504, 521], true);
}

function tipax_get_no_packing_profiles()
{
    $profiles = [
        507 => [
            'packingId' => 507,
            'packType' => 10,
            'packageContentId' => 3,
            'minWeight' => 0.1,
            'maxWeight' => 2.0,
            'minLength' => 0.0,
            'minWidth' => 0.0,
            'minHeight' => 0.0,
            'maxLength' => 45.0,
            'maxWidth' => 45.0,
            'maxHeight' => 5.0,
        ],
        504 => [
            'packingId' => 504,
            'packType' => 20,
            'packageContentId' => 9,
            'minWeight' => 0.0,
            'maxWeight' => 30.5,
            'minLength' => 0.0,
            'minWidth' => 0.0,
            'minHeight' => 0.0,
            'maxLength' => 200.0,
            'maxWidth' => 200.0,
            'maxHeight' => 200.0,
        ],
        521 => [
            'packingId' => 521,
            'packType' => 60,
            'packageContentId' => 19,
            'minWeight' => 30.5,
            'maxWeight' => 100.0,
            'minLength' => 10.0,
            'minWidth' => 10.0,
            'minHeight' => 10.0,
            'maxLength' => 120.0,
            'maxWidth' => 80.0,
            'maxHeight' => 120.0,
        ],
    ];

    // Prefer the current API catalog while retaining safe defaults when it is unavailable.
    if (function_exists('tipax_get_admin_packing_catalog')) {
        $catalog = tipax_get_admin_packing_catalog();
        $index = isset($catalog['index']) && is_array($catalog['index']) ? $catalog['index'] : [];
        $catalog_keys = [
            'packType',
            'packageContentId',
            'minWeight',
            'maxWeight',
            'minLength',
            'minWidth',
            'minHeight',
            'maxLength',
            'maxWidth',
            'maxHeight',
        ];

        foreach ($profiles as $packing_id => $profile) {
            if (empty($index[(string) $packing_id]) || !is_array($index[(string) $packing_id])) {
                continue;
            }

            $api_profile = array_intersect_key($index[(string) $packing_id], array_flip($catalog_keys));
            $profiles[$packing_id] = array_merge($profile, $api_profile, ['packingId' => $packing_id]);
        }
    }

    // Business segment boundaries take precedence over stale catalog ranges.
    $profiles[504]['minWeight'] = 0.0;
    $profiles[504]['maxWeight'] = 30.5;
    $profiles[521]['minWeight'] = 30.5;
    $profiles[521]['maxWeight'] = 100.0;

    return $profiles;
}

function tipax_get_packing_field($packing, $key, $default = null)
{
    if (is_array($packing) && array_key_exists($key, $packing)) {
        return $packing[$key];
    }

    if (is_object($packing) && isset($packing->{$key})) {
        return $packing->{$key};
    }

    return $default;
}

function tipax_is_flyer_or_envelope_packing($packing)
{
    $pack_type = (int) tipax_get_packing_field($packing, 'packType', 0);
    if (in_array($pack_type, [10, 50], true)) {
        return true;
    }

    $title = trim((string) tipax_get_packing_field($packing, 'title', ''));
    if ($title === '') {
        return false;
    }

    if (function_exists('mb_stripos')) {
        return mb_stripos($title, 'فلایر') !== false || mb_stripos($title, 'پاکت') !== false;
    }

    return strpos($title, 'فلایر') !== false || strpos($title, 'پاکت') !== false;
}

function tipax_packing_matches_height_rules($packing, $height_cm)
{
    $height_cm = is_numeric($height_cm) ? (float) $height_cm : 0.0;
    if ($height_cm <= 0) {
        return false;
    }

    $packing_id = (int) tipax_get_packing_field(
        $packing,
        'packingId',
        tipax_get_packing_field($packing, 'id', 0)
    );

    if ($packing_id === 521 && $height_cm <= 10.0) {
        return false;
    }

    if (tipax_is_flyer_or_envelope_packing($packing) && $height_cm > 5.0) {
        return false;
    }

    return true;
}

function tipax_get_selected_packing_payload_dimensions($packing)
{
    $dimensions = [
        'length' => (float) tipax_get_packing_field($packing, 'length', 0),
        'width' => (float) tipax_get_packing_field($packing, 'width', 0),
        'height' => (float) tipax_get_packing_field($packing, 'height', 0),
    ];

    $minimums = [
        'length' => (float) tipax_get_packing_field($packing, 'minLength', 0),
        'width' => (float) tipax_get_packing_field($packing, 'minWidth', 0),
        'height' => (float) tipax_get_packing_field($packing, 'minHeight', 0),
    ];

    $maximums = [
        'length' => (float) tipax_get_packing_field($packing, 'maxLength', 0),
        'width' => (float) tipax_get_packing_field($packing, 'maxWidth', 0),
        'height' => (float) tipax_get_packing_field($packing, 'maxHeight', 0),
    ];

    if (tipax_is_flyer_or_envelope_packing($packing)) {
        if ($maximums['length'] > 0 && $maximums['width'] > 0) {
            $dimensions['length'] = $maximums['length'];
            $dimensions['width'] = $maximums['width'];
        }
        $dimensions['height'] = $maximums['height'] > 0
            ? min(5.0, $maximums['height'])
            : min(5.0, max(1.0, $dimensions['height']));

        return $dimensions;
    }

    foreach (['length', 'width', 'height'] as $axis) {
        if ($dimensions[$axis] <= 0) {
            $dimensions[$axis] = $minimums[$axis] > 0 ? $minimums[$axis] : 1.0;
        }

        if ($minimums[$axis] > 0 && $dimensions[$axis] < $minimums[$axis]) {
            $dimensions[$axis] = $minimums[$axis];
        }

        if ($maximums[$axis] > 0 && $dimensions[$axis] > $maximums[$axis]) {
            $dimensions[$axis] = $maximums[$axis];
        }
    }

    return $dimensions;
}

function tipax_get_pack_type_max_package_value($pack_type)
{
    // Tipax does not expose value ranges in PackingPrices. The envelope segment
    // currently accepts parcel values up to 2,000,000 Toman (20,000,000 IRR).
    return (int) $pack_type === 10 ? 20000000.0 : 0.0;
}

function tipax_packing_matches_package_value($packing, $package_value)
{
    if (!is_numeric($package_value) || (float) $package_value <= 0) {
        return true;
    }

    if (is_array($packing)) {
        $pack_type = isset($packing['packType']) ? (int) $packing['packType'] : 0;
    } elseif (is_object($packing)) {
        $pack_type = isset($packing->packType) ? (int) $packing->packType : 0;
    } else {
        return false;
    }

    $max_value = tipax_get_pack_type_max_package_value($pack_type);
    return $max_value <= 0 || (float) $package_value <= $max_value;
}

function tipax_resolve_default_packing_for_package($pkg)
{
    if (!is_object($pkg)) {
        return [
            'success' => false,
            'message' => 'ساختار مرسوله برای تعیین سگمنت بدون بسته‌بندی معتبر نیست.',
        ];
    }

    $weight = isset($pkg->weight) && is_numeric($pkg->weight) ? (float) $pkg->weight : 0.0;
    $length = isset($pkg->length) && is_numeric($pkg->length) ? (float) $pkg->length : 0.0;
    $width = isset($pkg->width) && is_numeric($pkg->width) ? (float) $pkg->width : 0.0;
    $height = isset($pkg->height) && is_numeric($pkg->height) ? (float) $pkg->height : 0.0;
    $package_value = isset($pkg->packageValue) && is_numeric($pkg->packageValue)
        ? (float) $pkg->packageValue
        : 0.0;

    if ($weight <= 0 || $length <= 0 || $width <= 0 || $height <= 0) {
        return [
            'success' => false,
            'message' => 'وزن و ابعاد مرسوله برای تعیین سگمنت بدون بسته‌بندی باید بیشتر از صفر باشند.',
        ];
    }

    $profiles = tipax_get_no_packing_profiles();
    if ($weight < 2.0) {
        // A parcel that cannot use the envelope segment may use ID 504 up to 30.5kg.
        $candidate_ids = [507, 504];
    } elseif ($weight <= 30.5) {
        $candidate_ids = [504];
    } else {
        $candidate_ids = [521];
    }

    $has_weight_match = false;
    $has_dimension_match = false;
    foreach ($candidate_ids as $packing_id) {
        if (empty($profiles[$packing_id])) {
            continue;
        }

        $profile = $profiles[$packing_id];
        if (!tipax_packing_matches_weight_range($profile, $weight)) {
            continue;
        }
        $has_weight_match = true;

        if (!tipax_packing_matches_dimensions($profile, $length, $width, $height)) {
            continue;
        }
        if (!tipax_packing_matches_height_rules($profile, $height)) {
            continue;
        }
        $has_dimension_match = true;

        if (!tipax_packing_matches_package_value($profile, $package_value)) {
            continue;
        }

        return [
            'success' => true,
            'meta' => [
                'packingId' => (int) $profile['packingId'],
                'packType' => (int) $profile['packType'],
                'packageContentId' => (int) $profile['packageContentId'],
            ],
        ];
    }

    if (!$has_weight_match) {
        $message = 'وزن مرسوله در بازه هیچ‌یک از سگمنت‌های معتبر بدون بسته‌بندی تیپاکس قرار ندارد.';
    } elseif (!$has_dimension_match) {
        $message = 'ابعاد مرسوله با هیچ‌یک از سگمنت‌های معتبر بدون بسته‌بندی تیپاکس سازگار نیست.';
    } else {
        $message = 'ارزش مرسوله از سقف مجاز سگمنت پاکت بیشتر است و سگمنت بدون بسته‌بندی جایگزینی برای این وزن وجود ندارد.';
    }

    return [
        'success' => false,
        'message' => $message,
    ];
}

function tipax_get_default_packing_meta_for_weight($weight_kg)
{
    $weight_kg = is_numeric($weight_kg) ? (float) $weight_kg : 0.0;
    if ($weight_kg < 2.0) {
        return [
            'packingId' => 507,
            'packType' => 10,
            'packageContentId' => 3,
        ];
    }

    if ($weight_kg <= 30.5) {
        return [
            'packingId' => 504,
            'packType' => 20,
            'packageContentId' => 9,
        ];
    }

    return [
        'packingId' => 521,
        'packType' => 60,
        'packageContentId' => 19,
    ];
}

function tipax_get_default_packing_id_for_weight($weight_kg)
{
    $meta = tipax_get_default_packing_meta_for_weight($weight_kg);
    return $meta['packingId'];
}

function tipax_normalize_package_dimensions($length_cm, $width_cm, $height_cm)
{
    $dimensions = [
        'length' => is_numeric($length_cm) ? (float) $length_cm : 0.0,
        'width' => is_numeric($width_cm) ? (float) $width_cm : 0.0,
        'height' => is_numeric($height_cm) ? (float) $height_cm : 0.0,
    ];

    foreach ($dimensions as $dimension) {
        if ($dimension <= 0) {
            return null;
        }
    }

    return $dimensions;
}

function tipax_apply_package_dimensions(array $packages, array $dimensions)
{
    $normalized = tipax_normalize_package_dimensions(
        $dimensions['length'] ?? 0,
        $dimensions['width'] ?? 0,
        $dimensions['height'] ?? 0
    );
    if ($normalized === null) {
        return $packages;
    }

    foreach ($packages as $package) {
        if (!is_object($package)) {
            continue;
        }

        $package->length = $normalized['length'];
        $package->width = $normalized['width'];
        $package->height = $normalized['height'];
    }

    return $packages;
}

function tipax_get_package_weight_limit($selected_packing = null)
{
    if (is_array($selected_packing)) {
        $packing_id = isset($selected_packing['id']) ? (int) $selected_packing['id'] : 0;
        $pack_type = isset($selected_packing['packType']) ? (int) $selected_packing['packType'] : 0;
        $max_weight = isset($selected_packing['maxWeight']) && is_numeric($selected_packing['maxWeight'])
            ? (float) $selected_packing['maxWeight']
            : 0.0;

        if ($packing_id > 0 && $pack_type > 0 && $max_weight > 0) {
            return $max_weight;
        }
    }

    return tipax_get_default_packing_max_weight();
}

function tipax_get_package_content_id_for_pack_type($pack_type)
{
    $content_ids = [
        10 => 3,
        20 => 9,
        50 => 10,
        60 => 19,
        70 => 36,
    ];
    $pack_type = (int) $pack_type;

    return isset($content_ids[$pack_type]) ? $content_ids[$pack_type] : 9;
}

function tipax_apply_package_packing_meta($pkg, $packing_id, $pack_type, $package_content_id = 0)
{
    if (!is_object($pkg)) {
        return $pkg;
    }

    $pack_type = (int) $pack_type;
    $package_content_id = (int) $package_content_id;
    if ($package_content_id <= 0) {
        $package_content_id = tipax_get_package_content_id_for_pack_type($pack_type);
    }

    $pkg->packingId = (int) $packing_id;
    $pkg->packType = $pack_type;
    $pkg->packageContentId = $package_content_id;

    return $pkg;
}

function tipax_apply_default_pack_meta($pkg)
{
    if (!is_object($pkg)) {
        tipax_set_package_packing_error('ساختار مرسوله برای تعیین بسته‌بندی معتبر نیست.');
        return false;
    }

    $resolved = tipax_resolve_default_packing_for_package($pkg);
    if (empty($resolved['success']) || empty($resolved['meta'])) {
        tipax_set_package_packing_error($resolved['message'] ?? 'سگمنت معتبر بدون بسته‌بندی برای مرسوله پیدا نشد.');
        return false;
    }

    $meta = $resolved['meta'];
    return tipax_apply_package_packing_meta(
        $pkg,
        $meta['packingId'],
        $meta['packType'],
        $meta['packageContentId']
    );
}

function tipax_packing_matches_dimensions($packing, $length_cm, $width_cm, $height_cm)
{
    if (!is_array($packing) && !is_object($packing)) {
        return false;
    }

    $read_limit = static function ($key) use ($packing) {
        if (is_array($packing)) {
            return isset($packing[$key]) && is_numeric($packing[$key]) ? (float) $packing[$key] : 0.0;
        }

        return isset($packing->{$key}) && is_numeric($packing->{$key}) ? (float) $packing->{$key} : 0.0;
    };

    $parcel_dimensions = [(float) $length_cm, (float) $width_cm, (float) $height_cm];
    $packing_maximums = [
        $read_limit('maxLength'),
        $read_limit('maxWidth'),
        $read_limit('maxHeight'),
    ];
    $packing_minimums = [
        $read_limit('minLength'),
        $read_limit('minWidth'),
        $read_limit('minHeight'),
    ];

    foreach (array_merge($parcel_dimensions, $packing_maximums) as $dimension) {
        if ($dimension <= 0) {
            return false;
        }
    }

    // A parcel can be rotated inside the selected packing.
    sort($parcel_dimensions, SORT_NUMERIC);
    sort($packing_minimums, SORT_NUMERIC);
    sort($packing_maximums, SORT_NUMERIC);

    foreach ($parcel_dimensions as $index => $dimension) {
        if (
            $dimension < $packing_minimums[$index] ||
            $dimension > $packing_maximums[$index]
        ) {
            return false;
        }
    }

    return true;
}

function tipax_apply_selected_packing_to_packages(&$packages, $selected_packing)
{
    tipax_set_package_packing_error('');

    if (!is_array($selected_packing) || empty($packages)) {
        tipax_set_package_packing_error('اطلاعات بسته‌بندی انتخاب‌شده معتبر نیست.');
        return false;
    }

    $packing_id = isset($selected_packing['id']) ? (int) $selected_packing['id'] : 0;
    $pack_type = isset($selected_packing['packType']) ? (int) $selected_packing['packType'] : 0;
    $package_content_id = isset($selected_packing['packageContentId'])
        ? (int) $selected_packing['packageContentId']
        : tipax_get_package_content_id_for_pack_type($pack_type);
    $payload_dimensions = tipax_get_selected_packing_payload_dimensions($selected_packing);
    $length = $payload_dimensions['length'];
    $width = $payload_dimensions['width'];
    $height = $payload_dimensions['height'];
    $min_weight = isset($selected_packing['minWeight']) ? (float) $selected_packing['minWeight'] : 0.0;
    $max_weight = isset($selected_packing['maxWeight']) ? (float) $selected_packing['maxWeight'] : 0.0;

    if ($packing_id <= 0 || $pack_type <= 0 || $length <= 0 || $width <= 0 || $height <= 0) {
        tipax_set_package_packing_error('اطلاعات بسته‌بندی انتخاب‌شده از کاتالوگ تیپاکس کامل نیست.');
        return false;
    }

    if (!tipax_packing_matches_height_rules($selected_packing, $height)) {
        tipax_set_package_packing_error('ارتفاع مرسوله با محدودیت بسته‌بندی انتخاب‌شده سازگار نیست.');
        return false;
    }

    foreach ($packages as $pkg) {
        if (
            ($min_weight > 0 && (float) $pkg->weight < $min_weight) ||
            ($max_weight > 0 && (float) $pkg->weight > $max_weight)
        ) {
            tipax_set_package_packing_error('وزن مرسوله با بسته‌بندی انتخاب‌شده سازگار نیست.');
            return false;
        }

        if (!tipax_packing_matches_package_value($selected_packing, $pkg->packageValue ?? 0)) {
            tipax_set_package_packing_error('ارزش مرسوله از سقف مجاز سگمنت پاکت بیشتر است.');
            return false;
        }
    }

    foreach ($packages as $pkg) {
        $pkg->length = $length;
        $pkg->width = $width;
        $pkg->height = $height;
        tipax_apply_package_packing_meta($pkg, $packing_id, $pack_type, $package_content_id);
    }

    return true;
}

function tipax_packing_matches_weight_range($packing, $weight_kg)
{
    if (!is_numeric($weight_kg) || (float) $weight_kg <= 0) {
        return true;
    }

    $min_weight = 0.0;
    $max_weight = 0.0;

    if (is_array($packing)) {
        $min_weight = isset($packing['minWeight']) && is_numeric($packing['minWeight']) ? (float) $packing['minWeight'] : 0.0;
        $max_weight = isset($packing['maxWeight']) && is_numeric($packing['maxWeight']) ? (float) $packing['maxWeight'] : 0.0;
    } elseif (is_object($packing)) {
        $min_weight = isset($packing->minWeight) && is_numeric($packing->minWeight) ? (float) $packing->minWeight : 0.0;
        $max_weight = isset($packing->maxWeight) && is_numeric($packing->maxWeight) ? (float) $packing->maxWeight : 0.0;
    }

    $weight_kg = (float) $weight_kg;

    if ($min_weight > 0 && $weight_kg < $min_weight) {
        return false;
    }

    if ($max_weight > 0 && $weight_kg > $max_weight) {
        return false;
    }

    return true;
}

function tipax_split_packages_by_weight_limit(array $packages, $max_weight, $use_default_packing = false)
{
    $max_weight = is_numeric($max_weight) ? (float) $max_weight : 0.0;
    if ($max_weight <= 0) {
        return $packages;
    }

    $result = [];
    foreach ($packages as $package) {
        if (!is_object($package)) {
            continue;
        }

        $weight = isset($package->weight) ? max(0.0, (float) $package->weight) : 0.0;
        $parts = max(1, (int) ceil($weight / $max_weight));
        $value = isset($package->packageValue) ? (float) $package->packageValue : 0.0;
        $cod = isset($package->cod) ? (float) $package->cod : null;

        for ($part = 1; $part <= $parts; $part++) {
            $split = clone $package;
            $split->weight = $weight / $parts;
            $split->packageValue = $value / $parts;
            if ($cod !== null) {
                $split->cod = $cod / $parts;
            }
            if ($use_default_packing) {
                $split = tipax_apply_default_pack_meta($split);
                if ($split === false) {
                    return [];
                }
            }
            $result[] = $split;
        }
    }

    return $result;
}

function tipax_prepare_default_packing_packages(array $packages)
{
    tipax_set_package_packing_error('');

    return tipax_split_packages_by_weight_limit(
        $packages,
        tipax_get_default_packing_max_weight(),
        true
    );
}
