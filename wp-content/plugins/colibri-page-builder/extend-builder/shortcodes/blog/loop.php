<?php

namespace ExtendBuilder;

add_shortcode('colibri_item_template', function($attrs, $content = null) {
	$escaped_content = str_replace('<!---->', '', $content);
    $escaped_content = wp_kses_post($escaped_content);
    return do_shortcode($escaped_content);
});

add_shortcode('colibri_loop', '\ExtendBuilder\colibri_loop');

function colibri_loop($attrs, $content = null)
{
    ob_start();
    $atts = shortcode_atts(
        array(
            'query' => false,
            'no_posts_found_text' => 'No posts found',
            'posts' => '3',
            'filter_categories' => '',
            'filter_tags' => '',
            'filter_authors' => '',
            'order_by' => 'date',
            'order_type' => 'ASC',
            'post_order' => ''
        ),
        $attrs
    );

    $query = null;
    if ($atts['query'] == "true") {
	    $query = new \WP_Query(array(
		    'posts_per_page' =>  $atts['posts'],
            	    'ignore_sticky_posts' => 1,
		    'category_name' => $atts['filter_categories'],
		    'tag' => $atts['filter_tags'],
		    'author' => $atts['filter_authors'],
		    'orderby' => $atts['order_by'],
		    'order' => $atts['order_type'],
	    ));
    } else {
	    global $wp_query;
	    if (!$wp_query->in_the_loop) {
	    $query = $wp_query;
      }
    }

    $content = urldecode($content);


    if ($query) {

    if ($query->have_posts()):
        while ($query->have_posts()):
            $query->the_post();
            $escaped_content = str_replace('<!---->', '', $content);
            $shortcode_content = do_shortcode( $escaped_content );
            echo $shortcode_content;
        endwhile;
        wp_reset_postdata();
    else:
        ?>
          <div><?php echo wp_kses_post($atts['no_posts_found_text']); ?></div>
        <?php
    endif;
    }

    $content = ob_get_contents();
    ob_end_clean();

    return $content;

}
