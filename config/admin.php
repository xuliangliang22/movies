<?php

return [

    /*
     * Laravel-admin name.
     */
    'name' => '金蝌蚪CMS',

    /*
     * Laravel-admin url prefix.
     */
    'prefix' => 'admin',

    /*
     * Laravel-admin install directory.
     */
    'directory' => base_path('vendor/nthf/jkd/Admin'),

    /*
     * Default theme floder 
     */
    'theme' => [
        'floder' => 'newcms',
    ],

    /*
     * Laravel-admin title.
     */
    'title' => '金蝌蚪内容管理系统',

    /*
     * Laravel-admin auth setting.
     */
    'auth' => [
        'driver' => 'session',
        'provider' => '',
        'model' => Encore\Admin\Auth\Database\Administrator::class,
    ],

    /*
     * Laravel-admin upload setting.
     */
    'upload' => [

        'disk' => 'oss',

        'directory' => [
            'image' => '/uploads/allimg/'.date('ymd'),
            'file' => 'file',
        ],

//        'host' => 'http://t.dongtaitu888.com/t2/',
        'host' => 'http://t.dongtaitu888.com/dedea67/',
    ],

    /*
     * Laravel-admin database setting.
     */
    'database' => [

        // Database connection for following tables.
        'connection' => '',

        // User tables and model.
        'users_table' => 'admin_users',
        'users_model' => Encore\Admin\Auth\Database\Administrator::class,

        // Role table and model.
        'roles_table' => 'admin_roles',
        'roles_model' => Encore\Admin\Auth\Database\Role::class,

        // Permission table and model.
        'permissions_table' => 'admin_permissions',
        'permissions_model' => Encore\Admin\Auth\Database\Permission::class,

        // Menu table and model.
        'menu_table' => 'admin_menu',
        'menu_model' => Encore\Admin\Auth\Database\Menu::class,

        // Pivot table for table above.
        'operation_log_table' => 'admin_operation_log',
        'user_permissions_table' => 'admin_user_permissions',
        'role_users_table' => 'admin_role_users',
        'role_permissions_table' => 'admin_role_permissions',
        'role_menu_table' => 'admin_role_menu',
    ],

    /*
    |---------------------------------------------------------|
    | SKINS         | skin-blue                               |
    |               | skin-black                              |
    |               | skin-purple                             |
    |               | skin-yellow                             |
    |               | skin-red                                |
    |               | skin-green                              |
    |---------------------------------------------------------|
     */
    'skin' => 'skin-black',

    /*
    |---------------------------------------------------------|
    |LAYOUT OPTIONS | fixed                                   |
    |               | layout-boxed                            |
    |               | layout-top-nav                          |
    |               | sidebar-collapse                        |
    |               | sidebar-mini                            |
    |---------------------------------------------------------|
     */
    'layout' => ['sidebar-mini'],

    'version' => '1.0',

    'channel'=>['0'=>'请选择','1'=>'文章栏目','2'=>'聚合栏目','3'=>'导航条','4'=>'底部友链'],

    /* 推荐位 */
    'flags' => [
        'a' => '首页轮翻[a]',
        'b' => '首页置顶[b]',
        'c' => '首页活动[c]',
        'd' => '视频页轮翻[d]',
        'e' => '[e]',
        'f' => '[f]',
        'g' => '[g]',
        'h' => '[h]',
        'i' => '[i]',
        'j' => '[j]',
        'k' => '[k]',
        'l' => '[l]',
        'm' => '[m]',
        'n' => '[n]',
    ],

    'arcranks' => [
        '0' => '正常',
        '-1' => '审核',
        '-2' => '删除',
    ],
    
    'show' => [
        '0' => '聚合',
        '1' => '外链',
        '2' => '栏目',
    ],

    'zhuantishow' => [
        '0' => '标签',
        '1' => '外链',
        '2' => '栏目',
    ],
    
    /* 内容的属性 */
    'archiveshow' => [
        1 => '文章',
        2 => '图片',
        3 => '视频',
    ]


];
