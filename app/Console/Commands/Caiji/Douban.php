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
        $message = null;
        $take = 10;
        try {
            do {
                $movies = DB::connection($this->dbName)->table($this->tableName)->select('id','typeid','title','is_litpic')->where('id', '>', $aid)->where('typeid', $this->typeId)->where('is_douban', -1)->take($take)->get();
                //保存日志
                if($this->isCommandLogs === true){
                    file_put_contents($this->commandLogsFile,var_export($movies,true),FILE_APPEND);
                }
                $tot = count($movies);
                foreach ($movies as $key => $row) {
                    $aid = $row->id;
                    $message = date('Y-m-d H:i:s')." this is id {$row->id} title {$row->title}".PHP_EOL;
                    $this->info($message);
                    $url = 'https://www.douban.com/search?q=' . $row->title;
                    $conUrl = $this->getList($url);
                    if (!$conUrl) {
                        continue;
                    }
                    $rest = $this->getContent($conUrl,$url);
                    $updateArr = [];

                    foreach ($rest as $k => $v) {
                        switch ($k) {
                            case 'grade':
                                $updateArr['grade'] = trim($v);
                                break;
                            case 'litpic':
                                $updateArr['litpic'] = trim($v);
                                break;
                            case 'body':
                                if (mb_strlen($v) > 250) {
                                    $v = mb_substr($v, 0, 225,'utf-8') . '....';
                                }
                                $updateArr['body'] = trim($v);
                                break;
                            case 'html':
                                foreach ($v as $key=>$value){
                                    switch ($key)
                                    {
                                        case 'director':
                                            $updateArr['director'] = trim($value);
                                            break;
                                        case 'actors':
                                            $updateArr['actors'] = trim($value);
                                            break;
                                        case 'year':
                                            $updateArr['myear'] = trim($value);
                                            break;
                                        case 'language':
                                            $updateArr['lan_guage'] = trim($value);
                                            break;
                                        case 'types':
                                            $updateArr['types'] = trim($value);
                                            break;
                                        case 'episode_nums':
                                            $updateArr['episode_nums'] = trim($value);
                                            break;
                                    }
                                }
                                break;
                        }
                    }

                    if (empty($updateArr) === false) {
                        //保存到数据库
                        if($row->is_litpic == -1 && isset($updateArr['litpic']) === true){
                            //上传这张图
                            $this->savePath = config('admin.upload.directory.image').$row->typeid ;
                            $file = $this->imgUpload($updateArr['litpic']);
                            if($file){
                                $ossImg = rtrim(config('filesystems.disks.qiniu.domains.default'),'/').'/'.ltrim($file,'/').config('qiniu.qiniu_data.qiniu_postfix');
                                $updateArr['litpic'] = $ossImg;
                                $updateArr['is_litpic'] = 0;
                            }
                        }else{
                            unset($updateArr['litpic']);
                        }
                        //更新
                        $updateArr['is_douban'] = 0;
                        $rest = DB::connection($this->dbName)->table($this->tableName)->where('id', $row->id)->update($updateArr);
                        if ($rest) {
                            $message .= "douban aid {$row->id} update success !!";
                            $this->info($message);
                        } else {
                            $message .= "douban aid {$row->id} update fail !!";
                            $this->error($message);
                        }
                    }
                    //保存日志
                    if($this->isCommandLogs === true){
                        file_put_contents($this->commandLogsFile,$message,FILE_APPEND);
                    }
                    usleep(500);
                }
            } while ($tot > 0);
        } catch (\ErrorException $e) {
            $this->error('doban errorException ' . $e->getMessage());
            $this->initDouban($aid);
        } catch (\Exception $e) {
            $this->error('doban exception ' . $e->getMessage());
            $this->initDouban($aid);
        }
        $message = 'douban end !';
        $this->info($message);
        //保存日志
        if($this->isCommandLogs === true){
            file_put_contents($this->commandLogsFile,$message,FILE_APPEND);
        }
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
                        $rest['actors'] = $vret;
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
            $item['body'] = strtr($item['body'], array("\r" => '', "\n" => '', '©豆瓣' => '', '豆瓣' => '', ' ' => ''));
            return $item;
        });
        if (is_array($douban_data)) {
            $douban_data = $douban_data[0];
        }
        return $douban_data;
    }

}
