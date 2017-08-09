<?php

namespace App\Console\Commands\Caiji;

use Illuminate\Console\Command;
use QL\QueryList;

class Douban extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'caiji:douban {keyword}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '根据输入的关键词得到豆瓣信息';

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
        $keyword = $this->argument('keyword');
        $keyword = strtr($keyword, array('/' => '', ' ' => '', '　' => '', "\r" => '', "\n" => ''));
//        $url = 'https://www.douban.com/search?q=' . $keyword;
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
                    $title = strtr($value['title'], array('/' => '', ' ' => '', '　' => '', "\r" => '', "\n" => ''));
                    if (mb_strpos($title, $keyword) !== false || mb_strpos($keyword, $title) !== false) {
                        $rest = $value;
                        break;
                    }
                }
            }
        }
//        dd($rest);

        if (empty($rest)) {
            throw new \ErrorException('this ' . $keyword . ' content is not exits');
        }

        //取内容页的信息
        $this->getContent($rest['con_url']);
//        dd($content);
    }




    /**
     * https　curl
     * @param $url
     */
    public function getContent($url)
    {
        global $douban_data;
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
    }
}
