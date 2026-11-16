<?php
if (!defined('ABSPATH')) {
    exit;
}

if (class_exists('Tipax_Pricing_Service')) {
    return;
}

class Tipax_Pricing_Service
{
    private $request;
    private $pricing_api;
    private $runtime_pricing_cache = [];

    public function __construct($request = null)
    {
        $this->request = $request ?: new Request();
        $this->pricing_api = new Tipax_Pricing_Api($this->request);
    }

    public function price_with_origin_address_id($data, $accessToken, $timeout = null)
    {
        return $this->pricing_api->price_with_origin_address_id($data, $accessToken, $timeout);
    }

    private function price_checkout_payload($data, $accessToken, $timeout = null)
    {
        $payload = wp_json_encode($data);
        if (!is_string($payload) || $payload === '') {
            return $this->price_with_origin_address_id($data, $accessToken, $timeout);
        }

        $cache_key = 'tipax_price_' . substr(hash(
            'sha256',
            $payload . '|' . hash('sha256', (string) $accessToken)
        ), 0, 40);

        if (array_key_exists($cache_key, $this->runtime_pricing_cache)) {
            return $this->runtime_pricing_cache[$cache_key];
        }

        $cached = get_transient($cache_key);
        if (is_array($cached) && array_key_exists('response', $cached)) {
            $this->runtime_pricing_cache[$cache_key] = $cached['response'];
            return $cached['response'];
        }

        $response = $this->price_with_origin_address_id($data, $accessToken, $timeout);
        $this->runtime_pricing_cache[$cache_key] = $response;

        $cacheable = (is_array($response) && !empty($response))
            || (
                is_object($response)
                && !isset($response->errors)
                && !tipax_is_tipax_transport_error_response($response)
                && (!isset($response->status) || (int) $response->status < 400)
            );
        if ($cacheable) {
            set_transient($cache_key, ['response' => $response], 5 * MINUTE_IN_SECONDS);
        }

        return $response;
    }

    public function aggregate_rates($response, $forced_service_id = null)
    {
        $apiResults = is_array($response) ? $response : [$response];
        $aggregatedRates = [];

        foreach ($apiResults as $item) {
            if (!is_object($item)) {
                continue;
            }

            $rate_objects = [];
            if (isset($item->finalPrice)) {
                $rate_objects[] = $item;
            } else {
                foreach (get_object_vars($item) as $candidate) {
                    if (is_object($candidate) && isset($candidate->finalPrice)) {
                        $rate_objects[] = $candidate;
                    }
                }
            }

            foreach ($rate_objects as $rateObj) {
                if (!isset($rateObj->finalPrice)) {
                    continue;
                }

                $sid = $forced_service_id !== null
                    ? (int) $forced_service_id
                    : (isset($rateObj->serviceId) ? (int) $rateObj->serviceId : 0);
                if ($sid <= 0) {
                    continue;
                }
                if (!isset($aggregatedRates[$sid])) {
                    $aggregatedRates[$sid] = [
                        'serviceTitle' => isset($rateObj->serviceTitle) ? $rateObj->serviceTitle : '',
                        'finalPrice' => 0,
                        'serviceId' => $sid,
                    ];
                }

                $aggregatedRates[$sid]['finalPrice'] += (float) $rateObj->finalPrice;
            }
        }

        return $aggregatedRates;
    }

    private function clone_pricing_data_with_service_id($data, $service_id)
    {
        $copy = new stdClass;
        $copy->packageInputs = [];

        foreach ((array) ($data->packageInputs ?? []) as $package) {
            if (!is_object($package)) {
                continue;
            }

            $package_copy = clone $package;
            if (isset($package->origin) && is_object($package->origin)) {
                $package_copy->origin = clone $package->origin;
            }
            if (isset($package->destination) && is_object($package->destination)) {
                $package_copy->destination = clone $package->destination;
            }
            $package_copy->serviceId = (int) $service_id;
            $copy->packageInputs[] = $package_copy;
        }

        return $copy;
    }

    public function normalize_store_price($finalPrice, $woocommerce_currency)
    {
        $cost = round((float) $finalPrice);
        $currentPersianCurrency = 'ریال';

        if (strtoupper((string) $woocommerce_currency) === 'IRT') {
            $cost = ceil($cost / 5) * 5;
            $cost /= 10;
            $cost = (int) $cost;
            $currentPersianCurrency = 'تومان';
        }

        return [
            'cost' => $cost,
            'currency_label' => $currentPersianCurrency,
        ];
    }
    public function calculate_shipping_rates($package = array(), $context = [])
    {
        $request = isset($context['request']) ? $context['request'] : null;
        $addresses = isset($context['addresses']) ? $context['addresses'] : null;
        $accessToken = isset($context['accessToken']) ? $context['accessToken'] : null;
        $woocommerce_currency = isset($context['woocommerce_currency']) ? $context['woocommerce_currency'] : get_woocommerce_currency();
        $method_id = isset($context['method_id']) ? $context['method_id'] : 'tipax_shipping_method';
        $rates = [];

        if (!$request || !$addresses) {
            return false;
        }

    $cost = 0;

    // --- Store location & destination ---
    $storeRawCountry = get_option('woocommerce_default_country'); // e.g. IR:THR
    $splitSplit = explode(':', $storeRawCountry);
    $storeCountry = $splitSplit[0] ?? '';
    $storeState   = $splitSplit[1] ?? '';
    $storeCity    = get_option('woocommerce_store_city');

    $packageCity    = $package['destination']['city']    ?? '';
    $packageCountry = $package['destination']['country'] ?? '';
    $packageState   = $package['destination']['state']   ?? '';
    $destinationAddress = trim((string) (
        $package['destination']['address_1']
        ?? $package['destination']['address']
        ?? ''
    ));

    // Only IR supported
    if (strtoupper($storeCountry) !== 'IR') {
        return false;
    }

    $originStateReal   = WC()->countries->get_states($storeCountry)[$storeState] ?? null;
    $packageStateReal  = WC()->countries->get_states($packageCountry)[$packageState] ?? null;

    // Resolve Tipax destination city code
    $destinationCityId = get_city_code($packageCity, $packageStateReal);
    if ($destinationCityId === null) {
        tipax_log("packageCity: {$packageCity} storeCity: {$storeCity} destinationCityId: " . var_export($destinationCityId, true));
        return false;
    }

    if (!tipax_is_city_shipping_enabled($destinationCityId)) {
        tipax_log('Tipax pricing skipped for disabled destination cityId: ' . (int) $destinationCityId);
        return false;
    }

    // ---- Address method (address-based only, as requested) ----
    $addressMethod   = 3;
    $originAddressId = get_option('tipax_shipping_address');
    if ($addressMethod == 3 && empty($originAddressId)) {
        tipax_log('originAddressId: ' . (int)$originAddressId);
        return false;
    }

    // ---- Settings / options ----
    $plugin_settings = get_option('woocommerce_tipax_shipping_method_settings', []);
    $settlement_config = tipax_get_settlement_config($plugin_settings);
    $checkout_method = (int) $settlement_config['payment_type'];
    $pickup_type = tipax_normalize_pickup_type($plugin_settings['pickup_type'] ?? 10);
    $selected_packing = tipax_get_selected_packing($plugin_settings);
    $has_selected_packing = is_array($selected_packing)
        && !empty($selected_packing['id'])
        && !tipax_is_no_packing_id($selected_packing['id']);
    $averages = tipax_get_average_package_settings($plugin_settings);
    $avgWeight = $averages['weight'];
    $avgLength = $averages['length'];
    $avgWidth = $averages['width'];
    $avgHeight = $averages['height'];
    $default_package_dimensions = !$has_selected_packing
        ? tipax_normalize_package_dimensions($avgLength, $avgWidth, $avgHeight)
        : null;
    $calculation_dimensions = $has_selected_packing
        ? tipax_normalize_package_dimensions(
            $selected_packing['length'] ?? 0,
            $selected_packing['width'] ?? 0,
            $selected_packing['height'] ?? 0
        )
        : $default_package_dimensions;

    $weight_unit     = get_option('woocommerce_weight_unit');
    $dimension_unit  = get_option('woocommerce_dimension_unit');

    // Convert Woo dimension -> cm  (FIX: correct ratios)
    $unit_ratio = tipax_dim_ratio_to_cm($dimension_unit);

    // Weight prefers product data; default-mode dimensions prefer merchant settings.
    // ---- Gather cart totals & items ----
    $cart_subtotal_store = WC()->cart ? (float)WC()->cart->get_subtotal() : 0.0;

    $total_cart_weight_kg  = 0.0;
    $total_cart_volume_cm3 = 0.0;
    $total_item_qty        = 0;
    $single_item_snapshot  = null; // store single item dims/weight if qty==1 only

    if (!empty($package['contents'])) {
        foreach ($package['contents'] as $ci) {
            $qty = (int)($ci['quantity'] ?? 0);
            if ($qty <= 0) continue;

            $p = !empty($ci['variation_id'])
                ? wc_get_product($ci['variation_id'])
                : wc_get_product($ci['product_id'] ?? 0);
            if (!$p) continue;

            $rawW = is_numeric($p->get_weight()) ? (float)$p->get_weight() : null;
            $rawL = is_numeric($p->get_length()) ? (float)$p->get_length() : null;
            $rawH = is_numeric($p->get_height()) ? (float)$p->get_height() : null;
            $rawD = is_numeric($p->get_width())  ? (float)$p->get_width()  : null;

            // Product values use WooCommerce units; configured defaults are always kg/cm.
            $item_weight_kg = $rawW !== null && $rawW > 0
                ? tipax_to_kg($rawW, $weight_unit)
                : (is_numeric($avgWeight) && (float) $avgWeight > 0 ? (float) $avgWeight : 6.0);

            if ($calculation_dimensions !== null) {
                $L_cm = $calculation_dimensions['length'];
                $W_cm = $calculation_dimensions['width'];
                $H_cm = $calculation_dimensions['height'];
            } else {
                $L_cm = is_numeric($avgLength) && (float) $avgLength > 0
                    ? (float) $avgLength
                    : ($rawL !== null && $rawL > 0 ? $rawL * $unit_ratio : 45.0);
                $W_cm = is_numeric($avgWidth) && (float) $avgWidth > 0
                    ? (float) $avgWidth
                    : ($rawD !== null && $rawD > 0 ? $rawD * $unit_ratio : 45.0);
                $H_cm = is_numeric($avgHeight) && (float) $avgHeight > 0
                    ? (float) $avgHeight
                    : ($rawH !== null && $rawH > 0 ? $rawH * $unit_ratio : 45.0);
            }

            // Basic sanity check: block absurd values
            if ($L_cm <= 0 || $W_cm <= 0 || $H_cm <= 0 || $item_weight_kg <= 0) {
                tipax_log("Tipax shipping: some dimensions/weight are zero or invalid.");
                return false;
            }

            $total_cart_weight_kg  += $item_weight_kg * $qty;
            $total_cart_volume_cm3 += ($L_cm * $W_cm * $H_cm) * $qty;
            $total_item_qty        += $qty;

            if ($total_item_qty === $qty && $qty === 1) {
                // This is the only item in the cart (so far). Keep snapshot for single-item precision.
                $single_item_snapshot = [
                    'weight_kg' => $item_weight_kg,
                    'L_cm'      => $L_cm,
                    'W_cm'      => $W_cm,
                    'H_cm'      => $H_cm,
                    // price (for packageValue)
                    'price'     => (float)$p->get_price(),
                ];
            }
        }
    }

    // --- Light shipment threshold (for packType/packing choice) ---
    $threshold_store = 4000000.0; // in Toman by your original logic
    if (strtoupper($woocommerce_currency) === 'IRR') {
        $threshold_store *= 10; // IRR
    }
    $is_light_shipment = (
        $total_cart_weight_kg > 0 &&
        $total_cart_weight_kg < 2.0 &&
        $cart_subtotal_store < $threshold_store
    );
    tipax_log('total_cart_weight_kg=' . $total_cart_weight_kg . ' | is_light_shipment=' . (int)$is_light_shipment);
    tipax_log("weightUnit: {$weight_unit} | dimensionUnit: {$dimension_unit}");

    // ---- Load & validate origin address once (addressMethod = 3) ----
    $originCityETipaxId = null;
    if ($addressMethod == 3) {
        if (!$accessToken) return false;

        // Try cached transient first
        $addresses_objects = $addresses->load_default_address([
            'use_control_transient' => true,
            'set_control_transient' => true,
            'require_shipping_settings_page' => true,
            'filter_by_user' => false,
            'update_presence_option' => false,
            'missing_origin_uses_current' => true,
        ]);
        $selected = is_object($addresses_objects) ? $addresses_objects : null;
        $cached_origin_id = $selected && isset($selected->addressesId)
            ? (string) $selected->addressesId
            : '';

        // The cached selected address is sufficient for checkout. Refresh the full list
        // only when that cache is missing or belongs to another origin.
        if (!$selected || ($cached_origin_id !== '' && $cached_origin_id !== (string) $originAddressId)) {
            $responseAddr = $addresses->fetch_addresses($accessToken);
            if (!isset($responseAddr[0])) {
                return false;
            }

            $allAddresses = [];
            foreach ($responseAddr as $addr) {
                if (!isset($addr->id, $addr->addressesId)) {
                    continue;
                }
                $allAddresses[$addr->id] = [
                    'originId' => $addr->addressesId,
                    'fullAddress' => $addr,
                ];
                if ((string) $addr->addressesId === (string) $originAddressId) {
                    $selected = $addr;
                }
            }

            if (!$selected) {
                $fallback = isset($addresses_objects->id, $allAddresses[$addresses_objects->id])
                    ? $allAddresses[$addresses_objects->id]
                    : reset($allAddresses);
                if (!is_array($fallback) || empty($fallback['originId']) || empty($fallback['fullAddress'])) {
                    return false;
                }
                $originAddressId = $fallback['originId'];
                update_option('tipax_shipping_address', $originAddressId);
                $selected = $fallback['fullAddress'];
                tipax_log('Your new AddressId is :' . $originAddressId);
            }

            set_transient('tipax_addresses', $selected, 12 * HOUR_IN_SECONDS);
        }

        // For serviceId decision
        if (isset($selected->address->cityETipaxId)) {
            $originCityETipaxId = (string) $selected->address->cityETipaxId;
        } elseif (isset($selected->cityETipaxId)) {
            $originCityETipaxId = (string) $selected->cityETipaxId;
        } elseif (isset($selected->address->cityId)) {
            $originCityETipaxId = (string) $selected->address->cityId;
        } elseif (isset($selected->cityId)) {
            $originCityETipaxId = (string) $selected->cityId;
        }
    }

    // ---- Build packageInputs (single precise vs aggregated minimal) ----
    $data = new stdClass();
    $data->packageInputs = [];

    // Max constraints
    $MAX_DIM_CM      = 200.0;
    $PER_ITEM_MAX_KG = tipax_get_package_weight_limit($selected_packing);
    $CUBE_CM3        = $MAX_DIM_CM * $MAX_DIM_CM * $MAX_DIM_CM;

    // Helper to create a packageInput object for API
    $make_pkg = function ($weight_kg, $side_cm, $package_value_store) use ($addressMethod, $originAddressId, $destinationCityId, $pickup_type, $woocommerce_currency, $checkout_method) {
        $pkg = new stdClass();
        if ($addressMethod == 3) {
            $pkg->origin = new stdClass();
            $pkg->origin->Id = $originAddressId;
        }
        $pkg->destination = new stdClass();
        $pkg->destination->cityId = $destinationCityId;

        // Units for API
        $pkg->weight = (float)$weight_kg;
        $pkg->length = (float)$side_cm;
        $pkg->width  = (float)$side_cm;
        $pkg->height = (float)$side_cm;

        // packageValue: respect currency (IRT ×10 -> IRR)
        $pkg->packageValue = tipax_store_value_to_api_value($package_value_store, $woocommerce_currency);

        $pkg->distributionType = 10;
        $pkg->pickupType       = (int)$pickup_type;
        $pkg->paymentType      = (int) $checkout_method;

        return $pkg;
    };

    // Branch A: single item (qty = 1) → use its exact dims/weight; still split if exceeds limits
    if ($total_item_qty === 1 && $single_item_snapshot) {
        $w_kg = $single_item_snapshot['weight_kg'];
        $L    = $single_item_snapshot['L_cm'];
        $W    = $single_item_snapshot['W_cm'];
        $H    = $single_item_snapshot['H_cm'];

        // A conservative cube side approximation for API (use max dimension)
        $max_side = max($L, $W, $H);
        $vol_cm3  = $L * $W * $H;

        // Determine number of packages needed by constraints
        $n_by_kg = (int)ceil($w_kg / $PER_ITEM_MAX_KG);
        $n_by_dm = (int)ceil(($max_side > 0 ? ($max_side / $MAX_DIM_CM) : 0.0));
        $n_by_vol= (int)ceil($vol_cm3 / $CUBE_CM3);
        $num_pkgs = max(1, max($n_by_kg, max($n_by_dm, $n_by_vol)));
        $weight_only_split = $num_pkgs > 1 && $n_by_kg === $num_pkgs && $n_by_dm <= 1 && $n_by_vol <= 1;

        $pi_weight = $w_kg / $num_pkgs;
        $pi_value  = $single_item_snapshot['price'] / $num_pkgs;

        $remaining_vol = $vol_cm3;

        for ($i = 1; $i <= $num_pkgs; $i++) {
            if ($weight_only_split) {
                $side = $max_side;
                $pkg_weight = $pi_weight;
                $pkg_value = $pi_value;
            } elseif ($i < $num_pkgs) {
                $side = $MAX_DIM_CM;
                $pkg_weight = $pi_weight;
                $pkg_value  = $pi_value;
                $remaining_vol -= $CUBE_CM3;
                if ($remaining_vol < 0) $remaining_vol = 0;
            } else {
                // Last package: volume-based side
                $side = ($remaining_vol > 0) ? pow($remaining_vol, 1/3) : $MAX_DIM_CM;
                if ($side > $MAX_DIM_CM) $side = $MAX_DIM_CM;
                $pkg_weight = $pi_weight;
                $pkg_value  = $pi_value;
            }
            $pkg = $make_pkg($pkg_weight, ceil($side), $pkg_value);
            $data->packageInputs[] = $pkg;
        }
    }
    // Branch B: multiple items or qty > 1 → aggregate to minimal packages
    else {
        if ($total_cart_weight_kg <= 0 || $total_cart_volume_cm3 <= 0) {
            tipax_log("Tipax shipping: invalid cart totals.");
            return false;
        }

        // Determine minimal package count based on constraints
        $number_by_kg  = (int)ceil($total_cart_weight_kg / $PER_ITEM_MAX_KG);
        $number_by_vol = (int)ceil($total_cart_volume_cm3 / $CUBE_CM3);
        $package_count = max(1, max($number_by_kg, $number_by_vol));
        $weight_only_split = $package_count > 1 && $number_by_kg === $package_count && $number_by_vol <= 1;

        $pi_weight = $total_cart_weight_kg  / $package_count;
        $pi_value  = $cart_subtotal_store   / $package_count;

        $remaining_vol = $total_cart_volume_cm3;
        $weight_split_side = pow($total_cart_volume_cm3 / $package_count, 1 / 3);

        for ($i = 1; $i <= $package_count; $i++) {
            if ($weight_only_split) {
                $side = $weight_split_side;
                $pkg_weight = $pi_weight;
                $pkg_value = $pi_value;
            } elseif ($i < $package_count) {
                $side = $MAX_DIM_CM; // full cube
                $pkg_weight = $pi_weight;
                $pkg_value  = $pi_value;
                $remaining_vol -= $CUBE_CM3;
                if ($remaining_vol < 0) $remaining_vol = 0;
            } else {
                // last package gets the remaining volume
                $side = ($remaining_vol > 0) ? pow($remaining_vol, 1/3) : $MAX_DIM_CM;
                if ($side > $MAX_DIM_CM) $side = $MAX_DIM_CM;
                $pkg_weight = $pi_weight;
                $pkg_value  = $pi_value;
            }

            // Guard against limits
            if ($pkg_weight > $PER_ITEM_MAX_KG) {
                // extra split if weight rounding created an overflow
                $overflow_parts = (int)ceil($pkg_weight / $PER_ITEM_MAX_KG);
                $split_weight   = $pkg_weight / $overflow_parts;
                for ($k = 1; $k <= $overflow_parts; $k++) {
                    $pkg = $make_pkg($split_weight, ceil($side), $pkg_value / $overflow_parts);
                    $data->packageInputs[] = $pkg;
                }
            } else {
                $pkg = $make_pkg($pkg_weight, ceil($side), $pkg_value);
                $data->packageInputs[] = $pkg;
            }
        }
    }

    if ($has_selected_packing && !tipax_apply_selected_packing_to_packages($data->packageInputs, $selected_packing)) {
        tipax_log('Tipax pricing skipped: ' . tipax_get_package_packing_error('Selected packing is incompatible with the parcel.'));
        return false;
    }

    if (!$has_selected_packing) {
        if ($default_package_dimensions !== null) {
            $data->packageInputs = tipax_apply_package_dimensions($data->packageInputs, $default_package_dimensions);
        }
        $data->packageInputs = tipax_prepare_default_packing_packages($data->packageInputs);
        if (empty($data->packageInputs)) {
            tipax_log('Tipax pricing skipped: ' . tipax_get_package_packing_error('No compatible no-packing segment was found.'));
            return false;
        }
    }

    // ---- ServiceId (same logic as before): 7 for intra-city, 5 otherwise ----
    // If we know origin city eTipax id and it equals destination, mark serviceId=7 else 5
    $serviceIdForAll = 5;
    if ($originCityETipaxId !== null && (string)$originCityETipaxId === (string)$destinationCityId) {
        $serviceIdForAll = 7;
    }
    foreach ($data->packageInputs as $pkg) {
        $pkg->serviceId = $serviceIdForAll;
    }

    $same_day_eligibility = tipax_get_same_day_eligibility(
        $data->packageInputs,
        $originCityETipaxId,
        $destinationCityId,
        $plugin_settings,
        [
            'has_destination_address' => $destinationAddress !== '',
        ]
    );
    if (tipax_is_same_day_enabled($plugin_settings) && empty($same_day_eligibility['eligible'])) {
        tipax_log('Tipax Same Day skipped: ' . ($same_day_eligibility['message'] ?? 'Eligibility conditions failed.'));
    }

    $request_timeout = tipax_get_tipax_request_timeout($plugin_settings);

    // The regular in-city/inter-city rate and Same Day are priced independently.
    $response = $this->price_checkout_payload($data, $accessToken, $request_timeout);
    $same_day_response = null;
    if (!empty($same_day_eligibility['eligible'])) {
        $same_day_data = $this->clone_pricing_data_with_service_id(
            $data,
            tipax_get_same_day_service_id()
        );
        $same_day_response = $this->price_checkout_payload(
            $same_day_data,
            $accessToken,
            $request_timeout
        );
    }

    if (tipax_is_tipax_transport_error_response($response)) {
        tipax_log('Tipax pricing request failed after ' . $request_timeout . ' seconds: ' . tipax_extract_tipax_response_message($response, 'Transport error.'));

        $cart_subtotal = WC()->cart ? (float) WC()->cart->get_subtotal() : 0.0;
        $offline_settlement = tipax_calculate_rate_settlement(
            0,
            $serviceIdForAll,
            $settlement_config,
            $cart_subtotal
        );

        $offline_label = $settlement_config['payer'] === 'receiver'
            ? 'تیپاکس (پس‌کرایه)'
            : 'تیپاکس (ثبت آفلاین)';

        return [[
            'id'        => $method_id . '_offline',
            'label'     => $offline_label,
            'meta_data' => [
                'serviceId' => $serviceIdForAll,
                'tipax_offline_pricing' => 'yes',
                '_tipax_payment_type' => (int) $settlement_config['payment_type'],
                '_tipax_cod_enabled' => $settlement_config['cod_enabled'] ? 'yes' : 'no',
                '_tipax_settlement_payer' => $settlement_config['payer'],
                '_tipax_sender_payment_method' => $settlement_config['sender_method'],
                '_tipax_api_shipping_amount' => 0,
                '_tipax_receiver_shipping_amount' => $offline_settlement['receiver_shipping_amount'],
            ],
            'cost'      => $offline_settlement['rate_cost'],
        ]];
    }

    if (!$response || isset($response->errors)) {
        return false;
    }

    $aggregatedRates = $this->aggregate_rates($response);
    if ($same_day_response !== null) {
        if (tipax_is_tipax_transport_error_response($same_day_response)) {
            tipax_log('Tipax Same Day pricing transport error: ' . tipax_extract_tipax_response_message($same_day_response, 'Transport error.'));
        } elseif (!(is_object($same_day_response) && isset($same_day_response->errors))) {
            $same_day_rates = $this->aggregate_rates(
                $same_day_response,
                tipax_get_same_day_service_id()
            );
            foreach ($same_day_rates as $same_day_service_id => $same_day_rate) {
                $aggregatedRates[$same_day_service_id] = $same_day_rate;
            }
        }
    }
    $cart_subtotal = WC()->cart ? (float) WC()->cart->get_subtotal() : 0.0;

    foreach ($aggregatedRates as $rateData) {
        $normalized_price = $this->normalize_store_price($rateData['finalPrice'], $woocommerce_currency);
        $service_id = (int) $rateData['serviceId'];
        $settlement = tipax_calculate_rate_settlement(
            $normalized_price['cost'],
            $service_id,
            $settlement_config,
            $cart_subtotal
        );
        $currency_label = $normalized_price['currency_label'];
        $format_amount = static function ($amount) use ($currency_label) {
            return number_format((float) $amount) . ' ' . $currency_label;
        };

        if ($settlement_config['payer'] === 'receiver') {
            $service_desc = 'پس‌کرایه';
            if ($settlement_config['show_postpay_amount']) {
                $service_desc .= '، قابل پرداخت به تیپاکس ' . $format_amount($settlement['receiver_shipping_amount']);
            }
        } else {
            $sender_method_labels = [
                'credit' => 'اعتباری',
                'wallet' => 'کیف پول',
                'cash' => 'نقدی',
            ];
            $sender_method_label = $sender_method_labels[$settlement_config['sender_method']] ?? 'کیف پول';
            if ($settlement['rate_cost'] <= 0) {
                $service_desc = 'پرداخت ' . $sender_method_label . ' توسط فرستنده، ارسال رایگان';
            } elseif ($settlement_config['receiver_charge_mode'] === 'fixed') {
                $route_label = $settlement['is_inner_city'] ? 'درون‌شهری' : 'بین‌شهری';
                $service_desc = $route_label . '، مبلغ ثابت ' . $format_amount($settlement['rate_cost']);
            } else {
                $service_desc = 'پرداخت ' . $sender_method_label . ' توسط فرستنده، مبلغ '
                    . $format_amount($settlement['rate_cost']);
            }
        }

        $service_title = $service_id === tipax_get_same_day_service_id()
            ? 'همان روز'
            : trim((string) $rateData['serviceTitle']);
        $label = 'تیپاکس: (' . $service_desc . ')' . ($service_title !== '' ? ' ' . $service_title : '');
        $rates[] = [
            'id' => $method_id . $service_id,
            'label' => $label,
            'meta_data' => [
                'serviceId' => $service_id,
                '_tipax_payment_type' => (int) $settlement_config['payment_type'],
                '_tipax_cod_enabled' => $settlement_config['cod_enabled'] ? 'yes' : 'no',
                '_tipax_settlement_payer' => $settlement_config['payer'],
                '_tipax_sender_payment_method' => $settlement_config['sender_method'],
                '_tipax_api_shipping_amount' => $settlement['api_amount'],
                '_tipax_receiver_shipping_amount' => $settlement['receiver_shipping_amount'],
            ],
            'cost' => $settlement['rate_cost'],
        ];
    }


        return $rates;
    }

}



if (!class_exists('Tipax_Pricing_Module')) {
    class_alias('Tipax_Pricing_Service', 'Tipax_Pricing_Module');
}
