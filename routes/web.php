<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test', function () {
    //参数不需按照顺序排列
//    Artisan::call('caiji:ygdy8_rhantvs_update',['page_start'=>1,'type_id'=>18,'page_tot'=>1,'--queue' => 'list']);
//    Artisan::call('caiji:test');
//    echo $rest;
});


