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
            //$this->info("this is page {$i}");
            if ($i == 1) {
                $listUrl = $url . '.html';
            } else {
                $listUrl = $url . '_' . $i . '.html';
            }

            $list = $this->getList($listUrl);
            
            //保存进数据库中去
            foreach ($list as $key => $value) {
                $isAlready = DB::connection($this->dbName)->table($this->tableName)->where('typeid', $this->typeId)->where('title_hash', md5(trim($value['title'])))->first();
                if (count($isAlready) > 0) {
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
        $minId = 0;
        $take = 10;
        $message = null;

        do {
            $arc = DB::connection($this->dbName)->table($this->tableName)->select('id','con_url')->where('is_con', -1)->where('typeid', $this->typeId)->where('id', '>', $minId)->take($take)->get();
            $tot = count($arc);

            foreach ($arc as $key => $value) {
                $minId = $value->id;
                $message = date('Y-m-d H:i:s')."{$key}/{$tot} id is {$value->id} url is {$value->con_url}".PHP_EOL;
                $this->info($message);

                //得到保存的数组
                $conSaveArr = $this->getConSaveArr($value->con_url);
                if (!$conSaveArr) {
                    //内容不存在则删除这条记录
                    DB::connection($this->dbName)->table($this->tableName)->where('id', $value->id)->delete();
                    continue;
                }
                //内容主体
                $rest = DB::connection($this->dbName)->table($this->tableName)->where('id', $value->id)->update(['body' => $conSaveArr[0]['con'],'is_con'=>0]);
                if ($rest) {
                    $message .= "y3600 content aid {$value->id} save success ";
                    $this->info($message);
                } else {
                    $message .= "y3600 content aid {$value->id} save fail ";
                    $this->error($message);
                    DB::connection($this->dbName)->table($this->tableName)->where('id', $value->id)->delte();
                }

                //日志
                if($this->isCommandLogs === true) {
                    file_put_contents($this->commandLogsFile, $message, FILE_APPEND);
                }
            }
        } while ($tot > 0);
        $message = "y3600 content save end ";
        //日志
        if($this->isCommandLogs === true) {
            file_put_contents($this->commandLogsFile, $message, FILE_APPEND);
        }
    }


    /**
     * 得到内容页的保存数组,以◎分割
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

