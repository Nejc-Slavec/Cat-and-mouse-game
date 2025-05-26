<?php
/**
 * A class that extends WP_Customize_Setting so we can access
 * the protected updated method when importing options.
 *
 * Used in the Customizer importer.
 *
 * @since 1.1.1
 * @package ocdi
 */

namespace ColibriWP\PageBuilder\OCDI;

use WP_Customize_Setting;

final class CustomizerOption extends WP_Customize_Setting {
    /**
     * Import an option value for this setting.
     *
     * @param mixed $value The option value.
     *
     * @return void
     * @since 1.1.1
     */
    public function import( $value ) {
        $this->update( $value );
    }
}
