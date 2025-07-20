<?php
/*
Plugin Name: Free Colour Samples for WooCommerce
Description: Adds a "Free Colour Sample" feature to WooCommerce products.
Version: 2.2.0
Author: Expert Coders
Text Domain: free-colour-samples
Domain Path: /languages
*/

if ( ! defined( 'ABSPATH' ) ) exit;

// Define plugin constants
define('TIWSC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TIWSC_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load required files
require_once TIWSC_PLUGIN_DIR . 'includes/class-colour-map.php';
require_once TIWSC_PLUGIN_DIR . 'includes/class-samples-page.php';

// Initialize samples page
add_action('init', function() {
    new TIWSC_Samples_Page();
});

// Load text domain for translations
add_action('plugins_loaded', 'tiwsc_load_textdomain');
function tiwsc_load_textdomain() {
    load_plugin_textdomain('free-colour-samples', false, dirname(plugin_basename(__FILE__)) . '/languages');
}

// Check if the Free Colour Samples feature is enabled.
// If the option has never been saved before, default to "yes" so the
// functionality works out-of-the-box. Only an explicit "no" in the database will disable
// the feature.
function tiwsc_is_enabled() {
    // Provide a default value of "yes" to get_option so that the first run
    // behaves as enabled. Only an explicit "no" in the database will disable
    // the feature.
    return get_option('tiwsc_enable_samples', 'yes') === 'yes';
}

add_action('init', function() {
    // Start session if not already started and headers not sent
    if (!session_id() && !headers_sent()) {
        session_start();
    }
    
    // Initialize samples array if not set
    if (!isset($_SESSION['tiwsc_samples'])) {
        $_SESSION['tiwsc_samples'] = [];
    }
    
    // Clean up invalid product IDs from session
    if (isset($_SESSION['tiwsc_samples']) && is_array($_SESSION['tiwsc_samples'])) {
        $_SESSION['tiwsc_samples'] = array_filter($_SESSION['tiwsc_samples'], function($sample_key) {
            // Handle both simple (numeric ID) and variable (product|attribute|value) keys
            $product_id = $sample_key;
            if (strpos($sample_key, '|') !== false) {
                $parts = explode('|', $sample_key);
                $product_id = $parts[0]; // First part is always the product ID
            }

            return is_numeric($product_id) && get_post_status($product_id) !== false;
        });
        $_SESSION['tiwsc_samples'] = array_values($_SESSION['tiwsc_samples']); // Re-index array
    }
}, 1); // High priority to run early

add_action('admin_menu', function() {
    add_menu_page(
        __('Free Colour Samples', 'free-colour-samples'),
        __('Free Colour Samples', 'free-colour-samples'),
        'manage_options',
        'tiwsc-free-samples',
        'tiwsc_admin_samples_settings_page',
        'dashicons-admin-customizer'
    );
});

function tiwsc_admin_samples_settings_page() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $value = isset($_POST['tiwsc_enable_samples']) && $_POST['tiwsc_enable_samples'] === 'yes' ? 'yes' : 'no';
        update_option('tiwsc_enable_samples', $value);

        if (isset($_POST['tiwsc_admin_email'])) {
            $admin_email = sanitize_email($_POST['tiwsc_admin_email']);
            update_option('tiwsc_admin_email', $admin_email);
        }

        echo '<div class="updated"><p>' . __('Settings saved.', 'free-colour-samples') . '</p></div>';
    }
    $enabled = get_option('tiwsc_enable_samples', 'yes');
    $admin_email = get_option('tiwsc_admin_email', get_option('admin_email'));
    ?>
    <div class="wrap">
        <h1><?php _e('Free Colour Samples Settings', 'free-colour-samples'); ?></h1>
        <form method="post">
            <label>
                <input type="checkbox" name="tiwsc_enable_samples" value="yes" <?php checked($enabled, 'yes'); ?> />
                <?php _e('Enable Free Colour Sample functionality', 'free-colour-samples'); ?>
            </label>
            <br><br>
            <label>
                <strong><?php _e('Admin Email for Form Submissions:', 'free-colour-samples'); ?></strong><br>
                <input type="email" name="tiwsc_admin_email" value="<?php echo esc_attr($admin_email); ?>" style="width:350px;" required />
            </label>
            <br><br>
            <input type="submit" class="button button-primary" value="<?php _e('Save Changes', 'free-colour-samples'); ?>" />
        </form>
    </div>
    <?php
}

add_action('add_meta_boxes', function() {
    add_meta_box(
        'tiwsc_free_sample',
        __('Free Colour Sample', 'free-colour-samples'),
        function($post) {
            $enabled = get_post_meta($post->ID, '_tiwsc_free_sample', true);
            ?>
            <label>
                <input type="checkbox" name="tiwsc_free_sample" value="yes" <?php checked($enabled, 'yes'); ?> />
                <?php _e('Show "Free Colour Sample" button for this product', 'free-colour-samples'); ?>
            </label>
            <?php
        },
        'product',
        'side'
    );
});

add_action('save_post_product', function($post_id) {
    if (isset($_POST['tiwsc_free_sample'])) {
        update_post_meta($post_id, '_tiwsc_free_sample', 'yes');
    } else {
        delete_post_meta($post_id, '_tiwsc_free_sample');
    }
});

function tiwsc_safe_session_start() {
    if (!session_id() && !headers_sent()) {
        session_start();
    }
    if (!isset($_SESSION['tiwsc_samples'])) {
        $_SESSION['tiwsc_samples'] = [];
    }
}

add_action('woocommerce_after_shop_loop_item', function() {
    if (!function_exists('tiwsc_is_enabled') || !tiwsc_is_enabled()) return;
    global $product;
    $product_id = $product->get_id();
    if (get_post_meta($product_id, '_tiwsc_free_sample', true) !== 'yes') return;
    tiwsc_safe_session_start();
    $added = (isset($_SESSION['tiwsc_samples']) && in_array($product_id, $_SESSION['tiwsc_samples']));
    ?>
    <a href="#" class="tiwsc-free-sample-link<?php if($added) echo ' tiwsc-added'; ?>" data-product-id="<?php echo esc_attr($product_id); ?>" style="display:inline-block;margin:10px 0 0 0;">
        <span style="vertical-align:middle;">
            <?php if($added): ?>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="28" height="28">
                    <path d="m6,19c0,.552-.448,1-1,1s-1-.448-1-1,.448-1,1-1,1,.448,1,1Z" fill="#88ae98"/>
                    <path d="M24,14v10H5c-2.757,0-5-2.243-5-5V0h10v6.929l4.899-4.9,7.071,7.071-4.899,4.899h6.929ZM9.95,19.708l10.607-10.607-5.657-5.657-4.899,4.9v10.657c0,.24-.017.476-.05.708ZM1,6h8V1H1v5Zm0,6h8v-5H1v5Zm8,7v-6H1v6c0,2.209,1.791,4,4,4s4-1.791,4-4Zm14-4h-6.929l-7.536,7.536c-.169.169-.347.323-.535.464h14.999v-8Z" fill="#222"/>
                </svg>
            <?php else: ?>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="28" height="28">
                    <path d="m6,19c0,.552-.448,1-1,1s-1-.448-1-1,.448-1,1-1,1,.448,1,1Z" fill="none" stroke="#222"/>
                    <path d="M24,14v10H5c-2.757,0-5-2.243-5-5V0h10v6.929l4.899-4.9,7.071,7.071-4.899,4.899h6.929ZM9.95,19.708l10.607-10.607-5.657-5.657-4.899,4.9v10.657c0,.24-.017.476-.50.708ZM1,6h8V1H1v5Zm0,6h8v-5H1v5Zm8,7v-6H1v6c0,2.209,1.791,4,4,4s4-1.791,4-4Zm14-4h-6.929l-7.536,7.536c-.169.169-.347.323-.535.464h14.999v-8Z" fill="#222"/>
                </svg>
            <?php endif; ?>
        </span>
        <span class="tiwsc-free-sample-text" style="margin-left:5px;">
            <?php echo $added ? __('Added', 'free-colour-samples') : __('Gratis Kleurstaal', 'free-colour-samples'); ?>
        </span>
    </a>
    <?php
}, 25);

// Helper function to find kleur attribute in variable products
function tiwsc_get_kleur_attribute($product) {
    if (!$product || !$product->is_type('variable')) {
        return false;
    }
    
    $attributes = $product->get_attributes();
    // Acceptable labels/slugs that represent a colour attribute in different languages
    $acceptable_labels = array('kleur', 'color', 'colour');
    $acceptable_slugs  = array('pa_kleur', 'pa_color', 'pa_colour', 'kleur', 'color', 'colour');

    foreach ($attributes as $attr_name => $attr_obj) {
        // Get the human readable label
        $label = $attr_obj->is_taxonomy() ? wc_attribute_label($attr_obj->get_taxonomy()) : $attr_obj->get_name();
        $label_normalized = strtolower($label);

        // Determine the slug that WooCommerce uses internally
        $slug = $attr_obj->is_taxonomy() ? $attr_obj->get_taxonomy() : sanitize_title($label);
        $slug_normalized = strtolower($slug);

        if (in_array($label_normalized, $acceptable_labels) || in_array($slug_normalized, $acceptable_slugs)) {
            // Return the taxonomy slug if available, otherwise the generated slug
            return $attr_obj->is_taxonomy() ? $attr_obj->get_taxonomy() : sanitize_title($label);
        }
    }

    return false;
}

// Add color sample buttons for variable products
add_action('woocommerce_after_variations_table', function() {
    if (!tiwsc_is_enabled()) return;
    global $product;
    
    if (!$product || !$product->is_type('variable')) return;
    if (get_post_meta($product->get_id(), '_tiwsc_free_sample', true) !== 'yes') return;
    
    $color_attr_slug = tiwsc_get_kleur_attribute($product);
    if (!$color_attr_slug) return;
    
    $product_id = $product->get_id();
    $color_terms = wc_get_product_terms($product_id, $color_attr_slug, array('fields' => 'all'));
    
    if (empty($color_terms)) return;
    
    tiwsc_safe_session_start();
    $samples = isset($_SESSION['tiwsc_samples']) ? $_SESSION['tiwsc_samples'] : [];
    
    ?>
    <div class="tiwsc-color-sample-buttons" style="margin-top: 20px; padding: 15px; background: #f9f9f9; border-radius: 5px;">
        <button type="button"
                class="tiwsc-variable-sample-main-button"
                data-product-id="<?php echo esc_attr($product_id); ?>"
                data-attribute-name="<?php echo esc_attr($color_attr_slug); ?>"
                style="padding: 12px 20px; border: 1px solid #ddd; background: white; cursor: pointer; display: inline-flex; align-items: center; font-weight:600; transition: all 0.2s;">
            <span style="margin-right: 8px; display:inline-flex;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18">
                    <path d="m6,19c0,.552-.448,1-1,1s-1-.448-1-1,.448-1,1-1,1,.448,1,1Z" fill="none" stroke="#333" />
                    <path d="M24,14v10H5c-2.757,0-5-2.243-5-5V0h10v6.929l4.899-4.9,7.071,7.071-4.899,4.899h6.929ZM9.95,19.708l10.607-10.607-5.657-5.657-4.899,4.9v10.657c0,.24-.017.476-.05.708ZM1,6h8V1H1v5Zm0,6h8v-5H1v5Zm8,7v-6H1v6c0,2.209,1.791,4,4,4s4-1.791,4-4Zm14-4h-6.929l-7.536,7.536c-.169.169-.347.323-.535.464h14.999v-8Z" fill="#333" />
                </svg>
            </span>
            <span class="tiwsc-button-text"><?php _e('Gratis Kleurstaal', 'free-colour-samples'); ?></span>
        </button>
    </div>
    <style>
    .tiwsc-variable-sample-main-button:hover:not(.tiwsc-added) {
        background: #88ae98 !important;
        color: white !important;
        border-color: #88ae98 !important;
    }
    .tiwsc-variable-sample-main-button:hover:not(.tiwsc-added) svg path {
        stroke: white !important;
        fill: white !important;
    }
    .tiwsc-variable-sample-main-button.tiwsc-added {
        background: #f0f0f0;
        opacity: 0.7;
        cursor: default;
    }
    </style>
    <?php
});

// Add button to single product page next to Add to Cart (for simple products)
add_action('woocommerce_after_add_to_cart_button', function() {
    if (!tiwsc_is_enabled()) return;
    global $product;
    
    // Skip if this is a variable product (handled by woocommerce_after_variations_table)
    if ($product && $product->is_type('variable')) return;
    
    $product_id = $product->get_id();
    if (get_post_meta($product_id, '_tiwsc_free_sample', true) !== 'yes') return;
    tiwsc_safe_session_start();
    $added = (isset($_SESSION['tiwsc_samples']) && in_array($product_id, $_SESSION['tiwsc_samples']));
    ?>
    <div class="tiwsc-product-page-button" style="margin-top: 20px;">
        <a href="#" class="tiwsc-free-sample-link<?php if($added) echo ' tiwsc-added'; ?>" data-product-id="<?php echo esc_attr($product_id); ?>" style="display:inline-flex;align-items:center;text-decoration:none;font-weight:600;font-size:18px;line-height:27px;color:#333;transition:color 0.2s;">
            <span style="margin-right:8px;display:flex;align-items:center;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18">
                    <?php if($added): ?>
                        <path d="m6,19c0,.552-.448,1-1,1s-1-.448-1-1,.448-1,1-1,1,.448,1,1Z" fill="#88ae98"/>
                        <path d="M24,14v10H5c-2.757,0-5-2.243-5-5V0h10v6.929l4.899-4.9,7.071,7.071-4.899,4.899h6.929ZM9.95,19.708l10.607-10.607-5.657-5.657-4.899,4.9v10.657c0,.24-.017.476-.05.708ZM1,6h8V1H1v5Zm0,6h8v-5H1v5Zm8,7v-6H1v6c0,2.209,1.791,4,4,4s4-1.791,4-4Zm14-4h-6.929l-7.536,7.536c-.169.169-.347.323-.535.464h14.999v-8Z" fill="#333"/>
                    <?php else: ?>
                        <path d="m6,19c0,.552-.448,1-1,1s-1-.448-1-1,.448-1,1-1,1,.448,1,1Z" fill="none" stroke="#333"/>
                        <path d="M24,14v10H5c-2.757,0-5-2.243-5-5V0h10v6.929l4.899-4.9,7.071,7.071-4.899,4.899h6.929ZM9.95,19.708l10.607-10.607-5.657-5.657-4.899,4.9v10.657c0,.24-.017.476-.05.708ZM1,6h8V1H1v5Zm0,6h8v-5H1v5Zm8,7v-6H1v6c0,2.209,1.791,4,4,4s4-1.791,4-4Zm14-4h-6.929l-7.536,7.536c-.169.169-.347.323-.535.464h14.999v-8Z" fill="#333"/>
                    <?php endif; ?>
                </svg>
            </span>
            <span class="tiwsc-free-sample-text">
                <?php echo $added ? __('Added', 'free-colour-samples') : __('Kleurstaal aanvragen', 'free-colour-samples'); ?>
            </span>
        </a>
    </div>
    <style>
    .tiwsc-product-page-button .tiwsc-free-sample-link:hover {
        color: #88ae98 !important;
    }
    .tiwsc-product-page-button .tiwsc-free-sample-link:hover svg path {
        stroke: #88ae98 !important;
        fill: #88ae98 !important;
    }
    .tiwsc-product-page-button .tiwsc-added {
        opacity: 0.7;
        pointer-events: none;
    }
    </style>
    <?php
});

// Add sidebar links to more locations for testing
add_action('wp_footer', function() {
    if (!tiwsc_is_enabled()) return;
    ?>
    <!-- General sidebar link for all pages -->
    <div class="tiwsc-global-sidebar-link" style="margin:20px 0;text-align:center;background:#f0f0f0;padding:10px;">
        <a href="#" class="tiwsc-open-sidebar-link" style="display:inline-block;color:#333;text-decoration:none;">
            <span style="vertical-align:middle;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20">
                    <path d="m6,19c0,.552-.448,1-1,1s-1-.448-1-1,.448-1,1-1,1,.448,1,1Z" fill="none" stroke="#222"/>
                    <path d="M24,14v10H5c-2.757,0-5-2.243-5-5V0h10v6.929l4.899-4.9,7.071,7.071-4.899,4.899h6.929ZM9.95,19.708l10.607-10.607-5.657-5.657-4.899,4.9v10.657c0,.24-.017.476-.05.708ZM1,6h8V1H1v5Zm0,6h8v-5H1v5Zm8,7v-6H1v6c0,2.209,1.791,4,4,4s4-1.791,4-4Zm14-4h-6.929l-7.536,7.536c-.169.169-.347.323-.535.464h14.999v-8Z" fill="#222"/>
                </svg>
            </span>
            <span style="margin-left:5px;"><?php _e('Gratis Kleurstalen', 'free-colour-samples'); ?></span>
        </a>
    </div>
    <?php
}, 5); // Higher priority to load before the sidebar elements

add_action('woocommerce_after_shop_loop', function() {
    if (!tiwsc_is_enabled()) return;
    ?>
    <div class="tiwsc-global-sidebar-link" style="margin:20px 0;text-align:center;">
        <a href="#" class="tiwsc-open-sidebar-link" style="display:inline-block;">
            <span style="vertical-align:middle;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20">
                    <path d="m6,19c0,.552-.448,1-1,1s-1-.448-1-1,.448-1,1-1,1,.448,1,1Z" fill="none" stroke="#222"/>
                    <path d="M24,14v10H5c-2.757,0-5-2.243-5-5V0h10v6.929l4.899-4.9,7.071,7.071-4.899,4.899h6.929ZM9.95,19.708l10.607-10.607-5.657-5.657-4.899,4.9v10.657c0,.24-.017.476-.05.708ZM1,6h8V1H1v5Zm0,6h8v-5H1v5Zm8,7v-6H1v6c0,2.209,1.791,4,4,4s4-1.791,4-4Zm14-4h-6.929l-7.536,7.536c-.169.169-.347.323-.535.464h14.999v-8Z" fill="#222"/>
                </svg>
            </span>
            <span style="margin-left:5px;"><?php _e('Gratis Kleurstalen', 'free-colour-samples'); ?></span>
        </a>
    </div>
    <?php
}, 20);

add_action('woocommerce_after_single_product_summary', function() {
    if (!tiwsc_is_enabled()) return;
    ?>
    <div class="tiwsc-global-sidebar-link" style="margin:20px 0;text-align:center;">
        <a href="#" class="tiwsc-open-sidebar-link" style="display:inline-block;">
            <span style="vertical-align:middle;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20">
                    <path d="m6,19c0,.552-.448,1-1,1s-1-.448-1-1,.448-1,1-1,1,.448,1,1Z" fill="none" stroke="#222"/>
                    <path d="M24,14v10H5c-2.757,0-5-2.243-5-5V0h10v6.929l4.899-4.9,7.071,7.071-4.899,4.899h6.929ZM9.95,19.708l10.607-10.607-5.657-5.657-4.899,4.9v10.657c0,.24-.017.476-.05.708ZM1,6h8V1H1v5Zm0,6h8v-5H1v5Zm8,7v-6H1v6c0,2.209,1.791,4,4,4s4-1.791,4-4Zm14-4h-6.929l-7.536,7.536c-.169.169-.347.323-.535.464h14.999v-8Z" fill="#222"/>
                </svg>
            </span>
            <span style="margin-left:5px;"><?php _e('Gratis Kleurstalen', 'free-colour-samples'); ?></span>
        </a>
    </div>
    <?php
}, 20);

function tiwsc_toggle_sample_callback() {
    if (!tiwsc_is_enabled()) wp_send_json(['added' => false, 'limit' => false, 'not_allowed' => true]);
    
    $product_id = intval($_POST['product_id']);
    $attribute_name = isset($_POST['attribute']) ? sanitize_text_field($_POST['attribute']) : '';
    $attribute_value = isset($_POST['value']) ? sanitize_text_field($_POST['value']) : '';
    
    if (get_post_meta($product_id, '_tiwsc_free_sample', true) !== 'yes') {
        wp_send_json(['added' => false, 'limit' => false, 'not_allowed' => true]);
    }
    
    // Ensure session is started safely
    tiwsc_safe_session_start();
    
    $samples = $_SESSION['tiwsc_samples'];
    
    // For variable products, create a unique key with product|attribute|value
    // For simple products, just use the product ID
    if (!empty($attribute_name) && !empty($attribute_value)) {
        $sample_key = $product_id . '|' . $attribute_name . '|' . $attribute_value;
    } else {
        $sample_key = $product_id;
    }
    
    if (in_array($sample_key, $samples)) {
        // Remove sample
        $_SESSION['tiwsc_samples'] = array_values(array_diff($samples, [$sample_key]));
        wp_send_json(['added' => false, 'limit' => false]);
    } else {
        // Add sample
        if (count($samples) >= 5) {
            wp_send_json(['added' => false, 'limit' => true, 'message' => __('Je kunt maximaal 5 kleurstalen selecteren.', 'free-colour-samples')]);
        }
        $_SESSION['tiwsc_samples'][] = $sample_key;
        $_SESSION['tiwsc_samples'] = array_unique($_SESSION['tiwsc_samples']); // Remove duplicates
        $_SESSION['tiwsc_samples'] = array_values($_SESSION['tiwsc_samples']); // Re-index
        wp_send_json(['added' => true, 'limit' => false]);
    }
}
add_action('wp_ajax_tiwsc_toggle_sample', 'tiwsc_toggle_sample_callback');
add_action('wp_ajax_nopriv_tiwsc_toggle_sample', 'tiwsc_toggle_sample_callback');

function tiwsc_remove_sample_callback() {
    if (!tiwsc_is_enabled()) wp_send_json(['removed' => false]);
    
    $product_id = intval($_POST['product_id']);
    $sample_key = isset($_POST['sample_key']) ? sanitize_text_field($_POST['sample_key']) : $product_id;
    
    // Ensure session is started
    if (!session_id()) session_start();
    
    if (isset($_SESSION['tiwsc_samples'])) {
        $_SESSION['tiwsc_samples'] = array_values(array_diff($_SESSION['tiwsc_samples'], [$sample_key]));
    }
    wp_send_json(['removed' => true]);
}
add_action('wp_ajax_tiwsc_remove_sample', 'tiwsc_remove_sample_callback');
add_action('wp_ajax_nopriv_tiwsc_remove_sample', 'tiwsc_remove_sample_callback');

function tiwsc_get_sidebar_callback() {
    if (!tiwsc_is_enabled()) wp_die();
    if (!session_id()) session_start();
    $samples = isset($_SESSION['tiwsc_samples']) ? $_SESSION['tiwsc_samples'] : [];
    echo '<div class="tiwsc-sidebar-wrapper">';
    if ($samples) {
        echo '<div class="tiwsc-selected-samples-list">';
        echo '<h2 style="margin-top:0;">' . __('Geselecteerde Kleurstalen', 'free-colour-samples') . '</h2>';
        echo '<style>.tiwsc-color-chip{display:inline-block;width:40px;height:40px;margin-right:16px;border:1px solid #ddd;vertical-align:middle;border-radius:4px;background-size:cover;background-position:center;flex-shrink:0;}</style>';
        foreach ($samples as $sample_key) {
            // Parse the sample key
            if (strpos($sample_key, '|') !== false) {
                // Variable product: product_id|attribute|value
                list($product_id, $attribute, $value) = explode('|', $sample_key);
                $product = wc_get_product($product_id);
                if (!$product) continue;
                
                // Get the color term name
                $term = get_term_by('slug', $value, $attribute);
                $color_name = $term ? $term->name : $value;
                $title = $product->get_title() . ' - ' . $color_name;

                // Build a colour/image chip for the sidebar list
                $chip_html = '';
                if ( $term ) {
                    // Check for variation swatches plugin meta keys first
                    $hex = get_term_meta( $term->term_id, 'cfvsw_color', true );
                    $image_url = get_term_meta( $term->term_id, 'cfvsw_image', true );
                    
                    if ( $hex ) {
                        $chip_html = '<span class="tiwsc-color-chip" style="background:' . esc_attr( $hex ) . ';"></span>';
                    } else if ( $image_url ) {
                        $chip_html = '<span class="tiwsc-color-chip" style="background-image:url(' . esc_url( $image_url ) . ');"></span>';
                    } else {
                        // Fallback to other common meta keys
                        $possible_keys = array( 'color', 'product_attribute_color', 'taxonomy_color' );
                        foreach ( $possible_keys as $k ) {
                            $hex = get_term_meta( $term->term_id, $k, true );
                            if ( ! empty( $hex ) ) {
                                $chip_html = '<span class="tiwsc-color-chip" style="background:' . esc_attr( $hex ) . ';"></span>';
                                break;
                            }
                        }
                        
                        if ( ! $chip_html ) {
                            $thumb_id = get_term_meta( $term->term_id, 'thumbnail_id', true );
                            if ( $thumb_id ) {
                                $thumb_url = wp_get_attachment_image_url( $thumb_id, 'thumbnail' );
                                if ( $thumb_url ) {
                                    $chip_html = '<span class="tiwsc-color-chip" style="background-image:url(' . esc_url( $thumb_url ) . ');"></span>';
                                }
                            }
                        }
                    }
                }
            } else {
                // Simple product
                $product_id = $sample_key;
                $product = wc_get_product($product_id);
                if (!$product) continue;
                $title = $product->get_title();
            }
            
            $image = get_the_post_thumbnail($product_id, 'thumbnail', [
                'style' => 'width:56px;height:56px;object-fit:cover;vertical-align:middle;'
            ]);
            echo '<div class="sample-prodcuct-img">';
            echo '<div class="sample-prodcuct-img-wrapper">';
            echo '<div class="tiwsc-item-left-content">';
            echo $image;
            echo $chip_html;
            echo '<span class="tiwsc-item-title">' . esc_html($title) . '</span>';
            echo '</div>';
            echo '<a href="#" class="tiwsc-remove-sample" data-product-id="' . esc_attr($product_id) . '" data-sample-key="' . esc_attr($sample_key) . '" style="text-decoration:none;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                  </a>';
            echo '</div>';
            echo '</div>';
        }
        echo '<button class="add-more-clr-btn tiwsc-close-trigger">' . __('Voeg meer kleurstalen toe', 'free-colour-samples') . '</button>';
        echo '</div>'; 
    } else {
        echo '<div class="no-product-select"><h3>' . __('Nog geen kleurstalen geselecteerd.', 'free-colour-samples') . '</h3></div>';
    }
    echo '<div class="tiwsc-form-section">';
    echo '<div class="tiwsc-form-section-wrapper">';
    echo '<h3 style="margin-bottom:16px;color:#fff;padding-left:30px;padding-right:30px;">' . __('Aanvraag voltooien', 'free-colour-samples') . '</h3>';
    echo '<form id="tiwsc-sample-form">';
    echo '<div class="form-group"><label>' . __('Voornaam*', 'free-colour-samples') . '</label><input type="text" name="first_name" required></div>';
    echo '<div class="form-group"><label>' . __('Achternaam*', 'free-colour-samples') . '</label><input type="text" name="surname" required></div>';
    echo '<div class="form-group"><label>' . __('E-mailadres*', 'free-colour-samples') . '</label><input type="email" name="email" required></div>';
    echo '<div class="form-group"><label>' . __('Telefoonnummer*', 'free-colour-samples') . '</label><input type="text" name="phone_number" required></div>';
    echo '<div class="form-group"><label>' . __('Straatnaam*', 'free-colour-samples') . '</label><input type="text" name="street_name" required></div>';
    echo '<div class="double-container">';
        echo '<div class="form-group house-number"><label>' . __('Huisnummer*', 'free-colour-samples') . '</label><input type="text" name="house_number" required></div>';
        echo '<div class="form-group house-addition"><label>' . __('Toevoeging', 'free-colour-samples') . '</label><input type="text" name="house_addition"></div>';
    echo '</div>';
    echo '<div class="form-group"><label>' . __('Postcode*', 'free-colour-samples') . '</label><input type="text" name="postal_code" required></div>';
    echo '<div class="form-group"><label>' . __('Woonplaats*', 'free-colour-samples') . '</label><input type="text" name="place_of_residence" required></div>';
    echo '<div class="form-group"><label>' . __('Land', 'free-colour-samples') . '</label><select name="country" required>
            <option value="">' . __('Selecteer Land *', 'free-colour-samples') . '</option>
            <option value="Netherlands">' . __('Nederland', 'free-colour-samples') . '</option>
            <option value="Belgium">' . __('België', 'free-colour-samples') . '</option>
          </select>
          </div>';
    echo '<div class="tiwsc-form-bottom-section">';
    echo '<ul>
            <li>' . __('Binnen 1 werkdag verzonden', 'free-colour-samples') . '</li>
        </ul>';
    echo '<button type="submit"><span><img width="auto" height="auto" src="' . plugins_url('assets/images/arrow_black_right_small.png', __FILE__) . '" alt="" /></span>' . __('Nu aanvragen', 'free-colour-samples') . '</button>';
    echo '</form>';
    echo '<div id="tiwsc-sample-form-result"></div>';
    echo '</div>';
    echo '</div>';
    echo '<div class="terms">' . sprintf(__('Door verder te gaan ga je akkoord met onze %s', 'free-colour-samples'), '<a href="/algemene-voorwaarden" target="_blank">' . __('Algemene Voorwaarden', 'free-colour-samples') . '</a>') . '</div>';
    echo '</div>';
    wp_die();
}
add_action('wp_ajax_tiwsc_get_sidebar', 'tiwsc_get_sidebar_callback');
add_action('wp_ajax_nopriv_tiwsc_get_sidebar', 'tiwsc_get_sidebar_callback');

// Function to send HTML emails
function tiwsc_send_html_email($to, $subject, $html_body, $from_name = '', $from_email = '') {
    // Set up headers
    $headers = array();
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    
    if ($from_name && $from_email) {
        $headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';
    }
    
    // Temporarily set mail content type to HTML
    add_filter('wp_mail_content_type', function() {
        return 'text/html';
    });
    
    // Send the email
    $result = wp_mail($to, $subject, $html_body, $headers);
    
    // Reset content type
    remove_filter('wp_mail_content_type', function() {
        return 'text/html';
    });
    
    return $result;
}

function tiwsc_submit_sample_form_callback() {
    if (!tiwsc_is_enabled()) wp_die();
    tiwsc_safe_session_start();
    $samples = isset($_SESSION['tiwsc_samples']) ? $_SESSION['tiwsc_samples'] : [];
    $first_name = sanitize_text_field($_POST['first_name']);
    $surname = sanitize_text_field($_POST['surname']);
    $email = sanitize_email($_POST['email']);
    $country = sanitize_text_field($_POST['country']);
    $postal_code = sanitize_text_field($_POST['postal_code']);
    $house_number = sanitize_text_field($_POST['house_number']);
    $house_addition = sanitize_text_field($_POST['house_addition']);
    $street_name = sanitize_text_field($_POST['street_name']);
    $place_of_residence = sanitize_text_field($_POST['place_of_residence']);
    $phone_number = sanitize_text_field($_POST['phone_number']);

    $errors = [];
    $field_labels = [
        'first_name' => __('Voornaam', 'free-colour-samples'),
        'surname' => __('Achternaam', 'free-colour-samples'),
        'email' => __('E-mailadres', 'free-colour-samples'),
        'country' => __('Land', 'free-colour-samples'),
        'postal_code' => __('Postcode', 'free-colour-samples'),
        'house_number' => __('Huisnummer', 'free-colour-samples'),
        'street_name' => __('Straatnaam', 'free-colour-samples'),
        'place_of_residence' => __('Woonplaats', 'free-colour-samples'),
        'phone_number' => __('Telefoonnummer', 'free-colour-samples')
    ];
    
    foreach ($field_labels as $field => $label) {
        // Validate the actual user-submitted data rather than the field key name.
        if (empty($_POST[$field])) {
            $errors[] = $label . ' ' . __('is verplicht.', 'free-colour-samples');
        }
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = __('Ongeldig e-mailadres.', 'free-colour-samples');
    }
    if (!empty($errors)) {
        echo '<div style="color:#d00;">' . implode('<br>', $errors) . '</div>';
        wp_die();
    }

    // Build address with house addition if provided
    $full_house_number = $house_number;
    if (!empty($house_addition)) {
        $full_house_number .= ' ' . $house_addition;
    }

    $body = __('Voornaam', 'free-colour-samples') . ": $first_name\n" .
            __('Achternaam', 'free-colour-samples') . ": $surname\n" .
            __('E-mailadres', 'free-colour-samples') . ": $email\n" .
            __('Telefoonnummer', 'free-colour-samples') . ": $phone_number\n" .
            __('Straatnaam', 'free-colour-samples') . ": $street_name\n" .
            __('Huisnummer', 'free-colour-samples') . ": $full_house_number\n" .
            __('Postcode', 'free-colour-samples') . ": $postal_code\n" .
            __('Woonplaats', 'free-colour-samples') . ": $place_of_residence\n" .
            __('Land', 'free-colour-samples') . ": $country\n\n" .
            __('Geselecteerde Kleurstalen:', 'free-colour-samples') . "\n";
    
    foreach ($samples as $sample_key) {
        if (strpos($sample_key, '|') !== false) {
            // Variable product: product_id|attribute|value
            list($product_id, $attribute, $value) = explode('|', $sample_key);
            $product = wc_get_product($product_id);
            if ($product) {
                $term = get_term_by('slug', $value, $attribute);
                $color_name = $term ? $term->name : $value;
                $body .= "- " . $product->get_title() . " - " . $color_name . "\n";
            }
        } else {
            // Simple product
            $product = wc_get_product($sample_key);
            if ($product) $body .= "- " . $product->get_title() . "\n";
        }
    }

    $admin_email = get_option('tiwsc_admin_email', get_option('admin_email'));
    wp_mail($admin_email, __('Nieuwe Kleurstaal Aanvraag', 'free-colour-samples'), $body);

    $user_subject = __('Je Kleurstaal Aanvraag Ontvangen', 'free-colour-samples');
    $user_message = sprintf(__('Beste %s %s,', 'free-colour-samples'), $first_name, $surname) . "\n\n" .
                   __('Bedankt voor je aanvraag. We hebben de volgende informatie ontvangen:', 'free-colour-samples') . "\n\n" .
                   $body . "\n" .
                   __('We sturen de kleurstalen zo spoedig mogelijk op.', 'free-colour-samples') . "\n\n" .
                   __('Met vriendelijke groet,', 'free-colour-samples') . "\n" .
                   __('Het Team', 'free-colour-samples');
    wp_mail($email, $user_subject, $user_message);

    $_SESSION['tiwsc_samples'] = [];
    echo __('Bedankt voor je aanvraag! We sturen de kleurstalen zo spoedig mogelijk op. Een bevestigingsmail is naar je verstuurd.', 'free-colour-samples');
    wp_die();
}
add_action('wp_ajax_tiwsc_submit_sample_form', 'tiwsc_submit_sample_form_callback');
add_action('wp_ajax_nopriv_tiwsc_submit_sample_form', 'tiwsc_submit_sample_form_callback');

add_action('wp_enqueue_scripts', function() {
    if (!tiwsc_is_enabled()) return;
    wp_enqueue_style(
        'tiwsc-style',
        plugins_url('assets/css/tiwsc-style.css', __FILE__),
        [],
        '1.1.0'
    );
    wp_enqueue_script(
        'tiwsc-script',
        plugins_url('assets/js/tiwsc-script.js', __FILE__),
        ['jquery'],
        '1.1.1',
        true
    );
    wp_localize_script('tiwsc-script', 'tiwsc_ajax', [
        'ajax_url' => admin_url('admin-ajax.php')
    ]);
});

// Add floating test button for debugging
add_action('wp_footer', function() {
    if (!tiwsc_is_enabled()) return;
    ?>
    <!-- Floating test button for debugging -->
    <div style="position:fixed;bottom:20px;right:20px;z-index:999999;">
        <button class="tiwsc-open-sidebar-link" style="background:#88ae98;color:white;padding:15px;border:none;border-radius:5px;cursor:pointer;font-size:16px;box-shadow:0 2px 10px rgba(0,0,0,0.3);">
            🎨 Test Sidebar
        </button>
    </div>
    
    <div id="tiwsc-sidebar-overlay"></div>
    <div id="tiwsc-sidebar">
        <div class="tiwsc-sidebar-header" style="position:sticky;top:0;z-index:100000;">
            <button class="sidebar-close-btn tiwsc-close-trigger" id="tiwsc-sidebar-header-close">×</button>
            <img width="auto" height="auto" src="https://rolenhor.nl/wp-content/uploads/2025/04/Rol-hor-logo-wit.png.webp" alt="" />
        </div>
        <div id="tiwsc-sidebar-content"></div>
    </div>
    <!-- Styles moved to assets/css/tiwsc-style.css -->
    <?php
});

// Shortcode for easy placement of sidebar trigger in theme header or elsewhere
add_shortcode('tiwsc_sidebar_trigger', function($atts){
    if (!tiwsc_is_enabled()) return '';
    $defaults = array(
        'label' => __('Gratis Kleurstalen', 'free-colour-samples'),
    );
    $atts = shortcode_atts($defaults, $atts, 'tiwsc_sidebar_trigger');
    ob_start();
    ?>
    <a href="#" class="tiwsc-open-sidebar-link tiwsc-header-sidebar-trigger" style="display:inline-flex;align-items:center;gap:6px;">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18"><path d="m6,19c0,.552-.448,1-1,1s-1-.448-1-1,.448-1,1-1,1,.448,1,1Z" fill="none" stroke="#333"/><path d="M24,14v10H5c-2.757,0-5-2.243-5-5V0h10v6.929l4.899-4.9,7.071,7.071-4.899,4.899h6.929ZM9.95,19.708l10.607-10.607-5.657-5.657-4.899,4.9v10.657c0,.24-.017.476-.05.708ZM1,6h8V1H1v5Zm0,6h8v-5H1v5Zm8,7v-6H1v6c0,2.209,1.791,4,4,4s4-1.791,4-4Zm14-4h-6.929l-7.536,7.536c-.169.169-.347.323-.535.464h14.999v-8Z" fill="#333"/></svg>
        <span><?php echo esc_html($atts['label']); ?></span>
    </a>
    <?php
    return ob_get_clean();
});
?>