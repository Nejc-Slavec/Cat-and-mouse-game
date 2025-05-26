<?php

namespace ColibriWP\PageBuilder\Customizer\Panels;

use ColibriWP\PageBuilder\Customizer\Settings\ContentSetting;

class ContentPanel extends \ColibriWP\PageBuilder\Customizer\BasePanel {


	public function init() {

		$this->addSections(
			array(
				"page_layout_reorder"   => array(
					"wp_data" => array(
						"title" => __( 'Reorder and remove sections', 'colibri-page-builder' ),
						"panel" => $this->id,
					),
				),
				"page_content_section"  => array(
					"wp_data" => array(
						"title" => __( 'Add sections into page', 'colibri-page-builder' ),
						"panel" => $this->id,
					),
				),
				"page_content_settings" => array(
					"wp_data" => array(
						"panel" => $this->id,
					),
				),
			)
		);

		$this->addControls(
			array(
				"page_content_control" => array(
					"wp_data" => array(
						'section'  => 'page_content',
						"settings" => "page_content"
					)
				)
			)
		);


		$this->addSettings(
			array(
				"page_content" => array(
					"class"   => ContentSetting::class,
					"section" => "page_content_section",
					"wp_data" => array(
						"transport" => "postMessage",
						"default"   => array(),
					)
				),
			)
		);
	}

	public function render_template() {
		?>
        <li id="accordion-panel-{{ data.id }}" data-name="{{{ data.id }}}"
            class="accordion-section control-section control-panel control-panel-{{ data.type }}">
            <h3 class="accordion-section-title no-chevron" tabindex="0">
                {{ data.title }}
                <!--<span title="<?php _e( 'Add Section', 'colibri-page-builder' ) //phpcs:ignore WordPress.Security.EscapeOutput.UnsafePrintingFunction ?>" class="add-section-plus section-icon"></span>-->
                <!--<span title="<?php _e( 'Page Settings', 'colibri-page-builder' ) //phpcs:ignore WordPress.Security.EscapeOutput.UnsafePrintingFunction ?>" class="section-icon setting hidden"></span>-->
            </h3>

            <div class="sections-list-reorder">
                <div id="extend-builder-sections"></div>
            </div>
        </li>
		<?php ;
	}
}
