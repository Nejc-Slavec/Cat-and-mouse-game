<?php


namespace ColibriWP\PageBuilder\Notify;


class NotificationsManager {
	const DISMISSED_NOTIFICATIONS_OPTION      = "cp_dismissed_notifications";
	const INITIALIZATION_NOTIFICATIONS_OPTION = "cp_initialize_notifications";


	private static $remote_data_url_base = "http://extendthemes.com/wp-json/extendthemes/v1/notifications";

	public static function requestTimeout( $value ) {
		return 30;
	}

	public static function getRemoteNotifications() {
		check_ajax_referer('extendthemes_get_remote_data_notifications_nonce');
		$transientKey  = apply_filters( 'extendthemes_demo_import_transient_key', get_template() . '_notifications' );
		$notifications = null;
		if ( ! NotificationsManager::isDevMode() ) {
			$notifications = get_transient( $transientKey );
		}

		if ( ! $notifications ) {
			add_filter( 'http_request_timeout', array( __CLASS__, 'requestTimeout' ) );
			$data = wp_remote_get( NotificationsManager::getRemoteNotificationsURL(), array(
				'body' => array(),
			) );
			remove_filter( 'http_request_timeout', array( __CLASS__, 'requestTimeout' ) );

			if ( $data instanceof \WP_Error ) {
				if ( NotificationsManager::isDevMode() ) {
                    //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					die( $data->get_error_message() );
				}
			} else {
				$data = json_decode( $data['body'], true );

				if ( isset( $data['code'] ) && $data['code'] === 'extendthemes_notifications_data_ok' ) {
					$notifications = $data['data'];
					set_transient( $transientKey, $data['data'], 1800 );
				}

			}
		}

		wp_json_encode( $notifications );
	}

	public static function isDevMode() {
		return ( defined( 'EXTENDTHEMES_NOTIFICATIONS_DEV_MODE' ) && EXTENDTHEMES_NOTIFICATIONS_DEV_MODE );
	}

	public static function getRemoteNotificationsURL() {
		$dev_mode                       = NotificationsManager::isDevMode();
		$base                           = NotificationsManager::$remote_data_url_base;
        $theme                          = get_template();
        $option                         = "{$theme}_start-source";
        $start_source                   = get_option($option, "other");
        $builder_activation_time        = get_option('colibri_page_builder_activation_time', "0");
        $builder_pro_activation_time    = get_option('colibri_page_builder_pro_activation_time', "0");

		$query = array(
            'theme'                 => apply_filters( 'mesmerize_notifications_template_slug', $theme ),
            'stylesheet'            => apply_filters( 'mesmerize_notifications_stylesheet_slug', get_stylesheet() ),
            'license'               => urlencode( '' ),
            'dev_mode'              => $dev_mode ? "1" : "0",
            'source'    => $start_source,
            'activated_on'     => $builder_activation_time,
            'pro_activated_on' => $builder_pro_activation_time,
            'locale' => get_locale()
        );

		$query_string = build_query( $query );

		if ( $query_string ) {
			$query_string = "?" . $query_string;
		}

		return apply_filters( 'extendthemes_notifications_url', $base . $query_string, $base, $query );

	}

	public static function load( $notifications ) {

        add_action( "wp_ajax_extendthemes_get_remote_data_notifications", array(
            __CLASS__,
            'getRemoteNotifications'
        ) );
        if ( ! wp_next_scheduled( NotificationsManager::class . '::getRemoteNotifications' ) ) {
            wp_schedule_event( time(), 'twicedaily', NotificationsManager::class . '::getRemoteNotifications' );
        }

		if ( ! is_admin() ) {
			return;
		}
		static::initializationTS();

		if ( ! get_option( 'cp_initialize_notifications', false ) ) {
			update_option( 'cp_initialize_notifications', time() );
		}

		$notifications = NotificationsManager::addRemoteNotifications( $notifications );
		$notifications = apply_filters( 'cp_load_notifications', $notifications );

		foreach ( $notifications as $notification ) {
			new Notification( $notification );
		}

		if ( count( $notifications ) ) {
			add_action( 'admin_head', function () {
				?>
                <style type="text/css">
                    .cp-notification {
                        padding-top: 0rem;
                        padding-bottom: 0rem;
                    }

                    .cp-notification-card {
                        padding: 30px 20px;
                        margin: 0px 10px 20px 10px;
                        display: inline-block;
                        background: #fff;
                        box-shadow: 0 1px 20px 5px rgba(0, 0, 0, 0.1);
                    }

                    .cp-notification-card:first-of-type {
                        margin-left: 0px;
                    }

                    .cp-notification-card:last-of-type {
                        margin-right: 0px;
                    }

                </style>
				<?php
			} );

			add_action( 'wp_ajax_cp_dismiss_notification', array( __CLASS__, 'dismissNotification' ) );
		}

		add_action( 'admin_footer', function () {
			$transientKey  = apply_filters( 'extendthemes_demo_import_transient_key', get_template() . '_notifications' );
			$notifications = get_transient( $transientKey );

			if ( is_array( $notifications ) && ! NotificationsManager::isDevMode() ) {
				return;
			}

			?>
            <script>
                jQuery.post(
                    "<?php echo admin_url( "/admin-ajax.php" ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>",
                    {
                        action: "extendthemes_get_remote_data_notifications",
                        _wpnonce: '<?php echo wp_create_nonce('extendthemes_get_remote_data_notifications_nonce'); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped?>'
                    }
                )
            </script>
			<?php
		} );

	}

	public static function initializationTS() {
		$time = get_option( static::INITIALIZATION_NOTIFICATIONS_OPTION, false );
		if ( ! $time ) {
			$time = time();
			update_option( static::INITIALIZATION_NOTIFICATIONS_OPTION, $time );
		}

		return ( floor( $time / 86400 ) * 86400 );
	}

	public static function addRemoteNotifications( $current_notifications ) {
		$transientKey  = apply_filters( 'extendthemes_demo_import_transient_key', get_template() . '_notifications' );
		$notifications = get_transient( $transientKey );
		$notifications = is_array( $notifications ) ? $notifications : array();

		return array_merge( $notifications, $current_notifications );
	}

	public static function dismissNotification() {
		check_ajax_referer('cp_dismiss_notification_nonce');
		if ( ! is_user_logged_in() || ! current_user_can( 'edit_theme_options' ) ) {
			die();
		}

		$notification = isset( $_REQUEST['notification'] ) ? sanitize_text_field( $_REQUEST['notification']) : false;

		if ( $notification ) {
			$dismissedNotifications = get_option( static::DISMISSED_NOTIFICATIONS_OPTION, array() );
			if ( ! in_array( $notification, $dismissedNotifications ) ) {
				$dismissedNotifications[] = $notification;
			}

			update_option( static::DISMISSED_NOTIFICATIONS_OPTION, $dismissedNotifications );
		}

	}

}
