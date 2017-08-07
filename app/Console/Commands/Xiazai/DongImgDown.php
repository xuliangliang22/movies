<?php

namespace App\Console\Commands\Xiazai;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use QL\QueryList;

class DongImgDown extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     * @param img_type 代表采集图片分类，litpic与body两个值
     * php artisan xiazai:dongimgdown litpic
     * php artisan xiazai:dongimgdown body
     */
    protected $signature = 'xiazai:dongimgdown {img_type}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '图片下载';

    /**
     * 网站标志
     */
    protected $netFlag;

    /**
     * 文件保存的根目录
     * @var
     */
    protected $wwwRoot;

    /**
     * 文件保存的具体目录
     * @var
     */
    protected $uploadDir ;

    /**
     * 线上图片的链接前缀
     * @var
     */
    protected $uploadHost;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        //下面这三个是需要修改的
        $this->uploadDir = '/uploads/allimg/'.date('ymd');
        $this->uploadHost = 'http://t.dongtaitu888.com/dedea67';
        //网站标志
        $this->netFlag = 'nihan';

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->wwwRoot = 'D:/images/dongtaitu888';
        } else {
            $this->wwwRoot = '/data/oss/game888';
        }
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
        $imgType = $this->argument('img_type');

        switch ($imgType)
        {
            case 'litpic':
                $this->litpicImg();
                break;
            case 'body':
                $this->bodyImg();
                break;
        }
    }


    /**
     * 下载内容页的图片,并列新数据库
     */
    public function bodyImg()
    {
        do {
            $addonarchives = DB::table('dong_gather')->where('is_body', -1)->where('net_flag',$this->netFlag)->take(10)->get();

            $tot = count($addonarchives);
            foreach ($addonarchives as $key => $value) {

                $this->info("this is dong_img_down {$key}/{$tot} -- typeid is {$value->typeid} -- aid is {$value->id}");

                $savePath = rtrim($this->wwwRoot,'/').'/'.ltrim($this->uploadDir,'/').$value->typeid;
                if(!is_dir($savePath)){
                    mkdir($savePath,0777,true);
                }
                //数据为空,则删除这条记录
                if(empty($value->body) === true){
                    DB::table('dong_gather')->delete($value->id);
                   continue;
                }
                //得到所有图片链接
                $marest = preg_match_all('/<img\s+[^\>]*(src|SRC)\=(\"|\')([^\"\']+)(\"|\')/is',$value->body,$matchs);

                //数据不完整,则删除这条记录
                if($marest === 0 || isset($matchs[3]) === false || empty($matchs[3]) === true){
                    DB::table('dong_gather')->delete($value->id);
                    continue;
                }
//                $imgarr = $matchs[3];
                $imgarr = $this->multiDownPic($matchs[3],$savePath);
                if(empty($imgarr) === true){
                    continue;
                }

                $imgstr = '';
                foreach ($imgarr as $k=>$v){
                    $imgname = trim($this->uploadHost,'/').'/'.ltrim(str_replace($this->wwwRoot,'',$v),'/');
                    $imgstr .= '<p><img src="'.$imgname.'"/></p>';
                }
//                dd($imgstr);

//                //更新数据库
                $rest = DB::table('dong_gather')->where('id', $value->id)->update(['body' => $imgstr, 'is_body' => 0]);
                if($rest){
                    $this->info('img body update success');
                }else{
                    $this->error('img body update fail');
                    exit;
                }
            }
        }while($tot > 0);
        $this->info('img body update end');
    }

    /**
     * 下载列表页的图片,并列新数据库
     */
    public function litpicImg()
    {
        do {
            $addonarchives = DB::table('dong_gather')->where('is_litpic', -1)->where('net_flag',$this->netFlag)->take(10)->get();

            $tot = count($addonarchives);
            foreach ($addonarchives as $key => $value) {

                $this->info("this is dong_img_down {$key}/{$tot} -- typeid is {$value->typeid} -- aid is {$value->id}");

                $savePath = rtrim($this->wwwRoot,'/').'/'.ltrim($this->uploadDir,'/').$value->typeid;

                if(!is_dir($savePath)){
                    mkdir($savePath,0777,true);
                }

                //数据为空,则删除这条记录
                if(empty($value->litpic) === true){
                    DB::table('dong_gather')->delete($value->id);
                    continue;
                }
                //得到所有图片链接
                $marest = preg_match('/^http(s)?(.*?)/is',$value->litpic,$matchs);
                //数据不完整,则删除这条记录
                if($marest === 0 ){
                    DB::table('dong_gather')->delete($value->id);
                    continue;
                }

//                $imgarr = $matchs[3];
                $imgarr = $this->multiDownPic([$value->litpic],$savePath);
                if(empty($imgarr) === true){
                    continue;
                }

                $imgstr = '';
                foreach ($imgarr as $k=>$v){
                    $imgstr = trim($this->uploadHost,'/').'/'.ltrim(str_replace($this->wwwRoot,'',$v),'/');
                }
//                dd($imgstr);

//                //更新数据库
                $rest = DB::table('dong_gather')->where('id', $value->id)->update(['litpic' => $imgstr, 'is_litpic' => 0]);
                if($rest){
                    $this->info('img litpic update success');
                }else{
                    $this->error('img ltipic update fail');
                    exit;
                }
            }
        }while($tot > 0);
        $this->error('img ltipic update end');
    }

    //多线程下载图片
    public function multiDownPic($imgarr,$savepath)
    {
//        $imgarr = [
//            'http://image.tianjimedia.com/uploadImages/2012/231/32/89I0MZJKN63V.jpg',
//            'http://img.newyx.net/newspic/image/201511/05/2af5dc50fb.jpg',
//            'http://img.newyx.net/newspic/image/201512/09/0d18bcbdec.jpg',
//            'http://image.tianjimedia.com/uploadImages/2014/232/50/J53C02CD3132.jpg',
//        ];
        //返回生成的文件名
        $mch = curl_multi_init();
        $ch = array();
        $fp = array();
        $file_savepath = array();

        foreach ($imgarr as $key=>$url){
            $parseUrl = parse_url($url);
            if(isset($parseUrl['path']) === false){
               continue;
            }
            $pathinfo = pathinfo($parseUrl['path']);
            if(isset($pathinfo) === false){
                continue;
            }

            $ext = $pathinfo['extension'];
            $file_savepath[$key] = rtrim($savepath,'/').'/'.md5($url).'.'.$ext;

            $fp[$key] = fopen($file_savepath[$key],'wb');
            $ch[$key] = $this->getCurlObject($url,$fp[$key]);
            curl_multi_add_handle($mch,$ch[$key]);
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
        foreach ($ch as $key=>$item){
            fclose($fp[$key]);
            curl_multi_remove_handle($mch,$item);
            curl_close($item);
        }
        curl_multi_close($mch);

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
            foreach ($header as $header_key => $header_value) {
                $option[$header_key] = $header_value;
            }
        }

        if (stripos($url, 'https') === 0) {
            $option[CURLOPT_SSL_VERIFYHOST] = false;
        }

        $ch = curl_init();
        curl_setopt_array($ch, $option);
        return $ch;
    }

}
