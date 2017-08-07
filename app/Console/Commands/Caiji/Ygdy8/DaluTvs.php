<?php

namespace App\Console\Commands\Caiji\Ygdy8;

use Illuminate\Console\Command;
use App\Console\Commands\Mytraits\Ygdy8;
use App\Console\Commands\Mytraits\Douban;

class DaluTvs extends Command
{
    use Ygdy8;
    use Douban;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'caiji:ygdy8_dalutvs {aid?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '采集阳光电影8的大陆电视剧信息';

    //库名与表名
    public $dbName;
    public $tableName;


    //出错的时候调用大于这个aid的数据
    public $aid;
    public $typeId = 17;


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
        $this->aid = empty($this->argument('aid'))?0:$this->argument('aid');

        //内地电视 16 http://www.ygdy8.net/html/tv/hytv/list_71_2.html
        $url = 'http://www.ygdy8.net/html/tv/hytv/list_71_2.html';
        $pageTot = 16;
        //得到所有的列表页
        $this->MovieInit();
        $this->movieList($pageTot,$url,true);
        $this->getContent('other');
//        $this->aid = $aid;
//        $this->perfectContent();

        //下载图片
//        $qiniuDir = 'tvs/imgs';
        //内容页图片
        //9450
//        $this->call('xiazai:imgdownygdy8',['type'=>'body','qiniu_dir'=>$qiniuDir,'type_id'=>$this->typeId,'db_name'=>$this->dbName,'table_name'=>$this->tableName]);
        //缩略图
//        $this->call('xiazai:imgdownygdy8',['type'=>'litpic','qiniu_dir'=>$qiniuDir,'type_id'=>$this->typeId,'db_name'=>$this->dbName,'table_name'=>$this->tableName]);
        //百度图片
//        $this->call('caiji:baidulitpic',['qiniu_dir'=>$qiniuDir,'type_id'=>$this->typeId]);

    }
}
