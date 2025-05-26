<?php


namespace ColibriWP\PageBuilder\DemoImport\Views;


use ColibriWP\PageBuilder\DemoImport\DemoImport;
use ColibriWP\PageBuilder\LoadingScreen;
use ColibriWP\PageBuilder\PageBuilder;
use ColibriWP\PageBuilder\ThemeHooks;
use function ExtendBuilder\builderUrl;
use function ExtendBuilder\devUrl;
use function ExtendBuilder\isDev;
use function ExtendBuilder\registerBuilderAssets;

class PageView {

	/** @var DemoImport $demo_importer */
	private $demo_importer = false;

	public function __construct( $demo_importer ) {

			      $this->demo_importer = $demo_importer;

			      ThemeHooks::prefixed_add_filter( 'info_page_data_tab_demo-import', array( $this, 'tabData' ) );
			      ThemeHooks::prefixed_add_action( 'before_info_page_tab_demo-import', array( $this, 'beforeTab' ) );
			      ThemeHooks::prefixed_add_filter( 'info_page_tabs', function ( $tabs ) {

			$partial = PageBuilder::instance()->rootPath(). "/demo-importer/tab_partial.php";

			$tabs['demo-import'] = array(
				'title'       => "Demo Sites",
				'tab_partial' => $partial
			);

			return $tabs;
		} );

	}

	public function tabData() {
		return [
			'is_ocdi_installed' => $this->isImporterInstalled(),
			'demos'             => $this->demo_importer->getImporterFiles()
		];
	}

	public function isImporterInstalled() {
        return true;
//        return class_exists( "OCDI\OneClickDemoImport" );
	}

	public function beforeTab() {
	    LoadingScreen::echoScreen();
		wp_enqueue_script( 'colibri-demo-imports',
			PageBuilder::instance()->assetsRootURL() . "/js/demo-import.js",
			array( 'jquery' ),
			PageBuilder::instance()->getVersion(),
			true
		);

        ?>
        <link rel="stylesheet"
              href="<?php echo esc_attr( PageBuilder::instance()->assetsRootURL() . "/css/demo-import.css?ver=" . PageBuilder::instance()->getVersion() ) ?>"/>
        <?php

		$ver = PageBuilder::instance()->getVersion();

		if ( $this->isImporterInstalled() ) {
			wp_enqueue_script( 'wp-util' );
			wp_enqueue_style( 'thickbox' );
			wp_enqueue_script( 'thickbox' );
			wp_enqueue_script( 'jquery-ui-dialog' );
			wp_enqueue_style( 'wp-jquery-ui-dialog' );
            wp_enqueue_script( 'extendthemes-ocdi-main-js', PageBuilder::instance()->assetsRootURL() . '/ocdi/main.js', array( 'jquery', 'jquery-ui-dialog' ),
                COLIBRI_PAGE_BUILDER_VERSION );


			if ( ! isDev() ) {
				registerBuilderAssets();
				wp_enqueue_style( 'colibri-regenerate-theme', builderUrl( "renderer.css", "css" ), array(), $ver );
                wp_enqueue_script( 'colibri-regenerate-theme', builderUrl( "renderer.js", "js" ), array(
                    'h-vendor',
                    'shortcode'
                ),
					$ver, true );
			} else {
				wp_enqueue_script( 'colibri-regenerate-theme', devUrl( "renderer.js" ), array('shortcode') );
			}

            wp_localize_script( 'extendthemes-ocdi-main-js', 'extendthemes_ocdi',
				array(
					'ajax_url'         => admin_url( 'admin-ajax.php' ),
                    'ajax_nonce'       => wp_create_nonce( 'extendthemes-ocdi-ajax-verification' ),
					'import_files'     => $this->demo_importer->getImporterFiles(),
                    'wp_customize_on'  => apply_filters( 'extendthemes-ocdi/enable_wp_customize_save_hooks', false ),
					'import_popup'     => false,
					'theme_screenshot' => wp_get_theme()->get_screenshot(),
					'texts'            => array(
						'missing_preview_image' => esc_html__( 'No preview image defined for this import.',
							'colibri-page-builder' ),
						'dialog_title'          => esc_html__( 'Are you sure?', 'colibri-page-builder' ),
						'dialog_no'             => esc_html__( 'Cancel', 'colibri-page-builder' ),
						'dialog_yes'            => esc_html__( 'Yes, import!', 'colibri-page-builder' ),
						'selected_import_title' => "",
						'not-installed'         => esc_html__( 'Not installed', 'colibri-page-builder' ),
						'installed'             => esc_html__( 'Installed', 'colibri-page-builder' ),
						'active'                => esc_html__( 'Active', 'colibri-page-builder' ),
						'installing_plugins'    => esc_html__( 'Installing Plugins', 'colibri-page-builder' ),
						'installing'            => esc_html__( 'Installing', 'colibri-page-builder' ),
						'activating'            => esc_html__( 'Activating', 'colibri-page-builder' ),
						'importing_title'       => esc_html__( 'Importing the following demo site',
							'colibri-page-builder' ),

					),
					'plugin_state'     => intval( PageBuilder::instance()->isPRO() ),
                    'dialog_options'   => apply_filters( 'extendthemes-ocdi/confirmation_dialog_options', array() ),

				)
			);
		} else {
			wp_enqueue_script( 'updates' );
			?>
            <script>
                window.ocdi_needs_instalation = true;
                window.ocdi_current_state = "<?php echo PageBuilder::instance()->theme()->getPluginsManager()->getPluginState( 'one-click-demo-import' ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>";
            </script>
			<?php
		}

	}

}
