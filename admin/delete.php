<?php

namespace modules\pages\admin;

use m\module;
use m\core;
use modules\pages\models\pages;

class delete extends module {

    public function _init()
    {
        $page = new pages(!empty($this->get->delete) ? $this->get->delete : null);

        if (!empty($page->id) && !empty($this->user->profile) && $this->user->is_admin() && $page->destroy()) {
            core::redirect('/' . $this->conf->admin_panel_alias . '/pages');
        }
    }
}
