<?php
if (!function_exists('tipax_is_logging_enabled')) {
    function tipax_is_logging_enabled($settings = null) {
        if (!function_exists('get_option')) {
            return false;
        }

        if (!is_array($settings)) {
            $option_name = class_exists('Tipax_Settings')
                ? Tipax_Settings::SETTINGS_OPTION
                : 'woocommerce_tipax_shipping_method_settings';
            $settings = get_option($option_name, []);
        }

        if (!is_array($settings)) {
            return false;
        }

        return isset($settings['logging_enabled']) && $settings['logging_enabled'] === 'yes';
    }
}

class Tipax_Logger {
    private static $instance = null;
    private $log_file;
    private $log_dir;
    private $max_file_size = 1048576; // 1MB in bytes
    
    private function __construct() {
        $this->log_dir = defined('TSM_DIR') ? TSM_DIR . 'logs/' : dirname(__DIR__) . '/logs/';
        $this->log_file = $this->log_dir . 'tipax_shipping.log';
    }
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function log($message, $level = 'INFO') {
        if (!tipax_is_logging_enabled()) {
            return false;
        }

        $this->ensure_log_directory();

        $timestamp = current_time('mysql');
        $log_entry = "[{$timestamp}] [{$level}] {$message}\n";
        
        // Rotate log file if it's too big
        if (file_exists($this->log_file)) {
            $filesize = filesize($this->log_file);
            if ($filesize > $this->max_file_size) {
                $this->rotate_logs();
            }
        }
        
        return (bool) file_put_contents($this->log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }

    private function ensure_log_directory() {
        if (file_exists($this->log_dir)) {
            return true;
        }

        if (function_exists('wp_mkdir_p')) {
            return wp_mkdir_p($this->log_dir);
        }

        return mkdir($this->log_dir, 0755, true);
    }
    
    private function rotate_logs() {
        $backup_file = $this->log_dir . 'tipax_shipping_' . date('Y-m-d_H-i-s') . '.log';
        rename($this->log_file, $backup_file);
    }
    
    public function get_logs($lines = 100) {
        if (!file_exists($this->log_file)) {
            return array();
        }
        
        $file = file($this->log_file);
        $lines = min($lines, count($file));
        return array_slice($file, -$lines);
    }
    
    public function clear_logs() {
        if (!tipax_is_logging_enabled()) {
            return false;
        }

        if (file_exists($this->log_file)) {
            file_put_contents($this->log_file, '');
        }
        return true;
    }
    
    public function get_log_file_path() {
        return $this->log_file;
    }
}

// Helper function to replace error_log()
function tipax_log($message, $level = 'INFO') {
    return Tipax_Logger::get_instance()->log($message, $level);
}
