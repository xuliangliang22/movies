<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/2 0002
 * Time: 下午 4:46
 */
namespace App\Console\Commands\Mytraits;

trait DedeLogin
{
    /**
     * 首先登录动态图后台
     */
    public function dedeLogin($loginUrl, $userName, $passWord)
    {
        //保存cookie
        $is_login = false;
        $cookiePath = public_path() . DIRECTORY_SEPARATOR . 'cookie_dede';
        if(!is_dir($cookiePath)){
            mkdir($cookiePath,0755,true);
        }
        $cookieFile = $cookiePath . DIRECTORY_SEPARATOR . md5($loginUrl . $userName . $passWord) . '.txt';

        if (file_exists($cookieFile) && time()-filemtime($cookieFile) < 28800) {
            $this->cookie = file_get_contents($cookieFile);
            return true;
        }

        // 获取 PHPSEESION
        $headerStr = $this->getCurl($loginUrl);
        $this->cookie = $this->parseCookie($headerStr);

        // 获取登录COOKIE,这里dede需要修改
        $loginData = ['userid' => $userName, 'pwd' => $passWord, 'gotopage' => '', 'dopost' => 'login', 'sml' => '', 'adminstyle' => 'newdedecms'];

        $headerStr = $this->getCurl($loginUrl, 'post', $loginData);

        if (strpos($headerStr, '成功登录') !== false) {
            //将数据提交到后台
            $this->cookie .= $this->parseCookie($headerStr);
            $is_login = true;
            file_put_contents($cookieFile, $this->cookie);
        }
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






