<?php
if (!defined('ABSPATH')) {
    exit;
}

class Tipax_Pricing_Api
{
    private $client;

    public function __construct($client = null)
    {
        $this->client = $client ?: new Request();
    }

    public function price_with_origin_address_id($data, $accessToken, $timeout = null)
    {
        if (class_exists('Tipax_Hub_Updater', false) && Tipax_Hub_Updater::requires_update()) {
            return Tipax_Hub_Updater::blocked_response();
        }
        $requestOptions = [
            'return_transport_error' => true,
        ];

        if ($timeout !== null) {
            $requestOptions['timeout'] = $timeout;
        }

        if (class_exists('Tipax_Hub_Metrics')) { Tipax_Hub_Metrics::increment('quotes'); }
        return $this->client->apiRequest('Pricing/WithOriginAddressId', $data, 'POST', $accessToken, $requestOptions);
    }
}
