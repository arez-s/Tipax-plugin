<?php


// get tipax tracking code
add_action( 'woocommerce_before_thankyou', 'get_tipax_tracking_code', 2000, 1);

function get_tipax_tracking_code ($order_id)
{
    $request = new Request();
    $order = wc_get_order( $order_id );

    // Get Order Status
    $order_status = $order->get_status();

    if( !$order->has_shipping_method('tipax_shipping_method') ) { 
        return;
    }

    $accessToken = get_option('woocommerce_tipax_shipping_method_accesstoken', null);

    $originCityId = null;

    $order_data = $order->get_data(); // The Order data


    // Get Order Shipping
    $getShippingMethod = $order->get_shipping_method();
    $orderShippingGetItems = $order->get_items( 'shipping' );
    $getShippingToDisplay = $order->get_shipping_to_display();

    $serviceId = null;
    foreach (reset($orderShippingGetItems)->get_meta_data() as $item){
        $serviceId = $item->value;
    }

    $order_receiver_phone = $order_data['billing']['phone'];
    $order_receiver_plaque = null;

    foreach ($order_data["meta_data"] as $meta){
        if($meta->key == "phone"){
            $order_receiver_phone = $meta->value;
        }

        if($meta->key == "plaque"){
            $order_receiver_plaque = $meta->value;
        }
    }

    $order_shipping_first_name = $order_data['shipping']['first_name'];
    $order_shipping_last_name = $order_data['shipping']['last_name'];
    $order_billing_phone = $order_data['billing']['phone'];

    $order_shipping_postcode = $order_data['shipping']['postcode'];
    $order_shipping_postcode = postalcodeValidate($order_shipping_postcode);

    $order_shipping_address_1 = $order_data['shipping']['address_1'];

    $order_shipping_city = $order->get_shipping_city();
    $order_shipping_country = $order->get_shipping_country();
    $order_shipping_state = $order->get_shipping_state();
    if (array_key_exists($order_shipping_state,WC()->countries->get_states( $order_shipping_country ))){
        $stateTextShipping = WC()->countries->get_states( $order_shipping_country )[$order_shipping_state];
    } else {
        $stateTextShipping = $order_shipping_state;
    }
    $storeRawCountry = get_option( 'woocommerce_default_country' );
    $weight_unit = get_option('woocommerce_weight_unit');
    $dimension_unit = get_option('woocommerce_dimension_unit');

    error_log("order weightUnit: ".$weight_unit);
    error_log("order dimensionUnit: ".$dimension_unit);

    switch ($dimension_unit){
        case "m":
            $unit_ratio = 0.01;
            break;
        case "cm":
            $unit_ratio = 1;
            break;
        case "mm":
            $unit_ratio = 0.1;
            break;
        case "in":
            $unit_ratio = 2.54;
            break;
        case "yd":
            $unit_ratio = 91.44;
            break;
        default:
            $unit_ratio = 1;
            break;
    }

    // Split the country/state
    $splitSplit = explode( ":", $storeRawCountry );

    // Country and state separated:
    $storeCountry = $splitSplit[0];
    $storeState   = $splitSplit[1];
    //
    $storeCity = get_option('woocommerce_store_city');

    $storeStateReal = WC()->countries->get_states( $storeCountry )[$storeState];

    $order_shipping_city_id = get_city_code($order_shipping_city,$stateTextShipping);
    $origin_city_id = get_city_code($storeCity,$storeStateReal);


    //$woocommerce_store data
    $woocommerce_store_postcode = get_option( 'woocommerce_store_postcode' );
    $woocommerce_store_postcode = postalcodeValidate($woocommerce_store_postcode);

    $woocommerce_store_address = get_option( 'woocommerce_store_address' );
    $woocommerce_store_city_code = $origin_city_id;
    $woocommerce_currency = get_option( 'woocommerce_currency' );

    $fullAddress = $order->get_address();
    
    foreach($orderShippingGetItems as $item_id => $item ){
        $shipping_method_instance_id = $item->get_instance_id(); // The instance ID
    }

    $settings = get_option("woocommerce_tipax_shipping_method_".$shipping_method_instance_id."_settings",null);
    $method_2_origin = get_option('tipax_originid');
    if($settings != null){
        $shppingAddress = $settings["shipping_address"] ;
        $addressMethod = (int)$settings["address_method"];
        $checkout_method = $settings['checkout_method'];
        $collection_time = $settings['collection_time'];

        $lat_lon = $settings['lat_lon'];

        $lat = null;
        $lon = null;

        if($lat_lon != null){
            $lat_lon = explode(",",$lat_lon);
            $lat = $lat_lon[0];
            $lon = $lat_lon[1];
        }

        $plaque = $settings['plaque'];
        $originPhone = $settings['phone'];
        $originFullName = $settings['name'];
    }else{
        $shppingAddress = null;
        $addressMethod = 1; //1 is default from woocommerce,2 is from map , 3 is From defined addresses
        $checkout_method = 10;
        $collection_time = 1;
        $lat_lon = null;
        $lat = null;
        $lon = null;

        $plaque = null;
        $originPhone = null;
        $originFullName = null;
    }




    switch ($addressMethod){
        case 1: //default method,using woocommerce information

            //check required parameters
            if($woocommerce_store_city_code == null || $order_shipping_city_id == null || $plaque == null || $lat == null || $lon == null){
                return;
            }

            //Origin
            $data = new stdClass;
            $data->packages = [];
            foreach ( $order->get_items() as $item_id => $item ) {
                $quantity = (int)$item->get_quantity();

                for($i = 1; $i <= $quantity ; $i++){

                    $packageInputs = new stdClass;
                    $packageInputs->origin = new stdClass;
                    $packageInputs->origin->cityId = $woocommerce_store_city_code;
                    $packageInputs->origin->districtId = 0;
                    $packageInputs->origin->fullAddress = $woocommerce_store_address;
                    $packageInputs->origin->no = $plaque;
                    $packageInputs->origin->latitude = $lat;
                    $packageInputs->origin->longitude = $lon;
                    $packageInputs->origin->unit = null;
                    $packageInputs->origin->postalCode = $woocommerce_store_postcode;
                    $packageInputs->origin->beneficiary = new stdClass;
                    $packageInputs->origin->beneficiary->fullName = $originFullName; //shop name
                    $packageInputs->origin->beneficiary->mobile = $originPhone; //shop mobile
                    $packageInputs->origin->beneficiary->phone = ""; //shop phone

                    //Destination
                    $packageInputs->destination = new stdClass;
                    $packageInputs->destination->cityId = $order_shipping_city_id;//$order_shipping_city_id;
                    $packageInputs->destination->districtId = 0;
                    $packageInputs->destination->fullAddress = $order_shipping_address_1 ?? null;
                    $packageInputs->destination->unit = null;
                    $packageInputs->destination->no = $order_receiver_plaque;
                    $packageInputs->destination->postalCode = $order_shipping_postcode;

                    $packageInputs->destination->beneficiary = new stdClass;
                    $packageInputs->destination->beneficiary->fullName = $order_shipping_first_name . ' ' . $order_shipping_last_name;
                    $packageInputs->destination->beneficiary->mobile = $order_receiver_phone;
                    $packageInputs->destination->beneficiary->phone = $order_billing_phone;


                    //product
                    $product = $item->get_product();
                    $productWeight = $product->get_weight();

                    $packageInputs->weight              = $weight_unit == "kg" ? $productWeight : ($productWeight/1000);
                    $packageInputs->packageValue      = $woocommerce_currency == 'IRT' ? floatval($product->get_price()*10) : (int)$product->get_price();
                    $packageInputs->length              = $product->get_length() * $unit_ratio ?? 0;
                    $packageInputs->width               = $product->get_width() * $unit_ratio ?? 0;
                    $packageInputs->height              = $product->get_height() * $unit_ratio ?? 0;
                    $packageInputs->packType            = 20;
                    $packageInputs->packingId           = 3;
                    $packageInputs->packageContentId    = 9;
                    $packageInputs->description         = "";
                    $packageInputs->serviceId    = (int)$serviceId; //service id from last api
                    $packageInputs->enableLabelPrivacy  = true;
                    $packageInputs->paymentType           = $checkout_method; // retrieve from admin panel


                    $data->packages[] = $packageInputs;
                }
            }

            storeTipaxOrder("Orders",$order, $request, $data, $accessToken,$order_id);

            break;
        case 2: //with address_id

            //check required parameters
            if($order_shipping_city_id == null || $plaque == null || $method_2_origin == null){
                return;
            }

            $data = new stdClass;
            $data->packages = [];

            foreach ( $order->get_items() as $item_id => $item ) {
                $quantity = (int)$item->get_quantity();

                for($i = 1; $i <= $quantity ; $i++) {

                    $packageInputs = new stdClass;
                    //Destination
                    $packageInputs->destination = new stdClass;
                    $packageInputs->destination->cityId = $order_shipping_city_id;
                    $packageInputs->destination->districtId = 0;
                    $packageInputs->destination->fullAddress = $order_shipping_address_1 ?? null;
                    $packageInputs->destination->unit = null;
                    $packageInputs->destination->no = $order_receiver_plaque;
                    $packageInputs->destination->floor = null;
                    $packageInputs->destination->postalCode = $order_shipping_postcode;

                    $packageInputs->destination->beneficiary = new stdClass;
                    $packageInputs->destination->beneficiary->fullName = $order_shipping_first_name . ' ' . $order_shipping_last_name;
                    $packageInputs->destination->beneficiary->mobile = $order_receiver_phone;
                    $packageInputs->destination->beneficiary->phone = $order_billing_phone;

                    //receiver
                    $packageInputs->receiver = new stdClass;
                    $packageInputs->receiver->fullName = $order_shipping_first_name . ' ' . $order_shipping_last_name;
                    $packageInputs->receiver->mobile = $order_receiver_phone;
                    $packageInputs->receiver->phone = $order_billing_phone;

                    //Origin
                    $packageInputs->originId = $method_2_origin;

                    $product = $item->get_product();

                    
                    $productWeight = (!empty($product->get_weight()) && $product->get_weight() > 0) ? $product->get_weight() : 6;
                    $productLength = (!empty($product->get_length()) && $product->get_length() > 0) ? $product->get_length() : 45;
                    $productHeight = (!empty($product->get_height()) && $product->get_height() > 0) ? $product->get_height() : 45;
                    $productWidth  = (!empty($product->get_width()) && $product->get_width() > 0) ? $product->get_width() : 45;


                    $packageInputs->weight = $weight_unit == "kg" ? $productWeight : ($productWeight/1000);
                    $packageInputs->packageValue = $woocommerce_currency == 'IRT' ? floatval($product->get_price()*10) : (int)$product->get_price();
                    $packageInputs->length = $productLength * $unit_ratio ?? 45;
                    $packageInputs->width = $productWidth * $unit_ratio ?? 45;
                    $packageInputs->height = $productHeight * $unit_ratio ?? 45;
                    $packageInputs->packType = 20;
                    $packageInputs->packingId = 3;
                    $packageInputs->packageContentId = 9;
                    $packageInputs->description = "";
                    $packageInputs->serviceId = $serviceId; //service id from last api
                    $packageInputs->enableLabelPrivacy = true;
                    $packageInputs->paymentType = $checkout_method; // retrieve from admin panel
                    $packageInputs->pickupType = 10;
                    $packageInputs->distributionType = 10;
                    $packageInputs->parcelBookId = 0;
                    $packageInputs->isUnusual = false;

                    $data->packages[] = $packageInputs;
                }
            }

            storeTipaxOrder("Orders/WithPreDefinedOrigin",$order, $request, $data, $accessToken, $order_id);

            break;
        case 3: //with address_id

            //check required parameters
            if($order_shipping_city_id == null || $plaque == null || $shppingAddress == null){
                return;
            }

            $data = new stdClass;
            $data->packages = [];

            foreach ( $order->get_items() as $item_id => $item ) {
                $quantity = (int)$item->get_quantity();

                for($i = 1; $i <= $quantity ; $i++) {

                    $packageInputs = new stdClass;
                    //Destination
                    $packageInputs->destination = new stdClass;
                    $packageInputs->destination->cityId = $order_shipping_city_id;
                    $packageInputs->destination->districtId = 0;
                    $packageInputs->destination->fullAddress = $order_shipping_address_1 ?? null;
                    $packageInputs->destination->unit = null;
                    $packageInputs->destination->no = $order_receiver_plaque;
                    $packageInputs->destination->floor = null;
                    $packageInputs->destination->postalCode = $order_shipping_postcode;

                    $packageInputs->destination->beneficiary = new stdClass;
                    $packageInputs->destination->beneficiary->fullName = $order_shipping_first_name . ' ' . $order_shipping_last_name;
                    $packageInputs->destination->beneficiary->mobile = $order_receiver_phone;
                    $packageInputs->destination->beneficiary->phone = $order_billing_phone;

                    //receiver
                    $packageInputs->receiver = new stdClass;
                    $packageInputs->receiver->fullName = $order_shipping_first_name . ' ' . $order_shipping_last_name;
                    $packageInputs->receiver->mobile = $order_receiver_phone;
                    $packageInputs->receiver->phone = $order_billing_phone;

                    //Origin
                    $packageInputs->originId = $shppingAddress;

                    $product = $item->get_product();
                    
                    $productWeight = (!empty($product->get_weight()) && $product->get_weight() > 0) ? $product->get_weight() : 6;
                    $productLength = (!empty($product->get_length()) && $product->get_length() > 0) ? $product->get_length() : 45;
                    $productHeight = (!empty($product->get_height()) && $product->get_height() > 0) ? $product->get_height() : 45;
                    $productWidth  = (!empty($product->get_width()) && $product->get_width() > 0) ? $product->get_width() : 45;


                    $packageInputs->weight = $weight_unit == "kg" ? $productWeight : ($productWeight/1000);
                    $packageInputs->packageValue = $woocommerce_currency == 'IRT' ? floatval($product->get_price()*10) : (int)$product->get_price();
                    $packageInputs->length = $productLength * $unit_ratio ?? 45;
                    $packageInputs->width = $productWidth * $unit_ratio ?? 45;
                    $packageInputs->height = $productHeight * $unit_ratio ?? 45;
                    $packageInputs->packType = 20;
                    $packageInputs->packingId = 3;
                    $packageInputs->packageContentId = 9;
                    $packageInputs->description = "";
                    $packageInputs->serviceId = $serviceId; //service id from last api
                    $packageInputs->enableLabelPrivacy = true;
                    $packageInputs->paymentType = $checkout_method; // retrieve from admin panel
                    $packageInputs->pickupType = 10;
                    $packageInputs->distributionType = 10;
                    $packageInputs->parcelBookId = 0;
                    $packageInputs->isUnusual = false;

                    $data->packages[] = $packageInputs;
                }
            }

            storeTipaxOrder("Orders/WithPreDefinedOrigin",$order, $request, $data, $accessToken, $order_id);

            break;
    }
}

/**
 * @param $order
 * @param $apiPath
 * @param Request $request
 * @param stdClass $data
 * @param $accessToken
 * @return void
 */
function storeTipaxOrder($apiPath,$order, Request $request, stdClass $data, $accessToken,$order_id)
{
    foreach ($order->get_items() as $item) {
        $firstItem = $item;
    }

    echo '<ul>';

    $tipax_code = $firstItem->get_meta("tipax_tracking_code").$firstItem->get_meta("tipax_order_id");

    if (!$tipax_code) {
        //check cookie for stop duplication of order
        $tipaxOrderCookie = tipaxPluginCookie("tipaxOrder_".$order_id);

        if($tipaxOrderCookie){

            $tipaxOrderInfo = json_decode(base64_decode($tipaxOrderCookie),false);

            $i = 0;
            foreach ($order->get_items() as $item_id => $item) {
                $product = $item->get_product();
                $quantity = (int)$item->get_quantity();
                $tracking_codes = "";
                if ($quantity == 1) {
                    wc_add_order_item_meta($item_id, 'tipax_tracking_code', strtoupper($tipaxOrderInfo->trackingCodes[$i]));
                } else {
                    for ($j = 0; $j < $quantity; $j++) {
                        $tracking_codes .= strtoupper($tipaxOrderInfo->trackingCodes[$j]) . " ";
                    }
                    wc_add_order_item_meta($item_id, 'tipax_tracking_code', $tracking_codes);
                }

                wc_add_order_item_meta($item_id, 'tipax_order_id', $tipaxOrderInfo->orderId);

                echo '<li><strong>' . __('Tipax order information for ', 'tipax-shipping') . ' : ' . $product->get_name() . '</strong></li>';
                if ($quantity == 1) {
                    echo '<li>' . __('Tipax Tracking Code', 'tipax-shipping') . ' : ' . strtoupper($tipaxOrderInfo->trackingCodes[$i]) . '</li>';
                } else {
                    echo '<li>' . __('Tipax Tracking Code', 'tipax-shipping') . ' : ' . $tracking_codes . '</li>';
                }
                echo '<li>' . __('Tipax Order Id', 'tipax-shipping') . ' : ' . $tipaxOrderInfo->orderId . '</li>';
                echo '<br>';
                $i++;
            }

        }else{
            $response = $request->apiRequest($apiPath, $data, 'POST', $accessToken);

            if (isset($response->errors)) {
                wc_print_notice(__('There was a problem in creating tipax order', 'tipax-shipping'), 'error');
                wc_print_notice(__('Please contact us for fixing the error', 'tipax-shipping'), 'error');

                foreach ($response->errors as $error) {
                    wc_print_notice($error->errorMessage, 'error');
                }
            }

            if (isset($response->status) && $response->status == 401) {
                $accessToken = get_option('woocommerce_tipax_shipping_method_accesstoken', null);
                $response = $request->apiRequest($apiPath, $data, 'POST', $accessToken);
            }

            //if there is no cookie we create a cookie with data received
            $expiration = current_time( 'timestamp' ) + ( DAY_IN_SECONDS * 7 );
            tipaxPluginCookie("tipaxOrder_".$order_id,base64_encode(json_encode($response)),$expiration);

            if (isset($response->trackingCodes)) {
                wc_print_notice(__('Tipax order shipping created successfully', 'tipax-shipping'), 'notice');
                $i = 0;
                foreach ($order->get_items() as $item_id => $item) {
                    $product = $item->get_product();
                    $quantity = (int)$item->get_quantity();
                    $tracking_codes = "";
                    if ($quantity == 1) {
                        wc_add_order_item_meta($item_id, 'tipax_tracking_code', strtoupper($response->trackingCodes[$i]));
                    } else {
                        for ($j = 0; $j < $quantity; $j++) {
                            $tracking_codes .= strtoupper($response->trackingCodes[$j]) . " ";
                        }
                        wc_add_order_item_meta($item_id, 'tipax_tracking_code', $tracking_codes);
                    }

                    wc_add_order_item_meta($item_id, 'tipax_order_id', $response->orderId);

                    echo '<li><strong>' . __('Tipax order information for ', 'tipax-shipping') . ' : ' . $product->get_name() . '</strong></li>';
                    if ($quantity == 1) {
                        echo '<li>' . __('Tipax Tracking Code', 'tipax-shipping') . ' : ' . strtoupper($response->trackingCodes[$i]) . '</li>';
                    } else {
                        echo '<li>' . __('Tipax Tracking Code', 'tipax-shipping') . ' : ' . $tracking_codes . '</li>';
                    }
                    echo '<li>' . __('Tipax Order Id', 'tipax-shipping') . ' : ' . $response->orderId . '</li>';
                    echo '<br>';
                    $i++;
                }
            }
        }
    }
    else
    {
        wc_print_notice(__('Tipax order shipping created successfully', 'tipax-shipping'), 'notice');

        foreach ($order->get_items() as $item_id => $item) {
            $product = $item->get_product();

            echo '<li><strong>' . __('Tipax order information for ', 'tipax-shipping') . ' : ' . $product->get_name() . '</strong></li>';
            echo '<li>' . __('Tipax Tracking Code', 'tipax-shipping') . ' : ' . $item->get_meta("tipax_tracking_code") . '</li>';
            echo '<li>' . __('Tipax Order Id', 'tipax-shipping') . ' : ' . $item->get_meta("tipax_order_id") . '</li>';
            echo '<br>';
        }
    }

    echo '</ul>';
}
