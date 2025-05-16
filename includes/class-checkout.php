<?php
class TipaxLoadCities
{
    
    public function __construct() {
            add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
            add_action('wp_ajax_get_tipax_cities', array($this, 'get_cities'));
            add_action('wp_ajax_nopriv_get_tipax_cities', array($this, 'get_cities'));
    		add_filter('woocommerce_checkout_fields', [ $this, 'custom_modify_woocommerce_city_field' ]);
    }
	
	public function types() {

		$types = [ 'billing' ];

		if ( ! wc_ship_to_billing_address_only() ) {
			$types[] = 'shipping';
		}

		return $types;
	}
	
    public function custom_modify_woocommerce_city_field($fields) {
        $active_shipping_methods = new ActiveShippingMethods();
        $is_tipax_cities_allowed = $active_shipping_methods->check_for_only_tipax();
        if($is_tipax_cities_allowed){
    		$types = $this->types();
    		foreach($types as $type){
    			$fields[ $type ][ $type . '_state' ]['placeholder'] = __( 'Please select your state.' , 'tipax-shipping');
                $fields[ $type ][ $type . '_city'] = array(
                    'type' => 'select',
                    'class' => array(''),
                    'label' => __('City', 'woocommerce'),
                    'required' => true,
                    'options' => array('' => __( 'Select Your City.' , 'tipax-shipping')),
                );
    		}
            return $fields;
        } else {
            return $fields;
        }
    }

    // Enqueue JavaScript for AJAX functionality
    public function enqueue_scripts() {
        if (is_checkout()) {
            $active_shipping_methods = new ActiveShippingMethods();
            $is_tipax_cities_allowed = $active_shipping_methods->check_for_only_tipax();
            if($is_tipax_cities_allowed){
                wp_register_script('tsmCheckout', TSM_ASSETS_URL . 'js/cities.js', [ 'selectWoo' ], '1.0', true);
                wp_localize_script('tsmCheckout', 'tsm_settings', array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('tsm_settings'),
    			    'types'    => $this->types(),
    			    'is_cod'   => WC()->session->get( 'chosen_payment_method' ) == 'cod',
                ));
    		    wp_enqueue_script( 'tsmCheckout' );
            }
        }
    }

    // AJAX callback function to populate city options
    public function get_cities() {
        // check_ajax_referer('tsm_settings', 'security');
        // Query your database or data source to retrieve distinct city values
        $distinct_cities = $this->get_distinct_cities($_POST['state']);
        if ($distinct_cities) {
            $city_field = '<select name="billing_city" id="billing_city" class="select" required>';
            $city_field .= '<option value="">Select a city</option>';
            foreach ($distinct_cities as $city) {
                $city_field .= '<option value="' . $city->cityId . '">' . $city->cityTitle . '</option>';
            }
            $city_field .= '</select>';
            echo $city_field;
        }
    }

    private function get_distinct_cities($state) {
		global $wpdb;

		$table = $wpdb->prefix . 'tipax_cities';
		
        $state_cities = $wpdb->get_results(
            $wpdb->prepare("SELECT cityId,cityTitle FROM $table WHERE stateTitle = %s", $state)
        );
        return $state_cities;
    }
}

new TipaxLoadCities();