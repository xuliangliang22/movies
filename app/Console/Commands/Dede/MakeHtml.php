<?php

namespace App\Console\Commands\Dede;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Console\Commands\Mytraits\Common;
use App\Console\Commands\Mytraits\DedeLogin;

class MakeHtml extends Command
{
    use Common;
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

    public $cookie;


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
        $type = $this->argument('type');
        $typeid = $this->argument('typeid');
        if ($type == 'list') {
            if ($typeid === null) {
                $this->error('生成栏目内容必须输入typeid!');
                exit;
            }
            $this->makeLanmu($typeid);
        } elseif ($type == 'index') {
            $this->makeIndex();
        } elseif ($type == 'update') {
            //update电视剧更新集数的时候使用
            if ($typeid === null) {
                $this->error('有更新数据,但你没有传入typeid,不能正确执行!');
                exit;
            }
            $this->dedeDownLinkUpdate($typeid);
        } elseif ($type == 'arc') {
            if ($typeid === null) {
                $this->error('生成内容页必须输入typeid!');
                exit;
            }
            $startaid = $this->argument('start_aid');
            $endaid = $this->argument('end_aid');
            $this->makeArc($typeid, $startaid, $endaid);
        }
    }

    /**
     * dede生成栏目页
     */
    public function makeLanmu($typeId,$mkpage = 1,$url = '')
    {
        //first http://localhost:8127/wldy/makehtml_list_action.php?typeid=15&maxpagesize=50&upnext=1
        //second http://localhost:8127/wldy/makehtml_list_action.php?gotype=&uppage=0&mkpage=51&maxpagesize=50&typeid=15&pageno=0&isremote=0&serviterm=
        //third http://localhost:8127/wldy/makehtml_list_action.php?gotype=&uppage=0&mkpage=101&maxpagesize=50&typeid=15&pageno=0&isremote=0&serviterm=
        if($mkpage == 1) {
            $url = config('qiniu.qiniu_data.dede_url') . 'makehtml_list_action.php?typeid=' . $typeId . '&maxpagesize=50&upnext=1';
        }
//        dd($url);
        //登录
        $rest = $this->dedeLogin(config('qiniu.qiniu_data.dede_url') . 'login.php', config('qiniu.qiniu_data.dede_user'), config('qiniu.qiniu_data.dede_pwd'));
        if ($rest) {
            $this->curl->add()
                ->opt_targetURL($url)
                ->opt_sendHeader('cookie', $this->cookie)
                ->done('get');
            $this->curl->run();
            $content = $this->curl->get();
            if (mb_strpos($content, '完成所有栏目列表更新', 0, 'utf-8') !== false) {
                //logs
                if ($this->isCommandLogs === true) {
                    $command = "{$typeId}  lanmu list make success ! \n";
                    file_put_contents($this->commandLogsFile, $command, FILE_APPEND);
                }
                $this->info("{$typeId}  lanmu list make success !");
            } else {
                //logs
                if(mb_strpos($content,'继续进行操作',0,'utf-8') !== false) {
                    $mkpage = $mkpage + 50;
                    $url = config('qiniu.qiniu_data.dede_url').'makehtml_list_action.php?gotype=&uppage=0&mkpage='.$mkpage.'&maxpagesize=50&typeid='.$typeId.'&pageno=0&isremote=0&serviterm=';
                    if ($this->isCommandLogs === true) {
                        $command = "{$typeId} agin {$mkpage} list make ! \n";
                        file_put_contents($this->commandLogsFile, $command, FILE_APPEND);
                    }
                    $this->makeLanmu($typeId, $mkpage,$url);
                }else {
                    if ($this->isCommandLogs === true) {
                        $command = "{$typeId}  lanmu list make fail ! \n";
                        file_put_contents($this->commandLogsFile, $command, FILE_APPEND);
                    }
                    $this->error("{$typeId}  lanmu list make fail !");
                }
            }
        }
    }

    /**
     * 生成首页
     */
    public function makeIndex()
    {
        $url = config('qiniu.qiniu_data.dede_url') . 'makehtml_homepage.php';
        $rest = $this->dedeLogin(config('qiniu.qiniu_data.dede_url') . 'login.php', config('qiniu.qiniu_data.dede_user'), config('qiniu.qiniu_data.dede_pwd'));

        if ($rest) {
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
     * 更新内容页
     * @param $aid
     */
    public function makeArc($typeid, $startaid, $endaid)
    {
        $num = $endaid - $startaid + 1;
        $url = config('qiniu.qiniu_data.dede_url') . 'makehtml_archives_action.php?typeid=' . $typeid . '&startid=' . $startaid . '&endid=' . $endaid . '&pagesize=' . $num;

        $rest = $this->dedeLogin(config('qiniu.qiniu_data.dede_url') . 'login.php', config('qiniu.qiniu_data.dede_user'), config('qiniu.qiniu_data.dede_pwd'));
        if ($rest) {
            $this->curl->add()
                ->opt_targetURL($url)
                ->opt_sendHeader('cookie', $this->cookie)
                ->done('get');
            $this->curl->run();
            $content = $this->curl->getAll();
            if (mb_strpos($content['body'], '完成创建文件', 0, 'utf-8') !== false) {
                $this->info("{$startaid} -- {$endaid} arc make success !");
            } else {
                $this->error("{$startaid} -- {$endaid} arc make fail !");
            }
        }

    }


    /**
     * 将更新的数据替换到dede后台,主要是电视剧
     */
    public function dedeDownLinkUpdate($typeId)
    {
        $dedeDownLinkUpdateUrl = config('qiniu.qiniu_data.dede_url') . 'myplus/down_link_update.php';
        $isNoDownLinks = DB::connection($this->dbName)->table($this->tableName)->select('id','typeid','title','down_link')->where('typeid', $typeId)->where('is_update', -1)->get();
        $tot = count($isNoDownLinks);

        foreach ($isNoDownLinks as $key => $value) {
            $message = date('Y-m-d H:i:s')." {$key}/{$tot} aid is {$value->id}".PHP_EOL;
            $this->info($message);
            //先登录
            $rest = $this->dedeLogin(config('qiniu.qiniu_data.dede_url') . 'login.php', config('qiniu.qiniu_data.dede_user'), config('qiniu.qiniu_data.dede_pwd'));

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
//                print_r($content);
                $body = explode("\r\n\r\n",$content['body'],2);
                if(isset($body[1]) === false){
                    $body[1] = $content['body'];
                }
                if (stripos($body[1], 'update ok') !== false) {
                    //更新dede提交的状态文件
                    file_put_contents($this->dedeSendStatusFile,'is_update = 1');

                    //自动更新内容页,得到更新的文章id
                    if (preg_match('/\d+/', $body[1], $matchs)) {
                        $aid = $matchs[0];
                        $this->makeArc($typeId, $aid, $aid);
                    }
                    //更新数据库is_update
                    $message .= "dede down_link update aid {$value->id} update ok !";
                    $this->info($message);
                } else {
                    //没有更新成功,也将is_update更新为0
                    $message .= "dede down_link update aid {$value->id} update fail !";
                    $this->error($message);
                }
                //保存日志
                if($this->isCommandLogs === true){
                    file_put_contents($this->commandLogsFile,$message,FILE_APPEND);
                }
                DB::connection($this->dbName)->table($this->tableName)->where('id', $value->id)->update(['is_update' => 0]);
            } else {
                $this->error("dede down_link update login fail !");
            }
        }
        $message = "dede down_link update end !";
        $this->info($message);
        //保存日志
        if($this->isCommandLogs === true){
            file_put_contents($this->commandLogsFile,$message,FILE_APPEND);
        }
    }
}
