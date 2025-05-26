<?php

namespace ColibriWP\PageBuilder\Customizer;

class Translations {
	private static $translationMap = array();

	private static function getStringsArray() {
		return apply_filters( 'colibri_page_builder/customizer/translation_strings',
			array(
				// @TODO add translation here
				// example:   array(
				//		            "original"   => "square round outline",
				//		            "translated" => __("square round outline", "colibri-page-builder"),
				//	            ),
			)
		);
	}


	static public function getTranslations() {

		if ( ! static::$translationMap ) {
			foreach ( static::getStringsArray() as $match ) {
				static::$translationMap[ $match['original'] ] = $match['translated'];
			}
		}

		return (object) static::$translationMap;
	}
}
