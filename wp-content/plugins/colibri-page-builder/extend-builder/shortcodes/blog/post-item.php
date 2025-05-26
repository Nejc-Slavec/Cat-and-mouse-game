<?php

namespace ExtendBuilder;

add_shortcode( 'colibri_post_title', '\ExtendBuilder\colibri_post_title' );
add_shortcode( 'colibri_post_content', '\ExtendBuilder\colibri_post_content' );
add_shortcode( 'colibri_post_excerpt', '\ExtendBuilder\colibri_post_excerpt' );
add_shortcode( 'colibri_post_categories',
	'\ExtendBuilder\colibri_post_categories' );
add_shortcode( 'colibri_post_tags', '\ExtendBuilder\colibri_post_tags' );

add_shortcode( 'colibri_post_class', function () {
	return esc_attr(join( " ", get_post_class() ));
} );

add_shortcode( 'colibri_post_id', function () {
	return get_the_ID();
} );

add_shortcode( 'colibri_post_thumbnail', function ( $attrs = array() ) {
	$atts = shortcode_atts(
		array(
			'link' => 'true',
		),
		$attrs
	);
	ob_start();
	if ( has_post_thumbnail() ) {
		if ( $atts['link'] != "true" ) {
			echo get_the_post_thumbnail();
		} else {
			?>
            <a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>">
				<?php echo get_the_post_thumbnail(); ?>
            </a>
			<?php
		}
	}

	return ob_get_clean();
} );
add_shortcode( 'colibri_post_link', function () {
	return esc_url(get_the_permalink());
} );

add_shortcode( 'colibri_post_thumbnail_url', function () {
	return esc_url(get_the_post_thumbnail_url( null, 'post-thumbnail' ));
} );


add_shortcode( 'colibri_post_meta_date_url', function () {
	$id   = get_the_ID();
	$link = get_day_link( get_post_time( 'Y', false, $id, true ),
		get_post_time( 'm', false, $id, true ),
		get_post_time( 'j', false, $id, true ) );

	return esc_url($link);
} );

add_shortcode( 'colibri_post_meta_author_url', function () {
	return esc_url(get_author_posts_url( get_the_author_meta( 'ID' ) ));
} );

add_shortcode( 'colibri_post_meta_time_url', function () {
	ob_start();

	return ob_get_clean();
} );

add_shortcode( 'colibri_post_meta_comments_url', function () {
	return esc_url(get_comments_link());
} );

add_shortcode( 'colibri_post_meta_comments_content', function () {
	return esc_html(get_comments_number());
} );

add_shortcode( 'colibri_post_meta_author_content', function () {
	return esc_html(get_the_author_meta( 'display_name', get_the_author_meta( 'ID' ) ));
} );

add_shortcode( 'colibri_post_meta_time_content', function () {
	return get_the_time();
} );

add_shortcode( 'colibri_post_meta_date_content', function ( $atts ) {

    $format =  apply_filters( 'colibri_post_meta_date_content', $atts['date_format'] );
    $date =  get_the_date( $format);
    $content = apply_filters( 'colibri_post_meta_date_content_output', $date, $format);
    return $content;
} );


add_shortcode( 'colibri_post_thumbnail_classes', function () {
	$result = 'colibri-post-has-no-thumbnail';

	if ( has_post_thumbnail() ) {
		$result = 'colibri-post-has-thumbnail';
	}

	return $result;
} );

function colibri_post_categories( $attrs = array() ) {
	$categories = get_the_category( get_the_ID() );
	$atts       = shortcode_atts(
		array(
			'prefix' => '',
		),
		$attrs
	);

	$html = "";
	if ( $atts['prefix'] !== '' ) {
		$html .= '<span class="d-inline-block categories-prefix">' . colibri_esc_html_preserve_spaces( $atts['prefix'] ) . '</span>';
	}
	if ( $categories ) {
		foreach ( $categories as $category ) {
			$html .= sprintf( '<a class="d-inline-block colibri-post-category" href="%1$s">%2$s</a>',
				esc_url( get_category_link( $category->term_id ) ),
				esc_html( $category->name )
			);
		}
	} else {
		$html .= sprintf( '<span class="d-inline-block colibri-post-category">%s</span>', __('No Category', 'colibri-page-builder') );
	}

	return $html;
}

function colibri_post_tags( $attrs = array() ) {
	$atts = shortcode_atts(
		array(
			'prefix' => '',
		),
		$attrs
	);
	$tags = get_the_tags( get_the_ID() );
	$html = '';
	if ( $atts['prefix'] !== '' ) {
		$html .= '<span class="d-inline-block tags-prefix">' . colibri_esc_html_preserve_spaces( $atts['prefix'] ) . '</span>';
	}
	if ( $tags ) {
		foreach ( $tags as $tag ) {
			$tag_link       = get_tag_link( $tag->term_id );
			$sanitized_name = esc_html($tag->name);

			$html .= "<a class=\"d-inline-block colibri-post-tag\" href=\"{$tag_link}\" title=\"{$sanitized_name} Tag\">";
			$html .= "{$sanitized_name}</a>";
		}
	} else {
		$html .= sprintf( '<span class="d-inline-block colibri-post-tag">%s</span>', __('No Tag', 'colibri-page-builder') );
	}

	return $html;
}

function colibri_post_thumb( $atts ) {
	ob_start();
	?>
	<?php

	if ( has_post_thumbnail() ) {
		if ( $atts['single_post'] === 'false' ) {
			?>
            <a href="<?php the_permalink(); ?>">
				<?php
				the_post_thumbnail();
				?>
            </a>
			<?php
		} else {
			the_post_thumbnail();
		}
	} else {
		if ( $atts['show_placeholder'] === 'true' ) {
			if ( $atts['single_post'] === 'false' ) {
				?>
                <a href="<?php the_permalink(); ?>">
                    <div class="colibri_placeholder_image">
                        <div style="background-color:<?php echo esc_attr( $atts['placeholder_color'] ); ?> "></div>
                    </div>
                </a>
				<?php
			} else {
				?>
                <div class="colibri_placeholder_image">
                    <div style="background-color:<?php echo esc_attr( $atts['placeholder_color'] ); ?> "></div>
                </div>
				<?php
			}
		} ?>
		<?php
	}

	return ob_get_clean();
}

function colibri_post_title( $attrs = array() ) {

	$atts = shortcode_atts(
		array(
			'heading_type' => 'h3',
			'classes'      => 'colibri-word-wrap'
		),
		$attrs
	);

	$heading_type = $atts['heading_type'];

	if (!in_array($heading_type, array('h1', 'h2', 'h3', 'h4', 'h5', 'h6'))) {
		$heading_type = 'h3';
	}

	$title_tempalte = '<a href="%1$s"><%2$s class="%4$s">%3$s</%2$s></a>';

	return sprintf( $title_tempalte,
		get_the_permalink(),
		$heading_type,
		get_the_title(),
		esc_attr($atts['classes'])
	);

}

function colibri_post_excerpt_length( $value ) {

	if ( $length = colibri_cache_get( 'post_excerpt_length' ) ) {
		$value = $length;
	}

	return $value;
}

function colibri_post_excerpt( $attrs = array() ) {

	$atts = shortcode_atts(
		array(
			'max_length' => '',
		),
		$attrs
	);


	if ( is_numeric( $atts['max_length'] ) ) {
		colibri_cache_set( 'post_excerpt_length', $atts['max_length'] );

		if ( ! has_filter( 'excerpt_length', "\Extendbuilder\colibri_post_excerpt_length" ) ) {
			add_filter( 'excerpt_length', "\Extendbuilder\colibri_post_excerpt_length" );
		}

	}

	return '<div class="colibri-post-excerpt">' . wp_kses_post(get_the_excerpt()) . '</div>';

}

//TODO: fix isMaintainable page in Companion class and remove this.
function colibri_blog_print_post_content_add_wpautop( $content ) {
	add_filter( 'the_content', 'wpautop' );

	return $content;
}

function colibri_post_content() {
	ob_start();

	add_filter( 'the_content',
		'\ExtendBuilder\colibri_blog_print_post_content_add_wpautop', 5 );
	the_content();

	if ( false !== has_filter( 'the_content', 'wpautop' ) ) {
		remove_filter( 'the_content', 'wpautop' );
	}

	return ob_get_clean();
}



