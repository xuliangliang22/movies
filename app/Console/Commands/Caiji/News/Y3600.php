<?php

namespace App\Console\Commands\Caiji\News;

use Illuminate\Console\Command;
use App\Console\Commands\Mytraits\DedeLogin;
use App\Console\Commands\Mytraits\NewsY3600;

class Y3600 extends Command
{
    use DedeLogin;
    use NewsY3600;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'caiji:news_y3600 {aid?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '采集y3600新闻信息';

    //库名与表名
    protected $dbName;
    protected $tableName;
    //dede后台cookie
    protected $cookie;


    //出错的时候调用大于这个aid的数据
    public $aid;
    public $typeId = 22;
    public $channelId = 1;

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
        $aid = empty($this->argument('aid')) ? 0 : $this->argument('aid');
        $this->aid = $aid;
        //下载图片
        $qiniuDir = 'news/imgs/';
        //欧美 13 http://www.ygdy8.net/html/tv/oumeitv/index.html http://www.ygdy8.net/html/tv/oumeitv/list_9_2.html
        $url = 'http://www.y3600.com/news/index.html';
        $pageStart = 1;
        $pageTot = 10;
        //得到所有的列表页
        $this->MovieInit();
//        $this->movieList($pageStart,$pageTot,$url);
//        $this->aid = $aid;
//        $this->getContent();
//        dd();

        //内容页图片
        //9450
//        $this->call('xiazai:imgdownygdy8', ['type' => 'body', 'qiniu_dir' => $qiniuDir, 'type_id' => $this->typeId, 'db_name' => $this->dbName, 'table_name' => $this->tableName]);
        //缩略图
//        $this->call('xiazai:imgdownygdy8', ['type' => 'litpic', 'qiniu_dir' => $qiniuDir, 'type_id' => $this->typeId, 'db_name' => $this->dbName, 'table_name' => $this->tableName]);
        //百度图片
//        $this->call('caiji:baidulitpic',['qiniu_dir'=>$qiniuDir,'type_id'=>$this->typeId,'key_word_suffix'=>'娱乐']);

        echo "====================================\n";
        echo "add dede admin begin ! \n";

        //将新添加数据提交到dede后台 is_post = -1
//        $this->call('send:dedenewpost', ['channel_id' => $this->channelId, 'typeid' => $this->typeId]);
        if ($isSend = true) {
            //更新列表页
            $this->makeLanmu();
        }
        dd(222);
        echo "add dede admin end ! \n";
        echo "====================================\n\n";
//
        echo "====================================\n";
        echo "send to qiniu imgs begin !\n";
        //只有新增了数据才会去上传图片
        if ($isSend) {
            //图片上传
            $this->call('send:qiniuimgs', ['local_dir' => config('qiniu.qiniu_data.www_root') . '/' . date('ymd') . $this->typeId, 'qiniu_dir' => trim($qiniuDir,'/') .'/'. date('ymd') .$this->typeId. '/']);
        }
        echo "send to qiniu imgs end !\n";
        echo "====================================\n\n";
    }
}

