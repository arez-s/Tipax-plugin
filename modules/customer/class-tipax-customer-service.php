<?php
if (!defined('ABSPATH')) {
    exit;
}

if (class_exists('Tipax_Customer_Service')) {
    return;
}

class Tipax_Customer_Service
{
    private $request;
    private $auth;

    public function __construct($request = null, $auth = null)
    {
        $this->request = $request ?: new Request();
        $this->auth = $auth ?: new Tipax_Auth_Service($this->request);
    }

    private function ensure_access_token()
    {
        $accessToken = $this->auth->get_access_token();
        if (!$accessToken) {
            $this->auth->check_api_key(true);
            $accessToken = $this->auth->get_access_token();
        }

        return $accessToken;
    }

    public function get_packing_prices($force_refresh = false)
    {
        $cache_key = 'tipax_packing_prices';
        if (!$force_refresh) {
            $cached = get_transient($cache_key);
            if ($cached !== false) {
                return $cached;
            }
        }

        $accessToken = $this->ensure_access_token();
        if (!$accessToken) {
            return null;
        }

        $response = $this->request->apiRequest('PackingPrices', [], 'GET', $accessToken);

        $result = null;
        if (is_object($response) && isset($response->result) && is_array($response->result)) {
            $result = $response->result;
        } elseif (is_array($response)) {
            $result = $response;
        }

        if (is_array($result)) {
            set_transient($cache_key, $result, 12 * HOUR_IN_SECONDS);
        }

        return $result;
    }

    public function get_wallet_remaining_asset()
    {
        $accessToken = $this->ensure_access_token();
        if (!$accessToken) {
            return null;
        }

        return $this->request->apiRequest('Customers/Wallet', [], 'GET', $accessToken);
    }

    public function get_customer_info()
    {
        $accessToken = $this->ensure_access_token();
        if (!$accessToken) {
            return null;
        }

        return $this->request->apiRequest('Customers', [], 'GET', $accessToken);
    }

    public function recharge_wallet($amount)
    {
        if (!is_numeric($amount) || $amount <= 1000) {
            return false;
        }

        $accessToken = $this->ensure_access_token();
        if (!$accessToken) {
            return false;
        }

        $data = new stdClass;
        $data->amount = $amount;
        $data->frontCallBackUrl = admin_url() . 'admin.php?page=tipax-shipping-method';

        return $this->request->apiRequest('Customers/RechargeWallet', $data, 'POST', $accessToken);
    }
}


if (!class_exists('Tipax_Customer_Module')) {
    class_alias('Tipax_Customer_Service', 'Tipax_Customer_Module');
}
