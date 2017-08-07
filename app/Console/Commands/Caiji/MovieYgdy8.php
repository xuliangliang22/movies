<?php

namespace App\Console\Commands\Caiji;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use QL\QueryList;
use App\Console\Commands\Mytraits\Ygdy8;

class MovieYgdy8 extends Command
{
    use Ygdy8;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'caiji:movieygdy8 {aid?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '采集阳光电影数据http://www.ygdy8.com';


    /**
     * 文章id
     */
    protected $aid;

    /**
     * typeid
     */
//    protected $typeId = 14;
//    protected $typeId = 17;
//    protected $typeId = 19;
    protected $typeId = 20;

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
        // php artisan caiji:movieygdy8
        $aid = $this->argument('aid');
        if ($aid === null) {
            $this->aid = 0;
        } else {
            $this->aid = $aid;
        }


        //国内电影
//        $this->movieList(13,'http://www.ygdy8.net/html/tv/oumeitv/list_9_2.html');
//        $this->getContent('other');
//        $this->updateDownlink();
//        $url = 'http://www.ygdy8.net/html/gndy/dyzz/20150531/48185.html';
//        $this->getConSaveArr($url);
        //日韩 26 http://www.ygdy8.net/html/gndy/rihan/list_6_2.html
        //欧美 169 http://www.ygdy8.net/html/gndy/oumei/list_7_2.html
        //最新电影，判断时间用来更新http://www.ygdy8.net/html/gndy/dyzz/index.html  http://www.ygdy8.net/html/gndy/dyzz/list_23_2.html
        //内地电视 16 http://www.ygdy8.net/html/tv/hytv/list_71_2.html
        //日韩 34 http://www.ygdy8.net/html/tv/rihantv/list_8_2.html
        //欧美 13 http://www.ygdy8.net/html/tv/oumeitv/index.html http://www.ygdy8.net/html/tv/oumeitv/list_9_2.html
        //港台 1 http://www.ygdy8.net/html/tv/gangtai/index.html

        //完善内容
//        $this->perfectContent();
    }




    /**
     * 更新下载链接
     */
    public function updateDownlink()
    {
        try
        {
            $take = 10;
            do {
                $arc = DB::connection('dedea67')->table('gather_dedea67')->whereRaw('down_link not like "%ftp%"')->where('typeid', $this->typeId)->take($take)->get();
                dd($arc);
                $tot = count($arc);

                foreach ($arc as $key => $value) {
                    $this->aid = $value->id;
                    $this->info("{$key}/{$tot} id is {$value->id} url is {$value->con_url}");

                    //得到保存的数组
                    $this->curl->add()->opt_targetURL($value->con_url)->done();
                    $this->curl->run();
                    $html = $this->curl->getAll();
                    $html = $html['body'];
                    $html = iconv('gb2312', 'utf-8//IGNORE', $html);

                    $content = QueryList::Query($html, array(
                        'down_link' => array('table', 'html'),
                    ), '.co_content8')->getData(function ($item) {
                        $item['down_link'] = QueryList::Query($item['down_link'], array(
                            'down_link' => array('a', 'text'),
                        ))->getData(function ($item) {
                            return urldecode($item['down_link']);
                        });
                        return $item;
                    });
//                    dd($content);
                    $rest = DB::connection('dedea67')->table('gather_dedea67')->where('id', $value->id)->update(['down_link' => implode(',', $content[0]['down_link'])]);
                    if ($rest) {
                        $this->info('save con success');
                    } else {
                        $this->error('save con fail');
                    }
                }
            } while ($tot > 0);
            $this->info('save con end');
        }catch (\ErrorException $e){
            if($e->getMessage() == 'The received content is empty!')
            {
                DB::connection('dedea67')->table('gather_dedea67')->delete($this->aid);
                $this->error("{$this->aid} content is empty , delete success");
            }
            $this->updateDownlink();
        }
    }







}
