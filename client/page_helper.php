<?php

namespace modules\pages\client;

use libraries\helper\asset;
use libraries\helper\html;
use libraries\helper\js;
use libraries\helper\url;
use m\config;
use m\configurator;
use m\core;
use m\dynamic_view;
use m\functions;
use m\m_mail;
use m\module;
use m\registry;
use m\cache;
use m\template;
use m\view;
use m\i18n;
use m\logs;
use modules\users\models\visitors;

class page_helper extends module {

    public static $_name = '*Page helper*';

//    protected $events = [
////        'product_shown' => 'notify_product_shown',
////        'user_authorisation' => 'notify_user_authorisation',
//        'user_registered' => 'notify_user_registered',
//        'made_order' => 'notify_made_order',
//        'order_paid' => 'notify_order_paid',
//        'order_status_changed' => 'notify_order_status_changed',
//    ];

    protected $events = [
        'page_shown' => 'init_page_visitor',
    ];

    public function _init()
    {
        /**
         * Set a debug info for developer
         */
        if (config::has('debug_enable') && is_array($this->config->developers_ips)
            && (in_array('*', $this->config->developers_ips) || in_array($this->ip, $this->config->developers_ips))
            && isset($this->view->debug_info)) { // && $this->user->is_admin()

            config::append('css_asset', '/templates/admin/css/debug.css');

            ob_start();
            debug_print_backtrace();
            $short_backtrace = ob_get_contents();
            ob_clean();

            ob_start();
            print_r(debug_backtrace());
            $backtrace = ob_get_contents();
            ob_clean();

            $db_logs = '';

            foreach(registry::get('db_logs') as $n => $log_record) {
                if (empty($log_record['query'])) {
                    continue;
                }

                if (isset($this->view->debug_info_db_query)) {
                    $db_logs .= $this->view->debug_info_db_query->prepare([
                        'n' => $n + 1,
                        'query' => $log_record['query'],
                        'time' => $log_record['time'],
                        'backtrace' => implode("\n", $log_record['backtrace']),
                    ]);
                }
                else {
                    $db_logs .= $log_record['query'] . ' (' . $log_record['time'] . 's)' . "<br>";
                }
            }

            view::set('debug_info', $this->view->debug_info->prepare([
                'short_backtrace' => $short_backtrace,
                'backtrace' => str_replace(' ', '&nbsp;', $backtrace),
                'db_logs' => $db_logs,
            ]));
        }

        /**
         * Page HEAD container: title, SEO-tags, CSS
         */
        if (isset($this->view->head)) {
            template::set_head($this->view->head);
        }

        /**
         * Page footer: text, copyright, JS
         */
        if (isset($this->view->footer)) {
            template::set_footer($this->view->footer);
        }

        /**
         * Languages toggle links
         */
        if (isset($this->view->languages_toggle)) {
            template::set_languages_toggle($this->view->languages_toggle);
        }

        if ($this->view->admin_board && !empty($this->user) && $this->user->is_admin()) {
            view::set('admin_board', $this->view->admin_board->prepare([
                'seo_status' => $this->page && $this->page->seo && $this->page->seo->enabled == '1' ? 'enabled' : 'disabled',
            ]));
        }

        if (isset($this->view->header)) {

            $page_title = '';

            if (!empty($this->page->seo) && !empty($this->page->seo->title)) {
                $page_title = $this->page->seo->title;
            }
            else if (registry::get('title')) {
                $page_title = registry::get('title');
            }
            else if (!empty($this->page->name)) {
                $page_title = $this->page->name;
            }

            if (empty($page_title)){
                $page_title = $this->site->title;
            }

            $header_arr = [
                'slogan' => !empty($this->site->slogan) ? $this->site->slogan : '',
                'motto' => !empty($this->site->motto) ? $this->site->motto : '',
                'contacts' => !empty($this->site->contacts) ? htmlspecialchars_decode($this->site->contacts) : '',
                'random_num' => rand(1, 5),
                'title' => !empty($this->site->title) ? $this->site->title : '',
                'page_title' => $page_title,
                'alert' => empty($_COOKIE['hide_header_alert']) && !empty($this->site->alert) ? $this->site->alert : '',
            ];

            $site_variables = get_object_vars($this->site);

            foreach ($site_variables as $site_variable => $site_variable_val) {
                if (!isset($header_arr[$site_variable])) {
                    $header_arr[$site_variable] = $site_variable_val;
                }
            }

            view::set('header', $this->view->header->prepare($header_arr));
        }
		
        if (isset($this->view->search_link)) {
			view::set('search_link', $this->view->search_link->prepare());
        }

        if (isset($this->view->basket_link) && $this->site->type == 'shop' && class_exists('modules\shop\models\shop_basket')) {
			view::set('basket_link', $this->view->basket_link->prepare());
        }

        if (!empty($this->user->profile) && isset($this->view->personal_link)) {

            view::set('personal_link', $this->view->personal_link->prepare([
                'name' => trim($this->user->info->first_name),
            ]));
        }
        else if (isset($this->view->authorisation_link)) {
            view::set('personal_link', $this->view->authorisation_link->prepare());
        }

    }

    public function _get_page_seo()
    {
        $_enabled_keys = [
            'seo_description',
            'seo_enabled',
            'seo_keywords',
            'seo_title'
        ];

        if (empty($this->request->keys) || $this->request->keys !== implode(', ', $_enabled_keys))
            return false;

        $this->ajax_arr = [
            'seo_title' => !empty($this->page->seo->title) ? $this->page->seo->title : '',
            'seo_keywords' => !empty($this->page->seo->keywords) ? $this->page->seo->keywords : '',
            'seo_description' => !empty($this->page->seo->description) ? $this->page->seo->description : '',
            'seo_enabled' => !empty($this->page->seo->enabled) ? $this->page->seo->enabled : '0',
        ];
        return true;
    }

    public function _save_page_seo()
    {
        $request = $this->request;
        $request->site = $this->site->id;
        $request->route = '/' . implode('/', $this->route);
        $request->language = $this->language;

        $this->ajax_arr = [];

        if ($this->page->update_seo($request)) {
            $this->ajax_arr['text'] = i18n::get('page seo successfully saved');
        }
        else {
            $this->ajax_arr['error'] = i18n::get('page seo saving error');
        }

        return true;
    }

    public function __call($name, $arguments)
    {
        if (substr($name, 0, 7) == 'notify_') {
            return $this->notify($arguments['0'], substr($name, 7));
        }

        return false;
    }
	
	//
	public function init_page_visitor($page)
    {
		if (!registry::has('visitor') || !registry::has('site'))  {
			return false;
		}
	
        visitors::set_history([
			'visitor' => registry::has('visitor') ? registry::get('visitor')->id : null,
            'user' => registry::has('user') ? registry::get('user')->profile : null,
            'site' => registry::has('site') ? registry::get('site')->id : null,
            'related_model' => 'pages',
            'related_id' => $page->id,
        ]);
    }
}
