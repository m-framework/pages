<?php

namespace modules\pages\models;

use m\core;
use m\model;
use m\cache;

class pages_types_modules extends model
{
    public $_table = 'pages_types_modules';
    protected $_sort = ['sequence' => 'ASC', 'id' => 'ASC'];

    private static $types_modules = [];
    private static $system_types_modules = [];

    protected $fields = [
        'id' => 'int',
        'type' => 'varchar',
        'module' => 'varchar',
        'sequence' => 'int',
    ];

    public static function get_types_modules()
    {
        if (!empty(static::$types_modules)) {
            return static::$types_modules;
        }

        $cache_pages_types_modules = cache::get('pages_types_modules.json', 2592000); // 30 days
        if (!empty($cache_pages_types_modules)) {
            static::$types_modules = @json_decode($cache_pages_types_modules, true);
            return static::$types_modules;
        }

        static::$system_types_modules = is_file(__DIR__ . '/system_types_modules.php')
            ? include_once('system_types_modules.php') : [];

        $modules = pages_types_modules::call_static()->s([], [], [10000])->all();

        if (empty($modules)) {
            return static::$types_modules;
        }

        foreach ($modules as $module_row) {

            if (!isset(static::$types_modules[$module_row['type']])) {
                static::$types_modules[$module_row['type']] = [];
            }

            static::$types_modules[$module_row['type']][] = $module_row['module'];
        }

        static::$types_modules = array_replace_recursive(static::$system_types_modules, static::$types_modules);

        if (!empty(static::$types_modules)) {
            cache::set('pages_types_modules.json', json_encode(static::$types_modules));
//            file_put_contents('_types_modules.php', '<?php' . "\n" . 'return ' . var_export(static::$types_modules, true) . ";\n");
        }

        return static::$types_modules;
    }

    public static function get_modules($type)
    {
        static::get_types_modules();
        return empty(static::$types_modules[$type]) ? [] : static::$types_modules[$type];
    }

    public static function types_options_arr()
    {
        static::get_types_modules();
        $options = [];
        foreach (static::$types_modules as $k => $language_code) {
            $options[] = ['value' => $k, 'name' => $k,];
        }
        return $options;
    }

    public function _before_save()
    {
        cache::delete('pages_types_modules.json');
        cache::delete('pages.json');
        cache::delete('pages_tree.json');
        return true;
    }
}