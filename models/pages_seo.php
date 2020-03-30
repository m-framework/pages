<?php

namespace modules\pages\models;

use m\model;

class pages_seo extends model
{
    public $_table = 'pages_seo';

    public $id;
    public $site = 1;
    public $route = '';
    public $language = '';
    public $title = '';
    public $keywords = '';
    public $description = '';
    public $enabled = 0;

    protected $fields = [
        'id' => 'int',
        'site' => 'int',
        'page' => 'int',
        'language' => 'int',
        'title' => 'varchar',
        'keywords' => 'varchar',
        'description' => 'text',
        'enabled' => 'tinyint',
        'redirect' => 'varchar',
        'date' => 'timestamp'
    ];

    public function get_actual($arr)
    {
        if (empty($arr) || empty($arr['route']) || empty($arr['site']) || empty($arr['language'])) {

            unset($this->db);
            return $this;
        }

        return $this->s([], ['route'=>$arr['route'], 'site'=>$arr['site'], 'language'=>$arr['language']])
            ->obj();
    }
}