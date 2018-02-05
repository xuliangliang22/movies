<?php

namespace App\Console\Commands\Caiji\Movie;

use Illuminate\Console\Command;
use QL\QueryList;

class Yy4480 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'caiji:movie_yy4480';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //.subnav-tv .subnav-movie
//        $url = 'http://aaxxy.com';
//        $data = QueryList::Query($url,array(
//            'name' => array('','text'),
//            'link' => array('','href'),
//        ),'.subnav-movie a')->getData(function ($item) use ($url){
//            $item['link'] = $url.$item['link'];
//            return $item;
//        });
//        file_put_contents(storage_path('logs/yy4480.log'),'电影：'.var_export($data,true).PHP_EOL,FILE_APPEND);
//        dd('ok');

        //list
//        $url = 'http://aaxxy.com/vod-list-id-2-pg-1-order--by-time-class-26-year-0-letter--area--lang-.html';
//        $content = QueryList::Query($url,array(
//            'title' => array('img','alt'),
//            'litpic' => array('img','data-original'),
//            'con_url' => array('','href'),
//        ),'.show-list .play-img')->getData(function ($item){
//            $item['title'] = trim($item['title']);
//            $item['litpic'] = 'http://'.$item['litpic'];
//            $item['con_url'] = 'http://aaxxy.com'.$item['con_url'];
//            return $item;
//        });

        //$url = 'http://aaxxy.com/vod-play-id-7404-src-1-num-42.html';
//        $url = 'http://h1.aayyc.com/ckplayer/letv/index.m3u8?vid=74a74Vg6fynj/DzT7Oj3q5bhBkPwt+Vir/j5h4jJWn+RsCd5Dw&height=449';
//        $html = QueryList::Query($url,array())->getHtml();
//        file_put_contents(storage_path('logs/content.html'),$html);
//        dd('ok');
    }
}
