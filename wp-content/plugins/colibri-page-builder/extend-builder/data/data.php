<?php

namespace ExtendBuilder;

use ColibriWP\PageBuilder\Utils\Utils;

global $colibri_cached_posts_data;
$colibri_cached_posts_data = array();

function show_page_content()
{
    $value = is_front_page() || is_page();
    $colibri_show_page_content = apply_filters('colibri_show_page_content', $value);
    return $colibri_show_page_content;
}

function get_page_partials_default($post_id = -1)
{
    $partials = partials_types_list();
    $defaults = array();
    foreach ($partials as $partial) {
        if (post_supports_partial($post_id, $partial)) {
            $defaults[] = partial_template_default_structure($partial);
        }
    }
    return $defaults;
}

function get_default_data()
{
    return array(
        "options" => array(
            "theme" => array()
        ),
        "partials" => array(),
        "pages" => array(
          array(
              "ID" => -1,
              "partials" => array()
          )
        )
    );
}

function maybe_deduplicate_post($post_id) {
    $post_data = new PostData($post_id);
    $post_json_id = $post_data->get_meta_value("json", -1);
    if ($post_json_id !== -1) {
        $query = new \WP_Query(
            array(
                'post_type' => array('page'),
                'lang'           => '',
                'post_status' => 'any',
                'post__not_in' => array($post_id),
                'meta_query' => array(
                    array(
                        'key'     => 'extend_builder',
                        'value'   => "s:4:\"json\";i:$post_json_id;",
                        'compare' => 'LIKE',
                    ),
                )
            )
        );

        if ($query->post_count > 0) {
            $partial_json = $post_data->get_data('json');
            $json = json_decode( $partial_json, true );
            array_set_value($json, 'unlinkPage', $post_json_id);
            $post_data->set_data("json", json_encode($json), true);
        }
    }
}


function maybe_fix_partial_json( $json ) {

    if ( is_string( $json ) ) {
        if (strpos($json, "{\\") === 0) {
            $json = wp_unslash($json);
        }
        $last_closed_bracket_position = strrpos( $json, "}" );

        // remove extra strings after json
        $json = substr( $json, 0, $last_closed_bracket_position + 1 );

        // remove </p> at the beginning of a string
        $json = str_replace( '"</p>', '"', $json );
    }

    return $json;
}

function get_partial_data( $post_id, $type = "" ) {
    $lang = get_current_language();
    if ( $type === "content" ) {
        $post_data = new PostData( $post_id, $lang );
        $post_id   = $post_data->id_in_lang( $post_id );
    } else {
        $post_id_in_lang = get_post_in_language( $post_id, $lang );
        $post_data       = new PostData( $post_id_in_lang );
        $post_id         = $post_id_in_lang;
    }


    $json = $post_data->get_data( 'json', false, false );

    $data = array(
        'json'    => maybe_fix_partial_json( $json ),
        'meta'    => $post_data->get_meta_value( 'meta' ),
        'html'    => $post_data->get_post_content(),
        'id'      => $post_id,
        'lang'    => $lang,
        'dynamic' => false,
        'type'    => $type
    );


    $extra = partials_extra_data();
    if ( isset( $extra[ $type ] ) ) {
        $extra_props = $extra[ $type ];
        foreach ( $extra_props as $path => $value ) {
            array_set_value( $data, $path, $value );
        }
    }

    return $data;
}

function get_current_page_id()
{
    global $wp_query;
    return $wp_query->post ? $wp_query->post->ID : -1;
}

function partials_extra_data() {
    return array(
        'main' => array('dynamic' => false),
        'sidebar' => array('dynamic' => false)
    );
}

function get_saved_partials_for_post($post_id = -1)
{
    $partials = array();
    if ($post_id !== -1 && show_page_content()) {
        $partials[] = get_partial_data($post_id, "content");
    }

    $partials_types_list = partials_types_list();
    foreach ($partials_types_list as $type) {
        if (post_supports_partial($post_id, $type)) {
            $partial_post = get_current_partial_post($type, get_default_language());
            if ($partial_post) {
                $partials[] = get_partial_data($partial_post->ID, $type);
            }
        }
    }
    return $partials;
}


function colibri_data_json_uri()
{
    return 'base64colibri';
}

function json_inflate($string)
{
    return json_decode(Utils::inflate($string), true);
}

function json_archive($value)
{
    return colibri_data_json_uri() . Utils::archive(json_encode($value));
}

function maybe_inflate($value)
{
    if (is_string($value)) {
        $parts = explode(colibri_data_json_uri(), $value);
        if (count($parts) > 1) {
            return json_inflate($parts[1]);
        }
        return $value;
    }

    return $value;
}

function maybe_inflate_values(&$values, $max_level = 2, &$level = -1)
{
    $level++;
    if ($values && is_array($values)) {
        foreach ($values as $key => &$value) {
            if (is_string($value)) {
                $values[$key] = maybe_inflate($value);
            } else {
                if ($level < $max_level) {
                    maybe_inflate_values($value, $max_level, $level);
                }
            }
        }
    }
    return $values;
}

function get_current_data($post_id = -1, $theme_only = false)
{
    if ($post_id == -1) {
        $post_id = get_current_page_id();
    }

    $is_customizer = colibri_user_can_customize() && (is_customize_page() || is_customize_preview()) && $post_id != -1 && !$theme_only;
    if ($is_customizer) {
        ThemeDataAccess::maybeUpdateUrls();
        maybe_deduplicate_post($post_id);
    }

    $is_preview = \is_customize_preview();
    $data_key = compose_cache_key('cached_current_data', $post_id, $theme_only, $is_preview);

    if (colibri_cache_has($data_key)) {
        return colibri_cache_get($data_key);
    }

    $data = get_default_data($post_id);

    $theme_mods_data = get_theme_mods_data();

    if (!empty($theme_mods_data)) {
        $data = array_merge($data, $theme_mods_data);
        $options = get_key_value($data, 'options', array());
        foreach ($options as $option_name => $option_value) {
            if ($option_name != "theme") {
                $new_option_value = false;

                switch ($option_name) {
                    case ColibriOptionsIds::RULES:
                        $new_option_value = merge_values_by_id(get_plugin_option($option_name, "[]"), $option_value, false);
                        break;
                    case ColibriOptionsIds::CSS_BY_RULE_ID:
                    case ColibriOptionsIds::CSS_BY_PARTIAL_ID:
                        $new_option_value  = merge_values_by_id(get_plugin_option($option_name, []), $option_value, true);
                        break;
                }

                if ($new_option_value !== false) {
                    array_set_value($options, $option_name, $new_option_value);
                }
            }
        }
        array_set_value($data, 'options', $options);
    } else {
        $data['options'] = get_colibri_options();
    }

    if (!$theme_only) {
        $partials = get_saved_partials_for_post($post_id);
        $page_partials = array();
        // add partials that are not already present//
        foreach ($partials as $partial) {
            $partial_id = $partial['id'];
            $page_partials[$partial['type']] = $partial_id;
            if (!isset($data['partials'][$partial_id])) {
                array_set_value($data['partials'], $partial_id, $partial);
            }
        }

        $data['pages'] = array(
            array("id" => $post_id, "partials" => $page_partials)
        );
    }

    colibri_cache_set($data_key, $data);
    return $data;
}
