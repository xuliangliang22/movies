<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/2 0002
 * Time: 下午 3:55
 */
namespace App\Console\Commands\Mytraits;

use DiDom\Query;
use Illuminate\Support\Facades\DB;
use QL\QueryList;

trait M1905
{
    public $listNum;

    /**
     * 保存电影电视剧列表页
     */
    public function movieList($start, $pageTot, $baseListUrl)
    {
        for ($i = $start-1; $i <= $pageTot; $i++) {
            //$this->info("this is page {$i}");
            if ($i == 0) {
                $listUrl = 'http://www.1905.com/pinglun/?fr=homepc_menu_cmt';
            } else {
                $listUrl = sprintf($baseListUrl,$i);
            }

            $list = $this->getList($listUrl);
            
            //保存进数据库中去
            foreach ($list as $key => $value) {
                if (strpos($value['con_url'], 'news') !== false) {
                    $rest = DB::connection($this->dbName)->table($this->tableName)->where('typeid', $this->typeId)->where('title_hash', md5(trim($value['title'])))->first();
                    //判断重复性
                    if ($rest) {
                        continue;
                    } else {
                        //不是更新的时候判断名字的重复
                        $listSaveArr = [
                            'title' => trim($value['title']),
                            'title_hash' => md5(trim($value['title'])),
                            'con_url' => $value['con_url'],
                            //描述信息
                            'down_link' => SpHtml2Text($value['body']),
                            'litpic' => $value['litpic'],
                            'typeid' => $this->typeId,
                            'is_douban' => 0,
                            'm_time' => date('Y-m-d H:i:s'),
                        ];

                        $rs = DB::connection($this->dbName)->table($this->tableName)->insert($listSaveArr);
                        if ($rs) {
                            $this->listNum++;
                        }
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
        $list = null;
        if($url == 'http://www.1905.com/pinglun/?fr=homepc_menu_cmt'){
            $list = QueryList::Query($url,array(
                'title'=>array('.list-txt a','text'),
                'litpic' => array('img','src'),
                'con_url'=>array('.list-txt a','href'),
                //description
                'body'=>array('.list-txt .txt-abstract','text')
            ),'.comment-list li')->data;
        }else{
            $data = QueryList::Query($url,array())->getHtml();
            $data = json_decode($data,true);
            if(isset($data['info'])) {
                $data = $data['info'];
                foreach ($data as $key=>$value) {
                    $list[$key] = array(
                        'title' => $value['title'],
                        'litpic' => $value['thumb'],
                        'con_url' => $value['url'],
                        'body' => $value['description'],
                    );
                }
            }
        }
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
            $arc = DB::connection($this->dbName)->table($this->tableName)->where('is_con', -1)->where('typeid', $this->typeId)->where('id','>',$minId)->take($take)->get();
            $tot = count($arc);

            foreach ($arc as $key => $value) {
                $minId = $value->id;
                $message = date('Y-m-d H:i:s')."{$key}/{$tot} id is {$value->id} url is {$value->con_url}".PHP_EOL;
                $this->info($message);

                //得到保存的数组
                $conSaveArr = $this->getConSaveArr($value->con_url,$value->title);
                if (empty($conSaveArr)) {
                    //内容不存在则删除这条记录
                    DB::connection($this->dbName)->table($this->tableName)->where('id', $value->id)->delete();
                    continue;
                }

                //内容主体
                $rest = DB::connection($this->dbName)->table($this->tableName)->where('id', $value->id)->update(['body' => $conSaveArr[0]['con'],'is_con'=>0]);
                if ($rest) {
                    $message .= "m1905 content aid {$value->id} save success ";
                    $this->info($message);
                } else {
                    $message .= "m1905 content aid {$value->id} save fail ";
                    $this->error($message);
                    DB::connection($this->dbName)->table($this->tableName)->where('id', $value->id)->delete();
                }

                //日志
                if($this->isCommandLogs === true) {
                    file_put_contents($this->commandLogsFile, $message, FILE_APPEND);
                }
            }
        } while ($tot > 0);
        $message = "m1905 content save end ";
        //日志
        if($this->isCommandLogs === true) {
            file_put_contents($this->commandLogsFile, $message, FILE_APPEND);
        }
    }


    /**
     * 得到内容页的保存数组,以◎分割
     * @param $url 内容页的网址链接
     */
    public function getConSaveArr($url,$title)
    {
        $content = QueryList::Query($url, array(
            'con' => array('.pic-content', 'text', 'p -img -a -script -.atlas_placehoder'),
        ))->getData(function ($item) use($title){
            $pattern = array('/width\s*=\s*[\'"](.*?)[\'"]/is', '/height\s*=\s*[\'"](.*?)[\'"]/is','/style\s*=\s*["\'](.*?)["\']/is');
            $replace = array('', '','');
            $con = preg_replace($pattern, $replace, $item['con']);

            $con = strtr($con,array('1905电影网讯'=>'','1905'=>'','电影网'=>'','下一页：'=>''));
            $item['con'] = trim($con);
            return $item;
        });
//        dd($content);
        return $content;
    }

}

