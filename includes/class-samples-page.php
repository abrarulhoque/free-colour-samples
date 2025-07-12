<?php
/**
 * Samples page functionality
 */

if (!defined('ABSPATH')) exit;

class TIWSC_Samples_Page {
    
    public function __construct() {
        add_shortcode('tiwsc_colour_samples_page', array($this, 'render_shortcode'));
        add_action('wp_ajax_tiwsc_get_samples_grid', array($this, 'ajax_get_samples_grid'));
        add_action('wp_ajax_nopriv_tiwsc_get_samples_grid', array($this, 'ajax_get_samples_grid'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }
    
    /**
     * Render the shortcode
     */
    public function render_shortcode($atts) {
        if (!tiwsc_is_enabled()) {
            return '';
        }
        
        ob_start();
        ?>
        <div id="tiwsc-samples-page" class="tiwsc-samples-page-container">
            <div class="tiwsc-samples-layout">
                <!-- Left panel: Filters -->
                <div class="tiwsc-filters-panel">
                    <h3><?php _e('Filter op kleur', 'free-colour-samples'); ?></h3>
                    <div class="tiwsc-filter-checkboxes">
                        <?php
                        $master_colours = TIWSC_Colour_Map::get_master_colours();
                        $colour_hex_map = array(
                            'antraciet' => '#3a3a3a',
                            'beige' => '#d4b896',
                            'blauw' => '#1e5aa8',
                            'bruin' => '#8b4513',
                            'geel' => '#ffd700',
                            'goud' => '#d4af37',
                            'grijs' => '#808080',
                            'groen' => '#228b22',
                            'oranje' => '#ff8c00',
                            'paars' => '#800080',
                            'rood' => '#dc143c',
                            'roze' => '#ffc0cb',
                            'wit' => '#ffffff',
                            'zwart' => '#000000'
                        );
                        foreach ($master_colours as $slug => $label) {
                            $hex = isset($colour_hex_map[$slug]) ? $colour_hex_map[$slug] : '#cccccc';
                            ?>
                            <label class="tiwsc-filter-checkbox">
                                <input type="checkbox" name="colour_filter[]" value="<?php echo esc_attr($slug); ?>" />
                                <span class="tiwsc-filter-color-dot" style="background-color: <?php echo esc_attr($hex); ?>; <?php echo $slug === 'wit' ? 'border: 1px solid #ddd;' : ''; ?>"></span>
                                <span class="tiwsc-filter-label"><?php echo esc_html($label); ?></span>
                            </label>
                            <?php
                        }
                        ?>
                    </div>
                    <button type="button" class="tiwsc-clear-filters"><?php _e('Filters wissen', 'free-colour-samples'); ?></button>
                </div>
                
                <!-- Right panel: Grid -->
                <div class="tiwsc-samples-grid-container">
                    <div class="tiwsc-grid-header">
                        <h2><?php _e('Gratis kleurstalen', 'free-colour-samples'); ?></h2>
                        <div class="tiwsc-sample-count">
                            <span class="tiwsc-count-number">0</span> <?php _e('kleurstalen gevonden', 'free-colour-samples'); ?>
                        </div>
                    </div>
                    <div id="tiwsc-samples-grid" class="tiwsc-samples-grid">
                        <div class="tiwsc-loading">
                            <div class="tiwsc-spinner"></div>
                            <p><?php _e('Kleurstalen laden...', 'free-colour-samples'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * AJAX handler to get samples grid
     */
    public function ajax_get_samples_grid() {
        if (!tiwsc_is_enabled()) {
            wp_die();
        }
        
        $filters = isset($_POST['filters']) ? array_map('sanitize_text_field', $_POST['filters']) : array();
        
        // Get all variable products with color samples enabled
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_tiwsc_free_sample',
                    'value' => 'yes',
                    'compare' => '='
                )
            )
        );
        
        $products = get_posts($args);
        $samples = array();
        
        foreach ($products as $product_post) {
            $product = wc_get_product($product_post->ID);
            
            if (!$product || !$product->is_type('variable')) {
                continue;
            }
            
            $color_attr_slug = tiwsc_get_kleur_attribute($product);
            if (!$color_attr_slug) {
                continue;
            }
            
            $color_terms = wc_get_product_terms($product->get_id(), $color_attr_slug, array('fields' => 'all'));
            
            foreach ($color_terms as $term) {
                $master_color = TIWSC_Colour_Map::normalize_colour($term->slug);
                
                // Apply filters
                if (!empty($filters) && !in_array($master_color, $filters)) {
                    continue;
                }
                
                // Get color swatch data
                $chip_html = '';
                $hex = get_term_meta($term->term_id, 'cfvsw_color', true);
                $image_url = get_term_meta($term->term_id, 'cfvsw_image', true);
                
                if ($hex) {
                    $chip_html = '<span class="tiwsc-grid-swatch" style="background:' . esc_attr($hex) . ';"></span>';
                } else if ($image_url) {
                    $chip_html = '<span class="tiwsc-grid-swatch" style="background-image:url(' . esc_url($image_url) . ');"></span>';
                } else {
                    // Fallback to product image
                    $product_image = get_the_post_thumbnail_url($product->get_id(), 'thumbnail');
                    if ($product_image) {
                        $chip_html = '<span class="tiwsc-grid-swatch" style="background-image:url(' . esc_url($product_image) . ');"></span>';
                    } else {
                        $chip_html = '<span class="tiwsc-grid-swatch" style="background:#ddd;"></span>';
                    }
                }
                
                $samples[] = array(
                    'product_id' => $product->get_id(),
                    'product_title' => $product->get_title(),
                    'color_name' => $term->name,
                    'color_slug' => $term->slug,
                    'attribute' => $color_attr_slug,
                    'master_color' => $master_color,
                    'chip_html' => $chip_html,
                    'sample_key' => $product->get_id() . '|' . $color_attr_slug . '|' . $term->slug
                );
            }
        }
        
        // Check current session for added samples
        if (!session_id()) {
            session_start();
        }
        $session_samples = isset($_SESSION['tiwsc_samples']) ? $_SESSION['tiwsc_samples'] : array();
        
        ob_start();
        if (empty($samples)) {
            ?>
            <div class="tiwsc-no-results">
                <p><?php _e('Geen kleurstalen gevonden met de geselecteerde filters.', 'free-colour-samples'); ?></p>
            </div>
            <?php
        } else {
            foreach ($samples as $sample) {
                $is_added = in_array($sample['sample_key'], $session_samples);
                ?>
                <div class="tiwsc-grid-item" data-master-color="<?php echo esc_attr($sample['master_color']); ?>">
                    <?php echo $sample['chip_html']; ?>
                    <div class="tiwsc-grid-item-info">
                        <h4><?php echo esc_html($sample['color_name']); ?></h4>
                        <p class="tiwsc-product-name"><?php echo esc_html($sample['product_title']); ?></p>
                        <button type="button" 
                                class="tiwsc-grid-sample-button <?php echo $is_added ? 'tiwsc-added' : ''; ?>"
                                data-product-id="<?php echo esc_attr($sample['product_id']); ?>"
                                data-attribute-name="<?php echo esc_attr($sample['attribute']); ?>"
                                data-attribute-value="<?php echo esc_attr($sample['color_slug']); ?>"
                                data-color-name="<?php echo esc_attr($sample['color_name']); ?>">
                            <span class="tiwsc-button-icon">+</span>
                            <?php echo $is_added ? __('Toegevoegd', 'free-colour-samples') : __('Toevoegen', 'free-colour-samples'); ?>
                        </button>
                    </div>
                </div>
                <?php
            }
        }
        
        $html = ob_get_clean();
        
        wp_send_json(array(
            'html' => $html,
            'count' => count($samples)
        ));
    }
    
    /**
     * Enqueue assets for the samples page
     */
    public function enqueue_assets() {
        if (!tiwsc_is_enabled()) {
            return;
        }
        
        global $post;
        if (!$post || !has_shortcode($post->post_content, 'tiwsc_colour_samples_page')) {
            return;
        }
        
        wp_enqueue_style(
            'tiwsc-samples-page',
            plugins_url('../assets/css/samples-page.css', __FILE__),
            array(),
            '1.0.0'
        );
        
        wp_enqueue_script(
            'tiwsc-samples-page',
            plugins_url('../assets/js/samples-page.js', __FILE__),
            array('jquery'),
            '1.0.0',
            true
        );
        
        wp_localize_script('tiwsc-samples-page', 'tiwsc_samples_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tiwsc_samples_nonce')
        ));
    }
}