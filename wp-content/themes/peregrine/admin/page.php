<?php


use ColibriWP\Theme\Core\Hooks;
use ColibriWP\Theme\Translations;
use ColibriWP\Theme\View;

$peregrine_tabs            = View::getData( 'tabs', array() );
$peregrine_current_tab     = View::getData( 'current_tab', null );
$peregrine_url             = View::getData( 'page_url', null );
$peregrine_welcome_message = View::getData( 'welcome_message', null );
$peregrine_tab_partial     = View::getData( "tabs.{$peregrine_current_tab}.tab_partial", null );
Hooks::prefixed_do_action( "before_info_page_tab_{$peregrine_current_tab}" );
$peregrine_slug        = "colibri-wp-page-info";
$colibri_get_started = array(
    'plugin_installed_and_active' => Translations::escHtml( 'plugin_installed_and_active' ),
    'activate'                    => Translations::escHtml( 'activate' ),
    'activating'                  => __( 'Activating', 'peregrine' ),
    'install_recommended'         => isset( $_GET['install_recommended'] ) ? $_GET['install_recommended'] : ''
);

wp_localize_script( $peregrine_slug, 'colibri_get_started', $colibri_get_started );
?>
<div class="peregrine-admin-page wrap about-wrap full-width-layout mesmerize-page">

    <div class="peregrine-admin-page--hero">
        <div class="peregrine-admin-page--hero-intro peregrine-admin-page-spacing ">
            <div class="peregrine-admin-page--hero-logo">
                <img src="<?php echo esc_url( peregrine_theme()->getAssetsManager()->getBaseURL() . "/images/colibriwp-logo.png" ) ?>"
                     alt="logo"/>
            </div>
            <div class="peregrine-admin-page--hero-text ">
                <?php if ( $peregrine_welcome_message ): ?>
                    <h1><?php echo esc_html( $peregrine_welcome_message ); ?></h1>
                <?php endif; ?>
            </div>
        </div>
        <?php if ( count( $peregrine_tabs ) ): ?>
            <nav class="nav-tab-wrapper wp-clearfix">
                <?php foreach ( $peregrine_tabs as $peregrine_tab_id => $peregrine_tab ) : ?>
                    <a class="nav-tab <?php echo ( $peregrine_current_tab === $peregrine_tab_id ) ? 'nav-tab-active' : '' ?>"
                       href="<?php echo esc_url( add_query_arg( array( 'current_tab' => $peregrine_tab_id ),
                           $peregrine_url ) ); ?>">
                        <?php echo esc_html( $peregrine_tab['title'] ); ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>
    </div>
    <?php if ( $peregrine_tab_partial ): ?>
        <div class="peregrine-admin-page--body peregrine-admin-page-spacing">
            <div class="peregrine-admin-page--content">
                <div class="peregrine-admin-page--tab">
                    <div class="peregrine-admin-page--tab-<?php echo esc_attr( $peregrine_current_tab ); ?>">
                        <?php View::make( $peregrine_tab_partial,
                            Hooks::prefixed_apply_filters( "info_page_data_tab_{$peregrine_current_tab}",
                                array() ) ); ?>
                    </div>
                </div>

            </div>
            <div class="peregrine-admin-page--sidebar">
                <?php View::make( 'admin/sidebar' ) ?>
            </div>
        </div>
    <?php endif; ?>
</div>


