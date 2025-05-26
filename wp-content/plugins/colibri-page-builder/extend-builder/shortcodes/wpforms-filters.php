<?php

namespace ExtendBuilder;

function shortcode_render_can_apply_wpforms_filters( $shortcode ) {
	if ( ! class_exists( "WPForms" ) ) {
		return false;
	}

	if ( strpos( $shortcode, '[wpforms' ) !== false ) {
		return true;
	}

	if ( strpos( $shortcode, 'wpforms' ) !== false ) {
		return true;
	}

	return false;
}

add_action( 'colibri_page_builder/customizer/before_render_shortcode', function ( $shortcode ) {
	if ( shortcode_render_can_apply_wpforms_filters( $shortcode ) ) {

		remove_all_actions( 'wp_enqueue_scripts' );
		remove_all_actions( 'wp_print_footer_scripts' );
		remove_all_actions( 'wp_print_styles' );

		\WPForms::instance()->frontend->assets_css();

		ob_start();
		wp_print_styles();
		$ob_content = ob_get_clean();
		echo "\n\n<!--header  shortcode=wpforms scripts-->\n{$ob_content}<!--header scripts-->\n\n";

	}
}, 10 );

add_action( 'colibri_page_builder/customizer/after_render_shortcode', function ( $shortcode ) {
	if(colibri_shortcode_is_colibri_contact_form($shortcode)) {
		$shortcode = colibri_get_colibri_contact_form_shortcode($shortcode);
	}
	if ( shortcode_render_can_apply_wpforms_filters( $shortcode ) ) {
		ob_start();
		?>
        <div class="wpforms-confirmation-container-full colibri-hidden">
            <p>This is a success message preview text</p>
        </div>
		<?php
		wp_print_footer_scripts();
		$ob_content = ob_get_clean();
		echo "\n\n<!--footer scripts shortcode=wpforms-->\n{$ob_content}<!--footer scripts-->\n\n";
	}

}, 10 );
