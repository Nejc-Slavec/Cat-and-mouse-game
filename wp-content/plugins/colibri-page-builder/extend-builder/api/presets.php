<?php

namespace ExtendBuilder;

function wp_colibri_v1_add_preset($req)
{
    if(!isset($req['value']) || empty($req['value']) || !$req['value']) {
       return new \WP_REST_Response(null, 400);
    }
    if(!colibri_user_can_customize()) {
        return new \WP_REST_Response(null, 401);
    }
    $value = $req['value'];
    $args = array(
        //wp_slash is needed for keeping the double qoutes slashes
        //https://wordpress.stackexchange.com/questions/53336/wordpress-is-stripping-escape-backslashes-from-json-strings-in-post-meta
        'post_content' => wp_slash($value),
        'post_type' => 'preset',
    );

    $post_id = PostData::disabled_filters_and_run('\wp_insert_post',array($args));

    $post = get_post($post_id);

    $preset = json_decode($post->post_content, true);
    $preset['id'] = $post->ID;
    return json_encode($preset);
}

function wp_colibri_v1_delete_preset($req) {
    if(!isset($req['postId']) || empty($req['postId']) || !$req['postId']) {
        return new \WP_REST_Response(null, 400);
    }
    if(!colibri_user_can_customize()) {
        return new \WP_REST_Response(null, 401);
    }
    $post_id = $req['postId'];
    $result = wp_delete_post($post_id);
    if($result) {
	    return  new \WP_REST_Response(null, 204);
    } else {
        return array(
            'success' => false,
        );
    }
}


add_action('rest_api_init', function () {
    register_rest_route('colibri/v1', '/presets', array(
        'methods' => 'POST',
        'callback' => '\ExtendBuilder\wp_colibri_v1_add_preset',
        'permission_callback' => function () {
            return current_user_can( 'edit_theme_options' );
        }
    ));
    register_rest_route('colibri/v1', '/presets/delete', array(
        'methods' => 'POST',
        'callback' => '\ExtendBuilder\wp_colibri_v1_delete_preset',
        'permission_callback' => function () {
            return current_user_can( 'edit_theme_options' );
        }
    ));
});


