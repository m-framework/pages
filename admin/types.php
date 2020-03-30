<?php

namespace modules\pages\admin;

use m\core;
use m\module;
use m\view;
use m\registry;
use modules\pages\models\pages_types;

class types extends module {

    public function _init()
    {
        $types = pages_types::get_types();

        $items = [];

        if (!empty($types)) {
            foreach ($types as $type) {
                $items[$type] = $this->view->types_item->prepare(['type' => $type]);
            }
        }

        view::set_css($this->module_path . '/css/types_overview.css');

        view::set('page_title', '<h1><i class="fa fa-cog"></i> *Pages types*</h1>');
        registry::set('title', '*Pages types*');

        registry::set('breadcrumbs', [
            '/' . $this->conf->admin_panel_alias . '/pages' => '*Pages*',
            '/' . $this->conf->admin_panel_alias . '/pages/types' => '*Pages types*'
        ]);

        ksort($items);

        view::set('content', $this->view->types_overview->prepare([
            'items' => implode("\n", $items),
        ]));

        unset($types);
        unset($items);
    }
}
