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
    private $auth;
    private $addresses;
    private $pricing;

    public function __construct($instance_id = 0)
    {
        $this->id = 'tipax_shipping_method'; // Id for your shipping method. Should be uunique.
        $this->instance_id = absint($instance_id);
        $this->domain = 'tipax_shipping_method';
        $this->title = __('Tipax Shipping Method', 'delivery-service-tipax-shipping'); // Title shown in FrontEnd
        $this->method_title = __('Tipax Shipping Method', 'delivery-service-tipax-shipping'); // Title shown in admin
        $this->method_description = __('Ship your orders with tipax', 'delivery-service-tipax-shipping'); // Description shown in admin

        $this->supports = array(
            'settings',
            'shipping-zones',
            'instance-settings',
            'instance-settings-modal'
        );

        $this->enabled = $this->get_option('enabled');

        $this->request = new Request();
        $this->pricing = new Tipax_Pricing_Service($this->request);

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
        $this->auth = new Tipax_Auth_Service($this->request, $this->settings);
        $this->addresses = new Tipax_Address_Service($this->request, $this->settings, $this->auth);
        $this->accessToken = $this->auth->get_access_token();

        $plugin_settings = get_option('woocommerce_tipax_shipping_method_settings', []);
        $collection_time = $plugin_settings['collection_time'] ?? 1;
        $this->collection_time = $collection_time;
        $this->address_method = $this->get_option('address_method');
        $this->lat_lon = $this->get_option('lat_lon');
        $this->checkout_method = $this->get_option('checkout_method');
        $this->shipping_address = get_option('tipax_shipping_address');
        $this->woocommerce_currency = get_woocommerce_currency();

        $this->init();
    }

    /**
     * Is this method enabled?
     */
    public function is_enabled()
    {
        if ($this->get_option('enabled') == 'yes') {
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
    public function is_available($package): bool
    {
        if (class_exists('Tipax_Hub_Updater', false) && Tipax_Hub_Updater::requires_update()) { return false; }
        $available = $this->is_enabled();

        // Country availability (legacy, for non-zone based methods).
        if (! $this->instance_id && $available) {
            $countries = is_array($this->countries) ? $this->countries : array();

            switch ($this->availability) {
                case 'specific':
                case 'including':
                    $available = in_array($package['destination']['country'], array_intersect($countries, array_keys(WC()->countries->get_shipping_countries())));
                    break;
                case 'excluding':
                    $available = in_array($package['destination']['country'], array_diff(array_keys(WC()->countries->get_shipping_countries()), $countries));
                    break;
                default:
                    $available = in_array($package['destination']['country'], array_keys(WC()->countries->get_shipping_countries()));
                    break;
            }
        }
        //package must have value!
        if (empty($package)) {
            $available = false;
            tipax_log("empty( package ): " . (int)$available);
        }

        //if country is not iran is not available
        if ($package['destination']['country'] != 'IR') {
            $available = false;
            tipax_log("package['destination']['country'] != 'IR': " . (int)$available);
        }

        if ($available) {
            $destination_state = $package['destination']['state'] ?? '';
            $destination_states = WC()->countries->get_states('IR');
            $destination_state_title = $destination_states[$destination_state] ?? $destination_state;
            $destination_city_id = get_city_code($package['destination']['city'] ?? '', $destination_state_title);

            if ($destination_city_id !== null && !tipax_is_city_shipping_enabled($destination_city_id)) {
                $available = false;
                tipax_log('Tipax shipping disabled for destination cityId: ' . (int) $destination_city_id);
            }
        }

        //if accessToken not saved before is not available

        if (is_null($this->accessToken)) {
            $available = false;
            tipax_log("is_null(this->accessToken ): " . (int)$available);
        }

        $plugin_settings = get_option('woocommerce_tipax_shipping_method_settings', []);
        //if name is not confirmed is not available
        $senderOriginName = $plugin_settings['name'] ?? '';
        if (empty($senderOriginName)) {
            $available = false;
            tipax_log("is_null(senderOriginName ): " . (int)$available);
        }

        //if phone is not confirmed is not available
        $senderOriginPhone = $plugin_settings['phone'] ?? '';
        if (empty($senderOriginPhone)) {
            $available = false;
            tipax_log("is_null(senderOriginPhone ): " . (int)$available);
        }

        //if lat_lon is not selected is not available
        // $originLatLon = $this->get_option("lat_lon");
        // $lat = null;
        // $lon = null;
        // if($originLatLon != null){
        //     $lat_lon = explode(",",$originLatLon);
        //     $lat = $lat_lon[0];
        //     $lon = $lat_lon[1];
        // }

        // //the lat & lon is required
        // if(is_null( $originLatLon ) || $lat == null || $lon == null ){
        //     $available = false;
        //     tipax_log("is_null( originLatLon ): ".(int)$available);
        // }

        //check origin postalcode and validate it
        // $originPostalCode = get_option( 'woocommerce_store_postcode' );
        // $validatePostalCode = $originPostalCode ? postalcodeValidate($originPostalCode) : true;
        // if ($validatePostalCode == null ) {
        //     $available = false;
        //     tipax_log("is_null( validatePostalCode ): ". (int)$available . " originPostalCode: ".$originPostalCode);
        // }

        //validate and check destination postalcode
        // $destinationPostalCode = $package["destination"]["postcode"];
        // $validateDestinationPostalCode = $destinationPostalCode ? postalcodeValidate($destinationPostalCode) : true;
        // if ($validateDestinationPostalCode == null) {
        //     $available = false;
        //     tipax_log("is_null( validateDestinationPostalCode ): " . (int)$available . " DestinationPostalCode: " . $destinationPostalCode);
        // }

        if ($available) {
            tipax_log("No problem about available variable: " . (int)$available);
        }

        return apply_filters('woocommerce_shipping_' . $this->id . '_is_available', $available, $package, $this);
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
        $this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings
        $this->init_instance_form_fields();
        $this->init_settings(); // This is part of the settings API. Loads settings you previously init.

        // Save settings in admin if you have any defined
        add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
    }

    public function init_form_fields()
    {
        $this->checkApiKey();
        $this->form_fields = array(
            'enabled' => array(
                'title'   => __('Enable/Disable', 'delivery-service-tipax-shipping'),
                'type'    => 'checkbox',
                'label'   => __('Enable this shipping method', 'delivery-service-tipax-shipping'),
                'default' => 'yes',
            ),
            'cities' => array(
                'title'   => __('Only Tipax cities?', 'delivery-service-tipax-shipping'),
                'type'    => 'checkbox',
                'label'   => __('Do you want Tipax fills your cities?', 'delivery-service-tipax-shipping'),
                'default' => 'no',
            ),
            'username' => array(
                'title' => __('Username', 'delivery-service-tipax-shipping'),
                'type' => 'text',
                'required' => true,
                'placeholder' => __('Enter Your Tipax Username', 'delivery-service-tipax-shipping'),
                'description' => __('Username of your tipax account', 'delivery-service-tipax-shipping'),
                'desc_tip' => true
            ),
            'password' => array(
                'title' => __('Password', 'delivery-service-tipax-shipping'),
                'type' => 'password',
                'required' => true,
                'placeholder' => __('Enter Your Tipax Password', 'delivery-service-tipax-shipping'),
                'description' => __('password of your tipax account', 'delivery-service-tipax-shipping'),
                'desc_tip' => true
            ),
            'apiKey' => array(
                'title' => __('API KEY', 'delivery-service-tipax-shipping'),
                'type' => 'text',
                'required' => true,
                'placeholder' => __('Enter Your Tipax Api Key which is located in profile page', 'delivery-service-tipax-shipping'),
                'description' => __('this Tipax Api Key is located in profile page', 'delivery-service-tipax-shipping'),
                'desc_tip' => true
            ),
            'hub_usage_metrics' => array(
                'title' => '',
                'type' => 'checkbox',
                'label' => 'اجازه استفاده تیپاکس برای ردیابی',
                'description' => 'جهت تجربهٔ بهتر و ارائه تخفیفات اختصاصی',
                'default' => 'no',
            ),
        );
    }

    public function init_instance_form_fields()
    {
        $this->instance_form_fields = array(
            'tipax_settings_redirect' => array(
                'title'       => __('تنظیمات تیپاکس', 'delivery-service-tipax-shipping'),
                'type'        => 'tipax_settings_redirect',
                'description' => __('تنظیمات حمل‌ونقل تیپاکس از صفحه اختصاصی تنظیمات افزونه مدیریت می‌شود.', 'delivery-service-tipax-shipping'),
            ),
        );
    }

    public function generate_tipax_settings_redirect_html($key, $data)
    {
        $field_key = $this->get_field_key($key);
        $defaults  = array(
            'title'       => '',
            'description' => '',
        );
        $data = wp_parse_args($data, $defaults);

        ob_start();
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr($field_key); ?>"><?php echo esc_html($data['title']); ?></label>
            </th>
            <td class="forminp">
                <p style="margin-top:0;"><?php echo esc_html($data['description']); ?></p>
                <a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=tipax-api-config')); ?>">
                    <?php echo esc_html__('مدیریت تنظیمات افزونه تیپاکس', 'delivery-service-tipax-shipping'); ?>
                </a>
            </td>
        </tr>
        <?php
        return ob_get_clean();
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
    if (class_exists('Tipax_Hub_Updater', false) && Tipax_Hub_Updater::requires_update()) {
        $this->rates = [];
        return;
    }
    $rates = $this->pricing->calculate_shipping_rates($package, [
        'request' => $this->request,
        'addresses' => $this->addresses,
        'accessToken' => $this->accessToken,
        'woocommerce_currency' => $this->woocommerce_currency,
        'method_id' => $this->id,
    ]);

    if ($rates === false) {
        return false;
    }

    foreach ($rates as $rate) {
        $this->add_rate($rate);
    }

    return true;
}



    

    

    public function checkApiKey($refreshToken = false)
    {
        $message = $this->auth->check_api_key($refreshToken);
        $this->accessToken = $this->auth->get_access_token();

        return $message;
    }

    /**
     * Processes and saves global shipping method options in the admin area.
     *
     * This method is usually attached to woocommerce_update_options_x hooks.
     *
     * @since 2.6.0
     * @return bool was anything saved?
     */
    public function process_admin_options()
    {
        if ($this->instance_id == 0 && $this->id == "tipax_shipping_method") {
            delete_option("woocommerce_tipax_shipping_method_accesstoken");
            tipax_log("process_admin_options: woocommerce_tipax_shipping_method_accesstoken Deleted");
            delete_transient('tipax_addresses');
            tipax_log("process_admin_options: tipax_addresses transient Deleted");
        }

        if (! $this->instance_id) {
            return parent::process_admin_options();
        }

        return false;
    }

    public function loadAddresses()
    {
        return $this->addresses->load_default_address([
            'use_control_transient' => true,
            'set_control_transient' => true,
            'require_shipping_settings_page' => true,
            'filter_by_user' => false,
            'update_presence_option' => false,
            'missing_origin_uses_current' => true,
        ]);
    }
    public function loadCities()
    {
        return $this->addresses->load_cities($this->accessToken);
    }

    public function loadCustomerInfo()
    {
        $customer_module = new Tipax_Customer_Service($this->request, $this->auth);
        return $customer_module->get_customer_info();
    }
}
