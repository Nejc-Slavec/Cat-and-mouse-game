<?php

namespace ExtendBuilder;

function extract_paths_from_theme($paths, &$theme_data)
{
    $options = array();
    foreach ($paths as $source => $destination) {
        if (isset($theme_data[$source])) {
            $options[$destination] = $theme_data[$source];
            unset($theme_data[$source]);
        }
    }
    return $options;
}

function extract_options_from_theme(&$theme_data)
{
    $options = extract_paths_from_theme(colibri_theme_to_options_assoc(), $theme_data);
    return array(
        "options" => $options,
        "data" => $theme_data
    );
}

function update_plugin_options_from_theme_data($data)
{
    $result = extract_options_from_theme($data);
    $data = $result['data'];
    $options = $result['options'];
    foreach ($options as $option_name => $option_value) {
        set_plugin_option($option_name, $option_value);
    }
    return $data;
}

function maybe_normalize_old_format($pages_content)
{

    // old format was a string//
    if (!is_string($pages_content) || empty($pages_content)) {
        return null;
    }

    $pages_content = json_inflate($pages_content);

    if ($pages_content) {

        $partialsById = array();
        $theme_data = array();
        $partials = array();

        foreach ($pages_content as $id => $page) {
            if (is_numeric($id)) {
                $page_partials = get_key_value($page, 'page', array());
                foreach ($page_partials as $partial) {
                    $partial_id = get_key_value($partial, 'id', -1);
                    if (is_numeric($partial_id) && $partial_id != -1) {
                        $partialsById[$partial_id] = $partial;
                    }
                }
                $theme_data = get_key_value($page, 'data.theme', array());
            }
        }

        foreach ($partialsById as $partial_id => $partial) {
            $partialsById[$partial_id] = json_archive($partial);
        }

        $new_data = extract_options_from_theme($theme_data);

        $new_theme_data = array_get_value($new_data, 'data', array());
        $options = array_get_value($new_data, 'options', array());


        $options['theme'] = json_archive($new_theme_data);


        return array(
            "partials" => $partialsById,
            "options" => $options
        );
    }

    return null;
}

function maybe_migrate_to_options($data)
{
    $rules = get_plugin_option(ColibriOptionsIds::RULES, false);
    if ($rules !== false) {
        return $data;
    }
    $new_data = update_plugin_options_from_theme_data($data);
    return $new_data;
}
