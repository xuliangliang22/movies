<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Artisan;

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
        //================//=============//==========


        //下载图图片
        Commands\Xiazai\ImgDownYgdy8::class,
        //================//=============//==========

        //豆瓣网更新影视详情信息
        Commands\Caiji\Douban::class,
        //================//=============//==========


        //七牛云上传文件
        Commands\Send\QiniuImgsUp::class,
        //七牛云删除文件
        Commands\Send\QiniuImgsDel::class,
        //电影电视剧提交到dede后台
        Commands\Send\Dedea67Post::class,
        //新闻提交到dede后台
        Commands\Send\DedeNewPost::class,
        //生成dede页面
        Commands\Dede\MakeHtml::class,
        //================//=============//==========

        //www.huhupan.com
        //云盘资源,跟踪采集
        Commands\Caiji\Pan\HuhuUpdate::class,

        //Y3600
        //娱乐新闻采集
        //跟踪采集娱乐新闻
        Commands\Caiji\News\Y3600Update::class,
        //跟踪采集影视评论
        Commands\Caiji\News\M1905Update::class,
        //================//=============//==========


        //重写阳光电影
        Commands\Caiji\Movie\Ygdy8List::class,
        Commands\Caiji\Movie\Ygdy8Content::class,
        Commands\Caiji\Movie\Ygdy8Other::class,


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

        //大陆电影
        $schedule->command('caiji:movie_ygdy8_content 13')
            ->weekly()->mondays()->at('00:30')
            ->before(function () {
                // Task is about to start...
                Artisan::call('caiji:movie_ygdy8_list',['page_start'=>1,'page_tot'=>1,'typeid'=>13]);
            })
            ->after(function () {
                // Task is complete...
                Artisan::call('caiji:movie_ygdy8_other',['typeid'=>13]);
            });

        //日韩电影
        $schedule->command('caiji:movie_ygdy8_content 14')
            ->weekly()->tuesdays()->at('00:30')
            ->before(function () {
                // Task is about to start...
                Artisan::call('caiji:movie_ygdy8_list',['page_start'=>1,'page_tot'=>1,'typeid'=>14]);
            })
            ->after(function () {
                // Task is complete...
                Artisan::call('caiji:movie_ygdy8_other',['typeid'=>14]);
            });
        //欧美电影
        $schedule->command('caiji:movie_ygdy8_content 15')
            ->weekly()->wednesdays()->at('00:30')
            ->before(function () {
                // Task is about to start...
                Artisan::call('caiji:movie_ygdy8_list',['page_start'=>1,'page_tot'=>1,'typeid'=>15]);
            })
            ->after(function () {
                // Task is complete...
                Artisan::call('caiji:movie_ygdy8_other',['typeid'=>15]);
            });
//----------------------------------------------------------------------------------------------------------------------------------------------------------------------
        //大陆电视剧
        $schedule->command('caiji:movie_ygdy8_content 17')
            ->weekly()->thursdays()->at('00:30')
            ->before(function () {
                // Task is about to start...
                Artisan::call('caiji:movie_ygdy8_list',['page_start'=>1,'page_tot'=>1,'typeid'=>17]);
            })
            ->after(function () {
                // Task is complete...
                Artisan::call('caiji:movie_ygdy8_other',['typeid'=>17]);
            });

        //日韩电视剧
        $schedule->command('caiji:movie_ygdy8_content 18')
            ->weekly()->fridays()->at('00:30')
            ->before(function () {
                // Task is about to start...
                Artisan::call('caiji:movie_ygdy8_list',['page_start'=>1,'page_tot'=>1,'typeid'=>18]);
            })
            ->after(function () {
                // Task is complete...
                Artisan::call('caiji:movie_ygdy8_other',['typeid'=>18]);
            });

        //欧美电视剧
        $schedule->command('caiji:movie_ygdy8_content 19')
            ->weekly()->sundays()->at('00:30')
            ->before(function () {
                // Task is about to start...
                Artisan::call('caiji:movie_ygdy8_list',['page_start'=>1,'page_tot'=>1,'typeid'=>19]);
            })
            ->after(function () {
                // Task is complete...
                Artisan::call('caiji:movie_ygdy8_other',['typeid'=>19]);
            });

        //新闻
        $schedule->command('caiji:news_y3600_update 1 1 22')->dailyAt('02:30')->withoutOverlapping();
        //影视评论
        $schedule->command('caiji:news_m1905_update 1 2 24')->dailyAt('03:30')->withoutOverlapping();

        //huhu_pan资源
        $schedule->command('caiji:pan_huhu_update')->dailyAt('04:00')->withoutOverlapping()->appendOutputTo('/home/www/liangcommands/public/command_logs/huhupan.log');

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
