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
    protected $signature = 'send:dedea67post {channel_id}{typeid}{aid?}';

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
        //
        $dede_data = array(
            'channelid' => $this->channelId,
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
        $take = 10;
        $this->channelId = $this->argument('channel_id');
        $this->typeId = $this->argument('typeid');
        //取出最大的id加1
        $maxId = DB::connection('dedea67')->table('gather_dedea67')->where('typeid', $this->typeId)->max('id');
        $maxId = empty($this->argument('aid')) ? $maxId +1 : $this->argument('aid');
//        dd($minId);

        do {
            //提交数据
            $archives = DB::connection('dedea67')->table('gather_dedea67')->where('id', '<', $maxId)->where('typeid', $this->typeId)->where('is_post','-1')->orderBy('id','desc')->take($take)->get();
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
                    'channelid' => 17,
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
                    'director' => $value->director,
                    'actors' => $value->actors,
                    'myear' => $value->myear,
                    'types' => explode(',', $value->types),
//                    'types' => $value->types,
                    'down_link' => $value->down_link,
                    'grade' => $value->grade,
                    'episode_nums' => $value->episode_nums,
                ];
//                dd($rel_data);
                $data = array_merge($dede_data, $rel_data);
//                dd($data);
                $rest = $this->getCurl($addUrl, 'post', $data);
//                dd($rest);
                if (stripos($rest, '成功发布文') !== false) {
                    //成功提交后更新is_post
                    DB::connection('dedea67')->table('gather_dedea67')->where('id', $value->id)->update(['is_post' => 0]);
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
