<?php

namespace App\Console\Commands\Send;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DongPost extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:dongpost';

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
    protected $dedeUrl = 'http://localhost:8121/dede/';

    /**
     * 后台用户名
     */
    protected $dedeUser = 'admin';

    /**
     * 后台密码
     */
//    protected $dedePwd = 'ZWL19880921';
    protected $dedePwd = 'admin';


    /**
     *网站来源
     */
    protected $netFlag = 'nihan';


    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
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
            'remote' => 1,
            'autolitpic' => 1,
            'needwatermark' => 1,
            'sptype' => 'hand',
            'spsize' => 5,
            'body' => 'source body',
            'voteid' => '',
            'notpost' => 0,
            'click' => 52,
            'sortup' => 0,
            'color' => '',
            'arcrank' => -1,
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

        do {
            //提交数据
            $archives = DB::table('dong_gather')->where('is_post', -1)->where('net_flag', $this->netFlag)->take($take)->get();
            $tot = count($archives);
//            dd($tot);
            foreach ($archives as $key => $value) {
                $this->info("{$key}/{$tot} -- typeid is {$value->typeid} aid is {$value->id}");

                //判断是否登录
                if (!$this->dedeLogin($loginUrl, $this->dedeUser, $this->dedePwd)) {
                    $this->error('登录失败!');
                    exit;
                }

                //提交数据
                $rel_data = [
                    'title' => $value->title,
                    'picname' => $value->litpic,
                    'weight' => mt_rand(1, 100),
                    'click' => mt_rand(1000, 9999),
                    'typeid' => 2,
//                    'typeid' => $value->typeid,
                    'body' => $value->body,
                    'pubdate' => date('Y-m-d H:i:s'),
                    'arcrank' => -1,
                ];
                $data = array_merge($dede_data, $rel_data);
                $rest = $this->getCurl($addUrl, 'post', $data);
                if (stripos($rest, '成功发布文章') !== false) {
                    DB::table('dong_gather')->where('id',$value->id)->update(['is_post'=>0]);
                    $this->info('dede post archive success');
                } else {
                    $this->info('dede post archive fail');
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
        if (is_file($cookieFile) === true && time() - filemtime($cookieFile) < 28800) {
            $this->cookie = file_get_contents($cookieFile);
            return true;
        }

        // 获取 PHPSEESION
        $headerStr = $this->getCurl($loginUrl);
        $this->cookie = $this->parseCookie($headerStr);
//        dd($this->cookie);

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
