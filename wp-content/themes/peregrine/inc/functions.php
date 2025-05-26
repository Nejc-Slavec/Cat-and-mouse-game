<?php

use ColibriWP\Theme\AssetsManager;
use ColibriWP\Theme\Core\Hooks;
use ColibriWP\Theme\Core\Utils;
use ColibriWP\Theme\Defaults;
use ColibriWP\Theme\Theme;

require_once get_template_directory() . "/inc/vendor/autoload.php";


function peregrine_page_builder_components($components)
{
    $namespace = "ColibriWP\\Theme\\BuilderComponents";

    $components = array_merge($components, array(

        'css' 					=> "{$namespace}\\CSSOutput",

        // header components
        'header' 				=> "{$namespace}\\Header",

        // footer components
        'footer' 				=> "{$namespace}\\Footer",

        // page content
        'main' 					=> "{$namespace}\\MainContent",
        'single' 				=> "{$namespace}\\SingleContent",
        'content'				=> "{$namespace}\\PageContent",
        'front-page-content'	=> "{$namespace}\\FrontPageContent",
        // sidebar
        'sidebar' 				=> "{$namespace}\\Sidebar",
        // 404
        'page-not-found' 		=> "{$namespace}\\PageNotFound",

        // woo
        'main-woo' 				=> "{$namespace}\\WooContent",
    ));

    return $components;
}

function peregrine_default_components($components)
{

    $namespace = "ColibriWP\\Theme\\Components";

    $components = array_merge($components, array(

        // header components
        'header' 				=> "{$namespace}\\Header",
        'logo' 					=> "{$namespace}\\Header\\Logo",
        'header-menu' 			=> "{$namespace}\\Header\\HeaderMenu",

        // inner page fragments
        'inner-nav-bar' 		=> "{$namespace}\\InnerHeader\\NavBar",
        'inner-top-bar' 		=> "{$namespace}\\InnerHeader\\TopBar",
        'inner-hero' 			=> "{$namespace}\\InnerHeader\\Hero",
        'inner-title' 			=> "{$namespace}\\InnerHeader\\Title",

        // front page fragments
        'front-hero' 			=> "{$namespace}\\FrontHeader\\Hero",
        'front-title' 			=> "{$namespace}\\FrontHeader\\Title",
        'front-subtitle' 		=> "{$namespace}\\FrontHeader\\Subtitle",
        'front-buttons' 		=> "{$namespace}\\FrontHeader\\ButtonsGroup",
        'top-bar-list-icons' 	=> "{$namespace}\\FrontHeader\\TopBarListIcons",
        'top-bar-social-icons' 	=> "{$namespace}\\FrontHeader\\TopBarSocialIcons",
        'front-nav-bar' 		=> "{$namespace}\\FrontHeader\\NavBar",
        'front-top-bar' 		=> "{$namespace}\\FrontHeader\\TopBar",
        'front-image' 			=> "{$namespace}\\FrontHeader\\Image",


        // footer components
        'footer' 				=> "{$namespace}\\Footer",
        'front-footer' 			=> "{$namespace}\\Footer\\FrontFooter",

        // general components
        'css' 					=> "{$namespace}\\CSSOutput",

        // page content
        'main' 					=> "{$namespace}\\MainContent",
        'single' 				=> "{$namespace}\\SingleContent",
        'content' 				=> "{$namespace}\\PageContent",
        'front-page-content' 	=> "{$namespace}\\FrontPageContent",
        'search' 				=> "{$namespace}\\PageSearch",
        'page-not-found' 		=> "{$namespace}\\PageNotFound",

        // inner content fragments

        //main content
        'main-loop' 			=> "{$namespace}\\MainContent\ArchiveLoop",
        'post-loop' 			=> "{$namespace}\\MainContent\PostLoop",
        'archive-loop' 			=> "{$namespace}\\MainContent\ArchiveLoop",
        'single-template' 		=> "{$namespace}\\MainContent\SingleItemTemplate",

        // sidebar
        'sidebar' 				=> "{$namespace}\\Sidebar",

        // woo
        'main-woo' 				=> "{$namespace}\\WooContent",
    ));

    return $components;
}

function peregrine_register_components($components = array())
{
    if (apply_filters('colibri_page_builder/installed', false)) {
        $components = peregrine_page_builder_components($components);
    } else {
        $components = peregrine_default_components($components);
    }

    return $components;
}

Hooks::prefixed_add_action('components', 'peregrine_register_components');
Theme::load(array(
    'themeRelativePath' 		=> '',
    'themeBaseRelativePath' 	=> 'inc/vendor/colibriwp/themebase/wp/'
));

/**
 * @return Theme
 */
function peregrine_theme()
{
    return Theme::getInstance();
}


/**
 * @return AssetsManager
 */
function peregrine_assets()
{
    return peregrine_theme()->getAssetsManager();
}


peregrine_theme()
    ->add_theme_support('automatic-feed-links')
    ->add_theme_support('title-tag')
    ->add_theme_support('post-thumbnails')
    ->add_theme_support('custom-logo', array(
        'flex-height' 	=> true,
        'flex-width' 	=> true,
        'width' 		=> 150,
        'height' 		=> 70,
    ));

add_action('after_setup_theme', function() {
    peregrine_theme()->register_menus(array(
        'header-menu' => esc_html__('Header Menu', 'peregrine'),
        'footer-menu' => esc_html__('Footer Menu', 'peregrine'),
    ));

}, 1);



add_action('widgets_init', 'peregrine_register_sidebars');
function peregrine_register_sidebars()
{
    register_sidebar(array(
        'name' 			=> esc_html__('Blog sidebar widget area', 'peregrine'),
        'id' 			=> 'colibri-sidebar-1',
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'before_title' 	=> '<h5 class="widgettitle">',
        'after_title' 	=> '</h5>',
        'after_widget' 	=> '</div>',
    ));

    if (function_exists('\is_shop')) {
        register_sidebar(array(
            'name' 			=> esc_html__('Woo Commerce left sidebar widget area', 'peregrine'),
            'id' 			=> 'peregrine-ecommerce-left',
            'before_widget' => '<div id="%1$s" class="widget %2$s">',
            'before_title' 	=> '<h5 class="widgettitle">',
            'after_title' 	=> '</h5>',
            'after_widget' 	=> '</div>',
        ));

    }
}

    if (!apply_filters('colibri_page_builder/installed', false)) {
        peregrine_assets()
            ->registerTemplateScript(
                "peregrine-theme",
                "/theme/theme.js",
                array('jquery', 'jquery-effects-slide', 'jquery-effects-core')
            )
            ->registerStylesheet("peregrine-theme", "/theme/theme.css")
            ->addGoogleFont("Open Sans", array("300", "400", "600", "700"))
            ->addGoogleFont(
                "Muli",
                array(
                    "300",
                    "300italic",
                    "400",
                    "400italic",
                    "600",
                    "600italic",
                    "700",
                    "700italic",
                    "900",
                    "900italic"
                )
            );
    }

    peregrine_assets()->registerTemplateStyle('peregrine-theme-extras', '/theme/extras.css', 'peregrine-theme');

    add_filter('colibri_page_builder/theme_supported', '__return_true');


//blog options

    function peregrine_show_post_meta_setting_filter($value)
    {

        $value = get_theme_mod('blog_post_meta_enabled', $value);

        return ($value == 1);
    }

    add_filter('colibri_show_post_meta', 'peregrine_show_post_meta_setting_filter');


    function peregrine_posts_per_row_setting_filter($value)
    {

        $value = get_theme_mod('blog_posts_per_row', $value);

        return $value;
    }

    add_filter('colibri_posts_per_row', 'peregrine_posts_per_row_setting_filter');

    function peregrine_archive_post_highlight_setting_filter($value)
    {

        $value = get_theme_mod('blog_post_highlight_enabled', $value);

        return $value;
    }

    add_filter('colibri_archive_post_highlight', 'peregrine_archive_post_highlight_setting_filter');


    function peregrine_blog_sidebar_enabled_setting_filter($value)
    {
        $default = Defaults::get('blog_sidebar_enabled', $value);
        $value = get_theme_mod('blog_sidebar_enabled', $default);

        return (intval($value) == 1);
    }

    Hooks::prefixed_add_filter('blog_sidebar_enabled', 'peregrine_blog_sidebar_enabled_setting_filter');
//add_filter( 'blog_sidebar_enabled', 'peregrine_blog_sidebar_enabled_setting_filter' );

    function peregrine_override_with_thumbnail_image($value)
    {
        global $post;

        if (isset($post) && $post->post_type === 'post') {
            $value = get_theme_mod('blog_show_post_featured_image',
                Defaults::get('blog_show_post_featured_image', false));
            $value = (intval($value) === 1);
        }

        return $value;
    }

    add_filter('colibri_override_with_thumbnail_image', 'peregrine_override_with_thumbnail_image');

    function peregrine_print_archive_entry_class($class = "")
    {

        $classes = array("post-list-item", "h-col-xs-12", "space-bottom");
        $classes = array_merge($classes, explode(" ", $class));
        $classes = get_post_class($classes);

        $default = get_theme_mod('blog_posts_per_row', Defaults::get('blog_posts_per_row'));
        $postsPerRow = max(1, apply_filters('peregrine_posts_per_row', $default));


        $classes[] = "h-col-sm-12 h-col-md-" . (12 / intval($postsPerRow));

        $classes = apply_filters('peregrine_archive_entry_class', $classes);

        $classesText = implode(" ", $classes);

        echo esc_attr($classesText);
    }

    function peregrine_print_masonry_col_class($echo = false)
    {

        global $wp_query;
        $index = $wp_query->current_post;
        $hasBigClass = (is_sticky() || ($index === 0 && apply_filters('peregrine_archive_post_highlight', false)));
        $showBigEntry = (is_archive() || is_home());

        $class = "";
        if ($showBigEntry && $hasBigClass) {
            $class = "col-md-12";
        } else {
            $default = get_theme_mod('blog_posts_per_row', Defaults::get('blog_posts_per_row'));
            $postsPerRow = max(1, apply_filters('peregrine_posts_per_row', $default));

            $class = "col-sm-12.col-md-" . (12 / intval($postsPerRow));
        }

        if ($echo) {
            echo esc_attr($class);
        } else {
            return esc_attr($class);
        }


    }


    Hooks::prefixed_add_filter('info_page_tabs', 'peregrine_get_started_info_page_tab');

    function peregrine_get_started_info_page_tab($tabs)
    {

        $tabs['get-started'] = array(
            'title' => \ColibriWP\Theme\Translations::translate('get_started'),
            'tab_partial' => "admin/get-started"
        );

        return $tabs;
    }


    function peregrine_theme_plugins($plugins)
    {
        $theme_plugins = array();

        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $installed_plugins = get_plugins();
        $is_cf_7_installed = false;

        foreach (array_keys($installed_plugins) as $plugin_path) {
            if (strpos($plugin_path, 'contact-form-7') === 0) {
                $is_cf_7_installed = true;
                break;
            }
        }

        if (!$is_cf_7_installed) {
            $theme_plugins = array_merge($theme_plugins, array(
                'forminator' => array(
                    'name' => 'Forminator',
                    'description' => \ColibriWP\Theme\Translations::translate('contact_form_plugin_description')
                )
            ));
        }

        $builder_plugin = 'colibri-page-builder';

        foreach ($installed_plugins as $key => $plugin_data) {
            if (strpos($key, 'colibri-page-builder-pro/') !== false) {
                $builder_plugin = 'colibri-page-builder-pro';

            }

            if (strpos($key, 'wpforms-') !== false) {
                unset($theme_plugins['contact-form-7']);
                $slug = Utils::arrayGetAt(explode("/", $key), 0);
                $theme_plugins[$slug] = array(
                    'name' => Utils::pathGet($plugin_data, 'Name', 'WP Forms'),
                    'description' => Utils::pathGet($plugin_data, 'Description'),
                );
            }
        }

        Hooks::prefixed_add_filter('plugin_slug', function ($slug) use ($builder_plugin) {
            return $builder_plugin;
        });

        $theme_plugins = array_merge(array(
            $builder_plugin => array(
                'name' => $builder_plugin === 'colibri-page-builder-pro' ? 'Colibri Page Builder PRO' : 'Colibri Page Builder',
                'description' => \ColibriWP\Theme\Translations::translate('page_builder_plugin_description'),
                'plugin_path' => "{$builder_plugin}/{$builder_plugin}.php"
            )
        ), $theme_plugins);

        return array_merge($plugins, $theme_plugins);
    }

    Hooks::prefixed_add_filter('theme_plugins', 'peregrine_theme_plugins');


    add_filter('http_request_host_is_external', 'peregrine_allow_internal_host', 10, 3);
    function peregrine_allow_internal_host($allow, $host, $url)
    {
        if ($host === 'extendstudio.net') {
            $allow = true;
        }

        return $allow;
    }

    add_action('wp_ajax_peregrine_front_set_predesign', function () {
        check_ajax_referer( 'peregrine_front_set_predesign_nonce', 'nonce' );
        $predesign_index = isset($_REQUEST['index']) ? $_REQUEST['index'] : 0;
        $predesign_index = intval($predesign_index);
        $meta = array();

        foreach (Defaults::get('front_page_designs', array()) as $predesign) {
            if (intval($predesign['index']) === $predesign_index) {
                $meta = Utils::pathGet($predesign, 'meta', array());
                break;
            }
        }

        update_option('colibriwp_predesign_front_page_index', $predesign_index);
        update_option('colibriwp_predesign_front_page_meta', $meta);
    });

    /* WooCommerce support for latest gallery */
    if (class_exists('WooCommerce')) {
        peregrine_theme()
            ->add_theme_support('woocommerce')
            ->add_theme_support('wc-product-gallery-zoom')
            ->add_theme_support('wc-product-gallery-lightbox')
            ->add_theme_support('wc-product-gallery-slider');
    }

    function peregrine_override_main_row_class($classes)
    {
        return Defaults::get('templates.blog.row.layout-classes', $classes);
    }

    Hooks::prefixed_add_filter('main_row_class', 'peregrine_override_main_row_class', 10, 1);
    require_once __DIR__ . "/integration/colibri-page-builder/colibri-page-builder-integration.php";

add_action('wp_footer', function() {
    if(!function_exists('\is_account_page') ||  apply_filters( 'colibri_page_builder/installed', false )) {
        return;
    }
    if ( !is_account_page()) {
        return;
    }
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Check if we are on the "My Account" page by looking for a unique element
            if (document.querySelector('.woocommerce-MyAccount-navigation')) {

                // Define SVGs for each menu item
                const svgDashboard = '<svg  aria-hidden="true" focusable="false" data-prefix="fas" data-icon="tachometer-alt" class="svg-inline--fa fa-tachometer-alt fa-w-18" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path  d="M288 32C128.94 32 0 160.94 0 320c0 52.8 14.25 102.26 39.06 144.8 5.61 9.62 16.3 15.2 27.44 15.2h443c11.14 0 21.83-5.58 27.44-15.2C561.75 422.26 576 372.8 576 320c0-159.06-128.94-288-288-288zm0 64c14.71 0 26.58 10.13 30.32 23.65-1.11 2.26-2.64 4.23-3.45 6.67l-9.22 27.67c-5.13 3.49-10.97 6.01-17.64 6.01-17.67 0-32-14.33-32-32S270.33 96 288 96zM96 384c-17.67 0-32-14.33-32-32s14.33-32 32-32 32 14.33 32 32-14.33 32-32 32zm48-160c-17.67 0-32-14.33-32-32s14.33-32 32-32 32 14.33 32 32-14.33 32-32 32zm246.77-72.41l-61.33 184C343.13 347.33 352 364.54 352 384c0 11.72-3.38 22.55-8.88 32H232.88c-5.5-9.45-8.88-20.28-8.88-32 0-33.94 26.5-61.43 59.9-63.59l61.34-184.01c4.17-12.56 17.73-19.45 30.36-15.17 12.57 4.19 19.35 17.79 15.17 30.36zm14.66 57.2l15.52-46.55c3.47-1.29 7.13-2.23 11.05-2.23 17.67 0 32 14.33 32 32s-14.33 32-32 32c-11.38-.01-20.89-6.28-26.57-15.22zM480 384c-17.67 0-32-14.33-32-32s14.33-32 32-32 32 14.33 32 32-14.33 32-32 32z"></path></svg>';
                const svgOrders = '<svg  aria-hidden="true" focusable="false" data-prefix="fas" data-icon="shopping-basket" class="svg-inline--fa fa-shopping-basket fa-w-18" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path d="M576 216v16c0 13.255-10.745 24-24 24h-8l-26.113 182.788C514.509 462.435 494.257 480 470.37 480H105.63c-23.887 0-44.139-17.565-47.518-41.212L32 256h-8c-13.255 0-24-10.745-24-24v-16c0-13.255 10.745-24 24-24h67.341l106.78-146.821c10.395-14.292 30.407-17.453 44.701-7.058 14.293 10.395 17.453 30.408 7.058 44.701L170.477 192h235.046L326.12 82.821c-10.395-14.292-7.234-34.306 7.059-44.701 14.291-10.395 34.306-7.235 44.701 7.058L484.659 192H552c13.255 0 24 10.745 24 24zM312 392V280c0-13.255-10.745-24-24-24s-24 10.745-24 24v112c0 13.255 10.745 24 24 24s24-10.745 24-24zm112 0V280c0-13.255-10.745-24-24-24s-24 10.745-24 24v112c0 13.255 10.745 24 24 24s24-10.745 24-24zm-224 0V280c0-13.255-10.745-24-24-24s-24 10.745-24 24v112c0 13.255 10.745 24 24 24s24-10.745 24-24z"></path></svg>';
                const svgDownloads = '<svg  aria-hidden="true" focusable="false" data-prefix="far" data-icon="file-archive" class="svg-inline--fa fa-file-archive fa-w-12" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path d="M128.3 160v32h32v-32zm64-96h-32v32h32zm-64 32v32h32V96zm64 32h-32v32h32zm177.6-30.1L286 14C277 5 264.8-.1 252.1-.1H48C21.5 0 0 21.5 0 48v416c0 26.5 21.5 48 48 48h288c26.5 0 48-21.5 48-48V131.9c0-12.7-5.1-25-14.1-34zM256 51.9l76.1 76.1H256zM336 464H48V48h79.7v16h32V48H208v104c0 13.3 10.7 24 24 24h104zM194.2 265.7c-1.1-5.6-6-9.7-11.8-9.7h-22.1v-32h-32v32l-19.7 97.1C102 385.6 126.8 416 160 416c33.1 0 57.9-30.2 51.5-62.6zm-33.9 124.4c-17.9 0-32.4-12.1-32.4-27s14.5-27 32.4-27 32.4 12.1 32.4 27-14.5 27-32.4 27zm32-198.1h-32v32h32z"></path></svg>';
                const svgAddresses = '<svg  aria-hidden="true" focusable="false" data-prefix="fas" data-icon="home" class="svg-inline--fa fa-home fa-w-18" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path d="M280.37 148.26L96 300.11V464a16 16 0 0 0 16 16l112.06-.29a16 16 0 0 0 15.92-16V368a16 16 0 0 1 16-16h64a16 16 0 0 1 16 16v95.64a16 16 0 0 0 16 16.05L464 480a16 16 0 0 0 16-16V300L295.67 148.26a12.19 12.19 0 0 0-15.3 0zM571.6 251.47L488 182.56V44.05a12 12 0 0 0-12-12h-56a12 12 0 0 0-12 12v72.61L318.47 43a48 48 0 0 0-61 0L4.34 251.47a12 12 0 0 0-1.6 16.9l25.5 31A12 12 0 0 0 45.15 301l235.22-193.74a12.19 12.19 0 0 1 15.3 0L530.9 301a12 12 0 0 0 16.9-1.6l25.5-31a12 12 0 0 0-1.7-16.93z"></path></svg>';
                const svgAccountDetails = '<svg  aria-hidden="true" focusable="false" data-prefix="fas" data-icon="user" class="svg-inline--fa fa-user fa-w-14" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M224 256c70.7 0 128-57.3 128-128S294.7 0 224 0 96 57.3 96 128s57.3 128 128 128zm89.6 32h-16.7c-22.2 10.2-46.9 16-72.9 16s-50.6-5.8-72.9-16h-16.7C60.2 288 0 348.2 0 422.4V464c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48v-41.6c0-74.2-60.2-134.4-134.4-134.4z"></path></svg>';
                const svgLogout = '<svg  aria-hidden="true" focusable="false" data-prefix="fas" data-icon="sign-out-alt" class="svg-inline--fa fa-sign-out-alt fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path  d="M497 273L329 441c-15 15-41 4.5-41-17v-96H152c-13.3 0-24-10.7-24-24v-96c0-13.3 10.7-24 24-24h136V88c0-21.4 25.9-32 41-17l168 168c9.3 9.4 9.3 24.6 0 34zM192 436v-40c0-6.6-5.4-12-12-12H96c-17.7 0-32-14.3-32-32V160c0-17.7 14.3-32 32-32h84c6.6 0 12-5.4 12-12V76c0-6.6-5.4-12-12-12H96c-53 0-96 43-96 96v192c0 53 43 96 96 96h84c6.6 0 12-5.4 12-12z"></path></svg>';

                // Define the menu item IDs
                const menuItems = {
                    'dashboard': svgDashboard,
                    'orders': svgOrders,
                    'downloads': svgDownloads,
                    'edit-address': svgAddresses,
                    'edit-account': svgAccountDetails,
                    'customer-logout': svgLogout
                };

                // Loop through the items and prepend the corresponding SVG to the link text
                for (const [key, svg] of Object.entries(menuItems)) {
                    const menuItem = document.querySelector('.woocommerce-MyAccount-navigation-link--' + key + ' a');
                    if (menuItem) {
                        menuItem.insertAdjacentHTML('afterbegin', svg + ' ');
                    }
                }
            }
        });
    </script>
    <?php
});

add_filter( 'get_the_excerpt', function ( $excerpt, $post ) {
	$length = Defaults::get( 'blog_post_excerpt_length', 13 );
	$words  = explode( ' ', $excerpt );

	return join( ' ', array_slice( $words, 0, $length ) ) . " [&hellip;]";
}, 10, 2 );

function peregrine_has_fresh_site() {
	$mods = get_theme_mods();

	unset( $mods['0'] );
	unset( $mods['nav_menu_locations'] );
	unset( $mods['custom_css_post_id'] );

	return ! count( $mods );
}

