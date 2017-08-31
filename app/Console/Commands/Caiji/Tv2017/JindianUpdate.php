<?php

namespace App\Console\Commands\Caiji\Tv2017;

use Illuminate\Console\Command;
use App\Console\Commands\Mytraits\Tv2017;
use App\Console\Commands\Mytraits\DedeLogin;

class JindianUpdate extends Command
{
    use Tv2017;
    use DedeLogin;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'caiji:tv2017_jindian_update {page_start}{page_tot}{type_id}{aid?} {--queue=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '更新采集2017tv电影';


    //库名与表名
    protected $dbName;
    protected $tableName;
    //dede后台cookie
    protected $cookie;


    //出错的时候调用大于这个aid的数据
    public $aid;
    public $typeId;
    public $channelId = 17;
    public $qiniuDir = 'movies/imgs';

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

        $aid = empty($this->argument('aid')) ? 0 : $this->argument('aid');
        $this->aid = $aid;
        $url = 'http://www.2015tt.com/list/index1.html';
        //得到这条命令los
        if($this->isCommandLogs === true) {
            $command = "=========================================\n";
            $command .= date('Y-m-d H:i:s') . "\ncaiji:tv2017_jindian_update {$pageStart} {$pageTot} {$this->typeId} {$aid} {$queueName} \n the link is {$url} \n";
            file_put_contents($this->commandLogsFile, $command, FILE_APPEND);
        }
        //得到所有的列表页
        $this->MovieInit();
        //logs
        if($this->isCommandLogs === true) {
            $command = "开始采集列表页\n";
            file_put_contents($this->commandLogsFile, $command, FILE_APPEND);
        }

        if($queueName == 'all' || $queueName == 'list') {
            $this->movieList($pageStart,$pageTot,$url,true);

            if(empty($this->listNum)){
                $this->listNum = 0;
            }
            echo "列表页采集完成,一共 {$this->listNum} 条! \n";
            //logs
            if($this->isCommandLogs === true) {
                $command = "列表页采集完成,一共 {$this->listNum} 条! \n\n";
                file_put_contents($this->commandLogsFile, $command, FILE_APPEND);
            }
            if ($queueName == 'list') {
                exit;
            }
        }
        if($this->listNum < 1){
            echo "列表页为空,结束! \n";
            //logs
            if($this->isCommandLogs === true) {
                $command = "列表页为空,结束! \n\n";
                file_put_contents($this->commandLogsFile, $command, FILE_APPEND);
            }
            exit;
        }

        //内容页
        //logs
        if($this->isCommandLogs === true) {
            $command = "开始采集内容页 \n";
            file_put_contents($this->commandLogsFile, $command, FILE_APPEND);
        }
        if($queueName == 'all' || $queueName == 'content') {
            $this->aid = $aid;
            $this->getContent();
            echo "内容页采集完成,一共 {$this->contentNum} 条! \n";
            //logs
            if($this->isCommandLogs === true) {
                $command = "内容页采集完成,一共 {$this->contentNum} 条! \n\n";
                file_put_contents($this->commandLogsFile,$command,FILE_APPEND);
            }

            if ($queueName == 'content') {
                exit;
            }
        }

        //下载图片
        //logs
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

            echo "图片采集完成! \n";
            //logs
            if($this->isCommandLogs === true) {
                $command = "图片采集完成! \n";
                file_put_contents($this->commandLogsFile, $command, FILE_APPEND);
            }
            if ($queueName == 'pic') {
                exit;
            }
        }

        //将新添加数据提交到dede后台 is_post = -1
        //logs
        if($this->isCommandLogs === true) {
            $command = "将新添加数据提交到dede后台 \n";
            file_put_contents($this->commandLogsFile, $command, FILE_APPEND);
        }
        if($queueName == 'all' || $queueName == 'dede') {
            //将新添加数据提交到dede后台 is_post = -1
            $this->callSilent('send:dedea67post', ['db_name'=>$this->dbName,'table_name'=>$this->tableName,'channel_id' => $this->channelId, 'typeid' => $this->typeId]);
            if ($isSend) {
                //更新列表页
                $this->callSilent('dede:makehtml',['type'=>'list','typeid'=>$this->typeId]);
            }
            echo "上线部署完成! \n";
            //logs
            if($this->isCommandLogs === true) {
                $command = "上线部署完成! \n\n";
                file_put_contents($this->commandLogsFile, $command, FILE_APPEND);
            }
            if ($queueName == 'dede') {
                exit;
            }
        }

        //只有新增了数据才会去上传图片
        //logs
        if($this->isCommandLogs === true) {
            $command = "开始上传图片 qiniu\n";
            file_put_contents($this->commandLogsFile, $command, FILE_APPEND);
        }
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
            //logs
            if($this->isCommandLogs === true) {
                $command = "cdn传输完成,dirname {$localDir}!\n";
                file_put_contents($this->commandLogsFile, $command, FILE_APPEND);
            }
            if($queueName == 'cdn'){
                exit;
            }
        }
        echo "内容更新完成! \n";
        //logs
        if($this->isCommandLogs === true) {
            $command = "内容更新完成! \n\n\n";
            file_put_contents($this->commandLogsFile, $command, FILE_APPEND);
        }

    }
}
