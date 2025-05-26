<?php

namespace ExtendBuilder;

function catch_action_output($name, $validator){
    add_action("colibri_layout_wrapper_output_$name", function($content) use ($validator){
        if ($validator($content)) {
          return $content;
        }
        return "";
    });
}

catch_action_output('navigation_container', function($content) {
    $outputs = get_shortcodes_output();
    $shortcodes = ['colibri_archive_pagination', 'colibri_archive_nav_button', 'colibri_post_nav_button'];
    $should_output = false;
    foreach ($shortcodes as $shortcode) {
        if (!$should_output && is_array($outputs) && array_get_value($outputs, $shortcode, false)) {
            $should_output = true;
        }
    }
    return $should_output;
});

function get_nav_direction_wp_name( $type ) {
	return $type == "next" ? $type : "previous";
}

add_shortcode( 'colibri_post_nav_button',
	'\ExtendBuilder\colibri_post_nav_button' );


function colibri_post_nav_button( $attrs = array() ) {
	ob_start();
	$atts = shortcode_atts(
		array(
			'type'      => 'next',
			'next_post' => __('Next post:','colibri-page-builder'),
			'prev_post' => __('Previous post:','colibri-page-builder'),
		),
		$attrs
	);
	single_post_nav_button( $atts );

	return ob_get_clean();
}

function print_navigation_button( $type, $button_text ) {
	$args = array(
		'prev_text'          => '%title',
		'next_text'          => '%title',
		'in_same_term'       => false,
		'excluded_terms'     => '',
		'taxonomy'           => 'category',
		'screen_reader_text' => __( 'Post navigation' ),
	);

	$navigation        = '';
	$direction_wp_name = get_nav_direction_wp_name( $type );
	$outer             = "<div class=\"nav-{$direction_wp_name}\">%link</div>";
	$nav_link_fct      = "get_{$direction_wp_name}_post_link";
	$navigation        = call_user_func( $nav_link_fct,
		$outer,
		$button_text,
		$args['in_same_term'],
		$args['excluded_terms'],
		$args['taxonomy']
	);

	// Only add markup if there's somewhere to navigate to.
	if ( $navigation ) {
		$navigation = _navigation_markup( $navigation, 'post-navigation',
            wp_kses_post($args['screen_reader_text']) );
	}
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo $navigation;
}

function single_post_nav_button( $atts = array() ) {
	$type = $atts['type'];
	$meta = colibri_esc_html_preserve_spaces( $atts["{$type}_post"] );
	if ( is_customize_preview() ) {
		$nav_class = $type == "next" ? "nav-next" : "nav-previous";
		$nav_rel   = "{$type}";
		?>
        <nav class="navigation post-navigation" role="navigation">
            <div class="<?php echo esc_attr( $nav_class); ?>">
                <a href="" rel="<?php echo esc_attr($nav_rel); ?>">
                    <span class="meta-nav" aria-hidden="true"><?php
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        echo $meta
                        ?></span>
                    <span class="post-title" title="Test post">Test post</span>
                </a>
            </div>
        </nav>
		<?php
	} else {
		$meta        = colibri_esc_html_preserve_spaces( $atts["{$type}_post"] );
		$button_text = '<span class="meta-nav" aria-hidden="true">'
		               . $meta . '</span> ' .
		               '<span class="post-title" title="%title">%title</span>';
		print_navigation_button( $type, $button_text );
	}
}

add_shortcode( 'colibri_archive_pagination',
	'\ExtendBuilder\colibri_archive_pagination' );


function colibri_archive_pagination() {
  	$content = render_pagination( '\ExtendBuilder\numbers_pagination' );
	return $content;
}

add_shortcode( 'colibri_archive_nav_button',
	'\ExtendBuilder\colibri_archive_nav_button' );


function colibri_archive_nav_button( $attrs = array() ) {
	$atts = shortcode_atts(
		array(
			'type'       => 'next',
			'next_label' => '',
			'prev_label' => ''
		),
		$attrs
	);
  $content = render_pagination( '\ExtendBuilder\button_pagination', $atts );
	return $content;
}

function numbers_pagination( $args, $atts ) {
	$links = paginate_links( $args );
	$empty
	       = '<span class="page-numbers current">1</span> <a class="page-numbers">2</a>';
	$nav_links = ( is_customize_preview() ? $empty : $links );

	if ($nav_links) {
      $template
          = '<div class="navigation" role="navigation">
            <h2 class="screen-reader-text">' . $args["screen_reader_text"] . '</h2>
            <div class="nav-links"><div class="numbers-navigation">'
          . $nav_links . '</div></div>
            </div>';
      echo $template;
  }
}

function button_pagination( $args, $atts )
{
    $type = $atts['type'];
    $nav_direction = get_nav_direction_wp_name($type);
    $label = $atts["{$type}_label"];
    $fct_name = "get_{$nav_direction}_posts_link";
    $link = is_customize_preview()
        ? '<a>' . esc_html( $label ) . '</a>'
        : call_user_func($fct_name, '<span>' . esc_html( $label ). '</span>' );
    if ($link) {
        ?>
      <div class="navigation" role="navigation">
        <h2 class="screen-reader-text"><?php echo esc_html($args['screen_reader_text']); ?></h2>
        <div class="nav-links">
          <div class="<?php echo esc_attr($type) ?>-navigation"><?php echo $link // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
        </div>
      </div>
        <?php
    }
}


function render_pagination(
	$pagination_type,
	$atts = array(),
	$args = array()
) {
	$args = wp_parse_args( $args, array(
		'before_page_number' => '<span class="meta-nav screen-reader-text">'
		                        . __( 'Page', 'colibri-page-builder' )
		                        . ' </span>',
		'prev_text'          => '',
		'next_text'          => '',
		'prev_next'          => false,
		'screen_reader_text' => __( 'Posts navigation',
			'colibri-page-builder' ),
	) );

	if ( is_customize_preview() ) {
		global $wp_query, $paged;

		if ( isset($wp_query->query['paged']) && $wp_query->query['paged'] ) {
			$paged = $wp_query->query['paged'];
		}
	}


  	ob_start();
	call_user_func( $pagination_type, $args, $atts );
  	return ob_get_clean();
}


