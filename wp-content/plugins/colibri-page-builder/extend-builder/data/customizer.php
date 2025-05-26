<?php

namespace ExtendBuilder;

define('COLIBRI_THEME_MOD', 'page_content');


function is_customize_page() {
    return ( is_admin() && 'customize.php' == basename( $_SERVER['PHP_SELF'] ) );
}

function get_theme_mod_value($default = false)
{
    $mod_value = get_theme_mod(COLIBRI_THEME_MOD, $default);
    $normalized_value = maybe_normalize_old_format($mod_value);
    if ($normalized_value) {
        return $normalized_value;
    }
    return $mod_value;
}

function get_theme_mods_data()
{
    $is_preview = \is_customize_preview();
    if ($is_preview) {
        $instance_data = get_theme_mod_value(false);
        $use_theme_mod_version = $instance_data && !empty($instance_data);
        if ($use_theme_mod_version) {
            maybe_inflate_values($instance_data);
            return $instance_data;
        }
    }
    return null;
}
