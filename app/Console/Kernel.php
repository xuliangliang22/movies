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


        //采集阳光电影8所有内地电视剧
        Commands\Caiji\Ygdy8\DaluTvs::class,
        //采集阳光电影8所有欧美电视剧
        Commands\Caiji\Ygdy8\OumeiTvs::class,


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


        //Y3600
        //娱乐新闻采集
        Commands\Caiji\News\Y3600::class,
        //跟踪采集娱乐新闻
        Commands\Caiji\News\Y3600Update::class,
        //================//=============//==========



        //修改dede_archives的litpic,加?imageslim后缀名称
        Commands\Ca2722\Litpic::class,
        //测试使用
        Commands\Caiji\Test::class,


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
        $schedule->command('caiji:ygdy8_dalumovies_update',['page_start'=>1,'page_tot'=>2,'type_id'=>13,'--queue'=>'all'])->weekly()->mondays()->at('00:30')->withoutOverlapping();;
        $schedule->command('caiji:ygdy8_rhanmovies_update',['page_start'=>1,'page_tot'=>2,'type_id'=>14,'--queue'=>'all'])->weekly()->tuesdays()->at('00:30')->withoutOverlapping();;
        $schedule->command('caiji:ygdy8_oumeimovies_update',['page_start'=>1,'page_tot'=>2,'type_id'=>15,'--queue'=>'all'])->weekly()->wednesdays()->at('00:30')->withoutOverlapping();;

        $schedule->command('caiji:ygdy8_dalutvs_update',['page_start'=>1,'page_tot'=>2,'type_id'=>17,'--queue'=>'all'])->weekly()->thursdays()->at('00:30')->withoutOverlapping();;
        $schedule->command('caiji:ygdy8_rhantvs_update',['page_start'=>1,'page_tot'=>2,'type_id'=>18,'--queue'=>'all'])->weekly()->fridays()->at('00:30')->withoutOverlapping();;
        $schedule->command('caiji:ygdy8_oumeitvs_update',['page_start'=>1,'page_tot'=>2,'type_id'=>19,'--queue'=>'all'])->weekly()->sundays()->at('00:30')->withoutOverlapping();;

        //2015tv经典
        $schedule->command('caiji:tv2017_jindian_update',['page_start'=>1,'page_tot'=>1,'type_id'=>23,'--queue'=>'all'])->weekly()->saturdays()->at('00:30')->withoutOverlapping();;


        //新闻
        $schedule->command('caiji:news_y3600_update',['page_start'=>1,'page_tot'=>1,'type_id'=>22,'--queue'=>'all'])->twiceDaily(1,13)->withoutOverlapping();;
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
