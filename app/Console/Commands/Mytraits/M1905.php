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

trait M1905
{
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
                $value['title'] = trim($value['title']);

                if (strpos($value['con_url'], 'news') !== false) {
                    $isAlready = DB::table('ca_gather')->where('typeid', $this->typeId)->where('title_hash', md5($value['title']))->count();
                    //判断重复性
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
                        //描述信息
                        'down_link' => SpHtml2Text($value['body']),
                        'litpic' => $value['litpic'],
                        'typeid' => $this->typeId,
                        'is_douban' => 0,
                        'm_time' => date('Y-m-d H:i:s'),
                    ];
                    DB::table('ca_gather')->insert($listSaveArr);
                }
            }
        }
        //end
        $this->info('m1905 list end');
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
        $offset = 0;
        $limit = 1000;
        do
        {
            $arts = DB::table('ca_gather')->select('id','title','con_url')->where('is_con', -1)->where('typeid', $this->typeId)->skip($offset)->take($limit)->get();
            $tot = count($arts);
            foreach ($arts as $key=>$value){
                $conSaveArr = $this->getConSaveArr($value->con_url);
                if (!$conSaveArr) {
                    //内容不存在则删除这条记录
                    DB::table('ca_gather')->where('id', $value->id)->delete();
                    continue;
                }
                //内容主体
                DB::table('ca_gather')->where('id', $value->id)->update(['body' => $conSaveArr[0]['con'],'is_con'=>0]);
            }
            $offset+=$limit;
        }while($tot > 0);
        $this->info('m1905 content end');
    }


    /**
     * @param $url 内容页的网址链接
     */
    public function getConSaveArr($url)
    {
        $content = QueryList::Query($url, array(
            'con' => array('.pic-content', 'text', 'p -img -a -script -.atlas_placehoder'),
        ))->getData(function ($item){
            $pattern = array('/width\s*=\s*[\'"](.*?)[\'"]/is', '/height\s*=\s*[\'"](.*?)[\'"]/is','/style\s*=\s*["\'](.*?)["\']/is');
            $replace = array('', '','');
            $con = preg_replace($pattern, $replace, $item['con']);

            $con = strtr($con,array('1905电影网讯'=>'','1905'=>'','电影网'=>'','下一页：'=>''));
            $item['con'] = trim($con);
            return $item;
        });
        return $content;
    }

}

