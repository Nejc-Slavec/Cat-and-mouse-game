<?php

namespace ExtendBuilder;

use ColibriWP\PageBuilder\PageBuilder;

function prefix($name = "")
{
	return "extend_builder_$name";
}

function get_public_post_types()
{
	$args = array(
		'public'   => true,
		'_builtin' => false,
	);

	$output   = 'names';
	$operator = 'and';

	$post_types = get_post_types($args, $output, $operator);

	return $post_types;
}

function log($msg)
{
	//    openlog("extend builder", LOG_PID | LOG_PERROR, LOG_USER);
	//    $access = date("Y/m/d H:i:s");
	//    syslog(LOG_WARNING, "$access : $msg");
	//    closelog();
}

function log2($msg)
{
	$t     = microtime(true);
	$micro = sprintf("%06d", ($t - floor($t)) * 1000000);
	$d     = new \DateTime(date('Y-m-d H:i:s.' . $micro, $t));
	error_log($msg . "->" . $d->format("Y-m-d H:i:s.u"));
}

$colibri_loaded_files_values = array();

function load_file_value($key, $json_string)
{
	global $colibri_loaded_files_values;
	if (is_string($json_string)) {
		$colibri_loaded_files_values[$key] = json_decode($json_string, true);
	} else {
		$colibri_loaded_files_values[$key] = $json_string;
	}

	return $colibri_loaded_files_values[$key];
}

function get_file_value($key)
{
	global $colibri_loaded_files_values;

	return $colibri_loaded_files_values[$key];
}

function get_key_value($array, $key, $default)
{
	$value = array_get_value($array, $key, $default);

	return $value;
}

/**
 * @param array $array
 * @param array|string $parents
 * @param string $glue
 *
 * @return mixed
 */
function array_get_value(array &$array, $parents, $default = null, $glue = '.')
{
	if (!$array || !is_array($array)) {
		return $default;
	}

	if (!is_array($parents)) {
		$parents = explode($glue, $parents);
	}

	$ref = &$array;

	foreach ((array) $parents as $parent) {
		if (is_array($ref) && array_key_exists($parent, $ref)) {
			$ref = &$ref[$parent];
			// walk inside object
		} else if (is_object($ref) && property_exists($ref, $parent)) {
			$ref = &$ref->$parent;
		} else {
			return $default;
		}
	}

	return $ref;
}

function colibri_esc_html_preserve_spaces($text)
{
	return esc_html(str_replace(" ", "&nbsp;", $text));
}

/**
 * @param array $array
 * @param array|string $parents
 * @param mixed $value
 * @param string $glue
 */
function array_set_value(array &$array, $parents, $value, $glue = '.')
{
	if (!is_array($parents)) {
		$parents = explode($glue, (string) $parents);
	}

	$ref = &$array;

	foreach ($parents as $parent) {
		if (isset($ref) && !is_array($ref)) {
			$ref = array();
		}

		$ref = &$ref[$parent];
	}

	$ref = $value;
}

/**
 * @param array $array
 * @param array|string $parents
 * @param string $glue
 */
function array_unset_value(&$array, $parents, $glue = '.')
{
	if (!is_array($parents)) {
		$parents = explode($glue, $parents);
	}

	$key = array_shift($parents);

	if (empty($parents)) {
		unset($array[$key]);
	} else {
		array_unset_value($array[$key], $parents);
	}
}

function array_map_by_key($array, $key)
{
	$result = [];
	array_walk($array, function ($partial) use ($result, $key) {
		$id = array_get_value($partial, $key, null);
		if ($id !== null) {
			$result[$id] = $partial;
		}
	});

	return $result;
}

function colibri_placeholder_p($text, $echo = false)
{
	$content = "";

	if (mesmerize_is_customize_preview()) {
		$content = '<p class="content-placeholder-p">' . wp_kses_post($text) . '</p>';
	}

	if ($echo) {
        //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $content;
	} else {
		return $content;
	}
}

function colibri_cache_get($name, $default = null)
{

	$colibri_cache = isset($GLOBALS['__colibri_plugin_cache__']) ? $GLOBALS['__colibri_plugin_cache__'] : array();
	$value         = $default;

	if (colibri_cache_has($name)) {
		$value = $colibri_cache[$name];
	}

	return $value;
}

function colibri_cache_has($name)
{
	$colibri_cache = isset($GLOBALS['__colibri_plugin_cache__']) ? $GLOBALS['__colibri_plugin_cache__'] : array();

	return array_key_exists($name, $colibri_cache);
}

function colibri_cache_set($name, $value)
{
	$colibri_cache          = isset($GLOBALS['__colibri_plugin_cache__']) ? $GLOBALS['__colibri_plugin_cache__'] : array();
	$colibri_cache[$name] = $value;

	$GLOBALS['__colibri_plugin_cache__'] = $colibri_cache;
}

function _colibri_transient_cache_clear()
{
	delete_transient('colibri_page_builder_cache');
}

add_filter("customize_save_response", function ($value) {

	if (!isset($value['changeset_status']) || $value['changeset_status'] !== "auto-draft") {
		_colibri_transient_cache_clear();
	}

	return $value;
});

add_action('updated_postmeta', '\ExtendBuilder\_colibri_transient_cache_clear');
add_action('wp_insert_post', '\ExtendBuilder\_colibri_transient_cache_clear');

function colibri_transient_cache_get($name, $fallback = null)
{
	$transient = (array) get_transient('colibri_page_builder_cache');

	return array_get_value($transient, $name, $fallback);
}

function colibri_transient_cache_set($name, $value)
{
	$transient = (array) get_transient('colibri_page_builder_cache');
	array_set_value($transient, $name, $value);
	set_transient('colibri_page_builder_cache', $transient);
}

function is_true($var)
{

	if ($var === true || intval($var) !== 0) {
		return true;
	}


	switch (strtolower($var)) {
		case '1':
		case 'true':
		case 'on':
		case 'yes':
		case 'y':
			return true;
		default:
			return false;
	}
}


function is_false($var)
{

	if ($var === false || intval($var) === 0) {
		return true;
	}

	switch (strtolower($var)) {
		case '0':
		case 'false':
		case 'off':
		case 'no':
		case 'n':
			return true;
		default:
			return false;
	}
}

function get_template_part($slug, $name = null)
{
	do_action("get_template_part_{$slug}", $slug, $name);

	$templates = array();
	$name      = (string) $name;
	if ('' !== $name) {
		$templates[] = "{$slug}-{$name}.php";
	}

	$templates[] = "{$slug}.php";

	$located_in_theme = locate_template($templates, false, false);

	if ($located_in_theme) {
		locate_template($templates, true, false);
	} else {
		foreach ($templates as $template_name) {
			$path = "/template-parts/$template_name";
			if (PageBuilder::instance()->fileExists($path)) {
				PageBuilder::instance()->loadFile($path);
				break;
			}
		}
	}
}

function _sanitize_customizer_preview_context_query($query)
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

function apply_customizer_preview_context()
{
	if (!is_customize_preview()) {
		return;
	}

	$context = isset($_REQUEST['context']) ? $_REQUEST['context'] : array();
	$query   = is_array($context) && isset($context['query']) ? _sanitize_customizer_preview_context_query($context['query']) : array();

	if (count($query)) {
		query_posts($query);
	}
}

function ob_wrap($function, $params = array())
{
	ob_start();
	call_user_func_array($function, $params);

	return ob_get_clean();
}

function colibri_current_user_has_role($role)
{
	$user = wp_get_current_user();
	if (in_array($role, (array) $user->roles)) {
		return true;
	}

	return false;
}

function colibri_shortcode_decode($data)
{
	return urldecode(base64_decode($data));
}

function get_colibri_image($name)
{
	global $wpdb;
	$posts = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->posts WHERE post_title LIKE '%s'", '%' . $wpdb->esc_like($name) . '%'));
	if ($posts && count($posts)) {
		$id = $posts[0]->ID;

		return array("id" => $id, "url" => wp_get_attachment_url($id));
	}
}

function import_colibri_image($url)
{
	$skip_import = apply_filters('colibri_api_import_image_skip', false);
	if ($skip_import) {
		return  array(
			'colibri-url' => $url,
			'url'         => $url,
		);
	}
	include_once(ABSPATH . 'wp-admin/includes/image.php');
	$name           = basename($url);
	$existing_image = get_colibri_image($name);
	if ($existing_image) {
		$existing_image['colibri-url'] = $url;
		return $existing_image;
	}

	$filename     = $name;
	$response = null;

	try {
		$response = wp_safe_remote_get($url);
	} catch (Exception $e) {
	}

	$file_content = wp_remote_retrieve_body($response);
	if (empty($file_content)) {
		return false;
	}

	$upload = wp_upload_bits(
		$filename,
		null,
		$file_content
	);

	$post = [
		'post_title' => $filename,
		'guid'       => $upload['url'],
	];

	$info = wp_check_filetype($upload['file']);
	if ($info) {
		$post['post_mime_type'] = $info['type'];
	}

	$post_id = wp_insert_attachment($post, $upload['file']);
	wp_update_attachment_metadata(
		$post_id,
		wp_generate_attachment_metadata($post_id, $upload['file'])
	);
	$new_attachment = array(
		'colibri-url' => $url,
		'url'         => $upload['url'],
		'id'          => $post_id,
	);

	return $new_attachment;
}

function convertStrSpaceToHtml($str)
{
	return str_replace(' ', '&nbsp', $str);
}


function compose_cache_key($prefix)
{
	return implode('-', func_get_args());
}

function colibri_get_post_featured_img()
{
	$post = get_post();
	if (!$post) {
		return;
	}
	$background_img = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'single-post-thumbnail');
	if (!$background_img) {
		return null;
	}

	return $background_img[0];
}

function get_mailchimp_form_shortcode()
{
	$form_id = "";

	//check for mailchimp plugin
	if (class_exists('\MC4WP_Forms_Admin')) {
		$forms = \mc4wp_get_forms();
		if (count($forms) > 0) {
			$form_id = $forms[0]->ID;
		} else {


			//code from MC4WP_Forms_Admin->process_add_form function
			$form_content = include MC4WP_PLUGIN_DIR . 'config/default-form-content.php';

			$form_id = wp_insert_post(
				array(
					'post_type'    => 'mc4wp-form',
					'post_status'  => 'publish',
					'post_title'   => 'colibri-form',
					'post_content' => $form_content,
				)
			);
		}
	}
	$shortcode = '';
	if ($form_id) {
		$shortcode = sprintf('[mc4wp_form id="%d"]', $form_id);
	}

	return $shortcode;
}

function colibri_duplicate_post_as_draft($post_id, $title = null)
{

	/*
    * get the original post id
    */

	// verify Nonce
	global $wpdb;
	$suffix = '--copy';
	$post_status = 'draft';
	$returnpage = '';

	$post = get_post($post_id);

	if ($title === null) {
		$new_post_title = $post->post_title . $suffix;
	} else {
		$new_post_title = $title;
	}

	/*
    * if you don't want current user to be the new post author,
    * then change next couple of lines to this: $new_post_author = $post->post_author;
    */
	$current_user = wp_get_current_user();
	$new_post_author = $current_user->ID;
	/*
    * if post data exists, create the post duplicate
    */
	if (isset($post) && $post != null) {
		/*
        * new post data array
        */
		$args = array(
			'comment_status' => $post->comment_status,
			'ping_status' => $post->ping_status,
			'post_author' => $new_post_author,
			'post_content' => $post->post_content,
			'post_excerpt' => $post->post_excerpt,
			//'post_name' => $post->post_name,
			'post_parent' => $post->post_parent,
			'post_password' => $post->post_password,
			'post_status' => $post_status,
			'post_title' => $new_post_title,
			'post_type' => $post->post_type,
			'to_ping' => $post->to_ping,
			'menu_order' => $post->menu_order,
		);
		/*
        * insert the post by wp_insert_post() function
        */
		$new_post_id = wp_insert_post($args);
		/*
        * get all current post terms ad set them to the new post draft
        */
		$taxonomies = get_object_taxonomies($post->post_type);
		if (!empty($taxonomies) && is_array($taxonomies)) :
			foreach ($taxonomies as $taxonomy) {
				$post_terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'slugs'));
				wp_set_object_terms($new_post_id, $post_terms, $taxonomy, false);
			}
		endif;
		/*
        * duplicate all post meta
        */
		$post_meta_infos = $wpdb->get_results($wpdb->prepare("SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id = %d", $post_id));
		if (count($post_meta_infos) != 0) {

			foreach ($post_meta_infos as $meta_info) {
				$meta_key        = $meta_info->meta_key;
				$meta_value      = $meta_info->meta_value;
				$sql_query_sel[] = $wpdb->prepare(
					"SELECT %d, %s,%s ",
					$new_post_id,
					$meta_key,
					$meta_value
				);
			}

			$sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";
			$sql_query .= implode(" UNION ALL ", $sql_query_sel);

			$wpdb->query($sql_query);
		}
		return $new_post_id;
	} else {
        //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		wp_die('Error! Post creation failed, could not find original post: ' . $post_id);
	}
}

function colibri_is_blog_archive_page()
{
	$is_post_type_archive =  get_post_type() === 'post';
	return (is_archive() && $is_post_type_archive) || is_blog_posts();
}
