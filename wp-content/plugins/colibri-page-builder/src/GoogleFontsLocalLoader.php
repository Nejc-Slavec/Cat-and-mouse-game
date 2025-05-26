<?php

namespace ColibriWP\PageBuilder;

use function ExtendBuilder\array_get_value;
use function ExtendBuilder\array_set_value;

class GoogleFontsLocalLoader {

	private static $instance = null;

	private $queries_transient = 'colibri_local_google_queries_transient';
	private $font_file_action  = 'colibri_get_google_font_file';
	private $fonts_css_action  = 'colibri_get_google_font_css';

	private $uploads_dir = 'colibri-google-fonts-cache';

	private $google_css_url  = 'https://fonts.googleapis.com/css';
	private $google_font_url = 'https://fonts.gstatic.com/s';
	private $user_agent      = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:97.0) Gecko/20100101 Firefox/97.0';

	private $local_fonts_dir;
	private $local_fonts_url;


	/**
	 *
	 * @return GoogleFontsLocalLoader
	 */
	public static function getInstance() {
		if ( ! static::$instance ) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	public function __construct() {

		$upload_dir  = wp_upload_dir();
		$upload_path = untrailingslashit( $upload_dir['basedir'] );

		$this->local_fonts_dir = "{$upload_path}/{$this->uploads_dir}";
		$this->local_fonts_url = "{$upload_dir['baseurl']}/{$this->uploads_dir}";

		if ( ! file_exists( $this->local_fonts_dir ) ) {
			mkdir( $this->local_fonts_dir, 0777, true );
		}
	}


	public function resolveFontsCSS() {
		$action = array_get_value( $_REQUEST, 'action' );

		if ( $action !== $this->fonts_css_action ) {
			return;
		}

		header( 'Content-type: text/css' );
		header( 'Cache-control: public' );

		$key    = sanitize_text_field(array_get_value( $_REQUEST, 'key', '' ));
		$cached = $this->getCachedDataByKey( $key );

		if ( ! $cached ) {
			die( '' );
		}

		$query = array_get_value( $cached, 'query' );
		$css   = array_get_value( $cached, 'css' );

		if ( ! $css ) {
			$css = $this->getCSS( $query );
			$this->cacheQueryCSS( $query, $css );
		}

		$css = $this->replacePlaceholdersWithLocalCSS( $css );

//		if ( Utils::isDebug() ) {
//			$css = "/* {$this->google_css_url}?family={$query} */\n\n{$css}";
//		}

        //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		die( $css );

	}

	private function getCSS( $query, $replace_urls = true ) {
		$fonts_url = add_query_arg(
			array(
				'family'  => urlencode( $query ),
				'display' => 'swap',
			),
			$this->google_css_url
		);

		$response = wp_remote_get(
			$fonts_url,
			array(
				'user-agent' => $this->user_agent,
			)
		);

		if ( is_wp_error( $response ) ) {
			return null;
		}

		if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return null;
		}

		$css = wp_remote_retrieve_body( $response );

		if ( $replace_urls ) {
			$css = $this->replaceGoogleURLS( $css );
		}

		return $css;
	}

	private function replaceGoogleURLS( $css ) {

		$google_font_url = $this->google_font_url;
		$css             = preg_replace_callback(
			'#url\((.*?)\)#',
			function( $matches ) use ( $google_font_url ) {
				$url = str_replace( $google_font_url, '', array_get_value( $matches, 1, '' ) );

				return "url({{{{$url}}}})";
			},
			$css
		);

		return $css;
	}

	public function getLocalFontFilePath( $font_file ) {
		$file_key = md5( $font_file );
		return "{$this->local_fonts_dir}/{$file_key}.woff2";
	}

	public function getLocalFontFileURL( $font_file ) {
		$file_key = md5( $font_file );
		return "{$this->local_fonts_url}/{$file_key}.woff2";
	}

	public function saveFontContentToLocalFile( $font_file, $content ) {
		$path = $this->getLocalFontFilePath( $font_file );
		return file_put_contents( $path, $content );
	}

	public function localFontFileExists( $font_file ) {
		return file_exists( $this->getLocalFontFilePath( $font_file ) );
	}

	private function replacePlaceholdersWithLocalCSS( $css ) {
		$self   = $this;
		$action = $this->font_file_action;

		$css = preg_replace_callback(
			'#url\(\{\{\{(.*?)\}\}\}\)#',
			function( $matches ) use ( $self, $action ) {
				$font_file = array_get_value( $matches, 1, '' );

				if ( $self->localFontFileExists( $font_file ) ) {

					$url = $self->getLocalFontFileURL( $font_file );
				} else {
					$url = add_query_arg(
						array(
							'font'     => urlencode( $font_file ),
							'action'   => $action,
							'security' => $self->createSecurityKey( "{$action}_{$font_file}" ),
						),
						admin_url( 'admin-ajax.php' )
					);
				}

				return "url({$url})";
			},
			$css
		);

		return $css;

	}

	public function getCachedDataByKey( $key ) {
		$transient = get_transient( $this->queries_transient );
		if ( ! is_array( $transient ) ) {
			return null;
		}
		return array_get_value( $transient, $key );
	}

	public function getCachedQueryData( $query ) {
		return  $this->getCachedDataByKey( md5( $query ) );
	}

	public function cacheQueryCSS( $query, $css ) {

		$transient = get_transient( $this->queries_transient );
		if ( ! is_array( $transient ) ) {
			$transient = array();
		}

		array_set_value(
			$transient,
			md5( $query ),
			array(
				'query' => $query,
				'css'   => $css,
			)
		);

		set_transient( $this->queries_transient, $transient );
	}

	public function addQueryToCache( $query ) {
		$transient = get_transient( $this->queries_transient );
		if ( ! is_array( $transient ) ) {
			$transient = array();
		}

		$key = md5( $query );

		if ( isset( $transient[ $key ] ) ) {
			return;
		}

		array_set_value(
			$transient,
			$key,
			array(
				'query' => $query,
			)
		);
		set_transient( $this->queries_transient, $transient );
	}


	public function enqueueFonts( $query ) {
		$cached = $this->getCachedQueryData( $query );
		if ( ! $cached ) {
			$this->addQueryToCache(
				$query,
				array(
					'query' => $query,
				)
			);
		}

		wp_enqueue_style(
			'colibri-local-google-fonts',
			add_query_arg(
				array(
					'action' => $this->fonts_css_action,
					'key'    => md5( $query ),
				),
				site_url()
			)
		);
	}


	public function getFontsMap( $query, $subset = 'latin' ) {

		$css = $this->getCSS( $query, false );

//		if ( $subset === 'all' ) {
//			$subset_regex = '/\/\*([^*\/]*)\*\//i';
//			preg_match_all( $subset_regex, $css, $matches, PREG_SET_ORDER );
//			$subsets_list = array();
//			foreach ( $matches as $match ) {
//				$current_subset = trim( $match[1] );
//				if ( ! in_array( $current_subset, $subsets_list ) ) {
//					$subsets_list[] = $current_subset;
//				}
//			}
//			$all_fonts = array();
//			foreach ( $subsets_list as $subset_item ) {
//				$fonts     = $this->getFontsMap( $query, $subset_item );
//				$all_fonts = array_merge( $all_fonts, $fonts );
//			}
//
//			//sorts fonts faces
//			usort(
//				$all_fonts,
//				function( $a, $b ) {
//					$order = ['font-family', 'font-style', 'font-weight', 'subset'];
//					foreach($order as $property) {
//						$diff = strcmp($a[$property], $b[$property]);
//						if( $diff!== 0) {
//							return $diff;
//						}
//					}
//					//if no difference were found return 0
//					return 0;
////
////					return array( $a['font-family'], $a['font-style'], $a['font-weight'], $a['subset'] )
////					<=>
////					array( $b['font-family'], $b['font-style'], $b['font-weight'], $b['subset'] );
//				}
//			);
//			return $all_fonts;
//		}

		// prepare subset
		$css = preg_replace( '#/\*\s+?(' . $subset . ")\s+?\*/(.*\n?)@font-face#", 'is_subset_match', $css );

		// remove comments
		$css = preg_replace( '#/\*(.*?)\*/#', '', $css );
		$css = preg_replace( '#format\((.*?)\)#', '', $css );
		$css = preg_replace( '#url\(https://(.*?)\)#', '$1', $css );

		$re = '/is_subset_match.*{\K[^}]*(?=})/';
		preg_match_all( $re, $css, $matches, PREG_SET_ORDER );

		$parsed = array();
		$keys   = array( 'font-family', 'src', 'font-style', 'font-weight', 'unicode-range' );
		if ( $matches ) {

			foreach ( $matches as $k => $ff ) {

				$css   = $ff[0];
				$attrs = explode( ';', $css );

				$props = array();
				foreach ( $attrs as $attr ) {
					if ( strlen( trim( $attr ) ) > 0 ) {
						$pair = explode( ':', trim( $attr ) );
						if ( in_array( $pair[0], $keys ) ) {
							$value = trim( $pair[1] );

							if ( $pair[0] === 'font-family' ) {
								$value = str_replace( "'", '', $value );
							}

							if ( $pair[0] === 'font-weight' ) {
								$value = intval( $value );
							}

							if ( $pair[0] === 'src' ) {
								$value = "https://{$value}";
							}

							$props[ trim( $pair[0] ) ] = $value;
						}
					}
				}
				$props['subset'] = $subset;
				$parsed[ $k ]    = $props;
			}
		}

		return $parsed;
	}

	public function resolveFont() {

		$font_file    = sanitize_text_field( array_get_value( $_REQUEST, 'font', '' ) );
		$security_key = sanitize_text_field( array_get_value( $_REQUEST, 'security', '' ) );

		$valid_nonce = $this->verifySecurityKey( $security_key, "{$this->font_file_action}_{$font_file}" );

		if ( ! $valid_nonce ) {
            //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			wp_die( __( 'Frobidden', 'colibri' ), 403 );
		}

		$content = $this->resolveFontFileContent( $font_file );

		if ( is_wp_error( $content ) ) {
            //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			wp_die( $content, 404 );
		}

		$this_year = strtotime( date( 'Y' ) . '-01-01' );
		header( 'Content-type: font/woff2' );
		header( 'Cache-control: public' );
		header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s', $this_year ) . ' GMT' );
		header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', $this_year + YEAR_IN_SECONDS ) . ' GMT' );
		header( 'Etag: ' . md5( base64_encode( $content ) ) );

        //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		die( $content );
	}

	private function resolveFontFileContent( $font_file ) {
		if ( $this->localFontFileExists( $font_file ) ) {
			return file_get_contents( $this->getLocalFontFilePath( $font_file ) );
		}

		if ( ! is_writable( $this->local_fonts_dir ) ) {
			return new \WP_Error( 'folder_not_writable' );
		}

		$google_font_url = "{$this->google_font_url}/{$font_file}";

		$reponse = wp_remote_get( $google_font_url );
		if ( is_wp_error( $reponse ) ) {
			return new \WP_Error( 'could_not_retrieve_url' );
		}

		$content = wp_remote_retrieve_body( $reponse );

		$this->saveFontContentToLocalFile( $font_file, $content );

		return  $content;

	}

	private function getSecuritySalt() {
		if ( defined( 'NONCE_KEY' ) ) {
			return NONCE_KEY;
		}

		if ( defined( 'SECURE_AUTH_KEY' ) ) {
			return SECURE_AUTH_KEY;
		}

		if ( defined( 'AUTH_KEY' ) ) {
			return AUTH_KEY;
		}

		if ( defined( 'SECURE_AUTH_SALT' ) ) {
			return SECURE_AUTH_SALT;
		}

		if ( defined( 'AUTH_SALT' ) ) {
			return AUTH_SALT;
		}

//		$pro_activation_time = Flags::get( 'colibri_pro_activation_time' );
//
//		if ( $pro_activation_time ) {
//			return $pro_activation_time;
//		}
//
//		$activation_time = Flags::get( 'colibri_activation_time' );
//
//		if ( $activation_time ) {
//			return $activation_time;
//		}

		return uniqid( time() );
	}

	public function createSecurityKey( $action ) {
		return wp_hash( $this->getSecuritySalt() . '|' . $action );
	}

	public function verifySecurityKey( $nonce, $action ) {
		return $nonce === $this->createSecurityKey( $action );
	}


	public function addAdminAjaxActions() {
		add_action( "wp_ajax_{$this->font_file_action}", array( $this, 'resolveFont' ) );
		add_action( "wp_ajax_nopriv_{$this->font_file_action}", array( $this, 'resolveFont' ) );

		add_action( 'plugins_loaded', array( $this, 'resolveFontsCSS' ) );

	}

	public static function enqueuLocalGoogleFonts( $fonts_query ) {
		return  GoogleFontsLocalLoader::getInstance()->enqueueFonts( $fonts_query );
	}


	public static function registerFontResolver() {
		return GoogleFontsLocalLoader::getInstance()->addAdminAjaxActions();
	}

}
