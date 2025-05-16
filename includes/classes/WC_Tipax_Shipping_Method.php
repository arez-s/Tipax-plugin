<?php

/**
 * Shpping Class
 * This class is for adding tipax shipping method to woocommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

if (class_exists('WC_Tipax_Shipping_Method')) {
    return;
} // Stop if the class already registered

class WC_Tipax_Shipping_Method extends WC_Shipping_Method
{
    /**
     * Constructor for your shipping class
     *
     * @access public
     * @return void
     */

    private $request;

    public function __construct($instance_id = 0)
    {
        $this->id = 'tipax_shipping_method'; // Id for your shipping method. Should be uunique.
        $this->instance_id = absint($instance_id);
        $this->domain = 'tipax_shipping_method';
        $this->title = __('Tipax Shipping Method', 'tipax-shipping'); // Title shown in FrontEnd
        $this->method_title = __('Tipax Shipping Method', 'tipax-shipping'); // Title shown in admin
        $this->method_description = __('Ship your orders with tipax', 'tipax-shipping'); // Description shown in admin

        $this->supports = array(
            'settings',
            'shipping-zones',
            'instance-settings',
            'instance-settings-modal'
        );

        $this->enabled = $this->get_option('enabled');

        $this->request = new Request();

        //check access token exists or no
        $accessToken = get_option('woocommerce_tipax_shipping_method_accesstoken', null);

        if (isset($accessToken) || $accessToken != null) {
            $this->accessToken = $accessToken;
        } else {
            $this->accessToken = null;
        }

        //check settings exists or no
        $settings = get_option('woocommerce_tipax_shipping_method_settings', null);

        if (isset($settings) || $settings != null) {
            $this->settings = $settings;
        } else {
            $this->settings = null;
        }

        $this->collection_time = $this->get_option('collection_time');
        $this->address_method = $this->get_option('address_method');
        $this->lat_lon = $this->get_option('lat_lon');
        $this->checkout_method = $this->get_option('checkout_method');
        $this->shipping_address = $this->get_option('shipping_address');
        $this->woocommerce_currency = get_woocommerce_currency();

        add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
        $this->init();
    }

    /**
     * Is this method enabled?
     */
    public function is_enabled()
    {
        if($this->get_option('enabled') == 'yes'){
            return true;
        } else {
            return false;
        }
    }
    /**
     * Is this method available?
     *
     * @param array $package Package.
     * @return bool
     */
    public function is_available( $package ): bool
    {
        $available = $this->is_enabled();

        // Country availability (legacy, for non-zone based methods).
        if ( ! $this->instance_id && $available ) {
            $countries = is_array( $this->countries ) ? $this->countries : array();

            switch ( $this->availability ) {
                case 'specific':
                case 'including':
                    $available = in_array( $package['destination']['country'], array_intersect( $countries, array_keys( WC()->countries->get_shipping_countries() ) ) );
                    break;
                case 'excluding':
                    $available = in_array( $package['destination']['country'], array_diff( array_keys( WC()->countries->get_shipping_countries() ), $countries ) );
                    break;
                default:
                    $available = in_array( $package['destination']['country'], array_keys( WC()->countries->get_shipping_countries() ) );
                    break;
            }
        }
        //package must have value!
        if ( empty( $package ) ) {
            $available = false;
            error_log("empty( package ): ".(int)$available);
        }

        //if country is not iran is not available
        if ( $package['destination']['country'] != 'IR' ) {
            $available = false;
            error_log("package['destination']['country'] != 'IR': ".(int)$available);
        }

        //if accessToken not saved before is not available

        if ( is_null($this->accessToken ) ) {
           $available = false;
            error_log("is_null(this->accessToken ): ".(int)$available);
        }

        //if name is not confirmed is not available
        $senderOriginName = $this->get_option("name");

        if ( is_null($senderOriginName ) || $senderOriginName == ""  ) {
            $available = false;
            error_log("is_null(senderOriginName ): ".(int)$available);
        }

        //if phone is not confirmed is not available
        $senderOriginPhone = $this->get_option("phone");
        if ( is_null( $senderOriginPhone ) || $senderOriginPhone == "" ) {
            $available = false;
            error_log("is_null(senderOriginPhone ): ".(int)$available);
        }

        //if lat_lon is not selected is not available
        $originLatLon = $this->get_option("lat_lon");
        $lat = null;
        $lon = null;
        if($originLatLon != null){
            $lat_lon = explode(",",$originLatLon);
            $lat = $lat_lon[0];
            $lon = $lat_lon[1];
        }

        //the lat & lon is required
        if(is_null( $originLatLon ) || $lat == null || $lon == null ){
            $available = false;
            error_log("is_null( originLatLon ): ".(int)$available);
        }

        //check origin postalcode and validate it
        $originPostalCode = get_option( 'woocommerce_store_postcode' );
        $validatePostalCode = $originPostalCode ? postalcodeValidate($originPostalCode) : true;
        if ($validatePostalCode == null ) {
            $available = false;
            error_log("is_null( validatePostalCode ): ". (int)$available . " originPostalCode: ".$originPostalCode);
        }

        //check origin plaque
        $originPlaque = $this->get_option("plaque");
        if ( is_null( $originPlaque ) || $originPlaque == "" ) {
            $available = false;
            error_log("is_null( originPlaque ): ".(int)$available);
        }

        //validate and check destination postalcode
        $destinationPostalCode = $package["destination"]["postcode"];
        $validateDestinationPostalCode = $destinationPostalCode ? postalcodeValidate($destinationPostalCode) : true;
        if ( $validateDestinationPostalCode == null ) {
            $available = false;
            error_log("is_null( validateDestinationPostalCode ): ".(int)$available." DestinationPostalCode: ".$destinationPostalCode);
        }

        if($available){
            error_log("No problem about available variable: ".(int)$available);
        }

        return apply_filters( 'woocommerce_shipping_' . $this->id . '_is_available', $available, $package, $this);
    }
        /**
     * Init your settings
     *
     * @access public
     * @return void
     */
    public function init()
    {
        // Load the settings API
        $this->init_instance_form_fields(); // This is part of the instant settings API. showing in modal
        $this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings
        $this->init_settings(); // This is part of the settings API. Loads settings you previously init.

        // Save settings in admin if you have any defined
        add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
        //add assets needed
        add_action('admin_footer', array('WC_Tipax_Shipping_Method', 'enqueue_admin_js'), 10); // Priority needs to be higher than wc_print_js (25).
    }

    public function init_form_fields()
    {
        $this->checkApiKey();
        $this->form_fields = array(
            'enabled' => array(
                'title'   => __('Enable/Disable', 'tipax-shipping'),
                'type'    => 'checkbox',
                'label'   => __('Enable this shipping method', 'tipax-shipping'),
                'default' => 'yes',
            ),
            'cities' => array(
                'title'   => __('Only Tipax cities?', 'tipax-shipping'),
                'type'    => 'checkbox',
                'label'   => __('Do you want Tipax fills your cities?', 'tipax-shipping'),
                'default' => 'no',
            ),
            'username' => array(
                'title' => __('Username', 'tipax-shipping'),
                'type' => 'text',
                'required'=>true,
                'placeholder' => __('Enter Your Tipax Username', 'tipax-shipping'),
                'description' => __('Username of your tipax account', 'tipax-shipping'),
                'desc_tip' => true
            ),
            'password' => array(
                'title' => __('Password', 'tipax-shipping'),
                'type' => 'password',
                'required'=>true,
                'placeholder' => __('Enter Your Tipax Password', 'tipax-shipping'),
                'description' => __('password of your tipax account', 'tipax-shipping'),
                'desc_tip' => true
            ),
            'apiKey' => array(
                'title' => __('API KEY', 'tipax-shipping'),
                'type' => 'text',
                'required'=>true,
                'placeholder' => __('Enter Your Tipax Api Key which is located in profile page', 'tipax-shipping'),
                'description' => __('this Tipax Api Key is located in profile page', 'tipax-shipping'),
                'desc_tip' => true
            ),
        );
    }

    public function init_instance_form_fields()
    {
            $addresses = [];
            $custom_attributes_addresses = [];
            $custom_attributes_addresses_data = [];
            $addresses_objects = $this->loadAddresses();
            $cities_objects = $this->loadCities();
            $all_addresses = array();
            if ($addresses_objects != null) {
                $all_addresses[] = $addresses_objects->address;
                foreach ($all_addresses as $addresses_object) {
                    $addresses[$addresses_object->id] = $addresses_object->fullAddress;
    
                    $custom_attributes_addresses[]["id"] = $addresses_object->id;
                    $custom_attributes_addresses[]["cityId"] = $addresses_object->cityId;
                    $custom_attributes_addresses[]["stateId"] = $addresses_object->provinceId;
                    $custom_attributes_addresses[]["fullAddress"] = $addresses_object->fullAddress;
                }
    
                $custom_attributes_addresses_json = json_encode($custom_attributes_addresses);
                $custom_attributes_addresses_data["data"] = $custom_attributes_addresses_json;
            } else {
                $custom_attributes_addresses_data["data"] = "";
                $addresses = [];
            }
    
            $this->instance_form_fields = array(
                'phone' => array(
                    'title' => __('The phone name of sender (Required)', 'tipax-shipping'),
                    'type' => 'text',
                    'required'=>true,
                    'class' => 'phone',
                    'id' => 'phone',
                    'default' => null,
                    'label' => __('Phone', 'tipax-shipping'),
                ),
                'name' => array(
                    'title' => __('The fullname of sender (Required)', 'tipax-shipping'),
                    'type' => 'text',
                    'required'=>true,
                    'class' => 'name',
                    'id' => 'name',
                    'default' => null,
                    'label' => __('Full Name', 'tipax-shipping'),
                ),
                'address_method' => array(
                    'title' => __('The address method', 'tipax-shipping'),
                    'description' => __('Select the shipping address method', 'tipax-shipping'),
                    'type' => 'radio',
                    'class' => 'address_method',
                    'id' => 'address_method',
                    'default' => 1,
                    'label' => __('shipping address method', 'tipax-shipping'),
                    'options' => [
                        "1" => "1",
                        "2" => "2",
                        "3" => "2",
                    ]
                ),
                'lat_lon' => array(
                    'title' => __('pick address on map (Required)', 'tipax-shipping'),
                    'description' => __('click on map to select the point', 'tipax-shipping'),
                    'type' => 'map',
                    'class' => 'leaflet-map-main',
                    'id' => 'leaflet',
                    'default' => null,
                    'label' => __('shipping address', 'tipax-shipping'),
                ),
                'shipping_address' => array(
                    'title' => __('shipping address', 'tipax-shipping'),
                    'description' => __('Select the shipping address if you choose it for address method', 'tipax-shipping'),
                    'type' => 'select',
                    'default' => null,
                    'label' => __('shipping address', 'tipax-shipping'),
                    'custom_attributes' => $custom_attributes_addresses_data,
                    'options' => $addresses
                ),
                'checkout_method' => array(
                    'title' => __('Checkout Method', 'tipax-shipping'),
                    'description' => __('Select the type of order shipping amount settlement', 'tipax-shipping'),
                    'type' => 'select',
                    'default' => 40,
                    'label' => __('Checkout Method', 'tipax-shipping'),
                    'options' => array(
                        "20" => __('Post Pay (After Payment)', 'tipax-shipping'),
                        "30" => __('Cash on Delivery (COD)', 'tipax-shipping'),
                        "50" => __('Cash with your wallet in tipax', 'tipax-shipping'),
                        "10" => __('Cash with receiver in invoice', 'tipax-shipping'),
                    )
                ),
                'collection_time' => array(
                    'title' => __('Collection Time', 'tipax-shipping'),
                    'description' => __('Select the time of collection of products orders', 'tipax-shipping'),
                    'type' => 'select',
                    'default' => 1,
                    'label' => __('Collection Time', 'tipax-shipping'),
                    'options' => array(
                        '1' => __('The same day', 'tipax-shipping'),
                    )
                ),
                'title' => array(
                    'title' => __('Address Title', 'tipax-shipping'),
                    'type' => 'text',
                    'class' => 'title',
                    'id' => 'title',
                    'default' => null,
                    'label' => __('Title', 'tipax-shipping'),
                ),
                'postal_code' => array(
                    'title' => __('Postal Code', 'tipax-shipping'),
                    'type' => 'text',
                    'class' => 'postal_code',
                    'id' => 'postal_code',
                    'default' => null,
                    'label' => __('Correct Postal Code', 'tipax-shipping'),
                ),
                'floor' => array(
                    'title' => __('Floor', 'tipax-shipping'),
                    'type' => 'text',
                    'class' => 'floor',
                    'id' => 'floor',
                    'default' => null,
                    'label' => __('Floor', 'tipax-shipping'),
                ),
                'unit' => array(
                    'title' => __('Unit', 'tipax-shipping'),
                    'type' => 'text',
                    'class' => 'unit',
                    'id' => 'unit',
                    'default' => null,
                    'label' => __('Unit', 'tipax-shipping'),
                ),
                'city' => array(
                    'title' => __('city', 'tipax-shipping'),
                    'description' => __('City of Origin', 'tipax-shipping'),
                    'type' => 'select',
                    'default' => null,
                    'class' => 'js-example-basic-single',
                    'label' => __('City', 'tipax-shipping'),
                    'options' => $cities_objects
                ),
                'address' => array(
                    'title' => __('Address', 'tipax-shipping'),
                    'type' => 'text',
                    'class' => 'address',
                    'id' => 'address',
                    'default' => null,
                    'label' => __('Address', 'tipax-shipping'),
                ),
                'plaque' => array(
                    'title' => __('the Plaque of origin (Required)', 'tipax-shipping'),
                    'type' => 'text',
                    'class' => 'plaque',
                    'id' => 'plaque',
                    'default' => null,
                    'label' => __('Plaque origin', 'tipax-shipping'),
                ),
            );
    }

    public function generate_map_html($key, $data)
    {

        $field_key = $this->get_field_key($key);
        $defaults  = array(
            'title'             => '',
            'disabled'          => false,
            'class'             => '',
            'css'               => '',
            'placeholder'       => '',
            'type'              => 'text',
            'desc_tip'          => false,
            'description'       => '',
            'custom_attributes' => array(),
        );

        $data = wp_parse_args($data, $defaults);

        ob_start();
?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr($field_key); ?>"><?php echo wp_kses_post($data['title']); ?> <?php echo $this->get_tooltip_html($data); // WPCS: XSS ok. 
                                                                                                                ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo wp_kses_post($data['title']); ?></span></legend>

                    <div class="input-text regular-input <?php echo esc_attr($data['class']); ?>" type="<?php echo esc_attr($data['type']); ?>" name="<?php echo esc_attr($field_key); ?>" id="woocommerce_tipax_shipping_method_map" style="<?php echo esc_attr($data['css']); ?>" placeholder="<?php echo esc_attr($data['placeholder']); ?>" <?php disabled($data['disabled'], true); ?> <?php echo $this->get_custom_attribute_html($data); // WPCS: XSS ok. 
                                                                                                                                                                                                                                                                                                                                                                                            ?>></div>
                    <input type="hidden" class="input-text regular-input" name="<?php echo esc_attr($field_key); ?>" id="map_lat_lon" value="<?php echo esc_attr($this->get_option($key)); ?>">
                    <?php echo $this->get_description_html($data); // WPCS: XSS ok. 
                    ?>
                </fieldset>
            </td>
        </tr>

        <script>
            var map = L.map('woocommerce_tipax_shipping_method_map').setView([33.6371298, 53.2590134], 5);

            var lat_lon_selected = jQuery("#map_lat_lon").val();

            var lat_lon_is_valid = false;
            if(lat_lon_selected.indexOf(",") >= 0){
                lat_lon_is_valid = true;
            }

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: 'Tipax shipping plugin',
                center: [33.6371298, 53.2590134],
                zoom: 10
            }).addTo(map);

            var marker;

            if (lat_lon_selected && lat_lon_is_valid === true) {
                if (marker) {
                    map.removeLayer(marker);
                }

                var iconOptions = {
                    iconUrl: '<?php echo TSM_ASSETS_URL ?>images/tipax-pointer.png',
                    iconSize: [50, 50]
                }

                // Creating a custom icon
                var customIcon = L.icon(iconOptions);
                var markerOptions = {
                    title: "MyLocation",
                    clickable: false,
                    draggable: false,
                    icon: customIcon
                }
                // Creating a marker
                if (lat_lon_selected) {
                    lat_lon_selected = lat_lon_selected.split(',');
                    marker = L.marker(lat_lon_selected, markerOptions).addTo(map);
                }
            }

            map.on('click', function(e) {
                jQuery("#map_lat_lon").val("");
                console.log("Lat, Lon : " + e.latlng.lat + ", " + e.latlng.lng);

                if (marker) {
                    map.removeLayer(marker);
                }

                var iconOptions = {
                    iconUrl: '<?php echo TSM_ASSETS_URL ?>images/tipax-pointer.png',
                    iconSize: [50, 50]
                }

                // Creating a custom icon
                var customIcon = L.icon(iconOptions);
                var markerOptions = {
                    title: "MyLocation",
                    clickable: false,
                    draggable: false,
                    icon: customIcon
                }
                // Creating a marker
                marker = L.marker([e.latlng.lat, e.latlng.lng], markerOptions);

                jQuery("#map_lat_lon").val(e.latlng.lat + "," + e.latlng.lng);
                // Adding marker to the map
                marker.addTo(map);
            });
            jQuery(document).ready(function() {
                jQuery('.js-example-basic-single').select2();
            });
        </script>
    <?php
        return ob_get_clean();
    }

    public function generate_radio_html($key, $data)
    {
        $field_key = $this->get_field_key($key);
        $defaults  = array(
            'title'             => '',
            'disabled'          => false,
            'class'             => '',
            'css'               => '',
            'placeholder'       => '',
            'type'              => 'text',
            'desc_tip'          => false,
            'description'       => '',
            'custom_attributes' => array(),
        );

        $data = wp_parse_args($data, $defaults);

        ob_start();
    ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr($field_key); ?>"><?php echo wp_kses_post($data['title']); ?> <?php echo $this->get_tooltip_html($data); // WPCS: XSS ok. 
                                                                                                                ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo wp_kses_post($data['title']); ?></span></legend>

                    <!--<input style="width: 16px !important; min-width: unset !important;" type="radio" id="radio_example_one" name="woocommerce_tipax_shipping_method_address_method" value="1" <?php if (esc_attr($this->get_option($key)) == 1) {echo "checked='true'";} ?>>-->
                    <!--<label for="radio_example_one"><?php echo __('From woocommerce shop information', 'tipax-shipping') ?></label>-->

                    <input style="width: 16px !important; min-width: unset !important;" type="radio" id="radio_example_two" name="woocommerce_tipax_shipping_method_address_method" value="2" <?php if (esc_attr($this->get_option($key)) == 2) {echo "checked='true'";} ?>>
                    <label for="radio_example_two"><?php echo __('Pick up from map', 'tipax-shipping') ?></label>

                    <input style="width: 16px !important; min-width: unset !important;" type="radio" id="radio_example_three" name="woocommerce_tipax_shipping_method_address_method" value="3" <?php if (esc_attr($this->get_option($key)) == 3) {echo "checked='true'";} ?>>
                    <label for="radio_example_three"><?php echo __('From defined addresses', 'tipax-shipping') ?></label>

                    <?php echo $this->get_description_html($data); // WPCS: XSS ok.
                    ?>
                </fieldset>
            </td>
        </tr>
<?php
        return ob_get_clean();
    }

    public static function enqueue_admin_js()
    {

        if (is_checkout()) {
            return false;
        }

        wp_register_script('leaflet_map', TSM_ASSETS_URL . 'js/leaflet.js', ['jquery'], '4.0.3');
        wp_enqueue_script('leaflet_map', "", "", "", true);
        wp_register_style('leaflet_map_css', TSM_ASSETS_URL . 'css/leaflet.css');
        wp_enqueue_style('leaflet_map_css');
        wp_register_style( 'select2css', 'css/select2.css', false, '1.0', 'all' );
        wp_register_script( 'select2', 'js/select2.js', array( 'jquery' ), '1.0', true );
        wp_enqueue_style( 'select2css' );
        wp_enqueue_script( 'select2' );
    }

    public function add_rate($args = array())
    {
        $args = apply_filters(
            'woocommerce_shipping_method_add_rate_args',
            wp_parse_args(
                $args,
                array(
                    'id'             => $this->get_rate_id(), // ID for the rate. If not passed, this id:instance default will be used.
                    'label'          => '', // Label for the rate.
                    'cost'           => '0', // Amount or array of costs (per item shipping).
                    'taxes'          => '', // Pass taxes, or leave empty to have it calculated for you, or 'false' to disable calculations.
                    'calc_tax'       => 'per_order', // Calc tax per_order or per_item. Per item needs an array of costs.
                    'meta_data'      => array(), // Array of misc meta data to store along with this rate - key value pairs.
                    'package'        => false, // Package array this rate was generated for @since 2.6.0.
                    'price_decimals' => wc_get_price_decimals(),
                )
            ),
            $this
        );

        // ID and label are required.
        if (!$args['id'] || !$args['label']) {
            return;
        }

        // Total up the cost.
        $total_cost = is_array($args['cost']) ? array_sum($args['cost']) : $args['cost'];
        $taxes      = $args['taxes'];

        // Taxes - if not an array and not set to false, calc tax based on cost and passed calc_tax variable. This saves shipping methods having to do complex tax calculations.
        if (!is_array($taxes) && false !== $taxes && $total_cost > 0 && $this->is_taxable()) {
            $taxes = 'per_item' === $args['calc_tax'] ? $this->get_taxes_per_item($args['cost']) : WC_Tax::calc_shipping_tax($total_cost, WC_Tax::get_shipping_tax_rates());
        }

        // Round the total cost after taxes have been calculated.
        $total_cost = wc_format_decimal($total_cost, $args['price_decimals']);

        // Create rate object.
        $rate = new WC_Shipping_Rate();
        $rate->set_id($args['id']);
        $rate->set_method_id($this->id);
        $rate->set_instance_id($this->instance_id);
        $rate->set_label($args['label']);
        $rate->set_cost($total_cost);
        $rate->set_taxes($taxes);

        if (!empty($args['meta_data'])) {
            foreach ($args['meta_data'] as $key => $value) {
                $rate->add_meta_data($key, $value);
            }
        }

        // Store package data.
        if ($args['package']) {
            $items_in_package = array();
            foreach ($args['package']['contents'] as $item) {
                $product            = $item['data'];
                $items_in_package[] = $product->get_name() . ' &times; ' . $item['quantity'];
            }
            $rate->add_meta_data(__('Items', 'woocommerce'), implode(', ', $items_in_package));
        }

        $this->rates[$args['id']] = apply_filters('woocommerce_shipping_method_add_rate', $rate, $args, $this);
    }

    public function calculate_shipping($package = array())
    {
        $cost = 0;
        //origin and destination cityId

        // The country/state IR:THR
        $storeRawCountry = get_option( 'woocommerce_default_country' );
        // Split the country/state
        $splitSplit = explode( ":", $storeRawCountry );

        // Country and state separated: IR - THR
        $storeCountry = $splitSplit[0];
        $storeState   = $splitSplit[1];
        //get store city form woocommerce setting
        $storeCity = get_option('woocommerce_store_city');

        $packageCity = $package['destination']["city"];
        $packageCountry = $package['destination']["country"];
        $packageState = $package['destination']["state"];

        //if shop is not located in iran we could not provide service
        if($storeCountry != "IR") {
            return false;
        }

        $originStateReal = isset(WC()->countries->get_states( $storeCountry )[$storeState]) ? WC()->countries->get_states( $storeCountry )[$storeState] : null;
        $packageStateReal = isset(WC()->countries->get_states( $packageCountry )[$packageState]) ? WC()->countries->get_states( $packageCountry )[$packageState] : null;

        $destinationCityId = get_city_code($packageCity,$packageStateReal);
        
        if($destinationCityId == null){
            error_log("packageCity: ".$packageCity. "storeCity: ".$storeCity." destinationCityId: ".$destinationCityId);
            return false;
        }

        $addressMethod = $this->get_option("address_method");
        $originAddressId = $this->get_option("shipping_address");
        
        //if address method is address based, so address have to be chosen
        if($addressMethod == 3 && $originAddressId == null){
            error_log("originAddressId: ".(int)$originAddressId);
            return false;
        }

        $method_2_origin = get_option('tipax_originid');
        
        //if address method is address based, so address have to be chosen
        if($addressMethod == 2 && $method_2_origin == null){
            error_log("originAddressIdMethod2: ".(int)$method_2_origin);
            return false;
        }
        
        $method_2_origin_parent = get_option('tipax_originparentid');
        
        //if address method is address based, so address have to be chosen
        if($addressMethod == 2 && $method_2_origin_parent == null){
            error_log("originAddressParentIdMethod2: ".(int)$method_2_origin_parent);
            return false;
        }
        $originLatLon = $this->get_option("lat_lon");
        $lat = null;
        $lon = null;
        if($originLatLon != null){
            $lat_lon = explode(",",$originLatLon);
            $lat = $lat_lon[0];
            $lon = $lat_lon[1];
        }

        //lat & lon is required
        if($lat == null || $lon == null){
            return false;
        }

        $this->checkout_method = $this->get_option('checkout_method');

        if($this->checkout_method == null){
            $this->checkout_method = 10;
        }

        $multipleProducts = false;

        $weight_unit = get_option('woocommerce_weight_unit');
        $dimension_unit = get_option('woocommerce_dimension_unit');

        error_log("weightUnit: ".$weight_unit);
        error_log("dimensionUnit: ".$dimension_unit);

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

        if (count($package['contents']) > 0) {
            $data = new stdClass;
            $data->packageInputs = [];
            $productCounts = 0;

            foreach ($package['contents'] as $package) {

                $quantity = (int)$package["quantity"];
                $productCounts = $quantity + $productCounts;
                $innerDimensions = [];

                for($i = 1; $i <= $quantity ; $i++){
                    if(isset($package['variation_id']) && $package['variation_id'] == 0){
                        $product =  wc_get_product($package['product_id']);
                    } else {
                        $product =  wc_get_product($package['variation_id']);
                    }
                    
                    $productWeight = (!empty($product->get_weight()) && $product->get_weight() > 0) ? $product->get_weight() : 6;
                    $productLength = (!empty($product->get_length()) && $product->get_length() > 0) ? $product->get_length() : 45;
                    $productHeight = (!empty($product->get_height()) && $product->get_height() > 0) ? $product->get_height() : 45;
                    $productWidth  = (!empty($product->get_width()) && $product->get_width() > 0) ? $product->get_width() : 45;


                    $innerDimensions[] = [$productWeight,$productLength,$productHeight,$productWidth];

                    $packageInputs = new stdClass;

                    if($addressMethod == 3){
                        
                        $addresses_objects = $this->loadAddresses();
                        $dataAddr = [];
                        $allAddresses = [];
                        if ($this->accessToken) {
                            $responseAddr = $this->request->apiRequest('Addresses', $dataAddr, "GET", $this->accessToken);
                        } else {
                            return false;
                        }
                        if (isset($responseAddr[0])) {
                            foreach ($responseAddr as $address) {
                                $allAddresses[$address->id]['originId'] = $address->addressesId;
                                $allAddresses[$address->id]['fullAddress'] = $address;
                            }
                        } else {
                            return false;
                        }
                        $originIdToCheck = $originAddressId;
                        if (count($allAddresses) > 0) {
                            $exists = false;
                            
                            // Loop through each object in the array
                            foreach ($allAddresses as $object) {
                                // Check if the 'originId' matches the one we're looking for
                                if ($object['originId'] == $originIdToCheck) {
                                    $originCityETipaxId = $object;
                                    $exists = true;
                                    break; // Break the loop once the ID is found
                                }
                            }
                            if(!$exists){
                                $originAddressId = $allAddresses[$addresses_objects->id]['originId'];
                                 if( get_option('shipping_address') !== false ){
                                     update_option('shipping_address', $originAddressId);
                                 }else{
                                     add_option('shipping_address', $originAddressId, '', 'yes');
                                 }
                                 delete_transient( 'tipax_addresses');
                                 set_transient( 'tipax_addresses', $allAddresses[$addresses_objects->id]['fullAddress'],12 * HOUR_IN_SECONDS);
                                $settings = get_option('woocommerce_tipax_shipping_method_'.$this->instance_id.'_settings');
                                 if( $settings !== false){
                                     if($settings["shipping_address"] != $originAddressId) {
                                         $settings["shipping_address"] = $originAddressId;
                                        update_option('woocommerce_tipax_shipping_method_'.$this->instance_id.'_settings',$settings);
                                     }
                                 }else{
                                     add_option('woocommerce_tipax_shipping_method_'.$this->instance_id.'_settings',$settings,'','yes');
                                 }
                            }
                        } else {
                            return false;
                        }
                        
                        $packageInputs->origin = new stdClass;
                        $packageInputs->origin->Id = $originAddressId;
                    }else{
                        $dataAddr = [];
                        $allAddresses = [];
                        if ($this->accessToken) {
                            $responseAddr = $this->request->apiRequest('Addresses', $dataAddr, "GET", $this->accessToken);
                        }
                        if (isset($responseAddr[0])) {
                            foreach ($responseAddr as $address) {
                                $allAddresses[$address->id]['originId'] = $address->addressesId;
                                $allAddresses[$address->id]['fullAddress'] = $address;
                            }
                        }
                        $originIdToCheck = $method_2_origin;
                        if (count($allAddresses) > 0) {
                            $exists = false;
                            
                            // Loop through each object in the array
                            foreach ($allAddresses as $object) {
                                // Check if the 'originId' matches the one we're looking for
                                if ($object['originId'] == $originIdToCheck) {
                                    $exists = true;
                                    break; // Break the loop once the ID is found
                                }
                            }
                            if(!$exists){
                                $method_2_origin = $allAddresses[$method_2_origin_parent]['originId'];
                                 if( get_option('tipax_originid') !== false ){
                                     update_option('tipax_originid', $method_2_origin, '', 'yes');
                                 }else{
                                     add_option('tipax_originid', $method_2_origin, '', 'yes');
                                 }
                        
                                
                                if ($settings && isset($allAddresses[$method_2_origin_parent])) {
                                    $settings['title'] = $allAddresses[$method_2_origin_parent]['fullAddress']->title;
                                    $settings['address'] = $allAddresses[$method_2_origin_parent]['fullAddress']->address->fullAddress;
                                    $settings['postal_code'] = $allAddresses[$method_2_origin_parent]['fullAddress']->address->postalCode;
                                    $settings['floor'] = $allAddresses[$method_2_origin_parent]['fullAddress']->address->floor;
                                    $settings['unit'] = $allAddresses[$method_2_origin_parent]['fullAddress']->address->unit;
                                    $settings['plaque'] = $allAddresses[$method_2_origin_parent]['fullAddress']->address->plaque;
                                    $settings['city'] = $allAddresses[$method_2_origin_parent]['fullAddress']->address->cityETipaxId;
                                    $settings['lat_lon'] = $allAddresses[$method_2_origin_parent]['fullAddress']->address->latitude.','.$allAddresses[$method_2_origin_parent]['fullAddress']->address->longitude;
                                    update_option('woocommerce_tipax_shipping_method_'.$this->instance_id.'_settings', $settings);
                                }
                            }
                            // error_log(json_encode($allAddresses[$method_2_origin_parent]['fullAddress']->address));
                        }
                        $packageInputs->origin = new stdClass;
                        $packageInputs->origin->Id = $method_2_origin;
                    }

                    $packageInputs->destination = new stdClass;
                    $packageInputs->destination->cityId = $destinationCityId;

                    $packageInputs->weight = $weight_unit == "kg" ? $productWeight : ($productWeight/1000);
                    $packageInputs->length = $productLength * $unit_ratio;
                    $packageInputs->width = $productWidth * $unit_ratio;
                    $packageInputs->height = $productHeight * $unit_ratio;
                    $packageInputs->packageValue = $this->woocommerce_currency == 'IRT' ? floatval($product->get_price()*10) : $product->get_price();
                    $packageInputs->packingId = 3;
                    $packageInputs->distributionType = 10;
                    $packageInputs->pickupType = 10;
                    $packageInputs->packageContentId = 9;
                    $packageInputs->packType = 20;
                    $packageInputs->paymentType = $this->checkout_method;
                    if(isset($allAddresses[$address->id]['fullAddress']->address->cityETipaxId)) {
                        if($allAddresses[$address->id]['fullAddress']->address->cityETipaxId == $destinationCityId) {
                            $packageInputs->serviceId = 7;
                        }
                    }

                    $data->packageInputs[] = $packageInputs;
                }
            }


            //check has multiple products in cart
            if($productCounts>1){
                $multipleProducts = true;
            }

                if(count($innerDimensions)>0){
                    foreach ($innerDimensions as $dimensions){
                        if(in_array(0,$dimensions)){
                            //some dimensions are zero or no defined,so not we cannot use tipax methods
                            error_log("Tipax shipping: some dimensions are zero or not defined");
                            return false;
                        }
                    }
                }

                if($addressMethod == 3) {
                    $response = $this->request->apiRequest('Pricing/WithOriginAddressId', $data, 'POST',$this->accessToken);
                }else{
                    $response = $this->request->apiRequest('Pricing/WithOriginAddressId', $data, 'POST',$this->accessToken);
                }

                if(isset($response->errors)){
                    return false;
                }


                if($multipleProducts == false){
                    $shipping = $response[0];
                    //if we have just 1 product
                    if (isset($shipping->regularPlusRate)) {

                        $cost = $shipping->regularPlusRate->finalPrice;

                        $cost = floatval($cost);

                        $currentPersianCurrency = "ریال";
                        if($this->woocommerce_currency == 'IRT'){
                            $cost = ceil($cost/5) * 5;
                            $cost /= 10;
                            $cost = (int)($cost);
                            $currentPersianCurrency = "تومان";
                        }

                        $shippingServiceTitle = $shipping->regularPlusRate->serviceTitle;

                        //Check checkout_method if is 20 or 50
                        if($this->checkout_method == 20 || $this->checkout_method == 50){

                            $currentCost = $cost." ".$currentPersianCurrency;

                            if($this->checkout_method == 20){
                                $serviceDesc = "(پس کرایه ، مبلغ $currentCost )";
                            }else{
                                $serviceDesc = "(پرداخت از کیف پول مدیر فروشگاه، مبلغ $currentCost)";
                            }
                            $shippingServiceTitle = "تیپاکس: ".$serviceDesc." ".$shippingServiceTitle;
                            $cost = 0;
                        }else{
                            //when not is 30 or 40 and there is no need to change the price
                            $shippingServiceTitle = "تیپاکس "." ".$shippingServiceTitle;
                        }


                        $rate = array(
                            'id'        => $this->id.$shipping->regularPlusRate->serviceId,
                            'label'     => $shippingServiceTitle,
                            'meta_data' => array( 'serviceId' => $shipping->regularPlusRate->serviceId),
                            'cost'      => $cost
                        );
                        $this->add_rate($rate);
                    }

                    if (isset($shipping->expressRate)) {

                        $cost = $shipping->expressRate->finalPrice;
                        $cost = floatval($cost);

                        if($this->woocommerce_currency == 'IRT'){
                            $cost = ceil($cost/5) * 5;
                            $cost /= 10;
                            $cost = (int)($cost);
                            $currentPersianCurrency = "تومان";
                        }

                        $shippingServiceTitle = $shipping->expressRate->serviceTitle;

                        //Check checkout_method if is 20 or 50
                        if($this->checkout_method == 20 || $this->checkout_method == 50){

                            $currentCost = $cost." ".$currentPersianCurrency;

                            if($this->checkout_method == 20){
                                $serviceDesc = "(پس کرایه ، مبلغ $currentCost )";
                            }else{
                                $serviceDesc = "(پرداخت از کیف پول مدیر فروشگاه، مبلغ $currentCost)";
                            }
                            $shippingServiceTitle = "تیپاکس: ".$serviceDesc." ".$shippingServiceTitle;
                            $cost = 0;
                        }else{
                            //when not is 30 or 40 and there is no need to change the price
                            $shippingServiceTitle = "تیپاکس "." ".$shippingServiceTitle;
                        }

                        $rate = array(
                            'id'        => $this->id.$shipping->expressRate->serviceId,
                            'label'     => $shippingServiceTitle,
                            'meta_data' => array( 'serviceId' => $shipping->expressRate->serviceId),
                            'cost'      => $cost
                        );
                        $this->add_rate($rate);
                    }

                    if (isset($shipping->airExpressRate)) {

                        $cost = $shipping->airExpressRate->finalPrice;
                        $cost = floatval($cost);
                        if($this->woocommerce_currency == 'IRT'){
                            $cost = ceil($cost/5) * 5;
                            $cost /= 10;
                            $cost = (int)($cost);
                            $currentPersianCurrency = "تومان";
                        }

                        $shippingServiceTitle = $shipping->airExpressRate->serviceTitle;

                        //Check checkout_method if is 20 or 50
                        if($this->checkout_method == 20 || $this->checkout_method == 50){

                            $currentCost = $cost." ".$currentPersianCurrency;

                            if($this->checkout_method == 20){
                                $serviceDesc = "(پس کرایه ، مبلغ $currentCost )";
                            }else{
                                $serviceDesc = "(پرداخت از کیف پول مدیر فروشگاه، مبلغ $currentCost)";
                            }
                            $shippingServiceTitle = "تیپاکس: ".$serviceDesc." ".$shippingServiceTitle;
                            $cost = 0;
                        }else{
                            //when not is 30 or 40 and there is no need to change the price
                            $shippingServiceTitle = "تیپاکس "." ".$shippingServiceTitle;
                        }

                        $rate = array(
                            'id'        => $this->id.$shipping->airExpressRate->serviceId,
                            'label'     => $shippingServiceTitle,
                            'meta_data' => array( 'serviceId' => $shipping->airExpressRate->serviceId),
                            'cost'      => $cost
                        );
                        $this->add_rate($rate);
                    }

                    if (isset($shipping->regularRate)) {

                        $cost = $shipping->regularRate->finalPrice;
                        $cost = floatval($cost);
                        if($this->woocommerce_currency == 'IRT'){
                            $cost = ceil($cost/5) * 5;
                            $cost /= 10;
                            $cost = (int)($cost);
                            $currentPersianCurrency = "تومان";
                        }

                        $shippingServiceTitle = $shipping->regularRate->serviceTitle;

                        //Check checkout_method if is 20 or 50
                        if($this->checkout_method == 20 || $this->checkout_method == 50){

                            $currentCost = $cost." ".$currentPersianCurrency;

                            if($this->checkout_method == 20){
                                $serviceDesc = "(پس کرایه ، مبلغ $currentCost )";
                            }else{
                                $serviceDesc = "(پرداخت از کیف پول مدیر فروشگاه، مبلغ $currentCost)";
                            }
                            $shippingServiceTitle = "تیپاکس: ".$serviceDesc." ".$shippingServiceTitle;
                            $cost = 0;
                        }else{
                            //when not is 30 or 40 and there is no need to change the price
                            $shippingServiceTitle = "تیپاکس "." ".$shippingServiceTitle;
                        }

                        $rate = array(
                            'id'        => $this->id.$shipping->regularRate->serviceId,
                            'label'     => $shippingServiceTitle,
                            'meta_data' => array( 'serviceId' => $shipping->regularRate->serviceId),
                            'cost'      => $cost
                        );
                        $this->add_rate($rate);
                    }
                }else{
                    //if we have multiple products
                    $shippings = $response != null ? $response : [];
                    $rates = [];

                    if(count($shippings)>0){
                        foreach ($shippings as $shipping){


                            if (isset($shipping->regularPlusRate)) {

                                $cost = $shipping->regularPlusRate->finalPrice;

                                $cost = floatval($cost);

                                $currentPersianCurrency = "ریال";
                                if($this->woocommerce_currency == 'IRT'){
                                    $cost = ceil($cost/5) * 5;
                                    $cost /= 10;
                                    $cost = (int)($cost);
                                    $currentPersianCurrency = "تومان";
                                }

                                $shippingServiceTitle = $shipping->regularPlusRate->serviceTitle;

                                //Check checkout_method if is 20 or 50
                                if($this->checkout_method == 20 || $this->checkout_method == 50){

                                    $currentCost = $cost." ".$currentPersianCurrency;

                                    if($this->checkout_method == 20){
                                        $serviceDesc = "(پس کرایه ، مبلغ $currentCost )";
                                    }else{
                                        $serviceDesc = "(پرداخت از کیف پول مدیر فروشگاه، مبلغ $currentCost)";
                                    }
                                    $shippingServiceTitle = "تیپاکس: ".$serviceDesc." ".$shippingServiceTitle;
                                    $cost = 0;
                                }else{
                                    //when not is 30 or 40 and there is no need to change the price
                                    $shippingServiceTitle = "تیپاکس "." ".$shippingServiceTitle;
                                }

                                if(isset($calculatedRegularPlusRatePrice)){
                                    $calculatedRegularPlusRatePrice = $cost+$calculatedRegularPlusRatePrice;
                                }else{
                                    $calculatedRegularPlusRatePrice = $cost;
                                }


                                $rates["regularPlusRate"] = array(
                                    'id'        => $this->id.$shipping->regularPlusRate->serviceId,
                                    'label'     => $shippingServiceTitle,
                                    'meta_data' => array( 'serviceId' => $shipping->regularPlusRate->serviceId),
                                    'cost'      => $calculatedRegularPlusRatePrice,
                                    "name"=>"regularPlusRate"
                                );
                            }

                            if (isset($shipping->expressRate)) {

                                $cost = $shipping->expressRate->finalPrice;
                                $cost = floatval($cost);

                                if($this->woocommerce_currency == 'IRT'){
                                    $cost = ceil($cost/5) * 5;
                                    $cost /= 10;
                                    $cost = (int)($cost);
                                    $currentPersianCurrency = "تومان";
                                }

                                $shippingServiceTitle = $shipping->expressRate->serviceTitle;

                                //Check checkout_method if is 20 or 50
                                if($this->checkout_method == 20 || $this->checkout_method == 50){

                                    $currentCost = $cost." ".$currentPersianCurrency;

                                    if($this->checkout_method == 20){
                                        $serviceDesc = "(پس کرایه ، مبلغ $currentCost )";
                                    }else{
                                        $serviceDesc = "(پرداخت از کیف پول مدیر فروشگاه، مبلغ $currentCost)";
                                    }
                                    $shippingServiceTitle = "تیپاکس: ".$serviceDesc." ".$shippingServiceTitle;
                                    $cost = 0;
                                }else{
                                    //when not is 30 or 40 and there is no need to change the price
                                    $shippingServiceTitle = "تیپاکس "." ".$shippingServiceTitle;
                                }

                                if(isset($calculatedExpressRatePrice)){
                                    $calculatedExpressRatePrice = $cost+$calculatedExpressRatePrice;
                                }else{
                                    $calculatedExpressRatePrice = $cost;
                                }

                                $rates["expressRate"] = array(
                                    'id'        => $this->id.$shipping->expressRate->serviceId,
                                    'label'     => $shippingServiceTitle,
                                    'meta_data' => array( 'serviceId' => $shipping->expressRate->serviceId),
                                    'cost'      => $calculatedExpressRatePrice,
                                    "name"=>"expressRate"
                                );
                            }

                            if (isset($shipping->airExpressRate)) {

                                $cost = $shipping->airExpressRate->finalPrice;
                                $cost = floatval($cost);
                                if($this->woocommerce_currency == 'IRT'){
                                    $cost = ceil($cost/5) * 5;
                                    $cost /= 10;
                                    $cost = (int)($cost);
                                    $currentPersianCurrency = "تومان";
                                }

                                $shippingServiceTitle = $shipping->airExpressRate->serviceTitle;

                                //Check checkout_method if is 20 or 50
                                if($this->checkout_method == 20 || $this->checkout_method == 50){

                                    $currentCost = $cost." ".$currentPersianCurrency;

                                    if($this->checkout_method == 20){
                                        $serviceDesc = "(پس کرایه ، مبلغ $currentCost )";
                                    }else{
                                        $serviceDesc = "(پرداخت از کیف پول مدیر فروشگاه، مبلغ $currentCost)";
                                    }
                                    $shippingServiceTitle = "تیپاکس: ".$serviceDesc." ".$shippingServiceTitle;
                                    $cost = 0;
                                }else{
                                    //when not is 30 or 40 and there is no need to change the price
                                    $shippingServiceTitle = "تیپاکس "." ".$shippingServiceTitle;
                                }

                                if(isset($calculatedAirExpressRatePrice)){
                                    $calculatedAirExpressRatePrice = $cost+$calculatedAirExpressRatePrice;
                                }else{
                                    $calculatedAirExpressRatePrice = $cost;
                                }

                                $rates["airExpressRate"] = array(
                                    'id'        => $this->id.$shipping->airExpressRate->serviceId,
                                    'label'     => $shippingServiceTitle,
                                    'meta_data' => array( 'serviceId' => $shipping->airExpressRate->serviceId),
                                    'cost'      => $calculatedAirExpressRatePrice,
                                    "name"=>"airExpressRate"
                                );
                            }

                            if (isset($shipping->regularRate)) {

                                $cost = $shipping->regularRate->finalPrice;
                                $cost = floatval($cost);
                                if($this->woocommerce_currency == 'IRT'){
                                    $cost = ceil($cost/5) * 5;
                                    $cost /= 10;
                                    $cost = (int)($cost);
                                    $currentPersianCurrency = "تومان";
                                }

                                $shippingServiceTitle = $shipping->regularRate->serviceTitle;

                                //Check checkout_method if is 20 or 50
                                if($this->checkout_method == 20 || $this->checkout_method == 50){

                                    $currentCost = $cost." ".$currentPersianCurrency;

                                    if($this->checkout_method == 20){
                                        $serviceDesc = "(پس کرایه ، مبلغ $currentCost )";
                                    }else{
                                        $serviceDesc = "(پرداخت از کیف پول مدیر فروشگاه، مبلغ $currentCost)";
                                    }
                                    $shippingServiceTitle = "تیپاکس: ".$serviceDesc." ".$shippingServiceTitle;
                                    $cost = 0;
                                }else{
                                    //when not is 30 or 40 and there is no need to change the price
                                    $shippingServiceTitle = "تیپاکس "." ".$shippingServiceTitle;
                                }

                                if(isset($calculatedRegularRatePrice)){
                                    $calculatedRegularRatePrice = $cost+$calculatedRegularRatePrice;
                                }else{
                                    $calculatedRegularRatePrice = $cost;
                                }

                                $rates["regularRate"] = array(
                                    'id'        => $this->id.$shipping->regularRate->serviceId,
                                    'label'     => $shippingServiceTitle,
                                    'meta_data' => array( 'serviceId' => $shipping->regularRate->serviceId),
                                    'cost'      => $calculatedRegularRatePrice,
                                    "name"=>"regularRate"
                                );
                            }
                        }

                        foreach ($rates as $key=>$rate){

                            $mainRates = array(
                                'id'        => $rates[$key]["id"],
                                'label'     => $rates[$key]["label"],
                                'meta_data' => $rates[$key]["meta_data"],
                                'cost'      => $rates[$key]["cost"]
                            );

                            $this->add_rate($mainRates);
                        }
                    }

                }
        }
    }

    public function add_rate_cost($cost, $package)
    {
        $rate = apply_filters('tipax_add_rate', [
            'id'    => $this->get_rate_id(),
            'label' => $this->title,
            'cost'  => $cost,
        ], $package, $this);

        $this->add_rate($rate);
    }

    public function get_terms_option($term_id)
    {
        $ancestors = get_ancestors($term_id, 'state_city');

        if (empty($ancestors)) {
            return [];
        }

        array_unshift($ancestors, $term_id);

        $options = array_map([$this, 'get_term_option'], $ancestors);

        return $options;
    }

    public function checkApiKey($refreshToken = false)
    {
        if ($refreshToken == true) {
            $data = [
                'username' => isset($this->settings["username"]) ? $this->settings["username"] : null,
                'password' => isset($this->settings["password"]) ? $this->settings["password"] : null,
                'apiKey' => isset($this->settings["apiKey"]) ? $this->settings["apiKey"] : null,
            ];
            $response = $this->request->apiRequest('Account/token', $data, "POST");

            if ($response) {
                $accessToken = $response->accessToken;
                $this->accessToken = $accessToken;
                update_option('woocommerce_tipax_shipping_method_accesstoken', $accessToken, '', 'yes');
            }
        }

        if ((isset($this->settings['apiKey'])) || (isset($this->settings['username'])) || (isset($this->settings['password']))) {

            if($this->settings['apiKey'] || $this->settings['username']  || $this->settings['password']){

                $data = [
                    'username' => isset($this->settings["username"]) ? $this->settings["username"] : null,
                    'password' => isset($this->settings["password"]) ? $this->settings["password"] : null,
                    'apiKey' => isset($this->settings["apiKey"]) ? $this->settings["apiKey"] : null,
                ];


                if (isset($this->accessToken) || !$this->accessToken) {
                    if ($this->accessToken == null) {

                        $response = $this->request->apiRequest('Account/token', $data, "POST");
                        if(!isset($response->errors)){
                            $accessToken = isset($response->accessToken) ? $response->accessToken : null;

                            if($accessToken != null){
                                $this->accessToken = $accessToken;
                                 if( get_option('woocommerce_tipax_shipping_method_accesstoken') !== false ){
                                     update_option('woocommerce_tipax_shipping_method_accesstoken', $accessToken, '', 'yes');
                                 }else{
                                     add_option('woocommerce_tipax_shipping_method_accesstoken', $accessToken, '', 'yes');
                                 }
                            }
                        }else{
                            $message = new Message();
                            return $message->default($response->message);
                        }
                    }
                }


            }

        }
    }

    /**
     * Processes and saves global shipping method options in the admin area.
     *
     * This method is usually attached to woocommerce_update_options_x hooks.
     *
     * @since 2.6.0
     * @return bool was anything saved?
     */
    public function process_admin_options() {
        //when edit main tipax settings, address and accesstoken will be deleted
        if($this->instance_id == 0 && $this->id == "tipax_shipping_method"){
            delete_option("woocommerce_tipax_shipping_method_accesstoken");
            error_log("process_admin_options: woocommerce_tipax_shipping_method_accesstoken Deleted");
            delete_transient( 'tipax_addresses' );
            error_log("process_admin_options: tipax_addresses transient Deleted");
        }

        if ( ! $this->instance_id ) {
            return parent::process_admin_options();
        }

        // Check we are processing the correct form for this instance.
        if ( ! isset( $_REQUEST['instance_id'] ) || absint( $_REQUEST['instance_id'] ) !== $this->instance_id ) { // WPCS: input var ok, CSRF ok.
            return false;
        }

        $this->init_instance_settings();


        $post_data = $this->get_post_data();
        if(isset($post_data["woocommerce_tipax_shipping_method_address_method"]) && $post_data["woocommerce_tipax_shipping_method_address_method"] == 2){
            if($post_data['woocommerce_tipax_shipping_method_postal_code'] != $this->get_option('postal_code')){
                $lat = explode(',', $post_data['woocommerce_tipax_shipping_method_lat_lon'])[0];
                $lon = explode(',', $post_data['woocommerce_tipax_shipping_method_lat_lon'])[1];
                $data = [
                    'title' => $post_data['woocommerce_tipax_shipping_method_title'],
                    'cityId' => $post_data['woocommerce_tipax_shipping_method_city'],
                    'mainStreet1' => $post_data['woocommerce_tipax_shipping_method_address'],
                    'latitude' => $lat,
                    'longitude' => $lon,
                    'postalCode' => $post_data['woocommerce_tipax_shipping_method_postal_code'],
                    'unit' => $post_data['woocommerce_tipax_shipping_method_unit'],
                    'floor' => $post_data['woocommerce_tipax_shipping_method_floor'],
                    'no' => $post_data['woocommerce_tipax_shipping_method_plaque'],
                    'isDefault' => true
                ];
                if ($this->accessToken) {
                    $response = $this->request->apiRequest('Addresses', $data, "POST", $this->accessToken);
                    if( get_option('tipax_originid') !== false ){
                        update_option('tipax_originid', $response->addressesId, '', 'yes');
                    }else{
                        add_option('tipax_originid', $response->addressesId, '', 'yes');
                    }
                    if( get_option('tipax_originparentid') !== false ){
                        update_option('tipax_originparentid', $response->id, '', 'yes');
                    }else{
                        add_option('tipax_originparentid', $response->id, '', 'yes');
                    }
                }
            }
        }
        
        foreach ( $this->get_instance_form_fields() as $key => $field ) {
            if ( 'title' !== $this->get_field_type( $field ) ) {
                try {
                    $this->instance_settings[ $key ] = $this->get_field_value( $key, $field, $post_data );
                } catch ( Exception $e ) {
                    $this->add_error( $e->getMessage() );
                }
            }
        }
        return update_option( $this->get_instance_option_key(), apply_filters( 'woocommerce_shipping_' . $this->id . '_instance_settings_values', $this->instance_settings, $this ), 'yes' );
    }

    public function loadAddresses()
    {
        $controlCall = 0;
        if (get_transient('addresses_loaded')) {
            $controlCall = 1;
        }
        if (!$controlCall && isset($_GET['page']) && $_GET['page'] === 'wc-settings' && isset($_GET['tab']) && $_GET['tab'] === 'shipping') {
            set_transient('addresses_loaded', true, 10);
            $tipax_addresses = get_transient('tipax_addresses');
            $data = [];
    
            if ($this->accessToken) {
                $response = $this->request->apiRequest('Addresses', $data, "GET", $this->accessToken);
            }
            $allAddresses = [];
            if (isset($response[0])) {
                foreach ($response as $address) {
                    if ($address->isDefault) {
                        $tipax_addresses = $address;
                    }
                    $allAddresses[$address->id]['originId'] = $address->addressesId;
                    $allAddresses[$address->id]['fullAddress'] = $address;
                }
            }
    
            set_transient('tipax_addresses', $tipax_addresses, 12 * HOUR_IN_SECONDS);
            
            
                        
            $addresses_objects = $tipax_addresses;
            $dataAddr = [];
            if( get_option('shipping_address') !== false ){
                $originIdToCheck = get_option('shipping_address');
            } else {
                $originIdToCheck = $tipax_addresses->addressesId;
            }
            if (count($allAddresses) > 0) {
                $exists = false;
                
                // Loop through each object in the array
                foreach ($allAddresses as $object) {
                // Check if the 'originId' matches the one we're looking for
                    if ($object['originId'] == $originIdToCheck) {
                        $exists = true;
                        break; // Break the loop once the ID is found
                    }
                }
                if(!$exists){
                    $originAddressId = $allAddresses[$addresses_objects->id]['originId'];
                     if( get_option('shipping_address') !== false ){
                         update_option('shipping_address', $originAddressId, '', 'yes');
                    }else{
                         add_option('shipping_address', $originAddressId, '', 'yes');
                    }
                    delete_transient( 'tipax_addresses');
                    set_transient( 'tipax_addresses', $allAddresses[$addresses_objects->id]['fullAddress'],12 * HOUR_IN_SECONDS);
                }
            }
        }
        $tipax_addresses = get_transient( 'tipax_addresses' );

        //after one day just update the addresses
        //if there is no cache
        //if is not array it means UnauthorizedException happened
        if ( false === $tipax_addresses || !isset($tipax_addresses->address)) {
            $data = [];
            if ($this->accessToken) {
                $response = $this->request->apiRequest('Addresses', $data, "GET", $this->accessToken);
            }

            if (isset($response[0])) {
                foreach($response as $address){
                    if($address->isDefault){
                        $tipax_addresses = $address;
                    }
                }
            }

            set_transient( 'tipax_addresses', $tipax_addresses,12 * HOUR_IN_SECONDS);
        }
        return $tipax_addresses;
    }
    public function loadCities()
    {
        $tipax_cities = get_transient( 'tipax_cities' );
        if ( false === $tipax_cities ||  count($tipax_cities) == 0) {
            $tipax_cities = [];
            $responses = [];
            $data = [];
            if ($this->accessToken) {
                $responses = $this->request->apiRequest('Cities/plusstate', $data, "GET", $this->accessToken);
            }
            if(isset($responses->errors)){
                return false;
            }
            if(isset($responses) && is_array($responses)){
                foreach($responses as $response){
                    $tipax_cities[$response->id] = $response->title . ' - ' . $response->stateTitle;
                }
            }
            set_transient( 'tipax_cities', $tipax_cities,12 * HOUR_IN_SECONDS);
        }
        return $tipax_cities;
    }

    public function loadCustomerInfo()
    {
        $data = [];
        if ($this->accessToken) {
            $response = $this->request->apiRequest('Customers', $data, "GET", $this->accessToken);
        }
        return $response;
    }
}
