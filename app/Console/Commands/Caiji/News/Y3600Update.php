<?php

namespace App\Console\Commands\Caiji\News;

use Illuminate\Console\Command;
use App\Console\Commands\Mytraits\DedeLogin;
use App\Console\Commands\Mytraits\NewsY3600;
use App\Console\Commands\Mytraits\Common;
use Illuminate\Support\Facades\DB;

class Y3600Update extends Command
{
    use Common;
    use DedeLogin;
    use NewsY3600;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'caiji:news_y3600_update {page_start}{page_tot}{type_id}{--queue=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '更新采集y3600新闻信息';

    public $typeId;
    public $channelId = 1;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->initBegin();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
        $queueName = $this->option('queue');
        $pageStart = $this->argument('page_start');
        $pageTot = $this->argument('page_tot');
        $this->typeId = $this->argument('type_id');

        $url = 'http://www.y3600.com/news/index.html';
        //得到这条命令
        $message = date('Y-m-d H:i:s') . " caiji:news_y3600_update {$pageStart} {$pageTot} {$this->typeId} {$queueName} the link is {$url} ".PHP_EOL;
        $this->info($message);

        if($queueName == 'all' || $queueName == 'list') {
            $this->movieList($pageStart, $pageTot, $url);

            $message .= "y3600 列表页采集完成,一共 {$this->listNum} 条!".PHP_EOL;
            $this->info($message);
            //列表页为空
            if($this->listNum < 1){
                $message .= "列表页为空,结束! ".PHP_EOL;
                $this->info($message);
            }
            //日志
            if($this->isCommandLogs === true) {
                file_put_contents($this->commandLogsFile, $message, FILE_APPEND);
            }
            if ($queueName == 'list' || $this->listNum < 1) {
                exit;
            }
        }

        if($queueName == 'all' || $queueName == 'content') {
            $this->getContent();
            //删除内容页没有保存成功的数据
            DB::connection($this->dbName)->table($this->tableName)->where('typeid', $this->typeId)->where('is_con', -1)->delete();
            if ($queueName == 'content') {
                exit;
            }
        }

        //下载图片
        if($queueName == 'all' || $queueName == 'pic') {
            //封面图片
            $this->call('xiazai:img',['action'=>'litpic','type_id'=>$this->typeId]);
            //删除掉剩余封面图片完整的记录
            DB::connection($this->dbName)->table($this->tableName)->where('typeid', $this->typeId)->where('is_litpic', -1)->delete();
            if ($queueName == 'pic') {
                exit;
            }
        }

        //提交到dede后台
        if($queueName == 'all' || $queueName == 'dede') {
            //将新添加数据提交到dede后台 is_post = -1
            $this->call('send:dedenewpost', ['channel_id' => $this->channelId, 'typeid' => $this->typeId]);
            if (file_exists($this->dedeSendStatusFile)) {
                //更新列表页
                $message .= "更新列表页".PHP_EOL;
                $this->info($message);
                $this->call('dede:makehtml',['type'=>'list','typeid'=>$this->typeId]);
            }
            $message .= "上线部署完成!".PHP_EOL;
            //日志
            if($this->isCommandLogs === true) {
                file_put_contents($this->commandLogsFile, $message, FILE_APPEND);
            }
            if ($queueName == 'dede') {
                exit;
            }
        }
    }
}

