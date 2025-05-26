<?php

use ColibriWP\Theme\Core\Hooks;
use ColibriWP\Theme\Core\Utils;
use ColibriWP\Theme\Defaults;
use ColibriWP\Theme\Translations;

$peregrine_front_page_designs = array();
$peregrine_slug        = "colibri-wp-page-info";
$default_front_page_designs =   array(

    array(
        'name'  => __( "Modern", 'peregrine' ),
        "index" => 3,
        "meta"  => array(
            "slug"    => "modern",
            "version" => "v2"
        )
    ),

    array(
        'name'    => __( "Modern", 'peregrine' ),
        "index"   => 3,
        "display" => false,
        "meta"    => array(
            "slug"    => "modern",
            "version" => "v1"
        )
    ),

    array(
        'name'  => __( "Classic", 'peregrine' ),
        "index" => 2,
        "meta"  => array(
            "slug"    => "classic",
            "version" => "v1"
        )
    ),

    array(
        'name'  => __( "Fullscreen", 'peregrine' ),
        "index" => 1,
        "meta"  => array(
            "slug"    => "fullscreen",
            "version" => "v1"
        )
    ),
);

foreach ( $default_front_page_designs as $design ) {
    if ( Utils::pathGet( $design, 'display', true ) ) {
        if ( Utils::pathGet( $design, 'meta.slug' ) === 'modern' ) {
            $peregrine_front_page_design = $design;
            break;
        }

    }
}

$colibri_get_started = array(
    'plugin_installed_and_active' => Translations::escHtml( 'plugin_installed_and_active' ),
    'activate'                    => Translations::escHtml( 'activate' ),
    'activating'                  => __( 'Activating', 'peregrine' ),
    'install_recommended'         => isset( $_GET['install_recommended'] ) ? $_GET['install_recommended'] : ''
);

wp_localize_script( $peregrine_slug, 'colibri_get_started', $colibri_get_started );

?>
<style>
    .peregrine-admin-big-notice--container .action-buttons,
    .peregrine-admin-big-notice--container .content-holder {
        display: flex;
        align-items: center;
    }


    .peregrine-admin-big-notice--container .front-page-preview {
        max-width: 362px;
        margin-right: 40px;
    }

    .peregrine-admin-big-notice--container .front-page-preview img {
        max-width: 100%;
        border: 1px solid #ccd0d4;
    }

</style>
<div class="peregrine-admin-big-notice--container">
    <div class="content-holder">

        <div class="front-page-preview">
            <?php $peregrine_front_page_design_image = get_stylesheet_directory_uri() . "/screenshot.jpg"; ?>
            <img class="selected"
                 data-index="<?php echo esc_attr( $peregrine_front_page_design['index'] ); ?>"
                 src="<?php echo esc_url( $peregrine_front_page_design_image ); ?>"/>
        </div>
        <div class="messages-area">
            <div class="title-holder">
                <h1><?php esc_html_e( 'Would you like to install the pre-designed Peregrine homepage?',
                        'peregrine' ) ?></h1>
            </div>
            <div class="action-buttons">
                <button class="button button-primary button-hero start-with-predefined-design-button">
                    <?php esc_html_e( 'Install the Peregrine homepage', 'peregrine' ); ?>
                </button>
                <span class="or-separator">&ensp;<?php \ColibriWP\Theme\Translations::escHtmlE( 'or' ); ?>&ensp;</span>
                <button class="button-link peregrine-maybe-later dismiss">
                    <?php esc_html_e( 'Maybe Later', 'peregrine' ); ?>
                </button>
            </div>
            <div class="content-footer ">
                <div>
                    <div class="plugin-notice">
                        <span class="spinner"></span>
                        <span class="message"></span>
                    </div>
                </div>
                <div>
                    <p class="description large-text">
                        <?php esc_html_e( 'This action will also install the Colibri Page Builder plugin.',
                            'peregrine' ); ?>
                    </p>
                </div>
            </div>
        </div>

    </div>
    <?php
    $peregrine_builder_slug = Hooks::prefixed_apply_filters( 'plugin_slug', 'colibri-page-builder' );

    wp_localize_script( $peregrine_slug , 'peregrine_builder_status', array(
        "status"         => peregrine_theme()->getPluginsManager()->getPluginState( $peregrine_builder_slug ),
        "install_url"    => peregrine_theme()->getPluginsManager()->getInstallLink( $peregrine_builder_slug ),
        "activate_url"   => peregrine_theme()->getPluginsManager()->getActivationLink( $peregrine_builder_slug ),
        "slug"           => $peregrine_builder_slug,
        "view_demos_url" => add_query_arg(
            array(
                    'page'        => 'peregrine-page-info',
                'current_tab' => 'demo-import'
            ),
            admin_url( 'themes.php' )
        ),
        'peregrine_front_set_predesign_nonce' =>  wp_create_nonce( 'peregrine_front_set_predesign_nonce' ),
        'peregrine_disable_big_notice_nonce' => wp_create_nonce( 'peregrine_disable_big_notice_nonce' ),
        'colibri_plugin_install_activate_nonce' => wp_create_nonce( 'colibri_plugin_install_activate_nonce' ),
        "messages"       => array(
            "installing" => \ColibriWP\Theme\Translations::get( 'installing',
                'Colibri Page Builder' ),
            "activating" => \ColibriWP\Theme\Translations::get( 'activating',
                'Colibri Page Builder' )
        ),
    ) );
    ?>
</div>
