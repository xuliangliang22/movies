<?php

namespace App\Console\Commands\Caiji\News;

use Illuminate\Console\Command;
use App\Console\Commands\Mytraits\DedeLogin;
use App\Console\Commands\Mytraits\NewsY3600;

class Y3600Update extends Command
{
    use DedeLogin;
    use NewsY3600;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'caiji:news_y3600_update {page_start}{page_tot}{type_id}{aid?} {--queue=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '更新采集y3600新闻信息';

    //库名与表名
    protected $dbName;
    protected $tableName;
    //dede后台cookie
    protected $cookie;


    //出错的时候调用大于这个aid的数据
    public $aid;
    public $typeId;
    public $channelId = 1;
    public $qiniuDir = 'new/imgs';

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
        $url = 'http://www.y3600.com/news/index.html';
        //得到所有的列表页
        $this->MovieInit();
        if($queueName == 'all' || $queueName == 'list') {
            $this->movieList($pageStart, $pageTot, $url,true);
            echo "列表页采集完成,一共 {$this->listNum} 条! \n";
            if ($queueName == 'list') {
                exit;
            }
        }
        if($queueName == 'list' && $this->listNum < 1){
            exit;
        }

        //内容页
        if($queueName == 'all' || $queueName == 'content') {
            $this->aid = $aid;
            $this->getContent();
            echo "内容页采集完成,一共 {$this->contentNum} 条! \n";
            if ($queueName == 'content') {
                exit;
            }
        }

        //下载图片
        if($queueName == 'all' || $queueName == 'pic') {
            //内容页图片
            //9450
            $this->callSilent('xiazai:imgdownygdy8', ['type' => 'body', 'qiniu_dir' => $this->qiniuDir, 'type_id' => $this->typeId, 'db_name' => $this->dbName, 'table_name' => $this->tableName]);
            //缩略图
            $this->callSilent('xiazai:imgdownygdy8', ['type' => 'litpic', 'qiniu_dir' => $this->qiniuDir, 'type_id' => $this->typeId, 'db_name' => $this->dbName, 'table_name' => $this->tableName]);
            //百度图片
            $this->callSilent('caiji:baidulitpic', ['db_name'=>$this->dbName,'table_name'=>$this->tableName,'qiniu_dir' => $this->qiniuDir, 'type_id' => $this->typeId, 'key_word_suffix' => '娱乐']);

            echo "图片采集完成! \n";
            if ($queueName == 'pic') {
                exit;
            }
        }

        //将新添加数据提交到dede后台 is_post = -1
        if($queueName == 'all' || $queueName == 'dede') {
            //将新添加数据提交到dede后台 is_post = -1
            $this->callSilent('send:dedenewpost', ['db_name'=>$this->dbName,'table_name'=>$this->tableName,'channel_id' => $this->channelId, 'typeid' => $this->typeId]);
            if ($isSend) {
                //更新列表页
                $this->callSilent('dede:makehtml',['type'=>'list','typeid'=>$this->typeId]);
            }
            echo "上线部署完成! \n";
            if ($queueName == 'dede') {
                exit;
            }
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
            if($queueName == 'cdn'){
                exit;
            }
        }
        echo "内容更新完成! \n";
    }
}

