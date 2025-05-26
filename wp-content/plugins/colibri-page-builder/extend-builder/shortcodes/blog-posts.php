<?php

namespace ExtendBuilder;


add_shortcode( 'colibri_blog_posts', '\ExtendBuilder\colibri_blog_posts_shortcode' );
$icon_clock    = '<svg aria-hidden="true" width="24px" height="24px" data-prefix="far" data-icon="clock"
                 class="svg-inline--fa fa-clock fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg"
                 viewBox="0 0 512 512">
                <path fill="currentColor"
                      d="M256 8C119 8 8 119 8 256s111 248 248 248 248-111 248-248S393 8 256 8zm0 448c-110.5 0-200-89.5-200-200S145.5 56 256 56s200 89.5 200 200-89.5 200-200 200zm61.8-104.4l-84.9-61.7c-3.1-2.3-4.9-5.9-4.9-9.7V116c0-6.6 5.4-12 12-12h32c6.6 0 12 5.4 12 12v141.7l66.8 48.6c5.4 3.9 6.5 11.4 2.6 16.8L334.6 349c-3.9 5.3-11.4 6.5-16.8 2.6z"></path>
            </svg>';
$icon_user     = '<svg width="24px" height="24px" aria-hidden="true" data-prefix="far" data-icon="user"
                 class="svg-inline--fa fa-user fa-w-14" role="img" xmlns="http://www.w3.org/2000/svg"
                 viewBox="0 0 448 512">
                <path fill="currentColor"
                      d="M313.6 304c-28.7 0-42.5 16-89.6 16-47.1 0-60.8-16-89.6-16C60.2 304 0 364.2 0 438.4V464c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48v-25.6c0-74.2-60.2-134.4-134.4-134.4zM400 464H48v-25.6c0-47.6 38.8-86.4 86.4-86.4 14.6 0 38.3 16 89.6 16 51.7 0 74.9-16 89.6-16 47.6 0 86.4 38.8 86.4 86.4V464zM224 288c79.5 0 144-64.5 144-144S303.5 0 224 0 80 64.5 80 144s64.5 144 144 144zm0-240c52.9 0 96 43.1 96 96s-43.1 96-96 96-96-43.1-96-96 43.1-96 96-96z"></path>
            </svg>';
$icon_comment  = '<svg aria-hidden="true" width="24px" height="24px" data-prefix="far" data-icon="comment"
                 class="svg-inline--fa fa-comment fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg"
                 viewBox="0 0 512 512">
                <path fill="currentColor"
                      d="M256 32C114.6 32 0 125.1 0 240c0 47.6 19.9 91.2 52.9 126.3C38 405.7 7 439.1 6.5 439.5c-6.6 7-8.4 17.2-4.6 26S14.4 480 24 480c61.5 0 110-25.7 139.1-46.3C192 442.8 223.2 448 256 448c141.4 0 256-93.1 256-208S397.4 32 256 32zm0 368c-26.7 0-53.1-4.1-78.4-12.1l-22.7-7.2-19.5 13.8c-14.3 10.1-33.9 21.4-57.5 29 7.3-12.1 14.4-25.7 19.9-40.2l10.6-28.1-20.6-21.8C69.7 314.1 48 282.2 48 240c0-88.2 93.3-160 208-160s208 71.8 208 160-93.3 160-208 160z"></path>
            </svg>';
$icon_calendar = '<svg aria-hidden="true" width="24px" height="24px" data-prefix="far" data-icon="calendar"
                 class="svg-inline--fa fa-calendar fa-w-14" role="img" xmlns="http://www.w3.org/2000/svg"
                 viewBox="0 0 448 512">
                <path fill="currentColor"
                      d="M400 64h-48V12c0-6.6-5.4-12-12-12h-40c-6.6 0-12 5.4-12 12v52H160V12c0-6.6-5.4-12-12-12h-40c-6.6 0-12 5.4-12 12v52H48C21.5 64 0 85.5 0 112v352c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48V112c0-26.5-21.5-48-48-48zm-6 400H54c-3.3 0-6-2.7-6-6V160h352v298c0 3.3-2.7 6-6 6z"></path>
            </svg>';

$colibri_global_icons = array( $icon_clock, $icon_user, $icon_comment, $icon_calendar );

function colibri_blog_posts_has_category() {
	$categories = get_the_category();

	return ( count( $categories ) > 0 );
}

function colibri_blog_posts_the_category() {
	$categories   = get_the_category();
	$linkTemplate = '<a href="%1$s"  class="colibri_category_button">%2$s</a>';
	if ( ! count( $categories ) ) {
		return;
	}
	foreach ( $categories as $category ) {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		printf( $linkTemplate,
			esc_url( get_category_link( $category->term_id ) ),
			esc_html( $category->name )
		);
	}
}

function colibri_blog_posts_print_meta_data( $atts ) {
	ob_start();
	comments_link();
	$comment_link = ob_get_contents();
	ob_end_clean();

	global $colibri_global_icons;
	$metadata_hour             = '<a class="colibri_post_hour d-inline-block">' . $colibri_global_icons[0] . '
            <span class="d-inline-block">' . get_the_time() . '</span>
        </a>';
	$metadata_author           = '<a class="colibri_post_author d-inline-block" href="' . esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ) . '">' . $colibri_global_icons[1] . '
            <span>' . get_the_author() . '</span>
        </a>';
	$metadata_comments         = '<a class="colibri_post_comments d-inline-block" href="' . $comment_link . '">' . $colibri_global_icons[2] . '
            <span>' . get_comments_number() . '</span>
        </a>';
	$metadata_data             = '<a class="colibri_post_date d-inline-block" href="' . esc_url( get_day_link( get_post_time( 'Y' ),
			get_post_time( 'm' ), get_post_time( 'j' ) ) ) . '">' . $colibri_global_icons[3] . '
            <span>' . get_the_date( get_option( 'date_format' ) ) . '</span>
        </a>';
	$metadata_read_more_button = '<a class="colibri_post_read_more d-inline-flex" href="' . esc_url( get_permalink() ) . '">' . wp_kses_post($atts['button_text']) . '</a>';
	?>
    <div class="colibri_post_metadata  <?php if ( $atts['spacer_metadata'] === 'yes' )
		echo 'd-flex' ?>">
		<?php
		$left_div       = '';
		$right_div      = '';
		$left           = array();
		$right          = array();
		$metadata_order = explode( ",", trim( $atts['metadata_order'], ',' ) );
		$bool           = false;
		foreach ( $metadata_order as $key => $value ) {
			switch ( $value ) {
				case 'Time':
					if ( $bool ) {
						array_push( $right, $metadata_hour );
					} else {
						array_push( $left, $metadata_hour );
					}
					break;
				case 'Author':
					if ( $bool ) {
						array_push( $right, $metadata_author );
					} else {
						array_push( $left, $metadata_author );
					}
					break;
				case 'Comments':
					if ( $bool ) {
						array_push( $right, $metadata_comments );
					} else {
						array_push( $left, $metadata_comments );
					}
					break;
				case 'Date':
					if ( $bool ) {
						array_push( $right, $metadata_data );
					} else {
						array_push( $left, $metadata_data );
					}
					break;
				case 'Read Button':
					if ( $atts['single_post'] === 'false' ) {
						if ( $bool ) {
							array_push( $right, $metadata_read_more_button );
						} else {
							array_push( $left, $metadata_read_more_button );
						}
					}
					break;
				case 'Spacer':
					$bool = true;
			}
		}
		$separator = "<span>" . wp_kses_post($atts['metadata_separator']) . "</span>";
		foreach ( $right as $key => $value ) {
			if ( count( $right ) - 1 === $key ) {
				$right_div .= $value;
			} else {
				$right_div = $right_div . $value . $separator;
			}
		}
		foreach ( $left as $key => $value ) {
			if ( count( $left ) - 1 === $key ) {
				$left_div .= $value;
			} else {
				$left_div = $left_div . $value . $separator;
			}
		}
		if ( $atts['spacer_metadata'] === 'true' ) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<div class="left d-block" >' . $left_div . '</div><div class="spacer d-flex" style="margin:auto"></div><div class="right d-block" >' . wp_kses_post($right_div) . '</div>';
		} else {
			echo '<div class="d-block" >' . wp_kses_post($left_div) . '</div>';
		}
		?>
    </div>
	<?php
}

function colibri_blog_posts_post_thumb( $atts ) {
	?>
	<?php if ( has_post_thumbnail() ) {
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
                    <div class="colibri_post_placeholder_image">
                        <div style="background-color:<?php echo esc_attr( $atts['placeholder_color'] ); ?> "></div>
                    </div>
                </a>
				<?php
			} else {
				?>
                <div class="colibri_post_placeholder_image">
                    <div style="background-color:<?php echo esc_attr( $atts['placeholder_color'] ); ?> "></div>
                </div>
				<?php
			}
		} ?>
		<?php
	}
}

function colibri_blog_print_post_content() {
	add_filter( 'the_content', '\\ExtendBuilder\\colibri_blog_print_post_content_add_wpautop', 5 );
	the_content();

	if ( false !== has_filter( 'the_content', 'wpautop' ) ) {
		remove_filter( 'the_content', 'wpautop' );
	}

}

function colibri_blog_posts_normal_item( $atts ) {
	?>
	<?php
	$post_order = explode( ",", $atts['post_order'] );
	foreach ( $post_order as $key => $value ) {
		switch ( $value ) {
			case 'image':

				if ( $atts['show_image'] === 'true' ) : ?>
                    <div class="colibri_post_thumb">
						<?php colibri_blog_posts_post_thumb( $atts ); ?>
                    </div>
				<?php endif; ?>
				<?php

				break;
			case 'category':

				if ( $atts['show_category'] === 'true' && colibri_blog_posts_has_category() ): ?>
                    <div class="colibri_post_category">
						<?php colibri_blog_posts_the_category(); ?>
                    </div>
				<?php endif; ?>
				<?php

				break;
			case 'excerpt':

				if ( $atts['single_post'] === 'false' && $atts['show_excerpt'] === 'true' ) : ?>
                    <div class="colibri_post_excerpt">
						<?php
						add_filter( 'the_content', 'wpautop' );
						echo wp_kses_post(the_excerpt());
						remove_filter( 'the_content', 'wpautop' );
						?>
                    </div>
				<?php endif; ?>
				<?php if ( $atts['single_post'] === 'true' && $atts['show_excerpt'] === 'true' )  : ?>
                <div class="colibri_post_content">
					<?php colibri_blog_print_post_content(); ?>
                </div>
			<?php endif; ?>
				<?php

				break;
			case 'title':

				if ( $atts['show_title'] === 'true' ) : ?>
                    <div class="colibri_post_title">
                        <a href="<?php the_permalink(); ?>">
                            <<?php echo tag_escape($atts['title_type']) ?>
                            > <?php wp_kses_post(the_title()); ?>  </<?php echo tag_escape($atts['title_type']) ?>>
                        </a>
                    </div>
				<?php endif; ?>
				<?php

				break;
			case 'metaData':

				if ( $atts['show_metadata'] === 'true' ) : ?>
					<?php colibri_blog_posts_print_meta_data( $atts ); ?>
				<?php endif; ?>
				<?php

				break;

			case 'readButton':

				if ( $atts['show_read_more_button'] === 'true' && $atts['single_post'] === 'false' ) : ?>
					<?php
					echo '<a class="colibri_post_read_more d-inline-flex" href="' . esc_url( get_permalink() ) . '">' . wp_kses_post($atts['button_text']) . '</a>';
					?>
				<?php endif; ?>
				<?php

				break;
		}
	}
}


function colibri_blog_posts_shortcode( $attrs ) {
	ob_start(); ?>
	<?php
	$atts = shortcode_atts(
		array(
			'posts'                    => '3',
			'columns_desktop'          => "4",
			'columns_tablet'           => "6",
			'show_image'               => 'true',
			'show_category'            => 'true',
			'show_title'               => 'true',
			'title_type'               => 'h6',
			'show_excerpt'             => 'true',
			'excerpt_length'           => '55',
			'single_post'              => 'false',
			'show_metadata'            => 'true',
			'spacer_metadata'          => 'true',
			'show_read_more_button'    => 'true',
			'button_text'              => 'Read more',
			'show_placeholder'         => 'true',
			'placeholder_color'        => 'rgb(255,127,80)',
			'metadata_separator'       => '|',
			'metadata_order'           => '',
			'filter_categories'        => '',
			'filter_tags'              => '',
			'filter_authors'           => '',
			'order_by'                 => 'date',
			'order_type'               => 'ASC',
			'post_order'               => '',
			'classes_row_inside'       => '',
			'classes_row_outside'      => '',
			'horizontal_content_align' => 'text-left',
		),
		$attrs
	);
//    var_dump($atts);

	$cols_desktop = intval( $atts['columns_desktop'] );
	$post_numbers = ( $atts['posts'] ) ? $atts['posts'] : 12 / $cols_desktop;

	?>
	<?php
	$query = new \WP_Query( array(
		'posts_per_page' => $post_numbers,
		'category_name'  => $atts['filter_categories'],
		'tag'            => $atts['filter_tags'],
		'author'         => $atts['filter_authors'],
		'orderby'        => $atts['order_by'],
		'order'          => $atts['order_type'],
	) );
	?>
    <div class="<?php echo esc_attr(str_replace( ',', ' ', $atts['classes_row_outside'] )) ?>">
        <div class="<?php echo esc_attr(str_replace( ',', ' ', $atts['classes_row_inside'] )) ?>">
			<?php
			if ( $query->have_posts() ):
				while ( $query->have_posts() ):
					$query->the_post();

					if ( is_sticky() ) {
						continue;
					}
					colibri_blog_posts_content( $atts );
				endwhile;
				wp_reset_postdata();
			else:
				?>
                <div style="text-align: center; width: 100%"><?php echo __('No posts found', 'colbri-page-builder'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
			<?php
			endif;
			?>
        </div>
    </div>
	<?php
	$content = ob_get_contents();
	ob_end_clean();

	return $content;

}


function colibri_blog_posts_content( $atts ) {
	colibri_cache_set( 'excerpt_length', $atts['excerpt_length'] );
	add_filter( 'excerpt_length', '\ExtendBuilder\colibri_excerpt_length' );
	add_filter( 'excerpt_more', '\ExtendBuilder\colibri_excerpt_more' );
	$cols_desktop = intval( $atts['columns_desktop'] );
	$cols_tablet  = intval( $atts['columns_tablet'] );
	?>
    <div class="h-column h-column-container d-flex  masonry-item  h-col-lg-<?php echo esc_attr($cols_desktop); ?> h-col-md-<?php echo esc_attr($cols_tablet); ?>  h-col-xs-12"
         style="position: relative">
        <div class="d-flex h-flex-basis h-column__inner">
            <div class="h-column__content align-self-stretch" style="width: 100%;">
                <div id="post-<?php the_ID(); ?>"
                     class="colibri_blog_post <?php echo esc_attr($atts['horizontal_content_align']); ?>">
					<?php
					colibri_blog_posts_normal_item( $atts );
					?>
                </div>
            </div>
        </div>
    </div>
	<?php

	remove_filter( 'excerpt_length', '\ExtendBuilder\colibri_excerpt_length' );
	remove_filter( 'excerpt_more', '\ExtendBuilder\colibri_excerpt_more' );
}

function colibri_excerpt_length( $length ) {
	return colibri_cache_get( 'excerpt_length' );
}

function colibri_excerpt_more() {
	return "[&hellip;]";
}
