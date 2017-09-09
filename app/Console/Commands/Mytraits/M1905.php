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
                    $rest = DB::connection($this->dbName)->table($this->tableName)->where('typeid', $this->typeId)->where('title_hash', md5($value['title']))->first();
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
        do {
            $arc = DB::connection($this->dbName)->table($this->tableName)->where('is_con', -1)->where('typeid', $this->typeId)->where('id','>',$minId)->take($take)->get();
            $tot = count($arc);

            foreach ($arc as $key => $value) {
                $minId = $value->id;
                $this->info("{$key}/{$tot} id is {$value->id} url is {$value->con_url}");
                //得到保存的数组
                $conSaveArr = $this->getConSaveArr($value->con_url,$value->title);
                if (empty($conSaveArr)) {
                    //内容不存在则删除这条记录
                    DB::connection($this->dbName)->table($this->tableName)->where('id', $value->id)->delete();
                    continue;
                }
                //内容主体
//                $conSaveArr = SpHtml2Text($conSaveArr[0]['con']);
                $rest = DB::connection($this->dbName)->table($this->tableName)->where('id', $value->id)->update(['body' => $conSaveArr[0]['con']]);
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
    }


    /**
     * 得到内容页的保存数组,以◎分割
     * @param $url 内容页的网址链接
     */
    public function getConSaveArr($url,$title)
    {
        $content = QueryList::Query($url, array(
            'con' => array('.pic-content', 'text', 'p img -a -script'),
        ))->getData(function ($item) use($title){
            $pattern = array('/width\s*=\s*[\'"](.*?)[\'"]/is', '/height\s*=\s*[\'"](.*?)[\'"]/is','/style\s*=\s*["\'](.*?)["\']/is');
            $replace = array('', '','');
            $item['con'] = preg_replace($pattern, $replace, $item['con']);
            if(!empty($item['con'])) {
                $item['pic'] = QueryList::Query($item['con'], array(
                    'pic' => array('img', 'src')
                ))->data;
            }
            $item['con'] = preg_replace('/<img(.*?)>/is','%s',$item['con']);

            //组织图片链接字符串
            $picstr = '';
            if(!empty($item['pic'])){
//                $item['con'] = sprintf($item['con'],implode(',',$item['pic']));
                foreach ($item['pic'] as $key=>$value){
                    $alt = '迅雷电影下载_2017最新电影电视剧_ca2722电影网'.$title;
                    $picstr .= '<img src = "'.$value['pic'].'" title="'.$alt.'" alt="'.$alt.'">,';
                }
                $picstr = rtrim($picstr,',');
            }
            unset($item['pic']);
            $con = explode('%s',$item['con']);
            $last = array_pop($con);
            $picstr = explode(',',$picstr);

            foreach ($con as $key=>&$value){
                $value = $value.$picstr[$key];
            }
            $con = implode('',$con);
            $con = $con.$last;
            $con = strtr($con,array('1905电影网讯'=>'','1905'=>'','电影网'=>''));
            $item['con'] = trim($con);
            return $item;
        });
//        dd($content);
        return $content;
    }

}

