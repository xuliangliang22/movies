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

        //豆瓣网更新影视详情信息
        Commands\Caiji\Douban::class,

        //测试使用
        Commands\Caiji\Test::class,


        //七牛云上传文件
        Commands\Send\QiniuImgsUp::class,
        //七牛云删除文件
        Commands\Send\QiniuImgsDel::class,
        //电影电视剧提交到dede后台
        Commands\Send\Dedea67Post::class,
        //新闻提交到dede后台
        Commands\Send\DedeNewPost::class,



        //采集阳光电影8所有内地电视剧
        Commands\Caiji\Ygdy8\DaluTvs::class,
        //跟踪采集大陆电视剧
        Commands\Caiji\Ygdy8\DaluTvsUpdate::class,
        //采集阳光电影8所有日韩电视剧
        Commands\Caiji\Ygdy8\RhanTvs::class,
        //跟踪采集日韩电视剧
        Commands\Caiji\Ygdy8\RhanTvsUpdate::class,
        //采集阳光电影8所有欧美电视剧
        Commands\Caiji\Ygdy8\OumeiTvs::class,
        //跟踪采集欧美电视剧
        Commands\Caiji\Ygdy8\OumeiTvsUpdate::class,

        //跟踪采集大陆电影
        Commands\Caiji\Ygdy8\DaluMoviesUpdate::class,

        //Tv2017
        //经典电影采集
        Commands\Caiji\Tv2017\Jindian::class,
        //跟踪采集经典电影
        Commands\Caiji\Tv2017\JindianUpdate::class,

        //Y3600
        //娱乐新闻采集
        Commands\Caiji\News\Y3600::class,
        //跟踪采集娱乐新闻
        Commands\Caiji\News\Y3600Update::class,


        //sitemap curls.txt
        Commands\SiteMap\Baidu::class,


        //将没有的缩略图从面度上截取
        Commands\Caiji\BaiduLitpic::class,

        //修改dede_archives的litpic名称
        Commands\Ca2722\Litpic::class,

        //生成dede页面
        Commands\Dede\MakeHtml::class,



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
