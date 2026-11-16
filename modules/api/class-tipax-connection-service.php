<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_Tipax_V2
{
    private $settings;
    private $request;
    private $auth;
    private $addresses;
    private $accessToken;

    public function __construct()
    {
        $this->request = new Request();
        $this->settings = get_option('woocommerce_tipax_shipping_method_settings', []);
        if (!is_array($this->settings)) {
            $this->settings = [];
        }

        $this->auth = new Tipax_Auth_Service($this->request, $this->settings);
        $this->addresses = new Tipax_Address_Service($this->request, $this->settings, $this->auth);
        $this->accessToken = $this->auth->get_access_token();
    }

    public function checkApiKey($refreshToken = false)
    {
        $response_message = $this->auth->check_api_key($refreshToken);
        $this->accessToken = $this->auth->get_access_token();

        return $response_message;
    }

    public function loadAddresses()
    {
        return $this->addresses->load_default_address([
            'use_control_transient' => true,
            'set_control_transient' => false,
            'require_shipping_settings_page' => false,
            'filter_by_user' => true,
            'update_presence_option' => true,
        ]);
    }
}

