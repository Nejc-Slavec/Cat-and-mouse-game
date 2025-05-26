<?php
namespace ExtendBuilder;



function colibri_already_ran_contact_form_sections_spacing_fix() {
    $addedContactFormSectionsSpacingFlag = get_option('colibri_contact_form_sections_spacing_activation_logic_ran_flag',  false);
    if($addedContactFormSectionsSpacingFlag) {
        return true;
    }

    update_option('colibri_contact_form_sections_spacing_activation_logic_ran_flag', true);
    return false;
}


add_action('colibri_page_builder/activated', function() {

    if(colibri_already_ran_contact_form_sections_spacing_fix()) {
        return;
    }

    update_option('colibri_contact_form_sections_spacing_add_fix', true);
});


add_action('colibri_page_builder/deactivated', function() {

    //update option on deactivate so if the user deactivate and activate the plugin the activation logic is not ran
    colibri_already_ran_contact_form_sections_spacing_fix();

});


//for default form we recommend if you install it on the default homepage's contact section there is no spacing
//bellow the form and it looks weird. For new sites only ( to not affect production ) add a spacing
add_action('wp_head',  function () {
    if(!get_option('colibri_contact_form_sections_spacing_add_fix', false)) {
        return;
    }
    ob_start();
    ?>
    <style>
        .h-contact-form__outer--forminator  > *:not(.h-contact-form-shortcode--no-style) form.forminator-custom-form.forminator-design--default:not(#extra-1) {
            margin-bottom: 30px;
        }
    </style>
    <?php
    echo ob_get_clean();
});
