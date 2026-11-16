<?php
if (!defined('ABSPATH')) {
    exit;
}

class Tipax_Loader
{
    public function load()
    {
        $root = TSM_DIR;

        require_once $root . 'modules/api/exceptions/class-tipax-unauthorized-exception.php';
        require_once $root . 'modules/api/exceptions/class-tipax-refresh-token-exception.php';
        require_once $root . 'includes/class-tipax-message.php';
        require_once $root . 'includes/class-tipax-logger.php';
        require_once $root . 'includes/tipax-city-matching-functions.php';
        require_once $root . 'includes/tipax-settlement-functions.php';
        require_once $root . 'includes/tipax-package-functions.php';
        require_once $root . 'includes/tipax-same-day-functions.php';
        require_once $root . 'includes/tipax-same-day-location-functions.php';
        require_once $root . 'includes/tipax-order-location-functions.php';
        require_once $root . 'includes/tipax-functions.php';

        require_once $root . 'modules/api/class-tipax-api-client.php';
        require_once $root . 'modules/auth/class-tipax-token-store.php';
        require_once $root . 'modules/auth/class-tipax-auth-service.php';
        require_once $root . 'modules/cities/class-tipax-city-service.php';
        require_once $root . 'modules/cities/class-tipax-city-mapping-service.php';
        require_once $root . 'modules/cities/class-tipax-city-admin.php';
        require_once $root . 'modules/addresses/class-tipax-address-service.php';
        require_once $root . 'modules/customer/class-tipax-customer-service.php';
        require_once $root . 'modules/financial/class-tipax-financial-service.php';
        require_once $root . 'modules/financial/class-tipax-financial-admin.php';
        require_once $root . 'modules/ticketing/class-tipax-ticketing-service.php';
        require_once $root . 'modules/ticketing/class-tipax-ticketing-admin.php';
        require_once $root . 'modules/api/class-tipax-pricing-api.php';
        require_once $root . 'modules/api/class-tipax-order-api.php';
        require_once $root . 'modules/pricing/class-tipax-package-builder.php';
        require_once $root . 'modules/pricing/class-tipax-pricing-service.php';
        require_once $root . 'modules/orders/class-tipax-order-query.php';
        require_once $root . 'modules/orders/class-tipax-order-payload-builder.php';
        require_once $root . 'modules/orders/class-tipax-order-service.php';
        require_once $root . 'modules/orders/class-tipax-cancellation-service.php';
        require_once $root . 'modules/tracking/class-tipax-tracking-service.php';
        require_once $root . 'modules/tracking/class-tipax-tracking-hooks.php';
        require_once $root . 'modules/labels/class-tipax-label-manager.php';
        require_once $root . 'modules/admin/class-tipax-report-log-service.php';
        require_once $root . 'modules/api/class-tipax-connection-service.php';
        require_once $root . 'modules/shipping/class-tipax-shipping-hooks.php';
        require_once $root . 'modules/shipping/class-tipax-same-day-map.php';
        require_once $root . 'modules/admin/class-tipax-admin.php';
    }
}
