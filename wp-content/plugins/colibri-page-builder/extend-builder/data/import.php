<?php

namespace ExtendBuilder;

use ColibriWP\PageBuilder\PageBuilder;
use Exception;

class Import {
	public static  $theme_default_data_key = 'theme_default_data';
	private static $instance               = null;
	private static $temp_data
	                                       = array(
			"refs_map"       => array(),
			"next_style_ref" => 0,
		);

	public static function instance() {
		if ( ! static::$instance ) {
			static::$instance = new static();
		}

		return static::$instance;
	}


	public static function maybe_import_theme_default() {
		$theme_default_id = self::$theme_default_data_key;
		$imported         = self::default_is_imported( $theme_default_id );
		if ( ! $imported ) {
			$theme_default_data
				= self::get_file_value( $theme_default_id, $theme_default_id );
			if ( $theme_default_data ) {
				unset( $theme_default_data['cssByPartialId'] );
               			//unset( $theme_default_data[ColibriOptionsIds::CSS_BY_PARTIAL_ID] );

				self::import_theme_data( $theme_default_data );
				self::set_default_as_imported( $theme_default_id );
			}
		}

		return false;
	}

	public static function default_is_imported( $key ) {
		$import_flag = "imported.$key";
		$imported    = get_theme_data( $import_flag );

		return ! ! $imported;
	}

	public static function get_file_value( $id, $value_key ) {

		$front_page_design = get_option( 'colibriwp_predesign_front_page_index', 0 );
		if ( intval( $front_page_design ) ) {
			$full_path = __DIR__ . "/import-{$front_page_design}/$id.php";
		} else {
			$full_path = __DIR__ . "/import/$id.php";
		}

	        $value = apply_filters('colibri_page_builder/import_file_value', null, $id, $value_key, $front_page_design);

	        if ($value !== null) {
			return $value;
		}

		$full_path = apply_filters( 'colibri_page_builder/import_file_value_path', $full_path, $value_key );

		if ( file_exists( $full_path ) ) {
			try {
				require $full_path;
			} catch ( Exception $e ) {
				print_r( $e );
			}

			$value = get_file_value( $value_key );
		}

		return $value;
	}

	public static function import_theme_data( $theme_data ) {
		if ( $theme_data ) {
		    if (use_plugin_options()) {
                $data = extract_options_from_theme($theme_data);
                update_plugin_options_from_theme_data($data);
            }
            save_theme_data( $theme_data );
		}
	}

	public static function set_default_as_imported( $key ) {
		$import_flag = "imported.$key";
		set_theme_path( $import_flag, true );
	}

	public static function unset_default_as_imported( $key ) {
		$import_flag = "imported.$key";
		set_theme_path( $import_flag, false );
	}

	public static function maybe_import_available_partials( $partials ) {
		foreach ( (array) $partials as $partial_key ) {
			$path_array = explode( "/", $partial_key );
			$imported   = self::default_is_imported( $partial_key );
			if ( ! $imported ) {
				$partial_type    = $path_array[0];
				$default_for_key = $path_array[1];

				$partial_name = get_partial_default_for_key( $partial_type,
					$default_for_key );
				$partial_import_data
				              = self::get_partial_file_value( $partial_key,
					$partial_name );

				if ( $partial_import_data ) {
					$handled = apply_filters( 'colibri_page_builder/handled_partial_import', false, $partial_key, $partial_import_data );
					if ( $handled ) {
						continue;
					}

					$partial_post_id = create_empty_partial( $partial_type );

					$processed_data
						        = self::process_partial_data( $partial_import_data,
						self::get_next_style_ref_id(), $partial_post_id );
					$final_data = $processed_data['new'];

                    self::save_to_options($final_data);

					$partial_data = $final_data['partial']['data'];

					// not used anymore
					unset( $partial_data['css'] );

					init_empty_partial( $partial_post_id, $partial_type, $partial_data, $default_for_key, $partial_name, true );

					self::update_partial_visibility_id( $partial_import_data['partial']['id'], $partial_post_id );
					self::set_default_as_imported( $partial_key );
				}
			}
		}
	}

    public static function append_in_plugin_option($option_name, $to_append ) {
        $option_data = get_plugin_option($option_name, "{}");

        $old_value = $option_data;
        $is_string = is_string( $option_data );
        if ( $is_string ) {
            $old_value = json_decode( $option_data, true );
        }

        $new_value = array_merge( $old_value, $to_append );

        if ( $is_string ) {
            $new_value = json_encode( $new_value );
        }

        set_plugin_option( $option_name, $new_value );
        return $new_value;
    }

    public static function merge_in_plugin_option($option_name, $values_by_keys ) {
        $option_data = get_plugin_option($option_name, array());

        foreach ( $values_by_keys as $key => $value ) {
            $old_value = array_get_value( $option_data, $key, array() );
            $new_value = (array)$old_value + (array)$value;
            array_set_value( $option_data, $key, $new_value );
        }

        set_plugin_option( $option_name, $option_data );

        return $option_data;
    }

    public static function save_to_options( $data ) {

        self::append_in_plugin_option(ColibriOptionsIds::RULES, $data['rules']);
        self::merge_in_plugin_option(ColibriOptionsIds::CSS_BY_PARTIAL_ID, $data['partialCss']);
        self::merge_in_plugin_option(ColibriOptionsIds::CSS_BY_RULE_ID, $data['cssById']);

    }

	public static function get_partial_file_value( $id, $key ) {
		return self::get_file_value( 'partials/' . $id, 'partial_data' );
	}

	public static function process_partial_data(
		$partial_file_data,
		$next_style_ref = 0,
		$partial_post_id = null
	) {
		$partial     = $partial_file_data['partial'];
		$new_partial = $partial;

		$partial_data = $partial['data'];
		$rules        = array_get_value( $partial_file_data, [ 'rules' ], array() );
		$css_by_id    = $partial_file_data['cssById'];
		$partial_css  = $partial_file_data['partialCss'];

		$old_partial_post_id = $partial['id'];

		$json = $partial_data['json'];
		$html = $partial_data['html'];

		self::$temp_data = array(
			"next_style_ref" => $next_style_ref,
			'refs_map'       => array()
		);

		$new_rules = array();
		foreach ( $rules as $rule ) {
			$new_rule       = $rule;
			$new_rule['id'] = self::get_new_style_id( $rule['id'] );
			array_push( $new_rules, $new_rule );
		}

		$new_json = preg_replace_callback( '/styleRef":["]?([^,\}"]+)["]?/i',
			function ( $m ) {
				$current_id = $m[1];
				$next_id    = self::get_new_style_id( $current_id );

				return 'styleRef":' . $next_id;
			}, $json );

		$refs_map = self::$temp_data['refs_map'];

		$new_html = $html;
		foreach ( $refs_map as $old_ref => $new_ref ) {
			$new_html = preg_replace( '/([^\-])style\-' . $old_ref . '([^\d])/i',
				'$1{{#style}}-' . $new_ref . '$2', $new_html );
		}

		$new_html = preg_replace( '/\{\{#style\}\}/i', 'style', $new_html );

		$new_css_by_id = array();
		foreach ( $css_by_id as $id => $css_by_media ) {
			foreach ( $css_by_media as $media => $css ) {
				$new_css = $css;
				foreach ( $refs_map as $old_ref => $new_ref ) {
					$new_css = preg_replace( '/\.style\-(' . $old_ref
					                         . ')([^\d])/i',
						'.{{#style}}-' . $new_ref . '$2', $new_css );
				}

				$new_css = preg_replace( '/\{\{#style\}\}/i', 'style', $new_css );
				array_set_value( $new_css_by_id, [ $refs_map[ $id ], $media ],
					$new_css );
			}
		}

		// replace local ids //


		$new_html = self::replace_partial_id( $new_html, $old_partial_post_id, $partial_post_id );
		$new_json = self::replace_partial_id( $new_json, $old_partial_post_id, $partial_post_id );


		$new_partial_css = array();

		foreach ( $partial_css as $id => $css_by_media ) {
			foreach ( $css_by_media as $media => $css ) {
				$new_css = $css;

				$new_css = preg_replace( '/([\-])(' . $old_partial_post_id . ')([\-])/i',
					'${1}' . $partial_post_id . '${3}', $new_css );

				$new_key = self::replace_partial_id_short( $id, $old_partial_post_id, $partial_post_id );
				array_set_value( $new_partial_css, [ $partial_post_id, $new_key, $media ],
					$new_css );
			}
		}


		array_set_value( $new_partial['data'], 'json', $new_json );
		array_set_value( $new_partial['data'], 'html', $new_html );
		array_set_value( $new_partial['data'], 'id', $partial_post_id );

		$new_meta = array();
		foreach ( $partial_data['meta'] as $key => $value ) {
			array_set_value( $new_meta, self::replace_partial_id_short( $key, $old_partial_post_id, $partial_post_id ), $value );

		}

		// update partial style refs//
		$newStyleRefs = array();
		foreach ( $refs_map as $key => $value ) {
			array_push( $newStyleRefs, intval( $value ) );
		}

		array_set_value( $new_meta, 'styleRefs', $newStyleRefs );

		array_set_value( $new_partial['data'], 'meta', $new_meta );

		$new_data = static::replace_asset_urls_placeholder( array(
			"partialCss" => $new_partial_css,
			"cssById"    => $new_css_by_id,
			"partial"    => $new_partial,
			"rules"      => $new_rules
		) );

		$result = array(
			"old" => array(
				"partialCss" => $partial_css,
				"cssById"    => $css_by_id,
				"partial"    => $partial,
				"rules"      => $rules
			),
			"new" => $new_data,
		);

		return $result;
	}

	public static function get_new_style_id( $id ) {
		$str_id = $id . "";
		if ( ! isset( self::$temp_data['refs_map'][ $str_id ] ) ) {
			self::$temp_data['refs_map'][ $str_id ] = self::$temp_data['next_style_ref'];
			self::$temp_data['next_style_ref'] ++;
		}

		return self::$temp_data['refs_map'][ $str_id ];
	}

	public static function replace_partial_id( $str, $old, $new ) {
		$new_str = preg_replace( '/((?:offcanvas\-(?:wrapper|overlay))\-)(' . $old . ')(["\-])/i',
			'${1}' . $new . '${3}', $str );
		$new_str = preg_replace( '/((?:local|dynamic)\-)(' . $old . ')(["\-])/i',
			'${1}' . $new . '${3}', $new_str );
		$new_str = preg_replace( '/(data\-colibri\-id=")(' . $old . ')(["\-])/i',
			'${1}' . $new . '${3}', $new_str );
		$new_str = preg_replace( '/((?:id|partialId|parentId)"\s*:\s*["]?)(' . $old . ')(["\-,\}])/i',
			'${1}' . $new . '${3}', $new_str );

		return $new_str;
	}

	public static function replace_partial_id_short( $str, $old, $new ) {
		$new_str = preg_replace( '/^(' . $old . ')([\-])/i', $new . '-', $str );
		$new_str = preg_replace( '/\-(' . $old . ')([\-])/i', '-' . $new . '-', $new_str );

		return $new_str;
	}

	public static function replace_asset_urls_placeholder( $data ) {

		if ( is_string( $data ) ) {
			$data = str_replace( "[colibri_theme_url]", get_template_directory_uri(), $data );
			$data = str_replace( "[colibri_builder_plugin_url]", PageBuilder::instance()->rootURL() . "/extend-builder", $data );

            $remote_import_path = apply_filters('colibri_page_builder/remote_import_slug', get_stylesheet(), null);
            if ($remote_import_path) {
                $colibri_integration_assets_url = 'https://content.colibriwp.com/themes/' . $remote_import_path;
                $data = preg_replace_callback('/\[colibri_import_asset_url\]\/assets\/.*?\.[\w]+/', function($matches) use ($colibri_integration_assets_url) {
                    $url = $matches[0];
                    $url = str_replace("[colibri_import_asset_url]", $colibri_integration_assets_url, $url);
                    $imported = import_colibri_image($url);
                    if ($imported) {
                        return $imported['url'];
                    }
                    return $url;
                }, $data);
            }
		}


		if ( is_array( $data ) ) {

			if ( $json = array_get_value( $data, 'partial.data.json', false ) ) {
				$json = json_decode( $json, true );
				if ( json_last_error() === JSON_ERROR_NONE ) {
					$json = static::replace_asset_urls_placeholder( $json );
					array_set_value( $data, 'partial.data.json', json_encode( $json ) );
				}
			}

			foreach ( $data as $key => $value ) {
				$data[ $key ] = static::replace_asset_urls_placeholder( $value );
			}


		}

		return $data;
	}

	public static function get_next_style_ref_id() {
		$rules  = get_sheet_rules();
		$max_id = 0;
		foreach ( $rules as $rule ) {
			$max_id = max( $max_id, intval( $rule['id'] ) );
		}

		return $max_id + 1;
	}


	public static function update_partial_visibility_id( $old_id, $new_id ) {
		$theme_data = get_theme_data();


		$partials = array_get_value( $theme_data, "global.visible_partials", array() );

		foreach ( $partials as $type => $partials_visibility ) {
			foreach ( $partials_visibility as $id => $visibility ) {
				if ( $old_id === $id ) {
					unset( $partials[ $type ][ $id ] );
					$partials[ $type ][ $new_id ] = $visibility;
					break;
				}
			}
		}

		array_set_value( $theme_data, "global.visible_partials", $partials );

		save_theme_data( $theme_data, false );

		return $theme_data;
	}

	public static function import_defaults( $file ) {
		$full_path = __DIR__ . "/import/$file.php";
		if ( file_exists( $full_path ) ) {
			try {
				require_once $full_path;
			} catch ( Exception $e ) {
				print_r( $e );
			}

			$theme_default = get_file_value( $file );

			if ( isset( $theme_default['theme'] ) ) {
				save_theme_data( $theme_default['theme'] );
			}

			if ( isset( $theme_default['partials'] ) ) {
				$partialsByType = $theme_default['partials'];

				foreach ( $partialsByType as $partial_name => $default_for ) {
					foreach (
						$default_for as $default_for_key => $partial_posts
					) {
						if ( count( $partial_posts ) ) {
							$partial_data = $partial_posts[0]['data'];
							create_default_partial( $partial_name,
								$partial_data,
								$default_for_key,
								get_partial_default_for_key( $partial_name,
									$default_for_key ), true );
						}
					}
				}
			}
		}
	}

}

