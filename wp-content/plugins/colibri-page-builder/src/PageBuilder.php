<?php

namespace ColibriWP\PageBuilder;

use ColibriWP\PageBuilder\Customizer\Customizer;
use ColibriWP\PageBuilder\Customizer\Template;
use ColibriWP\PageBuilder\Customizer\ThemeSupport;
use ColibriWP\PageBuilder\DemoImport\DemoImport;
use ColibriWP\PageBuilder\License\License;
use ColibriWP\PageBuilder\License\Updater;
use ColibriWP\PageBuilder\Notify\NotificationsManager;
use ColibriWP\PageBuilder\OCDI\OCDI;
use ColibriWP\PageBuilder\OCDI\OneClickDemoImport;
use function ExtendBuilder\array_get_value;
use function ExtendBuilder\colibri_user_can_customize;
use function ExtendBuilder\get_current_theme_data;

class PageBuilder
{

	private static $instance    = null;
	public         $themeName   = null;
	public         $version     = null;
	private        $_customizer = null;
	private        $theme       = null;
	private        $themeSlug   = null;
	private        $path        = null;
	private        $root        = null;


	public function __construct($root = null)
	{
		$theme           = wp_get_theme();
		$this->themeName = $theme->get('Name');
		$this->path      = $root;

		$this->version = COLIBRI_PAGE_BUILDER_VERSION;


		// check for current theme in customize.php
		if ($previewedTheme = $this->checkForThemePreviewdInCustomizer()) {
			$this->themeSlug = $previewedTheme["TextDomain"];
			$this->themeName = $previewedTheme["Name"];
		} else {
			// current theme is a child theme
			if ($this->theme->get('Template')) {
				$template          = $this->theme->get('Template');
				$templateThemeData = wp_get_theme($template);
				$this->themeSlug   = $templateThemeData->get('TextDomain');
				$this->themeName   = $templateThemeData->get('Name');
			} else {
				$this->themeSlug = $this->theme->get('TextDomain');
			}
		}

		if (file_exists($this->utilsPath("/functions.php"))) {
			require_once $this->utilsPath("/functions.php");
		}

		$self = $this;
		add_action('after_setup_theme', function () use ($self) {

			if (!$this->isCurrentThemeSupported()) {
				return;
			}

			$self->initCompanion();
		}, 5);


		$this->registerActivationHooks();
	}

	public function checkForThemePreviewdInCustomizer()
	{
		$theme                   = false;
		$is_customize_admin_page = (is_admin() && 'customize.php' == basename($_SERVER['PHP_SELF']));
		$keys                    = array(
			'customize_theme',
			'theme',
		);
		$input_vars              = array_merge(
			wp_array_slice_assoc($_GET, $keys),
			wp_array_slice_assoc($_POST, $keys)
		);

		if ($is_customize_admin_page && isset($input_vars['theme'])) {
			$theme = sanitize_text_field($input_vars['theme']);
		} elseif (isset($input_vars['customize_theme'])) {
			$theme =  sanitize_text_field($input_vars['customize_theme']);
		}

		$themeData  = wp_get_theme($theme);
		$textDomain = $themeData->get('TextDomain');
		$name       = $themeData->get('Name');

		if ($themeData->get('Template')) {
			$parentThemeData = wp_get_theme($themeData->get('Template'));
			$textDomain      = $parentThemeData->get('TextDomain');
			$name            = $parentThemeData->get('Name');
		}

		return array(
			'TextDomain' => $textDomain,
			'Name'       => $name,
		);
	}

	public function utilsPath($rel = "")
	{
		return $this->rootPath() . "/utils/" . $rel;
	}

	public function themeDataPath($rel = "")
	{

		return $this->rootPath() . "/theme-data/" . $this->themeSlug . $rel;
	}

	public function rootPath()
	{

		if (!$this->root) {
			$this->root = untrailingslashit(wp_normalize_path(dirname($this->path)));
		}

		return $this->root;
	}

	public function isCurrentThemeSupported()
	{
		return apply_filters('colibri_page_builder/theme_supported', false);
	}

	public function initCompanion()
	{

		$this->checkNotifications();

		if (apply_filters('colibri_page_builder/show_top_bar_info_button', true)) {
			$style_action = 'wp_head';
			if (is_admin()) {
				$style_action = 'admin_head';
			}

			add_action($style_action, function () {

?>
				<style>
					#wpadminbar ul li#wp-admin-bar-colibri_top_bar_menu {
						background-color: rgba(3, 169, 244, 0.3);
						padding-left: 8px;
						padding-right: 8px;
						margin: 0px 16px;
					}

					#wpadminbar ul li#wp-admin-bar-colibri_top_bar_menu>a {
						background-color: transparent;
						color: #fff;
					}


					#wpadminbar ul li#wp-admin-bar-colibri_top_bar_menu>a img {
						max-height: 24px;
						margin-top: -4px;
						margin-right: 6px;
					}

					#wpadminbar ul li#wp-admin-bar-colibri_top_bar_menu>.ab-sub-wrapper {
						margin-left: -8px;
					}

					<?php if (is_admin()) : ?>#wpadminbar ul li#wp-admin-bar-colibri_top_bar_menu>a img {
						max-height: 24px;
						margin-bottom: -6px;
						margin-top: 0;
					}

					<?php endif; ?>
				</style>
		<?php


			});

			add_action('admin_bar_menu', function ($wp_admin_bar) {


				$colibriwp_info_page = add_query_arg(
					array(
						'page' => 'colibri-wp-page-info'
					),
					admin_url('themes.php')
				);

				$icon_url = \ColibriWP\PageBuilder\PageBuilder::instance()->assetsRootURL() . "/colibri-logo.png";

				/** @var \WP_Admin_Bar $wp_admin_bar */
				$wp_admin_bar->add_menu(array(
					'id'    => 'colibri_top_bar_menu',
					'title' => '<span class=""><img src="' . $icon_url . '"></span><span>' . __('Colibri', 'colibri-page-builder') . '</span>',
					'href'  => $colibriwp_info_page,
					'meta'  => array(
						'class' => 'colibri-top-bar-menu-item',
					)
				));

				global $wp_customize;
				$current_url = (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
				if (is_customize_preview() && $wp_customize->changeset_uuid()) {
					$current_url = remove_query_arg('customize_changeset_uuid', $current_url);
				}

				$customize_url = add_query_arg('url', urlencode($current_url), wp_customize_url());
				if (is_customize_preview()) {
					$customize_url = add_query_arg(array('changeset_uuid' => $wp_customize->changeset_uuid()), $customize_url);
				}


				if (!is_admin()) {

					$post_type = \ExtendBuilder\get_post_type();
					if ($post_type !== 'page' && !is_404()) {
						$label = apply_filters('colibri_page_builder/edit_with_colibri_label', __('Edit template with Colibri', 'colibri-page-builder'), $post_type);
						$title = '<span>' . $label . '</span>';
					} else {
						$title = '<span>' . __('Edit with Colibri', 'colibri-page-builder') . '</span>';
					}

					$wp_admin_bar->add_menu(array(
						'id'     => 'colibri_top_bar_menu-edit_in_customizer',
						'title'  => $title,
						'href'   => $customize_url,
						'parent' => 'colibri_top_bar_menu',
						'meta'   => array(
							'class' => 'hide-if-no-customize',
						),
					));
				}


				$wp_admin_bar->add_menu(array(
					'id'     => 'colibri_top_bar_menu-get_started',
					'title'  => '<span>' . __('Get Started', 'colibri-page-builder') . '</span>',
					'href'   => add_query_arg('current_tab', 'get-started', $colibriwp_info_page),
					'parent' => 'colibri_top_bar_menu'
				));

				$wp_admin_bar->add_menu(array(
					'id'     => 'colibri_top_bar_menu-get_started',
					'title'  => '<span>' . __('Get Started', 'colibri-page-builder') . '</span>',
					'href'   => add_query_arg('current_tab', 'get-started', $colibriwp_info_page),
					'parent' => 'colibri_top_bar_menu'
				));

				$wp_admin_bar->add_menu(array(
					'id'     => 'colibri_top_bar_menu-demo_sites',
					'title'  => '<span>' . __('Demo Sites', 'colibri-page-builder') . '</span>',
					'href'   => add_query_arg('current_tab', 'demo-import', $colibriwp_info_page),
					'parent' => 'colibri_top_bar_menu'
				));

				if (!PageBuilder::instance()->isPRO()) {
					$wp_admin_bar->add_menu(array(
						'id'     => 'colibri_top_bar_menu-upgrade_to_pro',
						'title'  => '<span style="text-transform: uppercase;color: #ff9900;">' . __('Upgrade to PRO', 'colibri-page-builder') . '</span>',
						'href'   => add_query_arg('current_tab', 'pro-upgrade', $colibriwp_info_page),
						'parent' => 'colibri_top_bar_menu'
					));
				}
			}, 72);
		}

		$this->_customizer = new Customizer($this);


		License::load($this->rootPath());
		Updater::load($this->path);

		Template::load();
		ThemeSupport::load();
		DemoImport::load();
		OneClickDemoImport::get_instance();


		add_action('wp_ajax_create_home_page', array($this, 'createFrontPage'));

		add_action('wp_ajax_cp_open_in_customizer', array($this, 'openPageInCustomizer'));
		add_action('wp_ajax_cp_shortcode_refresh', array($this, 'shortcodeRefresh'));

		add_filter('page_row_actions', array($this, 'addEditInCustomizer'), 0, 2);

		add_action('admin_footer', array($this, 'addAdminScripts'));

		add_action('media_buttons', array($this, 'addEditInCustomizerPageButtons'));

		add_filter('is_protected_meta', array($this, 'isProtectedMeta'), 10, 3);

		add_filter("customize_changeset_save_data", array($this, 'savePostAsDraft'), -10, 2);

		add_action('wp_ajax_cp_open_in_default_editor', array($this, 'openPageInDefaultEditor'));
		add_filter('user_can_richedit', array($this, 'showRichTextEditor'));
		add_filter('gutenberg_can_edit_post_type', array($this, 'showRichTextEditor'));
		add_filter('use_block_editor_for_post', array($this, 'showRichTextEditor'));
		add_filter('wp_editor_settings', array($this, 'wpEditorSettings'));
		add_filter('the_editor', array($this, 'maintainablePageEditor'));
		do_action('colibri_page_builder/ready', $this);
        add_action('wp_head', array($this, 'addGlobalColorsCssVars'));

		ThemeHooks::prefixed_add_filter('theme_plugins', function ($plugins) {

			if ($this->isPRO() || file_exists(WP_PLUGIN_DIR . "/colibri-page-builder-pro/colibri-page-builder-pro.php")) {
				$description                         = isset($plugins['colibri-page-builder']) ? $plugins['colibri-page-builder']['description'] : '';
				$plugins['colibri-page-builder-pro'] = array(
					'name'        => 'Colibri Page Builder PRO',
					'description' => $description,
					'priority'    => 0
				);
				unset($plugins['colibri-page-builder']);
			}

			return $plugins;
		}, 20);

		$this->addMaintainableMetaToRevision();

		if (isset($_REQUEST['colibri_create_pages']) && colibri_user_can_customize()) {
			if (!get_option('colibri_manual_create_pages', false)) {
				$this->__createFrontPage();
				update_option('colibri_manual_create_pages', true);
			}
		}
	}

    public function addGlobalColorsCssVars() {
        $colors_list = get_current_theme_data('colors', array (
                '#03a9f4',
            '#f79007',
            '#00bf87',
            '#6632ff',
            '#FFFFFF',
            '#000000',
        ));
        $color_variants_list = get_current_theme_data('colorVariants', [["#a6dcf4","#54c2f4","#03a9f4","#026e9f","#01334a"],["#f7d7ac","#f7b359","#f79007","#a25e05","#4d2d02"],["#7fbfac","#40bf9a","#00bf87","#006a4b","#00150f"],["#e5dcff","#a587ff","#6632ff","#4421aa","#221155"],["#ffffff","#cccccc","#999999","#666666","#333333"],["#cccccc","#999999","#666666","#333333","#000000"]]);
        ob_start();
        ?>
            <style type="text/css">
                body {
                <?php
                    foreach ($colors_list as $index => $color) {
                        echo "--colibri-color-" . ($index + 1) . ": " . esc_attr($color). ';';
                        if(!isset( $color_variants_list[$index])) {
                            continue;
                        }
                        $variant_data = $color_variants_list[$index];
                        foreach($variant_data as $variantIndex => $variantColor) {
                           echo "--colibri-color-" . ($index + 1) . "--variant-". ($variantIndex+1).": " . esc_attr($variantColor). ';';
                        }
                    }
                ?>
                }
                </style>
        <?php
        echo ob_get_clean();
    }

	public function checkNotifications()
	{

		if ($this->isPRO()) {
			return;
		}

		$notifications = $this->utilsPath("/notifications.php");
		if (file_exists($notifications)) {
			$notifications = require_once $notifications;
		} else {
			$notifications = array();
		}

		NotificationsManager::load($notifications);
	}

	public function isPRO()
	{
		$folder_parts = explode("/", $this->rootPath());
		$folder       = array_pop($folder_parts);

		return apply_filters('colibri_page_builder/is_pro', ($folder === "colibri-page-builder-pro"));
	}

	/**
	 * @return PageBuilder
	 */
	public static function instance()
	{
		return self::$instance;
	}

	public function theme()
	{
		$theme_fct = ThemeHooks::prefix();
		if (function_exists($theme_fct)) {
			return call_user_func($theme_fct);
		}
	}

	public function addMaintainableMetaToRevision()
	{
		$keys = $this->getMaintainableMetaKeys();
		foreach ($keys as $key) {
			add_filter("_wp_post_revision_field_{$key}", array($this, 'getMetaFieldRevision'), 10, 2);
		}

		add_action('save_post', array($this, 'saveMetaFieldRevision'), 10, 2);
		add_action('wp_restore_post_revision', array($this, 'restoreMetaFieldRevision'), 10, 2);
	}

	public function getMaintainableMetaKeys()
	{
		$keys = $this->getMaintainableKeysLabelPair();

		return array_keys($keys);
	}

	public function getMaintainableKeysLabelPair($fields = array())
	{
		$fields = array_merge($fields, array(
			'is_' . $this->themeSlug . '_front_page'        => 'Is ' . $this->themeName . ' Front Page',
			'is_' . $this->themeSlug . '_maintainable_page' => 'Is ' . $this->themeName . ' FMaintainable Page',
		));

		return $fields;
	}

	public static function getPageByTitle($page_title)
	{
		$posts = get_posts(
			array(
				'post_type'              => 'page',
				'title'                  => $page_title,
				'post_status'            => 'all',
				'numberposts'            => 1,
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false,
				'orderby'                => 'post_date ID',
				'order'                  => 'ASC',
			)
		);

		if (!empty($posts)) {
			return $posts[0];
		} else {
			return null;
		}
	}

	public function __createFrontPage()
	{
		$page = $this->getFrontPage();

		update_option($this->themeSlug . '_companion_old_show_on_front', get_option('show_on_front'));
		update_option($this->themeSlug . '_companion_old_page_on_front', get_option('page_on_front'));

		if (!$page) {
			$post_id = wp_insert_post(
				array(
					'comment_status' => 'closed',
					'ping_status'    => 'closed',
					'post_name'      => $this->themeName,
					'post_title'     => 'Front Page',
					'post_status'    => 'publish',
					'post_type'      => 'page',
					'page_template'  => apply_filters(
						'colibri_page_builder/front_page_template',
						"page-templates/homepage.php"
					),
					'post_content'   => '',
				)
			);

			$page = get_post($post_id);

			update_option('show_on_front', 'page');
			update_option('page_on_front', $post_id);
			update_post_meta($post_id, 'is_' . $this->themeSlug . '_front_page', "1");

			if (null == PageBuilder::getPageByTitle('Blog')) {
				$post_id = wp_insert_post(
					array(
						'comment_status' => 'closed',
						'ping_status'    => 'closed',
						'post_name'      => 'blog',
						'post_title'     => 'Blog',
						'post_status'    => 'publish',
						'post_type'      => 'page',
					)
				);
			}

			$blog = PageBuilder::getPageByTitle('Blog');

			update_option('page_for_posts', $blog->ID);
		} else {
			update_option('show_on_front', 'page');
			update_option('page_on_front', $page->ID);
			update_post_meta($page->ID, 'is_' . $this->themeSlug . '_front_page', "1");
		}

		return $page;
	}

	public function getFrontPage()
	{
		$query = new \WP_Query(
			array(
				"post_status" => "publish",
				"post_type"   => 'page',
				"meta_key"    => 'is_' . $this->themeSlug . '_front_page',
			)
		);
		if (count($query->posts)) {
			return $query->posts[0];
		}

		return null;
	}

	public function registerActivationHooks()
	{
		$self = $this;

		register_activation_hook($this->path, function () use ($self) {
			do_action('colibri_page_builder/activated', $self);
		});

		register_deactivation_hook($this->path, function () use ($self) {
			do_action('colibri_page_builder/deactivated', $self);
		});
	}

	public function getThemeSlug($as_fn_prefix = false)
	{
		$slug = $this->themeSlug;

		if ($as_fn_prefix) {
			$slug = str_replace("-", "_", $slug);
		}

		return $slug;
	}

	public static function load($pluginFile)
	{
		$memory_limit  = @ini_get('memory_limit');
		$needed_memory = '256M';
		if ($memory_limit) {
			$current_memory_value = self::letToNum($memory_limit);
			$desired_value        = self::letToNum($needed_memory);

			if ($current_memory_value < $desired_value) {
				@ini_set('memory_limit', $needed_memory);
			}
		}
		self::$instance = new PageBuilder($pluginFile);
	}

	public static function letToNum($size)
	{
		$l   = substr($size, -1);
		$ret = substr($size, 0, -1);
		switch (strtoupper($l)) {
			case 'P':
				$ret *= 1024;
			case 'T':
				$ret *= 1024;
			case 'G':
				$ret *= 1024;
			case 'M':
				$ret *= 1024;
			case 'K':
				$ret *= 1024;
		}

		return $ret;
	}

	public static function getTreeValueAt($tree, $path, $default = null)
	{
		$result   = $tree;
		$keyParts = explode(":", $path);
		if (is_array($result)) {
			foreach ($keyParts as $part) {
				if ($result && isset($result[$part])) {
					$result = $result[$part];
				} else {
					return $default;
				}
			}
		}

		return $result;
	}

	public static function prefixedMod($mod, $prefix = null)
	{
		$prefix = $prefix ? $prefix : self::instance()->getThemeSlug();
		$prefix = str_replace("-", "_", $prefix);

		return $prefix . "_" . $mod;
	}

	public static function filterDefault($data)
	{
		if (is_array($data)) {
			$data = self::filterArrayDefaults($data);
		} else {
			$data = str_replace('[tag_companion_uri]', PageBuilder::instance()->themeDataURL(), $data);
			$data = str_replace('[tag_theme_uri]', get_template_directory_uri(), $data);

			$data = str_replace('[tag_companion_dir]', PageBuilder::instance()->themeDataPath(), $data);
			$data = str_replace('[tag_theme_dir]', get_template_directory(), $data);
			$data = str_replace('[tag_style_uri]', get_stylesheet_directory_uri(), $data);
		}

		return $data;
	}

	public static function filterArrayDefaults($data)
	{
		foreach ($data as $key => $value) {
			$data[$key] = PageBuilder::filterDefault($value);
		}

		return $data;
	}

	public function themeDataURL($rel = "")
	{
		return $this->rootURL() . "/theme-data/" . $this->themeSlug . $rel;
	}

	public function rootURL()
	{
		$templateDir = wp_normalize_path(get_stylesheet_directory());
		$path        = wp_normalize_path($this->path);
		if (strpos($path, $templateDir) === 0) {
			$path = dirname($path);
			$abs  = wp_normalize_path(ABSPATH);
			$path = str_replace($abs, '/', $path);
			$url  = get_stylesheet_directory_uri() . $path;
		} else {
			$url = plugin_dir_url($this->path);
		}

		return untrailingslashit($url);
	}

	public static function dataURL($path = '')
	{
		return self::instance()->themeDataURL($path);
	}

	public static function loadJSONFile($path)
	{
		PageBuilder::instance()->loadJSON($path);
	}

	public function loadJSON($path)
	{

		if (!file_exists($path)) {
			return array();
		}

		$content = file_get_contents($path);

		return json_decode($content, true);
	}

	public function getVersion()
	{
		if ($this->version === "@@buildnumber@@") {
			return time();
		}

		return $this->version;
	}

	public function savePostAsDraft($data, $filter_context)
	{

		// TODO: change this to use the current theme slug not mesmerize-pro
		$status = $filter_context['status'];

		if ($status == "draft" && isset($data["mesmerize-pro::page_content"])) {
			$page_id = isset($_POST['customize_post_id']) ? intval($_POST['customize_post_id']) : -1;

			$encode        = false;
			$pages_content = $data["mesmerize-pro::page_content"];
			$pages_content = $pages_content["value"];
			if (is_string($pages_content)) {
				$pages_content = json_decode($pages_content, true);
				$encode        = true;
			}

			$page_content = $pages_content[$page_id];
			$page_content = preg_replace('/<!--@@CPPAGEID\[(.*)\]@@-->/s', '', $page_content);

			if ($page_id != -1) {

				$post = get_post($page_id);

				wp_create_post_autosave(array(
					'post_ID'      => $page_id,
					'post_content' => $page_content,
					'post_title'   => $post->post_title,
					'post_type'    => $post->post_type
				));
			}
		}

		return $data;
	}

	public function loadMaintainablePageAssets($post, $template)
	{
		do_action('colibri_page_builder/template/load_assets', $this, $post, $template);
	}

	public function getMetaFieldRevision($value, $field)
	{
		global $revision;

		return get_metadata('post', $revision->ID, $field, true);
	}

	public function saveMetaFieldRevision($post_id, $post)
	{
		if ($parent_id = wp_is_post_revision($post_id)) {

			$parent = get_post($parent_id);

			$keys = $this->getRevisionsMetaKeys();
			foreach ($keys as $key) {
				$meta_value = get_post_meta($parent->ID, $key, true);
				if ($meta_value) {
					add_metadata('post', $post_id, $key, $meta_value);
				}
			}
		}
	}

	public function getRevisionsMetaKeys()
	{
		$maintainable_keys = $this->getMaintainableMetaKeys();


		$keys = array_merge($maintainable_keys, array(
			'extend_builder'
		));

		return $keys;
	}

	public function restoreMetaFieldRevision($post_id, $revision_id)
	{
		if ($parent_id = wp_is_post_revision($revision_id)) {

			$keys = $this->getMaintainableMetaKeys();
			foreach ($keys as $key) {
				$meta_value = get_metadata('post', $revision_id, $key, true);

				if ($meta_value) {
					update_post_meta($post_id, $key, $meta_value);
				} else {
					delete_post_meta($post_id, $key);
				}
			}
		}
	}

	public function fileExists($path)
	{
		$path = wp_normalize_path($this->rootPath() . "/$path");

		return file_exists($path);
	}

	public function loadFile($path, $once = false)
	{
		$path = wp_normalize_path($this->rootPath() . "/$path");

		if (file_exists($path)) {
			if ($once) {
				require_once $path;
			} else {
				require $path;
			}
		}
	}

	public function openPageInDefaultEditor()
	{
		check_ajax_referer('cp_open_in_default_editor_nonce');
		$post_id = is_numeric($_REQUEST['page']) ? intval($_REQUEST['page']) : null;

		$post = get_post($post_id);

		if ($post) {
			update_post_meta($post_id, $this->getThemeSlug() . "-show-default-editor", "1");
		}
		exit;
	}

	public function replaceEditor($value = false)
	{
		return !$this->showRichTextEditor($value);
	}

	public function showRichTextEditor($value = true)
	{
		global $post;

		if (!$this->canShowDefaultEditor()) {
			$value = false;
		}

		return $value;
	}

	public function canShowDefaultEditor($post_id = false)
	{
		if (!$post_id) {
			global $post;
			$post_id = ($post && property_exists($post, "ID")) ? $post->ID : false;
		}

		if (!$post_id) {
			return true;
		}

		if ($this->isMaintainable($post_id)) {

			if (isset($_REQUEST['cp_default_editor'])) {
				return true;
			}

			$option = get_post_meta($post_id, $this->getThemeSlug() . "-show-default-editor", true);
			if ($option === "1") {
				return true;
			} else {
				return false;
			}
		}

		return true;
	}

	public function isMaintainable($post_id = false)
	{

		if (!$post_id) {
			global $post;
			$post_id = ($post && property_exists($post, "ID")) ? $post->ID : false;
		}

		$meta = get_post_meta($post_id, 'extend_builder', true);


		if (!\is_customize_preview()) {
			if (empty($meta)) {
				return false;
			}
		}

		return true;
	}

	public function wpEditorSettings($settings)
	{
		if (!$this->canShowDefaultEditor()) {
			$settings['quicktags'] = false;
		}

		return $settings;
	}

	public function maintainablePageEditor($editor)
	{
		global $pagenow;
		if (!$this->canShowDefaultEditor() && ($pagenow === "post.php" || $pagenow === "post-new.php")) {
			$editor_id_attr = "content";
			if (strpos($editor, 'wp-' . $editor_id_attr . '-editor-container') !== false) {
				ob_start();
				require $this->utilsPath("editor-overlay.php");
				$content = ob_get_clean();
				$content = str_replace("%", "%%", $content);
				$editor  .= $content;
			}
		}

		return $editor;
	}

	public function isProtectedMeta($protected, $meta_key, $meta_type)
	{
		$is_protected = array(
			'is_' . $this->themeSlug . '_front_page',
			'is_' . $this->themeSlug . '_maintainable_page',
			$this->themeSlug . '-show-default-editor',
		);
		if (in_array($meta_key, $is_protected)) {
			return true;
		}

		return $protected;
	}

	public function getCurrentPageId()
	{
		global $post;
		$post_id = ($post && property_exists($post, "ID")) ? $post->ID : false;

		if (!$post_id) {
			return false;
		}

		return $post_id;
	}

	public function assetsRootPath()
	{
		return $this->rootPath() . "/assets";
	}

	/**
	 * @return Customizer
	 */
	public function customizer()
	{
		return $this->_customizer;
	}

	public function createFrontPage()
	{
		$nonce = isset($_POST['create_home_page_nounce']) ? sanitize_text_field($_POST['create_home_page_nounce']) : '';

		if (!wp_verify_nonce($nonce, 'create_home_page_nounce')) {
			die();
		}

		$this->__createFrontPage();
	}

	public function restoreFrontPage()
	{
		if ($this->getFrontPage()) {
			update_option('show_on_front', get_option($this->themeSlug . '_companion_old_show_on_front'));
			update_option('page_on_front', get_option($this->themeSlug . '_companion_old_page_on_front'));
		}
	}

	public function addEditInCustomizer($actions, $post)
	{
		if ($this->canEditInCustomizer($post)) {

			$actions = array_merge(
				array(
					"cp_page_builder" => '<a href="javascript:void();" onclick="cp_open_page_in_customizer(' . $post->ID . ')" >Edit in Colibri</a>',
				),
				$actions
			);
		}

		return $actions;
	}

	public function canEditInCustomizer($post = null)
	{
		$canEdit = false;

		if (!$post) {
			global $post;
		}

		if (is_numeric($post)) {
			$post = get_post($post);
		}

		$editablePostTypes = apply_filters('colibri_page_builder/editable_post_types', array("page"));
		if (!$post || !in_array($post->post_type, $editablePostTypes)) {
			return false;
		} else {
			if ($this->isWCPage($post)) {
				return false;
			} else {
				//                if ($this->isMaintainable($post->ID) || $this->isFrontPage($post->ID)) {
				$canEdit = true;
				//                }
			}
		}

		return apply_filters('colibri_page_builder/companion/can_edit_in_customizer', $canEdit, $post);
	}

	public function isWCPage($post)
	{
		if (function_exists('wc_get_page_id')) {
			$shopId      = wc_get_page_id('shop');
			$cartId      = wc_get_page_id('cart');
			$checkoutId  = wc_get_page_id('checkout');
			$myaccountId = wc_get_page_id('myaccount');

			switch ($post->ID) {
				case $shopId:
				case $cartId:
				case $checkoutId:
				case $myaccountId:
					return true;
					break;
				default:
					return false;
			}
		} else {
			return false;
		}
	}

	public function isFrontPage($post_id = false)
	{

		if (!$post_id) {
			global $post;
			$post_id = ($post && property_exists($post, "ID")) ? $post->ID : false;
		}

		if (!$post_id) {
			return false;
		}

		$isFrontPage = '1' === get_post_meta($post_id, 'is_' . $this->themeSlug . '_front_page', true);

		$isWPFrontPage = is_front_page() && !is_home();

		if ($isWPFrontPage && !$isFrontPage && $this->isMaintainable($post_id)) {
			update_post_meta($post_id, 'is_' . $this->themeSlug . '_front_page', '1');
			delete_post_meta($post_id, 'is_' . $this->themeSlug . '_maintainable_page');
			$isFrontPage = true;
		}

		$isFrontPage = $isFrontPage || $this->applyOnPrimaryLanguage($post_id, array($this, 'isFrontPage'));

		return $isFrontPage;
	}

	private function applyOnPrimaryLanguage($post_id, $callback)
	{
		$result = false;
		global $post;


		if (function_exists('pll_get_post') && function_exists('pll_default_language')) {
			$slug      = pll_default_language('slug');
			$defaultID = pll_get_post($post_id, $slug);
			$sourceID  = isset($_REQUEST['from_post']) ? absint($_REQUEST['from_post']) : null;
			$defaultID = $defaultID ? $defaultID : $sourceID;

			if (!is_numeric($defaultID)) {
				return;
			}


			if ($defaultID && ($defaultID !== $post_id)) {
				$result = call_user_func($callback, $defaultID);
			}
		}

		global $sitepress;
		if ($sitepress) {
			$defaultLanguage = $sitepress->get_default_language();
			global $wpdb;

			$sourceTRID = isset($_REQUEST['trid']) ? absint($_REQUEST['trid']) : null;
			$trid       = $sitepress->get_element_trid($post_id);
			$trid       = $trid ? $trid : $sourceTRID;

			if (!is_numeric($trid)) {
				return;
			}

			$defaultID = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE trid=%d AND language_code=%s",
					$trid,
					$defaultLanguage
				)
			);


			if ($defaultID && ($defaultID !== $post_id)) {
				$result = call_user_func($callback, $defaultID);
			}
		}

		return $result;
	}

	public function addEditInCustomizerPageButtons()
	{
		global $post;

		if ($this->canEditInCustomizer($post)) {
            //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<a href="javascript:void();"  onclick="cp_open_page_in_customizer(' . $post->ID . ')"  class="button button-primary">' . __(
				'Edit in Colibri',
				'colibri-page-builder'
			) . '</a>';
		}
	}

	public function addAdminScripts()
	{


		if (!$this->canEditInCustomizer()) {
			return;
		}

		?>

		<style type="text/css">
			a:not(.button)[onclick*="cp_open_page_"] {
				background-color: #0073aa;
				color: #ffffff;
				padding: 0.2em 0.8em;
				line-height: 150%;
				border-radius: 4px;
				display: inline-block;
				text-transform: uppercase;
				font-size: 0.82em;
			}

			a:not(.button)[onclick*="cp_open_page_"]:hover {
				background-color: #0081bd;
			}
		</style>
		<?php


		global $post;


		if (!$post) {
			return;
		}

		if ($this->isMultipage()) {

			$title_placeholder = apply_filters(
				'enter_title_here',
				__('Enter title here', 'colibri-page-builder'),
				$post
			);
		?>
			<style>
				input[name=new-page-name-val] {
					padding: 3px 8px;
					font-size: 1.7em;
					line-height: 100%;
					height: 1.7em;
					width: 100%;
					outline: none;
					margin: 0 0 3px;
					background-color: #fff;
					border-style: solid;
					border-color: #c3c3c3;
					border-width: 1px;
					margin-bottom: 10px;
					margin-top: 10px;
				}

				input[name=new-page-name-val].error {
					border-color: #f39e9e;
					border-style: solid;
					color: #f39e9e;
				}

				h1.cp-open-in-custmizer {
					font-size: 23px;
					font-weight: 400;
					margin: 0;
					padding: 9px 0 4px 0;
					line-height: 29px;
				}
			</style>
			<div style="display: none;" id="open_page_in_customizer_set_name">
				<h1 class="cp-open-in-custmizer"><?php _e('Set a name for the new page', 'colibri-page-builder'); //phpcs:ignore WordPress.Security.EscapeOutput.UnsafePrintingFunction ?></h1>
				<input placeholder="<?php echo esc_attr($title_placeholder); ?>" class="" name="new-page-name-val" />
				<button class="button button-primary" name="new-page-name-save"> <?php _e('Set Page Name', 'colibri-page-builder'); //phpcs:ignore WordPress.Security.EscapeOutput.UnsafePrintingFunction ?></button>
			</div>

			<script type="text/html" id="colibri-page-builder-guttenberg">
				<a href="javascript:void();" onclick="cp_open_page_in_customizer(<?php echo wp_json_encode($post->ID); ?>)" onauxclick="cp_open_page_in_customizer('<?php echo wp_json_encode($post->ID); ?>')" class="button edit-in-customizer-colibri button-primary button-hero" style="height:40px; line-height: 40px">
					<img style="height: 25px;display: inline-block;vertical-align: middle;margin-top: -3px;margin-right: 0px;" src="<?php echo esc_attr($this->assetsRootURL() . "/colibri.png"); ?>">
					<span style=" text-shadow: none; font-size: 14px;"><?php _e('Edit in Colibri', 'colibri-page-builder'); //phpcs:ignore WordPress.Security.EscapeOutput.UnsafePrintingFunction ?></span>
				</a>
			</script>

			<script>
				jQuery(function() {


					if (!wp.data || !wp.data.subscribe) {
						return;
					}

					wp.data.subscribe(function() {
						if (window.editInCustomizerTimeout) {
							clearTimeout(editInCustomizerTimeout);
						}
						window.editInCustomizerTimeout = setTimeout(function() {
							try {
								var toolbar = document.querySelector('#editor .edit-post-header-toolbar')
								if (!toolbar) {
									return;
								}
								if (toolbar.querySelector('.edit-in-customizer-colibri')) {
									return;
								}
								var content = jQuery(
									'#colibri-page-builder-guttenberg').html();
								if (typeof content === 'string') {
									content = content.trim();
									content = jQuery.parseHTML(content);
									content = content[0];
								}
								if (content) {
									toolbar.append(content);
								}
							} catch (e) {
								console.error(e);
							}
						}, 100)
					});
				})

				function cp_open_page_in_customizer(page) {

					var isAutodraft = jQuery('[name="original_post_status"]').length ? jQuery(
						'[name="original_post_status"]').val() === "auto-draft" : false;

					function doAjaxCall(pageName) {
						var data = {
							action: 'cp_open_in_customizer',
							page: page,
                            _wpnonce: '<?php echo wp_create_nonce('cp_open_in_customizer_nonce'); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped?>'
						};

						if (pageName) {
							data['page_name'] = pageName;
						}

						jQuery.post(ajaxurl, data).done(function(response) {
							window.location = response.trim();
						});
					}

					function gutenbergRedirectWhenSave() {
						setTimeout(function() {
							if (wp.data.select('core/editor').isSavingPost()) {
								gutenbergRedirectWhenSave();
							} else {
								doAjaxCall();
							}
						}, 300);
					}

					if (isAutodraft) {

						if (wp.data) {
							var documentTitle = wp.data.select('core/editor').getEditedPostAttribute('title');
							if (!documentTitle) {
								wp.data.dispatch('core/editor').editPost({
									title: 'Colibri #' + jQuery('#post_ID').val()
								});
							}

							wp.data.dispatch('core/editor').savePost();
							gutenbergRedirectWhenSave();
							return;
						} else {

							alert("<?php echo __('Page needs to be published before editing it in customizer', 'colibri-page-builder'); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>");
							return;

							var title = jQuery('[name="post_title"]').val();
							tb_show('Set Page Name',
								'#TB_inline?inlineId=open_page_in_customizer_set_name&height=150',
								false);
							var TB_Window = jQuery('#TB_window').height('auto');

							var titleInput = TB_Window.find('[name="new-page-name-val"]');

							titleInput.val(title).on('keypress', function() {
								jQuery(this).removeClass('error');
							});

							TB_Window.find('[name="new-page-name-save"]').off('click').on('click', function() {
								var newTitle = titleInput.val().trim();
								if (newTitle.length == 0) {
									titleInput.addClass('error');
									return;
								} else {
									doAjaxCall(newTitle);
								}
							});
						}
					} else {
						doAjaxCall();
					}

				}
			</script>
		<?php

		}
	}

	public function isMultipage()
	{
		return true;
	}

	public function assetsRootURL()
	{
		return $this->rootURL() . "/assets";
	}

	public function openPageInCustomizer()
	{
		check_ajax_referer('cp_open_in_customizer_nonce');
		$post_id = is_numeric($_REQUEST['page']) ? intval($_REQUEST['page']) : null;

		$post = get_post($post_id);

		if ($post) {
		}

		delete_post_meta($post_id, $this->getThemeSlug() . "-show-default-editor");

		$url = $this->get_page_link($post_id);

		$customize_url = add_query_arg('url', urlencode($url), wp_customize_url());
		?>
		<?php echo esc_url($customize_url); ?>
<?php

		exit;
	}

	public function get_page_link($post_id)
	{
		global $sitepress;
		$url = false;
		if ($sitepress) {
			$url           = get_page_link($post_id);
			$args          = array('element_id' => $post_id, 'element_type' => 'page');
			$language_code = apply_filters('wpml_element_language_code', null, $args);
			$url           = apply_filters('wpml_permalink', $url, $language_code);
		}

		if (!$url) {
			$url = get_page_link($post_id);
		}

		return $url;
	}

	private function sanitizeShortcodeQuery($query)
	{

		if (!is_array($query)) {
			return array();
		}

		$query_args = array(
			'p'         => null,
			'page_id'   => null,
			'name'      => null,
			'pagename'  => null,
			'title'     => null,
			'post_type' => null,
		);
		$keys       = array('p', 'page_id', 'name', 'pagename', 'title', 'post_type');

		// pick only the needed variabiles in the query
		foreach ($query as $key => $value) {
			if (in_array($key, $keys)) {
				$query_args[$key] = $value;
			}
		}

		$post_types = get_post_types();

		// sanitize paramaters
		$query_args['p']        = is_scalar($query_args['p']) ? absint($query_args['page_id']) : null;
		$query_args['page_id']  = is_scalar($query_args['page_id']) ? absint($query_args['page_id']) : null;
		$query_args['pagename'] = is_string($query_args['pagename']) ? sanitize_title($query_args['pagename']) : null;
		$query_args['name']     = is_string($query_args['name']) ? sanitize_title($query_args['name']) : null;
		$query_args['title']    = is_string($query_args['title']) ? sanitize_title($query_args['title']) : null;
		// check if the post_type is an actually registered post type
		$query_args['post_type'] = isset($post_types[$query_args['post_type']]) ? $query_args['post_type'] : null;

		// cleanup null values
		foreach ($query_args as $key => $value) {
			if ($value === null) {
				unset($query_args[$key]);
			}
		}

		return $query_args;
	}



	public function shortcodeRefresh()
	{

		check_ajax_referer( 'cp_shortcode_refresh_nonce' );
		if (!is_user_logged_in() || !current_user_can('edit_theme_options')) {
			die();
		}

		add_filter('mesmerize_is_shortcode_refresh', '__return_true');

		$shortcode = isset($_REQUEST['shortcode']) ? $_REQUEST['shortcode'] : false;
		$context   = isset($_REQUEST['context']) && is_array($_REQUEST['context']) ? $_REQUEST['context'] : array();

		if (!$shortcode) {
			die();
		}

		$shortcode = urldecode(base64_decode($shortcode));
		$shortcode = wp_kses_post($shortcode);

		$query      = isset($context['query']) ? $this->sanitizeShortcodeQuery($context['query']) : array();
		$main_query = isset($context['main_query']) ? $this->sanitizeShortcodeQuery($context['main_query']) : array();
		$post_type  = array_get_value(
			$main_query,
			'post_type',
			array_get_value(
				$query,
				'post_type',
				'post'
			)
		);

		global $wp_query, $wp;
		$wp_query = new \WP_Query($query);

		foreach ($main_query as $query_var_key => $query_var_value) {
			$wp->set_query_var($query_var_key, $query_var_value);

			if (!isset($_GET[$query_var_key])) {
				$_GET[$query_var_key] = $query_var_value;
			}
		}
		if ($post_type === "product" && function_exists("\WC")) {
			global $wp_the_query;
			$wp_the_query = $wp_query;
			WC()->query   = new \WC_Query();
			WC()->query->pre_get_posts($wp_query);
		}
		global $wp_embed;
		do_action('colibri_page_builder/customizer/before_render_shortcode', $shortcode);
		$post_embed = $wp_embed->run_shortcode($shortcode);
		$shortcode  = do_shortcode($post_embed);
		echo $wp_embed->autoembed($shortcode);
		do_action('colibri_page_builder/customizer/after_render_shortcode', $shortcode);
		die();
	}

	public function addGoogleFonts()
	{
		$self = $this;

		/**
		 * Add preconnect for Google Fonts.
		 */
		add_filter('wp_resource_hints', function ($urls, $relation_type) use ($self) {
			if (wp_style_is($self->getThemeSlug() . '-fonts', 'queue') && 'preconnect' === $relation_type) {
				$urls[] = array(
					'href' => 'https://fonts.gstatic.com',
					'crossorigin',
				);
			}

			return $urls;
		}, 10, 2);
	}
}
