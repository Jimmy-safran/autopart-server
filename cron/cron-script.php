<?php
define('WP_USE_THEMES', false);
require_once __DIR__ . '/../wp-load.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/class-logger.php';
require_once __DIR__ . '/includes/class-ftp-client.php';
require_once __DIR__ . '/includes/class-product-importer.php';

// Load configuration
$config = include(__DIR__ . '/config.php');

// Initialize logger
$logger = new Logger($config['paths']['log_file']);

// Security check
$isCli = (php_sapi_name() === 'cli');
$hasValidKey = isset($_GET['key']) && $_GET['key'] === $config['security']['secret_key'];

if (!$isCli && !$hasValidKey) {
    $logger->log('Unauthorized access attempt', 'SECURITY');
    http_response_code(403);
    exit('Forbidden');
}

$logger->log('=== CRON JOB STARTED ===');

try {
    // Initialize FTP client
    $ftp = new FTPClient(
        $config['ftp']['host'],
        $config['ftp']['user'],
        $config['ftp']['pass'],
        $logger
    );
    
    // Download CSV
    $localFile = $config['paths']['local_csv'];
    if ($ftp->downloadFile($config['ftp']['remote_path'], $localFile)) {
        // Initialize product importer
        $importer = new ProductImporter($logger, $config['processing']);
        
        // Process products
        $results = $importer->processFromCSV($localFile);
        
        // Report results
        $logger->log(sprintf(
            "Import results: %d total | %d updated | %d created | %d errors",
            $results['total'],
            $results['updated'],
            $results['created'],
            $results['errors']
        ));
    } else {
        $logger->log('FTP download failed - import aborted', 'ERROR');
    }
} catch (Exception $e) {
    $logger->log('CRITICAL ERROR: ' . $e->getMessage(), 'ERROR');
}

$logger->log('=== CRON JOB COMPLETED ===');