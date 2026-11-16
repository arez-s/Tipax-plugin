<?php
if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/includes/class-tipax-settings.php';
require_once __DIR__ . '/includes/class-tipax-loader.php';
require_once __DIR__ . '/modules/hub/class-tipax-hub-client.php';
require_once __DIR__ . '/modules/hub/class-tipax-hub-metrics.php';
require_once __DIR__ . '/modules/hub/class-tipax-hub-assets.php';
// Direct-distribution updates are omitted from the WordPress.org package.
if (is_file(__DIR__ . '/modules/hub/class-tipax-hub-updater.php')) {
    require_once __DIR__ . '/modules/hub/class-tipax-hub-updater.php';
}
require_once __DIR__ . '/includes/class-tipax-plugin.php';
