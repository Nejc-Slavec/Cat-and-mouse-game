<?php


namespace ColibriWP\PageBuilder\DemoImport\Hooks;

use ExtendBuilder\ThemeDataAccess;
use function ExtendBuilder\set_theme_path;

class ImportCustomizerHook extends ImportHook {

	function transientKey() {
		return 'customizer';
	}

	public function run() {
		$self = $this;
		add_action( 'wp_ajax_extendthemes_ocdi_import_customizer_data', function () use ( $self ) {
			check_ajax_referer( 'extendthemes-ocdi-ajax-verification', 'security' );
			add_filter( 'pre_update_option_active_plugins', array( $self, 'installPlugins' ) );
		}, 0 );
	}

	public function afterImport( $data ) {
        ThemeDataAccess::getInstance()->moveFromOptionToFile();

       	$default_partials = \ExtendBuilder\get_theme_data( 'defaults.partials', false, array() );
		$colibri_posts_map    = $this->getGlobalTransient( 'colibri_posts_map', array() );

		foreach ( $default_partials as $area => $data ) {
			foreach ( $data as $partial => $id ) {
				if ( isset( $colibri_posts_map[ $id ] ) ) {
					$default_partials[ $area ][ $partial ] = $colibri_posts_map[ $id ];
				}
			}
		}
		set_theme_path( 'defaults.partials', $default_partials );
	}


	public function installPlugins( $plugins ) {

		$active_plugins = get_option( 'active_plugins', array() );

		foreach ( $plugins as $plugin ) {
			if ( ! in_array( $plugin, $active_plugins ) && file_exists( WP_PLUGIN_DIR . "/{$plugin}" ) ) {
				$active_plugins[] = $plugin;
			}
		}

		return array_unique( $active_plugins );

	}
}
