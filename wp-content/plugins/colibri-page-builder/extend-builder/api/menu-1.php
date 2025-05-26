<?php
namespace ExtendBuilder;
function wp_colibri_v1_get_all_menus()
{
    if(!colibri_user_can_customize()) {
        return new \WP_REST_Response(null, 401);
    }
    $menus_names = [];
    $menus = wp_get_nav_menus();
    foreach ($menus as $menu) {
        $obj = new \stdClass;
        foreach (get_object_vars($menu) as $key => $value) {
            $obj->$key = $value;
        }
//        $obj->name = $menu->name;
        $menus_names[] = $obj;
    }

    return $menus_names;
}

add_action('rest_api_init', function () {
    register_rest_route('colibri/v1', '/menus', array(
        'methods'  => 'GET',
        'callback' => '\ExtendBuilder\wp_colibri_v1_get_all_menus',
        'permission_callback' => function () {
            return current_user_can( 'edit_theme_options' );
        }
    ));
});
