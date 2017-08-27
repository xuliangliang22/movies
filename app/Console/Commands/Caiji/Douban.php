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

                $updateArr = [];
                foreach ($movies as $key => $row) {
                    $aid = $row->id;
                    //cli
                    $this->info("this is id {$row->id} title {$row->title}");
                    $rest = $this->getList($row->title);
                    if (!$rest) {
                        continue;
                    }

                    foreach ($rest as $k => $v) {
                        switch ($k) {
                            case 'grade':
                                if ((empty($v) || $v > 10) && isset($douban_data['grade'])) {
                                    $updateArr['grade'] = $douban_data['grade'];
                                }
                                break;
                            case 'litpic':
                                if (empty($v) && isset($douban_data['litpic'])) {
                                    $updateArr['litpic'] = $douban_data['litpic'];
                                }
                                break;
                            case 'body':
                                if (empty($v) && isset($douban_data['body'])) {
                                    $body = $douban_data['body'];
                                    if (mb_strlen($body) > 250) {
                                        $body = mb_substr($body, 0, 250) . '....';
                                    }
                                    $updateArr['body'] = $body;
                                }
                                break;
                            case 'director':
                                if (empty($v) && isset($douban_data['html']['director'])) {
                                    $updateArr['director'] = $douban_data['html']['director'];
                                }
                                break;
                            case 'actors':
                                if (empty($v) && isset($douban_data['html']['actors'])) {
                                    $actors = $douban_data['html']['actors'];
                                    if (mb_strlen($actors, 'utf-8') > 250) {
                                        $actors = explode(',', $actors);
                                        $actors = array_slice($actors, 0, 5);
                                        $actors = implode(',', $actors);
                                    }
                                    $updateArr['actors'] = $actors;
                                }
                                break;
                            case 'myear':
                                if ((empty($v) || preg_match('/^\d{4}$/', $v) === 0) && isset($douban_data['html']['year'])) {
                                    $updateArr['myear'] = $douban_data['html']['year'];
                                }
                                break;
                            case 'lan_guage':
                                if (empty($v) && isset($douban_data['html']['language'])) {
                                    $updateArr['lan_guage'] = $douban_data['html']['language'];
                                }
                                break;
                            case 'types':
                                if (empty($v) && isset($douban_data['html']['types'])) {
                                    $updateArr['types'] = $douban_data['html']['types'];
                                }
                                break;
                            case 'episode_nums':
                                if (empty($v) && isset($douban_data['html']['episode_nums'])) {
                                    $updateArr['episode_nums'] = intval($douban_data['html']['episode_nums']);
                                }
                                break;
                        }
                    }
                    //cli
                    print_r($updateArr);

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
                    } else {
                        DB::connection($this->dbName)->table($this->tableName)->where('id', $row->id)->update(['is_douban' => 0]);
                    }
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
                    echo $title . "\n";
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
                    case '集数':
                        $rest['episode_nums'] = $vret;
                        break;
                }
            }
            $item['html'] = $rest;
            $item['body'] = strtr($item['body'], array("\r" => '', "\n" => '', '©豆瓣' => '', '豆瓣' => '', ' ' => ''));
            return $item;
        });
//        dd($douban_data);
        if (is_array($douban_data)) {
            $douban_data = $douban_data[0];
        }
        return $douban_data;
    }


}
