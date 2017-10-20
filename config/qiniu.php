<?php

return [
    'qiniu_data' => [

        //七牛文件后缀
        'qiniu_postfix'=>'?imageslim',

        //本地的数据库名称
        //库名与表名
        'db_name' => 'mysql',
        'table_name' => 'ca_gather',

        //dede后台提交的路径,后台地址后面加/
//        'dede_url' => 'http://localhost:8127/wldy/',
//        'dede_url' => 'http://www.ca2722.com/wldy/',
        'dede_url' => env('DEDE_ADMIN_URL'),
        'dede_user' => env('DEDE_USER'),
        'dede_pwd' => env('DEDE_PASS'),

        //node的网址
        'node_url' => env('NODE_URL'),

        //日志保存位置
        'command_logs_path'=> public_path('command_logs'),
        'command_logs_file'=> date('Ymd').'.log',
        'is_command_logs' =>true,

        //是否在运行cli
        'is_cli'=>true,

        //dede内容有提交或更新
        'dede_send_status_dir' => storage_path('app/public/dede_send_status'),
        'dede_send_status_file' => 'status.txt',

        //语言
        'dede_languages' => [
            'Sprache akan',
            'Amharisch',
            'Arabisch',
            'Aramäisch',
            'Assamese',
            'Die baskische Sprache',
            'In Bayern',
            'Weißrussische Sprache',
            'Bemba',
            'Bengali',
             'Bulgarisch',
             'Bulgarien chuvash Sprache',
             'Kambodscha',
             'Katalanisch',
             'Cebuano',
             '齐切瓦 Sprache',
             'Chinesisch',
             'Kreolisch',
             'Kroatisch',
             'Die ungarische Sprache',
             'Die isländische Sprache',
             'Ido',
             'Ibo',
             'Bahasa Indonesia',
             'Die Internationale Sprache latein',
             'Italienisch',
             'Japanisch',
             '齐切瓦 Sprache',
             'Kannada',
             'Die kasachische Sprache',
             'Käse - ba - Sprache',
             'Koreanisch',
             'Laos',
             'Latein',
             'Madagaskar',
             'Malayalam',
             'Deneve (spanisch',
             'Marathi',
             'Mongolische',
             'Der indischen Sprache',
        ],
    ],
];

