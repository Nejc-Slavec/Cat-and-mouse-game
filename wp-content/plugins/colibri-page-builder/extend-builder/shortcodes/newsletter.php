<?php

namespace ExtendBuilder;

use ColibriWP\PageBuilder\ThemeHooks;

ThemeHooks::prefixed_add_filter('theme_plugins', function ($plugins) {
    $mailchimp_slug = 'mailchimp-for-wp';
    $plugins = array_merge($plugins, array(
            $mailchimp_slug => array(
            'internal' => true,
                'name' => 'Mailchimp',
                'description' => '',
                'plugin_path' => "$mailchimp_slug/$mailchimp_slug.php"
            )
        )
    );

    return $plugins;
});
add_filter('mc4wp_form_content', '\ExtendBuilder\colibri_mc4wp_filter');
function colibri_mc4wp_filter($content)
{

    $matches = array();
    preg_match_all('/<input[^>]+>/', $content, $matches);

    $attrs = colibri_cache_get('colibri_newsletter_attrs');

    //if the shortcode is not used using the newsletter component don't modify it;
    if(!$attrs) {
        //no need to escape because it's not a shortcode it's a filter from mailchimp. They need to escape the content
        return $content;
    }
    $email = "";
    $submit = "";
    $agree_terms = "";
    for ($i = 0; $i < count($matches[0]); $i++) {
        $match = $matches[0][$i];
        if (strpos($match, "email") !== false) {
            $email = $match;
        }
        if (strpos($match, "submit") !== false) {
            $submit = $match;
        }
        if (strpos($match, "AGREE_TO_TERMS") !== false) {
            $agree_terms = $match;
        }
    }

    ob_start();

    ?>
    <div class="colibri-newsletter__email-group colibri-newsletter-group">
        <?php if ($attrs['email_label']): ?>
            <label><?php echo esc_html($attrs['email_label']); ?></label>
        <?php endif; ?>
        <input type="email" name="EMAIL" placeholder="<?php echo esc_html($attrs['email_placeholder']); ?>" required/>
    </div>
    <?php
    $email_html = ob_get_clean();
    ob_start();
    ?>
    <div class=" colibri-newsletter__agree-terms-group colibri-newsletter-group">
        <label>
            <input type="checkbox" name="AGREE_TO_TERMS" value="1" required/>
            <?php echo esc_html($attrs['agree_terms_label']); ?>
        </label>
    </div>
    <?php
    $agree_terms_html = ob_get_clean();
    ob_start();
    ?>
    <div class="colibri-newsletter__submit-group colibri-newsletter-group">
        <button type="submit">
            <span class="h-svg-icon"><?php if ($attrs['submit_button_use_icon'] === '1') {
                    echo wp_kses_post($attrs['submit_button_icon']);
                } ?></span>
            <span class="colibri-newsletter__submit-text"><?php echo esc_html($attrs['submit_button_label']); ?></span>
        </button>
    </div>
    <?php
    $submit_html = ob_get_clean();

    $form = '';
    if ($email) {
        $form .= "$email_html";
    }
    if ($agree_terms) {
        $form .= "$agree_terms_html";
    }
    if ($submit) {
        $form .= "$submit_html";
    }


//    return $content;
    return $form;
}

add_shortcode('colibri_newsletter', '\ExtendBuilder\colibri_newsletter_shortcode');


function colibri_newsletter_shortcode($atts)
{
    $attrs = shortcode_atts(
        array(
            'email_label' => 'Email address: ',
            'email_placeholder' => 'Your email address',
            'submit_button_label' => 'Subscribe',
            'submit_button_icon' => '',
            'submit_button_use_icon' => '0',
            'agree_terms_label' => 'I have read and agree to the terms & conditions',
            'shortcode' => '',
            'position' => 'inline'
        ),
        $atts
    );
    $attrs['shortcode'] = colibri_shortcode_decode($attrs['shortcode']);
    $attrs['submit_button_icon'] = colibri_shortcode_decode($attrs['submit_button_icon']);
    colibri_cache_set('colibri_newsletter_attrs', $attrs);
    return do_shortcode($attrs['shortcode']);
}
