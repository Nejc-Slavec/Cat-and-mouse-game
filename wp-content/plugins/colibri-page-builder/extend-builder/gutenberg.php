<?php
namespace ExtendBuilder;

add_action( 'after_setup_theme', function () {

	$colors_list = get_current_theme_data('colors', array());
	$colors      = array();

	foreach ( $colors_list as $index => $item ) {
		$colors[] = array(
			'name'  => sprintf( __( 'Color %d', 'colibri-page-builder' ), $index + 1 ),
			'slug'  => 'colibri-color-' . ( $index + 1 ),
			'color' => $item
		);
	}

	add_theme_support(
		'editor-color-palette',
		$colors
	);

}, 40 );

add_action( 'enqueue_block_editor_assets', function(){

	$css =  get_current_theme_data('css', '');

	// move css from body to editor
	$css = str_replace('body ','.editor-block-list__layout ',$css);
	wp_add_inline_style( 'wp-block-library',$css  );
});
