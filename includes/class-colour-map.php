<?php
/**
 * Color mapping utility class
 * Maps various color names/slugs to 14 master categories
 */

if (!defined('ABSPATH')) exit;

class TIWSC_Colour_Map {
    
    /**
     * Get the master color categories
     */
    public static function get_master_colours() {
        return array(
            'antraciet' => __('Antraciet', 'free-colour-samples'),
            'beige' => __('Beige', 'free-colour-samples'),
            'blauw' => __('Blauw', 'free-colour-samples'),
            'bruin' => __('Bruin', 'free-colour-samples'),
            'geel' => __('Geel', 'free-colour-samples'),
            'goud' => __('Goud', 'free-colour-samples'),
            'grijs' => __('Grijs', 'free-colour-samples'),
            'groen' => __('Groen', 'free-colour-samples'),
            'oranje' => __('Oranje', 'free-colour-samples'),
            'paars' => __('Paars', 'free-colour-samples'),
            'rood' => __('Rood', 'free-colour-samples'),
            'roze' => __('Roze', 'free-colour-samples'),
            'wit' => __('Wit', 'free-colour-samples'),
            'zwart' => __('Zwart', 'free-colour-samples')
        );
    }
    
    /**
     * Get the color mapping array
     * Maps various color names/slugs to master categories
     */
    public static function get_colour_mappings() {
        return array(
            // Antraciet
            'antraciet' => 'antraciet',
            'anthracite' => 'antraciet',
            'charcoal' => 'antraciet',
            'anthraciet' => 'antraciet',
            'antraciet-50' => 'antraciet',
            
            // Beige
            'beige' => 'beige',
            'cream' => 'beige',
            'sand' => 'beige',
            'light-beige' => 'beige',
            'dark-beige' => 'beige',
            'taupe' => 'beige',
            'camel' => 'beige',
            'khaki' => 'beige',
            'ecru' => 'beige',
            'ivory' => 'beige',
            'cappuccino' => 'beige',
            'latte' => 'beige',
            'beige-001' => 'beige',
            'taupe-001' => 'beige',
            'caramel-001' => 'beige',
            
            // Blauw
            'blauw' => 'blauw',
            'blue' => 'blauw',
            'navy' => 'blauw',
            'royal-blue' => 'blauw',
            'light-blue' => 'blauw',
            'dark-blue' => 'blauw',
            'sky-blue' => 'blauw',
            'indigo' => 'blauw',
            'cobalt' => 'blauw',
            'steel-blue' => 'blauw',
            'midnight-blue' => 'blauw',
            'denim' => 'blauw',
            'turquoise' => 'blauw',
            'teal' => 'blauw',
            
            // Bruin
            'bruin' => 'bruin',
            'brown' => 'bruin',
            'chocolate' => 'bruin',
            'light-brown' => 'bruin',
            'dark-brown' => 'bruin',
            'chestnut' => 'bruin',
            'mocha' => 'bruin',
            'espresso' => 'bruin',
            'mahogany' => 'bruin',
            'walnut' => 'bruin',
            'hazelnut' => 'bruin',
            'bronze' => 'bruin',
            'rust' => 'bruin',
            'sienna' => 'bruin',
            'umber' => 'bruin',
            'chocolate-001' => 'bruin',
            'mokka-001' => 'bruin',
            
            // Geel
            'geel' => 'geel',
            'yellow' => 'geel',
            'gold' => 'geel',
            'mustard' => 'geel',
            'lemon' => 'geel',
            'honey' => 'geel',
            'amber' => 'geel',
            'golden' => 'geel',
            'saffron' => 'geel',
            'sunshine' => 'geel',
            'canary' => 'geel',
            
            // Goud
            'goud' => 'goud',
            'golden' => 'goud',
            'brass' => 'goud',
            'champagne' => 'goud',
            'metallic-gold' => 'goud',
            
            // Grijs
            'grijs' => 'grijs',
            'grey' => 'grijs',
            'gray' => 'grijs',
            'silver' => 'grijs',
            'light-grey' => 'grijs',
            'dark-grey' => 'grijs',
            'charcoal-grey' => 'grijs',
            'slate' => 'grijs',
            'ash' => 'grijs',
            'stone' => 'grijs',
            'pewter' => 'grijs',
            'smoke' => 'grijs',
            'graphite' => 'grijs',
            'steel' => 'grijs',
            'titanium' => 'grijs',
            'granite' => 'grijs',
            'iron-grey' => 'grijs',
            'pearl-grey' => 'grijs',
            'mouse-grey' => 'grijs',
            'dove-grey' => 'grijs',
            'grijs-beige' => 'grijs',
            'zand-beige' => 'grijs',
            
            // Groen
            'groen' => 'groen',
            'green' => 'groen',
            'olive' => 'groen',
            'forest-green' => 'groen',
            'light-green' => 'groen',
            'dark-green' => 'groen',
            'lime' => 'groen',
            'mint' => 'groen',
            'emerald' => 'groen',
            'jade' => 'groen',
            'sage' => 'groen',
            'pine' => 'groen',
            'moss' => 'groen',
            'army-green' => 'groen',
            'hunter-green' => 'groen',
            'sea-green' => 'groen',
            
            // Oranje
            'oranje' => 'oranje',
            'orange' => 'oranje',
            'tangerine' => 'oranje',
            'coral' => 'oranje',
            'peach' => 'oranje',
            'apricot' => 'oranje',
            'burnt-orange' => 'oranje',
            'terracotta' => 'oranje',
            'pumpkin' => 'oranje',
            'salmon' => 'oranje',
            
            // Paars
            'paars' => 'paars',
            'purple' => 'paars',
            'violet' => 'paars',
            'lavender' => 'paars',
            'plum' => 'paars',
            'magenta' => 'paars',
            'burgundy' => 'paars',
            'wine' => 'paars',
            'maroon' => 'paars',
            'eggplant' => 'paars',
            'mauve' => 'paars',
            'lilac' => 'paars',
            'orchid' => 'paars',
            
            // Rood
            'rood' => 'rood',
            'red' => 'rood',
            'crimson' => 'rood',
            'scarlet' => 'rood',
            'ruby' => 'rood',
            'cherry' => 'rood',
            'rose' => 'rood',
            'blood-red' => 'rood',
            'fire-red' => 'rood',
            'brick-red' => 'rood',
            'cardinal' => 'rood',
            'vermillion' => 'rood',
            
            // Roze
            'roze' => 'roze',
            'pink' => 'roze',
            'rose' => 'roze',
            'blush' => 'roze',
            'fuchsia' => 'roze',
            'hot-pink' => 'roze',
            'light-pink' => 'roze',
            'baby-pink' => 'roze',
            'powder-pink' => 'roze',
            'dusty-rose' => 'roze',
            'ballet-pink' => 'roze',
            
            // Wit
            'wit' => 'wit',
            'white' => 'wit',
            'snow' => 'wit',
            'pearl' => 'wit',
            'off-white' => 'wit',
            'cream-white' => 'wit',
            'snow-white' => 'wit',
            'pure-white' => 'wit',
            'arctic-white' => 'wit',
            'vanilla' => 'wit',
            'alabaster' => 'wit',
            'porcelain' => 'wit',
            
            // Zwart
            'zwart' => 'zwart',
            'black' => 'zwart',
            'jet-black' => 'zwart',
            'midnight' => 'zwart',
            'onyx' => 'zwart',
            'raven' => 'zwart',
            'obsidian' => 'zwart',
            'pitch-black' => 'zwart',
            'ebony' => 'zwart',
            'soot' => 'zwart',
            'coal' => 'zwart'
        );
    }
    
    /**
     * Normalize a color name/slug to a master category
     */
    public static function normalize_colour($term) {
        $mappings = self::get_colour_mappings();
        $normalized_term = strtolower(sanitize_title($term));
        
        // Direct mapping
        if (isset($mappings[$normalized_term])) {
            return $mappings[$normalized_term];
        }
        
        // Try without numbers at the end
        $term_without_numbers = preg_replace('/-?\d+$/', '', $normalized_term);
        if (isset($mappings[$term_without_numbers])) {
            return $mappings[$term_without_numbers];
        }
        
        // Try partial matches for compound colors
        foreach ($mappings as $color_key => $master_color) {
            if (strpos($normalized_term, $color_key) !== false) {
                return $master_color;
            }
        }
        
        // Default to grijs if no match found
        return 'grijs';
    }
}