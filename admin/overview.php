<?php

namespace modules\pages\admin;

use m\core;
use m\module;
use m\view;
use m\i18n;
use m\config;
use modules\admin\admin\overview_data;
use modules\pages\models\pages;

class overview extends module {

    public function _init()
    {
//        view::set('content', overview_data::items(
//            'modules\pages\models\pages',
//            [
//                'site' => i18n::get('Site id'),
//                'host' => i18n::get('Host'),
//            ],
//            [],
//            $this->view->overview,
//            $this->view->overview_item
//        ));

        $pages_tree = $this->page->get_pages_tree();

        if (empty($pages_tree)) {
            $this->page->prepare_page($this->route);
            $pages_tree = $this->page->get_pages_tree();
        }

        view::set('content', $this->view->overview->prepare([
            'items' => $this->wrap_recursively($pages_tree, ''),
        ]));
    }

    private function wrap_recursively($page_arr, $start_path = null)
    {
        if (!isset($this->view->overview_item) || !is_array($page_arr))
            return '';

        $tmp = [];

        if (empty($start_path))
            $start_path = '';

        $prefix = '';
        for ($p = 0; $p < substr_count ($start_path, '/'); $p++) {
            $prefix .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        }

        foreach ($page_arr as $page_item) {

//            if (is_object($page_item)) {
//                $page_item = get_object_vars($page_item);
//            }
//            $page_item = (array)$page_item;

            $tmp_path = !empty($page_item['address']) && $page_item['address'] !== '/' ?
                $start_path . $page_item['address'] : '';

            $sub_pages = !empty($page_item['sub_pages']) ? $page_item['sub_pages'] : [];

            $n = empty($page_item['sequence']) ? count($tmp) - 1 : $page_item['sequence'] . '.' . $page_item['id'];

            if (!empty($page_item['system'])) {
                $n = $page_item['id'];
            }

            $tpl = empty($page_item['system']) ? $this->view->overview_item : $this->view->overview_system_item;

            $tmp[$n] = $tpl->prepare([
                'id' => $page_item['id'],
                'type' => $page_item['type'],
                'link' => $tmp_path,
                'path' => $tmp_path,
                'spaces' => $prefix,
                'address' => $page_item['address'],
                'name' => str_replace('*', '&#42;', $page_item['name']),
                'cache' => $page_item['cache'],
                'template' => $page_item['template'],
                'daughters' => $this->wrap_recursively($sub_pages, $tmp_path),
            ]);
        }

        ksort($tmp);

        return implode("\n", $tmp);
    }
}
