<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/2 0002
 * Time: 下午 3:55
 */
namespace App\Console\Commands\Mytraits;

use Illuminate\Support\Facades\DB;
use QL\QueryList;

trait Ygdy8
{
    public $listNum;

    /**
     * 保存电影电视剧列表页
     */
    public function movieList($start, $pageTot, $baseListUrl)
    {

        if (strrpos($baseListUrl, '_') !== false) {
            $url = substr($baseListUrl, 0, strrpos($baseListUrl, '_'));
        } else {
            $url = substr($baseListUrl, 0, strrpos($baseListUrl, '/')) . '/';
        }

        //取出最大的时间
        $maxTime = DB::table('ca_gather')->where('typeid', $this->typeId)->where('is_post', 0)->max('m_time');
        if(!$maxTime){
            $maxTime = date('Y-m-d H:i:s');
        }
        for ($i = $start; $i <= $pageTot; $i++) {
            if ($i == 1) {
                $listUrl = substr($url, 0, strrpos($url, '/')) . '/index.html';
            } else {
                $listUrl = $url . '_' . $i . '.html';
            }
            $list = $this->getList($listUrl);
            $tot = count($list);
            if($tot < 1){
               die('sorry,movie list empty!!');
            }

            //保存进数据库中去
            foreach ($list as $key => $value) {
                if(empty($value['con_url'])){
                    continue;
                }

                $isAlready = DB::table('ca_gather')->where('typeid', $this->typeId)->where('title_hash', md5($value['title']))->first();
                if ($isAlready) {
                    //判断时间,更新的时候不需要判断名字的重复
                    if (strtotime($maxTime) >= strtotime($value['m_time'])) {
                        continue;
                    }
                    //更新这样记录的下载链接,将is_con=-1,down_link = '',is_update=-1//default 0
                    //说明这条记录有更新是电视剧
                    DB::table('ca_gather')->where('id',$isAlready->id)->update([
                        'is_con'=>-1,
                        'm_time' => $value['m_time'],
                        'is_update' => -1,
                        //运行node标志
                        'is_post' => -2,
                    ]);
                } else {
                    //不是更新的时候判断名字的重复
                    //新增
                    $listSaveArr = [
                        'title' => $value['title'],
                        'title_hash' => md5($value['title']),
                        'con_url' => $value['con_url'],
                        'm_time' => $value['m_time'],
                        'typeid' => $this->typeId,
                        //没有缩略图
                        'is_litpic' => 0,
                        //留着node
                        'is_post' => -2,
                    ];
                    $issave = DB::table('ca_gather')->insert($listSaveArr);
                    if($issave){
                        $this->info("{$key}/{$tot} movie list save success");
                    }else{
                        $this->error("{$key}/{$tot} movie list save fail");
                    }
                }
            }
        }
        $this->info("movie list save end");
        //end
    }

    /**
     * 采集列表页
     */
    public function getList($url)
    {
        $list = null;
        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = parse_url($url, PHP_URL_HOST);
        $host = $scheme . '://' . $host;

        $list = QueryList::Query($url, array(
            'title' => array('tr:eq(1)', 'html'),
            'con_url' => array('tr:eq(1)', 'html'),
            'm_time' => array('tr:eq(2)', 'text') //影片更新的时间
        ), '.tbspan', 'utf-8', 'gbk', true)->getData(function ($item) use ($host) {
            //《嗜血追凶》
            $startPosition = stripos($item['title'],'《');
            $endPosition = stripos($item['title'],'》');
            $item['title'] = substr($item['title'],$startPosition,$endPosition-$startPosition);
            $item['title'] = strtr($item['title'],array(
                '《'=>'',
                '》'=>'',
            ));
            $item['title'] = trim($item['title']);
            if(preg_match('/<a\s*href\s*=\s*["\'](.*?)["\']/',$item['con_url'],$matchs)){
                $item['con_url'] = $host . $matchs[1];
            }else{
                $item['con_url'] = '';
            }
            if(preg_match('/\d+\-\d+\-\d+\s*\d+:\d+:\d+/is',$item['m_time'],$matchs)){
                $item['m_time'] = $matchs[0];
            }else{
                $item['m_time'] = date('Y-m-d H:i:s');
            }
            return $item;
        });
        return $list;
    }


    /**
     * 内容页
     */
    public function getContent()
    {
        $offset = 0;
        $limit = 100;
        do
        {
            $arts = DB::table('ca_gather')->select('id','title','body','con_url','is_update')->where('is_con',-1)->where('typeid',$this->typeId)->skip($offset)->take($limit)->get();
            $tot = count($arts);
            foreach ($arts as $key=>$value){
                //内容中含有图片的标志
                $is_body = 0;
                $body = $this->getConSaveArr($value->con_url);
                if(!$body || isset($body['down_link']) === false || empty($body['down_link'])){
                    DB::table('ca_gather')->where('id',$value->id)->delete();
                    continue;
                }
                if (isset($body['pic']) && empty($body['pic']) === false) {
                    $body['pic'] = '<img src="' . $body['pic'] . '" alt="' . $value->title . '" />';
                    $is_body = -1;
                }else{
                    $body['pic'] = '';
                }
                $downLink = implode(',',$body['down_link']);
                if($value->is_update >= 0){
                    $bodySave = $body['body'].'<br/>'.$body['pic'];
                }else{
                    $bodySave = $value->body;
                }
                //保存到数据库
                $issave = DB::table('ca_gather')->where('id',$value->id)->update(array(
                    'body' => $bodySave,
                    'down_link' => $downLink,
                    'is_con' => 0,
                    'is_body' => $is_body,
                ));
                if($issave){
                    $this->info("{$key}/{$tot} movie content save success");
                }else{
                    $this->error("{$key}/{$tot} movie content save fail");
                }
            }
            $offset+=$limit;
        }while($tot > 0);
        $this->info('movie save content end');

    }


    /**
     * 影片内容
     * @param $url
     * @return bool
     */
    public function getConSaveArr($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_NOBODY, false);
        $content = curl_exec($ch);
        curl_close($ch);
        $content = mb_convert_encoding($content, 'utf-8', 'gbk,gb2312,big5,ASCII,unicode,utf-16,ISO-8859-1');

        //还是要将下载链接分出来
        $data = QueryList::Query($content,array(
            'body' => array('#Zoom','text','p br -a -script'),
            'pic' => array('#Zoom img:eq(1)','src'),
        ))->getData(function ($item) use($content){
            $item['body'] = strstr($item['body'],'【下载地址】',true);
            $item['body'] = removeCss($item['body']);
            $item['down_link'] = QueryList::Query($content,array(
                'down_link' => array('a','href'),
            ),'#Zoom table')->getData(function ($item){
                return $item['down_link'];
            });
            return $item;
        });
        return $data[0];
    }

    /**
     * 使用node去格式化下载链接
     */
    public function nodeDownLink()
    {
        $offset = 0;
        $limit = 100;
        do
        {
            $arts = DB::table('ca_gather')->select('id','down_link')->where('typeid',$this->typeId)->where('is_post',-2)->skip($offset)->take($limit)->get();
            $tot = count($arts);
            foreach ($arts as $key=>$value){
                $url = env('NODE_URL') . '?aid=' . $value->id . '&down_link=' . urlencode($value->down_link);
                $this->curl->runSmall($url);
//                $body = $this->curl->get();
                DB::table('ca_gather')->where('id',$value->id)->update([
                    'is_post' => -1,
                ]);
            }
            $offset+=$limit;
        }while($tot > 0);
        $this->info('node end');
    }

}

