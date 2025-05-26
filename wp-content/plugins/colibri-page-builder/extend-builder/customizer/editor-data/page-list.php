<?php

namespace ExtendBuilder;

require_once __DIR__ . '/page-list/page-data.php';

class PagesList
{

	static function get_page_list()
	{
		$sitePagesCategory    = array(
			"id"    => "sitePages",
			"label" => "Site pages",
		);
		$blogPostsCategory    = array(
			"id"    => "blogPosts",
			"label" => "Blog posts",
		);
		$generalPagesCategory = array(
			"id"    => "systemPages",
			"label" => "System pages",
		);

		return array_merge(
			self::get_user_pages($sitePagesCategory),
			self::get_blog_posts($blogPostsCategory),
			self::get_general_pages($generalPagesCategory)
		);
	}

	static function get_user_pages($category)
	{
		$args = apply_filters('colibri/page-list/get-pages', array(
			'post_status' => array('publish', 'draft'),
		));
		return self::get_formatted_wp_posts(get_pages($args), $category);
	}

	static function get_formatted_wp_posts($posts, $category)
	{
		$formatted_posts = [];
		for ($i = 0; $i < count($posts); $i++) {
			$post              = $posts[$i];
			$data              = new PageData($post->post_title, get_permalink($post), $category, $post->ID);
			$formatted_posts[] = get_object_vars($data);
		}

		return $formatted_posts;
	}

	static function get_blog_posts($category)
	{
		$posts_args = apply_filters('colibri/page-list/get-posts', array(
			'numberposts' => 50,
			'post_status' => array('publish'),
		));

		return self::get_formatted_wp_posts(get_posts($posts_args), $category);
	}

	static function get_general_pages($category)
	{

		$no_page_found = new PageData(
			'404',
			//se the url homepage with the post id of -1 so we are sure there is no result
			get_option('home') . '/?p=-1',
			$category
		);

		$search_page_url = \get_search_link('colibri-search-page');
		$search_page     = new PageData(
			'search',
			$search_page_url,
			$category
		);

		return [
			get_object_vars($no_page_found),
			get_object_vars($search_page)
		];
	}
}
