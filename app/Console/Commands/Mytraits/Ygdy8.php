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
use zgldh\QiniuStorage\QiniuStorage;

trait Ygdy8
{
    public $listNum;

    /**
     * 保存电影电视剧列表页
     */
    public function movieList($start, $pageTot, $baseListUrl, $isNew = false)
    {
        try {

            if (strrpos($baseListUrl, '_') !== false) {
                $url = substr($baseListUrl, 0, strrpos($baseListUrl, '_'));
            } else {
                $url = substr($baseListUrl, 0, strrpos($baseListUrl, '/')) . '/';
            }

            //取出最大的时间
            $maxTime = DB::connection($this->dbName)->table($this->tableName)->where('typeid', $this->typeId)->where('is_post', 0)->max('m_time');
            for ($i = $start; $i <= $pageTot; $i++) {
                $message = date('Y-m-d H:i:s')." this is page {$i} maxtime {$maxTime}".PHP_EOL;
                $this->info($message);

                if ($i == 1) {
                    $listUrl = substr($url, 0, strrpos($url, '/')) . '/index.html';
                } else {
                    $listUrl = $url . '_' . $i . '.html';
                }
                $list = $this->getList($listUrl);
//                dd($list);

                //保存进数据库中去
                foreach ($list as $key => $value) {
                    $rs = null;
                    $rest = DB::connection($this->dbName)->table($this->tableName)->where('typeid', $this->typeId)->where('title_hash', md5(trim($value['title'])))->first();
                    if ($rest) {
                        if ($isNew === true) {
                            $isNewType = 'update';
                            //判断时间,更新的时候不需要判断名字的重复
                            if (strtotime($maxTime) >= strtotime($value['m_time'])) {
//                                break 2;
                                continue;
                            }
                            //更新这样记录的下载链接,将is_con=-1,down_link = '',is_update=-1//default 0
                            $rs = DB::connection($this->dbName)
                                    ->table($this->tableName)
                                    ->where('id', $rest->id)
                                    ->update([
                                        'down_link' => '',
                                        'm_time' => $value['m_time'],
                                        'is_con' => -1,
                                        'is_update' => -1
                                    ]);
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
                        //记录列表页一共采集了多少
                        $this->listNum++;
                        $message .= $value['title'] . ' list ' . $isNewType . ' success'.PHP_EOL;
                        $this->info($message);
                    } else {
                        $message .= $value['title'] . ' list ' . $isNewType . ' fail'.PHP_EOL;
                        $this->error($message);
                    }
                }
                //保存日志
                if($this->isCommandLogs === true){
                    file_put_contents($this->commandLogsFile,$message,FILE_APPEND);
                }
            }
            $message = 'list save end';
            $this->info($message);
            //保存日志
            if($this->isCommandLogs === true){
                file_put_contents($this->commandLogsFile,$message,FILE_APPEND);
            }
        } catch (\ErrorException $e) {
            $message = 'list error exception ' . $e->getMessage().PHP_EOL;
            $this->info($message);
            //保存日志
            if($this->isCommandLogs === true){
                file_put_contents($this->commandLogsFile,$message,FILE_APPEND);
            }
            return;
        } catch (\Exception $e) {
            $message = 'list exception ' . $e->getMessage().PHP_EOL;
            $this->info($message);
            //保存日志
            if($this->isCommandLogs === true){
                file_put_contents($this->commandLogsFile,$message,FILE_APPEND);
            }
            return;
        }
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
            'title' => array('.ulink:last()', 'html'),
            'con_url' => array('.ulink:last()', 'href'),
            'm_time' => array('tr:eq(2) td:eq(1)', 'text') //影片更新的时间
        ), '.co_content8 table', 'utf-8', 'gbk', true)->getData(function ($item) use ($host) {
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
        return $list;
    }


    /**
     * 使用node去格式化下载链接
     */
    public function nodeDownLink()
    {
        //node自动更新下载链接
        //->where('down_link','not like','%thunder://%')
        $minId =0;
        $take = 10;
        $message = null;
        do {

            $downLinks = DB::connection($this->dbName)->table($this->tableName)->select('id','down_link')->where('typeid', $this->typeId)->where('id','>',$minId)->where('down_link', 'not like', '%thunder://%')->where(function ($query) {
                $query->where('is_post', -1)
                    ->orWhere('is_update', -1);
            })->take($take)->get();
            $tot = count($downLinks);

            foreach ($downLinks as $key => $value) {
                $minId = $value->id;
                $message = date('Y-m-d H:i:s')." node parse down_link {$key}/{$tot} aid -- {$value->id}".PHP_EOL;
                $this->info($message);
                $url = config('qiniu.qiniu_data.node_url') . '?aid=' . $value->id . '&down_link=' . urlencode($value->down_link);
                $this->curl->runSmall($url);
                //保存日志
                if($this->isCommandLogs === true){
                    file_put_contents($this->commandLogsFile,$message,FILE_APPEND);
                }
            }
        }while($tot > 0);
        $message = 'node parse down_link end';
        $this->info($message);
        //保存日志
        if($this->isCommandLogs === true){
            file_put_contents($this->commandLogsFile,$message,FILE_APPEND);
        }
    }


    /**
     * 填充豆瓣内容,下载图片,提交dede后台,上传cdn
     *
     * @param  $queueName 具体的操作类型
     * @param $keyWordSuffix 百度搜索图片时的后缀
     *
     */
    public function runOther($queueName)
    {
        //采集内容的第一步,将缩略图与下载链接采集下来
        if($queueName == 'all' || $queueName == 'content'){
            $this->call('caiji:ygdy8_get_content',['type_id'=>$this->typeId]);
            if($queueName == 'content'){
                exit;
            }
        }

        if ($queueName == 'all' || $queueName == 'pic' || $queueName == 'other') {
            //node格式化下载链接
            $this->nodeDownLink();
            //上传图片
            //litpic
            $this->call('xiazai:img',['action'=>'litpic','type_id'=>$this->typeId]);
            //bodypic
//            $this->callSilent('xiazai:img',['action'=>'body','type_id'=>$this->typeId]);
//            $this->call('caiji:baidulitpic',['type_id'=>$this->typeId,'key_word_suffix'=>$keyWordSuffix]);

            if($queueName == 'pic'){
                exit;
            }
        }

        //上线部署
        if ($queueName == 'all' || $queueName == 'dede' || $queueName == 'other') {
            //logs
            $message = date('Y-m-d H:i:s')." 将新添加数据提交到dede后台".PHP_EOL;
            $this->info($message);

            //采集豆瓣数据
            $this->call('caiji:douban',['type_id'=>$this->typeId]);
            //将豆瓣不好的数据删除
            $disk = QiniuStorage::disk('qiniu');
            DB::connection($this->dbName)->table($this->tableName)->where('typeid', $this->typeId)->where('is_litpic', -1)->delete();

            $doubans = DB::connection($this->dbName)->table($this->tableName)->select('id','litpic')->where('typeid', $this->typeId)->where('is_douban',-1)->get();
            $qiniuUrl = config('filesystems.disks.qiniu.domains.default');
            foreach ($doubans as $dok=>$dov){
                //删除图片
                if(stripos($dov->litpic,$qiniuUrl) !== false){
                    $file = str_replace($qiniuUrl,'',$dov->litpic);
                    if($disk->exists($file)){
                        $disk->delete($file);
                    }
                }
                //删除这样记录
                DB::connection($this->dbName)->table($this->tableName)->where('id',$dov->id)->delete();
            }

            //将新添加数据提交到dede后台 is_post = -1
            $this->call('send:dedea67post', ['channel_id' => $this->channelId, 'typeid' => $this->typeId]);
            //将更新数据提交到dede后台,直接替换数据库
            $this->call('dede:makehtml', ['type' => 'update', 'typeid' => $this->typeId]);
            if (file_exists($this->dedeSendStatusFile)) {
                //更新列表页
                $message .= "更新列表页".PHP_EOL;
                $this->info($message);
                $this->call('dede:makehtml', ['type' => 'list', 'typeid' => $this->typeId]);
            }
            //logs
            $message .=  "上线部署完成! ".PHP_EOL;
            //保存日志
            if($this->isCommandLogs === true){
                file_put_contents($this->commandLogsFile,$message,FILE_APPEND);
            }
            if ($queueName == 'dede') {
                exit;
            }
        }
        $message =  "内容更新完成! ".PHP_EOL;
        //保存日志
        if($this->isCommandLogs === true){
            file_put_contents($this->commandLogsFile,$message,FILE_APPEND);
        }
    }
}

