<?php

namespace App\Console\Commands\Send;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class Dedea67Post extends Command
{
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
        $minId = empty($this->argument('aid')) ? 0 : $this->argument('aid');
//        dd($minId);

        do {
            //提交数据
            $archives = DB::connection('dedea67')->table('gather_dedea67')->where('id', '>', $minId)->where('typeid', $this->typeId)->where('is_post','-1')->orderBy('id')->take($take)->get();
//            $archives = DB::table('gather_dedea67')->where('id', 1)->get();
            $tot = count($archives);
//            dd($tot);
            foreach ($archives as $key => $value) {
                $minId = $value->id;
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


    /**
     * 首先登录动态图后台
     */
    public function dedeLogin($loginUrl, $userName, $passWord)
    {
        //保存cookie
        $is_login = false;
        $cookieFile = public_path() . DIRECTORY_SEPARATOR . 'cookie_dede' . DIRECTORY_SEPARATOR . md5($loginUrl . $userName . $passWord) . '.txt';

        if (file_exists($cookieFile) && time()-filemtime($cookieFile) < 28800) {
            $this->cookie = file_get_contents($cookieFile);
//            dd($this->cookie);
            return true;
        }
//        dd($this->cookie);

        // 获取 PHPSEESION
        $headerStr = $this->getCurl($loginUrl);
        $this->cookie = $this->parseCookie($headerStr);

        // 获取登录COOKIE,这里dede需要修改
        $loginData = ['userid' => $userName, 'pwd' => $passWord, 'gotopage' => '', 'dopost' => 'login', 'sml' => '', 'adminstyle' => 'newdedecms'];

        $headerStr = $this->getCurl($loginUrl, 'post', $loginData);
//        dd($headerStr);

        if (strpos($headerStr, '成功登录') !== false) {
            //将数据提交到后台
            $this->info('login ok');
            $this->cookie .= $this->parseCookie($headerStr);
            $is_login = true;
        }
        file_put_contents($cookieFile, $this->cookie);
        return $is_login;
    }

    /*
     * 提交curl
     *
     */
    public function getCurl($url, $method = 'get', $data = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, false);
        if ($method == 'post') {
            $data = http_build_query($data);
//            dd($data);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_COOKIESESSION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'User-Agent:Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.14 Safari/537.36',
                'Cookie:' . $this->cookie,
            ]);
        }

        $info = curl_exec($ch);
//        dd($info);
        curl_close($ch);
        return $info;
    }

    /**
     *  解析cookie
     */
    public function parseCookie($headStr)
    {
        $restCookie = '';

        //将头部与响应体分离
        $cookie = explode("<html", $headStr);
        //解析cookie
        $cookie = explode("\r\n", $cookie[0]);
        $cookie = array_filter($cookie);
        foreach ($cookie as $item) {
            if (preg_match('/^HTTP\/1.1/', $item) || stripos($item, ':') === false) {
                continue;
            }
            list($key, $value) = explode(':', $item);
            if (trim($key) == 'Set-Cookie') {
//                $cvalue = trim(strstr($value,';',true));
                $cvalue = strstr($value, ';', true);
                $restCookie .= $cvalue . ';';
            }
        }
        return $restCookie;
    }


}
