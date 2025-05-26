<?php


namespace ColibriWP\PageBuilder\License;


class CheckForm {

	public function __construct() {
		add_action( "wp_ajax_colibriwp-page-builder-check-license", array( $this, 'callCheckLicenseEndpoint' ) );
	}

	public function printForm() {
		wp_enqueue_script( 'wp-util' );
		add_action( 'admin_notices', array( $this, 'makeNotice' ) );
		wp_add_inline_style( 'forms', $this->getInlineCSS() );
		wp_add_inline_script( 'jquery', $this->getInlineJS() );
	}

	public function makeNotice() {
		?>
        <div id="colibri-page-builder-check-license"
             class="colibri-page-builder-license-notice notice notice-error is-dismissible hidden">
            <h1>Colibri Page Builder PRO License</h1>
            <h3 id="colibri-page-builder-check-license-message" class="message error-message"></h3>
        </div>
		<?php
	}

	private function getInlineCSS() {
		ob_start();
		?>
        <style>

            .colibri-page-builder-license-notice.hidden {
                display: none !important;
            }

            .colibri-page-builder-license-notice {
                padding: 10px 20px;
            }

            .colibri-page-builder-license-notice h1 {
                font-size: 28px;
                font-weight: 400;
            }
        </style>
		<?php
		return strip_tags( ob_get_clean() );
	}

	private function getInlineJS() {
		ob_start();
		?>
        <script type="text/javascript">
            jQuery(function ($) {
                if(!window.wp || !window.wp.ajax){
                  return;
                }
                var notice = $("#colibri-page-builder-check-license"),
                    message = $("#colibri-page-builder-check-license-message");

                function getHTMLContent(text) {
                    var content = jQuery(document.createElement('div')).append(text);
                    content.find('input,button,script,style').remove();

                    if (content.find('body').length) {
                        return content.find('body').html();
                    }

                    return content.html();
                }

                wp.ajax
                    .send('colibriwp-page-builder-check-license', {
                        data: {
                            _wpnonce: '<?php echo wp_create_nonce( 'colibriwp-page-builder-check-license-nonce' ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped?>'
                        }
                    })
                    .fail(function (response) {
                        notice.removeClass('hidden');
                        message.html(getHTMLContent(response.responseJSON.data))
                    });
            });
        </script>
		<?php
		return strip_tags( ob_get_clean() );
	}

	public function callCheckLicenseEndpoint() {
		check_ajax_referer('colibriwp-page-builder-check-license-nonce');
		$response         = Endpoint::check();
		$response_message = $response->getMessage( true );

		if ( $response->isWPError() ) {
			$url              = esc_attr( License::getInstance()->getDashboardUrl() );
			$response_message = "There was an error calling ColibriWP License Server: <code>{$response_message}</code>. " .
			                    "Please contact us for support on <a href='https://colibriwp.com' target='_blank'>ColibriWP</a> website";
		}

		if ( $response->isSuccess() ) {
			License::getInstance()->touch();
		} else {
			if ( ! $response->isWPError() && $response->getResponseCode() === 403 ) {
				delete_option( 'colibriwp_builder_license_key' );
				$response_message = "Current license key was remove! Reason: {$response_message} ";
			}
		}

		wp_send_json( array(
			'data'    => $response_message,
			'success' => $response->isSuccess()
		), $response->getResponseCode() );
	}
}
