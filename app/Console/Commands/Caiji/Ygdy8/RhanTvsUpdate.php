<?php

namespace App\Console\Commands\Caiji\Ygdy8;

use Illuminate\Console\Command;
use App\Console\Commands\Mytraits\Ygdy8;
use App\Console\Commands\Mytraits\Douban;
use App\Console\Commands\Mytraits\DedeLogin;

class RhanTvsUpdate extends Command
{
    use Ygdy8;
    use Douban;
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
    protected $description = '采集阳光电影8的日韩电视剧信息';

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
        global $isUpdate;
        global $listNum;

        $queueName = $this->option('queue');
        $pageStart = $this->argument('page_start');
        $pageTot = $this->argument('page_tot');
        $this->typeId = $this->argument('type_id');

        $aid = empty($this->argument('aid')) ? 0 : $this->argument('aid');
        $this->aid = $aid;

        $url = 'http://www.ygdy8.net/html/tv/rihantv/list_8_2.html';
        //得到所有的列表页
        if($queueName === null || $queueName == 'list') {
            $this->MovieInit();
            $this->movieList($pageStart, $pageTot, $url, true);
            echo "列表页采集完成,一共 {$listNum} 条! \n";
            if ($queueName == 'list') {
                exit;
            }
        }
        if($queueName == 'list' && $listNum < 1){
           exit;
        }

        if($queueName === null || $queueName == 'content') {
            $this->getContent(true);
            $this->aid = $aid;
            //豆瓣数据填充
            $this->perfectContent();
            echo "内容页采集完成! \n";
            if ($queueName == 'content') {
                exit;
            }
        }

        if($queueName === null || $queueName == 'pic') {
            //内容页图片
            $this->call('xiazai:imgdownygdy8', ['type' => 'body', 'qiniu_dir' => $this->qiniuDir, 'type_id' => $this->typeId, 'db_name' => $this->dbName, 'table_name' => $this->tableName]);
            //缩略图
            $this->call('xiazai:imgdownygdy8', ['type' => 'litpic', 'qiniu_dir' => $this->qiniuDir, 'type_id' => $this->typeId, 'db_name' => $this->dbName, 'table_name' => $this->tableName]);
            //百度图片
            $this->call('caiji:baidulitpic', ['qiniu_dir' => $this->qiniuDir, 'type_id' => $this->typeId, 'key_word_suffix' => '电视剧']);

            echo "图片采集完成! \n";
            if ($queueName == 'pic') {
                exit;
            }
        }

        if($queueName === null || $queueName == 'dede') {
            //node格式化下载链接
            $this->nodeDownLink();
            //将更新数据提交到dede后台,直接替换数据库
            $this->call('dede:makehtml', ['type' => 'update', 'typeid' => $this->typeId]);
            //将新添加数据提交到dede后台 is_post = -1
            $this->call('send:dedea67post', ['db_name'=>$this->dbName,'table_name'=>$this->tableName,'channel_id' => $this->channelId, 'typeid' => $this->typeId]);
            if ($isUpdate || $isSend) {
                //更新列表页
                $this->call('dede:makehtml',['type'=>'list','typeid'=>$this->typeId]);
            }
            echo "上线部署完完成! \n";
            if ($queueName == 'dede') {
                exit;
            }
        }

        if($queueName === null || $queueName == 'cdn') {
            //只有新增了数据才会去上传图片
            if($queueName == 'cdn'){
                $isSend = true;
            }
            $localDir = '';
            if ($isSend) {
                //图片上传
                $localDir = config('qiniu.qiniu_data.www_root') . '/' . date('ymd') . $this->typeId;
                $this->call('send:qiniuimgs', ['local_dir' =>$localDir, 'qiniu_dir' => trim($this->qiniuDir, '/') . '/' . date('ymd') . $this->typeId . '/']);
            }
            echo "cdn传输完成,dirname {$localDir}!\n";
            if($queueName == 'cdn'){
                exit;
            }
        }

        $this->info('ygdy8 rhantvs update end!');
    }


}


