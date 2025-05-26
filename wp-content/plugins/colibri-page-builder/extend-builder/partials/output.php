<?php

namespace ExtendBuilder;


use ColibriWP\PageBuilder\ThemeHooks;

function get_templates_parts($type ) {
	$posts      = get_custom_posts( $type );
	$result     = array();
	$default_id = get_default_partial_id( $type );

	foreach ( $posts as $index => $post ) {
		array_push( $result, array(
			'id'        => $post->ID,
			'name'      => $post->post_title,
			'type'      => custom_post_type_simple_name( $post->post_type ),
			'data'      => get_partial_data( $post->ID, $type ),
			'isDefault' => $default_id == $post->ID,
		) );
	}

	return $result;
}

function get_partial_html( $type, $id = - 1 ) {
	$saved_data = get_current_data( $id );
	$partial_id = array_get_value( $saved_data, "pages.0.partials." . $type);
  	$html = array_get_value($saved_data, "partials.$partial_id.html");
	return $html;
}

add_filter( 'colibri_page_builder/template/page_content',
	function ( $content ) {
		// page preview with change set //
		if ( is_customize_changeset_preview() ) {
			$saved_content = get_partial_html( "content", get_the_ID() );
			if ( $saved_content ) {
				return $saved_content;
			}
		}
		if ( is_customize_preview() && show_page_content()) {
			// marker for content area//
			$content = $content
			           . "<script id=\"extend-builder-content-json\" type=\"template/json\"></script>";
		}

		return $content;
	} );

function colibri_output_sidebar_search_form( $form = '' ) {

	ob_start();
	require( extend_builder_path() . "/partials/blog/searchform.php" );

	return ob_get_clean();
}

add_action( 'sidebar_admin_setup', function () {

	$id_base = array_get_value( $_REQUEST, 'id_base', null );

	if ( $id_base === 'search' ) {
		add_filter( 'get_search_form', "ExtendBuilder\colibri_output_sidebar_search_form", PHP_INT_MAX );
	}
} );

function colibri_output_dynamic_template( $template, $type ) {
	if ( $template == "dynamic" ) {

		$lang                 = get_current_language();
		$data                 = partial_template_default_structure();
		$is_customize_preview = is_customize_preview();

		$partial_post    = get_current_partial_post( $type, get_default_language() );
		$post_id_in_lang = get_post_in_language( $partial_post->ID, $lang );

		$theme_data = get_current_data( - 1, true );
		$is_visible = true;


		if ( $type === 'sidebar' ) {
			add_filter( 'get_search_form', "ExtendBuilder\colibri_output_sidebar_search_form", 100 );
		}

		if ( ! is_customize_preview() ) {
			if ( ! $is_visible ) {
				remove_filter( 'get_search_form', "ExtendBuilder\colibri_output_sidebar_search_form", 100 );

				return;
			}
		}

		$partial_post = get_post( $post_id_in_lang );
		if ( $partial_post ) {
			$data['html'] = $partial_post->post_content;
		}

		if ( is_customize_changeset_preview() ) {
			$saved_html = get_partial_html( $type );
			if ( $saved_html ) {
				$data['html'] = $saved_html;
			}
		}

		global $wp_embed;
		$html = $data['html'];
		$html = $wp_embed->run_shortcode( $html );

		$html = do_shortcode( $html );
		$html = apply_filters( 'colibri_dynamic_content', $html, array('type' => $type) );

		$wrap_start = "";
		$wrap_end   = "";

		if ( $is_customize_preview ) {
			$wrap_start = "<div>";
			$wrap_end   = "</div>";
			$html       = "<script id=\"extend-builder-$type-json\" type=\"template/json\"></script>";
		}

		$start_mark = $wrap_start . "<!-- dynamic $type start -->";
		$end_mark   = $wrap_end . "<!-- dynamic $type end -->";

       		do_action( "colibri_before_dynamic_content", $type );
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $start_mark;
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $html;
		do_action( "colibri_after_dynamic_content", $type );
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $end_mark;

		remove_filter( 'get_search_form', "ExtendBuilder\colibri_output_sidebar_search_form", 100 );
	}
}

function handle_dynamic_template( $template, $type ) {
    $value = apply_filters("colibri_should_handle_template", "dynamic", $type, $template);
    return $value;
}

$partials_types_list = partials_types_list();

foreach ( $partials_types_list as $partial ) {
	ThemeHooks::prefixed_add_filter("{$partial}_partial_type", function ( $template ) use ( $partial ) {
		return handle_dynamic_template( $template, $partial );
	}, 1000 );

}
add_action('wp_head', '\ExtendBuilder\colibri_print_featured_img_bg', 100);

function colibri_print_featured_img_bg()
{
    $background_img = colibri_get_post_featured_img();

    ob_start();
    ?>
    <style>
        .colibri-featured-img-bg {
            background-image: url("<?php echo esc_html($background_img) ?>") !important;
        }
    </style>
    <?php
    $style = ob_get_clean();
    if ($background_img) {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $style;
    }
}
