<?php

namespace modules\pages\client;

use m\config;
use m\core;
use m\dynamic_view;
use m\module;
use m\registry;
use m\cache;
use m\view;
use m\i18n;
use m\logs;
use modules\users\models\visitors;

class block_404 extends module {

    public static $_name = '*Special block for 404 status page*';

    protected $css = ['/css/404.css'];

    public function _init()
    {
        if (isset($this->view->block_404)) {
            view::set('content', $this->view->block_404->prepare([]));
        }
    }
}
