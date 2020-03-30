<?php

namespace modules\pages\client;

use m\config;
use m\module;
use m\registry;
use m\cache;
use modules\articles\models\articles;
use modules\pages\models\pages;
use m\view;

class sub_pages_tree extends module {

    protected $cache = false;
    protected $cache_per_page = false;

    protected $css = [
        '/css/sub_pages_tree.css'
    ];

    public static $_name = '*Sub-pages tree*';

    public function _init()
    {
//        return false;

        $options = $this->options;

        $pages_tree = $this->page->get_pages_tree();

        $start_path = !empty($pages_tree) && !empty($pages_tree[$this->page->super_parent]['address']) ?
            $pages_tree[$this->page->super_parent]['address'] : $this->page->address;

        if (isset($this->view->sub_pages_tree) && !empty($this->page->super_parent) && !empty($pages_tree)
            && !empty($pages_tree[$this->page->super_parent]) && empty($pages_tree[$this->page->super_parent]['sub_pages'])) {
            $pages_tree[$this->page->super_parent]['sub_pages'] = [$this->page];
            $start_path = '';
        }
        else if (!isset($this->view->sub_pages_tree) || empty($this->page->super_parent) || empty($pages_tree)
            || empty($pages_tree[$this->page->super_parent]) || empty($pages_tree[$this->page->super_parent]['sub_pages'])) {
            return false;
        }

        $links = $this->wrap_recursively(
            $pages_tree[$this->page->super_parent]['sub_pages'],
            $start_path,
            empty($options) || empty($options->limit) ? null : $options->limit
        );

        if (empty($links) || empty($links['links']))
            return false;

        view::set('sub_pages_tree', $this->view->sub_pages_tree->prepare([
            'links' => $links['links'],
        ]));

        unset($pages_tree);

        return true;
    }

    private function wrap_recursively($page_arr, $start_path = null, $limit = null)
    {
        if (!isset($this->view->sub_pages_tree_item) || !is_array($page_arr))
            return '';

        $tmp = [];

        if (empty($start_path))
            $start_path = '';

        foreach ($page_arr as $page_item) {

            $page_item = (array)$page_item;

            $tmp_path = !empty($page_item['address']) && $page_item['address'] !== '/' ?
                $start_path . $page_item['address'] : '';

            $sub_pages = !empty($page_item['sub_pages']) ? $page_item['sub_pages'] : [];

            if (empty($sub_pages) && !empty($page_item['id'])) {

                $articles_items = articles::call_static()
                    ->s([], [
                        'page' => $page_item['id'],
                        "alias!='" . substr($page_item['address'], 1) . "'",
                        'published' => 1,
                        'language' => $this->language_id,
                    ], [10000])
                    ->all();

                if (!empty($articles_items))
                    foreach ($articles_items as $articles_item) {
                        $sub_pages[] = [
                            'address' => '/' . $articles_item['alias'],
                            'name' => $articles_item['title'],
                            'date' => empty($start_path) ? '' : strftime('%e %b', strtotime($articles_item['date'])),
                            'time' => empty($start_path) ? '' : strftime('%H:%M', strtotime($articles_item['date'])),
                        ];
                    }
            }

            $n = empty($page_item['sequence']) ? count($tmp) - 1 : $page_item['sequence'];

            $this_links = $this->wrap_recursively($sub_pages, $tmp_path, $limit);

            $tmp[$n] = $this->view->sub_pages_tree_item->prepare([
                'link' => $tmp_path,
                'name' => $page_item['name'],
                'count' => empty($this_links['count']) ? '' : '(' . $this_links['count'] . ')',
                'links' => empty($this_links['links']) ? '' : $this_links['links'],
                'article_date' => empty($start_path) || empty($page_item['date']) ? '' : $page_item['date'],
                'article_time' => empty($start_path) || empty($page_item['time']) ? '' : $page_item['time'],
            ]);
        }

        ksort($tmp);

        $cnt = count($tmp);

        if (!empty($limit) && (int)$limit > 1 && $cnt > (int)$limit) {
            $tmp = array_slice($tmp, 0, (int)$limit);
            $tmp[] = $this->view->sub_pages_tree_item->prepare([
                'link' => $start_path . ((int)$limit == (int)config::get('per_page') ? '/page/2' : '#'),
                'name' => '*Show more* (' . ($cnt - (int)$limit) . '*pcs.*)',
                'links' => '',
            ]);
        }

        return [
            'count' => $cnt,
            'links' => implode("\n", $tmp)
        ];
    }
}
