<?php

namespace App\Console\Commands\Caiji;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use QL\QueryList;

class DongGatherNihan extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'caiji:donggathernihan';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '采集http://www.neihanmanhua.cc/s/luguan数据';

    /**
     * 栏目id
     * @var int
     */
    protected $typeid = 6;

    /**
     * 网站标识
     */
    protected $netFlag = 'nihan';

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
        //
        $pageTot = 124;
        $url = 'http://www.neihanmanhua.cc/s/luguan/';
//        $this->getList($pageTot,$url);
        $this->getContent();
    }

    public function getList($pageTot,$url)
    {
        for ($i = 1; $i <= $pageTot; $i++) {
            if ($i == 1) {
                $listUrl = $url;
            } else {
                $listUrl = $url . 'index_' . $i . '.html';
            }

            $list = QueryList::Query($listUrl, array(
                'title' => array('a', 'text'),
                'litpic' => array('a', 'html'),
                'con_url' => array('a', 'href')
            ), '.piclist li', 'utf-8', 'gb2312', true)->getData(function ($item) {
                $marest = preg_match('/xSrc\s*=\s*["\'](.*?)["\']/is', $item['litpic'], $matchs);
                if ($marest === 1) {
                    $litpic = $matchs[1];
                } else {
                    $litpic = '';
                }
                $item['litpic'] = $litpic;
                $item['con_url'] = 'http://www.neihanmanhua.cc' . $item['con_url'];
                return $item;
            });

            //保存到数据库中去
            foreach ($list as $key => $value) {
                $saveArr = [
                    'typeid' => $this->typeid,
                    'title' => $value['title'],
                    'litpic' => $value['litpic'],
                    'con_url' => $value['con_url'],
                    'net_flag' => $this->netFlag,
                    'created_at' => date('Y-m-d H:i:s'),
                ];
                $rest = DB::table('dong_gather')->insert($saveArr);
                if ($rest) {
                    $this->info('insert dong list gather success');
                }
            }
//            dd(222);
            sleep(1);
        }
        $this->info('insert dong list gather end');
    }

    public function getContent()
    {

        $take = 20;

        do {
            $archives = DB::table('dong_gather')->where('is_con', -1)->where('net_flag',$this->netFlag)->take($take)->get();

            $tot = count($archives);
            foreach ($archives as $key => $value) {
                $this->info("{$key}/{$tot} con url is {$value->con_url}");
                if(empty($value->title) === true || empty($value->litpic) === true){
                   //删除不规则数据
                    DB::table('dong_gather')->delete($value->id);
                    continue;
                }

                $contentPageTot = QueryList::Query($value->con_url, array(
                    'page_tot' => array('', 'text'),
                ), '#pageList a')->getData(function ($item) {
                    return $item['page_tot'];
                });

                if (empty($contentPageTot) === true) {
                    $contentPageTot = 1;
                } else {
                    sort($contentPageTot, SORT_NUMERIC);
                    $contentPageTot = array_pop($contentPageTot);
                }

                $imgStr = '';
                for ($i = 1; $i <= $contentPageTot; $i++) {
                    if ($i == 1) {
                        $con_url = $value->con_url;
                    } else {
                        $con_url = str_replace('.html','_'.$i.'.html',$value->con_url);
                    }

                    $img = QueryList::Query($con_url, array(
                        'img' => array('.mnlt img', 'src'),
                    ))->data;

                    if (empty($img) === false) {
                        $oneImgStr = $img[0]['img'];
                        if (preg_match('/^http(.*?)/i', $oneImgStr) === 0) {
                            $oneImgStr = 'http:' . $oneImgStr;
                        }
                        $imgStr .= '<p><img src="' . $oneImgStr . '"/></p>';
                    }
                }
//                dd($imgStr);
                //更新数据库
                $rest = DB::table('dong_gather')->where('id', $value->id)->update(['body' => $imgStr, 'is_con' => 0]);
                if ($rest) {
                    $this->info('this aid is ' . $value->id . ' content save success');
                } else {
                    $this->info('this aid is ' . $value->id . ' content save fail');
                }
                sleep(1);
            }
        } while ($tot > 0);
        $this->info('this content save end');
    }
}
