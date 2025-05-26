<?php

namespace ExtendBuilder;


use ColibriWP\PageBuilder\PageBuilder;

function blog_partials() {
	return array(
		"main/post",
		"main/archive",
		"main/404",
		"main/search",
		"sidebar/post"
	);
}

function woo_partials() {
    return array(
        "sidebar/product"
    );
}

function extra_partials() {
	return array_merge( array(), blog_partials(), woo_partials() );
}

function all_partials() {
	$default_partials = array(
		"header/front_page",
		"header/post",
		"footer/post",
		//"sidebar/page",
	);

	$partials = array_merge( $default_partials, extra_partials() );

	return apply_filters( 'colibri_page_builder/import_all_partials', $partials );
}


add_filter( 'colibri_page_builder/import_all_partials', function ( $partials ) {
	$front_page_design = get_option( 'colibriwp_predesign_front_page_index', 0 );
	if ( intval( $front_page_design ) ) {
		$partials[] = "page/front_page";
	};

	return $partials;
} );

add_filter( 'colibri_page_builder/handled_partial_import', function ( $handled, $partial_key, $partial_import_data ) {

	if ( $partial_key === "page/front_page" ) {
		$front_page = PageBuilder::instance()->__createFrontPage();
		$data       = array_get_value( $partial_import_data, 'partial.data', array() );
		$data['id'] = $front_page->ID;
		if ( array_key_exists( 'css', $data ) ) {
			unset( $data['css'] );
		}

		$processed_data = Import::process_partial_data(
			$partial_import_data,
			Import::get_next_style_ref_id(),
			$front_page->ID
		);

		$final_data = $processed_data['new'];

        	Import::save_to_options($final_data);

		$partial_data = $final_data['partial']['data'];

		update_partial( $front_page->ID, $partial_data );

		Import::set_default_as_imported( $partial_key );

		$handled = true;
	}

	return $handled;
}, 10, 3);


// look for online importable content if available
add_filter('colibri_page_builder/import_file_value', function ($content, $id, $value_key, $front_page_design) {
    $remote_import_path = apply_filters('colibri_page_builder/remote_import_slug', false, $front_page_design);
    if ($remote_import_path) {
        $url = 'http://colibriwp.com/defaults/';
        $url = apply_filters( 'colibri_page_builder/remote_base_url', $url );

        $request_url = add_query_arg(array(
            'slug' => $remote_import_path,
            'design' => $front_page_design,
            'id' => $id,
            'value_key' => $value_key
        ), $url);

        $response = wp_remote_get($request_url);

        if (is_wp_error($response)) {
            return null;
        } else {
            $code = intval(wp_remote_retrieve_response_code($response));
            if ($code !== 200) {
                return null;
            }

            $body = wp_remote_retrieve_body($response);
            $content = json_decode($body, true);

            if ($content === null) {
                return null;
            }

            array_walk_recursive($content, function (&$item) {
                if (is_string($item)) {
                    $item = str_replace("[colibri_theme_url]", get_template_directory_uri(), $item);
                    $item = str_replace("[colibri_builder_plugin_url]", '[colibri_import_asset_url]', $item);
                }
            });

            load_file_value($value_key, $content);
        }
    }
    return $content;
}, 10, 4);


add_action('init', function () {

    if (wp_doing_ajax()) {
		return;
	}

	$extra_partials      = extra_partials();
	$all_partials        = all_partials();
	$default_is_imported = Import::default_is_imported( Import::$theme_default_data_key );
	$old_format_imported = get_theme_data( "imported.theme_default" );
	if ( $old_format_imported ) {
		// if single file theme default was already imported, import only new available partials files like blog, woocommerce, etc.
		// for testing
		Import::maybe_import_available_partials( $extra_partials );
	} else {
		// else import theme default and all partials
		Import::maybe_import_theme_default();
		Import::maybe_import_available_partials( $all_partials );

		if ( ! $default_is_imported ) {
			// in branch only
			do_action( 'colibri_page_builder/default_theme_data' );
		}
	}
} );
