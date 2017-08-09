<?php

namespace App\Console\Commands\Caiji\Tv2017;

use Illuminate\Console\Command;
use App\Console\Commands\Mytraits\Tv2017;
use App\Console\Commands\Mytraits\Douban;
use App\Console\Commands\Mytraits\DedeLogin;

class Jindian extends Command
{
    use Tv2017;
    use Douban;
    use DedeLogin;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'caiji:tv2017_jindian {aid?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '采集2017tv电影';


    //库名与表名
    protected $dbName;
    protected $tableName;
    //dede后台cookie
    protected $cookie;


    //出错的时候调用大于这个aid的数据
    public $aid;
    public $typeId = 23;
    public $channelId = 17;

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
        $qiniuDir = 'tvs/imgs/';
        //欧美 13 http://www.ygdy8.net/html/tv/oumeitv/index.html http://www.ygdy8.net/html/tv/oumeitv/list_9_2.html
        $url = 'http://www.2015tt.com/list/index1.html';
        $pageStart = 1;
        $pageTot = 233;
        //得到所有的列表页
        $this->MovieInit();
        $this->movieList($pageStart,$pageTot,$url);
        dd(22222);
        $this->aid = $aid;
        $this->getContent('other');
        $this->aid = $aid;
        //更新douban信息
        $this->perfectContent();

        //内容页图片
        //9450
        $this->call('xiazai:imgdownygdy8', ['type' => 'body', 'qiniu_dir' => $qiniuDir, 'type_id' => $this->typeId, 'db_name' => $this->dbName, 'table_name' => $this->tableName]);
        //缩略图
        $this->call('xiazai:imgdownygdy8', ['type' => 'litpic', 'qiniu_dir' => $qiniuDir, 'type_id' => $this->typeId, 'db_name' => $this->dbName, 'table_name' => $this->tableName]);
        //百度图片
        $this->call('caiji:baidulitpic',['qiniu_dir'=>$qiniuDir,'type_id'=>$this->typeId,'key_word_suffix'=>'电视剧']);

        echo "====================================\n";
        echo "add dede admin begin ! \n";
        //node格式化下载链接
        $this->nodeDownLink();
        //将新添加数据提交到dede后台 is_post = -1
        $this->call('send:dedea67post', ['channel_id' => $this->channelId, 'typeid' => $this->typeId]);
        if ($isSend = true) {
            //更新列表页
            $this->makeLanmu();
        }
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
