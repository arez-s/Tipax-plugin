<?php
if (!defined('ABSPATH')) {
    exit;
}

class Tipax_Order_Api
{
    private $client;

    public function __construct($client = null)
    {
        $this->client = $client ?: new Request();
    }

    public function submit($apiPath, stdClass $data, $accessToken, array $requestOptions = [])
    {
        return $this->client->apiRequest($apiPath, $data, 'POST', $accessToken, $requestOptions);
    }
}
