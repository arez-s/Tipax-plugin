<?php
if (!defined('ABSPATH')) {
    exit;
}

function tipax_get_same_day_service_id()
{
    return 12;
}

function tipax_get_tehran_city_id()
{
    return 1262;
}

function tipax_is_same_day_enabled($settings = null)
{
    if (!is_array($settings)) {
        $settings = get_option('woocommerce_tipax_shipping_method_settings', []);
    }

    return ($settings['same_day_enabled'] ?? 'no') === 'yes';
}

function tipax_get_iran_datetime($timestamp = null)
{
    $timestamp = $timestamp === null ? time() : (int) $timestamp;

    try {
        return (new DateTimeImmutable('@' . $timestamp))->setTimezone(new DateTimeZone('Asia/Tehran'));
    } catch (Exception $exception) {
        return null;
    }
}

function tipax_is_same_day_cutoff_open($timestamp = null)
{
    $iran_time = tipax_get_iran_datetime($timestamp);
    if (!$iran_time) {
        return false;
    }

    return (int) $iran_time->format('Hi') < 1300;
}

function tipax_same_day_dimensions_fit($length, $width, $height)
{
    $dimensions = [(float) $length, (float) $width, (float) $height];
    foreach ($dimensions as $dimension) {
        if ($dimension <= 0) {
            return false;
        }
    }

    // Rotation is allowed, but the parcel must fit inside a 37 x 37 x 15 locker space.
    sort($dimensions, SORT_NUMERIC);
    $limits = [15.0, 37.0, 37.0];

    foreach ($dimensions as $index => $dimension) {
        if ($dimension > $limits[$index]) {
            return false;
        }
    }

    return true;
}

function tipax_get_order_created_timestamp($order)
{
    if (!$order || !is_callable([$order, 'get_date_created'])) {
        return null;
    }

    $created = $order->get_date_created();
    return $created && is_callable([$created, 'getTimestamp']) ? (int) $created->getTimestamp() : null;
}

function tipax_get_same_day_eligibility(
    array $packages,
    $origin_city_id,
    $destination_city_id,
    $settings = null,
    array $args = []
) {
    $args = array_merge([
        'check_enabled' => true,
        'check_cutoff' => true,
        'timestamp' => null,
        'has_destination_address' => null,
    ], $args);

    $result = static function ($eligible, $code, $message) {
        return [
            'eligible' => (bool) $eligible,
            'code' => (string) $code,
            'message' => (string) $message,
        ];
    };

    if ($args['check_enabled'] && !tipax_is_same_day_enabled($settings)) {
        return $result(false, 'disabled', 'سرویس همان روز در تنظیمات غیرفعال است.');
    }

    if (
        $args['check_enabled']
        && function_exists('tipax_get_neshan_static_map_api_key')
        && tipax_get_neshan_static_map_api_key() === ''
    ) {
        return $result(false, 'map_not_configured', 'کلید امن نقشه نشان روی سرور تنظیم نشده است.');
    }

    $tehran_city_id = tipax_get_tehran_city_id();
    if ((int) $origin_city_id !== $tehran_city_id || (int) $destination_city_id !== $tehran_city_id) {
        return $result(false, 'city', 'سرویس همان روز فقط برای مبدا و مقصد تهران فعال است.');
    }

    if ($args['check_cutoff'] && !tipax_is_same_day_cutoff_open($args['timestamp'])) {
        return $result(false, 'cutoff', 'مهلت ثبت سرویس همان روز در ساعت ۱۳:۰۰ به وقت ایران پایان یافته است.');
    }

    if (empty($packages)) {
        return $result(false, 'packages', 'بسته‌ای برای سرویس همان روز ساخته نشده است.');
    }

    $total_weight = 0.0;
    $has_destination_address = $args['has_destination_address'];
    foreach ($packages as $package) {
        if (!is_object($package)) {
            return $result(false, 'packages', 'ساختار بسته برای سرویس همان روز معتبر نیست.');
        }

        $weight = isset($package->weight) ? (float) $package->weight : 0.0;
        if ($weight <= 0 || $weight > 12.0) {
            return $result(false, 'weight', 'وزن مرسوله سرویس همان روز باید حداکثر ۱۲ کیلوگرم باشد.');
        }
        $total_weight += $weight;

        if (!tipax_same_day_dimensions_fit(
            $package->length ?? 0,
            $package->width ?? 0,
            $package->height ?? 0
        )) {
            return $result(false, 'dimensions', 'ابعاد مرسوله سرویس همان روز باید حداکثر ۳۷ × ۳۷ × ۱۵ سانتی‌متر باشد.');
        }

        $pack_type = isset($package->packType) ? (int) $package->packType : 0;
        if (!in_array($pack_type, [10, 20], true)) {
            return $result(false, 'packing', 'سرویس همان روز فقط برای پاکت یا بسته قابل استفاده است.');
        }

        if ($has_destination_address === null && isset($package->destination) && is_object($package->destination)) {
            $has_destination_address = trim((string) ($package->destination->fullAddress ?? '')) !== '';
        }
    }

    if ($total_weight > 12.0) {
        return $result(false, 'weight', 'مجموع وزن مرسوله سرویس همان روز باید حداکثر ۱۲ کیلوگرم باشد.');
    }

    if ($has_destination_address === false) {
        return $result(false, 'address', 'برای سرویس همان روز باید آدرس کامل گیرنده وارد شود.');
    }

    return $result(true, 'eligible', 'شرایط سرویس همان روز برقرار است.');
}
