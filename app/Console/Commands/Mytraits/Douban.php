<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/10/20 0020
 * Time: 下午 7:10
 */
namespace App\Console\Commands\Mytraits;

use QL\QueryList;

trait Douban
{

    /**
     * 得到列表页
     */
    public function getDouList($url)
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
        ),'.result .title')->data;

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
    public function getDouContent($url,$refurl)
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




