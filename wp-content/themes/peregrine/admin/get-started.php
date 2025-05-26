<?php

use ColibriWP\Theme\PluginsManager;
use ColibriWP\Theme\Translations;

$peregrine_is_builder_installed = apply_filters( 'peregrine_page_builder/installed', false );

wp_enqueue_script( 'updates' );

function peregrine_get_setting_link( $setting ) {
    return esc_attr( peregrine_theme()->getCustomizer()->getSettingQuickLink( $setting ) );
}

?>

<div class="peregrine-get-started__container peregrine-admin-panel">
    <div class="peregrine-get-started__section">
        <h2 class="col-title peregrine-get-started__section-title">
            <span class="peregrine-get-started__section-title__icon dashicons dashicons-admin-plugins"></span>
            <?php Translations::escHtmlE( 'get_started_section_1_title' ); ?>
        </h2>
        <div class="peregrine-get-started__content">


            <?php foreach ( peregrine_theme()->getPluginsManager()->getPluginData() as $peregrine_recommended_plugin_slug => $peregrine_recommended_plugin_data ): ?>
                <?php
                $peregrine_plugin_state = peregrine_theme()->getPluginsManager()->getPluginState( $peregrine_recommended_plugin_slug );
                $peregrine_notice_type  = $peregrine_plugin_state === PluginsManager::ACTIVE_PLUGIN ? 'blue' : '';
                if ( isset( $peregrine_recommended_plugin_data['internal'] ) && $peregrine_recommended_plugin_data['internal'] ) {
                    continue;
                }
                ?>
                <div 
				
					class="peregrine-notice <?php echo esc_attr( $peregrine_notice_type ); ?> plugin-card-<?php echo $peregrine_recommended_plugin_slug;?>">
                    <div class="peregrine-notice__header">
                        <h3 class="peregrine-notice__title"><?php echo esc_html( peregrine_theme()->getPluginsManager()->getPluginData( "{$peregrine_recommended_plugin_slug}.name" ) ); ?></h3>
                        <div class="peregrine-notice__action">
                            <?php if ( $peregrine_plugin_state === PluginsManager::ACTIVE_PLUGIN ): ?>
                                <p class="peregrine-notice__action__active"><?php Translations::escHtmlE( 'plugin_installed_and_active' ); ?> </p>
                            <?php else: ?>
                                <?php if ( $peregrine_plugin_state === PluginsManager::INSTALLED_PLUGIN ): ?>
                                    <a class="button button-large colibri-plugin activate-now" 
										data-slug="<?php echo $peregrine_recommended_plugin_slug;?>"
                                       href="<?php echo esc_url( peregrine_theme()->getPluginsManager()->getActivationLink( $peregrine_recommended_plugin_slug ) ); ?>">
                                        <?php Translations::escHtmlE( 'activate' ); ?>
                                    </a>
                                <?php else: ?>
                                    <a class="button button-large colibri-plugin install-now"
									   data-slug="<?php echo $peregrine_recommended_plugin_slug;?>"
                                       href="<?php echo esc_url( peregrine_theme()->getPluginsManager()->getInstallLink( $peregrine_recommended_plugin_slug ) ); ?>">
                                        <?php Translations::escHtmlE( 'install' ); ?>
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <p class="peregrine-notice__description"><?php echo esc_html( peregrine_theme()->getPluginsManager()->getPluginData( "{$peregrine_recommended_plugin_slug}.description" ) ); ?></p>


                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="peregrine-get-started__section">
        <h2 class="peregrine-get-started__section-title">
            <span class="peregrine-get-started__section-title__icon dashicons dashicons-admin-appearance"></span>
            <?php Translations::escHtmlE( 'get_started_section_2_title' ); ?>
        </h2>
        <div class="peregrine-get-started__content">
            <div class="peregrine-customizer-option__container">
                <div class="peregrine-customizer-option">
                    <span class="peregrine-customizer-option__icon dashicons dashicons-format-image"></span>
                    <a class="peregrine-customizer-option__label"
                       target="_blank"
                       href="<?php echo esc_url( peregrine_get_setting_link( 'logo' ) ); ?>">
                        <?php Translations::escHtmlE( 'get_started_set_logo' ); ?>
                    </a>
                </div>
                <div class="peregrine-customizer-option">
                    <span class="peregrine-customizer-option__icon dashicons dashicons-format-image"></span>
                    <a class="peregrine-customizer-option__label"
                       target="_blank"
                       href="<?php echo esc_url( peregrine_get_setting_link( 'hero_background' ) ); ?>">
                        <?php Translations::escHtmlE( 'get_started_change_hero_image' ); ?>
                    </a>
                </div>
                <div class="peregrine-customizer-option">
                    <span class="peregrine-customizer-option__icon dashicons dashicons-menu-alt3"></span>
                    <a class="peregrine-customizer-option__label"
                       target="_blank"
                       href="<?php echo esc_url( peregrine_get_setting_link( 'navigation' ) ); ?>">
                        <?php Translations::escHtmlE( 'get_started_change_customize_navigation' ); ?>
                    </a>
                </div>
                <div class="peregrine-customizer-option">
                    <span class="peregrine-customizer-option__icon dashicons dashicons-layout"></span>
                    <a class="peregrine-customizer-option__label"
                       target="_blank"
                       href="<?php echo esc_url( peregrine_get_setting_link( 'hero_layout' ) ); ?>">
                        <?php Translations::escHtmlE( 'get_started_change_customize_hero' ); ?>
                    </a>
                </div>
                <div class="peregrine-customizer-option">
                    <span class="peregrine-customizer-option__icon dashicons dashicons-admin-appearance"></span>
                    <a class="peregrine-customizer-option__label"
                       target="_blank"
                       href="<?php echo esc_url( peregrine_get_setting_link( 'footer' ) ); ?>">
                        <?php Translations::escHtmlE( 'get_started_customize_footer' ); ?>
                    </a>
                </div>
                <?php if ( $peregrine_is_builder_installed ): ?>
                    <div class="peregrine-customizer-option">
                        <span class="peregrine-customizer-option__icon dashicons dashicons-art"></span>
                        <a class="peregrine-customizer-option__label"
                           target="_blank"
                           href="<?php echo esc_url( peregrine_get_setting_link( 'color_scheme' ) ); ?>">
                            <?php Translations::escHtmlE( 'get_started_change_color_settings' ); ?>
                        </a>
                    </div>
                    <div class="peregrine-customizer-option">
                        <span class="peregrine-customizer-option__icon dashicons dashicons-editor-textcolor"></span>
                        <a class="peregrine-customizer-option__label"
                           target="_blank"
                           href="<?php echo esc_url( peregrine_get_setting_link( 'general_typography' ) ); ?>">
                            <?php Translations::escHtmlE( 'get_started_customize_fonts' ); ?>
                        </a>
                    </div>

                <?php endif; ?>
                <div class="peregrine-customizer-option">
                    <span class="peregrine-customizer-option__icon dashicons dashicons-menu-alt3"></span>
                    <a class="peregrine-customizer-option__label"
                       target="_blank"
                       href="<?php echo esc_url( peregrine_get_setting_link( 'menu' ) ); ?>">
                        <?php Translations::escHtmlE( 'get_started_set_menu_links' ); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php



wp_print_request_filesystem_credentials_modal();
wp_print_admin_notice_templates();
