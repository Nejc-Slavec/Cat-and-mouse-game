<?php

namespace ExtendBuilder;

use ColibriWP\PageBuilder\Utils\Utils;

class PartialsApi
{
    // TODO: move from partials//
    public function import_images($data)
    {
        $urls = $data['urls'];
        $result = array();
        foreach ($urls as $url) {
            $result[] = import_colibri_image($url);
        }

        echo json_encode($result);
    }

    public function index($data)
    {
        \ExtendBuilder\log("Api::list_templates -> data = " . json_encode($data));

        $type = $data['type'];
        $templates = get_partials_of_type($type);

        echo json_encode($templates);
    }

    public function all($options = array())
    {
        echo export_colibri_data($options, true);
    }

    public function update($data)
    {
        $data = json_decode(Utils::inflate($data), true);
        save_options_and_partials_html($data);
        Regenerate::end();
        return;
    }


    public function insert($data)
    {
        \ExtendBuilder\log("Api::list_templates -> data = " . json_encode($data));

        $type = $data['type'];
        $name = $data['name'];
        $partial_data = $data['data'];

        $post_id = create_partial($type, $partial_data, $name);
        $post = get_post($post_id);

        wp_send_json(get_partial_details($post));
    }

    public function delete($data)
    {
        $id = $data['id'];
        wp_delete_post($id);
    }


    public function assign($data)
    {
        \ExtendBuilder\log("Api::assign_header -> data = " . json_encode($data));

        $type = $data['type'];
        $partial_id = $data['id'];
        $post_id = $data['post_id'];

        assign_partial($type, $post_id, $partial_id);
    }

    public function setDefault($data)
    {
        $type = $data['type'];
        $id = $data['id'];
        $key = $data['key'];
        maybe_set_as_default_partial($type, $id, $key, $force = true);
    }
}


Api::addEndPoint("partials", new PartialsApi());
