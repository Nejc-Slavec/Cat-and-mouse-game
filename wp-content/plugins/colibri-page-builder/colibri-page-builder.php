<?php
/* 
 *	Plugin Name: Colibri Page Builder 
 *  Author: ExtendThemes
 *  Description: Colibri Page Builder adds drag and drop page builder functionality to the ColibriWP theme.
 *
 * License: GPLv3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.en.html
 * Version: 1.0.332
 * Text Domain: colibri-page-builder
 */


$colibri_page_builder_supported_themes = array('colibri-wp', 'colibri', 'teluro', 'one-page-express', 'brite', 'althea-wp', 'hugo-wp', 'hillstar', "linnet", "peregrine", 'digitala', "skylink", "silverstorm", "synclet", "ecliptica");

if (!in_array(get_option('template'), $colibri_page_builder_supported_themes)) {
	require_once 'utils/survey.php';
	require_once 'recommendations/colibri-wp.php';
	return;
} else {
	$is_customize_page = (is_admin() && 'customize.php' == basename($_SERVER['PHP_SELF']));
	$theme = get_template();
	if (isset($_GET['theme']) && $_GET['theme'] != get_stylesheet()) {
		$theme = sanitize_text_field($_GET['theme']);
	}

	//if is theme preview
	if ($is_customize_page && !in_array($theme, $colibri_page_builder_supported_themes)) {
		return;
	}
}

$current_file = basename(__FILE__);
//is free
$is_free = $current_file === 'colibri-page-builder.php';
if ($is_free) {
	$pro_builder_is_active = false;

    add_action('shutdown', function () {
        if (!is_admin() || get_option('colibri_page_builder_activation_time')) {
            return;
        }
        $time = get_option(\ColibriWP\PageBuilder\Notify\NotificationsManager::INITIALIZATION_NOTIFICATIONS_OPTION, time());
        update_option('colibri_page_builder_activation_time', $time);
    });

	$active_plugins = get_option('active_plugins');
	foreach ($active_plugins as $active_plugin) {
		if (strpos($active_plugin, 'colibri-page-builder-pro') !== false) {
			$pro_builder_is_active = true;
		}
	}
}

//checks on free if the pro plugin is active
if (class_exists('\ColibriWP\PageBuilder\PageBuilder') || $is_free && $pro_builder_is_active) {
	return;
}

// Make sure that the companion is not already active from another theme
if (!defined("COLIBRI_PAGE_BUILDER_AUTOLOAD")) {
	require_once __DIR__ . "/vendor/autoload.php";
	define("COLIBRI_PAGE_BUILDER_AUTOLOAD", true);
}

if (!defined("COLIBRI_PAGE_BUILDER_VERSION")) {
	define("COLIBRI_PAGE_BUILDER_VERSION", "1.0.332");
}

require_once 'support/wp-5.8.php';
\ColibriWP\PageBuilder\PageBuilder::load(__FILE__);
\ColibriWP\PageBuilder\GoogleFontsLocalLoader::registerFontResolver();
add_filter('colibri_page_builder/installed', '__return_true');


require_once 'extend-builder/extend-builder.php';
require_once 'recommendations/wpmu.php';

if (!function_exists('colibriwp_check_builder_activation_source')) {
    function colibriwp_check_builder_activation_source()
    {
        $theme = get_template();
        $option = "{$theme}_start-source";
        $start_source = get_option($option);

        if (!$start_source) {
            update_option($option, 'manual');
        }
    }

    add_action('colibri_page_builder/activated', 'colibriwp_check_builder_activation_source');
}
