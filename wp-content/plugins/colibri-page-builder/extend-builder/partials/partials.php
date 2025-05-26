<?php

namespace ExtendBuilder;

function partials_types()
{
    return apply_filters('colibri_page_builder/partials_type', array(
        "header" => array("post", "front_page"),
        "footer" => array("post"),
        "sidebar" => array("post", "page", "product"),
        "main" => array("post", "archive", "product", "archive_product", "404", "search"),
    ));
}

function partial_template_default_structure($type = "")
{
    return array(
        "json" => "{}",
        "meta" => array(),
        "html" => "",
        "css" => "",
        "id" => -1,
        "dynamic" => ($type == "sidebar") ? true : false,
    );
}

function current_page_id()
{
    $id = get_the_ID();

    return $id;
}

function current_page_partial_key()
{
    if (is_singular()) {
        return get_the_ID();
    } else {
        return get_page_default_types();
    }
}

function get_current_partial_post($type, $lang = "default")
{

    if (is_singular()) {
        if ($type == "content") {
            return get_post();
        }

        $post_data = new PostData(current_page_id());
        // remove template meta to test default
        //$post_data->unset_meta_value($type);

        $custom_post = $post_data->get_data($type, true, default_partial_post($type, 'post'));


        return $custom_post;
    }

    $default_for = $type == "main" ? "archive" : "post";
    $default_post = default_partial_post($type, $default_for);

    return $default_post;
}

function assign_partial($type, $post_id, $header_id)
{
    $post_data = new PostData($post_id);

    $default_post = default_partial_post($type);

    //TODO @adi :
    if ($default_post->ID == $header_id) {
        $post_data->unset_meta_value($type);
    } else {
    $post_data->set_meta_value($type, $header_id);
    }
}

function default_partial_post($type, $default_for = 'post', $lang = "default")
{
    $post_default = get_default_partial_id($type, $default_for);

    $template = apply_filters(prefix("default_partial"), -1, $type);

    if ($template == -1) {
        $template = $post_default;
    }

    $template_post = get_post(get_post_in_language($template, $lang));

    return $template_post;
}

function get_default_partial_id($type, $default_for = "post")
{
    $path = "defaults.partials.$type.$default_for";

    $defaultId = get_current_theme_data($path);


    if ($defaultId == null || $defaultId == -1 || !get_post($defaultId)) {
        return -1;
    }

    return $defaultId;
}

function maybe_set_as_default_partial($type, $id, $default_for = "post", $force = false)
{
    $path = "defaults.partials.$type.$default_for";
    if ($force || get_default_partial_id($type, $default_for) == -1) {
        set_theme_path($path, $id);
    }
}

function get_partial_post_in_lang()
{

}

function update_partial($post_id, $data, $name = false)
{

    if (empty($data)) {
        return;
    }

    $lang = isset($data['lang']) ? $data['lang'] : 'default';

    $post = new \ExtendBuilder\PostData($post_id, $lang);


    if (isset($data['json'])) {
        $json = $data['json'];
        if (!is_string($json)) {
            $json = json_encode($data['json']);
        }

        $post->set_data('json', $json);
    }

    $post_data = array('ID' => $post_id);

    if (isset($data['html'])) {
        \ExtendBuilder\log("ContentSetting::update -> html ->" . $data['html']);
        $post_data['post_content'] = $data['html'];
    }

    if ($name) {
        $post_data['post_title'] = $name;
    }

    add_filter('wp_save_post_revision_post_has_changed', '\ExtendBuilder\save_post_data_post_has_changed', 20, 3);
    $post_id = wp_update_post($post_data);
    remove_filter('wp_save_post_revision_post_has_changed', '\ExtendBuilder\save_post_data_post_has_changed', 20);


    if (isset($data['meta'])) {
        $post->set_meta_value('meta', $data['meta']);
    }

    return $post;
}

function create_empty_partial($type)
{
    $post_data = new PostData();
    $r = $post_data->create_data($type, '', true);
    if (!is_wp_error($r)) {
        return $r->ID;
    }

    return -1;
}

function create_partial($type, $data, $name = "")
{
    $post_data = new PostData();
    $r = $post_data->create_data($type, '', true);

    if (!is_wp_error($r)) {
        update_partial($r->ID, $data, $name);

        return $r->ID;
    }

    return null;
}

function get_partial_details($post, $type = null)
{
    $partial_details =  array(
        'id' => $post->ID,
        'name' => $type ? $type : $post->post_title,
        'type' => $type ? $type : custom_post_type_simple_name($post->post_type),
        'data' => get_partial_data($post->ID),
        'slug' => $post->post_name,
        'permalink' => get_post_permalink($post->ID),
        'is-home' => intval( get_option( 'page_on_front' ) ) ===  $post->ID,
        'lang' => get_post_language($post->ID)
    );

    $partial_details = apply_filters('colibri_page_builder/get_partial_details', $partial_details);

    return $partial_details;
}

function get_partial_default_for_key_old($type, $default_for)
{
    return join("_", array($default_for, $type));
}

function get_partial_default_for_key($type, $default_for)
{
    return join("_", array($type, $default_for));
}

function get_partials_of_type($type = false, $default_for = false)
{
    $posts = get_custom_posts($type);
    $result = array();
    foreach ($posts as $index => $post) {
        $custom_default_for = $default_for === false || $post->post_title == get_partial_default_for_key($type, $default_for) || $post->post_title == get_partial_default_for_key_old($type, $default_for);

        $custom_default_for = apply_filters('colibri_page_builder/is_default', $custom_default_for, $post, $type, $default_for);

        if ($custom_default_for) {
            $post_details = get_partial_details($post);
            array_push($result, $post_details);
        }
    }

    return $result;
}

function post_supports_partial($post_id, $type)
{
    $value = true;
    if ($post_id !== -1 && is_page($post_id)) {
        if ($type === "main" || $type === "sidebar") {
            $value = false;
        }
    }

    $value = apply_filters('colibri_page_builder/post_supports_partial', $value, $post_id, $type);
    return $value;
}

function partials_types_list($include_content = false)
{
    $colibri_partials_types = partials_types();
    $parts = [];

    if ($include_content) {
        array_push($parts, "content");
    }

    foreach ($colibri_partials_types as $name => $partial) {
        array_push($parts, $name);
    }

    return $parts;
}

function init_empty_partial($post_id, $type, $data, $default_key, $name, $force_as_default = false)
{
    update_partial($post_id, $data, $name);
    maybe_set_as_default_partial($type, $post_id, $default_key, $force_as_default);

    return $post_id;
}

function create_default_partial($type, $data, $default_key, $name, $force_as_default = false)
{
    $post_id = create_partial($type, $data, $name);
    maybe_set_as_default_partial($type, $post_id, $default_key, $force_as_default);

    return $post_id;
}

function load_default_partial($name, $default_for = 'post', $force_as_default = false)
{
    $key = get_partial_default_for_key($name, $default_for);
    require_once __DIR__ . "/defaults/$key.php";
    create_default_partial($name, get_file_value($key), $default_for, $key, $force_as_default);
}


function export_colibri_data($options = array(), $encode = false)
{

    $generated_paths = array(
        "theme" => array("cssById", "cssByPartialId"),
        "partials" => array("data.html", "data.css")
    );

    $exclude_generated = get_key_value($options, 'exclude_generated', false);

    $partials = get_partials_of_type(array_keys(partials_types()));

    $args = array(
        'posts_per_page' => -1,
        "post_type" => 'page',
        'meta_key' => 'extend_builder'
    );

    $query = new \WP_Query($args);

    if ($query->have_posts()) {
        foreach ($query->posts as $post) {
            $partial = get_partial_details($post, "content");
            array_push($partials, $partial);
        }
    }

    $theme_data = get_theme_data();

    if ($exclude_generated) {
        foreach ($partials as &$partial) {
            foreach ($generated_paths['partials'] as $path) {
                array_unset_value($partial, $path);
            }
        }
        foreach ($generated_paths['theme'] as $path) {
            array_unset_value($theme_data, $path);
        }
    }


    $options = array(
        "theme" => $theme_data
    );

    $options[ColibriOptionsIds::RULES] = get_plugin_option(ColibriOptionsIds::RULES);

    $data = array(
        "options" => $options,
        "partials" => $partials
    );

    if ($encode) {
        $data = json_archive($data);
    }

    return $data;
}
