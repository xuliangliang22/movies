<?php

namespace App\Console\Commands\Xiazai;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use XuLiangLiang\Search\Factory;
use App\Console\Commands\Mytraits\QiniuTra;


class ImgDownYgdy8 extends Command
{

    use QiniuTra;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'xiazai:imgdownygdy8 {type}{qiniu_dir}{type_id}{db_name}{table_name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '下载阳光电影的图片';

    protected $typeId;
    protected $dbName;
    protected $tableName;
    protected $savePath;
    protected $qiniuKey;
    //下载图片的对象
    protected $picObj;


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
        //判断运行环境
//        if (substr(php_sapi_name(), 0, 3) == 'cgi') {
//            //cgi
//            define('IS_CGI',true);
//        } else {
//            //no cgi
//            define('IS_CGI',false);
//        }
        $type = $this->argument('type');
        $qiniuDir = $this->argument('qiniu_dir');
        $this->typeId = $this->argument('type_id');
        $this->dbName = $this->argument('db_name');
        $this->tableName = $this->argument('table_name');
        $factory = new Factory();
        $factory->getSelfObj('picdown');
        $this->picObj = $factory->factoryObj;

        $this->savePath = config('qiniu.qiniu_data.www_root') . date('ymd') . $this->typeId . '/';
        if (!is_dir($this->savePath)) {
            mkdir($this->savePath, 0777, true);
        }

        $this->qiniuKey = rtrim(config('qiniu.qiniu_data.qiniu_dns'), '/') . '/' . trim($qiniuDir, '/') . '/' . date('ymd') . $this->typeId . '/';

        if ($type == 'body') {
            $this->bodyImg();
        } else {
            $this->litpicImg();
        }

    }

    public function bodyImg()
    {

        do {
            $addonarchives = DB::connection($this->dbName)->table($this->tableName)->where('typeid', $this->typeId)->where('is_body', -1)->orderBy('id')->take(10)->get();

            $tot = count($addonarchives);
//            dd($tot);
            foreach ($addonarchives as $key => $value) {
                $this->info("this is dong_img_down {$key}/{$tot} -- typeid is {$value->typeid} -- aid is {$value->id}");

                //数据为空,则删除这条记录
                if (empty($value->body) === true) {
//                    DB::table('dong_gather')->delete($value->id);
                    DB::connection($this->dbName)->table($this->tableName)->where('id', $value->id)->update(['is_body' => 0]);
                    continue;
                }
                //得到所有图片链接
                $marest = preg_match_all('/<img\s*src=["\'](.*?)["\'][^>]*>/is', $value->body, $matchs);

                //数据不完整,内容中没有图片,则更新这条记录
//                dd($matchs);
                if ($marest === 0) {
//                    DB::table('dong_gather')->delete($value->id);
                    DB::connection($this->dbName)->table($this->tableName)->where('id', $value->id)->update(['is_body' => 0]);
                    continue;
                }
                //如果匹配到则将为空的数据替换掉
                foreach ($matchs[1] as $mk => $mv) {
                    if (empty($mv)) {
                        $value->body = str_replace($matchs[0][$mk], '', $value->body);
                        unset($matchs[1][$mk]);
                    }
                }
                //$matchs[1] 得到所有的图片链接
                $fileName = array();
                foreach ($matchs[1] as $k => $v) {
                    $ext = $this->getExt($v);
                    $fileName[$k] = $this->savePath . md5($v) . $ext;
                }
                //dd($fileName,$matchs[1]);
                //两个参数，保存路径，与图片网络路径
                $this->picObj->imgDown($fileName, $matchs[1]);
                $ossImg = array();
//                dd($fileName);
                foreach ($fileName as $fk => $fv) {
                    //判断图片文件是否有效
                    $isPic = $this->judgeImg($fv);
                    if ($isPic === true) {
                        $ossImg[$fk] = str_replace($this->savePath, $this->qiniuKey, $fv).'?imageslim';
                    } else {
                        if(file_exists($fv)){
                            unlink($fv);
                        }
                        $ossImg[$fk] = '';
                    }
                }
                $body = str_replace($matchs[1],$ossImg,$value->body);
                $body = preg_replace('/<img(.*)src=""(.*)>/isU','',$body);
//                dd($body);
                $this->info($body."\n");
//                //更新数据库
                $rest = DB::connection($this->dbName)->table($this->tableName)->where('id', $value->id)->update(['body' => $body, 'is_body' => 0]);
                if ($rest) {
                    $this->info('img body update success');
                } else {
                    $this->error('img body update fail');
                    exit;
                }
            }
        } while ($tot > 0);
        $this->info('img body update end');
    }

    /**
     * 下载列表页的图片,并列新数据库
     */
    public function litpicImg()
    {
        do {

            $addonarchives = DB::connection($this->dbName)->table($this->tableName)->where('typeid', $this->typeId)->where('is_litpic', -1)->orderBy('id')->take(10)->get();
//            dd($addonarchives);
            $tot = count($addonarchives);
            foreach ($addonarchives as $key => $value) {
                $this->info("this is litpic_down {$key}/{$tot} -- typeid is {$value->typeid} -- aid is {$value->id}");

                //数据为空,则更新这条记录
                if (empty($value->litpic) === true) {
                    DB::connection($this->dbName)->table($this->tableName)->where('id', $value->id)->update(['is_litpic' => 0]);
                    continue;
                }
                //得到所有图片链接
                $marest = preg_match('/^http(s)?(.*?)/is', $value->litpic, $matchs);
                //数据不完整,则删除这条记录
                if ($marest === 0) {
                    //如果图片格式不正确则更新这条记录
                    DB::connection($this->dbName)->table($this->tableName)->where('id', $value->id)->update(['is_litpic' => 0, 'litpic' => '']);
                    continue;
                }
                //提交单线程下载图片,本地图路径
                $ext = $this->getExt($value->litpic);
                $fileName = $this->savePath . md5($value->litpic) . $ext;
                $this->picObj->imgDown($fileName, $value->litpic);
                $ossImg = '';
                //判断图片格式是否正确
                $isPic = $this->judgeImg($fileName);
                if ($isPic === true) {
                    //更新数据库信息
                    $ossImg = str_replace($this->savePath, $this->qiniuKey, $fileName);
                    $ossImg = $ossImg.'?imageslim';
                } else {
                    unlink($fileName);
                }
                $this->info($ossImg);
//                dd($ossImg);
//                //更新数据库
                $rest = DB::connection($this->dbName)->table($this->tableName)->where('id', $value->id)->update(['litpic' => $ossImg, 'is_litpic' => 0]);
                if ($rest) {
                    $this->info('img litpic update success');
                } else {
                    $this->error('img ltipic update fail');
                    exit;
                }
            }
        } while ($tot > 0);
        $this->error('img ltipic update end');
    }

}
