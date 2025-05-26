<?php

use ColibriWP\Theme\Core\Hooks;
use ColibriWP\Theme\Translations;

$peregrine_info_page_support_link = Hooks::prefixed_apply_filters( 'info_page_support_link',
    'https://colibriwp.com/#support' );

$peregrine_info_page_review_link = Hooks::prefixed_apply_filters( 'info_page_review_link',
    'https://wordpress.org/support/theme/' . get_template() . '/reviews/' );

$peregrine_info_page_docs_link = Hooks::prefixed_apply_filters( 'info_page_docs_link',
    'https://docs.colibriwp.com/' );

?>

<div class="peregrine-admin-sidebar peregrine-admin-panel">
    <?php Hooks::prefixed_do_action('info_page_sidebar_before'); ?>
    <div class="peregrine-admin-sidebar__section">
        <div class="peregrine-admin-sidebar__section__title">
            <span class="peregrine-admin-sidebar__section__icon dashicons dashicons-media-text"></span>
            <h2><?php Translations::escHtmlE( 'admin_sidebar_documentation_title' ); ?></h2>
        </div>

        <p class="peregrine-admin-sidebar__section__description">
            <?php Translations::escHtmlE( 'admin_sidebar_documentation_description' ); ?>
        </p>
        <a href="<?php echo esc_url( $peregrine_info_page_docs_link ); ?>" target="_blank"
           class="button button-primary">
            <?php Translations::escHtmlE( 'admin_sidebar_documentation_action' ); ?>
        </a>
    </div>
    <div class="peregrine-admin-sidebar__section">
        <div class="peregrine-admin-sidebar__section__title">
            <span class="peregrine-admin-sidebar__section__icon dashicons dashicons-sos"></span>
            <h2><?php Translations::escHtmlE( 'admin_sidebar_support_title' ); ?></h2>
        </div>
        <p class="peregrine-admin-sidebar__section__description">
            <?php Translations::escHtmlE( 'admin_sidebar_support_description' ); ?>
        </p>
        <a href="<?php echo esc_url( $peregrine_info_page_support_link ); ?>" target="_blank"
           class="button button-primary">
            <?php Translations::escHtmlE( 'admin_sidebar_support_action' ); ?>
        </a>
    </div>
    <?php Hooks::prefixed_do_action('info_page_sidebar_after'); ?>
</div>
