<?php

namespace ExtendBuilder;

add_theme_support( 'customize-selective-refresh-widgets' );

add_shortcode( 'colibri_widget_area', '\ExtendBuilder\colibri_print_widget_area' );

add_action( 'customize_preview_init', function () {

	add_action( 'wp_footer', function () {
		$defaults = \ExtendBuilder\get_theme_data_defaults();

		$rendered = array();
		foreach ( $defaults['widget_areas'] as $key => $widget_area ) {
			$rendered["colibri-{$key}"] = true;
		}

		?>
        <script>
            _wpWidgetCustomizerPreviewSettings['renderedSidebars'] = <?php echo wp_json_encode( $rendered ); ?>;
        </script>
		<?php

	}, 21 );

} );

add_action( 'customize_register', function () {
	$defaults = \ExtendBuilder\get_theme_data_defaults();

	$widget_area_html = array(
		'before_widget' => '<div id="%1$s" class="widget %2$s">',
		'after_widget'  => '</div>',
		'before_title'  => '<h5 class="widgettitle">',
		'after_title'   => '</h5>',
	);

	foreach ( $defaults['widget_areas'] as $key => $widget_area ) {
		register_sidebar( array_merge(
			$widget_area,
			array_merge( array( 'id' => "colibri-{$key}" ), $widget_area_html )
		));
	}
}, 0 );

function colibri_preview_empty_area( $index ) {

	if ( ! is_customize_preview() ) {
		return;
	}

	global $wp_customize, $wp_registered_sidebars;
	/** @var \WP_Customize_Widgets $widgets */
	$widgets = $wp_customize->widgets;
	$widgets->start_dynamic_sidebar( $index );
	?>
    <div class="align-content-center align-items-center colibri-empty-widget-area d-flex flex-column justify-content-center">
        <p>Empty Widget Area</p>
    </div>

	<?php
	$widgets->end_dynamic_sidebar( $index );
}

function colibri_print_widget_area( $atts ) {

	if ( is_customize_preview() ) {
		global $wp_customize;
		$wp_customize->widgets->selective_refresh_init();
	}

	$atts = shortcode_atts(
		array(
			'id' => 'widget-1',
		),
		$atts
	);

	$id = "colibri-" . esc_html($atts['id']);

  	$id = \apply_filters('colibri_page_builder/widget_id', $id);

	ob_start();
	dynamic_sidebar( $id );
	$content = ob_get_clean();

	$sidebars_widgets = wp_get_sidebars_widgets();

	if ( empty( $sidebars_widgets[ $id ] ) || ! is_array( $sidebars_widgets[ $id ] ) ) {
		ob_start();
		colibri_preview_empty_area( $id );
		$content = ob_get_clean();
	}

	return $content;
}

