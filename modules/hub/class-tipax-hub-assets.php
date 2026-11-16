<?php
if (!defined('ABSPATH')) {
    exit;
}

class Tipax_Hub_Assets
{
    const SLOTS = ['dashboard_hero', 'settings_banner', 'support_card', 'tutorial_cover', 'notice_banner'];
    const TYPES = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    const MAX_BYTES = 5242880;

    public static function sync(Tipax_Hub_Client $client)
    {
        if (!Tipax_Hub_Client::enabled('media')) {
            return true;
        }
        $etag = (string) get_option('tipax_hub_config_etag', '');
        $headers = $etag !== '' ? ['If-None-Match' => $etag] : [];
        $response = $client->request('GET', '/api/v1/plugin/config', [], false, true, $headers);
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 304) {
            // Re-fetch the full manifest if a hosting cleanup removed a local file.
            foreach ((array) get_option('tipax_hub_asset_cache', []) as $asset) {
                if (!is_array($asset) || !self::cached_file_exists($asset)) {
                    delete_option('tipax_hub_config_etag');
                    return false;
                }
            }
            update_option('tipax_hub_last_config', time(), false);
            return true;
        }
        $data = Tipax_Hub_Client::response_data($response);
        if (!$data || (int) ($data['schema_version'] ?? 0) !== 1 || !is_array($data['assets'] ?? null)) {
            return false;
        }
        // Removal must win over a stale local fallback, even if downloading a
        // replacement fails or the upload directory is temporarily unwritable.
        self::forget_unpublished($data['assets']);
        if ($data['assets'] !== []) {
            $uploads = wp_upload_dir();
            if (!empty($uploads['error'])) {
                return false;
            }
            $directory = trailingslashit($uploads['basedir']) . 'tipax-hub';
            if (!wp_mkdir_p($directory)) {
                return false;
            }
        }
        $cache = [];
        $failed = false;
        // One published asset per supported slot keeps storage and remote work bounded.
        foreach ($data['assets'] as $asset) {
            if (!is_array($asset)) {
                $failed = true;
                continue;
            }
            $slot = (string) ($asset['slot'] ?? '');
            if (!in_array($slot, self::SLOTS, true) || isset($cache[$slot])) {
                continue;
            }
            $cached = self::cache_asset($asset, $directory, $uploads['baseurl']);
            if (!$cached) {
                $failed = true;
            } else {
                $cache[$slot] = $cached;
            }
        }
        if ($failed || !Tipax_Hub_Client::enabled('media')) {
            return false;
        }
        update_option('tipax_hub_asset_cache', $cache, false);
        $release = $data['release'] ?? [];
        $version = is_array($release) ? (string) ($release['version'] ?? '') : '';
        // The hub only supplies informational metadata. Never register an update
        // filter, download an executable package, or load remote JS/CSS/PHP.
        update_option('tipax_hub_remote_config', [
            'release_version' => preg_match('/^\d+\.\d+(?:\.\d+)?(?:[-+][a-zA-Z0-9.-]+)?$/', $version) ? $version : '',
        ], false);
        $new_etag = wp_remote_retrieve_header($response, 'etag');
        if (is_string($new_etag) && strlen($new_etag) <= 100 && !preg_match('/[\r\n]/', $new_etag)) {
            update_option('tipax_hub_config_etag', $new_etag, false);
        }
        update_option('tipax_hub_last_config', time(), false);
        self::prune($cache);
        return true;
    }

    private static function forget_unpublished(array $published)
    {
        $sources = [];
        foreach ($published as $asset) {
            if (is_array($asset) && isset($asset['slot'], $asset['url'])) {
                $sources[(string) $asset['slot']][] = (string) $asset['url'];
            }
        }
        $cached = get_option('tipax_hub_asset_cache', []);
        if (!is_array($cached)) {
            $cached = [];
        }
        $remaining = [];
        foreach ($cached as $slot => $asset) {
            if (is_array($asset) && in_array((string) ($asset['remote_url'] ?? ''), $sources[$slot] ?? [], true)) {
                $remaining[$slot] = $asset;
            }
        }
        if ($remaining !== $cached) {
            update_option('tipax_hub_asset_cache', $remaining, false);
            self::prune($remaining);
        }
    }

    public static function forget_from_browser()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(null, 403);
        }
        check_ajax_referer('tipax_hub_media', 'nonce');
        $sources = isset($_POST['sources']) && is_array($_POST['sources']) ? wp_unslash($_POST['sources']) : [];
        wp_send_json_success(['removed' => self::forget_sources(array_slice($sources, 0, 10))]);
    }

    public static function forget_sources(array $sources)
    {
        $valid = [];
        foreach ($sources as $source) {
            if (is_string($source) && preg_match('~^https://tipaxplugin\.ir/uploads/[a-f0-9]{32}\.(?:png|jpg|webp|gif)$~', $source)) {
                $valid[] = $source;
            }
        }
        $cached = get_option('tipax_hub_asset_cache', []);
        if (!is_array($cached) || $valid === []) {
            return 0;
        }
        $removed = 0;
        foreach ($cached as $slot => $asset) {
            if (is_array($asset) && in_array((string) ($asset['remote_url'] ?? ''), $valid, true)) {
                unset($cached[$slot]);
                $removed++;
            }
        }
        if ($removed > 0) {
            update_option('tipax_hub_asset_cache', $cached, false);
            self::prune($cached);
            delete_option('tipax_hub_config_etag');
            delete_option('tipax_hub_last_config');
        }
        return $removed;
    }

    private static function cache_asset(array $asset, $directory, $base_url)
    {
        $mime = (string) ($asset['mime_type'] ?? '');
        $sha = (string) ($asset['sha256'] ?? '');
        $bytes = (int) ($asset['bytes'] ?? 0);
        $width = (int) ($asset['width'] ?? 0);
        $height = (int) ($asset['height'] ?? 0);
        $url = (string) ($asset['url'] ?? '');
        if (!isset(self::TYPES[$mime]) || !preg_match('/^[a-f0-9]{64}$/', $sha)
            || $bytes < 1 || $bytes > self::MAX_BYTES || $width < 1 || $height < 1 || $width > 5000 || $height > 5000
            || !preg_match('~^https://tipaxplugin\.ir/uploads/[a-f0-9]{32}\.(?:png|jpg|webp|gif)$~', $url)) {
            return false;
        }
        $file_name = $sha . '.' . self::TYPES[$mime];
        $destination = trailingslashit($directory) . $file_name;
        if (!is_file($destination) || !hash_equals($sha, (string) hash_file('sha256', $destination))) {
            if (!Tipax_Hub_Client::enabled('media')) {
                return false;
            }
            $response = wp_safe_remote_get($url, [
                'timeout' => 8, 'redirection' => 0, 'sslverify' => true,
                'limit_response_size' => self::MAX_BYTES + 1,
                'user-agent' => 'Tipax-Plugin/' . TSM_VERSION,
            ]);
            if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
                return false;
            }
            $body = wp_remote_retrieve_body($response);
            $dimensions = @getimagesizefromstring($body);
            if (strlen($body) !== $bytes || !hash_equals($sha, hash('sha256', $body))
                || !is_array($dimensions) || ($dimensions['mime'] ?? '') !== $mime
                || (int) $dimensions[0] !== $width || (int) $dimensions[1] !== $height) {
                return false;
            }
            if (file_put_contents($destination, $body, LOCK_EX) !== $bytes) {
                return false;
            }
            if (defined('FS_CHMOD_FILE')) {
                chmod($destination, FS_CHMOD_FILE);
            }
        }
        $target = esc_url_raw((string) ($asset['target_url'] ?? ''), ['https']);
        return [
            'file_name' => $file_name,
            'remote_url' => $url,
            'url' => trailingslashit($base_url) . 'tipax-hub/' . $file_name,
            'alt' => sanitize_text_field($asset['alt'] ?? ''),
            'target_url' => $target, 'width' => $width, 'height' => $height,
        ];
    }

    private static function cached_file_exists(array $asset)
    {
        $name = (string) ($asset['file_name'] ?? '');
        if (!preg_match('/^[a-f0-9]{64}\.(png|jpg|webp|gif)$/', $name)) {
            return false;
        }
        $uploads = wp_upload_dir();
        return empty($uploads['error']) && is_file(trailingslashit($uploads['basedir']) . 'tipax-hub/' . $name);
    }

    public static function get($slot)
    {
        if (!Tipax_Hub_Client::enabled('media')) {
            return null;
        }
        $cache = get_option('tipax_hub_asset_cache', []);
        $asset = is_array($cache) ? ($cache[$slot] ?? null) : null;
        return is_array($asset)
            && preg_match('~^https://tipaxplugin\.ir/uploads/[a-f0-9]{32}\.(?:png|jpg|webp|gif)$~', (string) ($asset['remote_url'] ?? ''))
            && self::cached_file_exists($asset) ? $asset : null;
    }

    public static function render($slot)
    {
        if (!is_admin() || !current_user_can('manage_options')) {
            return;
        }
        $asset = self::get($slot);
        if (!$asset) {
            return;
        }
        echo '<div class="tipax-hub-banner" data-tipax-hub-banner hidden>';
        if ($asset['target_url'] !== '') {
            echo '<a href="' . esc_url($asset['target_url']) . '" target="_blank" rel="noopener noreferrer">';
        }
        echo '<img src="' . esc_url($asset['remote_url']) . '" alt="' . esc_attr($asset['alt']) . '" width="' . (int) $asset['width'] . '" height="' . (int) $asset['height'] . '" loading="lazy" referrerpolicy="no-referrer">';
        if ($asset['target_url'] !== '') {
            echo '</a>';
        }
        echo '</div>';
    }

    private static function prune(array $cache)
    {
        $uploads = wp_upload_dir();
        if (!empty($uploads['error'])) {
            return;
        }
        $directory = trailingslashit($uploads['basedir']) . 'tipax-hub';
        $keep = array_column($cache, 'file_name');
        foreach ((array) glob($directory . '/*') as $file) {
            $name = basename($file);
            // Only remove our own content-addressed raster files, never recurse.
            if (preg_match('/^[a-f0-9]{64}\.(png|jpg|webp|gif)$/', $name) && !in_array($name, $keep, true) && is_file($file)) {
                wp_delete_file($file);
            }
        }
    }

    public static function clear()
    {
        self::prune([]);
        foreach (['tipax_hub_asset_cache', 'tipax_hub_config_etag', 'tipax_hub_last_config', 'tipax_hub_remote_config'] as $option) {
            delete_option($option);
        }
    }
}
