<?php
class ProductImporter {
    private $logger;
    private $config;
    
    public function __construct($logger, $config) {
        $this->logger = $logger;
        $this->config = $config;
    }
    
    public function processFromCSV($filePath) {
        $results = [
            'total' => 0,
            'updated' => 0,
            'created' => 0,
            'errors' => 0
        ];
        
        if (!file_exists($filePath)) {
            $this->logger->log("CSV file not found: $filePath", 'ERROR');
            return $results;
        }
        
        $file = fopen($filePath, 'r');
        if ($file === false) {
            $this->logger->log("Failed to open CSV: $filePath", 'ERROR');
            return $results;
        }
        
        // Skip header
        fgetcsv($file, 0, $this->config['csv_delimiter']);
        
        while (($row = fgetcsv($file, 0, $this->config['csv_delimiter'])) ){
            $this->processRow($row, $results);
        }
        
        fclose($file);
        return $results;
    }
    
    private function processRow($row, &$results) {
        if (count($row) < $this->config['required_columns']) {
            $this->logger->log("Skipping row - insufficient columns", 'WARNING');
            $results['errors']++;
            return;
        }
        
        try {
            $sku = trim($row[0]);
            $name = trim($row[4]);
            
            if (empty($sku) || empty($name)) {
                throw new Exception("Missing essential fields for product");
            }
            
            // Extract data
            $data = [
                'sku' => $sku,
                'gtin' => trim($row[1]),
                'psh_code' => trim($row[2]),
                'psh_item' => trim($row[3]),
                'name' => $name,
                'oem' => trim($row[5]),
                'category' => trim($row[6]),
                'price' => (float)str_replace(',', '.', $row[7]),
                'barcode' => trim($row[19]),
                'quantity' => (int)$row[20],
                'equivalent_refs' => $this->processEquivalentRefs(array_slice($row, 9, 10))
            ];
            
            $this->importProduct($data, $results);
            $results['total']++;
            
        } catch (Exception $e) {
            $this->logger->log("Row processing failed: " . $e->getMessage(), 'ERROR');
            $results['errors']++;
        }
    }
    
    private function processEquivalentRefs($refs) {
        $filtered = array_filter(array_map('trim', $refs));
        return implode('|', $filtered);
    }
    
    private function importProduct($data, &$results) {
        $product_id = $this->findProductIdBySku($data['sku']);
        
        if ($product_id) {
            $product = wc_get_product($product_id);
            $action = 'Updating';
            $results['updated']++;
        } else {
            $product = new WC_Product_Simple();
            $product->set_sku($data['sku']);
            $action = 'Creating';
            $results['created']++;
        }
        
        $this->logger->log("$action product: {$data['sku']} - {$data['name']}");
        
        // Core product data
        $product->set_name($data['name']);
        $product->set_regular_price($data['price']);
        $product->set_price($data['price']);
        $product->set_status('publish');
        $product->set_manage_stock(true);
        $product->set_stock_quantity($data['quantity']);
        $product->set_stock_status($data['quantity'] > 0 ? 'instock' : 'outofstock');
        $product->save();
        
        // Category handling
        $this->processCategory($product->get_id(), $data['category']);
        
        // ACF fields
        $this->updateProductMeta($product->get_id(), $data);
    }
    
    private function findProductIdBySku($sku) {
        global $wpdb;
        $clean_sku = trim(strtolower($sku));
        return $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
             WHERE meta_key = '_sku' 
             AND LOWER(TRIM(meta_value)) = %s",
            $clean_sku
        ));
    }
    
    private function processCategory($product_id, $category) {
        if (empty($category)) return;
        
        $term = term_exists($category, 'product_cat');
        if (!$term) {
            $term = wp_insert_term($category, 'product_cat');
        }
        
        if (!is_wp_error($term) && isset($term['term_id'])) {
            wp_set_object_terms($product_id, (int)$term['term_id'], 'product_cat', false);
        }
    }
    
    private function updateProductMeta($product_id, $data) {
        update_field('gtin_number', $data['gtin'], $product_id);
        update_field('psh_code', $data['psh_code'], $product_id);
        update_field('psh_item_number', $data['psh_item'], $product_id);
        update_field('manufacturer_references', $data['oem'], $product_id);
        update_field('barcode', $data['barcode'], $product_id);
        update_field('equivalent_references', $data['equivalent_refs'], $product_id);
    }
}