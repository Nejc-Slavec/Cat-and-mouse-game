<?php

namespace ExtendBuilder;

function current_location() {
	if ( isset( $_SERVER['HTTPS'] ) &&
	     ( $_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1 ) ||
	     isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) &&
	     $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' ) {
		$protocol = 'https://';
	} else {
		$protocol = 'http://';
	}

	$request_uri_parts = explode( '#', $_SERVER['REQUEST_URI'] );
	$request_uri       = array_shift( $request_uri_parts );

	return untrailingslashit( $protocol . $_SERVER['HTTP_HOST'] . $request_uri );
}

add_action( 'customize_register', function ( $wp_customize ) {
	$defaults  = \ExtendBuilder\get_theme_data_defaults();
	$locations = array_get_value( $defaults, 'menu.locations', array() );
	foreach ( $locations as $location ) {
		/** @var \WP_Customize_Manager $wp_customize */
		$setting_id = "nav_menu_locations[{$location['id']}]";
		if ( $setting = $wp_customize->get_setting( $setting_id ) ) {
			$setting->transport = 'postMessage';
		}
	}
}, PHP_INT_MAX );

add_filter( 'wp_nav_menu_objects', function ( $sorted_menu_items, $args ) {
	global $wp;
	$current_url = current_location();


	/** @var \WP_Post $item */
	$select_classes = array( 'current-menu-item', 'current_page_item' );
	foreach ( $sorted_menu_items as $item ) {

		// allow selected classes on #page-top
		if ( strpos( $item->url, '#page-top' ) !== false ) {
			continue;
		}

		if ( count( array_intersect( $item->classes, $select_classes ) ) ) {
			if ( untrailingslashit( $item->url ) !== $current_url ) {
				$item->classes = array_diff( $item->classes, $select_classes );
			}
		}
	}

	return $sorted_menu_items;
}, 10, 2 );

add_shortcode( 'colibri_print_menu', function ( $attrs ) {
	$defaultAttrs = array(
		'id'                 => null,
		'classes'            => '',
		'show_shopping_cart' => '0',
		'depth'              => '0',
	);

	$merged_attrs = array_merge( $defaultAttrs, $attrs );
	ob_start();
	colibri_print_menu( $merged_attrs );
	$content = ob_get_clean();

	return $content;
} );

function colibri_theme_location_menu_is_empty( $theme_location ) {
	$theme_locations = get_nav_menu_locations();
	if ( ! isset( $theme_locations[ $theme_location ] ) ) {
		return false;
	}

	$menu_id    = $theme_locations[ $theme_location ];
	$menu_items = wp_get_nav_menu_items( $menu_id );

	if ( $menu_items !== false && count( $menu_items ) === 0 ) {
		return true;
	}

	return false;
}

function colibri_print_menu( $attrs, $walker = '' ) {

	add_filter( 'nav_menu_item_title', function ( $title, $item, $args, $depth ) {
		return colibri_menu_add_first_level_menu_icons( $title, $item );
	}, 10, 4 );

	$theme_location         = $attrs['id'];
	$customClasses          = $attrs['classes'];
	$drop_down_menu_classes = apply_filters( 'colibri_primary_drop_menu_classes', array( 'colibri-menu' ) );
	$drop_down_menu_classes = array_merge( $drop_down_menu_classes, array( $customClasses ) );

	if ( is_customize_preview() ) {
		global $wp_customize;
		$wp_customize->nav_menus->customize_preview_init();
	}
	if ( $attrs['depth'] === '1' ) {
		add_filter( 'wp_nav_menu_objects', '\ExtendBuilder\colibri_menu_remove_submenu_class', 10, 2 );
	}

//    if ($attrs['show_shopping_cart'] === '1') {
//        colibri_menu_add_shoping_cart();
//    }

	if ( colibri_theme_location_menu_is_empty( $theme_location ) ) {
		echo 'No menu items';

		return;
	}

	colibri_cache_set( 'colibri_nomenu_cb', $attrs );

    $args = array(
        'theme_location'  => $theme_location,
        'menu_class'      => esc_attr( implode( " ", $drop_down_menu_classes ) ),
        'container_class' => 'colibri-menu-container',
        'fallback_cb'     => "\\ExtendBuilder\\colibri_nomenu_fallback",
        'walker'          => $walker,
    );

	

    if($attrs['depth'] !== '0') {
		 $args['depth'] = $attrs['depth'];
    }

	wp_nav_menu( $args );


//    if ($attrs['show_shopping_cart'] === '1') {
//        remove_filter('wp_nav_menu_items', 'colibri_woocommerce_cart_menu_item', 10);
//        remove_filter('colibri_nomenu_after', 'colibri_woocommerce_cart_menu_item', 10);
//    }
	if ( $attrs['depth'] === '1' ) {
		remove_filter( 'wp_nav_menu_objects', '\ExtendBuilder\colibri_menu_remove_submenu_class' );
	}
}

function colibri_menu_add_first_level_menu_icons( $title, $item ) {

	$arrow = '';

	if(is_numeric($item)){
	    // TO DO handle page menu here
    } else {
		if ( in_array( 'menu-item-has-children', $item->classes ) && ! $item->menu_item_parent ) {
			// down arrow
			$arrow = '<svg aria-hidden="true" data-prefix="fas" data-icon="angle-down" class="svg-inline--fa fa-angle-down fa-w-10" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><path fill="currentColor" d="M143 352.3L7 216.3c-9.4-9.4-9.4-24.6 0-33.9l22.6-22.6c9.4-9.4 24.6-9.4 33.9 0l96.4 96.4 96.4-96.4c9.4-9.4 24.6-9.4 33.9 0l22.6 22.6c9.4 9.4 9.4 24.6 0 33.9l-136 136c-9.2 9.4-24.4 9.4-33.8 0z"></path></svg>';

			// right arrow
			$arrow .= '<svg aria-hidden="true" data-prefix="fas" data-icon="angle-right" class="svg-inline--fa fa-angle-right fa-w-8" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 512"><path fill="currentColor" d="M224.3 273l-136 136c-9.4 9.4-24.6 9.4-33.9 0l-22.6-22.6c-9.4-9.4-9.4-24.6 0-33.9l96.4-96.4-96.4-96.4c-9.4-9.4-9.4-24.6 0-33.9L54.3 103c9.4-9.4 24.6-9.4 33.9 0l136 136c9.5 9.4 9.5 24.6.1 34z"></path></svg>';
		}
    }



	return $title . $arrow;

}
function colibri_nomenu_fallback() {
	$attrs                  = colibri_cache_get( 'colibri_nomenu_cb' );
	$walker                 = '';
	$customClasses          = $attrs['classes'];
	$drop_down_menu_classes = apply_filters( 'colibri_primary_drop_menu_classes', array( 'colibri-menu' ) );
	$drop_down_menu_classes = array_merge( $drop_down_menu_classes, array( $customClasses ) );

	add_filter( 'the_title', "ExtendBuilder\colibri_menu_add_first_level_menu_icons", 10, 2 );

	$menu_args = array(
		"menu_class" => 'colibri-menu-container',
		'before'     => '<ul class="' . esc_attr( implode( " ", $drop_down_menu_classes ) ) . '">',
		'after'      => apply_filters( 'colibri_nomenu_after', '' ) . "</ul>",
		'walker'     => $walker
	);

	if($attrs['depth'] !== '0') {
		$menu_args['depth'] = $attrs['depth'];
   	}


	$menu = wp_page_menu($menu_args);

	remove_filter( 'the_title', "ExtendBuilder\colibri_menu_add_first_level_menu_icons" );

	return $menu;
}

function colibri_nomenu_cb( $attrs ) {
	$attrs = colibri_cache_set( 'colibri_nomenu_cb', $attrs );

	return colibri_nomenu_fallback();
}

function colibri_menu_add_shoping_cart() {
	add_filter( 'wp_nav_menu_items', '\ExtendBuilder\colibri_woocommerce_cart_menu_item', 10, 2 );
	add_filter( 'colibri_nomenu_after', '\ExtendBuilder\colibri_woocommerce_cart_menu_item', 10, 2 );
}

function colibri_woocommerce_cart_menu_item( $items, $args = false ) {


	$cart_url = wc_get_cart_url();

	$cart_id   = wc_get_page_id( 'cart' );
	$cartLabel = get_the_title( $cart_id );
	ob_start();
	?>
    <li class="mesmerize-menu-cart">
        <a href="<?php echo esc_url($cart_url) ?>">
            <i style="transition-duration: 0s;" class='dashicons dashicons-cart'></i>
			<?php echo esc_html($cartLabel); ?>
        </a>
    </li>
	<?php
	$item = ob_get_contents();
	ob_end_clean();

	return $items . $item;
}

/**
 * There is a bug on the depth parameter of the wp_page_menu function. It does not remove the submenu classes from the
 * li. So we have to remove them manually.
 */
function colibri_menu_remove_submenu_class( $sorted_menu_items, $args ) {
	foreach ( $sorted_menu_items as $elementKey => $element ) {
		$element->classes = array_filter( $element->classes, '\ExtendBuilder\colibri_remove_submenu_classes_filter' );
	}

	return $sorted_menu_items;
}

function colibri_remove_submenu_classes_filter( $element ) {
	$submenu_class = 'menu-item-has-children';

	return $element !== $submenu_class;
}
