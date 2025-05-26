<?php

use function ExtendBuilder\array_get_value;


function colibriwp_fix_wp58_sidebar()
{
    if (get_option('colibriwp_fix_wp58_sidebar')) {
        return;
    }

    update_option('colibriwp_fix_wp58_sidebar', true);

    $sidebars_widgets = wp_get_sidebars_widgets();
    $first_block = array_get_value($sidebars_widgets, 'colibri-sidebar-1.0');
    if ($first_block === 'block-2') {
        $widget_block_option = get_option('widget_block', array());

        if (isset($widget_block_option[2])) {
            $widget_block_option[2] = array(
                'content' => '<!-- wp:search {"showLabel":false,"buttonUseIcon":true} /-->'
            );
            update_option('widget_block', $widget_block_option);
        }
    }
}

add_action('admin_init', 'colibriwp_fix_wp58_sidebar');

add_action('colibri_page_builder/activated', 'colibriwp_fix_wp58_sidebar');
