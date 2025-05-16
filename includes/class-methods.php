<?php
class ActiveShippingMethods
{

	public function __construct(){

	}

	public function get_active_zones() {
		$active_zones   = array();
		if( class_exists( 'WC_Shipping_Zones' ) ) {
			$active_zones = \WC_Shipping_Zones::get_zones();
		}
		return $active_zones;
	}

	public function get_zones_active_methods(){
		$zones = $this->get_active_zones();
		$active_methods = [];
		foreach($zones as $zone){
			$methods = $zone['shipping_methods'];

			foreach($methods as $method){
				if( 'yes' == $method->enabled){
					$active_methods[] = $method;
				}
			}
		}
		return $active_methods;
	}

	public function is_tipax_alone(){
		$methods = $this->get_zones_active_methods();
		if( count($methods) != 1 )
			return false;

		if( 'tipax_shipping_method' == $methods[0]->id)
			return true;

		return false;
	}

	public function check_for_only_tipax(){


		$is_it_only_tipax = $this->is_tipax_alone();
        if($is_it_only_tipax){
            if(!isset(get_option('woocommerce_tipax_shipping_method_settings')['cities'])) {
                return true;
            } else {
		        $only_tipax_functionality_state = get_option('woocommerce_tipax_shipping_method_settings')['cities'];
                if('yes' == $only_tipax_functionality_state ){
                    return true;
                } else {
                    return false;
                }
            }
        } else {
            if(!isset(get_option('woocommerce_tipax_shipping_method_settings')['cities'])) {
                return false;
            } else {
		        $only_tipax_functionality_state = get_option('woocommerce_tipax_shipping_method_settings')['cities'];
                if('yes' == $only_tipax_functionality_state ){
                    return true;
                } else {
                    return false;
                }
            }
        }

	}

}

new ActiveShippingMethods();