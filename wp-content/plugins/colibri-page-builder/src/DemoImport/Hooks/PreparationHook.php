<?php


namespace ColibriWP\PageBuilder\DemoImport\Hooks;


use ColibriWP\PageBuilder\DemoImport\DemoImport;
use ColibriWP\PageBuilder\OCDI\Helpers;
use ColibriWP\PageBuilder\OCDI\Importer;
use ColibriWP\PageBuilder\OCDI\OneClickDemoImport;
use Plugin_Upgrader;
use ProteusThemes\WPContentImporter2\WXRImporter;
use function ExtendBuilder\array_get_value;
use function ExtendBuilder\custom_post_prefix;
use function ExtendBuilder\register_custom_post_type;

class PreparationHook extends ImportHook {

	function transientKey() {
		return 'informations';
	}

	public function run() {
		$self = $this;
		add_filter( 'wxr_importer.pre_process.post', function ( $data ) use ( $self ) {
            update_option( 'colibriwp_predesign_front_page_index', 0 );
			if ( ! $self->getTransient( 'plugins_installed', false ) ) {
				$self->setTransient( 'plugins_installed', true );
				$self->installPlugins( $data );
			}

			return $data;
		}, 0 );
        add_action( 'extendthemes-ocdi/before_content_import_execution', array( $this, 'clear' ), 1, 3 );
        add_action( 'extendthemes-ocdi/before_content_import_execution', array( $this, 'beforeImportContent' ), 10, 3 );
        add_action( 'extendthemes-ocdi/after_all_import_execution', array(
            $this,
            'emptyGlobalTransient'
        ), PHP_INT_MAX );
	}

	public function installPlugins( $pre_process_data ) {
		$importer_data = OneClickDemoImport::get_instance()->get_current_importer_data();

		$customizer_file = array_get_value( $importer_data, 'selected_import_files.customizer', false );
		$data            = null;
		if ( $customizer_file && file_exists( $customizer_file ) ) {
			$raw = Helpers::data_from_file( $customizer_file );
			if ( is_wp_error( $raw ) ) {
				return;
			}

			$data = unserialize( $raw );
		}

		if ( is_array( $data ) ) {
			$active_plugins = array_get_value( $data, 'options.active_plugins', array() );
			$installed      = false;
			foreach ( $active_plugins as $active_plugin ) {

				if ( strpos( $active_plugin, 'colibri-page-builder' ) !== false ) {
					continue;
				}

				if ( $this->installPlugin( $active_plugin ) ) {
					DemoImport::log_info( 'Installed Plugin: ' . $active_plugin );
					OneClickDemoImport::get_instance()->importer;
					$installed = true;
					$this->loadPlugin( $active_plugin );
				} else {
					DemoImport::log_error( 'Failed to install:' . $active_plugin );
				}
			}
		}

		if ( $installed ) {
			DemoImport::log_info( 'Plugins instalantion requests new ajax call' );
			$this->requestAnotherJSCall( $pre_process_data );
		}

	}

	public function installPlugin( $plugin ) {

		$active_plugins = get_option( 'active_plugins' );

		if ( in_array( $plugin, $active_plugins ) ) {
			return true;
		}

		if ( file_exists( WP_PLUGIN_DIR . "/$plugin" ) ) {

			return true;
		}

		$path       = wp_normalize_path( $plugin );
		$path_parts = explode( "/", $path );
		$slug       = array_shift( $path_parts );


		if ( ! function_exists( 'plugins_api' ) ) {
			include_once( ABSPATH . 'wp-admin/includes/plugin-install.php' ); //for plugins_api..
		}

		if ( ! class_exists( 'Plugin_Upgrader' ) ) {
			/** Plugin_Upgrader class */
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}


		$api = plugins_api(
			'plugin_information',
			array(
				'slug'   => $slug,
				'fields' => array(
					'sections' => false,
				),
			)
		);

		if ( is_wp_error( $api ) ) {
			return false;
		}

		$upgrader = new Plugin_Upgrader( new \Automatic_Upgrader_Skin() );
		$result   = $upgrader->install( $api->download_link );

		return ( $result === true );
	}

	private function loadPlugin( $plugin ) {
		$active_plugins = get_option( 'active_plugins', array() );
		array_push( $active_plugins, $plugin );
		$active_plugins = array_unique( $active_plugins );

		update_option( 'active_plugins', $active_plugins );
	}

	private function requestAnotherJSCall( $pre_process_data ) {
        add_filter( 'extendthemes-ocdi/time_for_one_ajax_call', "__return_zero" );
		/** @var Importer $importer */
		$importer = OneClickDemoImport::get_instance()->importer;
		$importer->new_ajax_request_maybe( $pre_process_data );
	}

	public function clear() {
		global $wp_post_types;

		if ( ! isset( $wp_post_types[ custom_post_prefix() . 'sidebar' ] ) ) {
			register_custom_post_type( 'sidebar' );
		}

	}

	public function beforeImportContent( $to_import, $import, $selected_inddex ) {


		$data         = OneClickDemoImport::get_instance()->get_current_importer_data();
		$content_file = array_get_value( $data, 'selected_import_files.content', false );

        $importer_options = apply_filters( 'extendthemes-ocdi/importer_options', array(
			'fetch_attachments' => true,
		) );

		$importer = new WXRImporter( $importer_options );
		$info     = $importer->get_preliminary_information( $content_file );

		unset( $importer );

		$transient_data = array(
			'siteurl' => $info->siteurl,
			'home'    => $info->home,
		);

		$this->setGlobalTransient( 'source_data', $transient_data );
	}


}
