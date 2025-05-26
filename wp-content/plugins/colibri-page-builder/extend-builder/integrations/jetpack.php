<?php

use ExtendBuilder\PostData;

add_action( 'jetpack_copy_post', function ( $source_post, $target_post_id, $update_results ) {
	/** @var WP_Post $source_post */


	$skip_for_types = \ExtendBuilder\post_types();

	$skip_for_types = array_map( function ( $item ) {
		return \ExtendBuilder\custom_post_prefix() . $item;
	}, $skip_for_types );

	$skip_for_types = array_merge( $skip_for_types, array( 'revision' ) );
	$target_post    = get_post( $target_post_id );

	if ( in_array( $target_post->post_type, $skip_for_types ) ) {
		return;
	}

	$original_post_data = new PostData( $source_post->ID );
	$json               = $original_post_data->get_data( "json" );
	if ( $json ) {
		$new_post_data = new PostData( $target_post_id );
		$new_post_data->set_data( "json", $json, true );
	}

	$metas_to_copy = array(
		'colibri_is_colibri-wp_maintainable_page',
		'_wp_page_template'

	);

	foreach ( $metas_to_copy as $meta ) {
		$value = get_post_meta( $source_post->ID, $meta, true );
		if ( ! empty( $value ) ) {
			update_post_meta( $target_post_id, $meta, $value );
		}
	}

	$post_data = array(
		'ID'         => $target_post_id,
		'post_title' => $source_post->post_title . " - Copy"
	);
	wp_update_post( $post_data );

}, 10, 3 );
