<?php

namespace ExtendBuilder;

add_shortcode( 'colibri_breadcrumb_element', '\ExtendBuilder\colibri_breadcrumb_element_shortcode' );


function colibri_breadcrumb_element_shortcode( $atts ) {
    $colibri_breadcrumb_index = 0;
    $colibri_breadcrumb_index = intval( get_theme_mod( 'colibri_breadcrumb_element_index', 0 ) );
    set_theme_mod( 'colibri_breadcrumb_element_index',
        $colibri_breadcrumb_index === PHP_INT_MAX ? 0 : $colibri_breadcrumb_index + 1 );
    $atts = shortcode_atts(
        array(
            'id'               => 'colibri-breadcrumb-' . ( $colibri_breadcrumb_index ),
            'separator_symbol' => '/',
            'prefix'           => '',
            'home_as_icon'     => '0',
            'home_icon'        => '',
            'home_label'       => '',
            'use_prefix'       => '1',
        ),
        $atts
    );

    $breadcrumb_separator    = urldecode( $atts['separator_symbol'] );
    $use_prefix              = $atts['use_prefix'];
    $breadcrumb_prefix       = $atts['prefix'];
    $home_as_icon            = ! ! $atts['home_as_icon'];
    $home_icon               = urldecode( base64_decode( $atts['home_icon'] ) );
    //remove the script tag
    $home_icon               = wp_kses_post($home_icon);
    $lana_breadcrumb_options = array(
        'home_as_icon' => $home_as_icon,
        'home_label'   => $atts['home_label']
    );

    if ( $home_icon ) {
        $lana_breadcrumb_options['home_icon'] = $home_icon;
    }

    ob_start();

    ?>

    <div class="<?= esc_attr($atts['id']) ?>-dls-wrapper breadcrumb-items__wrapper">
        <?php if ( $use_prefix ): ?>
            <span class="breadcrumb-items__prefix"><?php echo wp_kses_post($breadcrumb_prefix); ?></span>
        <?php endif; ?>
        <?php
        /**
         *  Workaround to issue with wp_query flags. It does not put the is_page or is_home flags because the parse_query that
         * sets this flags doesn't set the is_page or is_home flag if the is_single one is set. We are creating the query
         * for shortcodes in this function: "shortcodeRefresh"
         */
        global $post;
        global $wp_query;
        $modifiedFlags = [ 'is_page', 'is_home', 'is_single', 'is_singular' ];
        $backup_flags  = [];

        //backup wp_query flags
        foreach ( $modifiedFlags as $flag ) {
            $backup_flags[ $flag ] = $wp_query->{$flag};
        }

        $pageID = get_option( 'page_on_front' );
        if ( $post && $pageID == $post->ID ) {
            $wp_query->is_home     = true;
            $wp_query->is_single   = false;
            $wp_query->is_singular = false;
        }
        if ( $post && $post->post_type === 'page' && ! $wp_query->is_home ) {
            $wp_query->is_page = true;
        }


        if ( function_exists( 'is_woocommerce' ) && is_woocommerce() && function_exists( 'woocommerce_breadcrumb' ) ) {
            $woocommerce_breadcrumb_wrap_before = '<ol class="breadcrumb colibri-breadcrumb">';

            if ( $home_as_icon ) {
                $woocommerce_breadcrumb_wrap_before .= sprintf( '<li class="breadcrumb-item"><a href="%s">%s</a></li>',
                    esc_url( home_url() ),
                    $home_icon
                );
            }

            if ( ! is_shop() ) {

                if ( intval( get_option( 'page_on_front' ) ) !== wc_get_page_id( 'shop' ) ) {
                    $shop_page_name = wc_get_page_id( 'shop' ) ? get_the_title( wc_get_page_id( 'shop' ) ) : '';

                    if ( ! $shop_page_name ) {
                        $product_post_type = get_post_type_object( 'product' );
                        $shop_page_name    = $product_post_type->labels->name;
                    }


                    $woocommerce_breadcrumb_wrap_before .= sprintf( '<li class="breadcrumb-item"><a href="%s">%s</a></li>',
                        esc_url( get_post_type_archive_link( 'product' ) ),
                        esc_html($shop_page_name)
                    );
                }
            } else {
                if ( is_front_page() ) {
                    ?>
                    <ol class="breadcrumb colibri-breadcrumb">
                        <li class="breadcrumb-item"><a
                                    href="<?php echo esc_url( home_url() ); ?>"><?php echo( $home_as_icon ? $home_icon : wp_kses_post($atts['home_label']) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
                        </li>
                    </ol>
                    <?php

                }
            }

            woocommerce_breadcrumb( array(
                'delimiter'   => '',
                'home'        => $home_as_icon ? false : convertStrSpaceToHtml(wp_kses_post($atts['home_label'])),
                'wrap_before' => $woocommerce_breadcrumb_wrap_before,
                'wrap_after'  => '</ol>',
                'before'      => '<li class="breadcrumb-item">',
                'after'       => '</li>',
            ) );
        } else {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo lana_breadcrumb( $lana_breadcrumb_options );
        }


        //restore wp_query flags
        foreach ( $modifiedFlags as $flag ) {
            $wp_query->{$flag} = $backup_flags[ $flag ];
        }
        ?>
    </div>
    <?php

    $breadcrumb = ob_get_clean();


    ob_start();

    $breadcrumb_selector = strip_tags('#' . $atts['id']);

    ?>
    <style type="text/css">
        /* breadcrumb separator symbol */

        <?php echo strip_tags($breadcrumb_selector); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped?>
        .colibri-breadcrumb > li + li:before {
            content: "<?php echo strip_tags($breadcrumb_separator); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped?>";
            white-space: pre;
        }
    </style>

    <?php

    $style      = ob_get_clean();
    $breadcrumb = $style . $breadcrumb;
    $atts_id = esc_attr($atts['id']);
    return "<div id='{$atts_id}' class='breadcrumb-wrapper'>{$breadcrumb}</div>";

}

