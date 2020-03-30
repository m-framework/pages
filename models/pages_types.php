<?php

namespace modules\pages\models;

use m\model;
use m\registry;
use m\cache;
use modules\pages\models\pages_types_modules;

class pages_types extends model
{
    protected $_table = 'pages_types';
    protected $fields = [
        'type' => 'varchar'
    ];
    public $__id = 'type';

    private static $types = [];

    public static function get_types()
    {
        if (!empty(static::$types)) {
            return static::$types;
        }

        $cache_pages_types = cache::get('pages_types.json', 2592000); // 30 days

        if (!empty($cache_pages_types)) {
            static::$types = @json_decode($cache_pages_types, true);
            return static::$types;
        }

        $types = pages_types::call_static()->s(['*'], [], [1000])->all();

        if (empty($types)) {
            return static::$types;
        }

        foreach ($types as $type_row) {
            static::$types[] = mb_strtolower(stripslashes($type_row['type']), 'UTF-8');
        }

        if (!empty(static::$types)) {
            cache::set('pages_types.json', json_encode(static::$types));
        }

        return static::$types;
    }

    public function _before_save()
    {
        cache::delete('pages_types.json');
        cache::delete('pages.json');
        cache::delete('pages_tree.json');
        return true;
    }

    public function _before_destroy()
    {
        if (empty($this->type)) {
            return true;
        }

        $modules = pages_types_modules::call_static()->s([], ['type' => $this->type], [1000])->all('object');

        if (!empty($modules)) {
            foreach ($modules as $module) {
                $module->destroy();
            }
        }
        // TODO: check a pages that used this type and set to them a type `articles`

        cache::delete('pages_types.json');
        cache::delete('pages.json');
        cache::delete('pages_tree.json');

        return true;
    }
}