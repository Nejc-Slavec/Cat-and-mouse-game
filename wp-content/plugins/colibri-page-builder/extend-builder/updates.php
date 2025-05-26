<?php

namespace ExtendBuilder;

function colibri_force_check_updates()
{
    colibri_force_check_plugins_update();
    colibri_force_check_themes_update();
}

function colibri_force_check_plugins_update()
{
    $transient = get_site_transient('update_plugins');
    if ($transient) {
        foreach ($transient->checked as $path => $version) {
            if (strpos($path, "colibri-page-builder-pro") !== false || strpos($path,
                    "colibri-page-builder") !== false) {
                if (isset($transient->no_update[$path])) {
                    unset($transient->no_update[$path]);
                    unset($transient->checked[$path]);
                    set_site_transient('update_plugins', $transient);
                }
                break;
            }
        }
    }
}

function colibri_force_check_themes_update()
{
    $transient = get_site_transient('update_themes');
    if ($transient) {
        if (!isset($transient->response['colibri-wp'])) {
            if (isset($transient->checked['colibri-wp'])) {
                unset($transient->checked['colibri-wp']);
                set_site_transient('update_themes', $transient);
            }
        }
    }
}


function colibri_get_available_updates()
{

    $needs_update = array();

    $themes = get_theme_updates();

    $current_theme = get_template();
    $our_theme = 'colibri-wp';
    if ($themes && isset($themes[$our_theme]) && $our_theme === $current_theme) {
        $theme = $themes[$our_theme];

        $current_version = $theme->get('Version');
        if (version_compare($current_version, $theme->update['new_version'], "<")) {
            if (strtolower($current_version) !== "@@buildnumber@@") {
                $needs_update['themes'] = array(
                    array(
                        "version" => $theme->update['new_version'],
                        "name" => $theme->get("Name"),
                    ),
                );
            }
        }
    }

    $our_plugins = ['colibri-page-builder-pro', 'colibri-page-builder'];
    $plugins = get_plugin_updates();
    if ($plugins) {
        foreach ($plugins as $file => $plugin) {
            $current_version = $plugin->Version;
            if (version_compare($current_version, $plugin->update->new_version, "<")) {
                if (strtolower($current_version) !== "@@buildnumber@@") {
                    if (in_array($plugin->TextDomain, $our_plugins)) {
                        $needs_update['plugins'] = array(
                            array(
                                "version" => $plugin->update->new_version,
                                "name" => $plugin->Name,
                            ),
                        );
                    }
                }
            }
        }
    }


    return $needs_update;
}

function colibri_get_updates_msg()
{
    $updates = colibri_get_available_updates();

    $msg = "";

    if (isset($updates['themes'])) {
        for ($i = 0; $i < count($updates['themes']); $i++) {
            $update = $updates['themes'][$i];
            $msg .= "<h1>New version (" . $update['version'] . ") available for " . $update['name'] . "</h1>";
        }
    }

    if (isset($updates['plugins'])) {
        for ($i = 0; $i < count($updates['plugins']); $i++) {
            $update = $updates['plugins'][$i];
            $msg .= "<h1>New version (" . $update['version'] . ") available for " . $update['name'] . "</h1>";
        }
    }

    if ($msg) {
        $msg .= '<h2>Please update to the latest versions before editing in Customizer.</h2>';
        $msg .= '<br/>';
        $msg .= '<a href="' . get_admin_url(null,
                "update-core.php") . '" class="button button-orange">Go to updates</a> ';
    }

    return $msg;
}

function colibri_get_dashboard_updates_msg()
{
    $updates = colibri_get_available_updates();

    $msg = "";

    if (isset($updates['themes'])) {
        for ($i = 0; $i < count($updates['themes']); $i++) {
            $update = $updates['themes'][$i];
            $msg .= "<p>New version (" . $update['version'] . ") available for " . $update['name'] . "</p>";
        }
    }

    if (isset($updates['plugins'])) {
        for ($i = 0; $i < count($updates['plugins']); $i++) {
            $update = $updates['plugins'][$i];
            $msg .= "<p>New version (" . $update['version'] . ") available for " . $update['name'] . "</p>";
        }
    }

    if ($msg) {
        $msg .= '<p>Please update to the latest versions before editing in Customizer.</p>';
        $msg .= '<br/>';
        $msg .= '<a href="' . get_admin_url(null,
                "update-core.php") . '" class="button button-primary">Go to updates</a> ';
    }

    return $msg;
}

add_action("admin_init", function () {
    global $pagenow;

    try {
        if ('customize.php' === $pagenow) {
            $theme = wp_get_theme();

            if ($theme->template == "colibri-wp") {
                colibri_force_check_themes_update();

//                if (function_exists("mesmerize_pro_require") && !class_exists("Wp_License_Manager_Client")) {
//                    mesmerize_pro_require('/inc/class-wp-license-manager-client.php');
//                }
//
//                if (class_exists("Wp_License_Manager_Client")) {
//                    $licence_manager = new Wp_License_Manager_Client(
//                        'mesmerize-pro',
//                        'Mesmerize PRO',
//                        'mesmerize-pro',
//                        'http://onepageexpress.com/api/license-manager/v1/',
//                        'theme'
//                    );
//                }

                wp_update_themes();
            }

        }
    } catch (Exception $e) {
    }
});

$theme = wp_get_theme();
$__is_colibri_theme = ($theme->template == "colibri-wp");

if ($theme && $__is_colibri_theme) {
    add_action('customize_controls_print_footer_scripts', function () {
        ?>
        <script type="text/javascript">
            CP_Customizer.addModule(function () {
                CP_Customizer.bind(CP_Customizer.events.PREVIEW_LOADED, function () {
                    var updates_msg = <?php echo json_encode(colibri_get_updates_msg()); ?>;
                    if (updates_msg) {
                        CP_Customizer.popUpInfo('Updates available',
                            '<div class="pro-popup-preview-container">' +
                            updates_msg +
                            '</div>'
                        );
                    }
                    ;
                });
            });
        </script>
        <?php
    }, 11);
}

///*
//	enable theme updates, by sending the version parameter
//*/
//
//add_filter('http_request_args', function ($r, $url) {
//    if (strpos($url, "mesmerize-pro") !== false) {
//        $r['body'] = array("v" => "1.0");
//    }
//
//    return $r;
//}, PHP_INT_MAX, 2);

///*
//	fix updates apearring for pro child theme instead of pro theme
//*/
//
//add_filter('pre_set_site_transient_update_themes', function ($transient) {
//
//    if (property_exists($transient, 'response') && is_array($transient->response)) {
//        foreach ($transient->response as $slug => $value) {
//            if ($slug != "mesmerize-pro" && strpos($value["package"], "mesmerize-pro") !== false) {
//
//                $theme = wp_get_theme();
//                if ($theme->parent() && $theme->parent()->template == "mesmerize-pro") {
//                    // if different version, add as pro update//
//
//                    if ($theme->parent()->version != $value['new_version']) {
//                        $transient->response['mesmerize-pro'] = $value;
//                        $transient->checked['mesmerize-pro'] = $theme->parent()->version;
//                    }
//                }
//
//                unset($transient->response[$slug]);
//
//                if (isset($transient->checked[$slug])) {
//                    unset($transient->checked[$slug]);
//                }
//            }
//        }
//    }
//
//    return $transient;
//}, PHP_INT_MAX);

add_action('wp_dashboard_setup', 'ExtendBuilder\my_custom_dashboard_widgets');

function my_custom_dashboard_widgets()
{
    global $wp_meta_boxes;
    $dashboard_updates_msg = colibri_get_dashboard_updates_msg();
    if ($dashboard_updates_msg) {
        wp_add_dashboard_widget('colibri_updates_notice', 'Colibri Updates', 'ExtendBuilder\colibri_updates_notice');
    }
}


function colibri_updates_notice()
{
    echo colibri_get_dashboard_updates_msg();
}
