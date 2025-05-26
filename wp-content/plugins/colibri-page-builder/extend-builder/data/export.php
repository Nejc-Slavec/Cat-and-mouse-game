<?php

namespace ExtendBuilder;

use ColibriWP\Theme\Core\Utils;

class Export {

	public static function export_partials_defaults( $replace_assets = true ) {
		$theme_data = \ExtendBuilder\get_theme_data();

		$default_partials
			= \ExtendBuilder\get_theme_data( "defaults.partials" );

		$zipFile = __DIR__ . '/' . time() . '.zip';

		$zip = new \ZipArchive();
		$zip->open( $zipFile, \ZipArchive::CREATE );

		$default_partials = apply_filters( 'colibri_page_builder/default_partials', $default_partials );

		$colibri_partials_types = partials_types();
		foreach ( $colibri_partials_types as $partial_type => $default_for ) {
			if ( isset( $default_partials[ $partial_type ] ) ) {
				$to_save     = $default_partials[ $partial_type ];
				$to_save_ids = array();
				foreach ( $to_save as $name => $id ) {
					if ( $id !== - 1 ) {
						$to_save_ids[ $id ] = true;
					}
				}

				foreach ( $default_for as $default_for_key ) {
					$partials_of_type
						= \ExtendBuilder\get_partials_of_type( "$partial_type",
						$default_for_key );

					$partial_to_save = null;

					foreach ( $partials_of_type as $key => $partial_obj ) {
						$id = $partial_obj['id'];
						if ( isset( $to_save_ids[ $id ] ) ) {
							$partial_to_save = $partial_obj;
						}
					}

					if ( $partial_to_save ) {
						$partial_string
							= self::export_single_partial_file( $partial_to_save,
							$theme_data, $zip, $replace_assets );
						$zip->addFromString( 'partials/' . $partial_type . "/"
						                     . $default_for_key
						                     . ".php", $partial_string
						);
					}
				}
			}
		}

		$theme_key  = Import::$theme_default_data_key;
		$theme_data = self::export_theme_data( $theme_key, $zip, $replace_assets );

		$zip->addFromString( "$theme_key.php",
			$theme_data );

		$zip->close();

		return $zipFile;
	}

	public static function export_single_partial_file( $partial, $theme, $zip = null, $replace_assets = true ) {
		$refs      = array();
		$rules     = json_decode( $theme['rules'], true );
		$css_by_id = $theme['cssById'];

		$partial_json = json_decode( $partial['data']['json'], true );
		self::export_style_refs( $partial_json, $refs );

		$partial_id  = $partial['id'];
		$partial_css = array_get_value( $theme['cssByPartialId'], $partial_id,
			array() );

		$refs = array_unique( $refs );

		$rules_by_id = self::map_rules_by_id( $rules );
		$p_rules     = [];
		$p_css_by_id = [];

		foreach ( $refs as $ref ) {
			if ( isset( $css_by_id[ $ref ] ) ) {
				$p_css_by_id[ $ref ] = $css_by_id[ $ref ];
			}
			if ( isset( $rules_by_id[ $ref ] ) ) {
				array_push( $p_rules, $rules_by_id[ $ref ] );
			}
		}

		$data = array(
			'partial'    => $partial,
			'partialCss' => $partial_css,
			'styleRefs'  => $refs,
			'rules'      => $p_rules,
			'cssById'    => $p_css_by_id
		);

		if ( $replace_assets ) {
			$data = static::replace_asset_urls_in_partials( $data, $zip );
		}

		return self::generate_export_to_file( 'partial_data', $data );
	}

	public static function export_style_refs( $obj, &$refs ) {
		if ( isset( $obj['styleRef'] ) ) {
			array_push( $refs, $obj['styleRef'] );
		}

		if ( is_array( $obj ) ) {
			foreach ( $obj as $key => $child ) {
				self::export_style_refs( $child, $refs );
			}
		}
	}

	public static function map_rules_by_id( $rules ) {
		$by_id = array();
		foreach ( $rules as $rule ) {
			$by_id[ $rule['id'] ] = $rule;
		}

		return $by_id;
	}

	public static function replace_asset_urls_in_partials( $partial_data, $zip ) {

		$json = array_get_value( $partial_data, 'partial.data.json', '{}' );

		$html = array_get_value( $partial_data, 'partial.data.html', '' );
		array_set_value( $partial_data, 'partial.data.html', '' );
		//convert json into an array
		$json = json_decode( $json, true );

		if ( ! is_array( $json ) ) {
			$json = json_decode( wp_unslash( $json ), true );
		}

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			$json = false;
		}

		$find_in = array();

		if ( $json ) {
			array_set_value( $find_in, 'partial.data.json', $json );
			array_set_value( $partial_data, 'partial.data.json', $json );
		}

		array_set_value( $find_in, 'rules', array_get_value( $partial_data, 'rules', array() ) );

		$found = static::find_assets_in_array( $find_in );

		$images_map = colibri_cache_get( 'export_partials_images_map', array() );

		foreach ( $found as $key => $asset_data ) {
			$url     = $asset_data['url'];
			$path    = $asset_data['path'];
			$new_url = $url;

			if ( strpos( $url, "wp-content/themes/colibri-wp" ) !== false ) {
				// file is a theme resource
				$new_url = preg_replace( "#(.*)wp-content/themes/colibri-wp#", "[colibri_theme_url]", $url );
			} else {

				$image_content = null;
				if ( isset( $images_map[ $url ] ) ) {
					$new_url = $images_map[ $url ]['url'];
				} else {
					$response = wp_remote_get( $url );

					if ( ! is_wp_error( $response ) && 200 == wp_remote_retrieve_response_code( $response ) ) {
						$image_content = wp_remote_retrieve_body( $response );
						$hash          = sha1( base64_encode( $image_content ) );
						$hash_found    = false;
						foreach ( $images_map as $map_url => $map_data ) {
							if ( $map_data['hash'] === $hash ) {
								$new_url    = $images_map[ $map_url ]['url'];
								$hash_found = true;
								break;
							}
						}

						if ( ! $hash_found ) {
							$parsed_url     = parse_url( $url );
							$pathinfo       = pathinfo( $parsed_url['path'] );
							$extension      = $pathinfo['extension'];
							$image_name     = str_replace( ".{$extension}", "", urldecode( $pathinfo['basename'] ) );
							$image_name     = Utils::slugify( $image_name );
							$new_image_path = "assets/partials-images/" . $image_name . ".{$extension}";
							$new_url        = "[colibri_builder_plugin_url]/$new_image_path";
							/** @var \ZipArchive $zip */
							$zip->addFromString( $new_image_path, $image_content );
							$images_map[ $url ] = array(
								'url'  => $new_url,
								'hash' => $hash
							);
						}
					}
				}

			}

			colibri_cache_set( 'export_partials_images_map', $images_map );
			$found[ $key ]['new_url'] = $new_url;
			array_set_value( $partial_data, $path, $new_url );

		}


		foreach ( $found as $key => $found_data ) {
			$html          = str_replace( $found_data['url'], $found_data['new_url'], $html );
			$unescaped_url = str_replace( "&", "&amp;", $found_data['url'] );
			if ( $unescaped_url !== $found_data['url'] ) {
				$html = str_replace( $unescaped_url, $found_data['new_url'], $html );
			}

		}

		foreach ( $partial_data['partialCss'] as $css_key => $css_data ) {
			foreach ( $css_data as $media_key => $value ) {

				foreach ( $found as $key => $found_data ) {
					$value         = str_replace( $found_data['url'], $found_data['new_url'], $value );
					$unescaped_url = str_replace( "&", "&amp;", $found_data['url'] );
					if ( $unescaped_url !== $found_data['url'] ) {
						$value = str_replace( $unescaped_url, $found_data['new_url'], $value );
					}

				}
				$partial_data['partialCss'][ $css_key ][ $media_key ] = $value;
			}
		}


		foreach ( $partial_data['cssById'] as $css_key => $css_data ) {
			foreach ( $css_data as $media_key => $value ) {

				foreach ( $found as $key => $found_data ) {
					$value         = str_replace( $found_data['url'], $found_data['new_url'], $value );
					$unescaped_url = str_replace( "&", "&amp;", $found_data['url'] );
					if ( $unescaped_url !== $found_data['url'] ) {
						$value = str_replace( $unescaped_url, $found_data['new_url'], $value );
					}

				}
				$partial_data['cssById'][ $css_key ][ $media_key ] = $value;
			}
		}

		array_set_value( $partial_data, 'partial.data.html', $html );


		// put back as json
		if ( $json ) {
			$json = array_get_value( $partial_data, 'partial.data.json', (object) array() );
			array_set_value( $partial_data, 'partial.data.json', json_encode( $json ) );
		}

		return $partial_data;
	}

	public static function find_assets_in_array( $data, $path = "", $found = array() ) {
		if ( ! $path ) {
			$path_parts = array();
		} else {
			$path_parts = explode( ".", $path );
		}

		if ( is_string( $data ) ) {
			$image_regexp = "(https?://[^/\s]+/\S+\.(jpg|png|gif|mp4))";
			preg_match( $image_regexp, $data, $matches );
			if ( count( $matches ) ) {
				$match   = $matches[0];
				$found[] = array(
					"path" => $path,
					"url"  => $data
				);
			}
		}

		if ( is_array( $data ) ) {
			foreach ( $data as $key => $value ) {
				$new_path = $path_parts;
				array_push( $new_path, $key );
				$found = static::find_assets_in_array( $value, implode( ".", $new_path ), $found );
			}
		}


		return $found;
	}

	public static function generate_export_to_file( $key, $data ) {
		$value = var_export( $data, true );
		$result
		       = '<?php namespace ExtendBuilder; load_file_value(\'' . $key
		         . '\', ' . $value . ');';

		return $result;
	}

	public static function export_theme_data( $key, $zip = null, $replace_assets = true ) {
		$theme_data = \ExtendBuilder\get_theme_data();

		unset( $theme_data['imported'] );

		unset( $theme_data['rules'] );
		unset( $theme_data['cssById'] );


		if ( $replace_assets ) {
			$theme_data = static::replace_asset_urls_in_theme_data( $theme_data, $zip );
		}


		return self::generate_export_to_file( $key, $theme_data );
	}

	public static function replace_asset_urls_in_theme_data( $theme_data, $zip ) {
		$found = static::find_assets_in_array( $theme_data );


		return $theme_data;
	}

	public static function export_defaults( $file_path = "" ) {
		$theme_data = \ExtendBuilder\get_theme_data();
		$partials   = array();

		$default_partials
			= \ExtendBuilder\get_theme_data( "defaults.partials" );

		$colibri_partials_types = partials_types();
		foreach ( $colibri_partials_types as $partial_type => $default_for ) {
			if ( isset( $default_partials[ $partial_type ] ) ) {
				$to_save     = $default_partials[ $partial_type ];
				$to_save_ids = array();
				foreach ( $to_save as $name => $id ) {
					if ( $id !== - 1 ) {
						$to_save_ids[ $id ] = true;
					}
				}

				foreach ( $default_for as $default_for_key ) {
					$partials_of_type
						                      = \ExtendBuilder\get_partials_of_type( "$partial_type",
						$default_for_key );
					$partials_of_type_to_save = array();

					foreach ( $partials_of_type as $key => $partial_obj ) {
						$id = $partial_obj['id'];
						if ( isset( $to_save_ids[ $id ] ) ) {
							array_push( $partials_of_type_to_save,
								$partial_obj );
						}
					}

					\ExtendBuilder\array_set_value( $partials,
						$partial_type . "." . $default_for_key,
						$partials_of_type_to_save );
				}
			}
		}

		unset( $theme_data['imported'] );

		$export = array(
			"theme"    => $theme_data,
			"partials" => $partials,
		);

		$result
			= '<?php namespace ExtendBuilder; load_file_value(\'theme_default\', '
			  . var_export( $export, true ) . ');';

		if ( $file_path ) {
			file_put_contents( $file_path, $result );
		}

		return $result;
	}
}
