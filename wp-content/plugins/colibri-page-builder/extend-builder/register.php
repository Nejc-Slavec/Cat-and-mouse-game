<?php

namespace ExtendBuilder;

//colibri new menu
function colibri_register_dynamic_nav_menus() {
	$menu      = get_current_theme_data( 'menu', array( "locations" => array() ) );
	$locations = $menu['locations'];
	foreach ( $locations as $theme_location ) {
		//check if the theme_location is an associative array for backward compatibility
		if ( is_array( $theme_location ) ) {
			register_nav_menus( array(
				$theme_location['id'] => $theme_location['label']
			) );
		}
	}
}

add_action( 'init', '\ExtendBuilder\colibri_register_dynamic_nav_menus' );

//end colibri new menu


function colibri_init_custom_widgets_init() {
	$widget_areas = get_current_theme_data( 'widget_areas', array() );

	$widget_area_html = array(
		'before_widget' => '<div id="%1$s" class="widget %2$s">',
		'before_title'  => '<h5 class="widgettitle">',
		'after_title'   => '</h5>',
		'after_widget'  => '</div>',
	);

	register_sidebar( array_merge( array(
		'name'  => 'eCommerce Left Sidebar',
		'id'    => "colibri-ecommerce-left",
		'title' => "WooCommerce Left Sidebar",

	), $widget_area_html ) );

	//    register_sidebar( array_merge( array(
	//        'name'  => 'eCommerce Right Sidebar',
	//        'id'    => "colibri-ecommerce-right",
	//        'title' => "WooCommerce Right Sidebar",
	//
	//    ), $widget_area_html ) );
	//
	//	register_sidebar( array_merge( array(
	//		'name' => esc_html__( 'Page sidebar widget area', 'colibri-page-builder' ),
	//		'id'   => 'colibri-sidebar-page',
	//	), $widget_area_html ) );

	if ( ! is_registered_sidebar( 'colibri-sidebar-1' ) ) {
		register_sidebar( array_merge( array(
			'name' => esc_html__( 'Blog sidebar widget area', 'colibri-page-builder' ),
			'id'   => 'colibri-sidebar-1',
		), $widget_area_html ) );
	}
	
	foreach ( $widget_areas as $id => $data ) {
		register_sidebar( array_merge( array(
			'id'   => "colibri-{$id}",
			'name' => $data['name'],
		), $widget_area_html ) );

		// ob_start();
		// dynamic_sidebar("colibri-{$id}");
		// ob_get_clean();
	}
}

add_action( 'widgets_init', '\ExtendBuilder\colibri_init_custom_widgets_init' );

/**
 * if the user uses the pages panel and goes to the search page in the customizer with a specific search string, we show
 * a specific number of sample posts that are unrelated with the special search.
 */
function search_page_customize_action( $query ) {
	if ( $query->is_search && is_customize_preview() ) {
		$search_query_string = $query->get( 's' );
		if ( $search_query_string !== 'colibri-search-page' ) {
			return;
		}
		$query->set( 'post_type', 'post' );
		$query->set( 's', ' ' );
		$query->set( 'posts_per_page', '5' );
//        add_filter('get_search_query', function($filter) {
//            return 'sample';
//        }, 10, 2);
	}
}

add_action( 'pre_get_posts', '\ExtendBuilder\search_page_customize_action' );
