<?php
namespace ExtendBuilder;

function shortcode_render_can_apply_forminator_filters( $shortcode ) {
	if ( ! class_exists( "Forminator_Render_Form" ) ) {
		return false;
	}


	if ( strpos( $shortcode, 'forminator_form' ) !== false ) {
		return true;
	}

	return false;
}


add_action( 'colibri_page_builder/customizer/before_render_shortcode', function ( $shortcode ) {
	if(colibri_shortcode_is_colibri_contact_form($shortcode)) {
		$shortcode = colibri_get_colibri_contact_form_shortcode($shortcode);
	}
	if ( shortcode_render_can_apply_forminator_filters( $shortcode ) ) {
		$id_found = preg_match('/id="(\d+)"/', $shortcode, $matches);
		if(!$id_found) {
			return;
		}
		$form_id = $matches[1];

		remove_all_actions( 'wp_enqueue_scripts' );
		remove_all_actions( 'wp_print_footer_scripts' );
		remove_all_actions( 'wp_print_styles' );

		$form_class = null;
		//old
		$is_old = false;
		if(class_exists('\Forminator_Custom_Form_Model')) {
			$form_class = '\Forminator_Custom_Form_Model';
			$is_old = true;
		}
		//new
		if(class_exists('\Forminator_Form_Model')) {
			$form_class = '\Forminator_Form_Model';
		}
		if(!$form_class) {
			return;
		}
		try {
			$model = $form_class::model()->load( $form_id );
			if ( ! $model ) {
				return;
			}
			//$model->settings['form-style'] = 'none';
			$assets = new \Forminator_Assets_Enqueue_Form( $model, true );
			if($is_old) {
				$assets->load_assets();
			} else {
				$form = \Forminator_CForm_Front::get_instance();
				$form->model = $model;
				$assets->enqueue_styles($form);
				$assets->enqueue_scripts($form);
			}


			ob_start();
			wp_print_styles();
			$ob_content = ob_get_clean();
			echo "\n\n<!--header  shortcode=wpforms scripts-->\n{$ob_content}<!--header scripts-->\n\n";
		} catch(\Exception $exception) {
			echo '';
		}

	}
}, 10 );
