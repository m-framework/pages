<?php

namespace modules\pages\admin;

use m\module;
use m\view;
use m\core;
use m\i18n;
use m\registry;
use modules\modules\models\modules;
use modules\pages\models\pages_types_modules;

class type_modules extends module {

    public function _init()
    {
        if (empty($this->get->type_modules)) {
            core::redirect('/' . $this->conf->admin_panel_alias . '/pages/types');
        }

        $type = $this->get->type_modules;

        $types = pages_types_modules::call_static()->s([],['type' => $type],[1000])->all();

        view::set('page_title', '<h1><i class="fa fa-cog"></i> *Edit page type modules* `' . $type . '`</h1>');
        registry::set('title', i18n::get('Edit page type modules'));

        registry::set('breadcrumbs', [
            '/' . $this->conf->admin_panel_alias . '/pages' => '*Pages*',
            '/' . $this->conf->admin_panel_alias . '/pages/types' => '*Pages types*',
            '' => '*Edit page type modules*'
        ]);

        $items = [];

        if (!empty($types)) {
            foreach ($types as $types_module) {

                $items[] = $this->view->type_modules_item->prepare([
                    'id' => $types_module['id'],
                    'module' => $types_module['module'],
                    'type' => $type,
                    'classes_options' => $this->build_options($types_module['module']),
                ]);
            }
        }
        ksort($items);

        view::set('content', $this->view->type_modules_overview->prepare([
            'items' => implode("\n", $items),
            'type' => $type,
            'classes_options' => $this->build_options(),
        ]));

        view::set_css($this->module_path . '/css/types_overview.css');

        unset($types);
    }

    private function build_options($value = null)
    {
        $classes_options = [];

        $client_classes = modules::get_client_classes();

        if (!empty($client_classes)) {
            foreach ($client_classes as $client_group => $client_group_items) {
                $classes_options[] = '<optgroup label="' . $client_group . '">';

                if (is_array($client_group_items)) {
                    foreach ($client_group_items as $class => $class_name) {
                        $classes_options[] = '<option value="' . $class . '"' .
                            (empty($value) || $value !== $class ? '' : ' selected') . '>' . $class_name . '</option>';
                    }
                }

                $classes_options[] = '</optgroup>';
            }
        }

        return implode("\n", $classes_options);
    }
}
