<?php

namespace ColibriWP\PageBuilder\Customizer;

class ThemeSupport {
	private static $defaultSupport = array();

	private static $defaultsReady = false;

	private static function setDefault() {

		if ( self::$defaultsReady ) {
			return;
		}

		self::$defaultsReady = true;

		$companion            = \ColibriWP\PageBuilder\PageBuilder::instance();
		self::$defaultSupport = array(
			'custom-background' => array(
				'default-color'      => "#F5FAFD",
				'default-repeat'     => 'no-repeat',
				'default-position-x' => 'center',
				'default-attachment' => 'fixed',
			),
		);
	}

	public static function load() {
		self::setDefault();

		$supports = apply_filters( 'colibri_pagebuilder/theme_support', static::$defaultSupport );

		foreach ( $supports as $key => $value ) {
			add_theme_support( $key, $value );
		}
	}
}
