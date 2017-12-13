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
        //将没有的缩略图从百度上截取
        Commands\Caiji\BaiduLitpic::class,
        //================//=============//==========

        //豆瓣网更新影视详情信息
        Commands\Caiji\Douban::class,
        //百科取出简介信息
        Commands\Caiji\Baike::class,
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


        //阳光电影得到内容页信息***
        Commands\Caiji\Ygdy8GetContent::class,
        //跟踪采集大陆电视剧
        Commands\Caiji\Ygdy8\DaluTvsUpdate::class,
        //跟踪采集日韩电视剧
        Commands\Caiji\Ygdy8\RhanTvsUpdate::class,
        //跟踪采集欧美电视剧
        Commands\Caiji\Ygdy8\OumeiTvsUpdate::class,

        //跟踪采集大陆电影
        Commands\Caiji\Ygdy8\DaluMoviesUpdate::class,
        //跟踪采集日韩电影
        Commands\Caiji\Ygdy8\RhanMoviesUpdate::class,
        //跟踪采集欧美电影
        Commands\Caiji\Ygdy8\OumeiMoviesUpdate::class,

        //Tv2017
        //经典电影采集
        Commands\Caiji\Tv2017\Jindian::class,
        //跟踪采集经典电影
        Commands\Caiji\Tv2017\JindianUpdate::class,

        //duoduo
        //大陆电影
        Commands\Caiji\Duoduo\DaluUpdate::class,
        //================//=============//==========

        //www.huhupan.com
        //云盘资源
        Commands\Caiji\Pan\Huhu::class,
        //云盘资源,跟踪采集
        Commands\Caiji\Pan\HuhuUpdate::class,

        //Y3600
        //娱乐新闻采集
        Commands\Caiji\News\Y3600::class,
        //跟踪采集娱乐新闻
        Commands\Caiji\News\Y3600Update::class,
        //跟踪采集影视评论
        Commands\Caiji\News\M1905Update::class,
        //今日头条
        Commands\Caiji\News\ToutiaoUpdate::class,
        //================//=============//==========

        //修改dede_archives的litpic,加?imageslim后缀名称
        Commands\Ca2722\Litpic::class,
        //测试使用
        Commands\Caiji\Test::class,

        //下载youtube
        Commands\Caiji\Youtube::class,


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
        $schedule->command('caiji:ygdy8_get_content 13')
            ->weekly()->mondays()->at('00:30')
            ->before(function () {
                // Task is about to start...
                Artisan::call('caiji:ygdy8_dalumovies_update',['page_start'=>1,'page_tot'=>1,'type_id'=>13,'--queue'=>'olist']);
            })
            ->after(function () {
                // Task is complete...
                Artisan::call('caiji:ygdy8_dalumovies_update',['page_start'=>1,'page_tot'=>1,'type_id'=>13,'--queue'=>'pic']);
            });
        //间隔半个小时提交到dede后台
        $schedule->command('caiji:ygdy8_dalumovies_update 1 1 13 --queue=dede')->weekly()->mondays()->at('01:00');

        //日韩电影
        $schedule->command('caiji:ygdy8_get_content 14')
            ->weekly()->tuesdays()->at('00:30')
            ->before(function () {
                // Task is about to start...
                Artisan::call('caiji:ygdy8_rhanmovies_update',['page_start'=>1,'page_tot'=>10,'type_id'=>14,'--queue'=>'olist']);
            })
            ->after(function () {
                // Task is complete...
                Artisan::call('caiji:ygdy8_rhanmovies_update',['page_start'=>1,'page_tot'=>10,'type_id'=>14,'--queue'=>'pic']);
            });
        //间隔半个小时提交到dede后台
        $schedule->command('caiji:ygdy8_dalumovies_update 1 1 14 --queue=dede')->weekly()->tuesdays()->at('01:00');

        //欧美电影
        $schedule->command('caiji:ygdy8_get_content 15')
            ->weekly()->wednesdays()->at('00:30')
            ->before(function () {
                // Task is about to start...
                Artisan::call('caiji:ygdy8_oumeimovies_update',['page_start'=>1,'page_tot'=>10,'type_id'=>15,'--queue'=>'olist']);
            })
            ->after(function () {
                // Task is complete...
                Artisan::call('caiji:ygdy8_oumeimovies_update',['page_start'=>1,'page_tot'=>10,'type_id'=>15,'--queue'=>'pic']);
            });
        //间隔半个小时提交到dede后台
        $schedule->command('caiji:ygdy8_dalumovies_update 1 1 15 --queue=dede')->weekly()->wednesdays()->at('01:00');
//----------------------------------------------------------------------------------------------------------------------------------------------------------------------
        //大陆电视剧
        $schedule->command('caiji:ygdy8_get_content 17')
            ->weekly()->thursdays()->at('00:30')
            ->before(function () {
                // Task is about to start...
                Artisan::call('caiji:ygdy8_dalutvs_update',['page_start'=>1,'page_tot'=>10,'type_id'=>17,'--queue'=>'olist']);
            })
            ->after(function () {
                // Task is complete...
                Artisan::call('caiji:ygdy8_dalutvs_update',['page_start'=>1,'page_tot'=>10,'type_id'=>17,'--queue'=>'pic']);
            });
        //间隔半个小时提交到dede后台
        $schedule->command('caiji:ygdy8_dalumovies_update 1 1 17 --queue=dede')->weekly()->thursdays()->at('01:00');

        //日韩电视剧
        $schedule->command('caiji:ygdy8_get_content 18')
            ->weekly()->fridays()->at('00:30')
            ->before(function () {
                // Task is about to start...
                Artisan::call('caiji:ygdy8_rhantvs_update',['page_start'=>1,'page_tot'=>10,'type_id'=>18,'--queue'=>'olist']);
            })
            ->after(function () {
                // Task is complete...
                Artisan::call('caiji:ygdy8_rhantvs_update',['page_start'=>1,'page_tot'=>10,'type_id'=>18,'--queue'=>'pic']);
            });
        //间隔半个小时提交到dede后台
        $schedule->command('caiji:ygdy8_dalumovies_update 1 1 18 --queue=dede')->weekly()->fridays()->at('01:00');

        //欧美电视剧
        $schedule->command('caiji:ygdy8_get_content 19')
            ->weekly()->sundays()->at('00:30')
            ->before(function () {
                // Task is about to start...
                Artisan::call('caiji:ygdy8_oumeitvs_update',['page_start'=>1,'page_tot'=>10,'type_id'=>19,'--queue'=>'olist']);
            })
            ->after(function () {
                // Task is complete...
                Artisan::call('caiji:ygdy8_oumeitvs_update',['page_start'=>1,'page_tot'=>10,'type_id'=>19,'--queue'=>'pic']);
            });
        //间隔半个小时提交到dede后台
        $schedule->command('caiji:ygdy8_dalumovies_update 1 1 19 --queue=dede')->weekly()->sundays()->at('01:00');

//----------------------------------------------------------------------------------------------------------------------------------------------------------------------
        //2015tv经典
        $schedule->command('caiji:tv2017_jindian_update 1 5 23 --queue=all')->weekly()->saturdays()->at('00:30')->withoutOverlapping();

        //新闻
        $schedule->command('caiji:news_y3600_update 1 1 22 --queue=all')->dailyAt('02:30')->withoutOverlapping();
        //影视评论
        $schedule->command('caiji:news_m1905_update 1 2 24 --queue=all')->dailyAt('03:30')->withoutOverlapping();
        //头条娱乐新闻
//        $schedule->command('caiji:news_toutiao_update 1 24')->dailyAt('01:30')->withoutOverlapping()->appendOutputTo('/home/www/liangcommands/public/command_logs/toutiao24.log');
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
