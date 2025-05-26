<?php

namespace ExtendBuilder;

function custom_post_prefix() {
	return "extb_post_";
}

function post_types() {
	return array(
		"meta",
		"css",
		"json",
		"header",
		"footer",
		"partial",
		"main",
		"sidebar",
        'preset'
	);
}

function custom_post_type_options( $key ) {
	return array(
		'labels'           => array(
			'name'          => __( "Extend Builder Page $key" ),
			'singular_name' => __( "Extend Builder Page $key" ),
		),
		'public'           => false,
		'hierarchical'     => false,
		'rewrite'          => false,
		'query_var'        => false,
		'delete_with_user' => false,
		'can_export'       => true,
		'supports'         => array( 'title', 'revisions' ),
		'capabilities'     => array(
			'delete_posts'           => 'edit_theme_options',
			'delete_post'            => 'edit_theme_options',
			'delete_published_posts' => 'edit_theme_options',
			'delete_private_posts'   => 'edit_theme_options',
			'delete_others_posts'    => 'edit_theme_options',
			'edit_post'              => 'edit_css',
			'edit_posts'             => 'edit_css',
			'edit_others_posts'      => 'edit_css',
			'edit_published_posts'   => 'edit_css',
			'read_post'              => 'read',
			'read_private_posts'     => 'read',
			'publish_posts'          => 'edit_theme_options',
		),
	);
}

function custom_post_type_simple_name( $internal_name ) {

	return str_replace( custom_post_prefix(), "", $internal_name );
}

function custom_post_type_wp_name( $name ) {
	return custom_post_prefix() . $name;
}


function register_custom_post_type( $name ) {
	register_post_type( custom_post_type_wp_name( $name ), custom_post_type_options( $name ) );
}

function register_custom_post_types() {
	$post_types = post_types();
	foreach ( $post_types as $index => $name ) {
		register_custom_post_type( $name );
	}
}


function get_custom_posts( $type = false ) {

	$types = $type;
	if ( $type && ! is_array( $type ) ) {
		$types = array( $type );
	}

	for ( $i = 0; $i < count( $types ); $i ++ ) {
		if ( $types[ $i ] !== "page" ) {
			$types[ $i ] = custom_post_type_wp_name( $types[ $i ] );

		}
	}

	$args = array(
		'order'    => 'ASC',
		'nopaging' => true,
	);

	if ( $types ) {
		$args['post_type'] = $types;
	}

	$query = new \WP_Query( $args );

	return $query->posts;
}

add_action( 'init', '\ExtendBuilder\register_custom_post_types' );
