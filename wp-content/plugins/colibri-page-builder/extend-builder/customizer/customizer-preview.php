<?php

namespace ExtendBuilder;

add_action('get_footer', function () {
    if (is_customize_preview()) {
        echo '<script id="extend-builder-preview-ui-json" type="template/json"></script>';
    }
});

add_filter('colibri_page_builder/template',
    function ($page_template, $companion, $post) {
        if (is_customize_preview()) {
            if ($post && is_page($post->ID) && show_page_content()) {
                $post_id = $post->ID;
                $template = get_post_meta($post_id, '_wp_page_template', true);
                if (!$template || $template === "default") {
                    $full_page_template = apply_filters('colibri_page_builder/maintainable_default_template', "page-templates/full-width-page.php");

                    return get_query_template("page", [$full_page_template]);
                }
            }

        }

        return $page_template;
    }, 10, 3);


// register in preview//
function registerPreviewAssets($wp_customize)
{
    // skip in browser preview//
    if (is_customize_preview()) {
        registerBuilderAssets();

        $ver = version();

        if (isDev()) {
            wp_enqueue_script('h-preview', assetsUrl() . "/dev-preview.js",
                array(
                    'jquery',
                    'jquery-ui-sortable',
                    'customize-preview'
                ));

            wp_enqueue_script('h-iframe', devUrl("iframe.js"), array(
                'jquery',
                'jquery-ui-sortable',
                'customize-preview',
                'customize-selective-refresh',
                'colibri'
            ));

            wp_enqueue_style('h-style', devUrl("dist/static/css/iframe.css"),
                array(), $ver);

        } else {
            wp_enqueue_style('h-style-vendor', builderUrl("vendor.css", "css"));

            wp_enqueue_script('h-iframe', builderUrl("iframe.js", "js"),
                array(
                    'jquery',
                    'jquery-ui-sortable',
                    'customize-preview',
                    'customize-selective-refresh',
                    'colibri'
                ), $ver);

            wp_enqueue_style('h-style', builderUrl("iframe.css", "css"),
                array('h-style-vendor'), $ver);
        }

        wp_enqueue_style('colibri-icons-style',
            builderUrl('colibri-icons-style.css'), array(), $ver);

        wp_enqueue_style('h-fonts',
            "//fonts.googleapis.com/css?family=Roboto:400,500,700,400italic|Material+Icons");


        add_action('colibri_page_builder/template/load_assets',
            function ($companion) {
                wp_localize_script('h-iframe', 'colibriInit', init_data());
            });
    }
}

add_action('customize_preview_init', '\ExtendBuilder\registerPreviewAssets');


add_action('customize_preview_init', function () {
    if (!is_customize_preview()) {
        return;
    }

    add_action('wp_head', function () {
        ?>
      <script>
        var colibriCustomizerPreviewData = {};
      </script>
        <?php
    });
}, PHP_INT_MAX);
