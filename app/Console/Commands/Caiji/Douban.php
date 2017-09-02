<?php

namespace App\Console\Commands\Caiji;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use QL\QueryList;

class Douban extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'caiji:douban {db_name}{table_name}{type_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '根据输入的关键词得到豆瓣信息';


    public $dbName;
    public $tableName;
    public $typeId;


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
        $this->dbName = $this->argument('db_name');
        $this->tableName = $this->argument('table_name');
        $this->typeId = $this->argument('type_id');
        $this->initDouban(0);
    }


    public function initDouban($aid)
    {
        $take = 10;
        try {
            do {
                $movies = DB::connection($this->dbName)->table($this->tableName)->where('id', '>', $aid)->where('typeid', $this->typeId)->where('is_douban', -1)->take($take)->get();
                $tot = count($movies);
                if ($tot < 1) {
                    //cli
                    $this->error('no content to douban');
                    break;
                }

                foreach ($movies as $key => $row) {
                    $aid = $row->id;
                    //cli
                    $this->info("this is id {$row->id} title {$row->title}");
                    $rest = $this->getList($row->title);
//                    dd($rest);
                    if (!$rest) {
                        continue;
                    }

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
                    //cli
                    if(config('qiniu.qiniu_data.is_cli')) {
                        print_r($updateArr);
                    }

                    if (!empty($updateArr)) {
                        //保存到数据库
                        $rest = DB::connection($this->dbName)->table($this->tableName)->where('id', $row->id)->update(array_merge($updateArr, ['is_douban' => 0]));
                        if ($rest) {
                            //cli
                            $this->info('perfect content update success');
                        } else {
                            //cli
                            $this->error('perfect content update fail');
                        }
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
        $this->info('douban end !');
    }


    /**
     * 得到列表页
     */
    public function getList($keyword)
    {
        $keyword = _filterSpuerChar($keyword);

        $url = 'https://www.douban.com/search?q=' . $keyword;
        $content = QueryList::Query($url, array(
            'title' => array('a', 'text'),
            'con_url' => array('a', 'href'),
            'type' => array('span', 'text'),
        ), '.result .title')->getData(function ($item) {
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
//        dd($content);

        $rest = '';
        if (empty($content) === false) {
            foreach ($content as $key => $value) {
                if (empty($value['type']) === false && (mb_stripos($value['type'], '电视', 0, 'utf-8') !== false || mb_stripos($value['type'], '电影', 0, 'utf-8') !== false || mb_stripos($value['type'], '动漫', 0, 'utf-8') !== false)) {

                    $title = $value['title'];
                    $title = _filterSpuerChar($title);
                    if(config('qiniu.qiniu_data.is_cli')) {
                        echo $title . "\n";
                    }
                    if (mb_strpos($keyword, $title, 0, 'utf-8') !== false || mb_strpos($title, $keyword, 0, 'utf-8') !== false) {
                        $rest = $value;
                        break;
                    }
                }
            }
        }

        if (empty($rest)) {
            return false;
        } else {
            //取内容页的信息
            return $this->getContent($rest['con_url']);
        }
    }

    /**
     * https　curl
     * @param $url
     */
    public function getContent($url)
    {
        $douban_data = QueryList::Query($url, array(
            'grade' => array('.rating_num', 'text'),
            'litpic' => array('#mainpic img', 'src'),
            'body' => array('#link-report', 'text'),
            'html' => array('#info', 'html'),
        ))->getData(function ($item) {
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
