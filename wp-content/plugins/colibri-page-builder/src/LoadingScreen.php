<?php

namespace ColibriWP\PageBuilder;

class LoadingScreen {
	private static $added   = false;
	private static $showed  = false;
	private static $message = "";

	public static function echoScreen() {
		?>
        <style>
            /* CSS LOADER START*/
            .colibri-loading-iframe {
                width: 80px;
                height: 80px;
		overflow: hidden;
            }

            .colibri-loading-overlay {
                position: fixed;
                z-index: 10000000;
                width: 100%;
                height: 100%;
                top: 0;
                left: 0;
                background: rgba(0, 0, 0, 0.68);
                display: none;
            }

            .colibri-loading-overlay.active {
                display: block;
            }

            .colibri-loading-overlay-message {
                display: inline-block;
                padding: 8px 16px;
                background: #32a8d9;
                color: #ffffff;
                border-radius: 8px;
                margin-top: 16px;
                font-size: 12px;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            }

            .colibri-loading-overlay-content {
                display: flex;
                flex-direction: column;
                align-items: center;
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
            }

            /*CSS LOADER END*/
        </style>
        <div class="colibri-loading-overlay">
            <div class="colibri-loading-overlay-content">
                <iframe class="colibri-loading-iframe"
                        allowfullscreen
                        allowtransparency=""
                        src="about:blank"></iframe>
                <span class="colibri-loading-overlay-message"></span>
            </div>
        </div>
        <script>
            (function () {
                var data = <?php echo wp_json_encode( self::getIframeContent() ); ?>;
                var doc = document.querySelector(".colibri-loading-iframe").contentWindow.document;
                doc.open();
                doc.write('<html><body>' + data + '</body></html>');
                doc.close();
                var overlay = document.querySelector('.colibri-loading-overlay');
                var msgSpan = document.querySelector('.colibri-loading-overlay-message');
                window.colibriLoadingScreen = {
                    show: function (msg) {
                        msgSpan.innerHTML = msg;
                        overlay.classList.add('active');
                    },
                    hide: function () {
                        overlay.classList.remove('active');
                    },
                }
            })();
        </script>
		<?php
	}

	public static function getIframeContent() {
		ob_start(); ?>
        <span class="colibri-loading"></span>
        <style type="text/css">
            body {
                text-align: center;
            }

            .colibri-loading {
                display: inline-block;
                border: 4px solid #e6e6e6;
                border-top: 4px solid #32a8d9;
                border-radius: 50%;
                width: 50px;
                height: 50px;
                animation: colibri-adminspin 2s linear infinite;
            }

            @keyframes colibri-adminspin {
                0% {
                    transform: rotate(0deg);
                }
                100% {
                    transform: rotate(360deg);
                }
            }
        </style>
		<?php
		return ob_get_clean();
	}

	public static function add() {
		if ( self::$added ) {
			return;
		}
		add_action( 'admin_head', array( 'ColibriWP\PageBuilder\LoadingScreen', 'echoScreen' ) );
		add_action( 'wp_head', array( 'ColibriWP\PageBuilder\LoadingScreen', 'echoScreen' ) );
		self::$added = true;
	}

	public static function printShowScript() {
		?>
        <script>window.colibriLoadingScreen.show("<?php echo esc_html( self::$message ); ?>")</script>
		<?php
	}

	public static function show( $message = "" ) {

		self::$message = $message;
		if ( ! self::$showed ) {
			add_action( 'admin_head', array( 'ColibriWP\PageBuilder\LoadingScreen', 'printShowScript' ), 11 );
			add_action( 'wp_head', array( 'ColibriWP\PageBuilder\LoadingScreen', 'printShowScript' ), 11 );
			self::$showed = true;
		}
	}
}
