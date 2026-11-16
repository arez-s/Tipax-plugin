<?php
if (!defined('ABSPATH')) {
    exit;
}

if (class_exists('Tipax_Report_Log_Service')) {
    return;
}

class Tipax_Report_Log_Service
{
    private $logger;

    public function __construct($logger = null)
    {
        $this->logger = $logger ?: Tipax_Logger::get_instance();
    }

    public function log($message, $level = 'INFO')
    {
        return $this->logger->log($message, $level);
    }

    public function get_logs($lines = 100)
    {
        return $this->logger->get_logs($lines);
    }

    public function get_log_file_path()
    {
        return $this->logger->get_log_file_path();
    }

    public function get_log_contents()
    {
        $log_path = $this->get_log_file_path();
        if (!file_exists($log_path)) {
            return false;
        }

        return file_get_contents($log_path);
    }

    public function clear_logs()
    {
        return $this->logger->clear_logs();
    }
}



if (!class_exists('Tipax_Report_Log_Module')) {
    class_alias('Tipax_Report_Log_Service', 'Tipax_Report_Log_Module');
}
