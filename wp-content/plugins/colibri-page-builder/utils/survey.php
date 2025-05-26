<?php

if (!function_exists('extendthemes_switch_theme')) {
	add_action('after_switch_theme', 'extendthemes_switch_theme', 10, 2);

	function extendthemes_switch_theme($new_name, $old_theme)
	{

		global $colibri_page_builder_supported_themes;

		if (
			in_array($old_theme->template, $colibri_page_builder_supported_themes) &&
			!in_array(get_template(), $colibri_page_builder_supported_themes)
		) {

			extendthemes_show_survey($old_theme->stylesheet);
		}
	}
}

if (!function_exists('extendthemes_show_survey')) {
	function extendthemes_show_survey($theme)
	{

		add_action('admin_print_footer_scripts', function () use ($theme) {

			add_action('wp_enqueue_scripts', function () {
				add_thickbox();
			});

?>
			<div id="colibri-survey-modal" style="display:none;">
				<div class="colibri-survey">
					<div class="header">
						<img src="https://colibriwp.com/assets/colibri-logo.svg" class="logo">
						<div class="title">
							<h1><?php _e('Quick feedback', 'colibri-page-builder'); //phpcs:ignore WordPress.Security.EscapeOutput.UnsafePrintingFunction ?></h1>
							<p><?php _e('If you have a moment, can you please give us a feedback?', 'colibri-page-builder'); //phpcs:ignore WordPress.Security.EscapeOutput.UnsafePrintingFunction ?></p>
						</div>
					</div>

					<div class="content">

						<iframe id="survey_iframe" src="" width="100%" height="100%" outline="0">

						</iframe>

					</div>

					<div class="footer">
						<a href="#" class="skip-link" onclick="colibri_survey_close()"><?php _e('Skip', 'colibri-page-builder'); //phpcs:ignore WordPress.Security.EscapeOutput.UnsafePrintingFunction ?></a>
						<button class="button button-primary" id="survey-submit-btn" onclick="colibri_survey_submit(this)"><?php _e('Submit', 'colibri-page-builder');//phpcs:ignore WordPress.Security.EscapeOutput.UnsafePrintingFunction  ?></button>
					</div>
				</div>
			</div>

			<style>
				#TB_ajaxContent.TB_modal {
					padding: 0px;
				}

				.colibri-survey {
					position: relative;
					width: 100%;
					height: 100%;
					//font-family:'Open Sans';
				}

				.colibri-survey .logo {
					float: left;
					filter: invert();
				}

				.colibri-survey .title h1 {
					color: #17252A;
					font-weight: 500;
					font-size: 24px;
					margin: 0;
				}

				#TB_ajaxContent .colibri-survey .title p {
					font-size: 14px;
					margin: 2px;
					color: #46707F;
				}

				.colibri-survey .title {
					float: left;
					margin: 10px;
				}

				.colibri-survey .header {
					padding: 30px 40px;
				}

				.colibri-survey .content {
					padding: 0px 20px;
					height: 250px;
					clear: both;
				}

				.colibri-survey .content h3 {
					font-weight: 500;

				}

				.colibri-survey .content label {
					display: block;
					margin: 5px;
				}

				.colibri-survey .content label input[type="radio"] {
					margin-right: 10px;
				}

				.colibri-survey .footer {
					bottom: 0px;
					height: 74px;
					width: 100%;
					background: #F5FAFD;
					position: absolute;
					box-sizing: border-box;
					padding: 10px;
					vertical-align: middle;
					text-align: right;
				}


				.colibri-survey .footer .skip-link {
					margin: 17px;
					display: inline-block;
				}

				.colibri-survey .footer .button {
					margin: 10px;
					padding: 3px 35px;
					background-color: #03A9F4;
					text-transform: uppercase;
					font-weight: 600;
					border: none;
				}
			</style>

			<script>
				function extendthemes_adjust_thick_box_size() {

					var TB_WIDTH = 550,
						TB_HEIGHT = 420;
					jQuery("#TB_window").css({
						marginLeft: '-' + parseInt((TB_WIDTH / 2), 10) + 'px',
						width: TB_WIDTH + 'px',
						height: TB_HEIGHT + 'px',
						top: '50%',
						marginTop: '-' + parseInt((TB_HEIGHT / 2), 10) + 'px'
					});
				}

				var survey_iframe = jQuery("#survey_iframe");
				var survey_submitted = false;

				survey_iframe.on("load", function() {

					if (survey_submitted) {
						setTimeout(function() {
							colibri_survey_close();
							survey_submitted = false;
						}, 2000);
					}

				});
				jQuery(window).on("load", function() {
					setTimeout(function() {
						tb_show('', '#TB_inline?KeepThis=true&width=550&height=420&inlineId=colibri-survey-modal&modal=true', false);
						jQuery("#TB_ajaxContent #survey_iframe").attr("src", "https://colibriwp.com/survey/exit/?theme=<?php echo urlencode($theme); ?>");

						extendthemes_adjust_thick_box_size();
						jQuery(window).resize(extendthemes_adjust_thick_box_size);
					}, 100);
				});

				function colibri_survey_close() {
					jQuery("#survey_iframe").attr("src", "");
					tb_remove();
				}

				function colibri_survey_submit(element) {
					var message = {
						'action': 'submit'
					};
					survey_iframe[0].contentWindow.postMessage(message, "*");
					survey_submitted = true;
					//tb_remove();
				}
				window.addEventListener('message', function(event) {

					if (event.data && event.data.action && event.data.action == "form_submit") {
						jQuery("#survey-submit-btn").attr("disabled", true);
					}
				});
			</script>

<?php
		}, PHP_INT_MAX);
	}
}
