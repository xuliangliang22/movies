<?php

namespace App\Console\Commands\Caiji\Pan;

use DiDom\Query;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use QL\QueryList;
use App\Console\Commands\Mytraits\Common;

class Huhu extends Command
{
    use Common;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'caiji:pan_huhu';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'huhu pan resource';

    protected $channelId = 17;
    protected $sleep;
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->initBegin();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $typeids = array(26,27,28,29);
        $this->sleep = mt_rand(10,30);
        $url = null;
        foreach ($typeids as $key=>$value) {
            switch ($value) {
                //电影
                case 26:
                    $url = 'http://huhupan.com/dyfl/';
                    break;
                case 27:
                    //电视剧
                    $url = 'http://huhupan.com/rmdm/';
                    break;
                case 28:
                    //动漫
                    $url = 'http://huhupan.com/dsj/';
                    break;
                case 29:
                    //综艺
                    $url = 'http://huhupan.com/zyjm/';
                    break;
            }
            $this->getList($url,$value);
//            $this->getContent($value);
//            //下载图片
//            $this->call('xiazai:img',['action'=>'litpic','type_id'=>$value]);
//            //豆瓣
//            $this->call('caiji:douban',['type_id'=>$value]);
//            //dede
//            $this->call('send:dedea67post', ['channel_id' => $this->channelId, 'typeid' => $value]);
//            if (file_exists($this->dedeSendStatusFile)) {
//                //更新列表页
//                $this->info(date('Y-m-d H:i:s')." typeid {$value} 更新列表页");
//                $this->call('dede:makehtml',['type'=>'list','typeid'=>$value]);
//            }
//            $this->info(date('Y-m-d H:i:s')." typeid {$value} 上线部署完成!");
        }
    }


    public function getList($url,$typeId)
    {
        $host = 'http://huhupan.com';
        //获得总页数
        $ip = getRandIp();
        $ql = QueryList::run('Request',[
            'target' => $url,
            'method' => 'GET',
            'cache-control' => 'no-cache',
            'client-ip' => $ip,
            'x-forwarded-for' => $ip,
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3100.0 Safari/537.36',
        ]);
        $pageTot = $ql->setQuery(array(
            'page_tot' => array('.pagination a:last','href'),
        ))->getData(function ($item){
            preg_match('/\d+/',$item['page_tot'],$matchs);
            $item['page_tot'] = $matchs[0];
            return $item['page_tot'];
        });

        if(isset($pageTot[0]) === false || is_numeric($pageTot[0]) === false || $pageTot[0] < 1){
            $this->error("page tot is {$pageTot} exit!!!");
            return;
        }
        //休息
        sleep($this->sleep);

        for ($i=1;$i<=$pageTot[0];$i++) {
            $this->info(date('Y-m-d H:i:s')." pan huhu page {$i}/{$pageTot[0]} typeid {$typeId}");
            if($i == 1){
                $lurl = $url.'index.html';
            }else{
                $lurl = $url.'index_'.$i.'.html';
            }

            $ip = getRandIp();
            $ql = QueryList::run('Request',[
                'target' => $lurl,
                'method' => 'GET',
                'cache-control' => 'no-cache',
                'client-ip' => $ip,
                'x-forwarded-for' => $ip,
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3100.0 Safari/537.36',
            ]);
            $data = $ql->setQuery(array(
                'title' => array('h2 a:eq(1)','text'),
                'litpic' => array('.viewimg img','src'),
                'con_url' => array('h2 a:eq(1)','href'),
                'm_time' => array('.preem','text')
            ),'.main .block:gt(0)')->getData(function ($item) use($host){
                if(empty($item['title']) || empty($item['litpic']) || empty($item['con_url'])|| empty($item['m_time']) || $item['con_url'] == 'http://quan.huhupan.com'){
                    return false;
                }
                if(preg_match('/^\/(.*?)/is',$item['litpic'])){
                    $item['litpic'] = $host.$item['litpic'];
                }
                if(preg_match('/^\/(.*?)/is',$item['con_url'])){
                    $item['con_url'] = $host.$item['con_url'];
                }

                if(preg_match('/\d{4}\-\d{2}\-\d{2}/is',$item['m_time'],$matchs)){
                    $item['m_time'] = $matchs[0];
                }else{
                    $item['m_time'] = date('Y-m-d');
                }
                return $item;
            });

            //判断是否存在
            $ltot = count($data);
            foreach ($data as $key=>$value){
                $this->info(date('Y-m-d H:i:s')." pan huhu list {$key}/{$ltot}");
                if($value){
                    $isAlready = DB::connection($this->dbName)->table($this->tableName)->where('typeid', $typeId)->where('title_hash', md5(trim($value['title'])))->first();
                    if(count($isAlready) > 0){
                        //判断日期
                        if(strtotime($value['m_time']) > strtotime($isAlready->m_time)){
                            //更新这条记录
                            DB::connection($this->dbName)->table($this->tableName)->where('id', $isAlready->id)->update([
                                'is_update' => -1
                            ]);
                        }else{
                            continue;
                        }
                    }else{
                        //保存新内容
                        $saveArr = array_merge($value,['title_hash'=>md5($value['title']),'typeid'=>$typeId]);
                        $rest = DB::connection($this->dbName)->table($this->tableName)->insert($saveArr);
                        if($rest){
                            $this->info(date('Y-m-d H:i:s')." pan huhu list save success !!");
                        }else{
                            $this->error(date('Y-m-d H:i:s')." pan huhu list save fail !!");
                        }
                    }
                }
            }
            sleep($this->sleep);
        }
        $this->info(date('Y-m-d H:i:s')." pan huhu list end !!");
    }


    public function getContent($typeId)
    {
        $host = 'http://huhupan.com';
        $minId = 0;
        $take = 100;
        do {
            $arc = DB::connection($this->dbName)->table($this->tableName)->select('id', 'con_url')->where('is_con', -1)->where('typeid',$typeId)->where('id', '>', $minId)->take($take)->get();
            $tot = count($arc);

            foreach ($arc as $key => $value) {
                $minId = $value->id;
                $this->info(date('Y-m-d H:i:s') . " {$key}/{$tot} pan huhu content id is {$value->id} url is {$value->con_url}");
                $ip = getRandIp();
                $ql = QueryList::run('Request',[
                    'target' => $value->con_url,
                    'method' => 'GET',
                    'cache-control' => 'no-cache',
                    'client-ip' => $ip,
                    'x-forwarded-for' => $ip,
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3100.0 Safari/537.36',
                ]);
                $types = $ql->setQuery(array(
                    'type' => array('.meihua_1','text'),
                ))->getData(function ($item){
                    return $item['type'];
                });
                //休息
                sleep($this->sleep);
                $tk = array_search('网盘下载列表',$types);
                if($tk === false){
                    //删除这条记录
                    DB::connection($this->dbName)->table($this->tableName)->where('id', $value->id)->delete();
                    continue;
                }

                $ip = getRandIp();
                $ql = QueryList::run('Request',[
                    'target' => $value->con_url,
                    'method' => 'GET',
                    'cache-control' => 'no-cache',
                    'client-ip' => $ip,
                    'x-forwarded-for' => $ip,
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3100.0 Safari/537.36',
                ]);
                $data = $ql->setQuery(array(
                    'pan_url' => array('.meihua_2_1:eq('.$tk.') .meihua_btn:first','href')
                ))->getData(function ($item) use($host){
                    $item['pan_url'] = $host.$item['pan_url'];
                    return $item['pan_url'];
                });
                //休息
                sleep($this->sleep);
                if(empty($data)){
                    //删除这条记录
                    DB::connection($this->dbName)->table($this->tableName)->where('id', $value->id)->delete();
                    continue;
                }

                $ip = getRandIp();
                $ql = QueryList::run('Request',[
                    'target' => $data[0],
                    'method' => 'GET',
                    'cache-control' => 'no-cache',
                    'client-ip' => $ip,
                    'x-forwarded-for' => $ip,
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3100.0 Safari/537.36',
                ]);
                $types = $ql->setQuery(array(
                    'type' => array('.biaoti','text'),
                ))->getData(function ($item){
                    return $item['type'];
                });
                //休息
                sleep($this->sleep);
                $tk = array_search('网盘下载列表',$types);
                if($tk === false){
                    //删除这条记录
                    DB::connection($this->dbName)->table($this->tableName)->where('id', $value->id)->delete();
                    continue;
                }

                $ip = getRandIp();
                $ql = QueryList::run('Request',[
                    'target' => $data[0],
                    'method' => 'GET',
                    'cache-control' => 'no-cache',
                    'client-ip' => $ip,
                    'x-forwarded-for' => $ip,
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3100.0 Safari/537.36',
                ]);
                $con = $ql->setQuery(array(
                    //标题
                    'title' => array('.box:eq('.$tk.') .box1_4 a','text'),
                    //链接
                    'link' => array('.box:eq('.$tk.') .box1_4 a','href'),
                    //密码
                    'pass' => array('.box:eq('.$tk.') .box1_6 input','value'),
                ))->data;
                //休息
                sleep($this->sleep);
                $downLink = null;
                foreach ($con as $ck=>$cv){
                    $downLink .= '标题:'.$cv['title'].' 链接:'.$cv['link'].' 密码:'.$cv['pass'].',';
                }
                $downLink = rtrim($downLink,',');
                //更新到数据库中
                $rest = DB::connection($this->dbName)->table($this->tableName)->where('id', $value->id)->update([
                    'down_link' => $downLink,
                    'is_con' => 0
                ]);
                if($rest){
                    $this->info(date('Y-m-d H:i:s') . " pan huhu content update success !!");
                }else{
                    $this->info(date('Y-m-d H:i:s') . " pan huhu content update fail !!");
                }
            }
        } while ($tot > 0);
        $this->info(date('Y-m-d H:i:s') . "pan huhu content update end!!");
    }
}
