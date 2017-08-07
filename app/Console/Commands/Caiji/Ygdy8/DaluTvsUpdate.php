<?php

namespace App\Console\Commands\Caiji\Ygdy8;

use Illuminate\Console\Command;
use App\Console\Commands\Mytraits\Ygdy8;
use App\Console\Commands\Mytraits\Douban;
use Illuminate\Support\Facades\DB;
use App\Console\Commands\Mytraits\DedeLogin;

class DaluTvsUpdate extends Command
{
    use Ygdy8;
    use Douban;
    use DedeLogin;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'caiji:ygdy8_dalutvs_update {aid?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '采集阳光电影8的大陆电视剧信息';

    //库名与表名
    public $dbName;
    public $tableName;

    //dede后台的名称
    protected $dedeUrl;
    protected $dedeUser;
    protected $dedePwd;
    protected $cookie;


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

        $this->dedeUrl = config('qiniu.qiniu_data.dede_url');
        $this->dedeUser = config('qiniu.qiniu_data.dede_user');
        $this->dedePwd = config('qiniu.qiniu_data.dede_pwd');


    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $aid = empty($this->argument('aid')) ? 0 : $this->argument('aid');
        $this->aid = $aid;

        //内地电视 16 http://www.ygdy8.net/html/tv/hytv/list_71_2.html
        $url = 'http://www.ygdy8.net/html/tv/hytv/list_71_2.html';
        $pageTot = 16;
        //得到所有的列表页
        $this->MovieInit();
//        $this->movieList($pageTot,$url,true);
//        $this->getContent('other',true);
//        $this->aid = $aid;
//        $this->perfectContent();

        //下载图片
        $qiniuDir = 'tvs/imgs';
        //内容页图片
        //9450
//        $this->call('xiazai:imgdownygdy8',['type'=>'body','qiniu_dir'=>$qiniuDir,'type_id'=>$this->typeId,'db_name'=>$this->dbName,'table_name'=>$this->tableName]);
        //缩略图
//        $this->call('xiazai:imgdownygdy8',['type'=>'litpic','qiniu_dir'=>$qiniuDir,'type_id'=>$this->typeId,'db_name'=>$this->dbName,'table_name'=>$this->tableName]);
        //百度图片
//        $this->call('caiji:baidulitpic',['qiniu_dir'=>$qiniuDir,'type_id'=>$this->typeId,'key_word_suffix'=>'电视剧']);

        //node格式化下载链接
        //$this->nodeDownLink();
        $this->dedeDownLinkUpdate();
    }

    public function dedeDownLinkUpdate()
    {
        $dedeDownLinkUpdateUrl = config('qiniu.qiniu_data.dede_url') . 'myplus/down_link_update.php';
        $isNoDownLinks = DB::connection($this->dbName)->table($this->tableName)->where('typeid', $this->typeId)->where('is_update', -1)->get();
        $tot = count($isNoDownLinks);

        foreach ($isNoDownLinks as $key => $value) {
            $this->info("{$key}/{$tot} id is {$value->id}");
            echo $value->down_link."\n";
            $upUrl = $dedeDownLinkUpdateUrl.'?typeid='.$value->typeid.'&title='.$value->title.'&down_link='.$value->down_link;
            //先登录
            $rest = $this->dedeLogin($this->dedeUrl.'login.php',$this->dedeUser,$this->dedePwd);

            if($rest) {
                $this->curl->add()
                    ->opt_targetURL($upUrl)
                    ->opt_sendHeader('Cookie',$this->cookie)
                    ->done('get');
                $this->curl->run();
                $content = $this->curl->getAll();
                dd($content['body']);
            }else{
                dd('fail');
            }
        }
    }
}


