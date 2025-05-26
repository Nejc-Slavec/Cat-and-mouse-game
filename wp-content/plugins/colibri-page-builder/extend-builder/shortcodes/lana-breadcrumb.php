<?php
/**
 * Plugin Name: Lana Breadcrumb
 * Plugin URI: http://lana.codes/lana-product/lana-breadcrumb/
 * Description: Indicate the current page's location within a navigational hierarchy.
 * Version: 1.0.5
 * Author: Lana Codes
 * Author URI: http://lana.codes/
 * Text Domain: lana-breadcrumb
 * Domain Path: /languages
 */

namespace ExtendBuilder;
defined('ABSPATH') or die();
define('LANA_BREADCRUMB_VERSION', '1.0.5');

/**
 * Lana Breadcrumb
 * with Bootstrap
 */
function lana_breadcrumb($atts)
{
    $default_home_icon = '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" id="home" viewBox="0 0 1664 1896.0833"><path d="M1408 992v480q0 26-19 45t-45 19H960v-384H704v384H320q-26 0-45-19t-19-45V992q0-1 .5-3t.5-3l575-474 575 474q1 2 1 6zm223-69l-62 74q-8 9-21 11h-3q-13 0-21-7L832 424l-692 577q-12 8-24 7-13-2-21-11l-62-74q-8-10-7-23.5T37 878l719-599q32-26 76-26t76 26l244 204V288q0-14 9-23t23-9h192q14 0 23 9t9 23v408l219 182q10 8 11 21.5t-7 23.5z"></path></svg>';

    $atts = array_merge(array(
        'home_as_icon' => false,
        'home_icon' => $default_home_icon,
        'home_label' => ''
    ), $atts);

    global $post;
    global $wp_query;

    $blog_page_id =  get_option( 'page_for_posts' );
    /**
     * Html output
     */
    $output = '';

    /**
     * Breadcrumb html
     * tags
     */
    $breadcrumb_before = '<ol class="breadcrumb colibri-breadcrumb">';
    $breadcrumb_after = '</ol>';
    $breadcrumb_element_before = '<li class="breadcrumb-item">';
    $breadcrumb_element_after = '</li>';
    $breadcrumb_element_link_before = '<a href="%s">';
    $breadcrumb_element_link_after = '</a>';
    $breadcrumb_elements = array();

    $blog_element = array(
        'href' => esc_url(get_permalink( $blog_page_id )),
        'text' =>  wp_kses_post(get_the_title($blog_page_id))
    );
    /**
     * Breadcrumb
     * home element
     */
    $breadcrumb_elements['home'] = array(
        'href' => home_url('/'),
        'text' => wp_kses_post($atts['home_label'])
    );

    /**
     * Page
     * parents
     */
    if (is_page()) {
        $ancestors = get_post_ancestors($post);
        if (!empty($ancestors)) {
            $ancestors = array_reverse($ancestors);

            foreach ($ancestors as $ancestor) {
                $breadcrumb_elements['pages-' . $ancestor] = array(
                    'href' => esc_url(get_permalink($ancestor)),
                    'text' => wp_kses_post(get_the_title($ancestor))
                );
            }
        }
    }

    /**
     * Singular
     */
    if (is_singular()) {
        if($post && $post->post_type === 'post') {

            $breadcrumb_elements['blog'] = $blog_element;
        }
        $breadcrumb_elements['active'] = array(
            'href' => '',
            'text' => wp_kses_post(get_the_title())
        );
    }

    /**
     * Home
     */
//    if (is_home()) {
//        $breadcrumb_elements['active'] = array(
//            'href' => 'is_home',
//            'text' => single_post_title('', false)
//        );
//    }

    /**
     * Blog Page
     */
    if ( isset( $wp_query ) &&  $wp_query->is_posts_page ) {
        $breadcrumb_elements['active'] = array(
            'href' => '',
            'text' => wp_kses_post(get_the_title($blog_page_id))
        );
    }
    /**
     * Tax
     */
    if (is_tax()) {
        $breadcrumb_elements['active'] = array(
            'href' => '',
            'text' => wp_kses_post(single_term_title('', false))
        );
    }

    /**
     * Category
     */
    if (is_category()) {
        $breadcrumb_elements['blog'] = $blog_element;
        $breadcrumb_elements['active'] = array(
            'href' => '',
            'text' => wp_kses_post(single_cat_title('', false))
        );
    }

    /**
     * Tag
     */
    if (is_tag()) {
        $breadcrumb_elements['active'] = array(
            'href' => '',
            'text' => wp_kses_post(single_tag_title('', false))
        );
    }

    /**
     * Date
     */
    if (is_date()) {
        $breadcrumb_elements['blog'] = $blog_element;
        $breadcrumb_elements['active'] = array(
            'href' => '',
            'text' => wp_kses_post(get_the_archive_title())
        );
    }

    /**
     * Post type archive
     */
    if (is_post_type_archive()) {
        $breadcrumb_elements['active'] = array(
            'href' => '',
            'text' => wp_kses_post(get_the_archive_title())
        );
    }

    /**
     * Post format
     * aside, video, gallery etc.
     */
    if (is_tax('post_format')) {
        $breadcrumb_elements['active'] = array(
            'href' => '',
            'text' => wp_kses_post(get_the_archive_title())
        );
    }

    /**
     * Author
     */
    if (is_author()) {
        $breadcrumb_elements['active'] = array(
            'href' => '',
            'text' => wp_kses_post(get_the_author_meta('display_name'))
        );
    }

    /**
     * Search
     */
    if (is_search()) {
        $breadcrumb_elements['active'] = array(
            'href' => '',
            'text' => sprintf(__('Search Results for &#8220;%s&#8221;', 'lana-breadcrumb'), wp_kses_post(get_search_query()))
        );
    }

    /**
     * 404
     */
    if (is_404()) {
        $breadcrumb_elements['active'] = array(
            'href' => '',
            'text' => __('Page not found', 'lana-breadcrumb')
        );
    }

    /**
     * Generate
     * output
     */
    $output .= $breadcrumb_before;
    if (!empty($breadcrumb_elements)) {
        foreach ($breadcrumb_elements as $key => $breadcrumb_element) {

            $output .= $breadcrumb_element_before;

            if (!empty($breadcrumb_element['href'])) {
                $output .= sprintf($breadcrumb_element_link_before, $breadcrumb_element['href']);
            }
            if($key === 'home' && $atts['home_as_icon']) {
                $output .= $atts['home_icon'];
            } else {
                $output .= $breadcrumb_element['text'];
            }
            if (!empty($breadcrumb_element['href'])) {
                $output .= $breadcrumb_element_link_after;
            }

            $output .= $breadcrumb_element_after;
        }
    }
    $output .= $breadcrumb_after;

    return $output;
}
