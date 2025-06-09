<?php
// Exit if accessed directly
if (!defined('ABSPATH'))
    exit;

// BEGIN ENQUEUE PARENT ACTION
// AUTO GENERATED - Do not modify or remove comment markers above or below:

if (!function_exists('chld_thm_cfg_locale_css')):
    function chld_thm_cfg_locale_css($uri)
    {
        if (empty($uri) && is_rtl() && file_exists(get_template_directory() . '/rtl.css'))
            $uri = get_template_directory_uri() . '/rtl.css';
        return $uri;
    }
endif;
add_filter('locale_stylesheet_uri', 'chld_thm_cfg_locale_css');

if (!function_exists('chld_thm_cfg_parent_css')):
    function chld_thm_cfg_parent_css()
    {
        wp_enqueue_style('chld_thm_cfg_parent', trailingslashit(get_template_directory_uri()) . 'style.css', array('mobex-default-font'));
    }
endif;
add_action('wp_enqueue_scripts', 'chld_thm_cfg_parent_css', 10);

// END ENQUEUE PARENT ACTION

// hello123456

// Begin Header Section

/**
 * This section of the code is responsible for creating the menu 
 * that opens when the "All Products" button in the header is clicked.
 * It dynamically generates the menu items and ensures proper 
 * functionality for user interaction.
 */

function get_hierarchical_product_categories()
{
    $categories = get_terms(
        array(
            'taxonomy' => 'product_cat',
            'hide_empty' => true,
            'orderby' => 'name',
            'order' => 'ASC',
            'parent' => 0,
        )
    );

    $category_tree = array();
    if (!empty($categories) && !is_wp_error($categories)) {
        foreach ($categories as $category) {
            $category_tree[] = get_category_data($category);
        }
    }
    return $category_tree;
}

function get_category_data($category)
{
    $category_data = array(
        'id' => $category->term_id,
        'name' => $category->name,
        'slug' => $category->slug,
        'url' => get_term_link($category),
        'children' => array(),
    );

    $subcategories = get_terms(
        array(
            'taxonomy' => 'product_cat',
            'hide_empty' => true,
            'orderby' => 'name',
            'order' => 'ASC',
            'parent' => $category->term_id,
        )
    );

    if (!empty($subcategories) && !is_wp_error($subcategories)) {
        foreach ($subcategories as $subcategory) {
            $category_data['children'][] = get_category_data($subcategory);
        }
    }

    return $category_data;
}

function display_categories_frontend($categories, $level = 0)
{
    foreach ($categories as $category) {
        $has_children = false;
        if (!empty($category['children'])) {
            $has_children = true;
        }
        echo '<li class="product-category-item level-' . $level . ' ' . ($has_children ? 'has-children' : '') . '" data-category-id="' . $category['id'] . '" data-category-name="' . $category['name'] . '" data-category-slug="' . $category['slug'] . '" data-category-url="' . $category['url'] . '">';
        echo '<a href="#" class="category-link">';
        echo esc_html($category['name']);
        echo '</a>';
        if (!empty($category['children'])) {
            echo '<ul class="product-categories-list sub-level" data-parent-id="' . $category['id'] . '" style="display:none;">';
            display_categories_frontend($category['children'], $level + 1);
            echo '</ul>';
        }
        echo '</li>';
    }
}


function my_category_scripts()
{
    wp_enqueue_script('my-categories-script', get_stylesheet_directory_uri() . '/js/product-categories-sidebar.js', array('jquery'), '1.0.0', true);
}
add_action('wp_enqueue_scripts', 'my_category_scripts');

function product_categories_shortcode()
{
    $categories = get_hierarchical_product_categories();
    ob_start();
    ?>
    <div class="product-categories-wrapper">
        <ul class="product-categories-list main-level">
            <?php display_categories_frontend($categories, 0); ?>
        </ul>
    </div>
    <div class="category-screen" style="display: none;"></div>
    <?php
    $output = ob_get_clean();
    return $output;
}
add_shortcode('custom_product_categories', 'product_categories_shortcode');

// End Header Section





// Begin Homepage Section

function display_post_condition_has_value( $atts ) {
    
    
    $current_post_id = get_the_ID();
    $conditions = get_post_meta( $current_post_id, 'condition', true );

       

    if ( $current_post_id && $conditions ) {
        return "<a href='#'><span style=\"text-decoration: underline;\">See condition</span></a>";
    } else {
        return '';
    }
}
add_shortcode( 'if_condition_has_value', 'display_post_condition_has_value' );

function car_brands_list_shortcode()
{
    // Fetch all terms from the 'vehicles' taxonomy
    $vehicles = get_terms(array(
        'taxonomy' => 'vehicles', // Replace with the correct taxonomy slug
        'hide_empty' => false,      // Include terms with no associated posts
    ));

    // Start output buffering
    ob_start();

    if (!empty($vehicles) && !is_wp_error($vehicles)) {
        $makes = array(); // To store unique makes and their permalinks

        foreach ($vehicles as $vehicle) {
            // Parse the name to extract the "make"
            $name_parts = explode(',', $vehicle->name); // Split by comma
            $make = trim($name_parts[0]); // Get the first part and trim whitespace

            if (!empty($make) && !array_key_exists($make, $makes)) {
                // Construct the custom permalink
                $makes[$make] = home_url('/shop/?make=' . urlencode($make)); // Encode the make for the URL
            }
        }

        // Display the makes with load more functionality
        if (!empty($makes)) {
            $makes_array = array_keys($makes); // Get the list of makes
            $makes_to_show_initially = 16; // Number of makes to show initially

            echo '<div class="car-brands-section">';
            echo '<div class="car-brands-row row-1">';
            for ($i = 0; $i < min(8, count($makes_array)); $i++) {
                $make = $makes_array[$i];
                $permalink = $makes[$make];
                echo '<a href="' . esc_url($permalink) . '">' . esc_html($make) . '</a>';
            }
            echo '</div>';

            echo '<div class="car-brands-row row-2" style="' . (count($makes_array) <= 8 ? 'display: none;' : '') . '">';
            for ($i = 8; $i < min(16, count($makes_array)); $i++) {
                $make = $makes_array[$i];
                $permalink = $makes[$make];
                echo '<a href="' . esc_url($permalink) . '">' . esc_html($make) . '</a>';
            }
            echo '</div>';

            echo '<div class="all-brands-container" style="display: none;">';
            echo '</div>';

            echo '<div class="load-more-container" style="' . (count($makes_array) <= $makes_to_show_initially ? 'display: none;' : '') . '">';
            echo '<a href="#" id="load-more-brands">Load More</a>';
            echo '</div>';
            echo '</div>';

            // Add JavaScript for load more functionality
            ?>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const loadMoreLink = document.getElementById('load-more-brands');
                    const allBrandsContainer = document.querySelector('.all-brands-container');
                    const allBrands = <?php echo json_encode($makes_array); ?>;
                    const brandsToShowInitially = <?php echo intval($makes_to_show_initially); ?>;
                    let isExpanded = false;

                    if (loadMoreLink) {
                        loadMoreLink.addEventListener('click', function (e) {
                            e.preventDefault();
                            isExpanded = !isExpanded;
                            allBrandsContainer.innerHTML = ''; // Clear previous content

                            if (isExpanded) {
                                let html = '';
                                for (let i = brandsToShowInitially; i < allBrands.length; i++) {
                                    if (i % 8 === 0) {
                                        html += '<div class="car-brands-row">';
                                    }
                                    html +=
                                        `<a href="<?php echo home_url('/shop/?make='); ?>${encodeURIComponent(allBrands[i])}">${allBrands[i]}</a>`;
                                    if ((i + 1) % 8 === 0 || i === allBrands.length - 1) {
                                        html += '</div>';
                                    }
                                }
                                allBrandsContainer.innerHTML = html;
                                allBrandsContainer.style.display = 'block';
                                loadMoreLink.textContent = 'Load Less';
                            } else {
                                allBrandsContainer.style.display = 'none';
                                loadMoreLink.textContent = 'Load More';
                            }
                        });
                    }
                });
            </script>
            <?php
        } else {
            echo '<p>No vehicle makes found.</p>';
        }
    } else {
        echo '<p>No vehicles found.</p>';
    }

    // Return the buffered content
    return ob_get_clean();
}
add_shortcode('car_brands_list', 'car_brands_list_shortcode');

// End HomePage Section




// Begin Category Section


function styled_category_acf_field_shortcode()
{

    $output = "";

    $main_summary = get_category_acf_field("main_summary");

    if ($main_summary) {
        $sentences = preg_split('/([.?!]["\']?\s|$)/', wp_strip_all_tags($main_summary), -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        $title = isset($sentences[0]) ? trim($sentences[0]) : '';
        $content_raw = '';
        for ($i = 1; $i < count($sentences); $i++) {
            $content_raw .= trim($sentences[$i]);
        }
        $content = ltrim($content_raw, '? ');

        $output .= '<div class="styled-acf-field">';
        if ($title) {
            $output .= '<h6>' . wp_kses_post($title . (substr(rtrim($title), -1) === '?' ? '' : '?')) . '</h6>';
        }
        if ($content) {
            $paragraphs = explode("\n", $content);
            foreach ($paragraphs as $p) {
                $p = trim($p);
                if (!empty($p)) {
                    $output .= '<p>' . wp_kses_post($p) . '</p>';
                }
            }
        }

        $faq1_content = get_category_acf_field("faq1");
        if ($faq1_content) {
            $sentences_faq1 = preg_split('/([.?!]["\']?\s|$)/', wp_strip_all_tags($faq1_content), -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
            $title_faq1 = isset($sentences_faq1[0]) ? trim($sentences_faq1[0]) : 'FAQ';
            $content_faq1_raw = '';
            for ($i = 1; $i < count($sentences_faq1); $i++) {
                $content_faq1_raw .= trim($sentences_faq1[$i]);
            }
            $content_faq1 = ltrim($content_faq1_raw, '? ');
            $output .= '<div class="faq-accordion">';
            $output .= '<h6 class="faq-question">' . wp_kses_post($title_faq1 . (substr(rtrim($title_faq1), -1) === '?' ? '' : '?')) . '</h6>';
            $paragraphs_faq1 = explode("\n", $content_faq1);
            $output .= '<div class="faq-answer">';
            foreach ($paragraphs_faq1 as $p) {
                $p = trim($p);
                if (!empty($p)) {
                    $output .= '<p>' . wp_kses_post($p) . '</p>';
                }
            }
            $output .= '</div>';
            $output .= '</div>';
        }

        $faq2_content = get_category_acf_field("faq2");
        if ($faq2_content) {
            $sentences_faq2 = preg_split('/([.?!]["\']?\s|$)/', wp_strip_all_tags($faq2_content), -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
            $title_faq2 = isset($sentences_faq2[0]) ? trim($sentences_faq2[0]) : 'FAQ';
            $content_faq2_raw = '';
            for ($i = 1; $i < count($sentences_faq2); $i++) {
                $content_faq2_raw .= trim($sentences_faq2[$i]);
            }
            $content_faq2 = ltrim($content_faq2_raw, '? ');
            $output .= '<div class="faq-accordion">';
            $output .= '<h6 class="faq-question">' . wp_kses_post($title_faq2 . (substr(rtrim($title_faq2), -1) === '?' ? '' : '?')) . '</h6>';
            $paragraphs_faq2 = explode("\n", $content_faq2);
            $output .= '<div class="faq-answer">';
            foreach ($paragraphs_faq2 as $p) {
                $p = trim($p);
                if (!empty($p)) {
                    $output .= '<p>' . wp_kses_post($p) . '</p>';
                }
            }
            $output .= '</div>';
            $output .= '</div>';
        }

        $faq3_content = get_category_acf_field("faq3");
        if ($faq3_content) {
            $sentences_faq3 = preg_split('/([.?!]["\']?\s|$)/', wp_strip_all_tags($faq3_content), -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
            $title_faq3 = isset($sentences_faq3[0]) ? trim($sentences_faq3[0]) : 'FAQ';
            $content_faq3_raw = '';
            for ($i = 1; $i < count($sentences_faq3); $i++) {
                $content_faq3_raw .= trim($sentences_faq3[$i]);
            }
            $content_faq3 = ltrim($content_faq3_raw, '? ');
            $output .= '<div class="faq-accordion">';
            $output .= '<h6 class="faq-question">' . wp_kses_post($title_faq3 . (substr(rtrim($title_faq3), -1) === '?' ? '' : '?')) . '</h6>';
            $paragraphs_faq3 = explode("\n", $content_faq3);
            $output .= '<div class="faq-answer">';
            foreach ($paragraphs_faq3 as $p) {
                $p = trim($p);
                if (!empty($p)) {
                    $output .= '<p>' . wp_kses_post($p) . '</p>';
                }
            }
            $output .= '</div>';
            $output .= '</div>';
        }

        $output .= '</div>';
    }

    return $output;
}
add_shortcode('styled_category_info', 'styled_category_acf_field_shortcode');

function enqueue_category_accordion_styles()
{
    wp_enqueue_style('category-accordion-style', get_stylesheet_directory_uri() . '/css/category-accordion.css', array(), '1.0.0', 'all');
}
add_action('wp_enqueue_scripts', 'enqueue_category_accordion_styles');

function enqueue_category_accordion_scripts()
{
    wp_enqueue_script('category-accordion-script', get_stylesheet_directory_uri() . '/js/category-accordion.js', array(), '1.0.0', true);

}
add_action('wp_enqueue_scripts', 'enqueue_category_accordion_scripts');


function styled_link_acf_field_shortcode($atts)
{
    $atts = shortcode_atts(array(
        'field_name' => '',
        'field_name_2' => '',
    ), $atts);

    $field_name = sanitize_text_field($atts['field_name']);
    $field_value = get_category_acf_field($field_name); // Use the function from the previous answer

    $output = '';

    if ($field_value) {
        $output .= '<div style="
             background-color: #f7f7f7;
             padding: 15px;
             margin-bottom: 20px;
             border-radius: 5px;
             display: flex;
             flex-direction: column;
             align-items: center;
             justify-content: flex-end;
             background-image: url(' . get_bloginfo('template_directory') . '/../../uploads/Tuto_Fallback.jpg);
             background-size: cover;
             background-position: center;
             border-radius: 5px;
             min-height: 200px;
             position: relative;
             padding-bottom: 10px; /* Reduced padding-bottom */
             box-sizing: border-box;
             box-shadow:1px 2px 9px -3px rgba(0,0,0,0.3) !important;
         ">';

        $output .= '<a href="' . $field_value . '" style="
             display: block;
             width: 100%;
             height: 100%;
             position: absolute;
             top: 0;
             left: 0;
             text-indent: -9999px;
             overflow: hidden;
             z-index: 1;
         ">ASSEMBLY TUTORIAL</a>';

        $output .= '<div style="
             text-align: center;
             color: #fff;
             z-index: 2;
             padding-top: 0px; /* Removed padding-top */
             position: relative;
         ">';
        $output .= '<p style="
             font-size: 16px;
             line-height: 1.5;
             color: #fff; /* Set text color to white */
             margin-bottom: 5px; /* Add a little margin between text and link */
         ">Changing an alternator on a Peugeot 206 1.4 HDi</p>';
        $output .= '<a href="' . $field_value . '" style="
             color: #fff;
             text-decoration: none;
             font-weight: bold;
             display: inline-block; /* changed to inline-block */
             
         ">ASSEMBLY TUTORIAL</a>';
        $output .= '</div>';
        $output .= '</div>';
        return $output;
    }
    return '';
}
add_shortcode('styled_link_category_acf', 'styled_link_acf_field_shortcode');



function get_category_acf_field($field_name)
{
    // Get the current category ID.
    $current_category = get_queried_object();

    if ($current_category && isset($current_category->term_id)) {
        $category_id = $current_category->term_id;

        // Get the ACF field value for the category.
        $field_value = get_field($field_name, 'product_cat_' . $category_id);
        if ($field_value) {
            return $field_value;
        }
    }
    return ''; // Return empty string if no value
}
add_shortcode('styled_category_acf', 'styled_category_acf_field_shortcode');


function category_product_count_shortcode()
{
    global $wp_query;
    $total_products = $wp_query->found_posts;
    if ($total_products > 0) {
        return '<h6 class="category-product-count">Total products in this category: ' . esc_html($total_products) . '</h6>';
    }
    return '';
}
add_shortcode('category_product_count', 'category_product_count_shortcode');

// End Category Section




// Product Page Section

function mobex_custom_product_features_shortcode($atts)
{
    $atts = shortcode_atts(array(
        'heading' => 'Features', // Optional heading for the section
    ), $atts, 'mobex_custom_product_features');

    global $product;

    $output = ''; // Initialize the $output variable

    if (!is_product() || !$product) {
        return '';
    }

    // Get the product ID
    $product_id = $product->get_id();

    // Get the value of the custom field (replace 'enovathemes_addons_features' with your actual field name if it's different)
    $features_data = get_post_meta($product_id, 'enovathemes_addons_features', true);


    $output .= '<ul>';

    // Check if the features data is an array (e.g., from a repeater field)
    if (is_array($features_data) && !empty($features_data)) {
        foreach ($features_data as $feature) {
            // Adjust this based on how your repeater field is structured
            if (is_array($feature) && isset($feature['feature_item'])) { // Example for a repeater with a 'feature_item' sub-field
                $output .= '<li class="feature-item"><span class="feature-icon">&#10004;</span>' . esc_html($feature['feature_item']) . '</li>';
            } elseif (is_string($feature)) { // Example if each item is directly a string in the array
                $output .= '<li class="feature-item"><span class="feature-icon">&#10004;</span>' . esc_html($feature) . '</li>';
            }
        }
    } elseif (is_string($features_data) && !empty($features_data)) {
        // If the features are stored as a single string (e.g., comma-separated or line-separated)
        $features_array = array_map('trim', preg_split('/[\r\n,]+/', $features_data));
        if (!empty($features_array)) {
            foreach ($features_array as $feature) {
                $output .= '<li class="feature-item"><span class="feature-icon">&#10004;</span>' . esc_html($feature) . '</li>';
            }
        }
    } elseif (!empty($features_data)) {
        // If the custom field stores a simple text value (display as a single paragraph)
        $output .= '<li class="feature-item"><span class="feature-icon">&#10004;</span>' . wp_kses_post($features_data) . '</li>';
    } else {
        $output .= '<li class="feature-item">No features listed for this product.</li>';
    }

    $output .= '</ul>';


    return $output;
}
add_shortcode('mobex_custom_features', 'mobex_custom_product_features_shortcode');
function mobex_custom_features_scripts()
{
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $loaded = true;
    ?>
    <style>

    </style>
    <script type="text/javascript">
        function toggleFeatures(listId, buttonId) {
            const featuresList = document.getElementById(listId);
            const expandButton = document.getElementById(buttonId);
            featuresList.classList.toggle('hidden');
            expandButton.textContent = featuresList.classList.contains('hidden') ? '+' : '-';
        }
        // Initially hide all feature lists
        document.addEventListener('DOMContentLoaded', function () {
            const allFeatureLists = document.querySelectorAll('.feature-list');
            const allExpandButtons = document.querySelectorAll('.expand-collapse-button');
            allFeatureLists.forEach(list => list.classList.add('hidden'));
            allExpandButtons.forEach(button => button.textContent = '+');
        });
    </script>
    <?php
}

function display_product_all_attributes_shortcode($atts)
{
    $atts = shortcode_atts(array(
        'id' => get_the_ID(), // Default to the current product ID in the loop
    ), $atts, 'product_all_attributes');

    $product_id = intval($atts['id']);
    $output = '';

    if (!$product_id) {
        return ''; // Exit if no product ID is provided
    }

    $product = wc_get_product($product_id);

    if ($product) {
        $attributes = $product->get_attributes();

        if (!empty($attributes)) {
            $output .= '<ul class="product-attributes">';
            foreach ($attributes as $attribute) {
                $name = wc_attribute_label($attribute->get_name());
                $values = array();

                if ($attribute->is_taxonomy()) {
                    $terms = wp_get_post_terms($product_id, $attribute->get_name(), array('fields' => 'names'));
                    if (!is_wp_error($terms) && !empty($terms)) {
                        $values = $terms;
                    }
                } else {
                    $values = array_map('trim', explode(WC_DELIMITER, $product->get_attribute($attribute->get_name())));
                }

                if (!empty($values)) {
                    $output .= '<li class="product-attribute">';
                    $output .= '<strong class="attribute-name">' . esc_html($name) . ':</strong> ';
                    $output .= '<span class="attribute-value">' . esc_html(implode(', ', $values)) . '</span>';
                    $output .= '</li>';
                }
            }
            $output .= '</ul>';
        } else {
            $output .= '<p>No attributes found for this product.</p>';
        }
    } else {
        $output .= '<p>Product not found.</p>';
    }

    return $output;
}
add_shortcode('product_all_attributes', 'display_product_all_attributes_shortcode');

function manufacturer_references_shortcode($atts)
{

    global $product;

    if (!is_product() || !$product) {
        return '';
    }

    $manufact_refs = get_post_meta($product->get_id(), 'manufacturer_references', true);
    $output = '<div class="manufacturer-references-wrapper">';

    if ($manufact_refs) {
        $ref_numbers = array_filter(array_map('trim', explode('|', $manufact_refs)));

        if (!empty($ref_numbers)) {
            $output .= '<div class="manufacturer-references-list">';

            $output .= '<ul class="manufacturer-ref-numbers">';

            foreach ($ref_numbers as $ref) {
                $search_url = esc_url(add_query_arg(array(
                    's' => $ref,
                    'post_type' => 'product'
                ), home_url('/')));

                $output .= sprintf(
                    '<li><a href="%s" data-manufacturer="%s">%s</a></li>',
                    $search_url,
                    esc_attr($ref),
                    esc_html($ref)
                );
            }

            $output .= '</ul>';
            $output .= '</div>';
        } else {
            $output .= '<p>' . __('No reference numbers found.', 'your-text-domain') . '</p>';
        }
    } else {
        $output .= '<p>' . __('No manufacturer references data provided.', 'your-text-domain') . '</p>';
    }

    $output .= '</div>';

    return $output;
}
add_shortcode('manufacturer_references', 'manufacturer_references_shortcode');
function equivalent_references_shortcode($atts)
{
    global $product;

    if (!is_product() || !$product) {
        return '';
    }

    $equivalent_refs = get_post_meta($product->get_id(), 'equivalent_references', true);
    $output = '<div class="equivalent-references-wrapper">';

    if ($equivalent_refs) {
        $ref_numbers = array_filter(array_map('trim', explode('|', $equivalent_refs)));

        if (!empty($ref_numbers)) {
            $output .= '<div class="equivalent-references-list">';

            $output .= '<ul class="equivalent-ref-numbers">';

            foreach ($ref_numbers as $ref) {
                $search_url = esc_url(add_query_arg(array(
                    's' => $ref,
                    'post_type' => 'product'
                ), home_url('/')));

                $output .= sprintf(
                    '<li><a href="%s" data-equivalent="%s">%s</a></li>',
                    $search_url,
                    esc_attr($ref),
                    esc_html($ref)
                );
            }

            $output .= '</ul>';
            $output .= '</div>';
        } else {
            $output .= '<p>' . __('No reference numbers found.', 'your-text-domain') . '</p>';
        }
    } else {
        $output .= '<p>' . __('No equivalent references data provided.', 'your-text-domain') . '</p>';
    }

    $output .= '</div>';

    return $output;
}
add_shortcode('equivalent_references', 'equivalent_references_shortcode');
function mount_on_shortcode($atts)
{

    global $product;

    if (!is_product() || !$product) {
        return '';
    }

    $mount_refs = get_post_meta($product->get_id(), 'mount_on', true);
    $output = '<div class="mount-references-wrapper">';

    if ($mount_refs) {
        $ref_numbers = array_filter(array_map('trim', explode('|', $mount_refs)));

        if (!empty($ref_numbers)) {
            $output .= '<div class="mount-references-list">';

            $output .= '<ul class="mount-ref-numbers">';

            foreach ($ref_numbers as $ref) {
                $search_url = esc_url(add_query_arg(array(
                    's' => $ref,
                    'post_type' => 'product'
                ), home_url('/')));

                $output .= sprintf(
                    '<li><a href="%s" data-mount="%s">%s</a></li>',
                    $search_url,
                    esc_attr($ref),
                    esc_html($ref)
                );
            }

            $output .= '</ul>';
            $output .= '</div>';
        } else {
            $output .= '<p>' . __('No reference numbers found.', 'your-text-domain') . '</p>';
        }
    } else {
        $output .= '<p>' . __('No mount references data provided.', 'your-text-domain') . '</p>';
    }

    $output .= '</div>';

    return $output;
}
add_shortcode('mount_on', 'mount_on_shortcode');

function get_rating_histogram_html()
{
    if (!function_exists('wc_get_product'))
        return '';

    global $product;

    if (!$product && is_product()) {
        $product = wc_get_product(get_the_ID());
    }

    if (!$product)
        return '';

    $total_reviews = $product->get_review_count();
    $average_rating = $product->get_average_rating();
    $ratings = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];

    $comments = get_comments([
        'post_id' => $product->get_id(),
        'status' => 'approve',
        'type' => 'review',
        'meta_key' => 'rating'
    ]);

    foreach ($comments as $comment) {
        $rating = intval(get_comment_meta($comment->comment_ID, 'rating', true));
        if ($rating >= 1 && $rating <= 5) {
            $ratings[$rating]++;
        }
    }

    ob_start();
    ?>
    <div class="review-summary" id="review-summary-container">
        <div class="average-rating">
            <span class="star-icon">★</span>
            <span class="rating-number"><?php echo number_format($average_rating, 1); ?></span>
            <span class="rating-out-of">/5</span>
        </div>
        <div class="rating-meta">
            global ratings from <strong><?php echo $total_reviews; ?></strong> reviews
        </div>

        <div class="rating-histogram">
            <?php foreach (array_reverse($ratings, true) as $stars => $count):
                $percent = $total_reviews > 0 ? ($count / $total_reviews * 100) : 0;
                ?>
                <div class="rating-row">
                    <span class="star-level">
                        <span class="star-number"><?php echo $stars; ?></span>
                        <span class="star-icon-small">★</span>
                    </span>
                    <div class="bar-bg">
                        <div class="bar-fill" style="width:<?php echo $percent; ?>%"></div>
                    </div>
                    <span class="count"><?php echo $count; ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="sort-note">Reviews sorted from newest to oldest</div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('rating_histogram', 'get_rating_histogram_html');

function custom_review_form_shortcode()
{
    if (!is_product())
        return '';

    global $product;
    if (!$product)
        $product = wc_get_product(get_the_ID());
    if (!$product)
        return '';

    $output = '';

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['custom_review_submit'])) {
        $name = sanitize_text_field($_POST['custom_name']);
        $email = sanitize_email($_POST['custom_email']);
        $content = sanitize_textarea_field($_POST['custom_review']);
        $rating = intval($_POST['custom_rating']);
        $product_id = intval($_POST['product_id']);

        if ($name && $email && $content && $product_id && $rating) {
            $commentdata = [
                'comment_post_ID' => $product_id,
                'comment_author' => $name,
                'comment_author_email' => $email,
                'comment_content' => $content,
                'comment_approved' => 1, // Auto-approve (change to 0 for moderation)
                'comment_type' => 'review',
                'comment_meta' => [
                    'rating' => $rating
                ]
            ];

            $comment_id = wp_insert_comment($commentdata);
            if ($comment_id) {
                $output .= '<div class="review-success">Thank you! Your review has been submitted.</div>';

                // Clear WooCommerce transients to update review count
                if (function_exists('wc_delete_product_transients')) {
                    wc_delete_product_transients($product_id);
                }

                // Get updated review data
                $product = wc_get_product($product_id);
                $total_reviews = $product->get_review_count();
                $average_rating = $product->get_average_rating();
                $ratings = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];

                $comments = get_comments([
                    'post_id' => $product_id,
                    'status' => 'approve',
                    'type' => 'review',
                    'meta_key' => 'rating'
                ]);

                foreach ($comments as $comment) {
                    $rating = intval(get_comment_meta($comment->comment_ID, 'rating', true));
                    if ($rating >= 1 && $rating <= 5) {
                        $ratings[$rating]++;
                    }
                }

                // Prepare updated HTML
                $updated_html = '
                <div class="average-rating">
                    <span class="star-icon">★</span>
                    <span class="rating-number">' . number_format($average_rating, 1) . '</span>
                    <span class="rating-out-of">/5</span>
                </div>
                <div class="rating-meta">
                    global ratings from <strong>' . $total_reviews . '</strong> reviews
                </div>
                <div class="rating-histogram">';

                foreach (array_reverse($ratings, true) as $stars => $count) {
                    $percent = $total_reviews > 0 ? ($count / $total_reviews * 100) : 0;
                    $updated_html .= '
                    <div class="rating-row">
                        <span class="star-level">
                            <span class="star-number">' . $stars . '</span>
                            <span class="star-icon-small">★</span>
                        </span>
                        <div class="bar-bg">
                            <div class="bar-fill" style="width:' . $percent . '%"></div>
                        </div>
                        <span class="count">' . $count . '</span>
                    </div>';
                }

                $updated_html .= '</div>
                <div class="sort-note">Reviews sorted from newest to oldest</div>';

                // Add JavaScript to update the display
                $output .= '
                <script>
                    document.addEventListener("DOMContentLoaded", function() {
                        const reviewContainer = document.getElementById("review-summary-container");
                        if (reviewContainer) {
                            reviewContainer.innerHTML = `' . $updated_html . '`;
                        }
                    });
                </script>';
            } else {
                $output .= '<div class="review-error">There was an error submitting your review. Please try again.</div>';
            }
        }
    }

    ob_start(); ?>
    <div class="custom-review-box">
        <button class="toggle-review-btn">Write a Review</button>

        <form class="custom-review-form" method="post" style="display:none;">
            <input type="hidden" name="product_id" value="<?php echo get_the_ID(); ?>">

            <label for="custom_name">Name</label>
            <input type="text" id="custom_name" name="custom_name" required>

            <label for="custom_email">Email</label>
            <input type="email" id="custom_email" name="custom_email" required>

            <label for="custom_rating">Rating</label>
            <select id="custom_rating" name="custom_rating" required>
                <option value="">Select rating</option>
                <option value="5">5 Stars</option>
                <option value="4">4 Stars</option>
                <option value="3">3 Stars</option>
                <option value="2">2 Stars</option>
                <option value="1">1 Star</option>
            </select>

            <label for="custom_review">Review</label>
            <textarea id="custom_review" name="custom_review" rows="5" required></textarea>

            <button type="submit" name="custom_review_submit">Submit Review</button>
        </form>

        <?php echo $output; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toggleButton = document.querySelector('.toggle-review-btn');
            const reviewForm = document.querySelector('.custom-review-form');

            if (toggleButton && reviewForm) {
                toggleButton.addEventListener('click', function () {
                    if (window.getComputedStyle(reviewForm).display === 'none') {
                        reviewForm.style.display = 'block';
                    } else {
                        reviewForm.style.display = 'none';
                    }
                });
            }
        });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('custom_review_form', 'custom_review_form_shortcode');

// End Product Page Section




// Footer Section

// Remove the footer banner from the single product page
add_action('wp_loaded', function () {
    remove_action('woocommerce_after_single_product', 'mobex_enovathemes_woocommerce_after_single_product');
});

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_script('wc-cart-fragments');
}, 99);

add_action('wp_footer', 'fix_elementor_cart_ajax');
function fix_elementor_cart_ajax()
{
    if (!is_product())
        return;
    ?>
    <script>
        jQuery(document).on('added_to_cart', function () {
            jQuery('.elementor-menu-cart__toggle').click();
        });
    </script>
    <?php
}

// End Footer Section