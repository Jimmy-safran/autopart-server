<?php
class ProductImporter {
    private $logger;
    
    public function __construct($logger) {
        $this->logger = $logger;
    }
    
    public function processFromCSV($filePath) {
        if (!file_exists($filePath)) {
            $this->logger->log("CSV file not found: $filePath");
            return $this->emptyResults();
        }

        $file = fopen($filePath, 'r');
        if ($file === false) {
            $this->logger->log("Unable to open CSV file: $filePath");
            return $this->emptyResults();
        }

        // Skip header
        fgetcsv($file, 0, ";");
        
        $results = $this->emptyResults();

        while (($row = fgetcsv($file, 0, ";")) !== false) {
            $this->processRow($row, $results);
        }

        fclose($file);
        return $results;
    }
    
    private function processRow($row, &$results) {
        if (count($row) < 21) {
            $this->logger->log("Skipping row - insufficient columns");
            $results['errors']++;
            return;
        }

        $data = $this->extractRowData($row);
        
        if (empty($data['sku']) || empty($data['name'])) {
            $this->logger->log("Skipping product - missing essential fields");
            $results['errors']++;
            return;
        }

        try {
            $product = $this->findOrCreateProduct($data['sku'], $results);
            $this->updateProduct($product, $data);
            $this->updateProductMeta($product, $data);
            $results['total']++;
        } catch (Exception $e) {
            $this->logger->log("Error processing product {$data['sku']}: " . $e->getMessage());
            $results['errors']++;
        }
    }
    
    private function extractRowData($row) {
        return [
            'sku' => trim($row[0]),
            'gtin' => trim($row[1]),
            'psh_code' => trim($row[2]),
            'psh_item' => trim($row[3]),
            'name' => trim($row[4]),
            'oem' => trim($row[5]),
            'category' => trim($row[6]),
            'price' => (float)str_replace(',', '.', $row[7]),
            'barcode' => trim($row[19]),
            'quantity' => (int)$row[20],
            'equivalent_refs' => $this->processEquivalentRefs(array_slice($row, 9, 10))
        ];
    }
    
    private function processEquivalentRefs($refs) {
        $filtered = array_filter(array_map('trim', $refs));
        return implode('|', $filtered);
    }
    
    private function findOrCreateProduct($sku, &$results) {
        $product_id = $this->findProductIdBySku($sku);
        
        if ($product_id) {
            $results['updated']++;
            $this->logger->log("Updating existing product: $sku");
            return wc_get_product($product_id);
        }
        
        $results['created']++;
        $this->logger->log("Creating new product with SKU: $sku");
        $product = new WC_Product_Simple();
        $product->set_sku($sku);
        return $product;
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
    
    private function updateProduct($product, $data) {
        $product->set_name($data['name']);
        $product->set_regular_price($data['price']);
        $product->set_price($data['price']);
        $product->set_status('publish');
        $product->set_manage_stock(true);
        $product->set_stock_quantity($data['quantity']);
        $product->set_stock_status($data['quantity'] > 0 ? 'instock' : 'outofstock');
        $product->save();
        
        $this->updateCategory($product->get_id(), $data['category']);
    }
    
    private function updateCategory($product_id, $category) {
        if (empty($category)) return;
        
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
    
    private function updateProductMeta($product, $data) {
        $product_id = $product->get_id();
        
        update_field('gtin_number', $data['gtin'], $product_id);
        update_field('psh_code', $data['psh_code'], $product_id);
        update_field('psh_item_number', $data['psh_item'], $product_id);
        update_field('manufacturer_references', $data['oem'], $product_id);
        update_field('barcode', $data['barcode'], $product_id);
        update_field('equivalent_references', $data['equivalent_refs'], $product_id);
    }
    
    private function emptyResults() {
        return [
            'total' => 0,
            'updated' => 0,
            'created' => 0,
            'errors' => 0
        ];
    }
}