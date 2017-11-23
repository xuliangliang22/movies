<?php

namespace App\Console\Commands\Send;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Console\Commands\Mytraits\Common;
use App\Console\Commands\Mytraits\DedeLogin;

class Dedea67Post extends Command
{
    use Common;
    use DedeLogin;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:dedea67post {channel_id}{typeid}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '将采集到的数据提交到动态图后台';

    /**
     * 后台地址
     */
//    protected $dedeUrl = 'http://www.dongtaitu888.com/dongtaitu123/';
    protected $dedeUrl;

    /**
     * 后台用户名
     */
    protected $dedeUser;

    /**
     * 后台密码
     */
//    protected $dedePwd = 'ZWL19880921';
    protected $dedePwd;

    protected $typeId;
    //模型id
    protected $channelId;

    /**
     * cookie
     */
    protected $cookie;


    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->initBegin();
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
        $dede_data = array(
            'channelid' => 1,
            'cid' => 58,
            'dopost' => 'save',
            'title' => 'bbb',
            'shorttitle' => '',
            'redirecturl' => '',
            'tags' => '',
            'weight' => 1,
            'picname' => '',
            'typeid' => 58,
            'typeid2' => '',
            'types' => [],
            'director' => 'bbb',
            'actors' => 'bbbb',
            'myear' => '2000',
            'down_link' => 'bbb',
            'body' => 'bbbbbbbb',
            'dede_addonfields' => 'types,checkbox;director,text;actors,text;myear,text;down_link,text;body,htmltext;episode_nums,text',
            'autolitpic' => 1,
            'notpost' => 0,
            'click' => 104,
            'source' => '',
            'writer' => '',
            'sortup' => 0,
            'color' => '',
            'arcrank' => 0,
            'ishtml' => 1,
            'pubdate' => '2017-07-04 15:07:38',
            'money' => 0,
            'keywords]' => '',
            'autokey' => 1,
            'description' => '',
            'filename' => '',
            'imageField_x' => 28,
            'imageField_y' => 5,

        );

        $loginUrl = $this->dedeUrl . 'login.php';
        $addUrl = $this->dedeUrl . 'archives_add.php';
        $this->channelId = $this->argument('channel_id');
        $this->typeId = $this->argument('typeid');

        //取出最大的id加1
        $maxId = DB::connection($this->dbName)->table($this->tableName)->where('typeid', $this->typeId)->max('id');
        $maxId = $maxId +1;
        $take = 10;
        $message = null;
        $notAutoLitpic = config('qiniu.qiniu_data.dede_notautolitpic');

        do {
            //提交数据
            $archives = DB::connection($this->dbName)->table($this->tableName)->where('id', '<', $maxId)->where('typeid', $this->typeId)->where('is_post','-1')->orderBy('id','desc')->take($take)->get();
            $tot = count($archives);
            $rel_data = null;
            foreach ($archives as $key => $value) {
                $maxId = $value->id;
                $message = date('Y-m-d H:i:s')." {$key}/{$tot} -- typeid is {$value->typeid} aid is {$value->id}".PHP_EOL;
                $this->info($message);

                //判断是否登录
                if (!$this->dedeLogin($loginUrl, $this->dedeUser, $this->dedePwd)) {
                    $message = '登录失败!'.PHP_EOL;
                    $this->error($message);
                    //保存日志
                    if($this->isCommandLogs === true){
                        file_put_contents($this->commandLogsFile,$message,FILE_APPEND);
                    }
                    exit;
                }

                //提交数据
                $rel_data = [
                    'channelid' => $this->channelId,
                    'title' => $value->title,
                    'picname' => $value->litpic,
                    'weight' => mt_rand(1, 100),
                    'click' => mt_rand(1000, 9999),
//                    'typeid' => 58,
                    'typeid' => $value->typeid,
                    'cid' => $value->typeid,
                    'body' => $value->body,
                    'pubdate' => date('Y-m-d H:i:s'),
                    'arcrank' => 0,
                    'ishtml' => 0,
                    'director' => $value->director,
                    'actors' => $value->actors,
                    'myear' => $value->myear,
                    'types' => explode(',', $value->types),
//                    'types' => $value->types,
                    'down_link' => $value->down_link,
                    'grade' => $value->grade,
                    'episode_nums' => $value->episode_nums,
                ];
                if(in_array($value->typeid,$notAutoLitpic)){
                    $rel_data['autolitpic'] = 0;
                }
                $data = array_merge($dede_data, $rel_data);
//               dd($data);
                $rest = $this->getCurl($addUrl, 'post', $data);
                if (stripos($rest, '成功发布文') !== false) {
                    //成功提交后更新is_post
                    //更新状态文件
                    file_put_contents($this->dedeSendStatusFile,'is_send = 1');

                    $rest = DB::connection($this->dbName)->table($this->tableName)->where('id', $value->id)->update(['is_post' => 0]);
                    if($rest){
                        $message .= "dede post {$value->typeid} aid {$value->id} update gather tabel is_post=0 success !!".PHP_EOL;
                        $this->info($message);
                    }else{
                        $message .= "dede post {$value->typeid} adi {$value->id} update gather tabel is_post=0 fail !!".PHP_EOL;
                        $this->error($message);
                    }
                } else {
                    $message .= "dede post {$value->typeid} aid {$value->id} fail !!".PHP_EOL;
                    $this->error($message);
                }
                //保存日志
                if($this->isCommandLogs === true){
                    file_put_contents($this->commandLogsFile,$message,FILE_APPEND);
                }
            }
        } while ($tot > 0);
        $message = 'dede post archive end'.PHP_EOL;
        //保存日志
        if($this->isCommandLogs === true){
            file_put_contents($this->commandLogsFile,$message,FILE_APPEND);
        }
    }
}
