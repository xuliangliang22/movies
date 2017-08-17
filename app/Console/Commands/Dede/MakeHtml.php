<?php

namespace App\Console\Commands\Dede;

use Illuminate\Console\Command;
use App\Console\Commands\Mytraits\DedeLogin;
use Illuminate\Support\Facades\DB;

class MakeHtml extends Command
{
    use DedeLogin;
    /**
     * The name and signature of the console command.
     *
     * @var string
     * @type = list(生成栏目页) index(生成首页)
     */
    protected $signature = 'dede:makehtml {type}{typeid?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'dede后台列表页与首页的生成';

    public $curl;
    public $cookie;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        if(empty($this->curl)) {
            $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'curl' . DIRECTORY_SEPARATOR . 'curl.php';
            require_once $path;
            $this->curl = new \curl();
        }
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
        $type = $this->argument('type');
        $typeid = $this->argument('typeid');
        if($type == 'list'){
            if($typeid === null) {
                $this->error('生成栏目内容必须输入typeid!');
                exit;
            }
            $this->makeLanmu($typeid);
        }elseif ($type == 'index') {
            $this->makeIndex();
        }elseif ($type == 'update'){
            //update电视剧更新集数的时候使用
            if($typeid === null){
                $this->error('有更新数据,但你没有传入typeid,不能正确执行!');
                exit;
            }
            $this->dedeDownLinkUpdate($typeid);
        }
    }

    /**
     * dede生成栏目页
     */
    public function makeLanmu($typeId)
    {
        $url = config('qiniu.qiniu_data.dede_url') . 'makehtml_list_action.php?typeid=' . $typeId . '&maxpagesize=50&upnext=1';
        //dd($url);
        $rest = $this->dedeLogin(config('qiniu.qiniu_data.dede_url'). 'login.php', config('qiniu.qiniu_data.dede_user'), config('qiniu.qiniu_data.dede_pwd'));
        if($rest) {
            $this->curl->add()
                ->opt_targetURL($url)
                ->opt_sendHeader('cookie', $this->cookie)
                ->done('get');
            $this->curl->run();
            $content = $this->curl->getAll();
            if (mb_strpos($content['body'], '栏目列表更新', 0, 'utf-8') !== false) {
                $this->info("{$typeId}  lanmu list make success !");
            } else {
                $this->error("{$typeId}  lanmu list make fail !");
            }
        }
    }

    /**
     * 生成首页
     */
    public function makeIndex()
    {
        $url = config('qiniu.qiniu_data.dede_url') . 'makehtml_homepage.php';
        $rest = $this->dedeLogin(config('qiniu.qiniu_data.dede_url'). 'login.php', config('qiniu.qiniu_data.dede_user'), config('qiniu.qiniu_data.dede_pwd'));

        if($rest) {
            $this->curl->add()
                ->opt_targetURL($url)
                ->opt_sendHeader('cookie', $this->cookie)
                ->opt_sendPost('dopost', 'make')
                ->opt_sendPost('templet', 'default/index.htm')
                ->opt_sendPost('position', '../index.html')
                ->opt_sendPost('saveset', 1)
                ->opt_sendPost('showmod', 1)
                ->opt_sendPost('Submit', '更新主页HTML')
                ->done('post');
            $this->curl->run();
            $content = $this->curl->getAll();

            if (mb_strpos($content['body'], '成功更新主页', 0, 'utf-8') !== false) {
                $this->info("index make success !");
            } else {
                $this->error("index make fail !");
            }
        }
    }


    /**
     * 将更新的数据替换到dede后台
     */
    public function dedeDownLinkUpdate($typeId)
    {
        global $isUpdate;
        $isUpdate = false;
        //采集保存的数据库和表
        $dbName = config('qiniu.qiniu_data.db_name');
        $tableName = config('qiniu.qiniu_data.table_name');

        $dedeDownLinkUpdateUrl = config('qiniu.qiniu_data.dede_url') . 'myplus/down_link_update.php';
        $isNoDownLinks = DB::connection($dbName)->table($tableName)->where('typeid', $typeId)->where('is_update', -1)->get();
        $tot = count($isNoDownLinks);

        foreach ($isNoDownLinks as $key => $value) {
            $this->info("{$key}/{$tot} id is {$value->id}");
            //echo $value->down_link."\n";
            //先登录
            $rest = $this->dedeLogin(config('qiniu.qiniu_data.dede_url'). 'login.php', config('qiniu.qiniu_data.dede_user'), config('qiniu.qiniu_data.dede_pwd'));

            if ($rest) {
                $this->curl->add()
                    ->opt_targetURL($dedeDownLinkUpdateUrl)
                    ->opt_sendHeader('Cookie', $this->cookie)
                    ->opt_sendPost('typeid', $value->typeid)
                    ->opt_sendPost('title', $value->title)
                    ->opt_sendPost('down_link', $value->down_link)
                    ->done('post');
                $this->curl->run();
                $content = $this->curl->getAll();
                $body = explode("\r\n\r\n", $content['body'], 2);
                if (isset($body[1]) && $body[1] == 'update ok') {
                    $isUpdate = true;
                    //更新数据库is_update
                    $this->info("dede down_link update {$value->title} update ok !");
                } else {
                    //没有更新成功,也将is_update更新为0
                    $this->error("dede down_link update {$value->title} update fail !");
                }
                DB::connection($dbName)->table($tableName)->where('id', $value->id)->update(['is_update' => 0]);
            } else {
                $this->error("dede down_link update login fail !");
            }
        }
        $this->info("dede down_link update end !");
    }
}
