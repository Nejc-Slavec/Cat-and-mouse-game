<?php

namespace ColibriWP\PageBuilder\Customizer;

class BaseSetting extends \WP_Customize_Setting
{
    protected $cpData = null;

    public function __construct($manager, $id, $cpData = array())
    {
        $this->cpData = $cpData;
        $this->manager = $manager;
        $this->id = $id;

        $this->cpData["__is__kirki"] = isset($this->cpData["__is__kirki"]) ? $this->cpData["__is__kirki"] : false;

        $args = (isset($this->cpData['wp_data'])) ? $this->cpData['wp_data'] : array();

       
        if (isset($args['default'])) {
            $default = $args['default'];

            if ($default ==="__cp_filter__") {
                $filter = $this->cpData['filterOptions']['filter'];
                $filterDefault = isset($this->cpData['filterOptions']['default'])? $this->cpData['filterOptions']['default']: false;
                $default = apply_filters($filter, $filterDefault) ;
            }

            $args['default'] = BaseSetting::filterDefault($default);
        }

        $args['capability'] = isset($args['capability']) ? $args['capability'] : "edit_theme_options";
        $args['option_type'] = isset($args['option_type'])? $args['option_type'] : "theme_mod";
            
        $this->cpData['wp_data'] = $args;

        if ($this->isKirki()) {
            \Kirki::add_config($id, $args);
        } else {
            parent::__construct($manager, $id, $args);
        }

        $this->init();
    }


    public function isKirki()
    {
        $controlClass = \ColibriWP\PageBuilder\PageBuilder::getTreeValueAt($this->cpData, "control:class");
        $controlIsKirki = $controlClass && (strpos($controlClass, "kirki:") ===0);
        
        $isKirki = $this->cpData["__is__kirki"] || $controlIsKirki;

        return  $isKirki;
    }

    public function setControl()
    {
        if (isset($this->cpData['no-control']) && $this->cpData['no-control']) {
            return;
        }

        $controlData = array(
            "class"   => false,
            "wp_data" => array(
                "section" => $this->cpData['section'],
                "label"   => $this->id,
            ),
        );
        if (isset($this->cpData['control'])) {
            $controlData = $this->cpData['control'];

            if (!isset($controlData['wp_data'])) {
                $controlData['wp_data'] = array();
            }

            $controlData['wp_data']['section'] = isset($this->cpData['section']) ? $this->cpData['section'] : null;
            $controlData['wp_data']['settings'] = $this->id;
        }

        $this->companion()->customizer()->registerControls($this->manager, array(
            $this->id => $controlData,
        ));
    }

    protected function init()
    {
        return true;
    }

    final protected function companion()
    {
        return \ColibriWP\PageBuilder\PageBuilder::instance();
    }

    public static function filterDefault($data)
    {
        return \ColibriWP\PageBuilder\PageBuilder::filterDefault($data);
    }

    public static function filterArrayDefaults($data)
    {
        return \ColibriWP\PageBuilder\PageBuilder::filterArrayDefaults($data);
    }
}
