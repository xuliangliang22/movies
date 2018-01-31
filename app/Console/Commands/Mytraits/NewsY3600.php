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

trait NewsY3600
{
    protected $listNum;

    /**
     * 保存电影电视剧列表页
     */
    public function movieList($start, $pageTot, $baseListUrl)
    {
        $url = substr($baseListUrl, 0, strrpos($baseListUrl, '.'));

        for ($i = $start; $i <= $pageTot; $i++) {
            if ($i == 1) {
                $listUrl = $url . '.html';
            } else {
                $listUrl = $url . '_' . $i . '.html';
            }

            $listSaveArr = [];
            $list = $this->getList($listUrl);
            
            //保存进数据库中去
            foreach ($list as $key => $value) {
                $isAlready = DB::table('ca_gather')->where('typeid', $this->typeId)->where('title_hash', md5($value['title']))->count();
                if ($isAlready > 0) {
                    continue;
                }
                //百度判断状态
                if(!$this->baiduJudge($value['title'])){
                    continue;
                }
                $listSaveArr = [
                    'title' => $value['title'],
                    'title_hash' => md5($value['title']),
                    'con_url' => $value['con_url'],
                    'm_time' => $value['m_time'],
                    //描述信息
                    'down_link' => SpHtml2Text($value['body']),
                    'litpic' => $value['litpic'],
                    'typeid' => $this->typeId,
                    'is_douban' => 0,
                ];

                $issave = DB::table('ca_gather')->insert($listSaveArr);
                if ($issave) {
                    $this->info("y3600 list save success");
                }else{
                    $this->error("y3600 list save faile");
                }
            }
        }
        $this->info('y3600 list end');
    }

    /**
     * 采集列表页
     */
    public function getList($url)
    {
        $host = 'http://www.y3600.com';
        $list = QueryList::Query($url, array(
            'title' => array('ol a', 'text'),
            'litpic' => array('.img img', 'src'),
            'con_url' => array('ol a', 'href'),
            'body' => array('li', 'text'),
            'm_time' => array('em', 'text')
        ), '.wdls')->getData(function ($item) use ($host) {
            $item['title'] = trim($item['title']);
            $item['con_url'] = trim($host, '/') . $item['con_url'];
            $item['m_time'] = date('Y') . '-' . trim(strstr($item['m_time'], '['), '][');
            return $item;
        });
        return $list;
    }


    /**
     * 采信内容页
     */
    public function getContent()
    {
        $offset = 0;
        $limit = 1000;
        do
        {
            $arts = DB::table('ca_gather')->select('id','con_url')->where('is_con', -1)->where('typeid', $this->typeId)->skip($offset)->take($limit)->get();
            $tot = count($arts);
            foreach ($arts as $key=>$value){
                $conSaveArr = $this->getConSaveArr($value->con_url);
                if (!$conSaveArr) {
                    //内容不存在则删除这条记录
                    DB::table('ca_gather')->where('id', $value->id)->delete();
                    continue;
                }
                //内容主体
                $rest = DB::table('ca_gather')->where('id', $value->id)->update(['body' => $conSaveArr[0]['con'],'is_con'=>0]);
                if ($rest) {
                    $this->info("{$key}/{$tot} y3600 content save success");
                } else {
                    $this->info("{$key}/{$tot} y3600 content save fail");
                }
            }
            $offset+=$limit;
        }while($tot > 0);
        $this->info('y3600 content end');
    }


    /**
     * @param $url 内容页的网址链接
     */
    public function getConSaveArr($url)
    {
        $content = QueryList::Query($url, array(
            'con' => array('#article', 'text', 'p -img -.content_head -.editor -script -.show_author -img_descr'),
        ))->getData(function ($item) {
            $pattern = array('/width\s*=\s*[\'"](.*?)[\'"]/is', '/height\s*=\s*[\'"](.*?)[\'"]/is','/style\s*=\s*["\'](.*?)["\']/is');
            $replace = array('', '','');
            $item['con'] = preg_replace($pattern, $replace, $item['con']);
            return $item;
        });
        return $content;
    }

}

