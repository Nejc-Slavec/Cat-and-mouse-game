<?php
/*
 * Template Name: Front Page Template
 */
get_header();
?>
<?php peregrine_theme()->get( 'front-page-content' )->render(); ?>

<?php get_footer();
