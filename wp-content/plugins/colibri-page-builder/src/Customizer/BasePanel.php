<?php

namespace ColibriWP\PageBuilder\Customizer;

class BasePanel extends \WP_Customize_Panel {
	protected $cpData = null;

	public function __construct( $manager, $id, $cpData = array() ) {
		$this->cpData = $cpData;

		$args       = ( isset( $this->cpData['wp_data'] ) ) ? $this->cpData['wp_data'] : array();
		$this->type = $this->companion()->customizer()->removeNamespace( "\\" . get_class( $this ) );

		parent::__construct( $manager, $id, $args );

		if ( ! $this->isClassic() ) {
			$this->manager->register_panel_type( "\\" . get_class( $this ) );
		}


		$this->init();
	}

	protected function init() {
		return true;
	}

	final protected function companion() {
		return \ColibriWP\PageBuilder\PageBuilder::instance();
	}

	public function active_callback() {
		return ! $this->isDisabled();
	}

	public function addSections( $data ) {
		if ( $this->isDisabled() ) {
			return;
		}


		$customizerData = $this->companion()->customizer()->cpData;

		if ( ! isset( $customizerData['sections'] ) ) {
			$customizerData['sections'] = array();
		}

		$customizerData['sections'] = \ColibriWP\PageBuilder\Utils\Utils::mergeArrays( $data, $customizerData['sections'] );

		$this->companion()->customizer()->cpData = $customizerData;
	}

	public function addSettings( $data ) {
		if ( $this->isDisabled() ) {
			return;
		}

		$customizerData = $this->companion()->customizer()->cpData;

		if ( ! isset( $customizerData['settings'] ) ) {
			$customizerData['settings'] = array();
		}

		$customizerData['settings'] = \ColibriWP\PageBuilder\Utils\Utils::mergeArrays( $data, $customizerData['settings'] );

		$this->companion()->customizer()->cpData = $customizerData;
	}


	public function addControls( $data ) {
		if ( $this->isDisabled() ) {
			return;
		}

		$customizerData = $this->companion()->customizer()->cpData;

		if ( ! isset( $customizerData['controls'] ) ) {
			$customizerData['controls'] = array();
		}

		$customizerData['controls'] = \ColibriWP\PageBuilder\Utils\Utils::mergeArrays( $data, $customizerData['controls'] );

		$this->companion()->customizer()->cpData = $customizerData;
	}

	public function isClassic() {
		return ( isset( $this->cpData['mode'] ) && $this->cpData['mode'] === "classic" );
	}

	public function isDisabled() {
		return ( isset( $this->cpData['disabled'] ) && $this->cpData['disabled'] === true );
	}
}
