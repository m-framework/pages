<?php

namespace modules\pages\models;

use
    m\model,
    m\cache,
    m\config,
    m\core,
    m\registry;
use modules\seo\models\seo;

class pages extends model
{
    public
        $super_parent; // detected dynamically

    static $pages;
    static $system_pages;
    static $pages_tree;

    protected $fields = [
        'id' => 'int',
        'site' => 'int',
        'parent' => 'int',
        'sites_types' => 'varchar',
        'type' => 'varchar',
        'address' => 'varchar',
        'name' => 'varchar',
        'cache' => 'tinyint',
        'template' => 'varchar',
        'sequence' => 'int',
    ];

    public function _init()
    {
    }

    public function get_children($id = null)
    {
        if (empty($id) && empty($this->id))
            return null;

        $children = $this->s(
            [],
            ['parent' => !empty($id) ? $id : $this->id],
            [1000],
            ['sequence' => 'ASC']
        )->all('object');

        return (!empty($children)) ? $children : null;
    }

    public function prepare_page(array $route)
    {
//        if (empty($route)) {
//            $route = [];
//        }

        registry::set('alias', end($route));

        $pages = static::get_pages();
        $route_pages = [];
        $breadcrumbs = [];
        $parent = null;
        $addr = '';

        if (empty($route)) {
            foreach ($pages as $id => $page) {
                if ($page['address'] == '/') {
                    $route_pages[$id] = $page;
                    $this->super_parent = $id;
                    continue;
                }
            }
        }
        else { //  if (is_array($route))

            foreach ($route as $route_id => $address) {

                foreach ($pages as $id => $page) {

                    if ($page['address'] == '/'.$address && (($route_id == 0 && $parent == null) || (int)$page['parent'] == (int)$parent)) {

//                        if ($parent == null && !empty($page['parent']))
//                            return false;

                        $route_pages[$id] = $page;

                        if ($parent == null) {
                            $this->super_parent = $id;
                        }

                        $parent = $id;

                        $addr .= $page['address'];
                        $breadcrumbs[$addr] = $page['name'];

                        continue;
                    }
                }
            }
        }

        /**
         * Place important data (e.g. page name or page modules etc.) to current page object.
         */
        $this->import(end($route_pages));

        /**
         * Return HTML from cache if page allows completely caching
         */
        if (!empty($this->cache) && !empty($this->id) && config::get('cache_enable')) {
            $name = 'page_' . $this->id . '_' . registry::get('language') . '.html';
            $cache_page = cache::get($name);
            if (!empty($cache_page)) {
                core::out((string)$cache_page);
            }
        }

        /**
         * Take care, an array `breadcrumbs` can be rewritten inside of any other module,
         * so module `breadcrumbs` must be closer to the end of list of page's modules.
         */
        if (!empty($breadcrumbs) && (registry::has('get') && registry::get('get')->controller !== 'admin')) {
            registry::set('breadcrumbs', $breadcrumbs);
        }

        /**
         * An admin or page editor should't obtain a pages tree from cache
         */
        if (!(registry::get('user')->has_permission(null, $this->id))) {
            $pages_tree = cache::get('pages_tree.json', 30 * 86400);
        }

        if (!empty($pages_tree)) {
            /**
             * Don't place a `pages_tree` to registry ! This array can be very large and better to call it from model.
             */
            static::$pages_tree = json_decode($pages_tree, true);
        }
        else {

            krsort($pages);

            $tree = [];

            foreach ($pages as $id => $page) {

                if(empty($page['parent']) && empty($tree[$id])) {
                    $tree[$id] = $page;
                }

                if (empty($tree[$page['parent']]) && !empty($page['parent']) && !empty($pages[$page['parent']])) {
                    $tree[$page['parent']] = $pages[$page['parent']];
                }

                if (!empty($page['parent']) && !empty($tree[$page['parent']]) && !empty($tree[$id])
                    && isset($tree[$page['parent']]['sub_pages'])) {

                    $tree[$page['parent']]['sub_pages'][$id] = $tree[$id];
                    unset($tree[$id]);
                }
                else if (!empty($page['parent']) && !empty($pages[$page['parent']])
                    && isset($tree[$page['parent']]['sub_pages'])) {

                    $tree[$page['parent']]['sub_pages'][$id] = $page;
                }
            }

            static::$pages_tree = $this->array_reverse_recursive($tree);

//            core::out(static::$pages_tree);

            if (!empty(static::$pages_tree)) {
                cache::set('pages_tree.json', json_encode(static::$pages_tree));
            }
        }

        unset($pages);
        unset($breadcrumbs);
        unset($route_pages);
        unset($addr);

        $this->_autoload_seo();

        return $this;
    }

    private function array_reverse_recursive($arr)
    {
        foreach ($arr as &$val) {
            if (is_array($val)) {
                $val = $this->array_reverse_recursive($val);
            }
        }
        return array_reverse($arr, TRUE);
    }

    static function get_pages()
    {
        if (!empty(static::$pages))
            return static::$pages;

        static::$system_pages = is_file(__DIR__ . '/system_pages.php') ? include_once('system_pages.php') : [];

        if (!(registry::get('user')->has_permission(null))) {
            $pages_cache = cache::get('pages.json', 30 * 86400);
        }


        if (!empty($pages_cache)) {
            static::$pages = json_decode($pages_cache, true);
            return static::$pages;
        }

        $pages = [];

        $_site_pages = self::call_static()
            ->s(
                [],
                [
                    [['sites_types' => registry::get('site')->type], ['sites_types' => null]],
                    [['site' => registry::get('site')->id], ['site' => null]],
                ],
                [10000],
                ['sequence' => 'ASC']
            )
            ->all();

        if (!empty($_site_pages))
            foreach ($_site_pages as $_site_page) {

                $_site_page['modules'] = !empty($_site_page['type']) ? pages_types_modules::get_modules($_site_page['type']) : [];
                $_site_page['sub_pages'] = [];
                $pages[$_site_page['id']] = $_site_page;
            }

        $pages = array_replace_recursive(static::$system_pages, $pages);

        if (!empty($pages)) {
            cache::set('pages.json', json_encode($pages));
        }

        static::$pages = $pages;

        return static::$pages;
    }

    public function _autoload_seo()
    {
        if (!registry::has('language') || !registry::has('main_language') || !registry::has('site') || !registry::has('_route')) {
            return false;
        }

        $lang_link = rtrim('/' . registry::get_string('clean_route'), '/');

        if (empty($lang_link)) {
            $lang_link = '/';
        }

        $this->seo = seo::call_static()
            ->s([], [
                    [['site' => registry::get('site')->id], ['site' => null]],
                    [['language' => registry::get('language_id')], ['language' => null]],
                    'address' => $lang_link
                ])
            ->obj();

        return true;
    }

    public function get_pages_tree()
    {
        return !empty(static::$pages_tree) ? static::$pages_tree : [];
    }

    public function get_path($parent_id = null)
    {
        $path = $this->address;

        if (stripos($path, 'http') !== false) {
            return $path;
        }

        if (!empty($this->parent) && empty($parent_id)) {
            $path = $this->get_path($this->parent) . $path;
        }
        else if (!empty($parent_id)) {
            $page = new pages($parent_id);
            $path = $page->address;

            if (!empty($page->parent))
                $path = $this->get_path($page->parent) . $path;
        }

        return str_replace('//', '/', $path);
    }

    public function _autoload_path()
    {
        $this->path = $this->get_path();
        return $this->path;
    }

    public function _before_save()
    {
        cache::delete('pages.json');
        cache::delete('pages_tree.json');
        return true;
    }

    /**
     * Transform an array of pages to scalar array with whitespaces. Useful in forms where need to select a page.
     * An array from this method often used for helper url::arr_2_options()
     *
     * @param $page_arr
     * @param null $start_path
     * @return array
     */
    public static function options_arr_recursively($page_arr, $start_path = null)
    {
        $tmp = [];

        if (empty($page_arr)) {
            return $tmp;
        }

        if (empty($start_path))
            $start_path = '';

        $prefix = '';

        /**
         * Whitespaces for sub-pages
         */
        for ($p = 0; $p < substr_count ($start_path, '/'); $p++) {
            $prefix .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        }

        foreach ($page_arr as $page_item) {

            $sub_pages = !empty($page_item['sub_pages']) ? $page_item['sub_pages'] : [];

            $n = empty($page_item['sequence']) ? count($tmp) - 1 : $page_item['sequence'];

            if (!empty($page_item['system'])) {
                $n = $page_item['id'];
            }

            $tmp[$n] = [
                'value' => $page_item['id'],
                'name' => $prefix . $page_item['name'],
            ];

            $tmp = array_merge($tmp, self::options_arr_recursively($sub_pages, $start_path . $page_item['address']));
        }

        ksort($tmp);

        return $tmp;
    }
}