<?php


namespace ColibriWP\PageBuilder\DemoImport;


class DataApi {

	private $api_endpoint = "https://app.colibriwp.com/api/project/demo-sites";

	public function __construct() {

		$this->api_endpoint = apply_filters( 'colibri_page_builder/demo_api_endpoint', $this->api_endpoint );

		add_filter( 'extendthemes-ocdi/import_files', array( $this, 'registerImportFiles' ) );

		if ( is_admin() ) {
			$this->registerImportFiles();
		}
	}

	public function registerImportFiles() {
		$remote_files = get_option( 'colibri_page_builder_demo_sites', array(
			'timestamp' => time() - 2 * DAY_IN_SECONDS,
			'data'      => array()
		) );

		$is_debug = defined( 'COLIBRI_DEBUG' ) && COLIBRI_DEBUG;
		if ( ( $remote_files['timestamp'] + DAY_IN_SECONDS <= time() ) || $is_debug ) {
			$remote_files = $this->fetchRemoteData();
		}

		if ( isset( $remote_files['data'] ) ) {
			return $remote_files['data'];
		}

		return array();
	}

	public function fetchRemoteData() {
		$data = wp_remote_get( $this->api_endpoint, array(
			'body'      => array(),
			'verifyssl' => false
		) );

		$remote_files = [];

		if ( $data instanceof \WP_Error ) {
			return $remote_files;
		}

		$body = wp_remote_retrieve_body( $data );
		$body = json_decode( $body, true );

		$remote_files = array(
			'timestamp' => time(),
			'data'      => $body['body']
		);

		update_option( 'colibri_page_builder_demo_sites', $remote_files );

		return $remote_files;
	}
}
