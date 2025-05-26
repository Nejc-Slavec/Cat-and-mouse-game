<?php

/**
 *  SendGrid Optimizer skip html minify on customizer preview to ensure the proper work of Colibri Page Builder
 */
add_filter( 'sgo_html_minify_exclude_params', function ( $params ) {

	if ( ! in_array( 'customize_changeset_uuid', $params ) ) {
		array_push( $params, 'customize_changeset_uuid' );
	}

	return $params;

} );
