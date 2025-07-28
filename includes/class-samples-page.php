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
        <script>
        (function () {
          function setTwoPaneHeight() {
            var wrap = document.querySelector('.tiwsc-samples-layout');
            if (!wrap) return;
            var available = window.innerHeight - wrap.getBoundingClientRect().top;
            wrap.style.setProperty('--tiwsc-two-pane-h', available + 'px');
          }
          window.addEventListener('load', setTwoPaneHeight);
          window.addEventListener('resize', setTwoPaneHeight);
          window.addEventListener('orientationchange', setTwoPaneHeight);
        })();
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * AJAX handler to get samples grid (Version 2 - Corrected Filtering)
     */
    public function ajax_get_samples_grid() {
        if (!tiwsc_is_enabled()) {
            wp_die();
        }

        $filters = isset($_POST['filters']) ? array_map('sanitize_text_field', $_POST['filters']) : array();
        $category_filters = isset($_POST['category_filters']) ? array_map('intval', $_POST['category_filters']) : array();

        /*
         * Query for all products that offer free samples. We will filter them by color in PHP,
         * as the color attribute taxonomies have diverse names and are not suitable for a single DB query.
         */
        $args = array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => array(
                array(
                    'key'     => '_tiwsc_free_sample',
                    'value'   => 'yes',
                    'compare' => '='
                )
            ),
        );

        // Category filtering can still happen at the DB level if selected.
        if (!empty($category_filters)) {
            $args['tax_query'] = array(
                array(
                    'taxonomy'         => 'product_cat',
                    'field'            => 'term_id',
                    'terms'            => $category_filters,
                    'operator'         => 'IN',
                    'include_children' => true,
                )
            );
        }

        $product_ids = get_posts($args);
        $product_groups = array();
        $total_samples = 0;

        foreach ($product_ids as $product_id) {
            $product = wc_get_product($product_id);

            if (!$product || !($product->is_type('variable') || $product->is_type('simple'))) {
                continue;
            }

            // Find the correct color attribute slug for this specific product
            $color_attr_slug = tiwsc_get_kleur_attribute($product);
            if (!$color_attr_slug) {
                continue;
            }

            $color_terms = wc_get_product_terms($product->get_id(), $color_attr_slug, array('fields' => 'all'));
            if (empty($color_terms)) {
                continue;
            }

            $filtered_colors = array();
            foreach ($color_terms as $term) {
                // Use our robust function to get the master color
                $master_color = TIWSC_Colour_Map::normalize_colour($term->slug);

                // **This is the reliable PHP-level filtering.**
                // If filters are active, only include colors that match.
                if (!empty($filters) && !in_array($master_color, $filters, true)) {
                    continue; // Skip this color if it doesn't match the filter
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
                    $chip_html = '<span class="tiwsc-color-chip" style="background:#ddd;"></span>'; // Fallback
                }

                $filtered_colors[] = array(
                    'color_name' => $term->name,
                    'color_slug' => $term->slug,
                    'chip_html'  => $chip_html,
                    'sample_key' => $product->get_id() . '|' . $color_attr_slug . '|' . $term->slug
                );
            }

            // Only add the product to our results if it has colors that passed the filter
            if (!empty($filtered_colors)) {
                $total_samples += count($filtered_colors);
                $price_html = $product->get_price_html() ?: '<span class="price">' . __('Prijs op aanvraag', 'free-colour-samples') . '</span>';

                $product_groups[$product_id] = array(
                    'product_id'    => $product->get_id(),
                    'product_title' => $product->get_title(),
                    'product_image' => get_the_post_thumbnail_url($product->get_id(), 'woocommerce_thumbnail'),
                    'product_url'   => get_permalink($product->get_id()),
                    'price_html'    => $price_html,
                    'attribute'     => $color_attr_slug,
                    'colors'        => $filtered_colors
                );
            }
        }

        // Resume session to check which samples are already added
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
            ?>
            <div class="tiwsc-samples-catalog-grid">
                <?php 
                foreach ($product_groups as $group) {
                    foreach ($group['colors'] as $color):
                        $is_added = in_array($color['sample_key'], $session_samples, true);
                ?>
                        <div class="tiwsc-sample-item" data-master-color="<?php echo esc_attr($color['master_color']); ?>">
                            <div class="tiwsc-sample-swatch">
                                <?php echo $color['chip_html']; ?>
                            </div>
                            <div class="tiwsc-sample-info">
                                <span class="tiwsc-sample-color-name"><?php echo esc_html($color['color_name']); ?></span>
                                <span class="tiwsc-sample-product-name"><?php echo esc_html($group['product_title']); ?></span>
                            </div>
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
                <?php 
                    endforeach;
                }
                ?>
            </div>
            <?php
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