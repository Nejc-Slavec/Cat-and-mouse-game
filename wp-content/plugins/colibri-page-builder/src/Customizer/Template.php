<?php

namespace ColibriWP\PageBuilder\Customizer;

use function ExtendBuilder\is_customize_changeset_preview;
use function ExtendBuilder\is_shortcode_refresh;

class Template {

	public static function load() {

		add_filter( 'colibri_page_builder/customizer/global_data', array( __CLASS__, '__prepareStaticSections' ) );

		add_filter( 'the_content', array( __CLASS__, 'filterContent' ), 0 );

		add_filter( 'template_include', array( __CLASS__, 'filterTemplateFile' ) );

	}


	public static function filterContent( $content ) {
		$companion = \ColibriWP\PageBuilder\PageBuilder::instance();
		if ( $companion->isMaintainable() ) {
			remove_filter( 'the_content', 'wpautop' );

			return Template::content( $content, false );
		}

		return $content;
	}

	public static function content( $content = null, $echo = true ) {

		if ( $content === null ) {
			// directly  callfor the page content
			ob_start();
			remove_filter( 'the_content', 'wpautop' );
			the_content();
			$content = ob_get_clean();
		} else {
			$content = apply_filters( 'colibri_page_builder/template/page_content', $content );
		}

		if ( $echo ) {
			echo $content;
		} else {
			return $content;
		}
	}

	public static function filterTemplateFile( $template ) {
		global $post;
		$companion = \ColibriWP\PageBuilder\PageBuilder::instance();
		$companion->loadMaintainablePageAssets( $post, $template );
		$template = apply_filters( 'colibri_page_builder/template', $template, $companion, $post );

		return $template;
	}

	public static function __prepareStaticSections( $globalData ) {
		$globalData['contentSections'] = array();

		return $globalData;
	}

}
