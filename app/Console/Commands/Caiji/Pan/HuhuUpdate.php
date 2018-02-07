<?php

namespace App\Console\Commands\Caiji\Pan;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use QL\QueryList;
use App\Console\Commands\Mytraits\Common;
use App\Console\Commands\Mytraits\DedeLogin;

class HuhuUpdate extends Command
{
    use Common;
    use DedeLogin;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'caiji:pan_huhu_update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'huhu pan 每日更新';

    protected $channelId = 17;
    protected $typeId;

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
        $typeids = array(26, 27, 28, 29);
        $url = null;
        foreach ($typeids as $key => $value) {
            $this->typeId = $value;
            switch ($value) {
                //电影
                case 26:
                    $url = 'http://huhupan.com/dyfl/';
                    break;
                case 27:
                    //动漫
                    $url = 'http://huhupan.com/rmdm/';
                    break;
                case 28:
                    //电视剧
                    $url = 'http://huhupan.com/dsj/';
                    break;
                case 29:
                    //综艺
                    $url = 'http://huhupan.com/zyjm/';
                    break;
            }

            $this->movieList($url, $value);
            $this->getContent($value);
            //下载图片,到本地
            if(env('UPLOAD_IMG_DIRVER') == 'local') {
                $this->litpicDownload();
            }elseif (env('UPLOAD_IMG_DIRVER') == 'qiniu'){
                $this->litpicDownloadQiniu();
            }
            //豆瓣
            $this->call('caiji:douban', ['type_id' => $value]);
            //将不符合的数据删除掉
            DB::table('ca_gather')->where('is_litpic',-1)->delete();
            DB::table('ca_gather')->where('is_douban',-1)->delete();

            //发布
            $this->dedemoviePost();
            //更新
            $this->dedeupdatePost();
            $this->info(date('Y-m-d H:i:s') . " typeid {$value} 上线部署完成!");
        }
    }


    /**
     * @param $url
     * @param $typeId
     */
    public function movieList($url, $typeId)
    {
        $host = 'http://huhupan.com';
        //取出最大的时间
        $maxTime = DB::table('ca_gather')->where('typeid', $this->typeId)->where('is_post', 0)->max('m_time');

        for ($i = 1; $i <= 5; $i++) {
            $sleep = mt_rand(5, 10);
            $this->info(date('Y-m-d H:i:s') . " pan huhu update page {$i} typeid {$typeId}");
            if ($i == 1) {
                $lurl = $url . 'index.html';
            } else {
                $lurl = $url . 'index_' . $i . '.html';
            }

            $ip = getRandIp();
            $ql = QueryList::run('Request', [
                'target' => $lurl,
                'method' => 'GET',
                'cache-control' => 'no-cache',
                'client-ip' => $ip,
                'x-forwarded-for' => $ip,
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3100.0 Safari/537.36',
            ]);
            $data = $ql->setQuery(array(
                'title' => array('h2 a:eq(1)', 'text'),
                'litpic' => array('.viewimg img', 'src'),
                'con_url' => array('h2 a:eq(1)', 'href'),
                'm_time' => array('.preem', 'text')
            ), '.main .block:gt(0)')->getData(function ($item) use ($host) {
                if (empty($item['title']) || empty($item['litpic']) || empty($item['con_url']) || empty($item['m_time']) || $item['con_url'] == 'http://quan.huhupan.com') {
                    return false;
                }
                if (preg_match('/^\/(.*?)/is', $item['litpic'])) {
                    $item['litpic'] = $host . $item['litpic'];
                }
                if (preg_match('/^\/(.*?)/is', $item['con_url'])) {
                    $item['con_url'] = $host . $item['con_url'];
                }

                if (preg_match('/\d{4}\-\d{2}\-\d{2}/is', $item['m_time'], $matchs)) {
                    $item['m_time'] = $matchs[0];
                } else {
                    $item['m_time'] = date('Y-m-d');
                }
                return $item;
            });
            //判断是否存在
            $ltot = count($data);
            foreach ($data as $key => $value) {
                $this->info(date('Y-m-d H:i:s') . " pan huhu list {$key}/{$ltot}");
                if ($value) {
                    $isAlready = DB::table('ca_gather')->where('typeid', $typeId)->where('title_hash', md5($value['title']))->first();
                    if ($isAlready) {
                        //判断日期
                        if (strtotime($value['m_time']) > strtotime($maxTime)) {
                            //更新这条记录
                            DB::table('ca_gather')->where('id', $isAlready->id)->update([
                                'is_update' => -1,
                                'is_con' => -1,
                            ]);
                        }

                        continue;
                    }
                    //保存新内容
                    $saveArr = array_merge($value, ['title_hash' => md5($value['title']), 'typeid' => $typeId,'is_douban'=>-1]);
                    $rest = DB::table('ca_gather')->insert($saveArr);
                    if ($rest) {
                        $this->info(date('Y-m-d H:i:s') . " pan huhu typeid {$typeId} list save success !!");
                    } else {
                        $this->error(date('Y-m-d H:i:s') . " pan huhu typeid {$typeId} list save fail !!");
                    }
                }
            }
            sleep($sleep);
        }
        $this->info(date('Y-m-d H:i:s') . " pan huhu typeid {$typeId} list end !!");
    }


    /**
     *
     */
    public function getContent($typeId, $minId = 0)
    {
        $host = 'http://huhupan.com';
        $take = 100;
        $arc = null;
        $sleep = null;
        $data = null;
        $types = null;
        $con = null;
        try {
            do {
                $arc = DB::table('ca_gather')->select('id', 'con_url')->where('is_con', -1)->where('typeid', $typeId)->where('id', '>', $minId)->take($take)->get();
                $tot = count($arc);

                foreach ($arc as $key => $value) {
                    $minId = $value->id;
                    $this->info(date('Y-m-d H:i:s') . " {$key}/{$tot} pan huhu typeid {$typeId} content id is {$value->id} url is {$value->con_url}");

                    $ip = getRandIp();
                    $sleep = mt_rand(5, 10);
                    $ql = QueryList::run('Request', [
                        'target' => $value->con_url,
                        'method' => 'GET',
                        'cache-control' => 'no-cache',
                        'client-ip' => $ip,
                        'x-forwarded-for' => $ip,
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3100.0 Safari/537.36',
                    ]);
                    $types = $ql->setQuery(array(
                        'type' => array('.meihua_1', 'text'),
                    ))->getData(function ($item) {
                        return $item['type'];
                    });
                    //休息
                    sleep($sleep);
                    $tk = array_search('网盘下载列表', $types);
                    if ($tk === false) {
                        //删除这条记录
                        DB::table('ca_gather')->where('id', $value->id)->delete();
                        continue;
                    }

                    $ip = getRandIp();
                    $sleep = mt_rand(5, 10);
                    $ql = QueryList::run('Request', [
                        'target' => $value->con_url,
                        'method' => 'GET',
                        'cache-control' => 'no-cache',
                        'client-ip' => $ip,
                        'x-forwarded-for' => $ip,
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3100.0 Safari/537.36',
                    ]);
                    $data = $ql->setQuery(array(
                        'pan_url' => array('.meihua_2_1:eq(' . $tk . ') .meihua_btn:first', 'href')
                    ))->getData(function ($item) use ($host) {
                        $item['pan_url'] = $host . $item['pan_url'];
                        return $item['pan_url'];
                    });
                    //休息
                    sleep($sleep);
                    if (empty($data)) {
                        //删除这条记录
                        DB::table('ca_gather')->where('id', $value->id)->delete();
                        continue;
                    }

                    $ip = getRandIp();
                    $sleep = mt_rand(5, 10);
                    $ql = QueryList::run('Request', [
                        'target' => $data[0],
                        'method' => 'GET',
                        'cache-control' => 'no-cache',
                        'client-ip' => $ip,
                        'x-forwarded-for' => $ip,
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3100.0 Safari/537.36',
                    ]);
                    $types = $ql->setQuery(array(
                        'type' => array('.biaoti', 'text'),
                    ))->getData(function ($item) {
                        return $item['type'];
                    });
                    //休息
                    sleep($sleep);
                    $tk = array_search('网盘下载列表', $types);
                    if ($tk === false) {
                        //删除这条记录
                        DB::table('ca_gather')->where('id', $value->id)->delete();
                        continue;
                    }

                    $ip = getRandIp();
                    $sleep = mt_rand(5, 10);
                    $ql = QueryList::run('Request', [
                        'target' => $data[0],
                        'method' => 'GET',
                        'cache-control' => 'no-cache',
                        'client-ip' => $ip,
                        'x-forwarded-for' => $ip,
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3100.0 Safari/537.36',
                    ]);
                    $con = $ql->setQuery(array(
                        //标题
                        'title' => array('.box:eq(' . $tk . ') .box1_4 a', 'text'),
                        //链接
                        'link' => array('.box:eq(' . $tk . ') .box1_4 a', 'href'),
                        //密码
                        'pass' => array('.box:eq(' . $tk . ') .box1_6 input', 'value'),
                    ))->data;
                    //休息
                    sleep($sleep);

                    $downLink = '';
                    foreach ($con as $ck => $cv) {
                        if (stripos($cv['link'], 'pan') !== false || stripos($cv['link'], 'yunpan') !== false) {
                            $downLink .= '标题:' . $cv['title'] . ' 链接:' . $cv['link'] . ' 密码:' . $cv['pass'] . ',';
                        } else {
                            $downLink .= '标题:' . $cv['title'] . ' 链接:' . $cv['link'] . ',';
                        }
                    }
                    $downLink = rtrim($downLink, ',');
                    //更新到数据库中
                    $rest = DB::table('ca_gather')->where('id', $value->id)->update([
                        'down_link' => $downLink,
                        'is_con' => 0
                    ]);
                    if ($rest) {
                        $this->info(date('Y-m-d H:i:s') . " pan huhu typeid {$typeId} content id {$value->id} update success !!");
                    } else {
                        $this->info(date('Y-m-d H:i:s') . " pan huhu typeid {$typeId} content id {$value->id} update fail !!");
                    }
                }
            } while ($tot > 0);
            $this->info(date('Y-m-d H:i:s') . "pan huhu typeid {$typeId} content update end!!");
        } catch (\Exception $e) {
            $this->error(date('Y-m-d H:i:s') . "pan huhu content exception {$e->getMessage()} file {$e->getFile()} line {$e->getLine()}");
            DB::table('ca_gather')->where('id', $minId)->delete();
            $this->getContent($typeId, $minId);
        } catch (\ErrorException $e) {
            $this->error(date('Y-m-d H:i:s') . "pan huhu content error exception {$e->getMessage()} file {$e->getFile()} line {$e->getLine()}");
            DB::table('ca_gather')->where('id', $minId)->delete();
            $this->getContent($typeId, $minId);
        }
    }
}
