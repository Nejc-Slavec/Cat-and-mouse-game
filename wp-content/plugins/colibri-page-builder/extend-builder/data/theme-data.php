<?php

namespace ExtendBuilder;

function get_colibri_option_name($name) {
    return "colibri_page_builder_".$name;
}

function colibri_options_list() {
    $options = apply_filters('colibri_page_builder/options', array(
        ColibriOptionsIds::CSS_BY_PARTIAL_ID,
        ColibriOptionsIds::CSS_BY_RULE_ID,
        ColibriOptionsIds::GLOBAL_CSS,
        ColibriOptionsIds::RULES
    ));
    return $options;
}


function colibri_theme_to_options_assoc() {
    return array(
        'cssByPartialId' => ColibriOptionsIds::CSS_BY_PARTIAL_ID,
        'cssById' => ColibriOptionsIds::CSS_BY_RULE_ID,
        'css' => ColibriOptionsIds::GLOBAL_CSS,
        'rules' =>  ColibriOptionsIds::RULES,
    );
}

function colibri_options_to_theme_assoc() {
    $options = colibri_theme_to_options_assoc();
    return array_flip($options);
}



function get_colibri_options() {
    $options = colibri_options_list();
    $value = array();

    foreach ($options as $option_name) {
        $value[$option_name] = get_plugin_option($option_name);
    }

    $value["theme"] = get_theme_data();

    maybe_inflate_values($value);

    return $value;
}

function use_plugin_options() {
    return false;
}

function delete_plugin_option($option_name){
    delete_option(get_colibri_option_name($option_name));
}

function gather_partial_style_refs($data, &$collector)
{

    if (isset($data['styleRef'])) {
        $collector[$data['styleRef']] = true;
    }

    if (isset($data['children']) && is_array($data['children'])) {
        foreach ($data['children'] as $child) {
            gather_partial_style_refs($child, $collector);
        }
    }


    if (isset($data['slots']) && is_array($data['slots'])) {
        foreach ($data['slots'] as $slot_name => $slot_data) {
            foreach ($slot_data as $slot_child) {
                gather_partial_style_refs($slot_child, $collector);
            }
        }
    }
}

function key_by_prop($arr, $prop) {
    $by_key = [];
    foreach ($arr as $item) {
        if (isset($item[$prop])) {
            $by_key[$item[$prop]] = $item;
        }
    }
    return $by_key;
}

function get_partial_rules($partial, $data) {
    $partial_inlined_rules = array_get_value($partial, 'rules', false);
    if (!is_array($partial_inlined_rules)) {
        return extract_partial_rules($partial, $data);
    }
    return key_by_prop($partial_inlined_rules, 'id');
}

function extract_partial_rules($partial, $data)
{
    $sheet_rules = array_get_value($data, 'options.' . ColibriOptionsIds::RULES, "[]");
    $sheet_rules = json_decode($sheet_rules, true);
    $json = $partial["json"];
    if (is_string($json)) {
        $json = json_decode($json, true);
    }
    $style_refs = [];
    gather_partial_style_refs($json, $style_refs);
    $partial_rules = [];
    foreach ($sheet_rules as $rule) {
        if (isset($rule['id']) && isset($style_refs[$rule['id']])) {
            $partial_rules[$rule['id']] = $rule;
        }
    }
    return $partial_rules;
}
function set_plugin_option($option_name, $data, $backup = false)
{

    if (use_plugin_options()) {
        $colibri_option_name = get_colibri_option_name($option_name);
        if ($backup) {
            update_option("{$colibri_option_name}_".time(), get_option($colibri_option_name, array()));
        }
        update_option($colibri_option_name, $data);
    } else {
        $assoc = colibri_options_to_theme_assoc();
        if (isset($assoc[$option_name])) {
			$theme_asoc = $assoc[$option_name];
			set_theme_path($theme_asoc, $data);
		}
    }
}



function get_plugin_option($option_name, $default = null)
{
    if (use_plugin_options()) {
        return get_option(get_colibri_option_name($option_name), $default);
    } else {
        $theme_data = get_theme_data();
        $extracted = extract_options_from_theme($theme_data);
        $options = $extracted['options'];
        return array_get_value($options, $option_name, $default);
    }
}

function get_sheet_rules() {
    if (use_plugin_options()) {
        $rules = get_plugin_option(ColibriOptionsIds::RULES, "{}");
    } else {
        $rules = get_theme_data('rules', false, "{}");
    }
    return json_decode( $rules, true );
}

function save_sheet_rules( &$rules) {
    $rules_json = $rules;
    if (!is_string($rules_json)) {
        $rules_json = json_encode( $rules);
    }
    set_plugin_option(ColibriOptionsIds::RULES, $rules_json);
}

function append_sheet_rules( $rules)
{

}

function save_theme_data($data, $backup = false)
{
    $theme_data = new ThemeDataAccess();
	if ($backup || !$data) {
        $theme_data->backup();
	}
    $theme_data->set($data);
}

function get_theme_path($path, $use_current_data = false, $default = null)
{
    return get_theme_data($path, $use_current_data, $default);
}

function set_theme_path($path, $value)
{
    $old = get_theme_data();
    array_set_value($old, $path, $value);
    save_theme_data($old);
}


function get_theme_data($key = false, $use_current_data = false, $default = null)
{
    $defaults = get_theme_data_defaults();

    if ($use_current_data) {
    	$current_data = get_current_data(-1, true);
	    $value = array_get_value($current_data, 'options.theme', $default);
    } else {
        $data = new ThemeDataAccess();
        $value = $data->get();
        if (use_plugin_options()){
            if (!empty($value)) {
                maybe_migrate_to_options($value);
            }
        }
    }

    $value = maybe_inflate($value);

    $data  = array_replace_recursive($defaults, $value);

    if ($key) {
        return array_get_value($data, $key, $default);
    }

    return $data;
}

function get_current_theme_data($path, $default = null)
{
	return get_theme_data($path, true, $default);
}
