<?php

namespace ExtendBuilder;

use ColibriWP\PageBuilder\PageBuilder;

add_shortcode('colibri_gallery', '\ExtendBuilder\colibri_gallery_shortcode');


$colibri_gallery_index = 0;

function colibri_gallery_placeholder($atts, $defaultNumberOfPictures, $galleryImageOverlayClass)
{

    $atts_id = esc_attr($atts['id']);
    ob_start();
    ?>
    <div class="<?php echo esc_attr($atts['id']) ?>-dls-wrapper gallery-items-wrapper">
        <?php for ($img = 0; $img < $defaultNumberOfPictures; $img++): ?>
            <dl class="gallery-item masonry-item">
                <dt class="gallery-icon landscape">
                    <?php
                    $imgIndex = $img % 8 + 1;
                    $prefix   = ($atts['masonry'] == 1) ? 'masonry-' : '';
                    $imgURL   = PageBuilder::instance()->rootURL() . "/extend-builder/assets/images/{$prefix}{$imgIndex}.jpg";
                    ?>
                    <a <?php echo($atts['link'] === 'none' ? 'class="pointer-event-none"' : "") // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo($atts['lb'] == '1' ? "data-fancybox='{$atts_id}-group'" : "") // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            href="<?php echo esc_url($imgURL); ?>">
                        <img src="<?php echo esc_url( $imgURL); ?>" class="<?php echo esc_attr( $atts['id']); ?>-image" alt="">

                        <!-- Used for overlay option -->
                        <?php if ($galleryImageOverlayClass)  : ?>
                            <div class=<?= "{$galleryImageOverlayClass} " // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>></div>
                        <?php endif; ?>

                    </a>
                </dt>
                <div class=" gallery-caption__wrapper">
                    <dd class="wp-caption-text gallery-caption" style="display: none">
                        Image <?php echo ($img + 1); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> description
                    </dd>
                </div>
            </dl>
        <?php endfor; ?>
    </div>
    <?php

    $gallery = ob_get_clean();

    return $gallery;
}


function colibri_gallery_shortcode($atts)
{
    $columns_per_media         = array(
        'desktop' => $atts['columns-desktop'],
        'tablet' => $atts['columns-tablet'],
        'mobile' => $atts['columns-mobile'],
    );
    $atts['columns-per-media'] = $columns_per_media;
    global $colibri_gallery_index;
    $atts = shortcode_atts(
        array(
            'id' => 'ope-gallery-' . (++$colibri_gallery_index),
            'columns' => '4',
            'columns-per-media' => array(
                'desktop' => '4',
                'tablet' => '4',
                'mobile' => '1',
            ),
            'ids' => '',
            'link' => 'file',
            'lb' => '1',
            'orderby' => '',
            'skin' => 'skin01',
            'masonry' => '1',
            'size' => 'medium',
            'use_overlay' => '0',

            //deprecated
            'overlay' => '',
        ),
        $atts
    );

    $defaultNumberOfPictures = 8;
    //overlay
    $overlayColorExist = !!$atts['overlay'];
    $useOverlay        = $atts['use_overlay'] === '1' || $overlayColorExist;

    // Used for overlay option
    $galleryImageOverlayClass = '';
    if ($useOverlay) {
        $galleryImageOverlayClass = 'gallery-element-image-overlay';
    }

    if (empty($atts['ids'])) {
        $gallery = colibri_gallery_placeholder($atts, $defaultNumberOfPictures, $galleryImageOverlayClass);
    } else {


        add_filter('use_default_gallery_style', '__return_false');

        // make sure the gallery_shortcode function will return the default gallery
        // fixes japck issue
        add_filter('post_gallery', '__return_empty_string', PHP_INT_MAX);


        $gallery = gallery_shortcode($atts);

        remove_filter('post_gallery', '__return_empty_string', PHP_INT_MAX); // remove the previous filter
        remove_filter('use_default_gallery_style', '__return_false');

        $gallery = preg_replace("/<br(.*?)>/is", "", $gallery);
        $gallery = preg_replace("/<div(.*?)id='gallery-(.*?)>/",
            "<div $1 class='" . esc_attr($atts['id']) . "-dls-wrapper gallery-items-wrapper' >", $gallery);
        $gallery = preg_replace("/<img(.*)class=\"(.*?)\"/", "<img $1 class='" . esc_attr($atts['id']) . "-image'", $gallery);

        $gallery= preg_replace("/(class=['\"]gallery-item)/", "$1" . ' masonry-item', $gallery);

        //add caption wrapper
        $gallery= preg_replace('/(<dd class=["\']wp-caption-text.*?<\/dd>)/is', '<div class="gallery-caption__wrapper">'. "$1" . "</div>", $gallery);

        if (empty($gallery)) {
            $gallery = colibri_gallery_placeholder($atts, $defaultNumberOfPictures, $galleryImageOverlayClass);
        } else {
            //overlay
            $extraImageDivs = "";
            if ($galleryImageOverlayClass) {
                $gallery = preg_replace("/<dt(.*?)>/",
                    "<div class='" . $galleryImageOverlayClass . "'></div>", $gallery);
            }

            $gallery = $gallery . '<style>#' . esc_html($atts['id']) . ' .wp-caption-text.gallery-caption{display:none;}</style>';
        }


    }

    ob_start();

    ?>
    <style type="text/css">

        <?php

            $columns_media_sizes = array(
                    "desktop" => '@media (min-width: 1023px) {',
                    "tablet" => '@media (min-width: 768px) and (max-width: 1023px) {',
                    "mobile" => '@media (max-width: 767px) {',
            );

            foreach($atts['columns-per-media'] as $key => $value) {
                 $nr_columns_per_media = $value;
            if($columns_media_sizes[$key] !== null) {
                echo wp_kses_post($columns_media_sizes[$key]) ;
            }
        ?>
        #<?php echo wp_kses_post($atts['id']); ?>
        dl {
            float: left;
            width: <?php echo (100 / $nr_columns_per_media) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>% !important;
            max-width: <?php echo (100 / $nr_columns_per_media) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>% !important;
            min-width: <?php echo (100 / $nr_columns_per_media) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>% !important;
        }

        #<?php echo wp_kses_post($atts['id']); ?>
        dl:nth-of-type(<?php echo wp_kses_post($nr_columns_per_media); ?>n +1 ) {
            clear: both;
        }

        <?php

           //close media query
           if($columns_media_sizes[$key] !== null) {
              echo '}';
           }

           }
       ?>

        #<?php echo wp_kses_post($atts['id']); ?>
        .gallery-item {
            position: relative;
        }

        <?php if($useOverlay) : ?>
            #<?php echo wp_kses_post($atts['id']); ?>
            .gallery-item .gallery-element-image-overlay {
                position: absolute;
                top: 0;
                bottom: 0;
                left: 0;
                right: 0;
                pointer-events: none;
                z-index: 2;
                <?php if($overlayColorExist) echo sprintf(' background-color: %s;',wp_kses_post($atts['overlay']) )// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            }
        <?php endif; ?>

    </style>
    <?php


    $style = ob_get_clean();

    $gallery = $style . $gallery;

    if ($atts['lb'] == 1) {
        $gallery = preg_replace('/<a/', '<a data-fancybox="' . esc_attr($atts['id']) . '-group"', $gallery);
    }

	$atts_id = esc_attr($atts['id']);
    return "<div id='{$atts_id}' class='gallery-wrapper'>{$gallery}</div>";

}

