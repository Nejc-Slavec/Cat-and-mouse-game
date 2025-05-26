<?php

namespace ExtendBuilder;

add_filter('wp_revisions_to_keep', function ($revisions, $post) {
    $extend_builder_revisions = 20;
    $extend_builder_posts_types = array('extb_post_json', 'extb_post_main');

    /** @var \WP_Post $post */
    if (in_array($post->post_type, $extend_builder_posts_types)) {
        return $extend_builder_revisions;
    }

    $meta = get_post_meta($post->ID, 'extend_builder', true);

    if ($meta) {
        return $extend_builder_revisions;
    }


    return $revisions;
}, 20, 2);

add_action('_wp_put_post_revision', function ($revision_id) {

    global $extb_post_revisions;

    $extb_post_revisions = is_array($extb_post_revisions) ? $extb_post_revisions : array();
    $extb_handled_posts = array('page', 'post', 'product', 'extb_post_main');

    $revision = get_post($revision_id);
    $parent_post = get_post($revision->post_parent);

    if ($parent_post->post_type === "extb_post_json") {
        $extb_post_revisions[$parent_post->ID] = array(
            'post_id' => $parent_post->ID,
            'revision_id' => $revision_id,
            'post_type' => $parent_post->post_type,
            'json' => false
        );
    }

    if (in_array($parent_post->post_type, $extb_handled_posts)) {
        $meta = get_post_meta($parent_post->ID, 'extend_builder', true);
        $json = -1;

        if (is_array($meta) && isset($meta['json'])) {
            $json = $meta['json'];
        }

        $extb_post_revisions[$parent_post->ID] = array(
            'post_id' => $parent_post->ID,
            'revision_id' => $revision_id,
            'post_type' => $parent_post->post_type,
            'json' => $json
        );
    }

});

register_shutdown_function(function () {
    global $extb_post_revisions, $post;

    if (!$extb_post_revisions) {
        return;
    }
    if ($extb_post_revisions && colibri_user_can_customize()) {

        foreach ($extb_post_revisions as $post_id => $data) {
            if (isset($data['json']) && $data['json'] !== false) {
                $extb_post_json_id = $data['json'];
                $post_revision_id = $data['revision_id'];
                $extb_post_json_revision_id = array_get_value($extb_post_revisions, "{$extb_post_json_id}.revision_id", false);

                if ($extb_post_json_revision_id) {
                    continue;
                }

                if ($extb_post_json_revision_id) {
                    update_metadata('post', $post_revision_id, 'extb_json_revision_id', $extb_post_json_revision_id);
                }

            }
        }

    }

});

add_action('wp_restore_post_revision', function ($post_id, $revision_id) {
    $extb_json_revision_id = get_post_meta($revision_id, 'extb_json_revision_id', true);

    if ($extb_json_revision_id) {
        wp_restore_post_revision($extb_json_revision_id);
    }

}, 10, 2);

function save_post_data_post_has_changed($post_has_changed, $last_revision, $post)
{
    return true;
}

function save_post_data($post_id, $data, $type)
{

    if ($type != "content") {
        $save_lang = (isset($data['lang']) && $data['lang']) ? $data['lang'] : "default";
        $post_lang = get_post_language($post_id, get_default_language());

        if ($save_lang != "default" && $save_lang !== $post_lang) {

            $lang_post_id = get_post_in_language($post_id, $save_lang, false);

            if (!$lang_post_id || $post_id === $lang_post_id) {
                $source_post = get_post($post_id);
                $title = $source_post->post_title . ' - ' . $save_lang;
                $lang_post_id = create_partial($type, $data, $title);
                link_post_translations(
                    array(
                        "lang" => $post_lang,
                        "id" => $post_id
                    ),
                    array(
                    "lang" => $save_lang,
                    "id" => $lang_post_id
                    )
                );

                $new_post = new \ExtendBuilder\PostData($lang_post_id, $save_lang);

                return $new_post;
            } else {
                $post_id = $lang_post_id;
            }
        }

        return update_partial($post_id, $data);
    } else {
        if (!empty($data)) {
            $post = get_post($post_id);
            if ($post->post_type === "page") {
                maybe_update_page_template($post);
                wp_publish_post($post_id);
            }
        }
    }

    return update_partial($post_id, $data);
}
function maybe_update_page_template($post)
{
    if ($post && $post->post_type === "page") {
        $template = get_post_meta($post->ID, '_wp_page_template', true);
        if (!$template || $template === "default") {
            $page_template = apply_filters('colibri_page_builder/maintainable_default_template', "page-templates/full-width-page.php");
            update_post_meta($post->ID, '_wp_page_template', $page_template);
        }
    }
}

function update_menu_data($data)
{
    $menu = $data['theme']['menu'];
    $locations = $menu["locations"];
    $locations_to_add = $menu["locationsToAdd"];
    $locations_to_delete = $menu["locationsToDelete"];
    $default_location_names = array(
        $menu["defaultLocations"]["header"]["name"],
        $menu["defaultLocations"]["footer"]["name"],
    );

    for ($i = 0; $i < count($locations_to_delete); $i++) {
        $isDefaultLocation = false;
        foreach ($default_location_names as $default_location_name) {
            if ($locations_to_delete[$i] === $default_location_name) {
                $isDefaultLocation = true;
            }
        }
        //dont delete from the locations vector the default locations
        if ($isDefaultLocation) {
            array_splice($locations_to_delete, $i, 1);
        }
    }

    $new_data = $data;

    $new_locations = array_diff($locations, $locations_to_delete);
    $new_locations = array_merge($new_locations, $locations_to_add);

    $new_menu = $menu;

    $default_locations = array(
        "header-menu",
        "footer-menu",
    );
    $defaultLocations = array(
        "header" => array(
            "name" => "header-menu",
            "hasMenu" => false,
        ),
        "footer" => array(
            "name" => "footer-menu",
            "hasMenu" => false,
        ),
    );

    $new_menu["locations"] = $new_locations;
    $new_menu["locationsToAdd"] = array();
    $new_menu["locationsToDelete"] = array();

    $new_data['theme']['menu'] = $new_menu;

    return $new_data;
}


function save_options_and_partials_html($data)
{
    save_options($data, true);
    save_partials_html($data);
}

function merge_values_by_id($old_values, $new_values, $is_by_id_map = false)
{
    if ( is_string($new_values) ) {
        $new_values = json_decode( $new_values, true );
    }

    $is_string = is_string( $old_values );
    if ( $is_string ) {
        $old_values = json_decode( $old_values, true );
    }

    $old_values_by_id = $is_by_id_map ? $old_values : key_by_prop($old_values, 'id');
    $new_values_by_id = $is_by_id_map ? $new_values : key_by_prop($new_values, 'id');

    $new_values = array_replace( $old_values_by_id, $new_values_by_id );

    if (!$is_by_id_map) {
        $new_values = array_values($new_values);
    }

    if ( $is_string ) {
        $new_values = json_encode($new_values);
    }

    return $new_values;
}


function set_plugin_option_in_data(&$theme_data, $option_name, $value) {
    $assoc = colibri_options_to_theme_assoc();
    if (isset($assoc[$option_name])) {
        $theme_asoc = $assoc[$option_name];
        array_set_value($theme_data, $theme_asoc, $value);
    }
}
function save_options($data, $backup = false)
{
    // save theme//
    $options = get_key_value($data, 'options', array());

    if (isset($options["theme"])) {
        $theme_data = $options["theme"];
        if (!use_plugin_options()) {
            foreach ($options as $option_name => $option_value) {
                if ($option_name != "theme") {
                    switch ($option_name) {
                        case ColibriOptionsIds::RULES:
                            $new_option_value = merge_values_by_id(get_plugin_option($option_name, "[]"), $option_value, false);
                            break;
                        case ColibriOptionsIds::CSS_BY_RULE_ID:
                        case ColibriOptionsIds::CSS_BY_PARTIAL_ID:
                            $new_option_value  = merge_values_by_id(get_plugin_option($option_name, []), $option_value, true);
                            break;
                        default:
                            $new_option_value = $option_value;
                    }
                    set_plugin_option_in_data($theme_data, $option_name, $new_option_value);
                }
            }
            save_url_placeholders($theme_data);
            save_theme_data($theme_data, $backup);
        } else {
            save_theme_data($theme_data, $backup);
            foreach ($options as $option_name => $option_value) {
                if ($option_name != "theme") {
                    set_plugin_option($option_name, $option_value, $backup);
                }
            }
        }
    }
}

function save_partials_html($data)
{
    $partials = get_key_value($data, 'partials', array());
    foreach ($partials as $partial) {
        if ( isset( $partial['id'] ) ) {
            update_partial($partial['id'], $partial);
        }
    }
}
function assign_partials($data) {
	$options = get_key_value($data, 'options', array());
	$partials_to_assign = get_key_value($options, 'partialsToAssign', array());
	foreach($partials_to_assign as $page_id => $partials) {
		if(!is_array($partials)) {
			continue;
		}
		foreach ( $partials as $type => $partial_id ) {
			if ( ! $partial_id ) {
				continue;
			}
			assign_partial( $type, $page_id, $partial_id );
		}
	}
}


function save_url_placeholders(&$theme_data) {
    array_set_value($theme_data, 'urls.colibri_uploads_url', colibri_get_uploads_url());
    array_set_value($theme_data, 'urls.colibri_home_url', colibri_get_home_url());
}

function get_saved_url_placeholders($data = null) {
    return get_key_value($data, 'urls', array());
}

function get_url_placeholders() {
    return ["colibri_uploads_url", "colibri_home_url"];
}

function get_current_url_placeholders($reverse = false) {
    $arr = array(
        "colibri_uploads_url" => colibri_get_uploads_url(),
        "colibri_home_url" => colibri_get_home_url()
    );
    if ($reverse) {
        $arr = array_flip($reverse);
    }
    return $arr;
}


function replace_strings_in_data( $data, $strings = [] ) {
    if ( is_string( $data ) ) {
        $decoded_value = json_decode(  $data , true );
        if ( json_last_error() === JSON_ERROR_NONE ) {
            $data = json_encode(replace_strings_in_data($decoded_value, $strings));
        } else {
            foreach ($strings as $string_data) {
                $toFind = $string_data['find'];
                $toReplace = $string_data['replace'];
                $data = str_replace($toFind, $toReplace, $data);
            }
        }
    }
    if ( is_array( $data ) ) {
        foreach ( $data as $key => $value ) {
            $data[ $key ] = replace_strings_in_data( $value, $strings );
        }
    }
    return $data;
}

function to_ordered_placeholder_array($data, $flip = false) {
    $keys = get_url_placeholders();
    $ordered = [];
    foreach ($keys as $index => $key) {
        $key_shortcode = "[$key]";
        $find = $flip ? $data[$key] : $key_shortcode;
        $replace = $flip ? $key_shortcode : $data[$key];
        $ordered[] = array(
            "find" => $find,
            "replace" => $replace
        );
    }
    return $ordered;
}

function maybe_update_site_urls($data) {
    $old_urls = get_saved_url_placeholders($data);
    $new_data = false;
    if (!empty($old_urls)){
        $current_urls = get_current_url_placeholders();
        $diff = array_diff_assoc($old_urls, $current_urls);
        if (!empty($diff)) {
            $new_data = replace_strings_in_data($data, to_ordered_placeholder_array($old_urls, true));
            $new_data = replace_strings_in_data($new_data, to_ordered_placeholder_array($current_urls));
        }
    }
    return $new_data;
}

function save_partials_data($data)
{
	assign_partials($data);
    $partials = get_key_value($data, 'partials', array());
    foreach ($partials as $partial_id => $partial_value) {
        $partial_type = $partial_value['type'];
        if ($partial_type == "content") {
            save_post_data($partial_id, $partial_value, "content");
        }
    }

    foreach ($partials as $partial_id => $partial_value) {
        $partial_type = $partial_value['type'];
        if ($partial_type !== "content") {
            $partial_type = $partial_value['type'];
            $id = $partial_id;
            if ($id !== -1) {
                save_post_data($id, $partial_value, $partial_type);
            }
        }
    }
}

function save_options_and_partials($data)
{
    save_options($data);
    save_partials_data($data);
}


add_action('colibri_page_builder/content_setting_update', '\ExtendBuilder\save_options_and_partials', 1, 2);
