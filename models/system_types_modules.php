<?php

return [
    'ajax' => [
        'modules\\ajax\\client\\ajax',
    ],
    'captcha' => [
        'modules\\captcha\\client\\captcha',
    ],
    'photo' => [
        'modules\\photo\\client\\photo',
    ],
    'preview' => [
        'modules\\photo\\client\\preview',
    ],
    'registration' => [
        'modules\\menu\\client\\horizontal_menu',
        'modules\\pages\\client\\sub_pages_tree',
        'modules\\users\\client\\registration',
        'modules\\special_blocks\\client\\footer_blocks',
        'modules\\breadcrumbs\\client\\breadcrumbs',
        'modules\\pages\\client\\page_helper',
    ],
    'cron' => [
        'modules\\cron\\client\\cron',
    ],
    'sitemap' => [
        'modules\\seo\\client\\sitemap',
    ],
    '404' => [
        'modules\\menu\\client\\horizontal_menu',
        'modules\\special_blocks\\client\\special_blocks',
        'modules\\breadcrumbs\\client\\breadcrumbs',
        'modules\\pages\\client\\page_helper',
    ],
    'password_reset' => [
        'modules\\menu\\client\\horizontal_menu',
        'modules\\pages\\client\\sub_pages_tree',
        'modules\\articles\\client\\articles',
        'modules\\users\\client\\reset',
        'modules\\special_blocks\\client\\footer_blocks',
        'modules\\breadcrumbs\\client\\breadcrumbs',
        'modules\\pages\\client\\page_helper',
    ],
    'authorisation' => [
        'modules\\menu\\client\\horizontal_menu',
        'modules\\pages\\client\\sub_pages_tree',
        'modules\\users\\client\\authorisation',
        'modules\\special_blocks\\client\\recommended_blocks',
        'modules\\special_blocks\\client\\side_blocks',
        'modules\\special_blocks\\client\\footer_blocks',
        'modules\\breadcrumbs\\client\\breadcrumbs',
        'modules\\pages\\client\\page_helper',
    ],
    'social_action' => [
        'modules\\users\\client\\social_action',
    ],
    'cabinet' => [
        'modules\\menu\\client\\horizontal_menu',
        'modules\\pages\\client\\sub_pages_tree',
        'modules\\users\\client\\cabinet',
        'modules\\special_blocks\\client\\recommended_blocks',
        'modules\\special_blocks\\client\\side_blocks',
        'modules\\special_blocks\\client\\bottom_blocks',
        'modules\\special_blocks\\client\\footer_blocks',
        'modules\\breadcrumbs\\client\\breadcrumbs',
        'modules\\pages\\client\\page_helper',
    ],
];