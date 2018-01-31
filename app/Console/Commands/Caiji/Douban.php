<?php

namespace App\Console\Commands\Caiji;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use QL\QueryList;
use App\Console\Commands\Mytraits\Common;

class Douban extends Command
{
    use Common;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'caiji:douban {type_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '根据输入的关键词得到豆瓣信息';

    protected $typeId;

    /**
     * 七牛文件前缀
     */
    protected $savePath;

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
        $this->typeId = $this->argument('type_id');
        $this->initDouban(0);
    }

    public function initDouban($aid)
    {
        $message = '';
        $offset = 0;
        $limit  = 1000;
        try {
            do {
                $movies = DB::table('ca_gather')->select('id','typeid','title')->where('typeid',$this->typeId)->where('is_douban',-1)->skip($offset)->take($limit)->get();
                $tot = count($movies);
                foreach ($movies as $key => $row) {
                    $aid = $row->id;
                    $message = date('Y-m-d H:i:s')." this is id {$row->id} title {$row->title}".PHP_EOL;
                    $this->info($message);
                    $url = 'https://www.douban.com/search?q=' . urlencode($row->title);
                    $conUrl = $this->getList($url);
                    if (!$conUrl) {
                        continue;
                    }
                    $rest = $this->getContent($conUrl,$url);
                    $updateArr = [];

                    foreach ($rest as $k => $v) {
                        switch ($k) {
//                            case 'grade':
//                                $updateArr['grade'] = trim($v);
//                                break;
//                            case 'litpic':
//                                $updateArr['litpic'] = trim($v);
//                                break;
                            case 'body':
                                if (mb_strlen($v) > 250) {
                                    $v = mb_substr($v, 0, 225,'utf-8') . '....';
                                }
                                $updateArr['body'] = trim($v);
                                break;
//                            case 'html':
//                                foreach ($v as $key=>$value){
//                                    switch ($key)
//                                    {
//                                        case 'director':
//                                            $updateArr['director'] = trim($value);
//                                            break;
//                                        case 'actors':
//                                            $updateArr['actors'] = trim($value);
//                                            break;
//                                        case 'year':
//                                            $updateArr['myear'] = trim($value);
//                                            break;
//                                        case 'language':
//                                            $updateArr['lan_guage'] = trim($value);
//                                            break;
//                                        case 'types':
//                                            $updateArr['types'] = trim($value);
//                                            break;
//                                        case 'episode_nums':
//                                            $updateArr['episode_nums'] = trim($value);
//                                            break;
//                                    }
//                                }
//                                break;
                        }
                    }

                    //更新
                    $updateArr['is_douban'] = 0;
                    $rest = DB::table('ca_gather')->where('id', $row->id)->update($updateArr);
                    if ($rest) {
                        $message .= "douban aid {$row->id} update success !!".PHP_EOL;
                        $this->info($message);
                    } else {
                        $message .= "douban aid {$row->id} update fail !!".PHP_EOL;
                        $this->error($message);
                    }
                    usleep(500);
                }
            } while ($tot > 0);
        } catch (\ErrorException $e) {
            $message = 'doban error exception ' . $e->getMessage().PHP_EOL;
            $this->error($message);
            $this->initDouban($aid);
        } catch (\Exception $e) {
            $message = 'doban exception ' . $e->getMessage().PHP_EOL;
            $this->error($message);
            $this->initDouban($aid);
        }
        $message = 'douban end !'.PHP_EOL;
        $this->info($message);
    }


    /**
     * 得到列表页
     */
    public function getList($url)
    {
        $rest = false;
        $ip = getRandIp();
        $ql = QueryList::run('Request',[
            'http' => [
                'target' => $url,
                'referrer' => 'https://www.douban.com/',
                'method' => 'GET',
                'CLIENT-IP:'.$ip,
                'X-FORWARDED-FOR:'.$ip,
                'user_agent'=>'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.8; rv:21.0) Gecko/20100101 Firefox/21.0',
                //等等其它http相关参数，具体可查看Http类源码
            ],
            'callback' => function($html,$args){
                //处理html的回调方法
                return $html;
            },
        ]);
        $content = $ql->setQuery(array(
            'title' => array('a', 'text'),
            'con_url' => array('a', 'href'),
            'type' => array('span', 'text'),
        ),'.result .title')->getData(function ($item){
            $name = $item['title'];
            $last = mb_substr($name, -1, 1, 'utf-8');
            if ($last == ')') {
                $pos = mb_strrpos($name, '(', 0, 'utf-8');
            } elseif ($last == '）') {
                $pos = mb_strrpos($name, '（', 0, 'utf-8');
            } else {
                $pos = null;
            }
            $item['title'] = mb_substr($name, 0, $pos, 'utf-8');
            return $item;
        });

        if (empty($content) === false) {
            foreach ($content as $key => $value) {
                if (empty($value['type']) === false && (mb_stripos($value['type'], '电视', 0, 'utf-8') !== false || mb_stripos($value['type'], '电影', 0, 'utf-8') !== false || mb_stripos($value['type'], '动漫', 0, 'utf-8') !== false)) {
                    $rest = $value['con_url'];
                    break;
                }
            }
        }
        return $rest;
    }

    /**
     * https　curl
     * @param $url
     */
    public function getContent($url,$refurl)
    {
        //更换ip去采集
        $ip = getRandIp();
        $ql = QueryList::run('Request',[
            'http' => [
                'target' => $url,
                'referrer' => $refurl,
                'method' => 'GET',
                'CLIENT-IP:'.$ip,
                'X-FORWARDED-FOR:'.$ip,
                'user_agent'=>'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.8; rv:21.0) Gecko/20100101 Firefox/21.0',
                //等等其它http相关参数，具体可查看Http类源码
            ],
        ]);

        $douban_data = $ql->setQuery(array(
            'grade' => array('.rating_num', 'text'),
            'litpic' => array('#mainpic img', 'src'),
            'body' => array('#link-report', 'text'),
            'html' => array('#info', 'html'),
        ))->getData(function ($item){
            $html = explode('<br>', $item['html']);
            $html = array_map(function ($val) {
                return strip_tags($val);
            }, $html);
            $rest = array();
            foreach ($html as $key => $value) {
                if (empty($value)) {
                    continue;
                }

                list($prefix, $vret) = explode(':', $value);
                $vret = str_replace(' ', '', $vret);
                if (strpos($vret, '/') !== false) {
                    $vret = str_replace('/', ',', $vret);
                }
                switch ($prefix) {
                    case '导演':
                        $rest['director'] = $vret;
                        break;
                    case '编剧':
                        $rest['scriptwriter'] = $vret;
                        break;
                    case '主演':
                        $rest['actors'] = mb_substr($vret,0,50,'utf-8');
                        break;
                    case '类型':
                        $rest['types'] = $vret;
                        break;
                    case '制片国家/地区':
                        $rest['area'] = $vret;
                        break;
                    case '语言':
                        $rest['language'] = $vret;
                        break;
                    case '首播':
                        $marest = preg_match('/\d{4}/', $vret, $matchs);
                        if ($marest === 1) {
                            $vret = $matchs[0];
                        } else {
                            $vret = '';
                        }
                        $rest['year'] = $vret;
                        break;
                    case '上映日期':
                        $marest = preg_match('/\d{4}/', $vret, $matchs);
                        if ($marest === 1) {
                            $vret = $matchs[0];
                        } else {
                            $vret = '';
                        }
                        $rest['year'] = $vret;
                        break;
                    case '集数':
                        $rest['episode_nums'] = $vret;
                        break;
                }
            }
            $item['html'] = $rest;
            $item['body'] = isset($item['body']) ? strtr($item['body'], array("\r" => '', "\n" => '', '©豆瓣' => '', '豆瓣' => '', ' ' => '')) : '';
            return $item;
        });
        if (is_array($douban_data)) {
            $douban_data = $douban_data[0];
        }
        return $douban_data;
    }

}
