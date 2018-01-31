<?php

namespace App\Console\Commands\Send;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Console\Commands\Mytraits\DedeLogin;
use App\Console\Commands\Mytraits\Common;

class DedeNewPost extends Command
{
    use Common;
    use DedeLogin;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:dedenewpost{channel_id}{typeid}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '将采集到的数据提交到dede后台->channelid = 1';

    /**
     * 后台地址
     */
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
        //
        $dede_data = array(
            'channelid' => 1,
            'dopost' => 'save',
            'title' => 'source title',
            'shorttitle' => '',
            'redirecturl' => '',
            'tags' => '',
            'weight' => 5,
            'picname' => '',
            'source' => '',
            'writer' => 'admin',
            'typeid' => 2,
            'typeid2' => '',
            'keywords' => '',
            'autokey' => 1,
            'description' => '',
            'dede_addonfields' => '',
            'sptype' => 'hand',
            'spsize' => 5,
            'body' => 'source body',
            'voteid' => '',
            'notpost' => 0,
            'click' => 52,
            'sortup' => 0,
            'color' => '',
            'arcrank' => 0,
            'money' => 0,
            'pubdate' => '2017-02-15 21:30:50',
            'ishtml' => 1,
            'filename' => '',
            'templet' => '',
            'imageField_x' => 45,
            'imageField_y' => 9,
        );

        $loginUrl = $this->dedeUrl . 'login.php';
        $addUrl = $this->dedeUrl . 'article_add.php';

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
                    $this->error('登录失败!');
                    exit;
                }
                //提交数据
                $data = [
                    'channelid' => $this->channelId,
                    'title' => $value->title,
                    'picname' => $value->litpic,
                    'weight' => mt_rand(1, 100),
                    'click' => mt_rand(1000, 9999),
                    'typeid' => $value->typeid,
                    'cid' => $value->typeid,
                    'body' => $value->body,
                    'pubdate' => date('Y-m-d H:i:s'),
                    'arcrank' => 0,
                    'ishtml' => -1,
                    //文章描述
                    'description'=>$value->down_link,
                ];
                $data = array_merge($dede_data, $data);
                $rest = $this->getCurl($addUrl, 'post', $data);
                if (stripos($rest, '成功发布文') !== false) {
                    DB::table('ca_gather')->where('id', $value->id)->update(['is_post' => 0]);
                    $this->info("{$key}/{$tot}news dede post success");
                } else {
                    $this->error("{$key}/{$tot}news dede post fail");
                }
            }
            $offset+=$limit;
        }while($tot > 0);
        $this->info("news dede post end");

    }
}
