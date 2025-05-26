<?php

namespace ExtendBuilder;

function colibri_user_can_customize() {
	return is_user_logged_in() && current_user_can( 'edit_theme_options' );
}

function is_shortcode_refresh() {
	return apply_filters( 'mesmerize_is_shortcode_refresh',
		false );
}

// we are in browser preview
function is_customize_changeset_preview() {
	return \is_customize_preview()
	       && ! isset( $_GET['customize_messenger_channel'] );
}

function is_customize_preview() {
	$in_customizer       = \is_customize_preview()
	                       && isset( $_GET['customize_messenger_channel'] );
	$is_shortcode_render = apply_filters( 'mesmerize_is_shortcode_refresh',
		false );

	return ( $in_customizer || $is_shortcode_render );

}

function extend_builder_path() {
	return __DIR__;
}

require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/filters/index.php';
require_once __DIR__ . '/updates.php';

require_once __DIR__ . '/custom-posts.php';
require_once __DIR__ . '/data/index.php';

require_once __DIR__ . '/save.php';

require_once __DIR__ . '/assets.php';
require_once __DIR__ . '/fonts.php';

require_once __DIR__ . '/partials/index.php';

require_once __DIR__ . '/api/index.php';
require_once __DIR__ . '/shortcodes/index.php';

require_once __DIR__ . '/register.php';
require_once __DIR__ . '/gutenberg.php';

require_once __DIR__ . '/import.php';
require_once __DIR__ . '/customizer/index.php';
require_once __DIR__ . '/admin/index.php';
require_once __DIR__ . '/features/index.php';
require_once __DIR__ . '/integrations/index.php';


if ( file_exists( __DIR__ . '/../pro/index.php' ) ) {
    require_once __DIR__ . '/../pro/index.php';
}

function colibri_editor_add_editor_role() {

	if ( get_role( 'colibri_content_editor' ) ) {
		return;
	}

	$editor       = get_role( 'editor' );
	$capabilities = array_merge(
		$editor->capabilities,
		array(
			'edit_theme_options' => true,
			'customize'          => true,
		)
	);

	add_role(
		'colibri_content_editor',
		__( 'Content Editor' ),
		$capabilities
	);
}

colibri_editor_add_editor_role();


function colibri_theme_default_theme_data() {
	$front_page_design = get_option( 'colibriwp_predesign_front_page_index', 0 );
	if ( $front_page_design != 0 ) {
		return;
	}

	include( 'import_theme_data.php' );
	colibri_theme_import_theme_data();
	Regenerate::schedule();
}

add_action( 'colibri_page_builder/default_theme_data', 'ExtendBuilder\colibri_theme_default_theme_data' );

add_filter( 'colibri_page_builder/plugin-activated', function ( $response, $slug, $plugin_data ) {
	if ( $slug === 'mailchimp-for-wp' ) {
		$response[ $slug ] = get_mailchimp_form_shortcode();
	}
	return $response;
}, 10, 3 );


add_action( 'admin_init', function () {
	add_action( 'admin_notices', array( Regenerate::class, 'printSiteImportedNotice' ) );
} );

//used to show cropped images in the media picker
add_filter( 'add_post_metadata', function ( $check, $object_id, $meta_key, $meta_value, $unique ) {
	if ( $meta_key === '_wp_attachment_context' && preg_match( '/custom-image-cropper/i', $meta_value ) ) {
		return true;
	}

	return $check;
}, 10, 5 );


function colibri_crop_image_copy_alt( $data, $new_attachment_id ) {

	$original_attachment_id = colibri_cache_get( 'colibri_crop_original_attachment_id' );
	$image_alt              = get_post_meta( $original_attachment_id, '_wp_attachment_image_alt', true );

	if ( $image_alt ) {
		update_post_meta( $new_attachment_id, '_wp_attachment_image_alt', $image_alt );
	}

	//remove the current function from the filter because it's not needed anymore.
	remove_filter( 'wp_update_attachment_metadata', 'ExtendBuilder\colibri_crop_image_copy_alt' );

	return $data;
}

//when cropping copy the alt of the original image
add_action( 'wp_ajax_crop_image_pre_save', function ( $context, $attachment_id, $cropped ) {
	if ( ! preg_match( '/custom-image-cropper/i', $context ) ) {
		return;
	}
	colibri_cache_set( 'colibri_crop_original_attachment_id', $attachment_id );
	add_filter( 'wp_update_attachment_metadata', 'ExtendBuilder\colibri_crop_image_copy_alt', 10, 2 );
}, 10, 3 );


function get_attachment_id_by_url( $url ) {
	$attachment_id = 0;
	$dir           = wp_upload_dir();
	if ( false !== strpos( $url, $dir['baseurl'] . '/' ) ) { // Is URL in uploads directory?
		$file       = basename( $url );
		$query_args = array(
			'post_type'   => 'attachment',
			'post_status' => 'inherit',
			'fields'      => 'ids',
			'meta_query'  => array(
				array(
					'value'   => $file,
					'compare' => 'LIKE',
					'key'     => '_wp_attachment_metadata',
				),
			)
		);
		$query      = new \WP_Query( $query_args );
		if ( $query->have_posts() ) {
			foreach ( $query->posts as $post_id ) {
				$meta                = wp_get_attachment_metadata( $post_id );
				$original_file       = basename( $meta['file'] );
				$cropped_image_files = wp_list_pluck( $meta['sizes'], 'file' );
				if ( $original_file === $file || in_array( $file, $cropped_image_files ) ) {
					$attachment_id = $post_id;
					break;
				}
			}
		}
	}

	return $attachment_id;
}


function colibri_add_images_alts( $content ) {
	if ( ! preg_match_all( '/<img [^>]+>/', $content, $matches ) ) {
		return $content;
	}

	$selected_images = $attachment_ids = array();

	foreach ( $matches[0] as $image ) {

		if ( false === strpos( $image, ' alt=' ) ) {
			$attachment_id = false;
			if ( preg_match( '/wp-image-([0-9]+)/i', $image, $class_id ) ) {
				$attachment_id = absint( $class_id[1] );
			}

			if ( false !== strpos( $image, 'logo-image' ) ) {
				$attachment_id = get_theme_mod( 'custom_logo', false );
			}


			if ( false !== strpos( $image, 'logo-alt-image' ) ) {
				preg_match( '/src=[\'|"](.*?)[\'|"]/i', $image, $url_match );
				$url      = $url_match[1];
				$url_hash = md5( $url );

				if ( $id = colibri_transient_cache_get( "logo.logo-alt-image.{$url_hash}", false ) ) {
					$attachment_id = $id;
				} else {
					$attachment_id = get_attachment_id_by_url( $url );
					colibri_transient_cache_set( "logo.logo-alt-image.{$url_hash}", $attachment_id );
				}


			}


			if ( $attachment_id ) {
				$selected_images[ $image ] = $attachment_id;
				// Overwrite the ID when the same image is included more than once.
				$attachment_ids[ $attachment_id ] = true;
			}

		}
	}

	if ( count( $attachment_ids ) > 1 ) {
		/*
		 * Warm the object cache with post and meta information for all found
		 * images to avoid making individual database calls.
		 */
		_prime_post_caches( array_keys( $attachment_ids ), false, true );
	}

	foreach ( $selected_images as $image => $attachment_id ) {
		$image_alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
		$context   = get_post_meta( $attachment_id, '_wp_attachment_context', true );

		// fix already saved images missing from media gallery//
		if ( strpos( $context, "custom-image-cropper" ) !== false ) {
			delete_post_meta( $attachment_id, '_wp_attachment_context' );
		}
        $image_alt = esc_attr($image_alt);
		$new_image = preg_replace( '/<img ([^>]+?)[\/ ]*>/', '<img $1 alt="' . $image_alt . '" />', $image );
		$content   = str_replace( $image, $new_image, $content );
	}

	return $content;
}

add_action( 'init', function () {
	// remove the filter added by third party plugin 'Colibri alt text' to reduce overhead
	remove_filter( 'the_content', 'colibri_add_images_alts', 1 );
	remove_filter( 'colibri_dynamic_content', 'colibri_add_images_alts', 1 );
	$activate_theme_name = get_option( 'colibriwp_activate_theme_name', 0 );
	if ( $activate_theme_name === 0 ) {
		add_option( 'colibriwp_activate_theme_name', get_stylesheet() );
	}	
} );

add_filter( 'the_content', 'ExtendBuilder\colibri_add_images_alts' );
add_filter( 'colibri_dynamic_content', 'ExtendBuilder\colibri_add_images_alts' );

add_action("admin_init", function() {
    ThemeDataAccess::maybeEnableFs();
});

add_action("colibri_importer_after_import", function() {
    ThemeDataAccess::getInstance()->moveFromOptionToFile();
});

function colibri_add_kss_allowed_elements($allowed_html = array()) {

    $svg_elements = array(
        'svg' =>
            array(
                'xmlns',
                'viewbox',
                'id',
                'data-name',
                'width',
                'height',
                'version',
                'xmlns:xlink',
                'x',
                'y',
                'enable-background',
                'xml:space',
            ),
        'path' =>
            array(
                'd',
                'id',
                'class',
                'data-name',
            ),
        'g' =>
            array(
                'id',
                'stroke',
                'stroke-width',
                'fill',
                'fill-rule',
                'transform',
            ),
        'title' =>
            array(),
        'polygon' =>
            array(
                'id',
                'points',
            ),
        'rect' =>
            array(
                'x',
                'y',
                'width',
                'height',
                'transform',
            ),
        'circle' =>
            array(
                'cx',
                'cy',
                'r',
            ),
        'ellipse' =>
            array(
                'cx',
                'cy',
                'rx',
                'ry',
            ),
    );

    $shared_attrs = array('data-*', 'id', 'class');

    foreach ($svg_elements as $element => $attrs) {
        if (!isset($allowed_html[$element])) {
            $allowed_html[$element] = array();
        }

        $allowed_html[$element] = array_merge($allowed_html[$element],
            array_fill_keys(array_merge($attrs, $shared_attrs), true));
    }

    $allowed_html['input'] = array(
        'type' => true,
        'name' => true,
        'value' => true,
        'class' => true,

    );
    return $allowed_html;

}

add_filter('wp_kses_allowed_html', '\ExtendBuilder\colibri_add_kss_allowed_elements');
