<?php


namespace ColibriWP\PageBuilder\DemoImport\Hooks;

use WC_Install;
use WP_Post;
use function ExtendBuilder\array_get_value;
use function ExtendBuilder\custom_post_type_wp_name;
use function ExtendBuilder\post_types;

class ImportContentHook extends ImportHook
{

	private $colibri_post_types = null;

	function transientKey()
	{
		return 'content';
	}

	public function afterImportPriority()
	{
		return 0;
	}

	public function run()
	{
		add_action('extendthemes-ocdi/before_content_import', array($this, 'beforeImport'));
		add_filter('wxr_importer.pre_process.post', array($this, 'setMappingTransients'), 10, 4);
		add_filter('wxr_importer.pre_process.term', array($this, 'setTermMappingTransients'), 10, 2);
		add_filter('wp_import_post_data_processed', array($this, 'slashJSONPostContent'));
	}


	public function slashJSONPostContent($data)
	{


		if ($data['post_content'] && strpos($data['post_type'], custom_post_type_wp_name('json')) !== false) {
			$data['post_content'] = wp_slash($data['post_content']);
		}

		return $data;
	}

	public function beforeImport($data)
	{

		$this->preProcessColibriPosts();
		$this->preProcessPagesAndMenuItems();
		$this->preProcessMenus($data);
	}

	private function preProcessColibriPosts()
	{
		$colibri_post_types = $this->getColibriPostTypes();
		$colibri_posts      = get_posts(
			array(
				'posts_per_page' => -1,
				'post_type'      => $colibri_post_types,
				'post_status'    => array(
					'publish',
					'pending',
					'draft',
					'auto-draft',
					'future',
					'private',
					'inherit',
					'trash'
				),
			)
		);

		foreach ($colibri_posts as $colibri_post) {
			wp_delete_post($colibri_post->ID);
		}
	}

	private function getColibriPostTypes()
	{
		if (!$this->colibri_post_types) {
			$colibri_post_types = post_types();

			$colibri_post_types[] = "sidebar";

			$this->colibri_post_types = array_map(function ($value) {
				return "extb_post_{$value}";
			}, $colibri_post_types);
		}

		return $this->colibri_post_types;
	}

	private function preProcessPagesAndMenuItems()
	{
		global $wpdb;

		$wp_post_types = array('page', 'nav_menu_item');

		$wp_posts = get_posts(
			array(
				'posts_per_page' => -1,
				'post_type'      => $wp_post_types,
				'post_status'    => array(
					'publish',
					'pending',
					'draft',
					'auto-draft',
					'future',
					'private',
					'inherit',
					'trash'
				),
			)
		);

		$guids_to_update = array();
		foreach ($wp_posts as $item) {
			/** @var WP_Post $item */
			$newGuid   = (strpos($item->guid, '-old') !== false) ? $item->guid : $item->guid . "-old";
			$post_type = $item->post_type;

			switch ($post_type) {
				case "page":
					$newTitle = (strpos(
						$item->post_title,
						'[OLD]'
					) !== false) ? $item->post_title : $item->post_title . " [OLD]";
					$newName  = (strpos(
						$item->post_name,
						'-before-import'
					) !== false) ? $item->post_name : sanitize_title($item->post_name . "-before-import");
					wp_update_post(array(
						'ID'         => $item->ID,
						'post_name'  => $newName,
						'post_title' => $newTitle,
					));

					wp_trash_post($item->ID);

					if ($newGuid !== $item->guid) {
						$guids_to_update[$item->ID] = $newGuid;
					}
					break;
				case 'nav_menu_item':
					if ($newGuid !== $item->guid) {
						$guids_to_update[$item->ID] = $newGuid;
					}

					break;
			}
		}

		foreach ($guids_to_update as $id => $guid) {
			$query = $wpdb->prepare("UPDATE  {$wpdb->prefix}posts SET guid=%s WHERE ID=%d", $guid, $id);
			$wpdb->query($query);
		}
	}

	private function preProcessMenus($data)
	{
		$menus = get_theme_mod('nav_menu_locations', array());

		foreach ($menus as $location => $term_id) {
			$term = get_term_by('id', $term_id, 'nav_menu');
			wp_update_term($term->term_id, $term->taxonomy, array(
				'name' => $term->name . ' [OLD]',
				'slug' => $term->slug . "_" . time(),
			));
		}
	}

	public function afterImport($data)
	{
		$this->postProcessMapping($data);
		$this->postProcessContent();
		$this->postProcessMenuItems($data);
		$this->postProcessJSONTransient();
		$this->wooCommerceUpdate();
	}


	private function postProcessMapping($data)
	{
		global $wpdb;
		$colibri_posts_transient = $this->getTransient('colibri_posts', array());
		$colibri_posts_map       = $this->processBatch($colibri_posts_transient);
		$this->setGlobalTransient('colibri_posts_map', $colibri_posts_map);


		$pages_transient = $this->getTransient('pages', array());
		$pages_map       = $this->processBatch($pages_transient);
		$this->setGlobalTransient('pages_map', $pages_map);
	}

	private function processBatch($batch)
	{
		global $wpdb;
		$self  = $this;
		$guids = array_map(function ($guid) use ($wpdb, $self) {
			$wpdb->escape_by_ref($guid);

			$guid_escaped = $self->escapeGUID($guid);
			$wpdb->escape_by_ref($guid_escaped);

			$guid_escaped_2 = $self->escapeGUID2($guid);
			$wpdb->escape_by_ref($guid_escaped_2);

			return "'{$guid}' , '$guid_escaped', '$guid_escaped_2'";
		}, array_keys($batch));
		$query = "SELECT ID AS id, guid FROM $wpdb->posts WHERE guid IN (" . implode(",", $guids) . ")";

		$entities = $wpdb->get_results($query);

		$map = array();

		foreach ($entities as $colibri_post) {
			$post_guid = html_entity_decode($colibri_post->guid);
			$old_id    = null;
			if (isset($batch[$post_guid])) {
				$old_id = $batch[$post_guid];
			} else {
				$post_guid = $this->escapeGUID($post_guid);
				if (isset($batch[$post_guid])) {
					$old_id = $batch[$post_guid];
				}
			}

			if ($old_id) {
				$map[intval($old_id)] = intval($colibri_post->id);
			}
		}

		return $map;
	}

	public function escapeGUID($guid)
	{
		$guid_escaped = str_replace("&", "&#038;", $guid);
		return $guid_escaped;
	}

	public function escapeGUID2($guid)
	{
		$guid_escaped = str_replace("&", "&amp;", $guid);
		return $guid_escaped;
	}

	private function postProcessContent()
	{
		$pages_map          = $this->getGlobalTransient('pages_map', array());
		$old_page_on_front  = get_option('page_on_front');
		$old_page_for_posts = get_option('page_for_posts');

		if (isset($pages_map[$old_page_on_front])) {
			update_option('page_on_front', $pages_map[$old_page_on_front]);
		}


		if (isset($pages_map[$old_page_for_posts])) {
			update_option('page_for_posts', $pages_map[$old_page_for_posts]);
		}
	}

	private function postProcessMenuItems($data)
	{
		$menus       = $this->getTransient('menus', array());
		$cachedTerms = array();
		$map         = array();

		$nav_menu_locations = get_theme_mod('nav_menu_locations');

		foreach ($menus as $slug => $id) {

			if (!isset($cachedTerms[$slug])) {
				$cachedTerms[$slug] = get_term_by('slug', $slug, 'nav_menu');
			}
			$term = $cachedTerms[$slug];

			if ($term) {
				$map[intval($id)] = intval($term->term_id);
			}
		}

		if (count($map)) {

			foreach ($nav_menu_locations as $key => $value) {
				if (isset($map[$value])) {
					$nav_menu_locations[$key] = $map[$value];
				}
			}

			set_theme_mod('nav_menu_locations', $nav_menu_locations);
		}

		$source_url_base = untrailingslashit($this->getGlobalTransient('source_data.siteurl'));

		$blog_url = untrailingslashit(home_url());

		foreach ($cachedTerms as $term) {
			$menuItems = wp_get_nav_menu_items($term->term_id);

			/** @var WP_Post $menuItem */
			foreach ($menuItems as $menuItem) {
				if ($menuItem->type === 'custom' && strpos($menuItem->url, $source_url_base) === 0) {
					$classes = '';
					if (isset($menuItem->classes) && !empty($menuItem->classes) && is_array($menuItem->classes)) {
						$classes = implode(" ", $menuItem->classes);
					}
					$newURL = str_replace($source_url_base, $blog_url, $menuItem->url);
					wp_update_nav_menu_item($term->term_id, $menuItem->ID, array(
						'menu-item-object-id'   => $menuItem->object_id,
						'menu-item-object'      => $menuItem->object,
						'menu-item-parent-id'   => $menuItem->menu_item_parent,
						'menu-item-position'    => $menuItem->menu_order,
						'menu-item-type'        => $menuItem->type,
						'menu-item-title'       => $menuItem->post_title,
						'menu-item-url'         => $newURL,
						'menu-item-description' => $menuItem->post_content,
						'menu-item-attr-title'  => $menuItem->post_excerpt,
						'menu-item-target'      => $menuItem->target,
						'menu-item-classes'     => $classes,
						'menu-item-xfn'         => $menuItem->xfn,
						'menu-item-status'      => $menuItem->post_status,
					));
				}
			}
		}
	}


	private function postProcessJSONTransient()
	{
		$pages_map         = $this->getGlobalTransient('pages_map', array());
		$colibri_posts_map = $this->getGlobalTransient('colibri_posts_map', array());
		$entities          = $pages_map + $colibri_posts_map;
		$json_transients   = $this->getGlobalTransient('json_transient', array());

		foreach ($json_transients as $post_id => $json_id) {
			$new_post_id = isset($entities[$post_id]) ? $entities[$post_id] : false;
			$new_json_id = isset($entities[$json_id]) ? $entities[$json_id] : false;

			if ($new_json_id && $new_post_id) {
				$meta         = get_post_meta($new_post_id, 'extend_builder', true);
				if (!is_array($meta)) {
					continue;
				}
				$meta['json'] = $new_json_id;

				delete_post_meta($new_post_id, 'extend_builder');
				update_post_meta($new_post_id, 'extend_builder', $meta);
			}
		}
	}

	private function wooCommerceUpdate()
	{
		if (class_exists('\WC_Install')) {
			WC_Install::create_pages();


			$pages = apply_filters(
				'woocommerce_create_pages',
				array(
					'shop'           => array(),
					'cart'           => array(),
					'checkout'       => array(),
					'myaccount'      => array(),
					'refund_returns' => array(),
				)
			);

			foreach (array_keys($pages) as $key) {
				$option_value = get_option('woocommerce_' . $key . '_page_id');

				if ($option_value > 0) {
					$page_object = get_post($option_value);
					if ($page_object && 'page' === $page_object->post_type && !in_array($page_object->post_status, array('pending', 'trash', 'future', 'auto-draft'), true)) {

						$page_object->post_title = str_replace('[OLD]', '', $page_object->post_title);
						wp_update_post($page_object);
					}
				}
			}
		}


		// 'woocommerce_' . $key . '_page_id',
	}

	public function setTermMappingTransients($data, $meta)
	{

		if (isset($data['taxonomy']) && $data['taxonomy'] === "nav_menu") {
			$menus                  = $this->getTransient('menus', array());
			$menus[$data['slug']] = $data['id'];
			$this->setTransient('menus', $menus);
		}

		return $data;
	}

	public function setMappingTransients($data, $meta, $comments, $terms)
	{
		$colibri_post_types = $this->getColibriPostTypes();
		$post_type          = array_get_value($data, 'post_type', false);

		if ($post_type) {
			$post_id = $data['post_id'];
			$guid    = $data['guid'];
			if (in_array($post_type, $colibri_post_types)) {
				$transient = $this->getTransient('colibri_posts', array());
				if (!isset($transient[$guid])) {
					$transient[$guid] = $post_id;
				}
				$this->setTransient('colibri_posts', $transient);
			} else {
				if ($post_type === "page") {
					$transient = $this->getTransient('pages', array());
					if (!isset($transient[$guid])) {
						$transient[$guid] = $post_id;
					}
					$this->setTransient('pages', $transient);
				}
			}

			if (!empty($meta)) {
				$json_transients = $this->getGlobalTransient('json_transient', array());
				foreach ($meta as $meta_item) {
					if ($meta_item['key'] === "extend_builder") {
						$value                       = unserialize($meta_item['value']);
						$json_transients[$post_id] = intval($value['json']);
						$this->setGlobalTransient('json_transient', $json_transients);
						break;
					}
				}
			}
		}

		return $data;
	}
}
