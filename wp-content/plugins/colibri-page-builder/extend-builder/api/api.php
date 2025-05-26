<?php
namespace ExtendBuilder;

class Api
{

    public static $name       = "extend_builder";
    public static $end_points = array();
    public static function addEndPoint($name, $api)
    {
        Api::$end_points[$name] = $api;
    }

    public function __construct()
    {
        $this->extendBuilderApi();
    }

    public function apiCall()
    {


        if (!colibri_user_can_customize()) {
            wp_send_json_error('unauthenticated');
            wp_die();
        }

        if (!defined('DOING_AJAX')) {
            define( 'DOING_AJAX', true );
        }
	    check_ajax_referer( 'extend_builder_api_nonce' );
        $options = isset($_REQUEST['api']) ? $_REQUEST['api'] : "{}";
        $options = wp_unslash($options);

        $options = json_decode($options, true);

        $data    = isset($options['data']) ? $options['data'] : false;
        $slug    = isset($options['action']) ? $options['action'] : false;

        if ($slug && method_exists($this, $slug)) {
            call_user_func_array(array($this, $slug), array($data));
        }

        $path = explode("/", $slug);
        if (count($path) == 2) {
            if (isset(Api::$end_points[$path[0]])) {
                $class = Api::$end_points[$path[0]];
                if (method_exists($class, $path[1])) {
                    call_user_func_array(array($class, $path[1]), array($data));
                }
            }
        }

        wp_die();
    }

    public function list_page_types()
    {
        
        echo json_encode($post_types);
    }
    
    public function extendBuilderApi()
    {
        add_action('wp_ajax_' . self::$name, function () {
            $this->apiCall();
        });
    }

}

new Api();
