<?php

namespace App\Console\Commands\Caiji\Ygdy8;

use Illuminate\Console\Command;
use App\Console\Commands\Mytraits\Ygdy8;
use App\Console\Commands\Mytraits\DedeLogin;
use Illuminate\Support\Facades\DB;

class RhanTvsUpdate extends Command
{
    use Ygdy8;
    use DedeLogin;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'caiji:ygdy8_rhantvs_update {page_start}{page_tot}{type_id}{aid?} {--queue=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '跟踪采集阳光电影8的日韩电视剧信息';

    //库名与表名
    public $dbName;
    public $tableName;

    //dede后台cookie
    protected $cookie;


    //出错的时候调用大于这个aid的数据
    public $aid;
    public $typeId;
    public $channelId = 17;
    public $qiniuDir = 'tvs/imgs';

    //日志保存路径
    public $commandLogsFile;
    //是否开启日志
    public $isCommandLogs;


    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->dbName = config('qiniu.qiniu_data.db_name');
        $this->tableName = config('qiniu.qiniu_data.table_name');

        $this->commandLogsFile = config('qiniu.qiniu_data.command_logs_file');
        $this->isCommandLogs = config('qiniu.qiniu_data.is_command_logs');

        //---------this is my modify-------------
        if(!is_dir(public_path('command_logs'))){
            mkdir(public_path('command_logs'),0755,true);
        }
        //-------------------------

    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        global $isSend;
        global $isUpdate;

        $queueName = $this->option('queue');
        $pageStart = $this->argument('page_start');
        $pageTot = $this->argument('page_tot');
        $this->typeId = $this->argument('type_id');

        $aid = empty($this->argument('aid')) ? 0 : $this->argument('aid');
        $this->aid = $aid;

        // max_page_tot = 35 typeid = 18
        $url = 'http://www.ygdy8.net/html/tv/rihantv/list_8_2.html';
        //得到这条命令logs
        if ($this->isCommandLogs === true) {
            $command = "=========================================\n";
            $command .= date('Y-m-d H:i:s') . "\ncaiji:ygdy8_rhantvs_update {$pageStart} {$pageTot} {$this->typeId} {$aid} {$queueName} \n the link is {$url} \n";
            file_put_contents($this->commandLogsFile, $command, FILE_APPEND);
        }
        $this->MovieInit();

        //得到所有的列表页
        //logs
        if ($this->isCommandLogs === true) {
            $command = "开始采集列表页\n";
            file_put_contents($this->commandLogsFile, $command, FILE_APPEND);
        }

        if ($queueName === 'all' || $queueName == 'list') {
            $this->movieList($pageStart, $pageTot, $url, true);
            //logs
            if(empty($this->listNum)){
                $this->listNum = 0;
            }
            echo "列表页采集完成,一共 {$this->listNum} 条! \n";
            if ($this->isCommandLogs === true) {
                $command = "列表页采集完成,一共 {$this->listNum} 条! \n\n";
                file_put_contents($this->commandLogsFile, $command, FILE_APPEND);
            }
            if ($queueName == 'list') {
                exit;
            }

            //
            if ($this->listNum < 1) {
                //logs
                if ($this->isCommandLogs === true) {
                    $command = "列表页为空,结束! \n\n";
                    file_put_contents($this->commandLogsFile, $command, FILE_APPEND);
                }
                exit;
            }
        }


        //内容页
        //logs
        if ($this->isCommandLogs === true) {
            $command = "开始采集内容页 \n";
            file_put_contents($this->commandLogsFile, $command, FILE_APPEND);
        }

        if ($queueName === 'all' || $queueName == 'content') {
            $this->getContent(true);


            $this->aid = $aid;
            //豆瓣数据填充
            $this->callSilent('caiji:douban', ['db_name' => $this->dbName, 'table_name' => $this->tableName, 'type_id' => $this->typeId]);
            $this->callSilent('caiji:baike', ['db_name' => $this->dbName, 'table_name' => $this->tableName, 'type_id' => $this->typeId]);

            //logs
            echo "内容页采集完成,一共 {$this->contentNum} 条! \n";
            if ($this->isCommandLogs === true) {
                $command = "内容页采集完成,一共 {$this->contentNum} 条! \n\n";
                file_put_contents($this->commandLogsFile, $command, FILE_APPEND);
            }
            if ($queueName == 'content') {
                exit;
            }
        }

        //下载图片
        //logs
        if ($this->isCommandLogs === true) {
            $command = "开始下载图片 \n";
            file_put_contents($this->commandLogsFile, $command, FILE_APPEND);
        }

        if ($queueName === 'all' || $queueName == 'pic') {
            //内容页图片
            $this->callSilent('xiazai:imgdownygdy8', ['type' => 'body', 'qiniu_dir' => $this->qiniuDir, 'type_id' => $this->typeId, 'db_name' => $this->dbName, 'table_name' => $this->tableName]);
            //缩略图
            $this->callSilent('xiazai:imgdownygdy8', ['type' => 'litpic', 'qiniu_dir' => $this->qiniuDir, 'type_id' => $this->typeId, 'db_name' => $this->dbName, 'table_name' => $this->tableName]);
            //百度图片
            $this->callSilent('caiji:baidulitpic', ['db_name' => $this->dbName, 'table_name' => $this->tableName, 'qiniu_dir' => $this->qiniuDir, 'type_id' => $this->typeId, 'key_word_suffix' => '电视剧']);

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
        //logs
        if ($this->isCommandLogs === true) {
            $command = "将新添加数据提交到dede后台 \n";
            file_put_contents($this->commandLogsFile, $command, FILE_APPEND);
        }
        if ($queueName === 'all' || $queueName == 'dede') {
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
        //logs
        if ($this->isCommandLogs === true) {
            $command = "开始上传图片 qiniu\n";
            file_put_contents($this->commandLogsFile, $command, FILE_APPEND);
        }

        if ($queueName === 'all' || $queueName == 'cdn') {
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


