<?php

namespace ColibriWP\PageBuilder\Customizer\Settings;

use ColibriWP\PageBuilder\Utils\Utils;
use function ExtendBuilder\maybe_inflate_values;
use function ExtendBuilder\maybe_normalize_old_format;

class ContentSetting extends \ColibriWP\PageBuilder\Customizer\BaseSetting
{

    public function update($value)
    {
        if (is_string($value)) {
            $to_decode     = Utils::inflate($value);
            $data = json_decode($to_decode, true);
        } else {
            $data = $value;
        }

        $data = maybe_inflate_values($data);

        do_action('colibri_page_builder/content_setting_update', $data);

        parent::update(array());
    }

    public function value()
    {
        if ($this->is_previewed) {
            $value = $this->post_value(null);
            $new_format_value = maybe_normalize_old_format($value);
            if ($new_format_value) {
                return $new_format_value;
            } else {
                return $value;
            }
        } else {
            return "[]";
        }
    }
}
