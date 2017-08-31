<?php

use Illuminate\Routing\Router;

Admin::registerHelpersRoutes();

Route::group([
    'prefix'        => config('admin.prefix'),
    'namespace'     => Admin::controllerNamespace(),
    'middleware'    => ['web', 'admin'],
], function (Router $router) {

    $router->get('/', 'HomeController@index');
    //分类管理
    $router->resource('arctypes', 'ArctypeController');
    //采集链接管理
    $router->resource('gurls', 'GurlController');
    //采集规则管理
    $router->resource('rules', 'RuleController');
    //规则运行管理
    $router->resource('rule_run', 'RuleRunController');

});

Route::group([
    'prefix'        => config('admin.prefix'),
    'namespace'     => Admin::controllerNamespace(),
    'middleware'    => ['web', 'admin','admin.permission:deny,editor'],
], function (Router $router) {
    //采集规则管理
    $router->resource('rules', 'RuleController');
});
