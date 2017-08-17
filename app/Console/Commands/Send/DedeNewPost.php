<?php

namespace App\Console\Commands\Send;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Console\Commands\Mytraits\DedeLogin;

class DedeNewPost extends Command
{
    use DedeLogin;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:dedenewpost{db_name}{table_name}{channel_id}{typeid}{aid?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '将采集到的数据提交到dede后台->channelid = 1';

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
        global $isSend;
        $isSend = false;
        $dbName = $this->argument('db_name');
        $tableName = $this->argument('table_name');
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

        $take = 10;
        $this->channelId = $this->argument('channel_id');
        $this->typeId = $this->argument('typeid');
        //取出最大的id加1
        $maxId = DB::connection($dbName)->table($tableName)->where('typeid', $this->typeId)->max('id');
        $maxId = empty($this->argument('aid')) ? $maxId + 1 : $this->argument('aid');
//        dd($minId);

        do {
            //提交数据
            $archives = DB::connection($dbName)->table($tableName)->where('id', '<', $maxId)->where('typeid', $this->typeId)->where('is_post', '-1')->orderBy('id', 'desc')->take($take)->get();
            $tot = count($archives);
            foreach ($archives as $key => $value) {
                $maxId = $value->id;
                $this->info("{$key}/{$tot} -- typeid is {$value->typeid} aid is {$value->id}");

                //判断是否登录
                if (!$this->dedeLogin($loginUrl, $this->dedeUser, $this->dedePwd)) {
                    $this->error('登录失败!');
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
                    //文章描述
                    'description'=>$value->down_link,
                ];
//                dd($rel_data);
                $data = array_merge($dede_data, $rel_data);
                $rest = $this->getCurl($addUrl, 'post', $data);
                if (stripos($rest, '成功发布文') !== false) {
                    $isSend = true;
                    //成功提交后更新is_post
                    DB::connection($dbName)->table($tableName)->where('id', $value->id)->update(['is_post' => 0]);
                    $this->info('dede post archive success');
                } else {
                    $this->error('dede post archive fail');
                }
//                dd(22);
            }
        } while ($tot > 0);
        $this->info('dede post archive end');
    }

}
