<?php

if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

// config.php - Store all configuration values here
return [
    'security' => [
        'secret_key' => 'mySuperSecretKey123' // Change this to your actual secret
    ],
    'ftp' => [
        'host' => 'ftpupload.net',
        'user' => 'if0_39150789',
        'pass' => 'XoETdgu1v9iw0',
        'remote_path' => '/htdocs/stock.csv'
    ],
    'paths' => [
        'log_file' => __DIR__ . '/cron-log.txt',
        'local_csv' => __DIR__ . '/downloaded.csv'
    ]
];