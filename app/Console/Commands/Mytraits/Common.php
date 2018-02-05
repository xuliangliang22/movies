<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/10/19 0019
 * Time: 上午 11:08
 */
namespace App\Console\Commands\Mytraits;

use Illuminate\Support\Facades\DB;
use QL\QueryList;
use zgldh\QiniuStorage\QiniuStorage;

trait Common
{
    public $curl = null;

//    protected $wwwRoot = 'D:/laragon/www/ca2722';
    protected $wwwRoot = '/home/www/ca2722';
    protected $imagePath = '/uploads/';



    //dede
    protected $dedeUrl;
    protected $dedeUser;
    protected $dedePwd;

    /**
     * 初始化
     */
    protected function initBegin()
    {
        if ($this->curl === null) {
            $path = app_path('Console/Commands/curl/curl.php');
            require_once $path;
            $this->curl = new \curl();
        }
        $this->imagePath = $this->imagePath . date('ymd');
        $this->dedeUrl = env('DEDE_ADMIN_URL');
        $this->dedeUser = env('DEDE_ADMIN_USER');
        $this->dedePwd = env('DEDE_ADMIN_PWD');
    }

    /**
     * 上传图片,线上七牛空间
     */
    protected function imgUpload($url)
    {
        $file = null;
        $disk = QiniuStorage::disk('qiniu');

        //将网络图片上传到七牛云
        if (preg_match('/^https(.*?)/is', $url)) {
            $sslt = 2;
        } else {
            $sslt = 1;
        }
        $this->curl->add()->opt_targetURL($url, $sslt)->done();
        $this->curl->run();
        $data = $this->curl->getAll();
        $this->curl->free();

        if ($data['info']['http_code'] == '200' && stripos($data['info']['content_type'], 'image') !== false && (integer)$data['info']['size_download'] > 1024) {
            //获得文件后缀
            $ext = substr($data['info']['content_type'], stripos($data['info']['content_type'], '/') + 1);
            if ($ext == 'jpeg') {
                $ext = 'jpg';
            }
            $file = $this->savePath . '/' . md5($url) . '.' . $ext;

            //如果不存在则才会上传
            if (!$disk->exists($file)) {
                $content = $data['body'];
                $rest = $disk->put($file, $content);
                //上传成功去判断大小
                if ($rest) {
                    $size = $disk->size($file);
                    if ($size < 1024) {
                        //删除
                        $disk->delete($file);
                        $file = null;
                    }
                } else {
                    //上传没有成功
                    $file = null;
                }
            }
        }
        return $file;
    }


    /**
     * 在文档开头，改变属性，指定文件保存位置
     * 图片保存到本地
     * @param $wwwRoot
     * @param $imagePath
     */
    protected function bodypicDownload()
    {
        $offset = 0;
        $limit = 1000;
        do {
            $arts = DB::table('ca_gather')->select('id', 'title', 'body')->where('is_body', -1)->skip($offset)->take($limit)->get();
            $tot = count($arts);
            foreach ($arts as $key => $value) {
                if(empty($value->body) === false && stripos($value->body,'img') !== false) {
                    $html = QueryList::run('DImage', [
                        'content' => $value->body,
                        'www_root' => $this->wwwRoot,
                        'base_url' => '',
                        'attr' => 'src',
                        'image_path' => $this->imagePath,
                        'callback' => function ($o) use ($value) {
                            $o->attr('alt', $value->title);
                        }
                    ]);
                }else{
                    $html = $value->body;
                }
                //更新到数据库
                $issave = DB::table('ca_gather')->where('id', $value->id)->update([
                    'body' => trim($html),
                    'is_body' => 0
                ]);
                if ($issave) {
                    $this->info("{$key}/{$tot} bodypic download success");
                } else {
                    $this->error("{$key}/{$tot} bodypic download fail");
                }
            }
            $offset += $limit;
        } while ($tot > 0);
        $this->info("bodypic download end");
    }


    protected function litpicDownload()
    {
        $offset = 0;
        $limit = 1000;
        $savePath = $this->wwwRoot . $this->imagePath;
        if (is_dir($savePath) === false) {
            mkdir($savePath, 0755, true);
        }
        do {
            $arts = DB::table('ca_gather')->select('id', 'litpic')->where('is_litpic', -1)->skip($offset)->take($limit)->get();
            $tot = count($arts);
            foreach ($arts as $key => $value) {
                $litpic = '';
                $pathinfo = parse_url($value->litpic, PHP_URL_PATH);
                $ext = pathinfo($pathinfo, PATHINFO_EXTENSION);
                if (!$ext) {
                    $ext = 'jpg';
                }
                $fileName = $savePath . '/' . md5($value->litpic . mt_rand(1000, 9999)) . '.' . $ext;
                $fp = fopen($fileName, 'wb');
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $value->litpic);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HEADER, false);
                curl_setopt($ch, CURLOPT_NOBODY, false);
                curl_setopt($ch, CURLOPT_FILE, $fp);
                curl_exec($ch);
                $info = curl_getinfo($ch);
                curl_close($ch);
                if ($info['http_code'] == '200' && $info['size_download'] > 1024 && fclose($fp)) {
                    //更新数据库
                    $litpic = substr($fileName, strlen($this->wwwRoot));
                }
                DB::table('ca_gather')->where('id', $value->id)->update([
                    'litpic' => $litpic,
                    'is_litpic' => 0,
                ]);
            }
            $offset += $limit;
        } while ($tot > 0);
        $this->info('litpic download end ');
    }


    /**
     * dede影视模型提交
     */
    protected function dedemoviePost()
    {
        $loginUrl = $this->dedeUrl . 'login.php';
        $addUrl = $this->dedeUrl . 'archives_add.php';
        $offset = 0;
        $limit = 1000;
        do {
            $arts = DB::table('ca_gather')->where('typeid', $this->typeId)->where('is_post', -1)->where('is_con',0)->skip($offset)->take($limit)->get();
            $tot = count($arts);
            foreach ($arts as $key => $value) {
                //判断是否登录
                if (!$this->dedeLogin($loginUrl, $this->dedeUser, $this->dedePwd)) {
                    $this->error("sorry,{$loginUrl}-{$this->dedeUser}-{$this->dedePwd} login fail");
                    exit;
                }
                //提交数据
                $data = [
                    'channelid' => $this->channelId,
                    'cid' => $value->typeid,
                    'dopost' => 'save',
                    'title' => $value->title,
                    'shorttitle' => '',
                    'redirecturl' => '',
                    'tags' => '',
                    'weight' => mt_rand(1, 100),
                    'litipic' => '',
                    'picname' => isset($value->litpic) ? $value->litpic : '',
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
                    'remote' => 0,
                    'autolitpic' => 0,
                ];

                $rest = $this->getCurl($addUrl, 'post', $data);
                if (stripos($rest, '成功发布文') !== false) {
                    DB::table('ca_gather')->where('id', $value->id)->update(['is_post' => 0]);
                }
            }
            $offset += $limit;
        } while ($tot > 0);
        $this->info('congratulation,dede movie post end');
    }


    /**
     * dede文章模型数据提交
     */
    protected function dedeartPost()
    {
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
            'ishtml' => 0,
            'filename' => '',
            'templet' => '',
            'imageField_x' => 45,
            'imageField_y' => 9,
            'remote' => 0,
            'autolitpic' => 0,
        );

        $loginUrl = $this->dedeUrl . 'login.php';
        $addUrl = $this->dedeUrl . 'article_add.php';

        $offset = 0;
        $limit = 1000;
        do {
            $arts = DB::table('ca_gather')->where('typeid', $this->typeId)->where('is_post', -1)->where('is_con',0)->skip($offset)->take($limit)->get();
            $tot = count($arts);
            foreach ($arts as $key => $value) {
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
//                    'ishtml' => -1,
                    //文章描述
                    'description' => $value->down_link,
                ];
                $data = array_merge($dede_data, $data);
                $rest = $this->getCurl($addUrl, 'post', $data);
                if (stripos($rest, '成功发布文') !== false) {
                    DB::table('ca_gather')->where('id', $value->id)->update(['is_post' => 0]);
                }
            }
            $offset += $limit;
        } while ($tot > 0);
        $this->info("congratulation,news dede post end");
    }


    /**
     * 将更新的数据替换到dede后台,主要是电视剧
     */
    public function dedeupdatePost()
    {
        $loginUrl = $this->dedeUrl . 'login.php';
        $dedeDownLinkUpdateUrl = $this->dedeUrl . 'myplus/down_link_update.php';

        $isNoDownLinks = DB::table('ca_gather')->select('id', 'typeid', 'title', 'down_link')->where('typeid', $this->typeId)->where('is_update', -1)->get();
        $tot = count($isNoDownLinks);

        foreach ($isNoDownLinks as $key => $value) {
            $message = date('Y-m-d H:i:s') . " {$key}/{$tot} aid is {$value->id}" . PHP_EOL;
            $this->info($message);
            //判断是否登录
            if (!$this->dedeLogin($loginUrl, $this->dedeUser, $this->dedePwd)) {
                $this->error("sorry,{$loginUrl}-{$this->dedeUser}-{$this->dedePwd} login fail");
                exit;
            }

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
            $body = explode("\r\n\r\n", $content['body'], 2);
            if (isset($body[1]) === false) {
                $body[1] = $content['body'];
            }
            if (stripos($body[1], 'update ok') !== false) {
                //更新数据库is_update
                DB::table('ca_gather')->where('id', $value->id)->update([
                    'is_update' => 0
                ]);
                $message .= "dede down_link update aid {$value->id} update ok !" . PHP_EOL;
                $this->info($message);
            } else {
                //没有更新成功,也将is_update更新为0
                $message .= "dede down_link update aid {$value->id} update fail !" . PHP_EOL;
                $this->error($message);
            }
        }
        $message = "dede down_link update end !" . PHP_EOL;
        $this->info($message);
    }


}















