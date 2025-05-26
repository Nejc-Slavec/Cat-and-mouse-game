<?php
namespace ExtendBuilder;

require_once __DIR__.'/editor-data/index.php';

function asyncScripts() {
    if ( isDev() ) {
        $icons_url = devUrl( "svg-icons.js", "static" );
        $fonts_url = devUrl( "web-fonts.js", "static" );
    } else {
        $icons_url = builderUrl( "svg-icons.js" );
        $fonts_url = builderUrl( "web-fonts.js" );
    }

    $async_load = "extendBuilderLoader('$icons_url', 'svgIcons');";
    $async_load .= "extendBuilderLoader('$fonts_url', 'webFonts');";

    return $async_load;
}

add_action( 'customize_controls_print_scripts', function () {
    ?>
  <script type="text/javascript">
    var extendBuilderResources = {
      _cbs: {},
      _r: {},

      set: function (path, value) {
        this._r[path] = value;
        this.call(path);
      },

      get: function (path, callback) {
        if (this._r.hasOwnProperty(path)) {
          callback(this._r[path]);
        } else {
          if (!this._cbs[path]) {
            this._cbs[path] = [];
          }
          this._cbs[path].push({ cb: callback });
          this.call(path);
        }
      },

      call: function (path) {
        var calls = this._cbs[path];
        if (calls) {
          for (var i = 0; i < calls.length; i++) {
            calls[i].cb(this._r[path]);
          }
        }
      }
    }

    function colibriLoadAsset(path, value, compressed = false) {
      extendBuilderResources.set(path, (value));
    }
    function extendBuilderLoader(url, path) {
      var s = document.createElement('script');
      s.type = "text/javascript";
      s.async = true;
      s.defer = true;
      s.src = url;
      var fs = document.getElementsByTagName('script')[0];
      fs.parentNode.insertBefore(s, fs);
    }
    <?php
    echo asyncScripts();
    ?>
  </script>
    <?php
} );


function registerCustomizerAssets() {
    registerBuilderAssets();

    $root = assetsUrl() . "/";
    $ver = version();

    if ( isDev() ) {
        wp_enqueue_script( 'h-index', devUrl( "index.js" ),
            array( 'cp-customizer-base' ), false, true );
        wp_enqueue_style( 'h-index', devUrl( "dist/static/css/index.css" ),
            array(), $ver );
    } else {
        wp_enqueue_script( 'h-index', builderUrl( "index.js", "js" ),
            array( 'cp-customizer-base' ), $ver, true );
        wp_enqueue_style( 'h-style-vendor', builderUrl( "vendor.css", "css" ),
            array(), $ver );
        wp_enqueue_style( 'h-style-index', builderUrl( "index.css", "css" ),
            array( 'h-style-vendor' ), $ver );
    }

    wp_enqueue_style( 'colibri-icons-style',
        $root . "static/colibri-icons-style.css", array(), true );
    wp_enqueue_script( 'extend-builder', $root . "index.js", array( 'h-index' ),
        $ver, true );
    wp_enqueue_style( 'h-fonts',
        "//fonts.googleapis.com/css?family=Material+Icons" );
}

add_action( 'customize_controls_enqueue_scripts',
    '\ExtendBuilder\registerCustomizerAssets' );


// colibri Advanced Editor ( SIMPLE UI CUSTOMIZER )
add_action( 'customize_controls_init', function () {
	$user  = wp_get_current_user();
	$roles = $user->roles;

	$is_super_admin = is_super_admin($user->ID);
	if ( in_array( "colibri_content_editor", $roles ) && !$is_super_admin) {
		add_action( 'customize_controls_enqueue_scripts', function () {
			wp_add_inline_script( 'jquery', \ExtendBuilder\colibri_get_colibri_content_editor_script(), 'before' );
		} );
	}
} );

function colibri_get_colibri_content_editor_script() {
	ob_start();
	?>
    <script type="text/javascript">
        window.COLIBRI_USE_SIMPLIFIED_UI = true;
    </script>
	<?php
	return strip_tags( ob_get_clean() );
}

add_action( 'customize_controls_print_footer_scripts', function() {
    ?>
    <script type="text/javascript">
      _wpCustomizeSettings.timeouts.keepAliveCheck = 60000;
    </script>
    <?php
} , 1001 );

add_action( 'customize_register', function ( $wp_customize ) {

    /** @var \WP_Customize_Manager $wp_customize */
    $wp_customize->add_section( 'general_site_typography', array(
        'priority' => 2,
        'title'    => __( 'Typography', 'colibri' ),
        'panel'    => 'general_settings',
    ) );

    $wp_customize->add_setting( 'dummy_control_typography', array(
        'default' => true,
    ) );

    $wp_customize->add_control( 'dummy_control_typography', array(
        'label'    => esc_html__( '', 'colibri' ),
        'section'  => 'general_site_typography',
        'priority' => 9,
        'type'     => 'checkbox',
    ) );

    $multilanguage_on = colibri_multilanguage_is_active();
    if ( $multilanguage_on ) {
        $wp_customize->add_section( 'general_site_multilanguage', array(
            'priority' => 2,
            'title'    => __( 'Multi Language', 'colibri' ),
            'panel'    => 'general_settings',
        ) );

        $wp_customize->add_setting( 'dummy_control_multilanguage', array(
            'default' => true,
        ) );

        $wp_customize->add_control( 'dummy_control_multilanguage', array(
            'label'    => esc_html__( '', 'colibri' ),
            'section'  => 'general_site_multilanguage',
            'priority' => 9,
            'type'     => 'checkbox',
        ) );
    }
    $wp_customize->add_section( 'templates', array(
        'priority' => 2,
        'title'    => __( 'Templates', 'colibri' ),
        'panel'    => 'general_settings',
    ) );

    $wp_customize->add_setting( 'dummy_control_templates', array(
        'default' => true,
    ) );

    $wp_customize->add_control( 'dummy_control_templates', array(
        'label'    => esc_html__( '', 'colibri' ),
        'section'  => 'templates',
        'priority' => 9,
        'type'     => 'checkbox',
    ) );

    $wp_customize->add_section( 'general_site_spacing', array(
        'priority' => 2,
        'title'    => __( 'Spacing', 'colibri' ),
        'panel'    => 'general_settings',
    ) );

    $wp_customize->add_setting( 'dummy_control_spacing', array(
        'default' => true,
    ) );

    $wp_customize->add_control( 'dummy_control_spacing', array(
        'label'    => esc_html__( '', 'colibri' ),
        'section'  => 'general_site_spacing',
        'priority' => 9,
        'type'     => 'checkbox',
    ) );

    $wp_customize->add_section( 'general_site_effects', array(
        'priority' => 2,
        'title'    => __( 'Effects', 'colibri' ),
        'panel'    => 'general_settings',
    ) );

    $wp_customize->add_setting( 'dummy_control_effects', array(
        'default' => true,
    ) );

    $wp_customize->add_control( 'dummy_control_effects', array(
        'label'    => esc_html__( '', 'colibri' ),
        'section'  => 'general_site_effects',
        'priority' => 9,
        'type'     => 'checkbox',
    ) );


	$wp_customize->add_section( 'general_site_colors', array(
		'priority' => 2,
		'title'    => __( 'Global Color Scheme', 'colibri' ),
		'panel'    => 'general_settings',
	) );

	$wp_customize->add_setting( 'dummy_control_colors', array(
		'default' => true,
	) );

	$wp_customize->add_control( 'dummy_control_colors', array(
		'label'    => esc_html__( '', 'colibri' ),
		'section'  => 'general_site_colors',
		'priority' => 9,
		'type'     => 'checkbox',
	) );


	$wp_customize->add_section( 'general_site_google_fonts', array(
		'priority' => 2,
		'title'    => __( 'Google Fonts Settings', 'colibri' ),
		'panel'    => 'general_settings',
	) );

	$wp_customize->add_setting( 'dummy_control_google_fonts', array(
		'default' => true,
	) );

	$wp_customize->add_control( 'dummy_control_google_fonts', array(
		'label'    => esc_html__( '', 'colibri' ),
		'section'  => 'general_site_google_fonts',
		'priority' => 9,
		'type'     => 'checkbox',
	) );


	$wp_customize->add_section( 'colibri_dummy_upgrade_to_pro', array(
        'priority' => 99999999999
    ) );

    $wp_customize->add_setting( 'colibri_upgrade_to_pro', array(
        'default' => true,
    ) );

    $wp_customize->add_control( 'colibri_dummy_upgrade_to_pro', array(
        'label'    => esc_html__( '', 'colibri' ),
        'section'  => 'colibri_upgrade_to_pro',
    ) );
} );

add_action( 'customize_register', function ( $wp_customize ) {
    $settings_with_post_message = array(
        'show_on_front',
        'page_on_front',
        'page_for_posts',
        'background_color',
    );

    foreach ( $settings_with_post_message as $setting_id ) {
        /** @var \WP_Customize_Manager $wp_customize */
        if ( $wp_customize->get_setting( $setting_id ) ) {
            $wp_customize->remove_setting($setting_id);
        }

        if (  $wp_customize->get_control( $setting_id ) ) {
            $wp_customize->remove_control($setting_id);
        }
    }
}, 100 );
