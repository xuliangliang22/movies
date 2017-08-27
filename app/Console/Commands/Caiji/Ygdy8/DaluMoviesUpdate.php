<?php

namespace App\Console\Commands\Caiji\Ygdy8;

use Illuminate\Console\Command;
use App\Console\Commands\Mytraits\Ygdy8;
use App\Console\Commands\Mytraits\DedeLogin;

class DaluMoviesUpdate extends Command
{
    use Ygdy8;
    use DedeLogin;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'caiji:ygdy8_dalumovies_update {aid?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '更新阳光电影8的大陆电影信息';

    //库名与表名
    public $dbName;
    public $tableName;

    //dede后台cookie
    protected $cookie;


    //出错的时候调用大于这个aid的数据
    public $aid;
    public $typeId = 13;
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
        $aid = empty($this->argument('aid')) ? 0 : $this->argument('aid');
        $this->aid = $aid;

        $url = 'http://www.ygdy8.com/html/gndy/china/list_4_2.html';
        $pageStart = 1;
        $pageTot = 93;
        //下载图片
        $qiniuDir = 'tvs/imgs';
        //得到所有的列表页
        echo "update start\n";
        echo "====================================\n";
        echo "list and content and douban begin ! \n";
        $this->MovieInit();
        $this->movieList($pageStart,$pageTot, $url, true);
        $this->getContent('other', true);
        $this->aid = $aid;
        $this->perfectContent();
        echo "list and content and douban end ! \n";
        echo "====================================\n\n";

        echo "====================================\n";
        echo "img down begin ! \n";
        //内容页图片
        $this->call('xiazai:imgdownygdy8', ['type' => 'body', 'qiniu_dir' => $qiniuDir, 'type_id' => $this->typeId, 'db_name' => $this->dbName, 'table_name' => $this->tableName]);
        //缩略图
        $this->call('xiazai:imgdownygdy8', ['type' => 'litpic', 'qiniu_dir' => $qiniuDir, 'type_id' => $this->typeId, 'db_name' => $this->dbName, 'table_name' => $this->tableName]);
        //百度图片
        $this->call('caiji:baidulitpic', ['qiniu_dir' => $qiniuDir, 'type_id' => $this->typeId, 'key_word_suffix' => '电影']);
        echo "img down end ! \n";
        echo "====================================\n\n";
//
        echo "====================================\n";
        echo "update dede admin begin ! \n";
        //node格式化下载链接
        $this->nodeDownLink();
        //将更新数据提交到dede后台,直接替换数据库
        $isUpdate = $this->dedeDownLinkUpdate();
        //将新添加数据提交到dede后台 is_post = -1
        $this->call('send:dedea67post', ['channel_id' => $this->channelId, 'typeid' => $this->typeId]);
        if ($isUpdate || $isSend) {
            //更新列表页
            $this->makeLanmu();
        }
        echo "update dede admin end ! \n";
        echo "====================================\n\n";

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


