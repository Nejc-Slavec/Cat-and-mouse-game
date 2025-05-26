<?php

namespace ExtendBuilder;

use ColibriWP\PageBuilder\PageBuilder;

class Regenerate
{
	public static function schedule()
	{
		\update_option('colibri_page_builder_regenerate', true);
		self::init();
	}

	private static function init()
	{
		\ColibriWP\PageBuilder\LoadingScreen::add();
		\ColibriWP\PageBuilder\LoadingScreen::show('Preparing theme styles...');

		if (is_admin()) {
			add_action('admin_enqueue_scripts', 'ExtendBuilder\Regenerate::enqueueScripts');
			add_action('admin_print_footer_scripts', 'ExtendBuilder\Regenerate::printScript');
		} else {
			add_action('wp_enqueue_scripts', 'ExtendBuilder\Regenerate::enqueueScripts');
			add_action('print_footer_scripts', 'ExtendBuilder\Regenerate::printScript');
		}
	}

	public static function printSiteImportedNotice()
	{
		$theme_name = wp_get_theme()->get('Name');
		if (Regenerate::getGeneratorCallback() === "site_imported_notice") {
?>
			<div id="colibri_site_imported_notice" class="notice notice-success">
				<style>
					#colibri_site_imported_notice {
						border-left-color: #2787c1;
					}

					#colibri_site_imported_notice img {
						border-radius: 4px;
						max-width: 80px;
					}

					#colibri_site_imported_notice h2 {
						margin-top: 0px;
						margin-bottom: 4px;
						font-size: 24px;
					}

					#colibri_site_imported_notice p {
						font-size: 18px;
					}

					#colibri_site_imported_notice .colibri-display-table {
						display: table;
						width: 100%;
					}

					#colibri_site_imported_notice .colibri-display-table-cell {
						display: table-cell;
						vertical-align: middle;
						padding: 15px;
					}
				</style>
				<div class="colibri-display-table">

					<div class="colibri-display-table-cell">
						<img src="<?php echo \ColibriWP\PageBuilder\PageBuilder::instance()->rootURL(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>/assets/logo.jpg">
					</div>
					<div class="colibri-display-table-cell">
						<h2><?php echo esc_html($theme_name); ?> design has been successfully imported!</h2>
						<p>Your <?php echo esc_html($theme_name); ?> design has been successfully imported! You can take a look at your
							new design
							or
							start customizing it.</p>
					</div>
					<div class="colibri-display-table-cell">
						<a style="margin-right: 10px" href="<?php echo esc_attr(admin_url("/customize.php")); ?>" class="button button-primary button-hero">Start customizing</a>
						<a href="<?php echo esc_attr(site_url()); ?>" class="button button  button-hero">View
							site</a>
					</div>
				</div>
			</div>
		<?php
		}
	}

	public static function getGeneratorCallback()
	{
		$callback           = isset($_REQUEST['colibri_generator_callback']) ? sanitize_text_field($_REQUEST['colibri_generator_callback']) : '';
		$possible_callbacks = array('customizer', 'site_imported_notice');

		if (in_array($callback, $possible_callbacks)) {
			return $callback;
		}

		return false;
	}

	public static function checked_too_many_times()
	{
		$tries_option_name = 'colibri_page_builder_regenerate_tries_count';
		$checks            = intval(\get_option($tries_option_name, 0));
		if ($checks > 2) {
			return true;
		}

		\update_option($tries_option_name, $checks + 1);
	}

	public static function check()
	{
		if (colibri_user_can_customize()) {
			if (isset($_REQUEST['colibri_page_builder_regenerate_unset'])) {
				self::end();
			}

			if (!self::doing_ajax() && self::isRequired() && !self::checked_too_many_times()) {
				self::init();
			}
		}
	}

	public static function end()
	{
		\delete_option('colibri_page_builder_regenerate');
	}

	public static function doing_ajax()
	{
		return (defined('DOING_AJAX') && DOING_AJAX) || (isset($_REQUEST['action']) && $_REQUEST['action'] == "extend_builder");
	}

	public static function isRequired()
	{
		return \get_option('colibri_page_builder_regenerate', false);
	}

	public static function printScript()
	{

		?>
		<script>
			var _extendBuilderWPData = <?php echo json_encode((object) apply_filters(
											'extendbuilder_wp_data',
											array(
												'defaults'   => array(),
												'plugin_url' => PageBuilder::instance()->rootURL(),
											)
										)); ?>;
			var _colibriAllPartialsExport = <?php echo "'" . export_colibri_data(array("exclude_generated" => true), true) . "'"; ?>;
			document.addEventListener("DOMContentLoaded", function() {
				colibriVirtual.renderer.generate(_colibriAllPartialsExport, {}).then(function() {
					try {
						var generatorCallback = <?php echo json_encode(static::getGeneratorCallback()); ?>;
						if (generatorCallback === 'customizer') {
							setTimeout(function() {
								window.location = <?php echo json_encode(admin_url("/customize.php")); ?>;
							}, 2000);
							return;
						}

						if (generatorCallback === 'site_imported_notice') {
							document.querySelector('#colibri_site_imported_notice').classList.remove('hidden');
						}

					} catch (e) {
						console.error('error', e);
					}

					window.colibriLoadingScreen && window.colibriLoadingScreen.hide();
				}).catch(function(e) {
					window.colibriLoadingScreen && window.colibriLoadingScreen.hide();
					console.error('error', e);
				});
			});
		</script>
<?php
	}

	public static function test()
	{
		\update_option('colibri_page_builder_regenerate', true);
	}

	public static function enqueueScripts()
	{
		$ver = version();
		if (!isDev()) {
			registerBuilderAssets();
			wp_enqueue_style('colibri-regenerate-theme', builderUrl("renderer.css", "css"), array(), $ver);
			wp_enqueue_script('colibri-regenerate-theme', builderUrl("renderer.js", "js"), array(
				'h-vendor',
				'shortcode'
			), $ver, true);
		} else {
			wp_enqueue_script('colibri-regenerate-theme', devUrl("renderer.js"), array('shortcode'));
		}
	}
}

add_action('init', '\ExtendBuilder\Regenerate::check', PHP_INT_MAX);
