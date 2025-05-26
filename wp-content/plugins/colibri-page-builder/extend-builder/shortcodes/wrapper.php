<?php

namespace ExtendBuilder;

add_shortcode( 'colibri_wrapper', '\ExtendBuilder\colibri_wrapper' );
add_shortcode( 'colibri_wrapper_state', '\ExtendBuilder\colibri_wrapper_state' );

add_shortcode( 'colibri_wp_action', function ( $attrs , $content = null) {
	$atts = shortcode_atts(
		array(
			"name" => "",
		),
		$attrs
	);

	ob_start();
	do_action( 'colibri_wp_action_' . $atts['name'], $attrs );
	return ob_get_clean();
} );

function get_current_wrapper() {
	return colibri_cache_get( 'colibri_current_wrapper_data', array(
		"current_wrapper"
	) );
}

function set_current_wrapper( $data ) {
	colibri_cache_set( 'colibri_current_wrapper_data', $data );
}

function get_current_wrapper_name() {
	$colibri_wrapper_data = get_current_wrapper();
	return array_get_value( $colibri_wrapper_data, 'current_wrapper.name', false );
}

function set_current_wrapper_data( $atts ) {
	$colibri_wrapper_data = get_current_wrapper();
	array_set_value( $colibri_wrapper_data, 'current_wrapper', $atts );
	set_current_wrapper( $colibri_wrapper_data );
}

function colibri_wrapper_state( $attrs, $content = null ) {
	$atts = shortcode_atts(
		array(
			"name" => "",
		),
		$attrs
	);

	$should_render = apply_filters( "colibri_wrapper_state_should_render", true, array(
		"wrapper" => get_current_wrapper_name(),
		"state"   => $atts
	) );
	if ( $should_render ) {
		return do_shortcode( $content );
	}
}


function colibri_wrapper( $attrs, $content = null ) {
	ob_start();
	$atts = shortcode_atts(
		array(
			"name"  => "",
			"state" => "",
		),
		$attrs
	);

	set_current_wrapper_data( $atts );

	$name = $atts['name'];

	do_action( "colibri_wrapper_before_$name" );

	$content = urldecode( $content );
	echo do_shortcode( wp_kses_post($content) );

	do_action( "colibri_wrapper_after_$name" );

	$content = ob_get_contents();
	ob_end_clean();
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return $content;
}
