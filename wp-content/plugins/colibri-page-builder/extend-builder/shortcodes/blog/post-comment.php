<?php

namespace ExtendBuilder;

add_shortcode( 'colibri_post_comments', '\ExtendBuilder\colibri_post_comments' );

function colibri_post_comments_template( $template ) {

	return extend_builder_path() . "/partials/blog/comments.php";
}

function colibri_post_comments( $attrs = array() ) {
	// comments won't render without post//
	if ( is_customize_preview() ) {
		the_post();
	}

	$atts = shortcode_atts(
		array(
			'none'        => 'No responses yet',
			'one'         => 'One response',
			'multiple'    => 'Responses',
			'disabled'    => 'Comments are closed',
			'avatar_size' => 32
		),
		$attrs
	);

	colibri_cache_set( 'post_comments_atts', $atts );

	ob_start();


	add_filter( 'comments_template', '\\ExtendBuilder\\colibri_post_comments_template' );
	if ( comments_open( get_the_ID() ) ) {
		comments_template();
	} else {
		$comments_disabled = esc_html($atts['disabled']);
		return sprintf( '<p class="comments-disabled">%s</p>',$comments_disabled );
	}
	$content = ob_get_clean();

	remove_filter( 'comments_template', array( 'ExtendBuilder', 'colibri_post_comments_template' ) );

	return $content;
}

function disable_current_user() {
	return false;
}

add_shortcode( 'colibri_post_comment_form', '\ExtendBuilder\colibri_post_comment_form' );
function colibri_post_comment_form() {
	// comments won't render without post//
	if ( is_customize_preview() ) {
		ob_start();
		while ( have_posts() ) :
			the_post();
		endwhile;
		ob_end_clean();
	}

	ob_start();
	?>
	<?php
	if ( comments_open( get_the_ID() ) ) {
		if ( is_customize_preview() ) {
			$user = wp_get_current_user();
			wp_set_current_user( 0 );
			add_filter( 'determine_current_user', '\ExtendBuilder\disable_current_user', PHP_INT_MAX );
		}
		comment_form( get_the_ID() );
		if ( is_customize_preview() ) {
			if ( $user ) {
				wp_set_current_user( $user->ID );
			}
			remove_filter( 'determine_current_user', '\ExtendBuilder\disable_current_user', PHP_INT_MAX );
		}
	}
	?>
	<?php

	$content = ob_get_clean();

	return $content;
}
