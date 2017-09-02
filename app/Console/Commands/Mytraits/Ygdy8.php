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
    public $listNum;

    //库名与表名
    public $dbName;
    public $tableName;

    //日志保存路径
    public $commandLogsFile;
    //是否开启日志
    public $isCommandLogs;

    public function MovieInit()
    {
        $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'curl' . DIRECTORY_SEPARATOR . 'curl.php';
        require_once $path;
        $this->curl = new \curl();

        $this->dbName = config('qiniu.qiniu_data.db_name');
        $this->tableName = config('qiniu.qiniu_data.table_name');

        $this->commandLogsFile = config('qiniu.qiniu_data.command_logs_file');
        $this->isCommandLogs = config('qiniu.qiniu_data.is_command_logs');

        //---------this is my modify-------------
        if (!is_dir(public_path('command_logs'))) {
            mkdir(public_path('command_logs'), 0755, true);
        }
        //-------------------------
    }

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
                $this->listInfo = $i . '-' . $pageTot . '-' . $baseListUrl . '-' . $isNew;
                //cli
                if (config('qiniu.qiniu_data.is_cli')) {
                    $this->info("this is page {$i} maxtime {$maxTime}");
                }
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
//                                break 2;
                                continue;
                            }
                            //更新这样记录的下载链接,将is_con=-1,down_link = '',is_update=-1//default 0
                            $rs = DB::connection($this->dbName)->table($this->tableName)->where('id', $rest->id)->update(['down_link' => '', 'm_time' => $value['m_time'], 'is_con' => -1, 'is_update' => -1]);

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
                        $this->listNum++;
                        //cli
                        if (config('qiniu.qiniu_data.is_cli')) {
                            $this->info($value['title'] . ' list ' . $isNewType . ' success');
                        }
                    } else {
                        //cli
                        if (config('qiniu.qiniu_data.is_cli')) {
                            $this->info($value['title'] . ' list ' . $isNewType . ' fail');
                        }
                    }
                }
            }
            $this->info('list save end');
        } catch (\ErrorException $e) {
            $this->info('list error exception ' . $e->getMessage());
            $listInfoArr = explode('-', $this->listInfo);
            if ($listInfoArr[1] - $listInfoArr[0] < 2) {
                return;
            } else {
                $this->movieList($listInfoArr[0], $listInfoArr[1], $listInfoArr[2], $listInfoArr[3]);
            }
        } catch (\Exception $e) {
            $this->info('list exception ' . $e->getMessage());
            $listInfoArr = explode('-', $this->listInfo);
            if ($listInfoArr[1] - $listInfoArr[0] < 2) {
                return;
            } else {
                $this->movieList($listInfoArr[0], $listInfoArr[1], $listInfoArr[2], $listInfoArr[3]);
            }
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
        $isNoDownLinks = DB::connection($this->dbName)->table($this->tableName)->where('typeid', $this->typeId)->where('down_link', 'not like', '%thunder://%')->where(function ($query) {
            $query->where('is_post', -1)
                ->orWhere('is_update', -1);
        })->get();
        $tot = count($isNoDownLinks);
//        dd($tot);

        foreach ($isNoDownLinks as $key => $value) {
            //cli
            if (config('qiniu.qiniu_data.is_cli')) {
                $this->info("node parse down_link {$key}/{$tot} id -- {$value->id}");
            }
            //log
            if ($this->isCommandLogs === true) {
                $command = "node parse down_link {$key}/{$tot} id -- $value->id\n";
                file_put_contents($this->commandLogsFile, $command, FILE_APPEND);
            }
            $url = config('qiniu.qiniu_data.node_url') . '?aid=' . $value->id . '&down_link=' . urlencode($value->down_link);
            $this->curl->runSmall($url);
        }
        //cli
        if (config('qiniu.qiniu_data.is_cli')) {
            $this->info("node parse down_link end");
        }
        //log
        if ($this->isCommandLogs === true) {
            $command = "node parse down_link end\n";
            file_put_contents($this->commandLogsFile, $command, FILE_APPEND);
        }
    }


    /**
     * 填充豆瓣内容,下载图片,提交dede后台,上传cdn
     *
     * @param  $queueName 具体的操作类型
     * @param $keyWordSuffix 百度搜索图片时的后缀
     *
     */
    public function runOther($queueName, $keyWordSuffix)
    {
        global $isSend;
        global $isUpdate;

        //内容页
        if ($queueName === 'all' || $queueName == 'content' || $queueName == 'other') {
            //logs
            if ($this->isCommandLogs === true) {
                $command = "开始采集内容页 \n";
                file_put_contents($this->commandLogsFile, $command, FILE_APPEND);
            }
            // php artisan caiji:ygdy8_get_content 17(type_id)
            //豆瓣数据填充
            $this->callSilent('caiji:douban', ['db_name' => $this->dbName, 'table_name' => $this->tableName, 'type_id' => $this->typeId]);
            $this->callSilent('caiji:baike', ['db_name' => $this->dbName, 'table_name' => $this->tableName, 'type_id' => $this->typeId]);
//
            //logs
            echo "内容页采集完成! \n";
            if ($this->isCommandLogs === true) {
                $command = "内容页采集完成! \n\n";
                file_put_contents($this->commandLogsFile, $command, FILE_APPEND);
            }
            if ($queueName == 'content') {
                exit;
            }
        }

        //下载图片
        if ($queueName === 'all' || $queueName == 'pic' || $queueName == 'other') {
            //logs
            if ($this->isCommandLogs === true) {
                $command = "开始下载图片 \n";
                file_put_contents($this->commandLogsFile, $command, FILE_APPEND);
            }

            //内容页图片
            $this->callSilent('xiazai:imgdownygdy8', ['type' => 'body', 'qiniu_dir' => $this->qiniuDir, 'type_id' => $this->typeId, 'db_name' => $this->dbName, 'table_name' => $this->tableName]);
            //缩略图
            $this->callSilent('xiazai:imgdownygdy8', ['type' => 'litpic', 'qiniu_dir' => $this->qiniuDir, 'type_id' => $this->typeId, 'db_name' => $this->dbName, 'table_name' => $this->tableName]);
            //百度图片
            $this->callSilent('caiji:baidulitpic', ['db_name' => $this->dbName, 'table_name' => $this->tableName, 'qiniu_dir' => $this->qiniuDir, 'type_id' => $this->typeId, 'key_word_suffix' => $keyWordSuffix]);

            echo "图片采集完成! \n";
            if ($this->isCommandLogs === true) {
                $command = "图片采集完成! \n";
                file_put_contents($this->commandLogsFile, $command, FILE_APPEND);
            }
            if ($queueName == 'pic') {
                exit;
            }
        }

        //上线部署
        if ($queueName === 'all' || $queueName == 'dede' || $queueName == 'other') {
            //logs
            if ($this->isCommandLogs === true) {
                $command = "将新添加数据提交到dede后台 \n";
                file_put_contents($this->commandLogsFile, $command, FILE_APPEND);
            }

            //node格式化下载链接
            $this->nodeDownLink();
            //将新添加数据提交到dede后台 is_post = -1
            $this->callSilent('send:dedea67post', ['db_name' => $this->dbName, 'table_name' => $this->tableName, 'channel_id' => $this->channelId, 'typeid' => $this->typeId]);
            //将更新数据提交到dede后台,直接替换数据库
            $this->callSilent('dede:makehtml', ['type' => 'update', 'typeid' => $this->typeId]);
            if ($isUpdate || $isSend) {
                //更新列表页
                $this->callSilent('dede:makehtml', ['type' => 'list', 'typeid' => $this->typeId]);
            }
            //logs
            echo "上线部署完成! \n";
            if ($this->isCommandLogs === true) {
                $command = "上线部署完成! \n\n";
                file_put_contents($this->commandLogsFile, $command, FILE_APPEND);
            }
            if ($queueName == 'dede') {
                exit;
            }
        }

        //上传图片
        if ($queueName === 'all' || $queueName == 'cdn' || $queueName == 'other') {
            //logs
            if ($this->isCommandLogs === true) {
                $command = "开始上传图片 qiniu\n";
                file_put_contents($this->commandLogsFile, $command, FILE_APPEND);
            }
            //只有新增了数据才会去上传图片
            if ($queueName == 'cdn') {
                $isSend = true;
            }
            $localDir = '';
            if ($isSend) {
                //图片上传
                $localDir = rtrim(config('qiniu.qiniu_data.www_root'), '/') . '/' . date('ymd') . $this->typeId;
                $this->callSilent('send:qiniuimgs', ['local_dir' => $localDir, 'qiniu_dir' => trim($this->qiniuDir, '/') . '/' . date('ymd') . $this->typeId . '/']);
            }
            //logs
            echo "cdn传输完成,dirname {$localDir}!\n";
            if ($this->isCommandLogs === true) {
                $command = "cdn传输完成,dirname {$localDir}!\n";
                file_put_contents($this->commandLogsFile, $command, FILE_APPEND);
            }
            if ($queueName == 'cdn') {
                exit;
            }
        }

        //logs
        echo "内容更新完成! \n";
        if ($this->isCommandLogs === true) {
            $command = "内容更新完成! \n\n\n";
            file_put_contents($this->commandLogsFile, $command, FILE_APPEND);
        }

    }

}

