<?php

namespace App\Console\Commands\Send;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Console\Commands\Mytraits\DedeLogin;

class Dedea67Post extends Command
{
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
        $this->info('【'.date('Y-m-d H:i:s').'】 welcome,send dede admin');
        $loginUrl = $this->dedeUrl . 'login.php';
        $addUrl = $this->dedeUrl . 'archives_add.php';
        $this->channelId = $this->argument('channel_id');
        $this->typeId = $this->argument('typeid');

        $offset = 0;
        $limit = 1000;
        do
        {
            $arts = DB::table('ca_gather')->where('typeid',$this->typeId)->where('is_post',-1)->skip($offset)->take($limit)->get();
            $tot = count($arts);
            foreach ($arts as $key=>$value){
                //判断是否登录
                if (!$this->dedeLogin($loginUrl, $this->dedeUser, $this->dedePwd)) {
                    $this->error("sorry,{$loginUrl}-{$this->dedeUser}-{$this->dedePwd} login fail");
                    exit;
                }
                //提交数据
                $data = [
                    'channelid' => $this->channelId,
                    'cid' => $value->typeid,
                    'dopost'=>'save',
                    'title' => $value->title,
                    'shorttitle' => '',
                    'redirecturl' => '',
                    'tags' => '',
                    'weight' => mt_rand(1, 100),
                    'picname' => '',
                    'litpic' => '',
                    'typeid' => $this->typeId,
                    'typeid2' => '',
                    'down_link' => $value->down_link,
                    'body' => $value->body,
                    'dede_addonfields' => 'down_link,text;body,htmltext',
                    'notpost' => 0,
                    'click' => mt_rand(1000, 9999),
                    'source' => '',
                    'writer' => '',
                    'sortup' => 0,
                    'color' => '',
                    'arcrank' => 0,
                    'ishtml' => 0,
                    'pubdate' => date('Y-m-d H:i:s'),
                    'money' => 0,
                    'keywords' => '',
                    'description' => '',
                    'filename' => '',
                    'mageField.x' => 36,
                    'mageField.y' => 18,
                ];

                $rest = $this->getCurl($addUrl, 'post', $data);
                if (stripos($rest, '成功发布文') !== false) {
                    DB::table('ca_gather')->where('id', $value->id)->update(['is_post' => 0]);
                    $this->info('congratulation,send to dede success');
                } else {
                    $this->error('sorry,send to dede success');
                }
            }
            $offset+=$limit;
        }while($tot > 0);
        $this->info('【'.date('Y-m-d H:i:s').'】 congratulation,send to dede end');
    }
}
