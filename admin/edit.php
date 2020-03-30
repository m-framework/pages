<?php

namespace modules\pages\admin;

use m\module;
use m\i18n;
use m\registry;
use m\view;
use m\form;
use modules\pages\models\pages;
use modules\pages\models\pages_types;
use modules\pages\models\pages_types_modules;
use modules\sites\models\sites;

class edit extends module {

    public function _init()
    {
        if (!isset($this->view->{'page_' . $this->name . '_form'})) {
            return false;
        }

        $page = new pages(!empty($this->get->edit) ? $this->get->edit : null);

        if (!empty($page->id)) {
            view::set('page_title', '<h1><i class="fa fa-file-text-o"></i> *Edit a page* `' . $page->name . '`</h1>');
            registry::set('title', i18n::get('Edit a page'));
        }

        $page->path = $page->get_path();

        if (empty($page->sites_types) && empty($page->site)) {
            $page->sites_types = $this->site->type;
        }

        $page->name = str_replace('*', '&#42;', $page->name);

        $pages_tree = $this->page->get_pages_tree();

        if (empty($pages_tree)) {
            $this->page->prepare_page([]);
            $pages_tree = $this->page->get_pages_tree();
        }

        $pages_arr = empty($pages_tree) ? [] : pages::options_arr_recursively($pages_tree, '');

        new form(
            $page,
            [
                'site' => [
                    'field_name' => i18n::get('Site'),
                    'related' => sites::sites_arr(),
                ],
                'site_types' => [
                    'field_name' => i18n::get('Site types'),
                    'related' => sites::types_options_arr(),
                ],
                'parent' => [
                    'field_name' => i18n::get('Parent page'),
                    'related' => $pages_arr,
                ],
                'type' => [
                    'field_name' => i18n::get('Page type'),
                    'related' => pages_types_modules::types_options_arr(),
                ],
                'address' => [
                    'type' => 'varchar',
                    'field_name' => i18n::get('Address'),
                ],
                'name' => [
                    'type' => 'varchar',
                    'field_name' => i18n::get('Title'),
                ],
                'cache' => [
                    'type' => 'tinyint',
                    'field_name' => i18n::get('Cache this page'),
                ],
                'template' => [
                    'type' => 'varchar',
                    'field_name' => i18n::get('Special page template'),
                ],
                'sites_types' => [
                    'type' => 'hidden',
                    'field_name' => '',
                ],
            ],
            [
                'form' => $this->view->{'page_' . $this->name . '_form'},
                'varchar' => $this->view->edit_row_varchar,
                'int' => $this->view->edit_row_int,
                'tinyint' => $this->view->edit_row_tinyint,
                'related' => $this->view->edit_row_related,
                'hidden' => $this->view->edit_row_hidden,
                'saved' => $this->view->edit_row_saved,
                'error' => $this->view->edit_row_error,
            ]
        );
    }
}