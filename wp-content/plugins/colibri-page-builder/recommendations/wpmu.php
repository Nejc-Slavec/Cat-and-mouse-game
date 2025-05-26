<?php

namespace ExtendBuilder;


use ColibriWP\PageBuilder\PageBuilder;
use ColibriWP\PageBuilder\ThemeHooks;
use ColibriWP\Theme\PluginsManager;


function wpmu_get_classes() {

	$defender_hardener_namespace = "\\WP_Defender\\Module\\Hardener\\Component";
	$classes                     = [
		'file_editor'       => "{$defender_hardener_namespace}\\Disable_File_Editor_Service",
		'xml_rpc'           => "{$defender_hardener_namespace}\\Disable_Xml_Rpc_Service_Class",
		'trackback'         => "{$defender_hardener_namespace}\\Disable_Trackback_Service",
		'smush_settings'    => "\\Smush\\Core\\Settings",
		'hummingbird_utils' => "\\Hummingbird\\Core\\Utils"
	];


	$defender_security_tweaks_namespace = "\\WP_Defender\\Component\\Security_Tweaks";
	if ( class_exists( "{$defender_security_tweaks_namespace}\\Disable_File_Editor" ) ) {
		$classes['file_editor'] = "{$defender_security_tweaks_namespace}\\Disable_File_Editor";
	}

	if ( class_exists( "{$defender_security_tweaks_namespace}\\Disable_XML_RPC" ) ) {
		$classes['xml_rpc'] = "{$defender_security_tweaks_namespace}\\Disable_XML_RPC";
	}

	if ( class_exists( "{$defender_security_tweaks_namespace}\\Disable_Trackback" ) ) {
		$classes['trackback'] = "{$defender_security_tweaks_namespace}\\Disable_Trackback";
	}

	return $classes;
}

function colibri_wpmu_toggle_smush_lazy_load( $params ) {
	$classes              = wpmu_get_classes();
	$Smush_Settings_Class = $classes['smush_settings'];
	if ( ! class_exists( $Smush_Settings_Class ) ) {
		return false;
	}

	$smush_settings = $Smush_Settings_Class::get_instance();
	$activate       = is_true( array_get_value( $params, 'value', '0' ) );

	if ( $activate && defined( 'WP_SMUSH_PREFIX' ) ) {
		$smush_settings->init_lazy_load_defaults();
		$settings = $smush_settings->get_setting( WP_SMUSH_PREFIX . 'lazy_load' );
		array_set_value( $settings, 'format.iframe', false );
		$smush_settings->set_setting( WP_SMUSH_PREFIX . 'lazy_load', $settings );
	}

	$smush_settings->set( 'lazy_load', $activate );


	return true;

}


// count downloads by proxying request to colibriwp.com
add_filter( 'upgrader_package_options', function ( $options ) {

	if ( array_get_value( $options, 'hook_extra.action', false ) !== 'install' ) {
		return $options;
	}

	$package  = array_get_value( $options, 'package', '' );
	$basename = basename( $package );
	$name     = preg_replace( '#[0-9\.]+\.zip#', '', $basename );

	$wpmu_plugins = colibri_wpmu_get_recommended_plugins();

	foreach ( $wpmu_plugins as $wpmu_plugin ) {
		$free_slug = array_get_value( $wpmu_plugin, 'slug', '' );
		if ( $free_slug === $name ) {
			$options['package'] = 'http://colibriwp.com/wp-download/plugin/' . $basename;

			return $options;
		}
	}

	return $options;
} );

function wpmu_plugin_is_suppressed( $suppressor ) {

	if ( ! $suppressor ) {
		return false;
	}

	$plugins = get_option( 'active_plugins' );

	foreach ( $plugins as $plugin ) {
		$plugin = str_replace( "\\", "/", $plugin );
		if ( strpos( $plugin, "{$suppressor}/" ) === 0 ) {
			return true;
		}
	}

	return false;
}

function colibri_is_wpmu_plugin_active( $key ) {
	$wpmu_plugins = colibri_wpmu_get_recommended_plugins();
	$plugins      = get_option( 'active_plugins' );
	$plugin_path  = array_get_value( $wpmu_plugins, "{$key}.plugin_path", null );

	if ( $plugin_path ) {
		return in_array( $plugin_path, $plugins );
	}

	return false;

}

add_action( "wp_ajax_colibri_page_builder_wpmu_setting", function () {
	check_ajax_referer( 'colibri_page_builder_wpmu_nonce', 'nonce' );
	if ( !current_user_can( 'edit_theme_options' ) ) {
		wp_send_json_error();
	}
	$params  = $_REQUEST;
	$slug    = sanitize_text_field(array_get_value( $params, 'slug', '' ));
	$option  = sanitize_text_field(array_get_value( $params, 'option', '' ));
	$setting = "{$slug}_{$option}";

	$classes                           = wpmu_get_classes();
	$Disable_File_Editor_Service_Class = $classes['file_editor'];
	$Disable_Xml_Rpc_Service_Class     = $classes['xml_rpc'];
	$Disable_Trackback_Service_Class   = $classes['trackback'];
	$Smush_Settings_Class              = $classes['smush_settings'];
	$Hummingbird_Utils_Class           = $classes['hummingbird_utils'];

	$result = false;

	switch ( $setting ) {
		case  'smush_lazy_load':
			if ( colibri_is_wpmu_plugin_active( 'smush' ) ) {
				$result = colibri_wpmu_toggle_smush_lazy_load( $params );
			}
			break;

		case 'smush_auto':
		case  'smush_detection':
			if ( colibri_is_wpmu_plugin_active( 'smush' ) && class_exists( $Smush_Settings_Class ) ) {
				$smush_settings = $Smush_Settings_Class::get_instance();
				$smush_settings->set( $option, is_true( array_get_value( $params, 'value' ) ) );
				$result = true;
			}
			break;

		case  'hummingbird_page_cache':
		case  'hummingbird_minify':
			if ( colibri_is_wpmu_plugin_active( 'hummingbird' ) && class_exists( $Hummingbird_Utils_Class ) ) {
				$module = $Hummingbird_Utils_Class::get_module( $option );
				$module->toggle_service( is_true( array_get_value( $params, 'value' ) ) );

				$result = true;
			}
			break;


		case  'defender_file_editor':
		case  'defender_xml_rpc':
		case  'defender_trackback':
			if ( colibri_is_wpmu_plugin_active( 'defender' ) ) {
				$classes = [
					'file_editor' => class_exists( $Disable_File_Editor_Service_Class ) ? $Disable_File_Editor_Service_Class : false,
					'xml_rpc'     => class_exists( $Disable_Xml_Rpc_Service_Class ) ? $Disable_Xml_Rpc_Service_Class : false,
					'trackback'   => class_exists( $Disable_Trackback_Service_Class ) ? $Disable_Trackback_Service_Class : false,
				];

				if ( $classes[ $option ] ) {

					$service = new $classes[ $option ];

					if ( is_true( array_get_value( $params, 'value' ) ) ) {
						$result = $service->process();
					} else {
						$result = $service->revert();
					}
				}
			}
			break;
	}


	if ( $result ) {
		wp_send_json_success( $result );
	}

	wp_send_json_error();

} );

function colibri_wpmu_get_options( $key, $status ) {
	$options = [];

	$classes                           = wpmu_get_classes();
	$Disable_File_Editor_Service_Class = $classes['file_editor'];
	$Disable_Xml_Rpc_Service_Class     = $classes['xml_rpc'];
	$Disable_Trackback_Service_Class   = $classes['trackback'];
	$Smush_Settings_Class              = $classes['smush_settings'];
	$Hummingbird_Utils_Class           = $classes['hummingbird_utils'];

	switch ( $key ) {
		case 'smush':
			$options = [
				'lazy_load' => [
					'description' => 'Load page faster by delaying images loading until a visitor scrolls to them.',
					'value'       => false
				],
				'detection' => [
					'description' => 'Highlight images that are either too large or too small for their containers.',
					'value'       => false
				],
				'auto'      => [
					'description' => 'When images are upload they we will automatically be optimized and compressed for you.',
					'value'       => false
				]
			];

			if ( $status === PluginsManager::ACTIVE_PLUGIN && class_exists( $Smush_Settings_Class ) ) {
				$options['lazy_load']['value'] = ! ! $Smush_Settings_Class::get_instance()->get( 'lazy_load' );
				$options['detection']['value'] = ! ! $Smush_Settings_Class::get_instance()->get( 'detection' );
			}

			break;

		case 'hummingbird':
			$options = [
				'page_cache' => [
					'description' => 'Page caching stores static copies of your pages which are then served to visitors, reducing server load and speeding up the site.',
					'value'       => false
				],

				'minify' => [
					'description' => 'Compress and combine assets to improve your page load speed.',
					'value'       => false
				]
			];

			if ( $status === PluginsManager::ACTIVE_PLUGIN && class_exists( $Hummingbird_Utils_Class ) ) {
				$options['page_cache']['value'] = ! ! $Hummingbird_Utils_Class::get_module( 'page_cache' )->is_active();
				$options['minify']['value']     = ! ! $Hummingbird_Utils_Class::get_module( 'minify' )->is_active();
			}

			break;


		case 'defender':
			$options = [
				'file_editor' => [
					'description' => 'Disable WordPress builtin file editor to avoid editing your plugins and themes files and inject malicious code.',
					'value'       => false
				],

				'xml_rpc' => [
					'description' => 'If you are not using third party apps to post on your WordPress site, you can increase security by disabling XML-RPC feature',
					'value'       => false
				],


				'trackback' => [
					'description' => 'Disable trackbacks and pingbacks to avoid DDos attacks or spam comments in posts. ',
					'value'       => false
				]
			];

			if ( $status === PluginsManager::ACTIVE_PLUGIN ) {
				if ( class_exists( $Disable_File_Editor_Service_Class ) ) {
					$options['file_editor']['value'] = ( new $Disable_File_Editor_Service_Class )->check();

				}

				if ( class_exists( $Disable_Xml_Rpc_Service_Class ) ) {
					$options['xml_rpc']['value'] = ( new $Disable_Xml_Rpc_Service_Class )->check();
				}

				if ( class_exists( $Disable_Trackback_Service_Class ) ) {
					$options['trackback']['value'] = ( new $Disable_Trackback_Service_Class )->check();
				}
			}

			break;

	}

	return $options;
}

function colibri_wpmu_get_plugin_page( $key ) {
	$url = admin_url();

	switch ( $key ) {
		case 'defender':
			$url = add_query_arg( 'page', 'wp-defender', admin_url( '/admin.php' ) );
			break;
		case 'smush':
			$url = add_query_arg( 'page', 'smush', admin_url( '/admin.php' ) );
			break;

		case 'hummingbird':
			$url = add_query_arg( 'page', 'wphb', admin_url( '/admin.php' ) );
			break;

		case 'forminator':
			$url = add_query_arg( 'page', 'forminator', admin_url( '/admin.php' ) );
			break;

		case 'hustle':
			$url = add_query_arg( 'page', 'hustle', admin_url( '/admin.php' ) );
			break;

	}


	return $url;
}
global $colibri_recommended_plugins_data;
$colibri_recommended_plugins_data = [
	'smush'       => [
		'free'        => 'wp-smushit/wp-smush.php',
		'pro'         => 'wp-smush-pro/wp-smush.php',
		'name'        => 'Smush',
		'description' => 'Compress and optimize images with lazy load, WebP conversion, and resize detection to increase site performance.',
		'wp_org_slug'        => 'wp-smushit',
	],
	'hummingbird' => [
		'free'        => 'hummingbird-performance/wp-hummingbird.php',
		'pro'         => 'wp-hummingbird/wp-hummingbird.php',
		'name'        => 'Hummingbird',
		'description' => 'Make your site faster by adding cache, minify CSS and Javascript, defer critical .CSS and .JS, Smush lazy load integration and much more.',
		'wp_org_slug'        => 'hummingbird-performance',
	],
	'defender'    => [
		'free'        => 'defender-security/wp-defender.php',
		'pro'         => 'wp-defender/wp-defender.php',
		'name'        => 'Defender',
		'description' => 'WordPress security plugin with malware scans, IP blocking, audit logs, firewall, login security & more.',
		'wp_org_slug'        => 'defender-security',
	],

	'forminator' => [
		'free'          => 'forminator/forminator.php',
		'pro'           => 'forminator/forminator.php',
		'name'          => 'Forminator',
		'description'   => 'Forminator is an expandable form builder plugin for WordPress.',
		'suppressed_by' => 'contact-form-7',
		'wp_org_slug'          => 'forminator',
	],

	'hustle' => [
		'free'          => 'wordpress-popup/popover.php',
		'pro'           => 'hustle/opt-in.php',
		'name'          => 'Hustle',
		'description'   => 'Collect email addresses with pop-ups, slide-ins, widgets, or in post opt-in forms.',
		'suppressed_by' => 'mailchimp-for-wp',
		'wp_org_slug'          => 'wordpress-popup',
	],

];

function colibri_wpmu_get_recommended_plugins() {
    global $colibri_recommended_plugins_data;
	$plugins = $colibri_recommended_plugins_data;

	$result = [];

	foreach ( $plugins as $plugin => $plugin_data ) {
		$data = array(
			'name'          => $plugin_data['name'],
			'description'   => $plugin_data['description'],
			'plugin_path'   => $plugin_data['free'],
			'suppressed_by' => array_get_value( $plugin_data, 'suppressed_by', false ),
            'wp_org_slug'   => $plugin_data['wp_org_slug']
		);


		if ( file_exists( WP_PLUGIN_DIR . "/" . $plugin_data['pro'] ) ) {
			$data['plugin_path'] = $plugin_data['pro'];
		}

		$data['slug'] = dirname( $data['plugin_path'] );

		$result[ $plugin ] = $data;
	}

	return $result;
}


function colibri_plugin_is_compatible($plugin_slug) {
	//only do requests in admin pages. For other types of pages mark it as true. We only care for admin pages where you have buttons to install the
    //recommended plugins. This needs to be true for plugins inside the customizer
	if(!is_admin()) {
		return true;
	}
	$wp_org_data_transient_key = 'colibri_recommended_plugin_' . $plugin_slug . '_wp_org_data';
	// delete_transient($wp_org_data_transient_key);

	$wp_org_data = get_transient( $wp_org_data_transient_key );
	if ( ! $wp_org_data ) {


		// WordPress.org API URL to fetch plugin information
		$api_url = 'https://api.wordpress.org/plugins/info/1.0/' . $plugin_slug . '.json';

		$response = wp_remote_get( $api_url );
		$response_code = wp_remote_retrieve_response_code($response);
		if ( ! is_wp_error( $response ) && $response_code === 200 ) {
			$body          = wp_remote_retrieve_body( $response );
			$response_data = json_decode( $body, true );

			$wp_org_data = [];

			if ( $response_data && isset( $response_data['requires'] ) ) {


				$wp_org_data['required_php_version'] = $response_data['requires_php']; // Required PHP version
				$wp_org_data['required_wp_version']  = $response_data['requires']; // Required PHP version

				//save transient for one day
				set_transient( $wp_org_data_transient_key, $wp_org_data, DAY_IN_SECONDS );
			}
		} else {
            //also set transient on error to not spam the user with requests
			set_transient( $wp_org_data_transient_key, ['error' => true], DAY_IN_SECONDS );
        }

	}

	$required_php_version = isset( $wp_org_data['required_php_version'] ) ? $wp_org_data['required_php_version'] : false;
	$required_wp_version  = isset( $wp_org_data['required_wp_version'] ) ? $wp_org_data['required_wp_version'] : false;
	if ( $required_php_version && $required_wp_version ) {
		//if php or wp version is not compatible skip the plugin
		if ( version_compare( PHP_VERSION, $required_php_version ) < 0 ||
		     version_compare( get_bloginfo( 'version' ), $required_wp_version ) < 0
		) {
			return false;
		}
	} else {
        return false;
    }

    return true;
}
/**
 * @param $plugins
 * Filter out plugins that are not compatible with the current php and wp version to not crash
 * we only care for the get starter page;
 * @return array|mixed
 */

function colibri_filter_theme_plugins_that_are_not_compatible($plugins) {

	$final_plugins = [];

	global $colibri_recommended_plugins_data;

	//Remove plugins that are not compatible with the current php and wp version
	foreach ( $plugins as $plugin => $plugin_data ) {
		$plugin_slug = isset( $plugin_data['wp_org_slug'] ) ? $plugin_data['wp_org_slug'] : false;



		//if slug is no wp org slug is provided then we skip version check
		if ( ! $plugin_slug ) {
			$final_plugins[ $plugin ] = $plugin_data;
			continue;
		}
        $plugin_is_compatible = colibri_plugin_is_compatible($plugin_slug);
        if(!$plugin_is_compatible) {
            continue;
        }

		$final_plugins[ $plugin ] = $plugin_data;
	}

    return $final_plugins;
}
ThemeHooks::prefixed_add_filter( 'theme_plugins', function ( $plugins ) {
	$wpmu_plugins   = colibri_wpmu_get_recommended_plugins();
	$mapped_plugins = [];

	foreach ( $wpmu_plugins as $plugin ) {
		$mapped_plugins[ $plugin['slug'] ] = $plugin;
	}
	$plugins = array_merge( $plugins, $mapped_plugins );


    $final_plugins = colibri_filter_theme_plugins_that_are_not_compatible($plugins);

	return $final_plugins;
}, 50 );

function wpmu_recommendation_customize_register( $wp_customize ) {

    if(!colibri_plugin_is_compatible('defender-security') ||
       !colibri_plugin_is_compatible('hummingbird-performance') ||
       !colibri_plugin_is_compatible('wp-smushit') ||
       !colibri_plugin_is_compatible('wordpress-popup')
    ) {
        return;
    }

	/** @var \WP_Customize_Manager $wp_customize */
	$wp_customize->add_control( 'colibri_wpmu_recommended_security_container', array(
		'section'    => 'colibri_wpmu_recommended_security',
		'settings'   => array(),
		'type'       => 'hidden',
		'capability' => 'edit_theme_options',
	) );

	$wp_customize->add_control( 'colibri_wpmu_recommended_performance_container', array(
		'section'    => 'colibri_wpmu_recommended_performance',
		'settings'   => array(),
		'type'       => 'hidden',
		'capability' => 'edit_theme_options',
	) );

	$wp_customize->add_section( 'colibri_wpmu_recommended_security', array(
		'title'    => esc_html__( 'Security Settings', 'colibri-page-builder' ),
		'priority' => 300,
	) );

	$wp_customize->add_section( 'colibri_wpmu_recommended_performance', array(
		'title'    => esc_html__( 'Performance Settings', 'colibri-page-builder' ),
		'priority' => 300,
	) );


	add_action( 'colibri_page_builder/customizer/managed_sections', function ( $sections ) {

		$sections = array_merge( $sections, array(
			'colibri_wpmu_recommended_security'    => 'wpmu_security',
			'colibri_wpmu_recommended_performance' => 'wpmu_performance',
		) );

		return $sections;
	} );

	add_filter( 'extendbuilder_wp_data', function ( $data ) {
		$manager          = PageBuilder::instance()->theme()->getPluginsManager();
		$plugins          = colibri_wpmu_get_recommended_plugins();
		$wpmu_plugin_data = [];

		foreach ( $plugins as $key => $plugin ) {
			$status                   = $manager->getPluginState( $plugin['slug'] );
			$wpmu_plugin_data[ $key ] = [
				"status"        => $status,
				"install_url"   => $manager->getInstallLink( $plugin['slug'] ),
				"activate_url"  => $manager->getActivationLink( $plugin['slug'] ),
				"description"   => $plugin['description'],
				"slug"          => $plugin['slug'],
				"options"       => (object) colibri_wpmu_get_options( $key, $status ),
				"page"          => colibri_wpmu_get_plugin_page( $key ),
				'is_suppressed' => wpmu_plugin_is_suppressed( array_get_value( $plugin, 'suppressed_by', false ) )
			];
		}

		$data['wpmu_plugin_data'] = (object) $wpmu_plugin_data;

		return $data;
	} );
}

add_action( 'colibri_page_builder/ready', function () {

	add_action( 'customize_register', '\ExtendBuilder\wpmu_recommendation_customize_register' );

} );


add_action( 'admin_notices', function () {
	/** @var PluginsManager $manager */


	if ( get_transient( 'colibri_wpmu_forminator_hide_notice' ) || !colibri_plugin_is_compatible('forminator')) {
		return;
	}

	//don't display notice after import
	if ( Regenerate::getGeneratorCallback() === "site_imported_notice" ) {
		return;
	}


	// check for of7
	if ( ! function_exists( 'get_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$installed_plugins = get_plugins();
	$active_plugins    = get_option( 'active_plugins', array() );

	foreach ( array_keys( $installed_plugins ) as $plugin_path ) {
		if ( strpos( $plugin_path, 'contact-form-7' ) === 0 && in_array( $plugin_path, $active_plugins ) ) {
			return;
		}
	}


	$manager = PageBuilder::instance()->theme()->getPluginsManager();
	$status  = $manager->getPluginState( 'forminator' );

	$link = add_query_arg(
		array(
			'page'                => 'colibri-wp-page-info',
			'install_recommended' => 'forminator'
		),
		admin_url( 'themes.php' ) );

	$text = $status === 'not-installed' ? 'Install Forminator' : 'Activate Forminator';

	if ( $status !== PluginsManager::ACTIVE_PLUGIN ) {

		?>
        <div class="notice notice-warning is-dismissible wpmu-forminator-recommendation">
            <style>
                .colibri-wpmu-notice {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    padding: 4px 20px;
                }

                .colibri-wpmu-notice-col1 {
                    margin-right: 20px;
                }

                .colibri-wpmu-notice-col1 h3 {
                    font-size: 16px;
                    font-weight: normal;
                }

            </style>
            <div class="colibri-wpmu-notice">
                <div class="colibri-wpmu-notice-col1">
                    <h3>Colibri recommends <strong>Forminator</strong>, an expandable form builder plugin for WordPress.
                    </h3>
                </div>
                <div class="colibri-wpmu-notice-col2">
                    <a class="button button-primary" href="<?php echo esc_url( $link ); ?>"><?php echo esc_html($text); ?></a>
                </div>
            </div>
        </div>

		<?php

		add_action( 'admin_footer', function () {
			?>
            <script>
                jQuery(function ($) {
                    $(document).on('click', '.wpmu-forminator-recommendation .notice-dismiss', function () {
                        $.post("<?php echo admin_url( "/admin-ajax.php" ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>", {
                            action: 'colibri_wpmu_forminator_hide_notice',
                            _wpnonce: '<?php echo wp_create_nonce('colibri_wpmu_forminator_hide_notice_nonce');//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>'
                        })
                    });
                });
            </script>
			<?php
		} );
	}
} );


add_action( 'wp_ajax_colibri_wpmu_forminator_hide_notice', function () {
	check_ajax_referer('colibri_wpmu_forminator_hide_notice_nonce');
	set_transient( 'colibri_wpmu_forminator_hide_notice', true, WEEK_IN_SECONDS );
} );

function colibri_wpmu_activate_flag( $plugin_data, $functionName ) {
	$plugin_path = WP_PLUGIN_DIR . "/" . $plugin_data['plugin_path'];
	if ( file_exists( $plugin_path ) ) {
		require_once $plugin_path;
		if ( function_exists( $functionName ) ) {
			call_user_func( $functionName );
		}
	}
}

function colibri_activate_forminator() {
	if ( class_exists( '\Forminator' ) ) {
		\Forminator::activation_hook();
	}
}

add_filter( 'colibri_page_builder/plugin-activated', function ( $response, $slug, $plugin_data ) {

	$wpmu_plugins = [
		[
			'slug'               => [ 'defender-security', 'wp-defender' ],
			'activationFunction' => 'wp_defender_activate'
		],
		[
			'slug'               => [ 'hustle', 'wordpress-popup' ],
			'activationFunction' => 'hustle_activation'
		],
		[
			'slug'               => [ 'forminator' ],
			'activationFunction' => 'ExtendBuilder\colibri_activate_forminator'
		]
	];

	foreach ( $wpmu_plugins as $plugin ) {
		$activationFunction = $plugin['activationFunction'];
		if ( in_array( $slug, $plugin['slug'] ) ) {
			colibri_wpmu_activate_flag( $plugin_data, $activationFunction );
		}
	}


	return $response;
}, 10, 3 );
