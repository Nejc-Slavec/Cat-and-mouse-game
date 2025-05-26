<?php

namespace ExtendBuilder;


add_shortcode( 'colibri_contact_form', '\ExtendBuilder\colibri_contact_form_shortcode' );

function colibri_shortcode_is_colibri_contact_form( $shortcode ) {
	return strpos( $shortcode, 'colibri_contact_form' ) !== false;
}

function colibri_get_colibri_contact_form_shortcode( $shortcode ) {

	$matches_found = preg_match( "/shortcode=\"(.+)\"/", $shortcode, $matches );
	if ( ! $matches_found ) {
		return null;
	}
	$inner_shortcode = $matches[1];

	return colibri_shortcode_decode( $inner_shortcode );

}

function colibri_contact_form_shortcode( $atts ) {

	$atts = shortcode_atts(
		array(
			'shortcode'           => '',
			'use_shortcode_style' => '0'
		),
		$atts
	);

	$atts['shortcode'] = colibri_shortcode_decode( $atts['shortcode'] );
	$shortcode         = wp_kses_post($atts['shortcode']);
	if ( shortcode_render_can_apply_forminator_filters( $shortcode ) ) {
		if ( is_customize_preview() && colibri_forminator_is_auth_form( $shortcode ) ) {
			return colibri_forminator_get_auth_placeholder();
		}
		if ( $atts['use_shortcode_style'] == '0' ) {
			return colibri_forminator_form_shortcode( $shortcode );
		} else {
			return do_shortcode( $shortcode );
		}
	} else {
		return do_shortcode( $shortcode );
	}
}

function colibri_forminator_get_auth_placeholder() {
	return '<p class="shortcode-placeholder-preview">Forminator\'s login and register forms are not visible if you are logged in</p>';
}

function colibri_forminator_is_auth_form( $shortcode ) {
	$id_found = preg_match( '/id="(\d+)"/', $shortcode, $matches );
	if ( ! $id_found ) {
		return false;
	}
	$form_id    = $matches[1];
	$form_class = null;

	//free
	if ( class_exists( '\Forminator_Custom_Form_Model' ) ) {
		$form_class = '\Forminator_Custom_Form_Model';
	}
	//pro
	if ( class_exists( '\Forminator_Form_Model' ) ) {
		$form_class = '\Forminator_Form_Model';
	}

	if ( ! $form_class ) {
		return false;
	}
	try {
		$model = $form_class::model()->load( $form_id );
		if ( ! $model ) {
			return false;
		}

		return in_array( $model->settings['form-type'], array( 'login', 'registration' ) );
	} catch ( \Exception $e ) {
		return false;
	}
}

function colibri_forminator_form_shortcode( $shortcode ) {

	$html             = do_shortcode( $shortcode );
	return $html;
//	$form_class_regex = "/<form(.*?)class=\"(.*?)\"/s";
//	$classes_found    = preg_match( $form_class_regex, $html, $matches );
//	if ( $classes_found ) {
//		$classes       = $matches[2];
//		$classes_array = explode( " ", $classes );
//		$valid_classes = [];
//		foreach ( $classes_array as $class ) {
//			$invalid_classes_prefix = [ 'forminator-custom-form', 'forminator-design' ];
//			$valid_class            = true;
//			foreach ( $invalid_classes_prefix as $prefix ) {
//				if ( strpos( $class, $prefix ) === 0 ) {
//					$valid_class = false;
//				}
//			}
//			if ( $valid_class ) {
//				$valid_classes[] = $class;
//			}
//		}
//		$valid_classes_string = implode( " ", $valid_classes );
//		$html                 = preg_replace( $form_class_regex, "<form $1 class=\"" . $valid_classes_string . "\"",
//			$html );
//	}
//
//
//	return $html;
//	$id_found = preg_match('/id="(\d+)"/', $shortcode, $matches);
//	if(!$id_found) {
//		return;
//	}
//	$form_id = $matches[1];
//	$model = \Forminator_Custom_Form_Model::model()->load( $form_id );
//	if(!$model) {
//		return;
//	}
//	$model->settings['form-style'] = 'none';
//	$assets = new \Forminator_Assets_Enqueue_Form( $model, true );
//	$assets->load_assets();
//
//	$preview_data = [
//		'settings' => $model->settings,
//		'fields' => $model->fields
//	];
//	$data = [
//		'id' => $form_id,
//		'is_preview' => false,
//		'preview_data' => $preview_data
//	];
//	echo \Forminator_CForm_Front::get_instance()->render_shortcode($data);
//	\Forminator_CForm_Front::get_instance()->display(237);
	//echo do_shortcode($shortcode);
}
