<?php


namespace ColibriWP\PageBuilder\License;


use Plugin_Upgrader;
use function ExtendBuilder\array_get_value;

class ActivationForm {

	public function __construct() {
		add_action( "wp_ajax_colibriwp-page-builder-activate", array( $this, 'callActivateLicenseEndpoint' ) );
		add_action( "wp_ajax_colibriwp-page-builder-maybe-install-pro", array( $this, 'maybeInstallPRO' ) );
	}

	public function printForm() {
		add_action( 'admin_notices', array( $this, 'makeActivateNotice' ) );
		$this->enqueue();
	}

	public function enqueue() {
		wp_enqueue_script( 'wp-util' );
		wp_add_inline_style( 'forms', $this->getInlineCSS() );
		wp_add_inline_script( 'jquery', $this->getInlineJS() );

	}

	public function getInlineCSS() {
		ob_start();
		?>
        <style>

            .colibri-page-builder-license-notice {
                padding: 10px 20px;
            }

            .colibri-page-builder-license-notice h1 {
                font-size: 28px;
                font-weight: 400;
            }

            .colibri-page-builder-license-notice h3 {
                font-size: 18px;
                font-weight: normal;
            }

            .colibri-page-builder-license-notice .activate-form {
                display: inline-block;
            }

            .colibri-page-builder-license-notice .form-table tr > td:first-of-type {
                padding-left: 0;
            }

            .colibri-page-builder-license-notice .form-table tr > td {
                padding-bottom: 10px;
                padding-top: 0;
            }


            .colibri-page-builder-license-notice .message {
                font-weight: bold;
                margin-top: 0;
                font-size: 16px;
                white-space: nowrap;
            }

            .colibri-page-builder-license-notice .success-message {
                color: #6ee817 !important;
            }

            .colibri-page-builder-license-notice .spinner {
                visibility: visible;
                display: block;
            }

            .colibri-page-builder-license-notice form.disabled input,
            .colibri-page-builder-license-notice form.disabled button {
                opacity: 0.5;
                pointer-events: none;
            }

        </style>
		<?php
		$content = ob_get_clean();

		return strip_tags( $content );
	}

	private function getInlineJS() {
		ob_start();
		?>
        <script type="text/javascript">
            jQuery(function ($) {
                var form = $('#colibri-page-builder-activate-license-form'),
                    message = $('#colibri-page-builder-activate-license-message'),
                    keyInput = form.find('input[type=text]'),
                    okMessage = form.siblings('.ok-message'),
                    notice = form.closest('.notice');


                function getHTMLContent(text) {
                    var content = jQuery(document.createElement('div')).append(text);
                    content.find('input,button,script,style').remove();

                    if (content.find('body').length) {
                        return content.find('body').html();
                    }

                    return content.html();
                }

                function hideMessage() {
                    message.hide();
                }

                function showErrorMessage(messageText) {
                    message.attr('class', 'message error-message');

                    message.html(getHTMLContent(messageText));
                    message.show();
                }

                function showSuccessMessage(messageText) {
                    form.hide();
                    okMessage.show().html(getHTMLContent(messageText));
                    notice.removeClass('notice-error').addClass('notice-success');
                }

                function showSpinner() {
                    form.find('.spinner-holder').show();
                }


                function hideSpinner() {
                    form.find('.spinner-holder').hide();
                }

                keyInput.on('keyup change', hideMessage);

                form.on('submit', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    var key = keyInput.val();
                    if (!key) {
                        showErrorMessage('License key is empty');
                        return;
                    }

                    hideMessage();
                    showSpinner();
                    form.addClass('disabled');
                    wp.ajax.send('colibriwp-page-builder-activate', {
                        data: {
                            key: key,
                            '_wpnonce': '<?php echo wp_create_nonce('colibriwp-page-builder-activate-nonce'); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped?>'
                        }
                    }).done(function (response) {
                        hideSpinner();
                        showSuccessMessage(response || "Activated successfully");
                    }).fail(function (response) {
                        hideSpinner();
                        form.removeClass('disabled');
                        showErrorMessage(response.responseJSON.data);
                    });
                })
            });
        </script>
		<?php
		$content = ob_get_clean();

		return strip_tags( $content );
	}

	public function getUpgradeInlineJS() {
		ob_start();
		?>
        <script type="text/javascript">
            jQuery(function ($) {
                var form = $('#colibri-page-builder-activate-license-form'),
                    message = $('#colibri-page-builder-activate-license-message'),
                    keyInput = form.find('input[type=text]'),
                    okMessage = form.siblings('.ok-message'),
                    notice = form.closest('.notice');


                function getHTMLContent(text) {
                    var content = jQuery(document.createElement('div')).append(text);
                    content.find('input,button,script,style').remove();

                    if (content.find('body').length) {
                        return content.find('body').html();
                    }

                    return content.html();
                }

                function hideMessage() {
                    message.hide();
                }

                function showErrorMessage(messageText) {
                    message.attr('class', 'message error-message');

                    message.html(getHTMLContent(messageText));
                    message.show();
                }

                function showSuccessMessage(messageText) {
                    form.hide();
                    okMessage.show().html(getHTMLContent(messageText));
                    notice.removeClass('notice-error').addClass('notice-success');
                }

                function showSpinner() {
                    form.find('.spinner-holder').show();
                }


                function hideSpinner() {
                    form.find('.spinner-holder').hide();
                }

                keyInput.on('keyup change', hideMessage);

                form.on('submit', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    var key = keyInput.val();
                    if (!key) {
                        showErrorMessage('License key is empty');
                        return;
                    }

                    hideMessage();
                    showSpinner();
                    form.addClass('disabled');
                    wp.ajax.send('colibriwp-page-builder-activate', {
                        data: {
                            key: key,
                            '_wpnonce': '<?php echo wp_create_nonce('colibriwp-page-builder-activate-nonce'); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped?>'
                        }
                    }).done(function (response) {
                        hideSpinner();
                        form.hide();
                        $('.spinner-holder.plugin-installer-spinner .message').text('Installing Colibri Page Builder PRO...');
                        $('.spinner-holder.plugin-installer-spinner').show();
                        wp.ajax.post('colibriwp-page-builder-maybe-install-pro', {
                            _wpnonce: '<?php echo wp_create_nonce( 'colibriwp-page-builder-maybe-install-pro-nonce' ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped?>'
                        }).done(function () {
                            $('.spinner-holder.plugin-installer-spinner .message').text('Colibri Page Builder PRO sucessfully installed');
                            $('.spinner-holder.plugin-installer-spinner .spinner').remove();
                        }).fail(function () {
                            showErrorMessage('There was an error installing the Colibri Page Builder PRO plugin');
                        });

                        // /// /// ///

                        // // /// /

                    }).fail(function (response) {
                        hideSpinner();
                        form.removeClass('disabled');
                        showErrorMessage(response.responseJSON.data);
                    });
                })
            });
        </script>
		<?php
		$content = ob_get_clean();

		return strip_tags( $content );
	}

	public function makeUpgradeView() {
		?>
        <div class="colibri-page-builder-upgade-view colibri-admin-panel">
            <div class="colibri-page-builder-license-notice colibri-page-builder-activate-license">
                <h2>Upgrade to Colibri Page Builder PRO</h2>
                <h3>Enter a valid Colibri Page Builder PRO license key to unlock all the PRO features</h3>
                <form id="colibri-page-builder-activate-license-form" class="activate-form">
                    <table class="form-table">
                        <tbody>
                        <tr>
                            <td><input placeholder="6F474380-5929B874-D2E0CB90-C7282097" type="text"
                                       value="<?php esc_attr( get_option( 'colibri_sync_data_source', '' ) ); ?>"
                                       class="regular-text"></td>
                            <td>
                                <button type="submit" class="button button-primary">Activate License</button>
                            </td>
                            <td class="spinner-holder" style="display: none">
                                <span class="spinner"></span>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </form>

                <p id="colibri-page-builder-activate-license-message" class="message"></p>
                <div>
                    <div class="spinner-holder plugin-installer-spinner" style="display: none">
                        <span class="spinner"></span>
                        <span class="message">Message</span>
                    </div>
                </div>
                <p class="message success-message ok-message" style="display: none"></p>
                <p class="description">
                    Your key was sent via email when the purchase was completed. Also you can find the key in the
                    <a href="<?php echo esc_attr( License::getInstance()->getDashboardUrl() ); ?>/#/my-plans"
                       target="_blank">My plans</a> of your
                    <a href="<?php echo esc_attr( License::getInstance()->getDashboardUrl() ); ?>" target="_blank">ColibriWP</a>
                    account.
                </p>
            </div>
        </div>
		<?php
	}

	public function makeActivateNotice() {
		?>
        <div class="notice notice-error is-dismissible">
            <div class="colibri-page-builder-license-notice colibri-page-builder-activate-license">
                <h1>Activate Colibri Page Builder PRO License</h1>
                <h3>If this is a testing site you can ignore this message. If this is your live site then please insert
                    the license key bellow.</h3>
                <form id="colibri-page-builder-activate-license-form" class="activate-form">
                    <table class="form-table">
                        <tbody>
                        <tr>
                            <td><input placeholder="6F474380-5929B874-D2E0CB90-C7282097" type="text"
                                       value="<?php esc_attr( get_option( 'colibri_sync_data_source', '' ) ); ?>"
                                       class="regular-text"></td>
                            <td>
                                <button type="submit" class="button button-primary">Activate License</button>
                            </td>
                            <td class="spinner-holder" style="display: none">
                                <span class="spinner"></span>
                            </td>
                            <td>
                                <p id="colibri-page-builder-activate-license-message" class="message"></p>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </form>
                <p class="message success-message ok-message" style="display: none"></p>
                <p class="description">
                    Your key was sent via email when the purchase was completed. Also you can find the key in the
                    <a href="<?php echo esc_attr( License::getInstance()->getDashboardUrl() ); ?>/#/my-plans"
                       target="_blank">My plans</a> of your
                    <a href="<?php echo esc_attr( License::getInstance()->getDashboardUrl() ); ?>" target="_blank">ColibriWP</a>
                    account.
                </p>
            </div>
        </div>
		<?php
	}

	public function callActivateLicenseEndpoint() {
		check_ajax_referer('colibriwp-page-builder-activate-nonce');
		$key = isset( $_REQUEST['key'] ) ? sanitize_text_field($_REQUEST['key']) : false;

		if ( ! $key ) {
			wp_send_json_error( 'License key is empty', 403 );
		}

		License::getInstance()->setLicenseKey( $key );
		$response = Endpoint::activate();

		if ( $response->isError() ) {
			License::getInstance()->setLicenseKey( null );
		}

		wp_send_json( array(
			'data'    => $response->getMessage( true ),
			'success' => $response->isSuccess()
		), $response->getResponseCode() );
	}

	public function maybeInstallPRO() {
		check_ajax_referer('colibriwp-page-builder-maybe-install-pro-nonce');

		add_filter( 'colibri_page_builder/companion/update_remote_data', function ( $data ) {
			$data['args'] = array(
				'product' => 'colibri-page-builder-pro',
				'key'     => License::getInstance()->getLicenseKey()
			);

			$data['plugin_path'] = 'colibri-page-builder-pro/colibri-page-builder-pro.php';

			return $data;
		}, PHP_INT_MAX );

		$status = (array) Updater::getInstance()->isUpdateAvailable();

		$url = array_get_value( $status, 'package_url', false );

		if ( $url ) {

			if ( ! function_exists( 'plugins_api' ) ) {
				include_once( ABSPATH . 'wp-admin/includes/plugin-install.php' ); //for plugins_api..
			}

			if ( ! class_exists( 'Plugin_Upgrader' ) ) {
				/** Plugin_Upgrader class */
				require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			}

			$upgrader = new Plugin_Upgrader( new \Automatic_Upgrader_Skin() );
			$result   = $upgrader->install( $url );

			if ( $result !== true ) {
				wp_send_json_error();
			}

			$ac   = get_option( 'active_plugins' );
			$ac   = array_diff( $ac, array( 'colibri-page-builder/colibri-page-builder.php' ) );
			$ac[] = "colibri-page-builder-pro/colibri-page-builder-pro.php";

			update_option( 'active_plugins', $ac );

            update_option('colibri_page_builder_pro_activation_time', time());

		}

		wp_send_json_success( $status );
	}
}
