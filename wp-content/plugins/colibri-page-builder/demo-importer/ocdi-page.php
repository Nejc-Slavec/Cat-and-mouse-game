<?php
/**
 * The plugin page view - the "settings" page of the plugin.
 *
 * @package ocdi
 */

namespace ColibriWP\PageBuilder\OCDI;

use ColibriWP\PageBuilder\PageBuilder;

$predefined_themes = array();

wp_enqueue_script( 'extendthemes-ocdi-main-js', PageBuilder::instance()->assetsRootURL() . '/ocdi/main.js', array(
    'jquery',
    'jquery-ui-dialog'
),
    COLIBRI_PAGE_BUILDER_VERSION );
wp_localize_script( 'extendthemes-ocdi-main-js', 'extendthemes_ocdi',
    array(
        'ajax_url'         => admin_url( 'admin-ajax.php' ),
        'ajax_nonce'       => wp_create_nonce( 'extendthemes-ocdi-ajax-verification' ),
        'import_files'     => array(),
        'wp_customize_on'  => apply_filters( 'extendthemes-ocdi/enable_wp_customize_save_hooks', false ),
        'import_popup'     => false,
        'theme_screenshot' => wp_get_theme()->get_screenshot(),
        'texts'            => array(
            'missing_preview_image' => esc_html__( 'No preview image defined for this import.',
                'colibri-page-builder' ),
            'dialog_title'          => esc_html__( 'Are you sure?', 'colibri-page-builder' ),
            'dialog_no'             => esc_html__( 'Cancel', 'colibri-page-builder' ),
            'dialog_yes'            => esc_html__( 'Yes, import!', 'colibri-page-builder' ),
            'selected_import_title' => "",
            'not-installed'         => esc_html__( 'Not installed', 'colibri-page-builder' ),
            'installed'             => esc_html__( 'Installed', 'colibri-page-builder' ),
            'active'                => esc_html__( 'Active', 'colibri-page-builder' ),
            'installing_plugins'    => esc_html__( 'Installing Plugins', 'colibri-page-builder' ),
            'installing'            => esc_html__( 'Installing', 'colibri-page-builder' ),
            'activating'            => esc_html__( 'Activating', 'colibri-page-builder' ),
            'importing_title'       => esc_html__( 'Importing the following demo site',
                'colibri-page-builder' ),

        ),
        'plugin_state'     => intval( PageBuilder::instance()->isPRO() ),
        'dialog_options'   => apply_filters( 'extendthemes-ocdi/confirmation_dialog_options', array() ),

    )
);

/**
 * Hook for adding the custom plugin page header
 */
do_action( 'pt-ocdi/plugin_page_header' );
?>

    <div class="ocdi  wrap  about-wrap">
        <link type="text/css" rel='stylesheet'
              href="<?php echo PageBuilder::instance()->assetsRootURL() //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>/ocdi/main.css"/>
        <?php

        // Display warrning if PHP safe mode is enabled, since we wont be able to change the max_execution_time.
        if ( ini_get( 'safe_mode' ) ) {
            printf(
                esc_html__( '%sWarning: your server is using %sPHP safe mode%s. This means that you might experience server timeout errors.%s', 'pt-ocdi' ),
                '<div class="notice  notice-warning  is-dismissible"><p>',
                '<strong>',
                '</strong>',
                '</p></div>'
            );
        }


        ?>

        <div class="ocdi__intro-notice  notice  notice-warning  is-dismissible">
            <p><?php esc_html_e( 'Before you begin, make sure all the required plugins are activated.', 'pt-ocdi' ); ?></p>
        </div>

    </div>

    <div class="ocdi__file-upload-container">
        <h2><?php esc_html_e( 'Manual demo files upload', 'pt-ocdi' ); ?></h2>

        <div class="ocdi__file-upload">
            <h3>
                <label for="content-file-upload"><?php esc_html_e( 'Choose a XML file for content import:', 'pt-ocdi' ); ?></label>
            </h3>
            <input id="ocdi__content-file-upload" type="file" name="content-file-upload">
        </div>

        <div class="ocdi__file-upload">
            <h3>
                <label for="widget-file-upload"><?php esc_html_e( 'Choose a WIE or JSON file for widget import:', 'pt-ocdi' ); ?></label>
            </h3>
            <input id="ocdi__widget-file-upload" type="file" name="widget-file-upload">
        </div>

        <div class="ocdi__file-upload">
            <h3>
                <label for="customizer-file-upload"><?php esc_html_e( 'Choose a DAT file for customizer import:', 'pt-ocdi' ); ?></label>
            </h3>
            <input id="ocdi__customizer-file-upload" type="file" name="customizer-file-upload">
        </div>

    </div>

    <p class="ocdi__button-container">
        <button class="ocdi__button  button  button-hero  button-primary  js-ocdi-import-data"><?php esc_html_e( 'Import Demo Data', 'pt-ocdi' ); ?></button>
    </p>


    <p class="ocdi__ajax-loader  js-ocdi-ajax-loader">
        <span class="spinner"></span> <?php esc_html_e( 'Importing, please wait!', 'pt-ocdi' ); ?>
    </p>

    <div class="ocdi__response  js-ocdi-ajax-response"></div>


    <?php
/**
 * Hook for adding the custom admin page footer
 */
do_action( 'pt-ocdi/plugin_page_footer' );
