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
    public $curl;
    public $listInfo;
    public $listNum;
    public $contentNum;

    public function MovieInit()
    {
        $this->listNum = 0;
        $this->contentNum = 0;

        if (empty($this->curl)) {
            $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'curl' . DIRECTORY_SEPARATOR . 'curl.php';
            require_once $path;
            $this->curl = new \curl();
        }
    }

    /**
     * 保存电影电视剧列表页
     */
    public function movieList($start, $pageTot, $baseListUrl)
    {
        $url = substr($baseListUrl, 0, strrpos($baseListUrl, '.'));

        for ($i = $start; $i <= $pageTot; $i++) {
            //$this->info("this is page {$i}");
            if ($i == 1) {
                $listUrl = $url . '.html';
            } else {
                $listUrl = $url . '_' . $i . '.html';
            }

            $list = $this->getList($listUrl);
            
            //保存进数据库中去
            foreach ($list as $key => $value) {

                $rs = null;
                $rest = DB::connection($this->dbName)->table($this->tableName)->where('typeid', $this->typeId)->where('title_hash', md5($value['title']))->first();
                if ($rest) {
                    continue;
                } else {
                    //不是更新的时候判断名字的重复
                    $listSaveArr = [
                        'title' => trim($value['title']),
                        'title_hash' => md5(trim($value['title'])),
                        'con_url' => $value['con_url'],
                        'm_time' => $value['m_time'],
                        //描述信息
                        'down_link' => SpHtml2Text($value['body']),
                        'litpic' => $value['litpic'],
                        'typeid' => $this->typeId,
                        'is_douban' => 0,
                    ];

                    $rs = DB::connection($this->dbName)->table($this->tableName)->insert($listSaveArr);
                    if ($rs) {
                        $this->listNum++;
                    }
                }
            }
        }
    }

    /**
     * 采集列表页
     */
    public function getList($url)
    {
//        echo $url."\n";
        $host = 'http://www.y3600.com';
        $list = QueryList::Query($url, array(
            'title' => array('ol a', 'text'),
            'litpic' => array('.img img', 'src'),
            'con_url' => array('ol a', 'href'),
            'body' => array('li', 'text'),
            'm_time' => array('em', 'text')
        ), '.wdls')->getData(function ($item) use ($host) {
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
        do {
            $take = 10;
            $arc = DB::connection($this->dbName)->table($this->tableName)->where('id', '>', $this->aid)->where('is_con', -1)->where('typeid', $this->typeId)->take($take)->orderBy('id')->get();
            $tot = count($arc);

            foreach ($arc as $key => $value) {
                $this->aid = $value->id;
                //$this->info("{$key}/{$tot} id is {$value->id} url is {$value->con_url}");

                //得到保存的数组
                $conSaveArr = $this->getConSaveArr($value->con_url);
                if (!$conSaveArr) {
                    //内容不存在则删除这条记录
                    DB::connection($this->dbName)->table($this->tableName)->where('id', $value->id)->delete();
                    continue;
                }
                //内容主体
                $conSaveArr = SpHtml2Text($conSaveArr[0]['con']);

                $rest = DB::connection($this->dbName)->table($this->tableName)->where('id', $value->id)->update(['body' => $conSaveArr]);
                if ($rest) {
                    $this->contentNum++;
                    DB::connection($this->dbName)->table($this->tableName)->where('id', $value->id)->update(['is_con' => 0]);
                    //$this->info('save con success');
                } else {
                    //$this->error('save con fail');
                    DB::connection($this->dbName)->table($this->tableName)->where('id', $value->id)->delte();
                }
            }
        } while ($tot > 0);
        //$this->info('save con end');
        $this->aid = 0;
    }


    /**
     * 得到内容页的保存数组,以◎分割
     * @param $url 内容页的网址链接
     */
    public function getConSaveArr($url)
    {
        $content = QueryList::Query($url, array(
            'con' => array('#article', 'text', 'img -.content_head -.editor -script -.show_author'),
        ))->getData(function ($item) {
            $pattern = array('/width\s*=\s*[\'"](.*?)[\'"]/is', '/height\s*=\s*[\'"](.*?)[\'"]/is');
            $replace = array('', '');
            $item['con'] = preg_replace($pattern, $replace, $item['con']);
            return $item;
        });
//        dd($content);
        return $content;
    }

}

