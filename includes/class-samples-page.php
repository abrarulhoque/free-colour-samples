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
                    <!-- Mobile close button -->
                    <button type="button" class="tiwsc-mobile-filter-close" aria-label="Sluiten">&times;</button>
                    
                    <!-- Category Filter -->
                    <div class="tiwsc-filter-section">
                        <h3><?php _e('Categorie', 'free-colour-samples'); ?></h3>
                        <div class="tiwsc-category-checkboxes">
                            <?php
                            // Get all product categories
                            $product_categories = get_terms(array(
                                'taxonomy' => 'product_cat',
                                'orderby' => 'name',
                                'hide_empty' => true,
                                'exclude' => array(get_option('default_product_cat'))
                            ));
                            
                            foreach ($product_categories as $category) {
                                ?>
                                <label class="tiwsc-filter-checkbox">
                                    <input type="checkbox" name="category_filter[]" value="<?php echo esc_attr($category->term_id); ?>" />
                                    <span class="tiwsc-filter-label"><?php echo esc_html($category->name); ?></span>
                                </label>
                                <?php
                            }
                            ?>
                        </div>
                    </div>
                    
                    <!-- Color Filter -->
                    <div class="tiwsc-filter-section">
                        <h3><?php _e('Kleur', 'free-colour-samples'); ?></h3>
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
                        <!-- Mobile: filter toggle button -->
                        <button type="button" class="tiwsc-mobile-filter-toggle" aria-label="Filter">&#9776; <?php _e('Filter', 'free-colour-samples'); ?></button>
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
        $category_filters = isset($_POST['category_filters']) ? array_map('intval', $_POST['category_filters']) : array();
        
        /*
         * Build the product query so that as much filtering as possible is done by MySQL
         * instead of PHP. This dramatically improves performance for large catalogs.
         */
        $args = array(
            'post_type'      => 'product',
            'post_status'    => 'publish', // only published products
            'posts_per_page' => -1,
            'fields'         => 'ids',     // fetch only the IDs to keep memory footprint small
            'meta_query'     => array(
                array(
                    'key'     => '_tiwsc_free_sample',
                    'value'   => 'yes',
                    'compare' => '='
                )
            ),
        );

        // Prepare tax_query parts
        $tax_query = array();

        // 1. Category filtering
        if (!empty($category_filters)) {
            $tax_query[] = array(
                'taxonomy'         => 'product_cat',
                'field'            => 'term_id',
                'terms'            => $category_filters,
                'operator'         => 'IN',
                'include_children' => true,
            );
        }

        // 2. Master-colour filtering
        if (!empty($filters)) {
            // Convert master colours (rood, blauw …) to the actual term slugs used by products.
            $all_colour_mappings = TIWSC_Colour_Map::get_colour_mappings();
            $target_slugs        = array();
            foreach ($all_colour_mappings as $slug => $master) {
                if (in_array($master, $filters, true)) {
                    $target_slugs[] = $slug;
                }
            }

            if (!empty($target_slugs)) {
                // Accept a set of possible taxonomy names that may represent colour
                $colour_taxonomies = array('pa_kleur', 'pa_color', 'pa_colour', 'kleur', 'color', 'colour');

                // Build an OR group so a product matching ANY colour taxonomy with the slug qualifies
                $colour_group = array('relation' => 'OR');
                foreach ($colour_taxonomies as $tax) {
                    $colour_group[] = array(
                        'taxonomy' => $tax,
                        'field'    => 'slug',
                        'terms'    => $target_slugs,
                        'operator' => 'IN',
                    );
                }

                $tax_query[] = $colour_group;
            }
        }

        if (!empty($tax_query)) {
            // If we have more than one condition, wrap them with relation AND
            if (count($tax_query) > 1) {
                $tax_query = array_merge(array('relation' => 'AND'), $tax_query);
            }
            $args['tax_query'] = $tax_query;
        }
        
        $products = get_posts($args);
        $product_groups = array();
        $total_samples = 0;
        
        foreach ($products as $product_post) {
            // When 'fields' => 'ids' is used, get_posts returns integers
            $product_id = is_numeric($product_post) ? intval($product_post) : $product_post->ID;
            $product    = wc_get_product($product_id);
            
            // initialize variable to avoid undefined warnings in static analysis
            $color_attr_slug = '';
            
            if ( ! $product ) {
                continue;
            }
            
            // allow both variable and simple product types
            if ( ! $product->is_type( array( 'variable', 'simple' ) ) ) {
                continue;
            }
            
            $color_attr_slug = tiwsc_get_kleur_attribute($product);
            if (!$color_attr_slug) {
                continue;
            }
            
            $color_terms = wc_get_product_terms($product->get_id(), $color_attr_slug, array('fields' => 'all'));
            $filtered_colors = array();
            
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
                    $chip_html = '<span class="tiwsc-color-chip" style="background:' . esc_attr($hex) . ';"></span>';
                } else if ($image_url) {
                    $chip_html = '<span class="tiwsc-color-chip" style="background-image:url(' . esc_url($image_url) . ');"></span>';
                } else {
                    // Fallback to a simple colored square
                    $chip_html = '<span class="tiwsc-color-chip" style="background:#ddd;"></span>';
                }
                
                $filtered_colors[] = array(
                    'color_name' => $term->name,
                    'color_slug' => $term->slug,
                    'master_color' => $master_color,
                    'chip_html' => $chip_html,
                    'sample_key' => $product->get_id() . '|' . $color_attr_slug . '|' . $term->slug
                );
                $total_samples++;
            }
            
            // Only add product if it has colors after filtering
            if (!empty($filtered_colors)) {
                // Get product price
                $price_html = $product->get_price_html();
                if (empty($price_html)) {
                    $price_html = '<span class="price">' . __('Prijs op aanvraag', 'free-colour-samples') . '</span>';
                }
                
                $product_groups[] = array(
                    'product_id' => $product->get_id(),
                    'product_title' => $product->get_title(),
                    'product_image' => get_the_post_thumbnail_url($product->get_id(), 'woocommerce_thumbnail'),
                    'product_url' => get_permalink($product->get_id()),
                    'price_html' => $price_html,
                    'attribute' => $color_attr_slug,
                    'colors' => $filtered_colors
                );
            }
        }
        
        // Check current session for added samples
        if (!session_id()) {
            session_start();
        }
        $session_samples = isset($_SESSION['tiwsc_samples']) ? $_SESSION['tiwsc_samples'] : array();
        
        ob_start();
        if (empty($product_groups)) {
            ?>
            <div class="tiwsc-no-results">
                <p><?php _e('Geen kleurstalen gevonden met de geselecteerde filters.', 'free-colour-samples'); ?></p>
            </div>
            <?php
        } else {
            foreach ($product_groups as $group) {
                ?>
                <div class="tiwsc-product-card">
                    <div class="tiwsc-product-header">
                        <?php if ($group['product_image']): ?>
                            <img src="<?php echo esc_url($group['product_image']); ?>" alt="<?php echo esc_attr($group['product_title']); ?>" class="tiwsc-product-image">
                        <?php endif; ?>
                        <div class="tiwsc-product-info">
                            <h3 class="tiwsc-product-title">
                                <a href="<?php echo esc_url($group['product_url']); ?>" target="_blank">
                                    <?php echo esc_html($group['product_title']); ?>
                                </a>
                            </h3>
                            <div class="tiwsc-product-price"><?php echo $group['price_html']; ?></div>
                        </div>
                    </div>
                    <div class="tiwsc-color-grid">
                        <?php foreach ($group['colors'] as $color): 
                            $is_added = in_array($color['sample_key'], $session_samples);
                        ?>
                            <div class="tiwsc-color-item" data-master-color="<?php echo esc_attr($color['master_color']); ?>">
                                <?php echo $color['chip_html']; ?>
                                <span class="tiwsc-color-name"><?php echo esc_html($color['color_name']); ?></span>
                                <button type="button" 
                                        class="tiwsc-add-sample-btn <?php echo $is_added ? 'tiwsc-added' : ''; ?>"
                                        data-product-id="<?php echo esc_attr($group['product_id']); ?>"
                                        data-attribute-name="<?php echo esc_attr($group['attribute']); ?>"
                                        data-attribute-value="<?php echo esc_attr($color['color_slug']); ?>"
                                        data-color-name="<?php echo esc_attr($color['color_name']); ?>"
                                        title="<?php echo $is_added ? __('Toegevoegd aan kleurstalen', 'free-colour-samples') : __('Toevoegen aan kleurstalen', 'free-colour-samples'); ?>">
                                    <?php echo $is_added ? __('TOEGEVOEGD', 'free-colour-samples') : __('+ TOEVOEGEN', 'free-colour-samples'); ?>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php
            }
        }
        
        $html = ob_get_clean();
        
        wp_send_json(array(
            'html' => $html,
            'count' => $total_samples
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