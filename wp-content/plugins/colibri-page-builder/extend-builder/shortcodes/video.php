<?php
namespace ExtendBuilder;

add_shortcode( 'colibri_video_player', function ( $atts ) {
	ob_start();
	if ( key_exists( 'type', $atts ) && $atts['type'] === 'external' ) {
		colibri_html_embed_iframe( $atts['url'], $atts['autoplay'] );
	} else {
		colibri_html_embed_video( $atts['url'], $atts['attributes'] );
	}
	$content = ob_get_clean();

	return $content;
} );

function colibri_html_embed_iframe($url,$autoplay){

    $url = esc_url($url);

    // Parse the URL using wp_parse_url (safe wrapper)
    $parsed_url = wp_parse_url( $url );

    if (
        ! $parsed_url ||
        ! isset( $parsed_url['host'], $parsed_url['scheme'] ) ||
        ! in_array( $parsed_url['scheme'], [ 'https' ], true )
    ) {
        echo sprintf('<p>%s</p>', __('Invalid video URL. Only videos from youtube or vimeo are allowed, based on your video type','colibri-page-builder'));
        return;
    }


    // Whitelisted domains
    $allowed_domains = array(
        'youtube.com',
        'www.youtube.com',
        'youtu.be',
        'vimeo.com',
        'www.vimeo.com',
        'player.vimeo.com',
    );

    $host = strtolower( $parsed_url['host'] );

    if ( ! in_array( $host, $allowed_domains, true ) ) {
        echo sprintf('<p>%s</p>', __('Invalid video URL. Only videos from youtube or vimeo are allowed, based on your video type','colibri-page-builder'));
        return;
    }

    echo "<iframe src=".$url." class='h-video-main'".(($autoplay === 'true') ? 'allow="autoplay"' : '')."  allowfullscreen></iframe>";
}

function colibri_html_embed_video( $url, $attributes ) {
	$attrs         = explode( " ", $attributes );
	$allowed_attrs = [
		'controls',
		'muted',
		'loop',
		'autoplay'
	];

	$filtered_attrs = array_filter( $attrs, function ( $attr ) use ( $allowed_attrs ) {
		$cleaned_attribute = trim($attr);
        $cleaned_attribute = str_replace("\n", "", $cleaned_attribute);
        if ( ! in_array( $cleaned_attribute, $allowed_attrs ) ) {
			return false;
		}

		return true;
	} );



	echo "<video class='h-video-main' " . esc_attr( implode( " ", $filtered_attrs ) ) . " ><source src=" . esc_url( $url ) . " type='video/mp4' /></video>";
}


