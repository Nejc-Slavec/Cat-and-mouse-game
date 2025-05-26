<?php
namespace ExtendBuilder;

add_filter('extendbuilder_wp_data', function ($value) {
    $pages = get_pages(array(
        'post_status' => array('publish', 'draft'),
    ));
    $posts = get_posts(array(
        'numberposts' => -1,
        'post_status' => array('publish', 'draft'),
    ));
    $items = array_merge(array(), $pages, $posts);
    $pages_by_partials = array(
        'header' => array(),
        'footer' => array()
    );
    $partials = ['header', 'footer'];
    foreach ($items as $page) {
        $post_data = new PostData($page->ID);

        foreach ($partials as $partial) {
            $partial_id = $post_data->get_meta_value($partial);
            if ($partial_id) {
                if (!isset($pages_by_partials[$partial][$partial_id])) {
                    $pages_by_partials[$partial][$partial_id] = array($page->ID);
                } else {
                    $pages_by_partials[$partial][$partial_id][] = $page->ID;
                }
            }
        }
    }

    $value['pagesPerPartial'] = $pages_by_partials;
    return $value;
});
