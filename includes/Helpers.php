<?php

function postalcodeValidate($string){

    if (strlen($string) != 10)
    {
        return null;
    }

    if (preg_match("/^([0-9])\\1*$/",$string))
    {
        return null;
    }

    if (preg_match("/^([0-9])\\1*$/",substr($string,0, 4)))
    {
        return null;
    }

    if (preg_match("[02]",$string))
    {
        return null;
    }

    if(!preg_match("/^(\d{5}-?\d{5})$/", $string)){
        return null;
    }

    if (substr($string,4,1) == "5")
    {
        return null;
    }

    if(!preg_match("/\b(?!(\d)\1{3})[13-9]{4}[1346-9]-?[013-9]{5}\b/", $string)){
        return null;
    }

    return convertToEnglishNumber($string);
}

function get_city_code($cityName,$stateName)
{
    global $wpdb;

    if($cityName != null && $stateName != null){
        $prepared_sql = $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}tipax_cities WHERE cityTitle = '%s' AND stateTitle = '%s' ",
               [
                $cityName,
                $stateName
               ]
        );
        $result = $wpdb->get_results( $prepared_sql );
        if($result != null){
            return $result[0]->cityId;
        } else {
            $prepared_sql = $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}tipax_cities WHERE cityId = '%s' AND stateTitle = '%s' ",
                   [
                    $cityName,
                    $stateName
                   ]
            );
            $result = $wpdb->get_results( $prepared_sql );
            if($result != null){
                return $result[0]->cityId;
            } else {
                // Get the value of the transient
                $pws_states_transient = get_transient('pws_states');
                
                // Check if the transient exists and has a value
                if (false !== $pws_states_transient) {
                    foreach ($pws_states_transient as $id => $value) {
                        if ($value === $stateName) {
                            $topin_id = $id;
                            break; // Exit the loop once the ID is found
                        }
                    }
                    $topin_city_name = '';
                    $pws_cities_transient = get_transient('pws_cities_'.$topin_id);
                    if (false !== $pws_cities_transient) {
                        foreach ($pws_cities_transient as $id => $value) {
                            if ($id == $cityName) {
                                $topin_city_name = $value;
                                break; // Exit the loop once the ID is found
                            }
                        }
                    }
                    if($topin_city_name == ''){
                        $pws_states_transient = get_transient('pws_tapin_states');
                        if (false !== $pws_states_transient) {
                            foreach ($pws_states_transient as $id => $value) {
                                if ($value === $stateName) {
                                    $topin_id = $id;
                                    break; // Exit the loop once the ID is found
                                }
                            }
                            $topin_city_name = '';
                            $pws_cities_transient = get_transient('pws_tapin_cities_'.$topin_id);
                            foreach ($pws_cities_transient as $id => $value) {
                                if ($id == $cityName) {
                                    $topin_city_name = $value;
                                    break; // Exit the loop once the ID is found
                                }
                            }
                        }
                    }
                    return get_city_code($topin_city_name,$stateName);
                } else {
                    
                }
            }
        }
    }

    return null;
}


function convertToEnglishNumber($string) {
    $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $arabic = ['٩', '٨', '٧', '٦', '٥', '٤', '٣', '٢', '١','٠'];

    $num = range(0, 9);
    $convertedPersianNums = str_replace($persian, $num, $string);
    $englishNumbersOnly = str_replace($arabic, $num, $convertedPersianNums);

    return $englishNumbersOnly;
}


function tipaxPluginCookie( $key, $value = false, $expiration = false ) {
    if ( $value ) {
        // Set a cookie
        return setcookie( $key, $value, $expiration, COOKIEPATH, COOKIE_DOMAIN );
    }
    return isset( $_COOKIE[ $key ] ) ? $_COOKIE[ $key ] : false;
}