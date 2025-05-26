<?php

namespace ExtendBuilder;

function get_theme_data_defaults()
{

	$default_ids = array(
		"front_page" => - 1,
		"page"       => - 1,

		"post"         => - 1,
		"archive_post" => - 1,
	);

	$default_theme_location = array(
		"header"  => array(
			array( "id" => "header-menu", "label" => "Header primary menu", "priority" => 1 ),
			array( "id" => "header-menu-1", "label" => "Header secondary menu", "priority" => 2 ),
		),
		"content" => array(
			array( "id" => "content-menu", "label" => "In Page Menu", "priority" => 1 ),
		),
		"footer"  => array(
			array( "id" => "footer-menu", "label" => "Footer primary menu", "priority" => 1 ),
			array( "id" => "footer-menu-1", "label" => "Footer secondary menu", "priority" => 2 ),
		),
	);

	$theme_locations = array(
		array( "id" => "header-menu", "label" => "Header primary menu" ),
		array( "id" => "header-menu-1", "label" => "Header secondary menu" ),
		array( "id" => "content-menu", "label" => "In Page Menu" ),
		array( "id" => "footer-menu", "label" => "Footer primary menu" ),
		array( "id" => "footer-menu-1", "label" => "Footer secondary menu" ),
	);

	$post_types = get_public_post_types();

	foreach ( $post_types as $type ) {
		$default_ids[ $type ]              = - 1;
		$default_ids[ 'archive_' . $type ] = - 1;
	}

	$default_partials = array(
		"sidebar" => $default_ids,
		"header"  => $default_ids,
		"footer"  => $default_ids,
	);

	$colibri_data_default = array(
		"fonts"        => array(
			"google" => array(),
		),
		"dummyChange" => "",
		"icons"        => array(),
		"css"          => "",
		"rules"        => "[]",
		"cssById"      => array(),
		"cssByPartialId"      => array(),
		"medias"       => array(),
		"defaults"     => array(
			"partials" => $default_partials,
		),
		"menu"         => array(
			"defaultLocations"  => $default_theme_location,
			"locations"         => $theme_locations,
			"locationsToAdd"    => array(),
			"locationsToDelete" => array(),
		),
		"widget_areas" => array(
            "header-widget-area-1" => array("name" => "Header widget area 1", "index" => 1, "type"=> "header"),
            "header-widget-area-2" => array("name" => "Header widget area 2", "index" => 2, "type"=> "header"),
            "header-widget-area-3" => array("name" => "Header widget area 3", "index" => 3, "type"=> "header"),
            "content-widget-area-1" => array("name" => "In page widget area 1", "index" => 1, "type"=> "content"),
            "content-widget-area-2" => array("name" => "In page widget area 2", "index" => 2, "type"=> "content"),
            "content-widget-area-3" => array("name" => "In page widget area 3", "index" => 3, "type"=> "content"),
            "footer-widget-area-1" => array("name" => "Footer widget area 1", "index" => 1, "type"=> "footer"),
            "footer-widget-area-2" => array("name" => "Footer widget area 2", "index" => 2, "type"=> "footer"),
            "footer-widget-area-3" => array("name" => "Footer widget area 3", "index" => 3, "type"=> "footer"),
            "footer-widget-area-4" => array("name" => "Footer widget area 4", "index" => 4, "type"=> "footer"),
		),
	);

	return $colibri_data_default;
}
