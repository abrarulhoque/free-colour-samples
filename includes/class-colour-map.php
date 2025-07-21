<?php
/**
 * Color mapping utility class (Version 2.1 - Final Polished)
 * Maps various color names/slugs to 14 master categories with performance and accuracy in mind.
 * Addresses underscore vs. hyphen edge cases.
 */

if (!defined('ABSPATH')) exit;

class TIWSC_Colour_Map {

    /**
     * Get the 14 master color categories and their display names.
     */
    public static function get_master_colours() {
        return array(
            'antraciet' => __('Antraciet', 'free-colour-samples'),
            'beige'     => __('Beige', 'free-colour-samples'),
            'blauw'     => __('Blauw', 'free-colour-samples'),
            'bruin'     => __('Bruin', 'free-colour-samples'),
            'geel'      => __('Geel', 'free-colour-samples'),
            'goud'      => __('Goud', 'free-colour-samples'),
            'grijs'     => __('Grijs', 'free-colour-samples'),
            'groen'     => __('Groen', 'free-colour-samples'),
            'oranje'    => __('Oranje', 'free-colour-samples'),
            'paars'     => __('Paars', 'free-colour-samples'),
            'rood'      => __('Rood', 'free-colour-samples'),
            'roze'      => __('Roze', 'free-colour-samples'),
            'wit'       => __('Wit', 'free-colour-samples'),
            'zwart'     => __('Zwart', 'free-colour-samples')
        );
    }

    /**
     * Get the comprehensive color mapping array.
     */
    public static function get_colour_mappings() {
        static $mappings = null;
        if ($mappings === null) {
            $mappings = array(
                // ANTRACIET
                'antraciet' => 'antraciet', 'anthracite' => 'antraciet', 'antrasiet' => 'antraciet',
                'donkergrijs' => 'antraciet', 'dark-greige' => 'antraciet', 'griseo' => 'antraciet',
                'charcoal' => 'antraciet', 'smokey-grey' => 'antraciet', 'smoke-grey' => 'antraciet',
                'stone-ash' => 'antraciet', 'carbon' => 'antraciet', 'shadow' => 'antraciet',
                'donker-grijs' => 'antraciet', 'carbon-express' => 'antraciet',

                // BEIGE
                'beige' => 'beige', 'light-beige' => 'beige', 'dark-beige' => 'beige', 'zand' => 'beige',
                'sand' => 'beige', 'ecru' => 'beige', 'taupe' => 'beige', 'khaki' => 'beige',
                'cappuccino' => 'beige', 'latte' => 'beige', 'creme' => 'beige', 'cream' => 'beige',
                'ivory' => 'beige', 'linnen' => 'beige', 'schelp' => 'beige', 'burlywood' => 'beige',
                'corn-silk' => 'beige', 'sterrenstof-beige' => 'beige',
                'zand-1' => 'beige', 'zand-beige' => 'beige', 'light-greige' => 'beige', 'royal-clay-klei' => 'beige',
                'vanille' => 'beige', 'soleado' => 'beige', 'cascara' => 'beige', 'naturel' => 'beige',
                'grijs-beige' => 'beige', 'taupe-1' => 'beige',

                // BLAUW
                'blauw' => 'blauw', 'blue' => 'blauw', 'navy' => 'blauw', 'denim' => 'blauw',
                'marineblauw' => 'blauw', 'royal-blue-mat' => 'blauw', 'hemelsblauw' => 'blauw',
                'medium-blue-mat' => 'blauw', 'staalblauw-metallic' => 'blauw',

                // BRUIN
                'bruin' => 'bruin', 'brown' => 'bruin', 'beech-beuken' => 'bruin', 'esdoorn' => 'bruin',
                'kastanje' => 'bruin', 'light-oak-eik' => 'bruin', 'wengee' => 'bruin', 'wenge' => 'bruin',
                'black-coffee' => 'bruin', 'cacao-bean' => 'bruin', 'dark-chocolate' => 'bruin',
                'eik' => 'bruin', 'maple-esdoorn' => 'bruin', 'pecannoot' => 'bruin', 'walnoot' => 'bruin',
                'walnoot-2' => 'bruin', 'castana' => 'bruin', 'nogal' => 'bruin', 'nuez' => 'bruin',
                'donker-bruin' => 'bruin', 'sienna-matt' => 'bruin', 'gember' => 'bruin', 'java' => 'bruin',
                'cedro' => 'bruin', 'cottage-pine' => 'bruin', 'gouden-eik' => 'bruin', 'houtlook' => 'bruin',
                'brons' => 'bruin', 'bronsbruin' => 'bruin', 'koffie' => 'bruin', 'mocca' => 'bruin',
                'tiger-eye' => 'bruin',

                // GEEL
                'geel' => 'geel', 'yellow' => 'geel', 'mustard' => 'geel', 'lemon-chiffon' => 'geel', 'honing' => 'geel',

                // GOUD
                'goud' => 'goud', 'gold' => 'goud', 'golden-rod' => 'goud', 'light-goldenrod' => 'goud',
                'sterrenstof-goud' => 'goud', 'champagne' => 'goud', 'koper' => 'goud',

                // GRIJS
                'grijs' => 'grijs', 'grey' => 'grijs', 'gray' => 'grijs', 'lichtgrijs' => 'grijs', 'licht-grijs' => 'grijs',
                'grey-shadow' => 'grijs', 'limestone' => 'grijs', 'stone-grey' => 'grijs', 'zilver' => 'grijs',
                'silver' => 'grijs', 'zilver-matt' => 'grijs', 'zilver-metallic' => 'grijs',
                'sterrenstof-zilver' => 'grijs', 'grijs-mat' => 'grijs', 'atmosfeer' => 'grijs',
                'oester' => 'grijs', 'staal' => 'grijs', 'stone' => 'grijs', 'betongrijs' => 'grijs',
                'zilvergrijs' => 'grijs', 'silverwhite' => 'grijs', 'nube' => 'grijs', 'cloud-1' => 'grijs', 'marmer' => 'grijs',

                // GROEN
                'groen' => 'groen', 'green' => 'groen', 'donker-zeegroen-mat' => 'groen',
                'donkergroen-mat' => 'groen', 'lichtgroen-mat' => 'groen', 'pale-green' => 'groen',

                // ORANJE
                'oranje' => 'oranje', 'orange' => 'oranje', 'peachpuff-matt' => 'oranje',

                // PAARS
                'paars' => 'paars', 'purple' => 'paars', 'lavendel-blush-mat' => 'paars',
                'violet-red-metallic' => 'paars', 'violet-red' => 'paars',

                // ROOD
                'rood' => 'rood', 'red' => 'rood', 'cherry-kers' => 'rood', 'crimson' => 'rood', 'donkerrood-mat' => 'rood',
                'stardust-rosy-brown' => 'rood', 'rosa' => 'rood',

                // ROZE
                'roze' => 'roze', 'pink' => 'roze',

                // WIT
                'wit' => 'wit', 'white' => 'wit', 'parel-wit' => 'wit', 'pearl-white' => 'wit', 'gebroken-wit' => 'wit', 'offwhite' => 'wit',
                'stone-white' => 'wit', 'antiek-wit' => 'wit', 'ghost-white' => 'wit',
                'mint-creme-wit' => 'wit', 'snow-mat' => 'wit', 'albast-mat' => 'wit',
                'ivoor' => 'wit', 'blanco' => 'wit', 'antiek-wit-mat' => 'wit', 'wit-1' => 'wit',

                // ZWART
                'zwart' => 'zwart', 'black' => 'zwart', 'zwart-1' => 'zwart', 'nacht-zwart' => 'zwart',
            );
        }
        return $mappings;
    }

    /**
     * Normalize a color term slug to a master category using a performant, prioritized matching process.
     */
    public static function normalize_colour($term_slug) {
        static $sorted_keys = null;
        $mappings = self::get_colour_mappings();
        
        // Sanitize and normalize the input slug to handle variations like spaces or underscores.
        $normalized_slug = sanitize_title($term_slug);
        // *** BUG FIX: Ensure underscores are treated as hyphens for matching our array keys. ***
        $normalized_slug = str_replace('_', '-', $normalized_slug);

        // Priority 1: Check for an exact match.
        if (isset($mappings[$normalized_slug])) {
            return $mappings[$normalized_slug];
        }

        // Initialize and cache the sorted keys once per request.
        if ($sorted_keys === null) {
            $sorted_keys = array_keys($mappings);
            usort($sorted_keys, function($a, $b) { return strlen($b) - strlen($a); });
        }

        // Priority 2: Find the best partial match.
        foreach ($sorted_keys as $key) {
            if (strpos($normalized_slug, $key) !== false) {
                return $mappings[$key];
            }
        }

        // Log unmapped colors for future improvement.
        if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log('[Free Colour Samples] Unmapped color term slug: ' . $term_slug . ' (normalized: ' . $normalized_slug . ')');
        }

        return 'grijs'; // Fallback
    }
}