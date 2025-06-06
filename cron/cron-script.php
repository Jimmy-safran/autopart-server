<?php
define('WP_USE_THEMES', false);
require_once __DIR__ . '/../wp-load.php';

$secretKey = 'mySuperSecretKey123'; // Change this to something secure

// Only allow if run from CLI (cron) or with a valid GET key
$isCli = (php_sapi_name() === 'cli');
$hasValidKey = isset($_GET['key']) && $_GET['key'] === $secretKey;

if (!$isCli && !$hasValidKey) {
    http_response_code(403);
    exit('Forbidden');
}

// === Initialize logging ===
$logFile = __DIR__ . '/cron-log.txt';

function log_message($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    
    // Also output to screen if not CLI
    if (!(php_sapi_name() === 'cli')) {
        echo htmlspecialchars($message) . "<br>\n";
    }
}

log_message("Script started");

// === FTP Details ===
$ftpHost = 'ftpupload.net';
$ftpUser = 'if0_39150789';
$ftpPass = 'XoETdgu1v9iw0';
$remoteFile = '/htdocs/FTP-STOCK.csv';
$localFile = __DIR__ . '/downloaded.csv';

// === Connect to FTP ===
try {
    log_message("Connecting to FTP server...");
    $conn = ftp_connect($ftpHost);
    if (!$conn) {
        throw new Exception("Could not connect to FTP server");
    }

    log_message("Logging in to FTP...");
    $login = ftp_login($conn, $ftpUser, $ftpPass);
    if (!$login) {
        throw new Exception("FTP login failed");
    }

    ftp_pasv($conn, true); // Passive mode for firewalls

    // === Download the file ===
    log_message("Downloading CSV file...");
    if (ftp_get($conn, $localFile, $remoteFile, FTP_ASCII)) {
        log_message("CSV downloaded successfully");
    } else {
        throw new Exception("Failed to download CSV");
    }
    
    ftp_close($conn);
} catch (Exception $e) {
    if (isset($conn) && $conn) {
        ftp_close($conn);
    }
    log_message("FTP Error: " . $e->getMessage());
    exit(1);
}

// === Process CSV ===
function find_product_id_by_sku_case_insensitive($sku) {
    global $wpdb;
    
    $clean_sku = trim(strtolower($sku));
    return $wpdb->get_var($wpdb->prepare(
        "SELECT post_id FROM {$wpdb->postmeta} 
         WHERE meta_key = '_sku' 
         AND LOWER(TRIM(meta_value)) = %s",
        $clean_sku
    ));
}

function process_products_from_csv($file_path) {
    log_message("Starting CSV processing...");
    
    if (!file_exists($file_path)) {
        log_message("Error: CSV file not found");
        return ['total' => 0, 'updated' => 0, 'created' => 0, 'errors' => 0];
    }

    $file = fopen($file_path, 'r');
    if ($file === false) {
        log_message("Error: Unable to open CSV file");
        return ['total' => 0, 'updated' => 0, 'created' => 0, 'errors' => 0];
    }

    // Skip header
    fgetcsv($file, 0, ";");
    
    $count = 0;
    $updated = 0;
    $created = 0;
    $errors = 0;

    while (($row = fgetcsv($file, 0, ";"))) {
        // Validate minimum columns (21 columns expected)
        if (count($row) < 21) {
            log_message("Skipping row - insufficient columns");
            $errors++;
            continue;
        }

        // Clean and extract all fields
        $sku = trim($row[0]);
        $gtin = trim($row[1]);
        $psh_code = trim($row[2]);
        $psh_item = trim($row[3]);
        $name = trim($row[4]);
        $oem = trim($row[5]);
        $category = trim($row[6]);
        $price = (float)str_replace(',', '.', $row[7]);
        $barcode = trim($row[19]);
        $quantity = (int)$row[20];
        
        // Skip if essential fields are empty
        if (empty($sku) || empty($name)) {
            log_message("Skipping product $sku - missing essential fields");
            $errors++;
            continue;
        }

        try {
            // Process original numbers (columns 9-18)
            $original_numbers = [];
            for ($i = 9; $i <= 18; $i++) {
                if (!empty(trim($row[$i]))) {
                    $original_numbers[] = trim($row[$i]);
                }
            }
            $equivalent_refs = implode('|', $original_numbers);

            // Find existing product by SKU (case insensitive)
            $product_id = find_product_id_by_sku_case_insensitive($sku);
            
            if ($product_id) {
                $product = wc_get_product($product_id);
                $action = 'Updating';
                $updated++;
                log_message("Updating existing product: $sku");
                
                // Debug: Verify matched SKU
                $actual_sku = $product->get_sku();
                if (strtolower($actual_sku) !== strtolower($sku)) {
                    log_message("Note: Case difference - DB has '$actual_sku', CSV has '$sku'");
                }
            } else {
                $product = new WC_Product_Simple();
                $product->set_sku($sku);
                $action = 'Creating';
                $created++;
                log_message("Creating new product with SKU: $sku");
            }

            // Update core product data
            $product->set_name($name);
            $product->set_regular_price($price);
            $product->set_price($price);
            $product->set_status('publish');
            
            // Handle stock quantity
            $product->set_manage_stock(true);
            $product->set_stock_quantity($quantity);
            $product->set_stock_status($quantity > 0 ? 'instock' : 'outofstock');
            
            $saved = $product->save();
            $product_id = $product->get_id();

            if (!$saved || !$product_id) {
                throw new Exception("Failed to save product");
            }

            // Update category (case insensitive match)
            if (!empty($category)) {
                $term = get_term_by('name', $category, 'product_cat', ARRAY_A);
                
                if (!$term) {
                    $term = term_exists($category, 'product_cat');
                    
                    if (!$term) {
                        $term = wp_insert_term($category, 'product_cat');
                    }
                }
                
                if (!is_wp_error($term) && isset($term['term_id'])) {
                    wp_set_object_terms($product_id, (int)$term['term_id'], 'product_cat', false);
                }
            }

            // Update ACF fields
            update_field('gtin_number', $gtin, $product_id);
            update_field('psh_code', $psh_code, $product_id);
            update_field('psh_item_number', $psh_item, $product_id);
            update_field('manufacturer_references', $oem, $product_id);
            update_field('barcode', $barcode, $product_id);
            update_field('equivalent_references', $equivalent_refs, $product_id);

            $count++;
        } catch (Exception $e) {
            log_message("Error processing product $sku: " . $e->getMessage());
            $errors++;
        }
    }

    fclose($file);
    
    return [
        'total' => $count,
        'updated' => $updated,
        'created' => $created,
        'errors' => $errors
    ];
}

// Run the import
$result = process_products_from_csv($localFile);

// Final log and output
$message = sprintf(
    "Import completed: %d total (Updated: %d, Created: %d), %d errors",
    $result['total'],
    $result['updated'],
    $result['created'],
    $result['errors']
);

log_message($message);

if ($isCli) {
    echo $message . "\n";
} else {
    echo "<h2>Import Results</h2>";
    echo "<p>Total processed: {$result['total']}</p>";
    echo "<p>Products updated: {$result['updated']}</p>";
    echo "<p>Products created: {$result['created']}</p>";
    echo "<p>Errors: {$result['errors']}</p>";
    echo "<p>Last run: " . date('Y-m-d H:i:s') . "</p>";
}

log_message("Script finished");