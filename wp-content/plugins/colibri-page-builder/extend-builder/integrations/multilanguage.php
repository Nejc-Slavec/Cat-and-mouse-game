<?php

namespace ExtendBuilder;

function colibri_polylang_is_active()
{
    return function_exists('pll_get_post');
}

function colibri_wpml_is_active()
{
    return class_exists('SitePress');
}

function colibri_multilanguage_is_active()
{
    return colibri_polylang_is_active() || colibri_wpml_is_active();
}

function get_default_language()
{
    if (function_exists('\ExtendBuilder\colibri_get_default_language')) {
        $default = colibri_get_default_language();
        if ($default) {
            return $default;
        }
    }
    return "default";
}

function get_current_language()
{
    if (function_exists('\ExtendBuilder\colibri_get_current_language')) {
        return colibri_get_current_language();
    }
    return get_default_language();
}

function get_post_language($post_id, $default = null)
{

    if (is_null($default)) {
        $default = get_default_language();
    }

    $lang = "";

    if (function_exists('\ExtendBuilder\colibri_get_post_language')) {
        $lang = colibri_get_post_language($post_id);
    }

    if (!$lang) {
        $lang = $default;
    }
    return $lang;
}

function set_post_language($post_id, $lang)
{
    if (function_exists('pll_set_post_language')) {
        return pll_set_post_language($post_id, $lang);
    }
}

function get_post_in_language($post_id, $lang, $default = true)
{


    if (function_exists('\ExtendBuilder\colibri_is_default_language') && !colibri_is_default_language($lang)) {
        $post_id_lang = null;
        if (colibri_polylang_is_active()) {
            $post_id_lang = pll_get_post($post_id, $lang);
        }

        if (colibri_wpml_is_active()) {
            $post_id_lang = apply_filters('wpml_object_id', $post_id, "any", false, $lang);
            $translations = apply_filters('wpml_get_element_translations', null, $post_id, 'post_post');
            if (!$post_id_lang && isset($translations[$lang])) {
                $post_id_lang = $translations[$lang]->element_id;
            }

            //icl_object_id( $post_id, get_post($post_id)->post_type, false, $lang );
        }

        if ($post_id_lang) {
            return $post_id_lang;
        }
    }

    if ($default === true) {
        return $post_id;
    }

    return $default;
}
