<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //动态图数据提交到线上后台
        Commands\Send\DongPost::class,
        //图片下载
        Commands\Xiazai\DongImgDown::class,

        //动态图内容采集
        Commands\Caiji\DongGatherLutub::class,
        Commands\Caiji\DongGatherNihan::class,

        //dedea67电影网采集
        Commands\Caiji\MovieYgdy8::class,
        Commands\Xiazai\ImgDownYgdy8::class,
        Commands\Send\Dedea67Post::class,

        //豆瓣网更新影视详情信息
        Commands\Caiji\Douban::class,

        //测试使用
        Commands\Caiji\Test::class,


        //七牛云上传文件
        Commands\Send\QiniuImgsUp::class,
        //七牛云删除文件
        Commands\Send\QiniuImgsDel::class,



        //采集阳光电影8所有内地电视剧
        Commands\Caiji\Ygdy8\DaluTvs::class,
        //采集阳光电影8所有日韩电视剧
        Commands\Caiji\Ygdy8\RhanTvs::class,


        //将没有的缩略图从面度上截取
        Commands\Caiji\BaiduLitpic::class,


    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')
        //          ->hourly();
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        require base_path('routes/console.php');
    }
}
