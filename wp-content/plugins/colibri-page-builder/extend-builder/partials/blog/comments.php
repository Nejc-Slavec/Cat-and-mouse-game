<?php
if ( post_password_required() ):
	return;
endif;
$colibri_post_comments_atts = \ExtendBuilder\colibri_cache_get( 'post_comments_atts' );

?>

<div id="comments" class="post-comments">
    <h4 class="comments-title">
    	<span class="comments-number">
            <?php comments_number(
                esc_html($colibri_post_comments_atts['none']),
                esc_html($colibri_post_comments_atts['one']),
	            str_replace( '{COMMENTS-COUNT}', '%', esc_html($colibri_post_comments_atts['multiple']) )
            ); ?>
    	</span>
    </h4>

    <ol class="comment-list">
		<?php
		wp_list_comments( array(
			'avatar_size' => esc_html($colibri_post_comments_atts['avatar_size']),
			'format'      => 'html5'
		) );
		?>
    </ol>

	<?php
	if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ):
		?>
        <div class="navigation">
            <div class="prev-posts">
				<?php previous_comments_link( __( '<i class="font-icon-post fa fa-angle-double-left"></i> Older Comments',
					'colibri-wp' ) ); ?>
            </div>
            <div class="next-posts">
				<?php next_comments_link( __( 'Newer Comments <i class="font-icon-post fa fa-angle-double-right"></i>',
					'colibri-wp' ) ); ?>
            </div>
        </div>
	<?php
	endif;
	?>

	<?php
	if ( ! comments_open() && get_comments_number() && post_type_supports( get_post_type(), 'comments' ) ):
		?>
        <p class="no-comments"><?php _e( 'Comments are closed.', 'colibri-wp' );// phpcs:ignore WordPress.Security.EscapeOutput.UnsafePrintingFunction?></p>
	<?php
	endif;
	?>

</div>
