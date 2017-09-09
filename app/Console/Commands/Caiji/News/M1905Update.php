<?php

namespace App\Console\Commands\Caiji\News;

use Illuminate\Console\Command;
use App\Console\Commands\Mytraits\DedeLogin;
use App\Console\Commands\Mytraits\M1905;

class M1905Update extends Command
{
    use DedeLogin;
    use M1905;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'caiji:news_m1905_update {page_start}{page_tot}{type_id}{--queue=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '更新采集m1905影评信息';

    //库名与表名
    protected $dbName;
    protected $tableName;
    //dede后台cookie
    protected $cookie;


    //出错的时候调用大于这个aid的数据
    public $typeId;
    public $channelId = 1;
    public $qiniuDir = 'news/imgs';

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
        //
        $queueName = $this->option('queue');
        $pageStart = $this->argument('page_start');
        $pageTot = $this->argument('page_tot');
        $this->typeId = $this->argument('type_id');

        $url = 'http://www.1905.com/api/content/index.php?m=converged&a=comment&page=%s&pagesize=20';
        //得到这条命令
        if($this->isCommandLogs === true) {
            $command = "=========================================\n";
            $command .= date('Y-m-d H:i:s') . "\ncaiji:news_y3600_update {$pageStart} {$pageTot} {$this->typeId}{$queueName} \n the link is {$url} \n";
            file_put_contents($this->commandLogsFile, $command, FILE_APPEND);
        }
        //得到所有的列表页
        $this->MovieInit();
        if($this->isCommandLogs === true) {
            $command = "开始采集列表页\n";
            file_put_contents($this->commandLogsFile, $command, FILE_APPEND);
        }
        if($queueName == 'all' || $queueName == 'list') {
            file_put_contents($this->commandLogsFile, "list start in \n", FILE_APPEND);
            //typeid = 24
            $this->movieList($pageStart, $pageTot, $url);

            if(empty($this->listNum)){
                $this->listNum = 0;
            }
            echo "列表页采集完成,一共 {$this->listNum} 条! \n";
            if($this->isCommandLogs === true) {
                $command = "列表页采集完成,一共 {$this->listNum} 条! \n\n";
                file_put_contents($this->commandLogsFile, $command, FILE_APPEND);
            }
            if ($queueName == 'list') {
                exit;
            }

            //
            if($this->listNum < 1){
                echo "列表页为空,结束! \n";
                if($this->isCommandLogs === true) {
                    $command = "列表页为空,结束! \n\n";
                    file_put_contents($this->commandLogsFile, $command, FILE_APPEND);
                }
                exit;
            }
        }

        //内容页
        if($this->isCommandLogs === true) {
            $command = "开始采集内容页 \n";
            file_put_contents($this->commandLogsFile, $command, FILE_APPEND);
        }
        if($queueName == 'all' || $queueName == 'content') {
            $this->getContent();
            echo "内容页采集完成,一共 {$this->contentNum} 条! \n";
            if($this->isCommandLogs === true) {
                $command = "内容页采集完成,一共 {$this->contentNum} 条! \n\n";
                file_put_contents($this->commandLogsFile,$command,FILE_APPEND);
            }
            if ($queueName == 'content') {
                exit;
            }
        }

        //下载图片
        if($this->isCommandLogs === true) {
            $command = "开始下载图片 \n";
            file_put_contents($this->commandLogsFile, $command, FILE_APPEND);
        }
        if($queueName == 'all' || $queueName == 'pic') {
            //内容页图片
            //9450
            $this->callSilent('xiazai:imgdownygdy8', ['type' => 'body', 'qiniu_dir' => $this->qiniuDir, 'type_id' => $this->typeId, 'db_name' => $this->dbName, 'table_name' => $this->tableName]);
            //缩略图
            $this->callSilent('xiazai:imgdownygdy8', ['type' => 'litpic', 'qiniu_dir' => $this->qiniuDir, 'type_id' => $this->typeId, 'db_name' => $this->dbName, 'table_name' => $this->tableName]);
            //百度图片
            $this->callSilent('caiji:baidulitpic', ['db_name'=>$this->dbName,'table_name'=>$this->tableName,'qiniu_dir' => $this->qiniuDir, 'type_id' => $this->typeId, 'key_word_suffix' => '娱乐']);

            echo "图片采集完成! \n";
            if($this->isCommandLogs === true) {
                $command = "图片采集完成! \n";
                file_put_contents($this->commandLogsFile, $command, FILE_APPEND);
            }
            if ($queueName == 'pic') {
                exit;
            }
        }

        //将新添加数据提交到dede后台 is_post = -1
        if($this->isCommandLogs === true) {
            $command = "将新添加数据提交到dede后台 \n";
            file_put_contents($this->commandLogsFile, $command, FILE_APPEND);
        }
        if($queueName == 'all' || $queueName == 'dede') {
            //将新添加数据提交到dede后台 is_post = -1
            $this->callSilent('send:dedenewpost', ['db_name'=>$this->dbName,'table_name'=>$this->tableName,'channel_id' => $this->channelId, 'typeid' => $this->typeId]);
            if ($isSend) {
                //更新列表页
                $this->callSilent('dede:makehtml',['type'=>'list','typeid'=>$this->typeId]);
            }
            echo "上线部署完成! \n";
            if($this->isCommandLogs === true) {
                $command = "上线部署完成! \n\n";
                file_put_contents($this->commandLogsFile, $command, FILE_APPEND);
            }
            if ($queueName == 'dede') {
                exit;
            }
        }

        //cdn
        if($this->isCommandLogs === true) {
            $command = "开始上传图片 qiniu\n";
            file_put_contents($this->commandLogsFile, $command, FILE_APPEND);
        }
        //只有新增了数据才会去上传图片
        if($queueName == 'all' || $queueName == 'cdn') {
            //只有新增了数据才会去上传图片
            if($queueName == 'cdn'){
                $isSend = true;
            }
            $localDir = '';
            if ($isSend) {
                //图片上传
                $localDir = rtrim(config('qiniu.qiniu_data.www_root'),'/') . '/' . date('ymd') . $this->typeId;
                $this->callSilent('send:qiniuimgs', ['local_dir' =>$localDir, 'qiniu_dir' => trim($this->qiniuDir, '/') . '/' . date('ymd') . $this->typeId . '/']);
            }
            echo "cdn传输完成,dirname {$localDir}!\n";
            if($this->isCommandLogs === true) {
                $command = "cdn传输完成,dirname {$localDir}!\n";
                file_put_contents($this->commandLogsFile, $command, FILE_APPEND);
            }
            if($queueName == 'cdn'){
                exit;
            }
        }
        echo "内容更新完成! \n";
        if($this->isCommandLogs === true) {
            $command = "内容更新完成! \n\n\n";
            file_put_contents($this->commandLogsFile, $command, FILE_APPEND);
        }
    }

}

