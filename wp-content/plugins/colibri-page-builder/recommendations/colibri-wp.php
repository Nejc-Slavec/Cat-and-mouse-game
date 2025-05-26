<?php

namespace ExtendBuilder;

use ColibriWP\PageBuilder\PageBuilder;
use ColibriWP\PageBuilder\ThemeHooks;
use ColibriWP\Theme\PluginsManager;


if (!function_exists('ExtendBuilder\admin_notice_colibriwp_theme_required')) {
    add_action('admin_notices', 'ExtendBuilder\admin_notice_colibriwp_theme_required');

    function admin_notice_colibriwp_theme_required()
    {

        /** @var PluginsManager $manager */


        if (get_transient('colibri_wp_recommendation_hide_notice')) {
            return;
        }

        $current_template = get_template();

        global $colibri_page_builder_supported_themes;
        if (!in_array($current_template, $colibri_page_builder_supported_themes)) {
            return;
        }

        $installed = false;
        $themes = wp_get_themes();
        foreach ($themes as $theme) {
            if ($theme->stylesheet == 'colibri-wp') {
                $installed = true;
                break;
            }
        }

        if ($installed) {
            $link = add_query_arg(
                array(
                    'action'     => 'activate',
                    'stylesheet' => 'colibri-wp',
                    '_wpnonce'   => wp_create_nonce('switch-theme_colibri-wp')
                ),
                admin_url('themes.php')
            );

            $text = 'Activate Colibri Theme';
        } else {
            $link = add_query_arg(
                array(
                    'theme' => 'colibri-wp',
                    '_wpnonce' => wp_create_nonce('install-theme_colibri-wp')
                ),
                network_admin_url('update.php?action=install-theme')
            );

            $text = 'Install Colibri Theme';
        }


?>
        <div class="notice notice-warning is-dismissible colibri-wp-recommendation">
            <style>
                .colibri-wp-notice {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    padding: 4px 20px;
                }

                .colibri-wp-notice-col1 {
                    margin-right: 20px;
                }

                .colibri-wp-notice-col1 h3 {
                    font-size: 16px;
                    font-weight: normal;
                }
            </style>
            <div class="colibri-wp-notice">
                <div class="colibri-wp-notice-col1">
                    <h3><strong>Colibri Page Builder</strong> requires the <strong>Colibri WP Theme</strong> to be installed and active.
                    </h3>
                </div>
                <div class="colibri-wp-notice-col2">
                    <a class="button button-primary" href="<?php echo esc_url($link); ?>"><?php echo esc_html($text); ?></a>
                </div>
            </div>
        </div>

        <?php

        add_action('admin_footer', function () {
        ?>
            <script>
                jQuery(function($) {
                    $(document).on('click', '.colibri-wp-recommendation .notice-dismiss', function() {
                        $.post("<?php echo admin_url("/admin-ajax.php");//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>", {
                            action: 'colibri_wp_recommendation_hide_notice',
                            _wpnonce: '<?php echo wp_create_nonce('colibri_wp_recommendation_hide_notice_nonce'); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>'
                        })
                    });
                });
            </script>
<?php
        });
    }


    add_action('wp_ajax_colibri_wp_recommendation_hide_notice', function () {
	    check_ajax_referer('colibri_wp_recommendation_hide_notice_nonce');
        set_transient('colibri_wp_recommendation_hide_notice', true, WEEK_IN_SECONDS);
    });
}
