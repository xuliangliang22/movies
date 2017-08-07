<?php
/**
 * User: Administrator
 * Date: 2017/7/3 0003
 * Time: 下午 9:12
 */
namespace App\Console\Commands\Mytraits;


trait MultiImgDown
{
    public $wwwRoot;
    public $uploadDir;
    public $uploadHost;

    public function PicInit($qiniuDir)
    {
        $qiniuDir = trim($qiniuDir,'/').'/';
        //本地保存目录
        $this->uploadDir = date('ymd');
        $this->uploadHost = 'http://ojnhba94s.bkt.clouddn.com/' . $qiniuDir ;
        //网站标志
//        $this->netFlag = 'nihan';

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            //根路径
            $this->wwwRoot = 'D:/images/dedea67/';
        } else {
            $this->wwwRoot = '/data/oss/game888';
        }

    }

    public function singleDownPic($imgurl, $savepath)
    {
        $parseUrl = parse_url($imgurl);
        $ext = $this->getExt($imgurl);
        if ($ext === false) {
            return false;
        }

        $filname = rtrim($savepath, '/') . '/' . md5($imgurl) . '.' . $ext;
        $fp = fopen($filname, 'wb');
        $ch = $this->getCurlObject($imgurl, $fp,[],['Host:'.$parseUrl['host']]);
        curl_exec($ch);
        fclose($fp);
        curl_close($ch);
        return $filname;
    }


    public function getExt($url)
    {
        $parseUrl = parse_url($url);
        $pathinfo = pathinfo($parseUrl['path']);

        $ext = isset($pathinfo['extension']) ? $pathinfo['extension'] : 'jpg';
        return $ext;
    }


    //多线程下载图片
    public function multiDownPic($imgarr, $savepath)
    {
        //返回生成的文件名
        $mch = curl_multi_init();
        $ch = array();
        $fp = array();
        $file_savepath = array();

        foreach ($imgarr as $key => $url) {
            $parseUrl = parse_url($url);
            $ext = $this->getExt($url);
            if ($ext === false) {
                $file_savepath[$key] = '';
                continue;
            }
            $file_savepath[$key] = rtrim($savepath, '/') . '/' . md5($url) . '.' . $ext;

            $fp[$key] = fopen($file_savepath[$key], 'wb');
            $ch[$key] = $this->getCurlObject($url, $fp[$key], [], ['Host:' . $parseUrl['host']]);
            curl_multi_add_handle($mch, $ch[$key]);
        }

        $active = null;
        // 执行批处理句柄
        do {
            $mrc = curl_multi_exec($mch, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);

        while ($active && $mrc == CURLM_OK) {
            // add this line
            while (curl_multi_exec($mch, $active) === CURLM_CALL_MULTI_PERFORM) ;

            if (curl_multi_select($mch) != -1) {
                do {
                    $mrc = curl_multi_exec($mch, $active);
                } while ($mrc == CURLM_CALL_MULTI_PERFORM);
            }
        }

        //关闭多线程,$item=$ch
        foreach ($ch as $key => $item) {
            fclose($fp[$key]);
            curl_multi_remove_handle($mch, $item);
            curl_close($item);
        }
        curl_multi_close($mch);

//        dd($file_savepath);
        return $file_savepath;
    }

    /**
     * 多线程下载图片
     * @param $url
     * @param $fp
     * @param array $postdata
     * @param array $header
     * @return resource
     */
    public function getCurlObject($url, $fp, $postdata = array(), $header = array())
    {
        $option = array();
        $url = trim($url);
        $option[CURLOPT_URL] = $url;
        $option[CURLOPT_TIMEOUT] = 10;
        $option[CURLOPT_RETURNTRANSFER] = true;
        $option[CURLOPT_HEADER] = false;
        $option[CURLOPT_USERAGENT] = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/53.0.2763.0 Safari/537.36';
        $option[CURLOPT_NOBODY] = false;
        $option[CURLOPT_FILE] = $fp;


        if (empty($postdata) === false && is_array($postdata) === true) {
            $option[CURLOPT_POST] = true;
            $option[CURLOPT_POSTFIELDS] = http_build_query($postdata);
        }

        if (empty($header) === false && is_array($header) === true) {
            $option[CURLOPT_HTTPHEADER] = $header;
        }

        if (stripos($url, 'https') === 0) {
            $option[CURLOPT_SSL_VERIFYHOST] = 0;
            $option[CURLOPT_SSL_VERIFYPEER] = false;
        }

        $ch = curl_init();
        curl_setopt_array($ch, $option);
        return $ch;
    }
}