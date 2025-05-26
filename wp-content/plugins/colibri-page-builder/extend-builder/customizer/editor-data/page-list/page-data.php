<?php

namespace ExtendBuilder;

class PageData
{
    public $label;
    public $url;
    public $category;
    public $colibri;
    public $id;
    public $is_blog_page;
    public $is_woo_page;

    function __construct($label, $url, $category, $post_id = -1)
    {
        $this->label = $label;
        $this->url = $this->get_customizer_url($url);
        $this->category = $category;
        $this->colibri = $this->is_colibri_page($post_id);
        $this->id = $post_id;
        $this->is_blog_page = $post_id == get_option( 'page_for_posts' );
        $this->is_woo_page = is_woocommerce_page($post_id) || is_woocommerce_shop_page($post_id);
    }

     function is_colibri_page($post_id) {
        if ($post_id === -1) {
            return false;
        }
        $post_data = new PostData($post_id);
        $post_json_id = $post_data->get_meta_value("json", -1);
        if ($post_json_id !== -1) {
            return true;
        }
        return false;
    }

    function get_customizer_url($url)
    {
        $encodedUrl = rawurlencode($url);
        return get_option('home') . "/wp-admin/customize.php?url=" . $encodedUrl;
    }

    function toArray() {
        return get_object_vars( $this );
    }

}
