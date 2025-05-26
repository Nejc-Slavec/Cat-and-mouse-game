<?php

namespace ExtendBuilder;

function set_shortcode_output($tag, $output) {
    $key = "colibri_shortcodes_output";
    $colibri_run = colibri_cache_get($key);
    $colibri_run[$tag] = $output;
    colibri_cache_set($key, $colibri_run);
}

function get_shortcodes_output() {
    $key = "colibri_shortcodes_output";
    return colibri_cache_get($key);
}

function get_shortcode_output($tag) {
    $outputs = get_shortcodes_outputs();
    return array_get_value($outputs, $tag, null);
}


add_filter('do_shortcode_tag', function ( $output, $tag, $attr, $m) {
    if ($output && strpos($tag, "colibri_") !== FALSE) {
        set_shortcode_output($tag, $output);
        return $output;
    }
    return $output;
}, PHP_INT_MAX, 4);

add_shortcode( 'colibri_layout_wrapper', function ( $attrs , $content = null) {
    $atts = shortcode_atts(
        array(
            "name" => "",
        ),
        $attrs
    );

    $name = $atts['name'];
    $escaped_content = do_shortcode( $content );
    return apply_filters('colibri_layout_wrapper_output_' . $name, $escaped_content);
} );
