<?php
if (!defined('ABSPATH')) {
    exit;
}

if (class_exists('Tipax_Auth_Service')) {
    return;
}

class Tipax_Auth_Service
{
    private $request;
    private $settings;
    private $token_store;

    public function __construct($request = null, $settings = null)
    {
        $this->request = $request ?: new Request();
        $this->token_store = new Tipax_Token_Store();
        $this->settings = is_array($settings) ? $settings : get_option(Tipax_Settings::SETTINGS_OPTION, []);
        if (!is_array($this->settings)) {
            $this->settings = [];
        }
        
    }

    public function get_settings()
    {
        return $this->settings;
    }

    public function get_access_token()
    {
        return $this->token_store->get();
    }

    public function set_access_token($accessToken)
    {
        return $this->token_store->set($accessToken);
    }

    public function clear_access_token()
    {
        $this->token_store->clear();
    }

    public function credentials()
    {
        return tipax_get_api_credentials($this->settings);
    }

    public function has_credentials()
    {
        return (
            isset($this->settings['apiKey'], $this->settings['username'], $this->settings['password']) &&
            $this->settings['apiKey'] !== '' &&
            $this->settings['username'] !== '' &&
            $this->settings['password'] !== ''
        );
    }

    public function request_token()
    {
        $response = $this->request->apiRequest('Account/token', $this->credentials(), 'POST');
        if (is_object($response) && !empty($response->accessToken)) {
            return [
                'success' => true,
                'token' => $this->set_access_token($response->accessToken),
                'response' => $response,
                'message' => __('Connection successful. Token saved.', 'delivery-service-tipax-shipping'),
            ];
        }

        return [
            'success' => false,
            'token' => null,
            'response' => $response,
            'message' => (is_object($response) && !empty($response->message)) ? $response->message : __('Failed to retrieve access token.', 'delivery-service-tipax-shipping'),
        ];
    }

    public function check_api_key($refreshToken = false)
    {
        $response_message = '';

        if (!$this->has_credentials()) {
            return __('Missing credentials: Please check your username, password, and API key.', 'delivery-service-tipax-shipping');
        }

        if ($refreshToken == true) {
            $token = $this->request_token();
            if ($token['success']) {
                return __('Connection successful. Token saved.', 'delivery-service-tipax-shipping');
            }
            return $token['message'];
        }

        if ($this->get_access_token() != null) {
            return __('Connection successful. Access token already available.', 'delivery-service-tipax-shipping');
        }

        $response = $this->request->apiRequest('Account/token', $this->credentials(), 'POST');
        if (!is_object($response) || !isset($response->errors)) {
            $accessToken = (is_object($response) && isset($response->accessToken)) ? $response->accessToken : null;

            if ($accessToken != null) {
                $this->set_access_token($accessToken);
                $response_message = __('Connection successful. Token refreshed.', 'delivery-service-tipax-shipping');
            } else {
                $response_message = __('Failed to retrieve access token.', 'delivery-service-tipax-shipping');
            }
        } else {
            $response_message = isset($response->message) ? $response->message : __('Unknown error occurred.', 'delivery-service-tipax-shipping');
        }

        return $response_message;
    }
}



if (!class_exists('Tipax_Auth_Module')) {
    class_alias('Tipax_Auth_Service', 'Tipax_Auth_Module');
}
