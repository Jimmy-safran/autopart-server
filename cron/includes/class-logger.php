<?php
class Logger {
    private $logFile;
    
    public function __construct($logFile) {
        $this->logFile = $logFile;
    }
    
    public function log($message, $level = 'INFO') {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] [$level] $message" . PHP_EOL;
        file_put_contents($this->logFile, $logEntry, FILE_APPEND);
        
        // Output to console when run via CLI
        if (php_sapi_name() === 'cli') {
            echo $logEntry;
        }
    }
}