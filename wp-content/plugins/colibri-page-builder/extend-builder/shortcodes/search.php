<?php
namespace ExtendBuilder;

add_shortcode('colibri_search', function ($atts, $content) {

    do_action( 'pre_get_search_form' );
    $form = '<form role="search" '.(($atts['preview']==='false')?'onsubmit="return false"':'').' method="get" class="search-form colibri_search_form" action="' . esc_url( home_url( '/' ) ) . '">'.wp_kses_post(do_shortcode($content)).'</form>';

//	$result = apply_filters( 'get_search_form', $form );
//	if ( null === $result )
//		$result = $form;
    
	return $form;
});


