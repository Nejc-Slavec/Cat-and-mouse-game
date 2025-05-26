<?php


namespace ColibriWP\Theme\Components\FrontHeader;


use ColibriWP\Theme\Components\CSSOutput;
use ColibriWP\Theme\Core\ComponentBase;
use ColibriWP\Theme\Defaults;
use ColibriWP\Theme\Translations;
use ColibriWP\Theme\View;

class Title extends ComponentBase {

    protected static $settings_prefix = "header_front_page.title.";

    /**
     * @return array();
     */
    protected static function getOptions() {
        $prefix = static::$settings_prefix;
        $colibriwp_theme_click_pen_to_edit_title = __( 'Click the pencil icon to edit the text', 'peregrine' );

        if ( apply_filters( 'colibri_page_builder/installed', false ) ) {
            $colibriwp_theme_click_pen_to_edit_title = __( 'Just click and start typing to change the site headline.',
                'peregrine' );
        }
        $colibriwp_theme_click_pen_to_edit_title = apply_filters('colibri_theme_title_default_content', $colibriwp_theme_click_pen_to_edit_title);
        return array(
            "sections" => array(
                "{$prefix}section" => array(
                    'title'  => Translations::get( 'title' ),
                    'panel'  => 'header_panel',
                    'type'   => 'colibri_section',
                    'hidden' => true
                )
            ),

            "settings" => array(
                "{$prefix}show"               => array(
                    'default'   => Defaults::get( "{$prefix}show" ),
                    'transport' => 'refresh',
                    'control'   => array(
                        'label'       => Translations::get( 'show_title' ),
                        'type'        => 'switch',
                        'show_toggle' => true,
                        'section'     => "hero",
                        'colibri_tab' => 'content',
                    ),

                ),
                "{$prefix}localProps.content" => array(
                    'default' => $colibriwp_theme_click_pen_to_edit_title,
                    'control' => array(
                        'label'       => Translations::get( 'title' ),
                        'type'        => 'input',
                        'input_type'  => 'textarea',
                        'section'     => "{$prefix}section",
                        'colibri_tab' => "content",
                    ),
                ),
                "{$prefix}style.textAlign"    => array(
                    'default'    => Defaults::get( "{$prefix}style.textAlign" ),
                    'control'    => array(
                        'label'       => Translations::escHtml( "align" ),
                        'type'        => 'align-button-group',
                        'button_size' => 'medium',
                        //labels are used as values for align-button-group
                        'choices'     => array(
                            'left'   => 'left',
                            'center' => 'center',
                            'right'  => 'right',
                        ),
                        'none_value'  => 'flex-start',
                        'section'     => "{$prefix}section",
                        'colibri_tab' => "content",
                    ),
                    'css_output' => array(
                        array(
                            'selector' => static::selectiveRefreshSelector(),
                            'media'    => CSSOutput::NO_MEDIA,
                            'property' => 'text-align',
                        ),
                    ),
                ),
            ),
        );
    }

    public static function selectiveRefreshSelector() {
        return Defaults::get( static::$settings_prefix . 'selective_selector', false );
    }

    public function getPenPosition() {
        return static::PEN_ON_RIGHT;
    }

    public function renderContent() {

        if ( $this->mod( static::$settings_prefix . 'show' ) ) {
            View::partial( 'front-header', 'title', array(
                "component" => $this,
            ) );
        }
    }

    public function printTitle( $shortcode = '' ) {

        $prefix = static::$settings_prefix;

	    if ( get_theme_mod( "{$prefix}localProps.content", false ) || is_user_logged_in() ) {
		    $content = $this->mod( "{$prefix}localProps.content", "" );
		    if ( $content ) {
			    $value = trim( $content );
		    } else {
			    $value = trim( Defaults::get( "{$prefix}.value" ) );
		    }
	    } else {
		    $value = get_bloginfo( 'name' );
	    }


        echo str_replace( "\n", "<br/>", $value );
    }
}
