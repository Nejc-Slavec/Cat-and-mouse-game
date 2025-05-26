<?php

namespace ColibriWP\PageBuilder\Customizer;

use ColibriWP\PageBuilder\Customizer\Panels\ContentPanel;

class Customizer {
	public $cpData = array();

	/** @var \ColibriWP\PageBuilder\PageBuilder $_companion */
	private $_companion = null;

	private $registeredTypes = array(
		'panels'   => array(
			"ColibriWP\\PageBuilder\\Customizer\\BasePanel" => true,
		),
		'sections' => array(),
		'controls' => array(
			"ColibriWP\\PageBuilder\\Customizer\\BaseControl" => true,
		),
	);

	public function __construct( $companion ) {
		$this->_companion = $companion;


		if ( ! $this->customizerSupportsViewedTheme() ) {
			return;
		}

		do_action( 'colibri_page_builder/customizer/loaded' );


		$this->register( array( $this, '__registerComponents' ) );

		$this->registerScripts( array( $this, '__registerAssets' ), 20 );
		$this->previewInit( array( $this, '__registePreviewAssets' ) );

		$this->register( array( $this, '__addGlobalScript' ) );
		$this->previewInit( array( $this, '__previewScript' ) );
	}

	public function customizerSupportsViewedTheme() {

		$supported = $this->companion()->isCurrentThemeSupported();
		$supported = apply_filters( 'colibri_page_builder/customizer/supports', $supported );

		return $supported;

	}

	public function companion() {
		return $this->_companion;
	}


	public function __registerAssets( $wp_customize ) {
		$self = $this;
		wp_enqueue_style( 'thickbox' );
		wp_enqueue_script( 'thickbox' );

		$jsUrl  = $self->companion()->assetsRootURL() . "/js/customizer/";
		$cssUrl = $self->companion()->assetsRootURL() . "/css";

		$ver = $self->companion()->version;
		wp_enqueue_style( 'cp-customizer-base', $cssUrl . '/customizer.css', array(), $ver );
		wp_enqueue_script( 'cp-customizer-base', $jsUrl . "customizer-base.js", array(
			"customize-base",
		), $ver, true );

		wp_localize_script( 'cp-customizer-base', '__colibriBuilderCustomizerStrings', (array) Translations::getTranslations() );
		wp_register_script( 'customizer-base', null, array( 'cp-customizer-base' ), $ver, true );
		wp_enqueue_script( 'customizer-base' );


		do_action( 'colibri_page_builder/customizer/add_assets', $self, $jsUrl, $cssUrl );
	}

	public function __addGlobalScript( $wp_customize ) {
		$self = $this;


		add_action( 'customize_controls_print_scripts', function () {
			if ( isset( $_REQUEST['cp__changeset__preview'] ) ): ?>
                <style>
                    #customize-controls {
                        display: none !important;
                    }

                    div#customize-preview {
                        position: fixed;
                        top: 0px;
                        left: 0px;
                        height: 100%;
                        width: 100%;
                        z-index: 10000000;
                        display: block;
                    }

                    html, body {
                        width: 100%;
                        max-width: 100%;
                        overflow-x: hidden;
                    }
                </style>
                <script>
                    window.__isCPChangesetPreview = true;
                </script>
			<?php endif;
		} );

		add_action( 'customize_controls_print_footer_scripts', function () use ( $self ) {

			if ( defined( "CP__addGlobalScript" ) ) {
				return;
			}

			define( "CP__addGlobalScript", "1" );

			$isScriptDebugging           = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG );
			$isShowingNextFeaturesActive = ( defined( 'SHOW_NEXT_FEATURES' ) && SHOW_NEXT_FEATURES );

			$globalData = apply_filters( 'colibri_page_builder/customizer/global_data', array(
				"slugPrefix"             => $self->companion()->getThemeSlug( true ),
				"cssAllowedProperties"   => \ColibriWP\PageBuilder\Utils\Utils::getAllowCssProperties(),
				"stylesheetURL"          => get_stylesheet_directory_uri(),
				"includesURL"            => includes_url(),
				"themeURL"               => get_template_directory_uri(),
				"isMultipage"            => $self->companion()->isMultipage(),
				"restURL"                => get_rest_url(),
				"SCRIPT_DEBUG"           => $isScriptDebugging,
				"SHOW_NEXT_FEATURES"     => $isShowingNextFeaturesActive,
				"isWoocommerceInstalled" => class_exists( 'WooCommerce' ),
			) );

			?>

            <script type="text/javascript">
                (function () {
                    parent.cpCustomizerGlobal = window.cpCustomizerGlobal = {
                        pluginOptions:  <?php echo json_encode( $globalData ); ?>
                    };
                })();
            </script>

            <div id="cp-full-screen-loader" class="active">
                <div class="wrapper">
					<?php ob_start(); ?>
                    <style type="text/css">

                        body {
                            text-align: center;
                        }

                        span.message-area-text-holder {
                            display: inline-block;
                            padding: 8px 16px;
                            background: #32a8d9;
                            color: #ffffff;
                            border-radius: 8px;
                            margin-top: 16px;
                            font-size: 12px;
                            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                        }

                        .loader {
                            display: inline-block;
                            border: 4px solid #e6e6e6;
                            border-top: 4px solid #32a8d9;
                            border-radius: 50%;
                            width: 50px;
                            height: 50px;
                            animation: spin 2s linear infinite;
                        }

                        @keyframes spin {
                            0% {
                                transform: rotate(0deg);
                            }
                            100% {
                                transform: rotate(360deg);
                            }
                        }
                    </style>
                    <span class="loader"></span>
                    <p class="message-area">
                      <span class="message-area-text-holder"><?php _e( 'Loading, please wait...', 'colibri-page-builder' ) //phpcs:ignore WordPress.Security.EscapeOutput.UnsafePrintingFunction ?></span></p>

					<?php $iframe_content = ob_get_clean(); ?>
                    <iframe style="width: 100%;" id="colibri-preloader-browser" allowfullscreen allowtransparency=""
                            src="about:blank"></iframe>
                    <script>
                        (function () {
                            var data = <?php echo wp_json_encode( $iframe_content ); ?>;
                            var doc = document.querySelector("#colibri-preloader-browser").contentWindow.document;
                            doc.open();
                            var content = '{{html}}{{body}}@@data@@{{/body}}{{/html}}';
                            content = content.split("{{").join('<');
                            content = content.split("}}").join('>');
                            content = content.replace('@@data@@', data);
                            doc.write(content);
                            doc.close();
                        })();
                    </script>
                </div>
            </div>
			<?php do_action( "colibri_page_builder/customizer/global_scripts", $self ); ?>

			<?php

		} );
	}

	public function insideCustomizer() {
		return isset( $_GET['customize_messenger_channel'] );
	}

	public function __registePreviewAssets( $wp_customize ) {
		$jsUrl  = $this->_companion->assetsRootURL() . "/js/customizer";
		$cssUrl = $this->_companion->assetsRootURL() . "/css";

		if ( $this->insideCustomizer() ) {
			wp_enqueue_script( 'cp-customizer-preview', $jsUrl . "/preview.js", array(
				'jquery',
				'jquery-ui-sortable',
				'customize-preview'
			) );
		}

	}

	public function queryVarsCleaner( $input ) {
		foreach ( $input as $key => &$value ) {
			if ( is_array( $value ) ) {
				$value = $this->queryVarsCleaner( $value );
			} else {
				if ( strpos( $key, 'cache' ) !== false ) {
					unset( $input[ $key ] );
				}
			}
		}

		return array_filter( $input );
	}

	public function __previewScript( $wp_customize ) {
		if ( defined( "CP__previewScript" ) ) {
			return;
		}
		if ( ! $this->insideCustomizer() ) {
			return;
		}

		define( "CP__previewScript", "1" );

		$self = $this;

		add_action( 'wp_footer', function () use ( $self ) {
		     global  $wp, $wp_query, $post;
		      $mainQueryVars = $wp->query_vars;
		      foreach ($_GET as $name => $value) {
		        if (!isset($mainQueryVars[$name])) {
		            $mainQueryVars[$name] = $value;
		        }
		      }

			$vars              = $self->queryVarsCleaner( $wp_query->query_vars );
			if (get_post_type() !== false) {
				$vars['post_type'] = get_post_type();
			}

			$previewData = apply_filters( 'colibri_page_builder/customizer/preview_data', array(
				"slug"                   => $self->companion()->getThemeSlug(),
				"maintainable"           => $self->companion()->isMaintainable(),
				"isFrontPage"            => $self->companion()->isFrontPage(),
				"canEditInCustomizer"    => $self->companion()->canEditInCustomizer(),
				"pageID"                 => $self->companion()->getCurrentPageId(),
				"queryVars"              => $vars,
				"mainQueryVars"              => $mainQueryVars,
				"hasFrontPage"           => ( $self->companion()->getFrontPage() !== null ),
				"siteURL"                => get_home_url(),
				"pageURL"                => $post ? get_page_link() : null,
				"includesURL"            => includes_url(),
				"mod_defaults"           => apply_filters( 'colibri_page_builder/customizer/mod_defaults', array() ),
				"isWoocommerceInstalled" => class_exists( 'WooCommerce' ),
			) );
			?>
            <script type="text/javascript">
                (function () {
                    window.cpCustomizerPreview = <?php echo json_encode( $previewData ); ?>;
                    wp.customize.bind('preview-ready', function () { 
                        // if this is removed, the iframe returned by customizer is the old one, needs more investigation
                        jQuery(function () {
                            setTimeout(function () {
                                parent.postMessage('colibri_page_builder_update_customizer', "*");
                            }, 100);
                        });
                    });
                })();
            </script>

            <style>
                *[contenteditable="true"] {
                    user-select: auto !important;
                    -webkit-user-select: auto !important;
                    -moz-user-select: text !important;
                }
            </style>

			<?php do_action( "colibri_page_builder/customizer/preview_scripts", $self ); ?>
			<?php

		} );
	}

	public function removeNamespace( $name ) {
		$parts  = explode( "\\", $name );
		$result = array();

		foreach ( $parts as $part ) {
			$part = trim( $part );
			if ( ! empty( $part ) ) {
				$result[] = $part;
			}
		}

		$result = implode( "-", $result );

		return strtolower( $result );
	}

	public function __registerComponents( $wp_customize ) {
		$this->cpData = apply_filters( 'colibri_page_builder/customizer_data', array(), $this );

		/** @var \WP_Customize_Manager $wp_customize */
		$wp_customize->add_panel( new ContentPanel( $wp_customize, 'page_content_panel', array( 'wp_data' => array( 'priority' => 2 ) ) ) );
		$this->registerComponents( $wp_customize );

	}

	private function registerComponents( $wp_customize ) {
		/** @var \WP_Customize_Manager $wp_customize */
		$wp_customize->register_panel_type( "ColibriWP\\PageBuilder\\Customizer\\BasePanel" );
		$wp_customize->register_control_type( "ColibriWP\\PageBuilder\\Customizer\\BaseControl" );

		foreach ( $this->cpData as $category => $components ) {
			switch ( $category ) {
				case 'panels':
					$this->registerPanels( $wp_customize, $components );
					break;
				case 'sections':
					$components = $this->cpData['sections'];
					$this->registerSections( $wp_customize, $components );
					break;

				case 'controls':
					$components = $this->cpData['controls'];
					$this->registerControls( $wp_customize, $components );
					break;
				case 'settings':
					$components = $this->cpData['settings'];
					$this->registerSettings( $wp_customize, $components );
					break;
			}
		}
	}

	public function registerPanels( $wp_customize, $components ) {
		/** @var \WP_Customize_Manager $wp_customize */
		foreach ( $components as $id => $data ) {
			if ( $panel = $wp_customize->get_panel( $id ) ) {
				if ( isset( $data['wp_data'] ) ) {
					foreach ( $data['wp_data'] as $key => $value ) {
						$panel->$key = $value;
					}
				}
				continue;
			}

			$panelClass = "ColibriWP\\PageBuilder\\Customizer\\BasePanel";

			if ( isset( $data['class'] ) && $data['class'] ) {
				$panelClass = $data['class'];
			}

			if ( ! isset( $this->registeredTypes['panels'][ $panelClass ] ) ) {
				$this->registeredTypes['panels'][ $panelClass ] = true;
			}


			if ( strpos( $panelClass, "WP_Customize_" ) !== false ) {
				$data = isset( $data['wp_data'] ) ? $data['wp_data'] : array();
			}

			$wp_customize->add_panel( new $panelClass( $wp_customize, $id, $data ) );
		}
	}


	public function registerSections( $wp_customize, $components ) {
		foreach ( $components as $id => $data ) {
			if ( $section = $wp_customize->get_section( $id ) ) {
				if ( isset( $data['wp_data'] ) ) {
					foreach ( $data['wp_data'] as $key => $value ) {
						$section->$key = $value;
					}
				}
				continue;
			}

			$sectionClass = "ColibriWP\\PageBuilder\\Customizer\\BaseSection";

			if ( isset( $data['class'] ) && $data['class'] ) {
				$sectionClass = $data['class'];
			}

			if ( ! isset( $this->registeredTypes['sections'][ $sectionClass ] ) ) {
				$this->registeredTypes['sections'][ $sectionClass ] = true;
				$wp_customize->register_section_type( $sectionClass );
			}


			if ( strpos( $sectionClass, "WP_Customize_" ) !== false ) {
				$data = isset( $data['wp_data'] ) ? $data['wp_data'] : array();
			}

			$wp_customize->add_section( new $sectionClass( $wp_customize, $id, $data ) );
		}
	}

	public function registerControls( $wp_customize, $components ) {
		foreach ( $components as $id => $data ) {
			if ( $control = $wp_customize->get_control( $id ) ) {
				if ( isset( $data['wp_data'] ) ) {
					foreach ( $data['wp_data'] as $key => $value ) {
						$control->$key = $value;
					}
				}
				continue;
			}

			$controlClass = "ColibriWP\\PageBuilder\\Customizer\\BaseControl";
			if ( isset( $data['class'] ) && $data['class'] ) {
				$controlClass = $data['class'];
			}

			if ( ! isset( $this->registeredTypes['controls'][ $controlClass ] ) ) {
				$this->registeredTypes['controls'][ $controlClass ] = true;
				// $wp_customize->register_control_type($controlClass);
			}


			if ( strpos( $controlClass, "WP_Customize_" ) !== false ) {
				$data = isset( $data['wp_data'] ) ? $data['wp_data'] : array();
			}

			$wp_customize->add_control( new $controlClass( $wp_customize, $id, $data ) );

		}
	}

	public function registerSettings( $wp_customize, $components ) {
		foreach ( $components as $id => $data ) {
			if ( $setting = $wp_customize->get_setting( $id ) ) {
				if ( isset( $data['wp_data'] ) ) {
					foreach ( $data['wp_data'] as $key => $value ) {
						if ( $key === "default" ) {
							$value = BaseSetting::filterDefault( $value );
						}
						$setting->$key = $value;
					}
				}
				continue;
			}

			$settingClass = "ColibriWP\\PageBuilder\\Customizer\\BaseSetting";

			if ( isset( $data['class'] ) && $data['class'] ) {
				$settingClass = $data['class'];
			}

			if ( strpos( $settingClass, "WP_Customize_" ) !== false ) {
				$data = isset( $data['wp_data'] ) ? $data['wp_data'] : array();
			}

			$setting = new $settingClass( $wp_customize, $id, $data );

			$wp_customize->add_setting( $setting );
			if ( method_exists( $setting, 'setControl' ) ) {
				$setting->setControl();
			}
		}
	}

	public function register( $callback, $priority = 40 ) {
		add_action( 'customize_register', $callback, $priority );
	}

	public function registerScripts( $callback, $priority = 40 ) {
		add_action( 'customize_controls_enqueue_scripts', $callback, $priority );
	}

	public function previewInit( $callback, $priority = 40 ) {
		add_action( 'customize_preview_init', $callback, $priority );
	}
}
