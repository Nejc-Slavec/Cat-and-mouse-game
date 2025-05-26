<?php

namespace ExtendBuilder;

use ColibriWP\PageBuilder\PageBuilder;

require_once __DIR__ . '/page-list.php';

add_filter('colibri_page_builder/customizer/preview_data', function ($value) {
    $current_page_title = get_the_title();
    $site_title = get_bloginfo('name');
    if (!$current_page_title) {
        $current_page_title = $site_title;
    }

    //temporary, some sites like 404, search don't show a good title, so for now we show the site title everywhere
    $current_page_title = $site_title;
    $value['currentPageTitle'] = $current_page_title;

    return $value;
});

add_filter( 'colibri_page_builder/customizer/preview_data', function ( $value ) {
    $data = array(
        'isFrontPage' => is_front_page(),
        'isPost' => is_single(),
        'isPage' => is_page(),
        'isArchive' => colibri_is_blog_archive_page(),
        'isSearch' => is_search(),
        'is404' => is_404()
    );

    $data = apply_filters('colibri_page_builder/customizer/preview_data/currentPageType', $data);
    $value['currentPageType'] = $data;
    return $value;
} );

add_filter('colibri_page_builder/customizer/preview_data', function ($value) {
    global $post;
    $current_page_id = -1;
    if ($post) {
        $current_page_id = $post->ID;
    }

    if (is_search() || is_404() || colibri_is_blog_archive_page()) {
        $current_page_id = -1;
    }

    $current_page_id = apply_filters('colibri_page_builder/customizer/preview_data/currentPageId', $current_page_id);

    $value['currentPageId'] = $current_page_id;
    return $value;
});


add_filter('colibri_page_builder/customizer/preview_data', function ($value) {
    $value['currentPageIsPost'] = is_single();
    return $value;
});

add_filter('extendbuilder_wp_data', function ($data) {
    $data['default_search_form'] = colibri_output_sidebar_search_form();

    return $data;
});

add_filter('extendbuilder_wp_data', function ($value) {
    if (!defined('EXTEND_BUILDER_DEBUG')) {
        $value["assets_url"] = assetsUrl() . "/";
    } else {
        $value["assets_url"] = devUrl("");
    }
    $value['ajax_url'] = admin_url('admin-ajax.php');
    $value["version"] = version();

    return $value;
});

add_filter('extendbuilder_wp_data', function ($value) {
    $value['colibri-show-tour'] = get_option("colibri-show-tour", false);
    return $value;
});

add_filter('extendbuilder_wp_data', function ($value) {
    if (\function_exists('\is_plugin_active')) {
        $value['mailchimp_is_active'] = \is_plugin_active('mailchimp-for-wp/mailchimp-for-wp.php');
    }

    return $value;
});
add_filter('extendbuilder_wp_data', function ($value) {

	$value['cp_shortcode_refresh_nonce'] = wp_create_nonce( 'cp_shortcode_refresh_nonce' );
	$value['extend_builder_api_nonce'] = wp_create_nonce( 'extend_builder_api_nonce' );
    $value['colibri_plugin_install_activate_nonce'] = wp_create_nonce( 'colibri_plugin_install_activate_nonce' );
	$value['colibri_page_builder_wpmu_nonce'] = wp_create_nonce( 'colibri_page_builder_wpmu_nonce' );

    $value['forminator_is_compatible'] = colibri_plugin_is_compatible('forminator');
//	$value['smush_is_compatible'] = colibri_plugin_is_compatible('wp-smushit');
//	$value['hummingbird_is_compatible'] = colibri_plugin_is_compatible('hummingbird-performance');
//	$value['defender_is_compatible'] = colibri_plugin_is_compatible('defender-security');
	$value['hustle_is_compatible'] = colibri_plugin_is_compatible('wordpress-popup');

	return $value;
});
add_filter('extendbuilder_wp_data', function ($value) {

    $value['page_list'] = PagesList::get_page_list();

    return $value;
});

add_filter('extendbuilder_wp_data', function ($value) {
    $posts = get_posts(array(
        'post_type' => 'preset',
        'posts_per_page' => -1,
        'post_status' => array(
            'publish',
            'pending',
            'draft',
            'auto-draft',
            'future',
            'private',
            'inherit',
            'trash'
        ),
    ));
    $presets = array();
    foreach ($posts as $post) {
        $preset = json_decode($post->post_content, true);
        if (!$preset) {
            continue;
        }
        $preset['id'] = $post->ID;
        $presets[] = $preset;
    }
    $value['blockCustomPresets'] = $presets;
    return $value;
});

add_filter('extendbuilder_wp_data', function ($value) {

    $value['current_user_id'] = get_current_user_id();

    return $value;
});
add_filter('extendbuilder_wp_data', function ($value) {
    $uploads = wp_upload_dir();
    $value['home_page_url'] = get_option('home');
    $value['uploads_url'] = $uploads['baseurl'];
    return $value;
});

add_filter('extendbuilder_wp_data', function ($value) {
    $value['front_page_design'] = get_option('colibriwp_predesign_front_page_meta', array());
    return $value;
});

add_filter('extendbuilder_wp_data', function ($value) {

    $value['attachment_sizes'] = apply_filters('image_size_names_choose', array(
        'thumbnail' => __('Thumbnail'),
        'medium' => __('Medium'),
        'large' => __('Large'),
        'full' => __('Full Size'),
        'post-thumbnail' => __('Post Thumbnail'),
        'medium_large' => __('Medium Large'),
    ));

    return $value;
});

add_action('plugins_loaded', function () {
    add_filter('extendbuilder_wp_data', function ($value) {

        $plugin_name = 'mailchimp-for-wp';
        $manager = PageBuilder::instance()->theme()->getPluginsManager();
        $value['newsletter_plugin_data'] = [
            "status" => $manager->getPluginState($plugin_name),
            "install_url" => $manager->getInstallLink($plugin_name),
            "activate_url" => $manager->getActivationLink($plugin_name)
        ];

        return $value;
    });
});

add_filter('extendbuilder_wp_data', function ($value) {

    $shortcode = "";

    if (class_exists('\WPCF7_ContactForm')) {
        $first_form = \WPCF7_ContactForm::find(array(
            'posts_per_page' => 1,
        ));

        if (count($first_form)) {
            /** @var WPCF7_ContactForm $first_form */
            $first_form = $first_form[0];
            $shortcode = $first_form->shortcode();

        }
    }

    $value['defaults']['contact-form-7'] = $shortcode;

    return $value;
});

add_filter('extendbuilder_wp_data', function ($value) {


    $value['defaults']['mailchimp-signup-form'] = get_mailchimp_form_shortcode();

    return $value;
});


add_action('customize_controls_print_scripts', function () {

    $debug = defined('COLIBRI_SCRIPT_DEBUG') && COLIBRI_SCRIPT_DEBUG;
    ?>
  <script>
    var _extendBuilderWPData = <?php echo json_encode((object)apply_filters('extendbuilder_wp_data',
        array(
            'debug' => $debug,
				'upgrade_url'			   => colibri_upgrade_url(),
                'try_url'                  => colibri_try_url(),
            'rest_url' => rest_url(),
            'plugin_url' => PageBuilder::instance()->rootURL(),
            'cp_shortcode_refresh_nonce' =>  wp_create_nonce('cp_shortcode_refresh_nonce'),
            'extend_builder_api_nonce' =>  wp_create_nonce('extend_builder_api_nonce'),
            "shapes_url" => get_template_directory_uri() . '/resources/images/header-shapes/',
                'defaults'                 => array(),
                'content'                  => array(
                    'url'              => apply_filters( 'colibri_page_builder/content_url', 'https://content.colibriwp.com/' ),
                    'theme_collection' => apply_filters( 'colibri_page_builder/theme_collection', array() )
                ),
                'colibri_managed_sections' => (object) apply_filters( 'colibri_page_builder/customizer/managed_sections', array() ),
        ))); ?>;
  </script>
    <?php
}, PHP_INT_MAX);
