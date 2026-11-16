<?php
if (!defined('ABSPATH')) {
    exit;
}

class Tipax_Order_Payload_Builder
{
    public function from_packages($packages)
    {
        $data = new stdClass();
        $data->packages = $packages;
        return $data;
    }
}
