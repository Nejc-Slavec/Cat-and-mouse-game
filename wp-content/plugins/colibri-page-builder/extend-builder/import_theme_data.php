<?php

namespace ExtendBuilder;


use ColibriWP\Theme\Core\Utils;
use ColibriWP\Theme\Defaults;

class ColibriThemeDataImporter {

	const UNDEFINED = "__ColibriThemeDataImporter__UNDEFINED__DATA__";

	private $current_data;
	private $page_builder_data;
	private $to_copy = array();

	private $areas = array(
		'sidebar.post'      => 'sidebar_post',
		'header.front_page' => 'header_front_page',
		'header.post'       => 'header_post',
		'footer.post'       => 'footer_post',
		'main.404'          => 'main_404',
		'main.post'         => 'main_post',
		'main.archive'      => 'main_archive',
		'main.search'       => 'main_search',
	);

	public function __construct() {
		$current_data = [];

		$this->page_builder_data          = get_theme_data();
		$this->page_builder_data['rules'] = get_sheet_rules();

		foreach ( $this->areas as $area ) {
			$current_data[ $area ] = Defaults::get( $area, array() );
		}

		$theme_mods = get_theme_mods();

		foreach ( (array) $theme_mods as $area => $value ) {
			array_set_value( $current_data, $area, $value );
		}
		$this->current_data = $current_data;
	}

	public function execute() {
		$this->preProcessData();
		$this->processPartialsStructureChanges();
		$this->setNodeRules();
		$this->setSpecificRules();

        	$this->page_builder_data['rules'] = json_encode($this->page_builder_data['rules']);

		if (use_plugin_options()) {
	            save_sheet_rules($this->page_builder_data['rules']);
	            unset($this->page_builder_data['rules']);
	        }

		save_theme_data( $this->page_builder_data );
	}


	private function preProcessData() {
		foreach ( $this->areas as $area ) {
			$data = array_get_value( $this->current_data, $area );
			$data = $this->processForArea( $area, Utils::flatten( $data ) );

			$expanded = [];

			foreach ( $data as $key => $value ) {
				array_set_value( $expanded, $key, $value );
			}

			$this->current_data[ $area ] = $expanded;
		}


		// copy data from or to inner
		$copy_descriptors = array(
			array(
				"from"  => 'front_page_header',
				"to"    => 'post_header',
				"paths" => array(
					'icon_list.localProps.iconList',
					'social_icons.localProps.icons',
					'logo.props.layoutType'
				)
			),

		);

		foreach ( $copy_descriptors as $copy_descriptor ) {
			$from = $copy_descriptor['from'];
			$to   = $copy_descriptor['to'];
			foreach ( $copy_descriptor['paths'] as $path ) {
				$data = array_get_value( $this->current_data, "{$from}.{$path}" );
				array_set_value( $this->current_data, "{$to}.{$path}", $data );
			}
		}

		// expand to partials structure

		$keys = array_keys( $this->areas );

		$parials_structured_data = array();
		foreach ( $keys as $key ) {
			$value = $this->current_data[ $this->areas[ $key ] ];
			array_set_value( $parials_structured_data, $key, $value );
		}

		$this->current_data = $parials_structured_data;
	}

	private function updateIconListSocialIconsDataNoArrayFormat(& $data, $initial_path = 'icon_list', $icons_path = 'iconList') {
		$key = "$initial_path.localProps.$icons_path";
		//check for multiple icons, if it's not found they will not be added because we do a check before inserting. 
		//This is added for social icons in case the user added more icons
		$nr_of_icons = 10;
		$icons = [];
		for($i = 0; $i < $nr_of_icons; $i++) {
			$icon = [
				"type" => "svg",
				'icon' => isset($data["$key.$i.icon.name"]) ? $data["$key.$i.icon.name"] : null,
				"name" =>  isset($data["$key.$i.icon.name"]) ? $data["$key.$i.icon.name"] : null,
				"link" => array(
					"value" =>  isset($data["$key.$i.link_value"]) ? $data["$key.$i.link_value"] : null,
				),
				"text" => isset($data["$key.$i.text"]) ? $data["$key.$i.text"] : ''
			];
			if(!empty($icon['name'])) {
				$icons[] = $icon;
			}

		}
		$data[$key] = $icons;
	}

	private function processForArea( $area, $data ) {
        // fix default opacity not working//
	   	if (!isset($data['hero.style.background.overlay.color.opacity_']) && isset($data['hero.style.background.overlay.color.opacity'])) {
	        $data['hero.style.background.overlay.color.opacity_'] = $data['hero.style.background.overlay.color.opacity'];
	    }

		foreach ( $data as $key => $value ) {
			switch ( $key ) {
				case 'hero.props.useWhiteText':
					if ( $value === true ) {
						$data[ 'title.style.color' ] = 'rgb(255,255,255)';
						$data[ 'subtitle.style.color' ] = "rgb(255,255,255)";
					}
					break;

				case 'logo.props.layoutType':
					$data[ $key ] = $this->current_data["header_front_page"]["logo"]["props"]["layoutType"];
					break;

				case 'hero.full_height':
					if ( $value ) {
						$data['hero.style.customHeight.type'] = 'full-screen';
					}
					break;

				case 'hero.image.props.frame.type':
					$frame_prefix = "hero.image.style.descendants.frameImage";
					$thickness    = array_key_exists( "{$frame_prefix}.thickness",
						$data ) ? $data["{$frame_prefix}.thickness"] : 10;

					$bg_color = array_key_exists( "{$frame_prefix}.backgroundColor",
						$data ) ? $data["{$frame_prefix}.backgroundColor"] : "transparent";

					$border_color = "transparent";
					$border_style = "none";

					if ( $value === "border" ) {
						$border_style = "solid";
						$border_color = $bg_color;
						$bg_color     = "transparent";
					}

					foreach ( array( 'top', 'bottom', 'left', 'right' ) as $position ) {
						$data["{$frame_prefix}.border.{$position}.style"] = $border_style;
						$data["{$frame_prefix}.border.{$position}.color"] = $border_color;
						$data["{$frame_prefix}.border.{$position}.width"] = array( 'value' => intval( $thickness ) );
					}

					$data["{$frame_prefix}.backgroundColor"] = $bg_color;

					break;


				case 'hero.image.props.showFrameOverImage':

					if ( $value ) {
						$data["hero.image.style.descendants.frameImage.zIndex"] = 1;
					} else {
						$data["hero.image.style.descendants.frameImage.zIndex"] = - 1;
					}

					break;

				case 'hero.image.style.descendants.frameImage.width':
				case 'hero.image.style.descendants.frameImage.height':
					$data[ $key ] = array(
						'value' => intval( $value ),
					);
					break;

				//forced to use x_value instead of x.value because of value_pattern syntax, change back to x.value
				case 'hero.image.style.descendants.frameImage.transform.translate':
					$transform              = $this->maybeJSONDecode( $value );
					$data["{$key}.x.value"] = $transform['x_value'];
					$data["{$key}.y.value"] = $transform['y_value'];
					unset( $data["{$key}.x_value"] );
					unset( $data["{$key}.y_value"] );
					break;

				case 'navigation.style.padding.top.value':
					$data[ $key ]                                  = intval( $value );
					$data["navigation.style.padding.bottom.value"] = intval( $value );
					$data["navigation.style.padding.bottom.unit"]  = 'px';
					$data["navigation.style.padding.top.unit"]     = 'px';
					break;


				case 'navigation.props.width':
					$data["top_bar.props.width"] = $value;
					break;

				case 'hero.hero_column_width':
					for ( $column = 1; $column <= 2; $column ++ ) {
						$value                                     = intval( $value );
						$width                                     = $column === 1 ? $value : 100 - $value;
						$column_width_prefix                       = "hero.column-{$column}.style.descendants.outer.columnWidth";
						$data["$column_width_prefix.type"]         = 'custom';
						$data["$column_width_prefix.custom.value"] = $width;
						$data["$column_width_prefix.custom.unit"]  = '%';
					}

					break;

				case 'icon_list.localProps.iconList':
				case 'social_icons.localProps.icons':
					$value = $this->maybeJSONDecode( $value );
					foreach ( $value as $index => $icon ) {
						$value[ $index ] = array(
							"type" => "svg",
							"icon" => array_get_value( $icon, 'icon.name' ),
							"name" => array_get_value( $icon, 'icon.name' ),
							"link" => array(
								"value" => array_get_value( $icon, 'link_value' ),
							),
							"text" => array_get_value( $icon, 'text', '' )
						);
					}
					$data[ $key ] = $value;

					break;
					//For althea. The data is saved differently in this theme for iconList
				case 'icon_list.localProps.iconList.0.icon.name':

					$this->updateIconListSocialIconsDataNoArrayFormat($data, 'icon_list', 'iconList', 3);
					break;
				//For althea. The data is saved differently in this theme for social icons
				case 'social_icons.localProps.icons.0.icon.name':
					$this->updateIconListSocialIconsDataNoArrayFormat($data, 'social_icons', 'icons', 4);
					break;
				case 'hero.style.background.overlay.color.opacity_':
					//rewrite opacity_ to opacity (there was a problem with customizer controls that prevented usage of opacity)
					$new_key = 'hero.style.background.overlay.color.opacity';
					if ( floatval( $value ) > 1 ) {
						$value = floatval( $value ) / 100;
					}
					$data[ $new_key ] = $value;
					break;

				case 'hero.style.background.video.internalUrl':
					$data[ $key ] = wp_get_attachment_url( intval( $value ) );
					break;


				case 'hero.style.background.overlay.shape.value':
					$value = $this->getShapeValue( $value );
					if ( $value ) {
						$data['hero.style.background.overlay.shape.img'] = $value;
					}

					break;

				case 'hero.image.style.descendants.image.boxShadow.layers.0':
					$data[ $key ] = $this->maybeJSONDecode( $value );

					break;

				case 'hero.style.background.overlay.gradient':
				case 'hero.style.background.image.0.source.gradient':

					if ( $key === 'hero.style.background.image.0.source.gradient' ) {
						$opacity = 1;
					} else {
						if ( array_key_exists( 'hero.style.background.overlay.color.opacity_', $data ) ) {
							$opacity = $data['hero.style.background.overlay.color.opacity_'];
						} else {
							$opacity = 70;
						}
						$opacity = intval( $opacity ) / 100;
					}

					$value = $this->maybeJSONDecode( $value );

					if ( is_array( $value ) && isset( $value['steps'] ) ) {
						foreach ( $value['steps'] as $step_key => $color ) {
							$rgba                                 = $this->colorToRGB( $color['color'] );
							$value['steps'][ $step_key ]['color'] = "rgba({$rgba['red']},{$rgba['green']},{$rgba['blue']}, $opacity)";
						}
					}

					$data[ $key ] = $value;

					break;

				case 'hero.style.background.slideshow.slides':
					$value        = $this->maybeJSONDecode( $value );
					$data[ $key ] = $value;
					break;

				case 'hero.style.background.type':
					if ( $value === "gradient" ) {
						$data["hero.style.background.image.0.source.type"] = $value;
					}
					break;
			}

			if ( strpos( $key, 'hero.style.background.overlay.gradient.steps' ) !== false ) {
				if ( strpos( $key, 'color' ) !== false ) {
					$opacity = false;

					if ( array_key_exists( 'hero.style.background.overlay.color.opacity', $data ) ) {
						$opacity = $data['hero.style.background.overlay.color.opacity'];
					}

					if ( array_key_exists( 'hero.style.background.overlay.color.opacity_', $data ) ) {
						$opacity = $data['hero.style.background.overlay.color.opacity_'];
					} else {
						$opacity = 70;
					}


					$opacity      = intval( $opacity ) / 100;
					$rgba         = $this->colorToRGB( $value );
					if (isset($rgba['red'])) {
						$value        = "rgba({$rgba['red']},{$rgba['green']},{$rgba['blue']}, $opacity)";
					}
					$data[ $key ] = $value;
				}
			}
		}

		return $data;

	}

	private function maybeJSONDecode( $value ) {
		if ( is_string( $value ) && strlen( trim( $value ) ) ) {
			// try to decode an url encoded value
			$maybe_value = json_decode( urldecode( $value ), true );

			if ( json_last_error() === JSON_ERROR_NONE ) {
				return $maybe_value;
			} else {
				// try to decode the value directly
				$maybe_value = json_decode( $value, true );
				if ( json_last_error() === JSON_ERROR_NONE ) {
					return $maybe_value;
				}
			}

		}

		return $value;
	}

	private function getShapeValue( $shape ) {

		if ( ! file_exists( get_template_directory() . "/resources/images/header-shapes/$shape.png" ) ) {
			return false;
		}
		$shape      = get_template_directory() . "/resources/images/header-shapes/$shape.png";
		$shape_data = 'data:image/png;base64,' . base64_encode( file_get_contents( $shape ) );

		return $shape_data;

	}

	private function colorToRGB( $color ) {
		if ( strpos( $color, 'rgb' ) === 0 ) {
			$color = $this->rgba2Hex( $color );
		}

		$color = trim( $color, '#' );

		if ( strlen( $color ) === 3 ) {
			$r = hexdec( substr( $color, 0, 1 ) . substr( $color, 0, 1 ) );
			$g = hexdec( substr( $color, 1, 1 ) . substr( $color, 1, 1 ) );
			$b = hexdec( substr( $color, 2, 1 ) . substr( $color, 2, 1 ) );
		} elseif ( strlen( $color ) === 6 ) {
			$r = hexdec( substr( $color, 0, 2 ) );
			$g = hexdec( substr( $color, 2, 2 ) );
			$b = hexdec( substr( $color, 4, 2 ) );
		} else {
			return array();
		}

		return array( 'red' => $r, 'green' => $g, 'blue' => $b );
	}

	private function rgba2Hex( $rgba ) {
		$hex = "#";

		if ( strpos( $rgba, 'rgba' ) === 0 ) {
			$rgb = sscanf( $rgba, "rgba(%d, %d, %d, %f)" );
		} else {
			$rgb = sscanf( $rgba, "rgb(%d, %d, %d)" );
		}

		$hex .= str_pad( dechex( $rgb[0] ), 2, "0", STR_PAD_LEFT );
		$hex .= str_pad( dechex( $rgb[1] ), 2, "0", STR_PAD_LEFT );
		$hex .= str_pad( dechex( $rgb[2] ), 2, "0", STR_PAD_LEFT );

		return $hex; // returns the hex value including the number sign (#)
	}

	private function processPartialsStructureChanges() {
		$default_partials = array_get_value( $this->page_builder_data, 'defaults.partials' );

		foreach ( $default_partials as $area => $area_partials ) {
			foreach ( $area_partials as $partial => $partial_id ) {
				$prefix = "{$area}.{$partial}";

				$partial_id   = array_get_value( $default_partials, $prefix );
				$partial      = get_partial_data( $partial_id );
				$partial_json = json_decode( $partial['json'], true );

				// change hero layout
				if ( $this->getCurrentData( "{$prefix}.hero.props.heroSection.layout" ) !== self::UNDEFINED ) {
					$value = $this->getCurrentData( "{$prefix}.hero.props.heroSection.layout" );
					array_set_value( $this->current_data, "{$prefix}.hero.props.heroSection.layout", $value );


					// $partial_json["children"][1]["children"][0]["children"]
					$columns_array = array_get_value( $partial_json, 'children.1.children.0.children', array() );

					for ( $column = 1; $column <= 2; $column ++ ) {
						$width = array_get_value(
							$this->current_data,
							"{$prefix}.hero.column-{$column}.style.descendants.outer.columnWidth",
							self::UNDEFINED
						);

						if ( $width !== self::UNDEFINED ) {
							$column_index = $column - 1;
							array_set_value(
								$columns_array,
								"{$column_index}.style.descendants.outer.columnWidth",
								$width
							);
						}
					}

					$new_columns_array = $columns_array;

					if ( $value == 'textWithMediaOnLeft' ) {
						$new_columns_array = [ $columns_array[1], $columns_array[0] ];
					}

					if ( $value === 'textOnly' ) {
						$new_columns_array = [ $columns_array[0] ];
					}

					array_set_value( $partial_json, 'children.1.children.0.children', $new_columns_array );

				}

				// remove title
				if ( $this->getCurrentData( "{$prefix}.title.show" ) !== self::UNDEFINED ) {
					$show_title = $this->getCurrentData( "{$prefix}.title.show" );

					if ( ! $show_title ) {
						$columns_array = array_get_value( $partial_json, 'children.1.children.0.children', array() );
						foreach ( $columns_array as $column_index => $column ) {
							foreach ( array_get_value( $column, 'children', array() ) as $child_index => $child ) {
								if ( array_get_value( $child, 'name' ) === 'hop-heading' || array_get_value( $child,
										'name' ) === 'hop-page-title' ) {
									// use splice to automatically recreate indexes
									array_splice( $columns_array[ $column_index ]['children'], $child_index, 1 );
									break;
								}
							}
						}
						array_set_value( $partial_json, 'children.1.children.0.children', $columns_array );
					}
				}


				// remove subtitle
				if ( $this->getCurrentData( "{$prefix}.subtitle.show" ) !== self::UNDEFINED ) {
					$show_title = $this->getCurrentData( "{$prefix}.subtitle.show" );

					if ( ! $show_title ) {
						$columns_array = array_get_value( $partial_json, 'children.1.children.0.children', array() );
						foreach ( $columns_array as $column_index => $column ) {
							foreach ( (array) $column['children'] as $child_index => $child ) {
								if ( array_get_value( $child, 'name' ) === 'hop-text' ) {
									// use splice to automatically recreate indexes
									array_splice( $columns_array[ $column_index ]['children'], $child_index, 1 );
									break;
								}
							}
						}
						array_set_value( $partial_json, 'children.1.children.0.children', $columns_array );
					}
				}

				// remove buttons
				if ( $this->getCurrentData( "{$prefix}.button_group.show" ) !== self::UNDEFINED ) {
					$show_title = $this->getCurrentData( "{$prefix}.button_group.show" );

					if ( ! $show_title ) {
						$columns_array = array_get_value( $partial_json, 'children.1.children.0.children', array() );
						foreach ( $columns_array as $column_index => $column ) {
							foreach ( (array) $column['children'] as $child_index => $child ) {
								if ( array_get_value( $child, 'name' ) === 'hop-button-group' ) {
									// use splice to automatically recreate indexes
									array_splice( $columns_array[ $column_index ]['children'], $child_index, 1 );
									break;
								}
							}
						}
						array_set_value( $partial_json, 'children.1.children.0.children', $columns_array );
					}
				}

				// set buttons
				if ( $this->getCurrentData( "{$prefix}.button_group.value" ) !== self::UNDEFINED ) {
					$buttons = $this->maybeJSONDecode( $this->getCurrentData( "{$prefix}.button_group.value" ) );

					unset( $this->current_data["{$prefix}.button_group.value"] );

					$button_group_data = $this->findNodeByTpye( $partial_json, 'hop-button-group' );
					$button_group      = $button_group_data['data'];
					$button_group_path = $button_group_data['path'];

					$style_refs = array(
						0 => $button_group["children"][0]["styleRef"],
						1 => $button_group["children"][1]["styleRef"]
					);

					$style = array(
						0 => $this->getStyleRefData( $style_refs[0], 'style' ),
						1 => $this->getStyleRefData( $style_refs[1], 'style' )
					);

					foreach ( $buttons as $index => $button ) {
						$style_index = intval( $button['button_type'] );
						$this->setStyleRefData( $style_refs[ $index ], 'style', $style[ $style_index ] );
						$button_group['children'][ $index ]['props'] = array(
							'text' => $button['label'],
							'link' => array(
								'value' => $button['url']
							)
						);
					}

					if ( count( $buttons ) === 1 ) {
						$button_group['children'] = array( $button_group['children'][0] );
					}

					array_set_value( $partial_json, $button_group_path, $button_group );

				}

				// change nav layout
				if ( $this->getCurrentData( "{$prefix}.navigation.props.layoutType" ) !== self::UNDEFINED ) {
					$value = $this->getCurrentData( "{$prefix}.navigation.props.layoutType" );

					// $partial_json["children"][0]["children"][0]["children"]
					$columns_array = array_get_value( $partial_json, 'children.0.children.0.children', array() );

					$offscreen_logo_ref = isset($columns_array[2]["children"][0]["children"][0]["children"][0]["slots"]["header"][0]["children"][0]["children"][0]["styleRef"]) ?
										  $columns_array[2]["children"][0]["children"][0]["children"][0]["slots"]["header"][0]["children"][0]["children"][0]["styleRef"] : [];
					$main_logo_ref      = isset($columns_array[0]["children"][0]["styleRef"]) ? $columns_array[0]["children"][0]["styleRef"] : [];

					$this->copyStyle( $main_logo_ref, $offscreen_logo_ref, "props.layoutType" );

					if ( $value === 'logo-above-menu' ) {

						array_set_value( $columns_array, '0.style.descendants.outer.columnWidth.type', 'custom' );
						array_set_value( $columns_array, '0.style.descendants.outer.columnWidth.custom.value', '100' );
						array_set_value( $columns_array, '2.style.descendants.outer.columnWidth.type', 'custom' );
						array_set_value( $columns_array, '2.style.descendants.outer.columnWidth.custom.value', '100' );

						array_set_value( $this->current_data, "{$prefix}.logo.props.horizontalTextAlign",
							'center' );

						$menu = &$columns_array[2]['children'][0];
						//style.descendants.innerMenu.justifyContent center
						$menu['style']['descendants']['innerMenu']['justifyContent'] = 'center';
						$menu['style']['buttonAlign']                                = 'center';
						//sticky
						$columns_array[0]['style']['ancestor']['sticky']['descendants']['outer']['columnWidth']['type'] = 'custom';
						$columns_array[2]['style']['ancestor']['sticky']['descendants']['outer']['columnWidth']['type'] = 'custom';

						$columns_array = array( $columns_array[0], $columns_array[2] );
					}

					array_set_value( $partial_json, 'children.0.children.0.children', $columns_array );
				}

				// blog data
				if ( $prefix === "main.archive" ) {
				   	$loop_node_data = $this->findNodeByTpye( $partial_json, 'hop-archive-loop' );
				   	$loop_node = $loop_node_data['data'];
					$loop_ref   = $loop_node["styleRef"];
					$loop_props = $this->getStyleRefData( $loop_ref, 'props' );

					$loop_props['layout']['itemsPerRow'] = intval( $this->getMod( "blog_posts_per_row", 2 ) );
					$loop_props['showMasonry']           = $this->getBoolMod( 'blog_enable_masonry', true );

					$this->setStyleRefData( $loop_ref, 'props', $loop_props );


					$post_thumbnail_data = $this->findNodeByTpye( $partial_json, 'hop-post-thumbnail' );
					$post_thumbnail      = $post_thumbnail_data['data'];
					$post_thumbnail_path = $post_thumbnail_data['path'];

					$color = $this->getMod( "blog_post_thumb_placeholder_color", '#F79007' );

					$post_thumbnail_style = $this->getStyleRefData( $post_thumbnail['styleRef'], 'style' );
					array_set_value( $post_thumbnail_style, 'background.color', $color );

					$post_thumbnail['props']['showPlaceholder'] = $this->getBoolMod( 'blog_show_post_thumb_placeholder',
						true );

					if ( ! $post_thumbnail['props']['showPlaceholder'] ) {
						array_set_value( $post_thumbnail_style, 'background.color', 'rgba(255,255,255,0)' );
					}

					$this->setStyleRefData( $post_thumbnail['styleRef'], 'style', $post_thumbnail_style );
					array_set_value( $partial_json, $post_thumbnail_path, $post_thumbnail );

				}

				if ( $prefix === "header.post" ) {
					$inner_logo_data = $this->findNodeByTpye( $partial_json, 'hop-logo' );

					$inner_offscreen_logo_data = $this->findNodeByTpye(
						$partial_json,
						'hop-logo',
						"",
						$inner_logo_data['path']
					);

					$logo_props = $this->current_data["header"]["front_page"]["logo"]["props"];
					$refs       = array(
						$inner_logo_data['data']['styleRef'],
						$inner_offscreen_logo_data['data']['styleRef']
					);

					foreach ( $refs as $ref ) {
						$props = $this->getStyleRefData( $ref, 'props' );
						$props = array_replace_recursive( $props, $logo_props );
						$this->setStyleRefData( $ref, 'props', $props );
					}


				}

				if ( $prefix === "main.post" ) {
					$post_thumbnail_data = $this->findNodeByTpye( $partial_json, 'hop-post-thumbnail' );
					$post_thumbnail      = $post_thumbnail_data['data'];
					$post_thumbnail_path = $post_thumbnail_data['path'];

					$color = $this->getMod( "blog_post_thumb_placeholder_color", '#F79007' );

					$post_thumbnail_style = $this->getStyleRefData( $post_thumbnail['styleRef'], 'style' );
					array_set_value( $post_thumbnail_style, 'background.color', $color );


					$post_thumbnail['props']['showPlaceholder'] = $this->getBoolMod( 'blog_show_post_thumb_placeholder',
						true );

					if ( ! $post_thumbnail['props']['showPlaceholder'] ) {
						array_set_value( $post_thumbnail_style, 'background.color', 'rgba(255,255,255,0)' );
					}

					$this->setStyleRefData( $post_thumbnail['styleRef'], 'style', $post_thumbnail_style );
					array_set_value( $partial_json, $post_thumbnail_path, $post_thumbnail );
				}

				$partial['json'] = $partial_json;
				update_partial( $partial_id, $partial );
			}
		}

	}

	private function getCurrentData( $path ) {
		return array_get_value( $this->current_data, $path, self::UNDEFINED );
	}

	private function findNodeByTpye( $data, $type, $path = "", $skip_path = false ) {

		$path = trim( $path, '.' );

		if ( $skip_path === $path ) {
			return false;
		}

		if ( isset( $data['name'] ) && $data['name'] === $type ) {

			return array(
				'data' => $data,
				'path' => $path
			);

		}

		if ( isset( $data['children'] ) && is_array( $data['children'] ) ) {
			foreach ( $data['children'] as $child_index => $child ) {
				$child_path = "{$path}.children.{$child_index}";
				if ( $r = $this->findNodeByTpye( $child, $type, $child_path, $skip_path ) ) {
					return $r;
				};
			}
		}


		if ( isset( $data['slots'] ) && is_array( $data['slots'] ) ) {
			foreach ( $data['slots'] as $slot_name => $slot_data ) {
				foreach ( $slot_data as $slot_index => $slot_child ) {
					$slot_path = "{$path}.slots.{$slot_name}.{$slot_index}";
					if ( $r = $this->findNodeByTpye( $slot_child, $type, $slot_path, $skip_path ) ) {
						return $r;
					};
				}
			}
		}
	}

	private function getStyleRefData( $ref, $path ) {
		foreach ( $this->page_builder_data['rules'] as $id => $rule ) {
			if ( intval( $rule['id'] ) === intval( $ref ) ) {
				return array_get_value( $rule, $path, array() );
			}
		}

		return array();
	}

	private function setStyleRefData( $ref, $path, $value ) {
		foreach ( $this->page_builder_data['rules'] as $id => $rule ) {
			if ( intval( $rule['id'] ) === intval( $ref ) ) {
				array_set_value( $this->page_builder_data['rules'][ $id ], $path, $value );
				break;
			}
		}
	}

	private function copyStyle( $source_ref, $target_ref, $path ) {
		$this->to_copy[] = compact( 'source_ref', 'target_ref', 'path' );
	}

	private function getMod( $mod, $fallback = false ) {
		return get_theme_mod( $mod, Defaults::get( $mod, $fallback ) );
	}

	private function getBoolMod( $mod, $fallback = false ) {
        return $this->getMod($mod, $fallback) ? true : false;
    }

	private function setNodeRules() {
		$default_partials = array_get_value( $this->page_builder_data, 'defaults.partials' );

		foreach ( $default_partials as $area => $area_partials ) {
			foreach ( $area_partials as $partial => $partial_id ) {
				$prefix       = "{$area}.{$partial}";
				$theme_nodes  = array_get_value( $this->current_data, $prefix );
				$partial_id   = array_get_value( $default_partials, $prefix );
				$partial      = get_partial_data( $partial_id );
				$partial_json = json_decode( $partial['json'], true );

				if ( ! $theme_nodes ) {
					continue;
				}

				foreach ( $theme_nodes as $node ) {
					$this->setNodeStyleAndProps( $node, $partial_json, $partial_id );
					$this->walkInnerNodesToSetData( $node, $partial_json, $partial_id );

				}

				$partial['json'] = $partial_json;
				update_partial( $partial_id, $partial );

			}
		}

		$this->executeCopyStyle();
	}

	private function setNodeStyleAndProps( &$node, &$partial_json, $partial_id ) {
		$style_ref = array_get_value( $node, 'styleRef', self::UNDEFINED );

		if ( $style_ref === self::UNDEFINED ) {
			return false;
		}

		$style_ref = intval( $style_ref );
		$rules_id  = - 1;
		$rule      = array();

		$id_parts    = explode( "-", $node['nodeId'] );
		$new_node_id = $partial_id . "-" . $id_parts[1];

		$new_style_ref = $this->findStyleRefByNewNodeIde( $partial_json, $new_node_id );

		if ( $new_style_ref ) {
			$style_ref = $new_style_ref;
		}

		foreach ( $this->page_builder_data['rules'] as $id => $_rule ) {
			if ( intval( $_rule['id'] ) === $style_ref ) {
				$rule     = $_rule;
				$rules_id = $id;

				break;
			}
		}

		if ( $rules_id === - 1 ) {
			return false;
		}


		$style      = array_get_value( $node, 'style', self::UNDEFINED );
		$localStyle = array_get_value( $node, 'localStyle', self::UNDEFINED );
		$props      = array_get_value( $node, 'props', self::UNDEFINED );
		$localProps = array_get_value( $node, 'localProps', self::UNDEFINED );

		if ( $style !== self::UNDEFINED ) {
			$current_style = array_get_value( $rule, 'style', array() );
			$style         = $this->merge_arrays( $current_style, $style );
			array_set_value( $rule, 'style', $style );
			$this->page_builder_data['rules'][ $rules_id ] = $rule;
		}

		if ( $localStyle !== self::UNDEFINED ) {
			$this->setDataToNode( $partial_json, $style_ref, 'style', $localStyle );
		}

		if ( $props !== self::UNDEFINED ) {
			$current_props = array_get_value( $rule, 'props', array() );
			$props         = $this->merge_arrays( $current_props, $props );
			array_set_value( $rule, 'props', $props );
			$this->page_builder_data['rules'][ $rules_id ] = $rule;
		}

		if ( $localProps !== self::UNDEFINED ) {
			$this->setDataToNode( $partial_json, $style_ref, 'props', $localProps );
		}

		return true;
	}

	private function findStyleRefByNewNodeIde( $data, $node_id ) {
		if ( isset( $data['id'] ) && $data['id'] === $node_id ) {
			return $data['styleRef'];

		}

		if ( isset( $data['children'] ) && is_array( $data['children'] ) ) {
			foreach ( $data['children'] as $child ) {
				if ( $r = $this->findStyleRefByNewNodeIde( $child, $node_id ) ) {
					return $r;
				};
			}
		}


		if ( isset( $data['slots'] ) && is_array( $data['slots'] ) ) {
			foreach ( $data['slots'] as $slot_name => $slot_data ) {
				foreach ( $slot_data as $slot_child ) {

					if ( $r = $this->findStyleRefByNewNodeIde( $slot_child, $node_id ) ) {
						return $r;
					};
				}
			}
		}

		return false;
	}

	private function merge_arrays( $array1, $array2 ) {
		return array_replace_recursive( $array1, $array2 );
	}

	private function setDataToNode( &$tree, $style_ref, $path, $value ) {
		if ( isset( $tree['styleRef'] ) && intval( $tree['styleRef'] === $style_ref ) ) {

			if ( is_array( $value ) ) {
				$current_value = array_get_value( $tree, $path, array() );

				if (
					array_key_exists( 'iconList', $value ) ||
					array_key_exists( 'icons', $value )
				) {
					// ignore merge
				} else {
					$value = $this->merge_arrays( $current_value, $value );
				}


			}

			array_set_value( $tree, $path, $value );
		}

		if ( isset( $tree['children'] ) && is_array( $tree['children'] ) ) {
			foreach ( $tree['children'] as $index => $child_data ) {
				$this->setDataToNode( $child_data, $style_ref, $path, $value );
				$tree['children'][ $index ] = $child_data;
			}
		}

		if ( isset( $tree['slots'] ) && is_array( $tree['slots'] ) ) {
			foreach ( $tree['slots'] as $slot => $slot_data ) {
				foreach ( (array) $slot_data as $slot_data_id => $slot_data_item ) {
					$this->setDataToNode( $slot_data_item, $style_ref, $path, $value );
					$tree['slots'][ $slot ][ $slot_data_id ] = $slot_data_item;
				}
			}
		}

		return $tree;
	}

	private function walkInnerNodesToSetData( &$node, &$partial_json, $partial_id ) {
		$keys          = array_keys( $node );
		$node_keys     = array( 'partialId', 'id', 'nodeId', 'selective_selector', 'styleRef', 'style', 'props' );
		$remining_keys = array_diff( $keys, $node_keys );

		foreach ( $remining_keys as $key ) {
			$inner_node = $node[ $key ];

			if ( ! is_array( $inner_node ) ) {
				continue;
			}

			$this->setNodeStyleAndProps( $inner_node, $partial_json, $partial_id );
			$this->walkInnerNodesToSetData( $inner_node, $partial_json, $partial_id );
			$node[ $key ] = $inner_node;
		}
	}

	private function executeCopyStyle() {

		foreach ( $this->to_copy as $data ) {

			$source_ref = $data['source_ref'];
			$target_ref = $data['target_ref'];
			$path       = $data['path'];

			$source_rule  = false;
			$dest_rule_id = - 1;
			foreach ( $this->page_builder_data['rules'] as $id => $_rule ) {
				if ( intval( $_rule['id'] ) === intval( $source_ref ) ) {
					$source_rule = $_rule;
					break;
				}
			}

			foreach ( $this->page_builder_data['rules'] as $id => $_rule ) {
				if ( intval( $_rule['id'] ) === intval( $target_ref ) ) {
					$dest_rule_id = $id;
					break;
				}
			}

			if ( is_array( $source_rule ) && $dest_rule_id >= 0 ) {
				$value = array_get_value( $source_rule, $path );
				array_set_value( $this->page_builder_data['rules'][ $dest_rule_id ], $path, $value );
			}
		}
	}

	private function setSpecificRules() {
		$theme_mods = (array) get_theme_mods();

		// set alternate logo
		if ( array_key_exists( 'alternate_logo', $theme_mods ) ) {
			$alternate_logo = $theme_mods['alternate_logo'];

			if ( is_numeric( $alternate_logo ) ) {
				$alternate_logo = wp_get_attachment_image_url( $alternate_logo, 'full' );
			}

			array_set_value( $this->page_builder_data, 'global.logo.alternateImage', $alternate_logo );
		}

		if ( array_key_exists( 'blog_sidebar_enabled', $theme_mods ) ) {
			$blog_sidebar_enabled = ! ! $theme_mods['blog_sidebar_enabled'];

			if ( ! $blog_sidebar_enabled ) {
				$partial_id = array_get_value( $this->page_builder_data, 'defaults.partials.sidebar.post' );
				array_set_value( $this->page_builder_data, "global.visible_partials.sidebar.{$partial_id}",
					$blog_sidebar_enabled );
			}

		}

	}

}


function colibri_theme_import_theme_data() {
	$importer = new ColibriThemeDataImporter();
	$importer->execute();
}
