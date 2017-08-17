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
    public $curl;
    public $listInfo;

    public function MovieInit()
    {
        if (empty($this->curl)) {
            $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'curl' . DIRECTORY_SEPARATOR . 'curl.php';
            require_once $path;
            $this->curl = new \curl();
        }
    }

    /**
     * 保存电影电视剧列表页
     */
    public function movieList($start,$pageTot, $baseListUrl, $isNew = false)
    {
        global $listNum;
        $listNum = 0;
        try {

            if (strrpos($baseListUrl, '_') !== false) {
                $url = substr($baseListUrl, 0, strrpos($baseListUrl, '_'));
            } else {
                $url = substr($baseListUrl, 0, strrpos($baseListUrl, '/')) . '/';
            }

            //取出最大的时间
            $maxTime = DB::connection($this->dbName)->table($this->tableName)->where('typeid', $this->typeId)->max('m_time');

            for ($i = $start; $i <= $pageTot; $i++) {
                $this->listInfo = $i.'-'.$pageTot.'-'.$baseListUrl.'-'.$isNew;
                $this->info("this is page {$i} maxtime {$maxTime}");
                if ($i == 1) {
                    $listUrl = substr($url, 0, strrpos($url, '/')) . '/index.html';
                } else {
                    $listUrl = $url . '_' . $i . '.html';
                }
                $list = $this->getList($listUrl);
//            dd($list);

                //保存进数据库中去
                foreach ($list as $key => $value) {
                    $rs = null;
                    $rest = DB::connection($this->dbName)->table($this->tableName)->where('typeid', $this->typeId)->where('title_hash', md5($value['title']))->first();
                    if ($rest) {
                        if ($isNew === true) {
                            $isNewType = 'update';
                            //判断时间,更新的时候不需要判断名字的重复
                            if (strtotime($maxTime) >= strtotime($value['m_time'])) {
                                break 2;
                            }
                            //更新这样记录的下载链接,将is_con=-1,down_link = '',is_update=-1//default 0
                            $rs = DB::connection($this->dbName)->table($this->tableName)->where('id', $rest->id)->update(['down_link' => '', 'is_con' => -1, 'is_update' => -1]);

                        } else {
                            continue;
                        }
                    } else {
                        //不是更新的时候判断名字的重复
                        $isNewType = 'save';
                        $listSaveArr = [
                            'title' => trim($value['title']),
                            'title_hash' => md5(trim($value['title'])),
                            'con_url' => $value['con_url'],
                            'm_time' => $value['m_time'],
                            'typeid' => $this->typeId,
                        ];
//                        dd($listSaveArr);
                        $rs = DB::connection($this->dbName)->table($this->tableName)->insert($listSaveArr);
                    }

                    if ($rs) {
                        $listNum++;
                        $this->info($value['title'] . ' list ' . $isNewType . ' success');
                    } else {
                        $this->info($value['title'] . ' list ' . $isNewType . ' fail');
                    }
                }
            }
            $this->info('list save end');
        }catch (\ErrorException $e){
            $this->info($e->getMessage());
            $listInfoArr = explode('-',$this->listInfo);
            if($listInfoArr[1] -  $listInfoArr[0] < 2){
                return;
            }else{
                $this->movieList($listInfoArr[0],$listInfoArr[1],$listInfoArr[2],$listInfoArr[3]);
            }
        }catch (\Exception $e){
            $this->info($e->getMessage());
            $listInfoArr = explode('-',$this->listInfo);
            if($listInfoArr[1] -  $listInfoArr[0] < 2){
                return;
            }else{
                $this->movieList($listInfoArr[0],$listInfoArr[1],$listInfoArr[2],$listInfoArr[3]);
            }
        }
    }

    /**
     * 采集列表页
     */
    public function getList($url)
    {
        $list = null;
        $host = 'http://www.ygdy8.com';

        $this->curl->add()->opt_targetURL($url)->done();
        $this->curl->run();
        $html = $this->curl->getAll();
        $html = $html['body'];
        $html = iconv('gb2312', 'utf-8//IGNORE', $html);
//        dd($html);

        $list = QueryList::Query($html, array(
            'title' => array('.ulink:last()', 'html'),
            'con_url' => array('.ulink:last()', 'href'),
            'm_time' => array('tr:eq(2) td:eq(1)', 'text') //影片更新的时间
        ), '.co_content8 table')->getData(function ($item) use ($host) {
            $marest = preg_match('/《(.*?)》/i', $item['title'], $matchs);
            if ($marest === 1) {
                $item['title'] = $matchs[1];
            } else {
                $item['title'] = '';
            }
            if (strpos($item['title'], '/') !== false) {
                $item['title'] = strstr($item['title'], '/', true);
            }
            $item['con_url'] = $host . $item['con_url'];
            $m_time = explode("\n", str_replace("\r", '', $item['m_time']));
            $m_time = explode('：', $m_time[0])[1];
            $item['m_time'] = $m_time;
            return $item;
        });
//        dd($list);
        return $list;
    }


    /**
     * 采信内容页
     * @param  $type 1.movie(下载电影) 2.other(只下载链接)
     */
    public function getContent($isNew = false)
    {
        try {
            do {
                $take = 10;
                $arc = DB::connection($this->dbName)->table($this->tableName)->where('id', '>', $this->aid)->where('is_con', -1)->where('typeid', $this->typeId)->take($take)->orderBy('id')->get();
                $tot = count($arc);

                foreach ($arc as $key => $value) {
                    $this->aid = $value->id;
                    $this->info("{$key}/{$tot} id is {$value->id} url is {$value->con_url}");

                    //得到保存的数组
//                    $conSaveArr = $this->getConSaveArr($value->con_url);
                    $conSaveArr = $this->getConSaveArr($value->con_url,true);
                    if (!$conSaveArr) {
                        continue;
                    }
                    if ($isNew === true && $value->is_update == -1) {
                        unset($conSaveArr['litpic']);
                    }
                    print_r($conSaveArr);
                    $rest = DB::connection($this->dbName)->table($this->tableName)->where('id', $value->id)->update($conSaveArr);
                    if ($rest) {
                        DB::connection($this->dbName)->table($this->tableName)->where('id', $value->id)->update(['is_con' => 0]);
                        $this->info('save con success');
                    } else {
                        $this->error('save con fail');
                    }
                }
            } while ($tot > 0);
        } catch (\ErrorException $e) {
            $this->info('get content error exception ' . $e->getMessage().'line is '.$e->getLine());
            $this->getContent($this->aid);
        } catch (\Exception $e) {
            $this->info('get content exception ' . $e->getMessage().'line is '.$e->getLine());
            $this->getContent($this->aid);
        }
        //电视剧需要更新,还要再添加一个字段
        $this->info('save con end');
        $this->aid = 0;
        //删除下载链接为空的数据
        DB::connection($this->dbName)->table($this->tableName)->whereNull('down_link')->delete();
    }


    /**
     * 只得到下载链接
     */
    public function getConSaveArr($url,$isclear = false)
    {
        static $restArr;
        if($isclear === true){
            $restArr = array();
        }
        $content = QueryList::Query($url, array(
            'litpic' => array('img:first()', 'src'),
            'down_link' => array('table', 'html'),
        ), '.co_content8','utf-8','gbk',true)->getData(function ($item) {
            if (strlen($item['litpic']) > 250) {
                $item['litpic'] = '';
            }

            $item['down_link'] = QueryList::Query($item['down_link'], array(
                'down_link' => array('a', 'href'),
            ))->getData(function ($item) {
                return urldecode($item['down_link']);
            });
            return $item;
        });
        if(empty($content)) {
            $url = str_replace('.com', '.net', $url);
            $this->getConSaveArr($url);
        }else {
            $restArr = array(
                'litpic' => $content[0]['litpic'],
                'down_link' => implode(',', $content[0]['down_link'])
            );
        }
        return $restArr;
    }

    /**
     * 使用node去格式化下载链接
     */
    public function nodeDownLink()
    {
        //node自动更新下载链接
        //->where('down_link','not like','%thunder://%')
        $isNoDownLinks = DB::connection($this->dbName)->table($this->tableName)->where('typeid', $this->typeId)->where('down_link', 'not like', '%thunder://%')->where(function ($query) {
            $query->where('is_post', -1)
                ->orWhere('is_update', -1);
        })->get();
        $tot = count($isNoDownLinks);
//        dd($tot);

        foreach ($isNoDownLinks as $key => $value) {
            $this->info("parse down_link {$key}/{$tot}");
            $url = config('qiniu.qiniu_data.node_url') . '?aid=' . $value->id . '&down_link=' . urlencode($value->down_link);
            $this->curl->runSmall($url);
        }
        $this->info("parse down_link end");
    }


    /**
     * 将更新的数据替换到dede后台
     */
    public function dedeDownLinkUpdate()
    {
        $isUpdate = false;

        $dedeDownLinkUpdateUrl = config('qiniu.qiniu_data.dede_url') . 'myplus/down_link_update.php';
        $isNoDownLinks = DB::connection($this->dbName)->table($this->tableName)->where('typeid', $this->typeId)->where('is_update', -1)->get();
        $tot = count($isNoDownLinks);

        foreach ($isNoDownLinks as $key => $value) {
            $this->info("{$key}/{$tot} id is {$value->id}");
            //echo $value->down_link."\n";
            //先登录
            $rest = $this->dedeLogin($this->dedeUrl . 'login.php', $this->dedeUser, $this->dedePwd);

            if ($rest) {
                $this->curl->add()
                    ->opt_targetURL($dedeDownLinkUpdateUrl)
                    ->opt_sendHeader('Cookie', $this->cookie)
                    ->opt_sendPost('typeid', $value->typeid)
                    ->opt_sendPost('title', $value->title)
                    ->opt_sendPost('down_link', $value->down_link)
                    ->done('post');
                $this->curl->run();
                $content = $this->curl->getAll();
                $body = explode("\r\n\r\n", $content['body'], 2);
                if (isset($body[1]) && $body[1] == 'update ok') {
                    $isUpdate = true;
                    //更新数据库is_update
                    DB::connection($this->dbName)->table($this->tableName)->where('id', $value->id)->update(['is_update' => 0]);
                    $this->info("dede down_link update {$value->title} update ok !");
                } else {
                    //没有更新成功,也将is_update更新为0
                    DB::connection($this->dbName)->table($this->tableName)->where('id', $value->id)->update(['is_update' => 0]);
                    $this->error("dede down_link update {$value->title} update fail !");
                }
            } else {
                $this->error("dede down_link update login fail !");
            }
        }
        $this->info("dede down_link update end !");
        return $isUpdate;
    }


    /**
     * dede生成栏目页
     */
    public function makeLanmu()
    {
        $url = config('qiniu.qiniu_data.dede_url') . 'makehtml_list_action.php?typeid=' . $this->typeId . '&maxpagesize=50&upnext=1';
        //dd($url);
        $this->dedeLogin(config('qiniu.qiniu_data.dede_url'). 'login.php', config('qiniu.qiniu_data.dede_user'), config('qiniu.qiniu_data.dede_pwd'));
        $this->curl->add()
            ->opt_targetURL($url)
            ->opt_sendHeader('cookie', $this->cookie)
            ->done('get');
        $this->curl->run();
        $content = $this->curl->getAll();
        if (mb_strpos($content['body'], '栏目列表更新',0, 'utf-8') !== false) {
            $this->info("{$this->typeId}  lanmu list make success !");
        } else {
            $this->error("{$this->typeId}  lanmu list make fail !");
        }
    }
}

