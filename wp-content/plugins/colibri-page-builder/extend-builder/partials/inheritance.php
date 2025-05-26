<?php

namespace ExtendBuilder;

function get_post_type() {
    global $wp_query;
    if ( isset( $wp_query ) ) {
        $post_type = $wp_query->get( 'post_type' );
        if ( is_array( $post_type ) ){
            $post_type = reset( $post_type );
        }
        $post_type_object = get_post_type_object( $post_type );
        if (isset($post_type_object)) {
            return $post_type_object->name;
        }
    }
    return \get_post_type();
}
function is_blog_posts() {
    global $wp_query;
    if ( isset( $wp_query ) && (bool) $wp_query->is_posts_page ) {
        return true;
    }
    return false;
}

function get_page_default_types()
{
    $defaults   = array();
    $defaults[] = "post";

    $post_type = get_post_type();

    if (is_singular()) {
        $defaults[] = $post_type;
    }

    if (is_front_page()) {
        $defaults[] = "front_page";
        return $defaults;
    }

    if (is_archive() || is_blog_posts()) {
        $defaults[] = 'archive_post';

        if ($post_type && $post_type !== "post") {
            $defaults[] = 'archive_' . $post_type;
        }
    }

	if ( is_woocommerce_page() ) {
		$defaults[] = 'archive_product';
	}
    return $defaults;
}

//404 page defaults
add_filter(prefix("default_partial"), function ($value, $type) {

    // archive default//
    if ($type == "main" && $value == -1 && (\is_404())) {
        $default = get_default_partial_id($type, '404');
        return $default;
    }

    return $value;
}, 11, 2);

//search page defaults
add_filter(prefix("default_partial"), function ($value, $type) {

    // archive default//
    if ($type == "main" && $value == -1 && (\is_search())) {
        $default = get_default_partial_id($type, 'search');
        return $default;
    }

    return $value;
}, 11, 2);

// front page defaults//

add_filter(prefix("default_partial"), function ($value, $type) {
    // front page default//
    if ($value == -1  && \is_front_page()) {
        $default = get_default_partial_id($type, 'front_page');
        return $default;
    }
    return $value;
}, 10, 2);

// singular defaults//
// use woo cart / account / checkout pages
add_filter( prefix( "default_partial" ), function ( $value, $type ) {
	$post_type = get_post_type();
	// archive default//
	if ( $value == - 1 && is_woocommerce_page() ) {
		$default = get_default_partial_id( $type, 'archive_product' );
		return $default;
	}
	return $value;
}, 11, 2 );

// use [post_type] if any is defined
add_filter(prefix("default_partial"), function ($value, $type) {
    $post_type = get_post_type();

    // custom post default//
    if ($value == -1 && \is_singular()) {
        $default = get_default_partial_id($type, $post_type);
        return $default;
    }

    return $value;
}, 10, 2);

// archive defaults //

// use archive_[post_type] if any is defined
add_filter(prefix("default_partial"), function ($value, $type) {
    $post_type = get_post_type();

    // archive default//
    if ($value == -1 && (\is_archive() || is_blog_posts())) {
        if ($post_type !== "page") {
            $default = get_default_partial_id($type, 'archive_' . $post_type);
            return $default;
        }
    }

    return $value;
}, 10, 2);


// use archive_post, if no archive_[post_type] was found
add_filter(prefix("default_partial"), function ($value, $type) {
    $post_type = get_post_type();

    // archive default//
    if ($value == -1 && \is_archive()) {
        if ($post_type !== "page" && $post_type !== "post") {
            $default = get_default_partial_id($type, 'archive_post');
            return $default;
        }
    }

    return $value;
}, 11, 2);
