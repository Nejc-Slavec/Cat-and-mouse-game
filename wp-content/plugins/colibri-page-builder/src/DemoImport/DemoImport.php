<?php


namespace ColibriWP\PageBuilder\DemoImport;

use ColibriWP\PageBuilder\DemoImport\Hooks\ImportContentHook;
use ColibriWP\PageBuilder\DemoImport\Hooks\ImportCustomizerHook;
use ColibriWP\PageBuilder\DemoImport\Hooks\PreparationHook;
use ColibriWP\PageBuilder\DemoImport\Views\PageView;
use ColibriWP\PageBuilder\OCDI\Helpers;
use ColibriWP\PageBuilder\OCDI\OneClickDemoImport;
use ColibriWP\PageBuilder\PageBuilder;
use function ExtendBuilder\export_colibri_data;

class DemoImport {

    private static $instance;

    private $data_api = false;

    public function __construct() {
        PreparationHook::init();
        ImportContentHook::init();
        ImportCustomizerHook::init();

        $this->data_api = new DataApi();

        $this->addAdminFilters();


        new PageView( $this );

    }

    public function addAdminFilters() {
        add_filter( 'extendthemes-ocdi/plugin_page_setup', '__return_false' );
        add_filter( 'extendthemes-ocdi/disable_pt_branding', '__return_true' );

        add_filter( 'extendthemes-ocdi/upload_file_path', function ( $path ) {
            $template = get_template();

            $newPath = "{$path}/{$template}-ocdi/";

            $newPath = wp_normalize_path( $newPath );
            $newPath = trailingslashit( $newPath );

            if ( ! is_dir( $newPath ) ) {
                if ( ! wp_mkdir_p( $newPath ) ) {
                    $newPath = $path;
                }
            }

            return $newPath;
        } );


        add_action( 'wp_ajax_get_after_import_builder_data', function () {
	        check_ajax_referer('extendthemes-ocdi-ajax-verification');
            $debug  = defined( 'COLIBRI_SCRIPT_DEBUG' ) && COLIBRI_SCRIPT_DEBUG;
            $result = array(
                'data'    => array(
                    '_extendBuilderWPData'      => (object) apply_filters( 'extendbuilder_wp_data',
                        array(
                            'defaults' => array(
                                'debug'       => $debug,
                                'upgrade_url' => colibri_upgrade_url(),
                                'try_url'     => colibri_try_url(),
                                'rest_url'    => rest_url(),
                                'plugin_url'  => PageBuilder::instance()->rootURL(),
                                "shapes_url"  => get_template_directory_uri() . '/resources/images/header-shapes/',
                                'defaults'    => array()
                            )
                        ) ),
                    '_colibriAllPartialsExport' => export_colibri_data( array( "exclude_generated" => true ), true )
                ),
                'success' => true
            );

            wp_send_json( $result );
        } );
    }

    public static function load() {
        static::$instance = new static();
    }

    public static function log_info( $message = '', $context = array() ) {
        static::log( 'info', $message, $context );
    }

    public static function log( $level = 'info', $message = '', $context = array() ) {
        $data = OneClickDemoImport::get_instance()->get_current_importer_data();

        $log_file = $data['log_file_path'];

        Helpers::append_to_file(
            sprintf( "[%s] - %s", strtoupper( $level ), $message ),
            $log_file,
            " Colibri Importer "
        );


    }

    public static function log_debug( $message = '', $context = array() ) {
        static::log( 'debug', $message, $context );
    }

    public static function log_error( $message = '', $context = array() ) {
        static::log( 'error', $message, $context );
    }

    public static function log_notice( $message = '', $context = array() ) {
        static::log( 'notice', $message, $context );
    }

    public static function log_warning( $message = '', $context = array() ) {
        static::log( 'warning', $message, $context );
    }

    public function getImporterFiles() {
        return $this->data_api->registerImportFiles();
    }


}
