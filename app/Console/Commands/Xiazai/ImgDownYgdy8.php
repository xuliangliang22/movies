<?php

namespace App\Console\Commands\Xiazai;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Console\Commands\Mytraits\Common;


class ImgDownYgdy8 extends Command
{
    use Common;

    /**
     * The name and signature of the console command.
     * @var string
     * php artisan xiazai:img body 13 //内容页图片
     * php artisan xiazai:img litpic 13 //封面图片
     */
    protected $signature = 'xiazai:img {action} {type_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '上传图片到七牛';

    /**
     * 七牛文件前缀
     */
    protected $savePath;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->initBegin();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $action = $this->argument('action');
        $typeId = $this->argument('type_id');

        $this->savePath = config('admin.upload.directory.image').$typeId ;

        switch ($action)
        {
            case 'body':
                $this->bodyImg($typeId);
                break;
            case 'litpic':
                $this->litpicImg($typeId);
                break;
        }
    }


    /**
     * 下载内容页图片
     * @param $typeId
     */
    public function bodyImg($typeId)
    {
        $message = null;
        $content = null;
        $minId = 0;
        $take = 10;

        do {
            $addonarchives = DB::connection($this->dbName)->table($this->tableName)->select('id','body')->where('typeid', $typeId)->where('is_body', -1)->where('id','>',$minId)->take($take)->get();
            $tot = count($addonarchives);

            foreach ($addonarchives as $key => $value) {
                $minId = $value->id;
                $message = date('Y-m-d H:i:s')." this is dong_img_down {$key}/{$tot} -- typeid is {$typeId} -- aid is {$value->id}".PHP_EOL;
                $this->info($message);

                //得到所有图片链接
                $marest = preg_match_all('/<img(.*?)src\s*=\s*["\'](.*?)["\'][^>]*>/is', $value->body, $matchs);
                //数据不完整,内容中没有图片,则更新这条记录
//                dd($matchs);
                if ($marest === 0) {
                    DB::connection($this->dbName)->table($this->tableName)->where('id', $value->id)->update(['is_body' => 0]);
                    continue;
                }
                //如果匹配到则将为空的数据替换掉
                foreach ($matchs[2] as $mk => $mv) {
                    //上传图片,得到一个数组
                    $file = $this->imgUpload($mv);
                    if($file === false){
                        $value->body = str_replace($matchs[1][$mk],'',$value->body);
                        continue;
                    }
                    $ossImg = rtrim(config('filesystems.disks.qiniu.domains.default'),'/').'/'.ltrim($file,'/').config('qiniu.qiniu_data.qiniu_postfix');;
                    $value->body = str_replace($mv,$ossImg,$value->body);
                }
//                dd($value->body);
                //更新数据库
                $rest = DB::connection($this->dbName)->table($this->tableName)->where('id', $value->id)->update(['body' => $value->body, 'is_body' => 0]);
                if ($rest) {
                    $message .= "{$typeId} aid {$value->id} body image upload success !!".PHP_EOL;
                    $this->info($message);
                } else {
                    $message .= "{$typeId} aid {$value->id} body image upload success !!".PHP_EOL;
                    $this->error($message);
                }
                //保存日志
                if($this->isCommandLogs === true){
                    file_put_contents($this->commandLogsFile,$message,FILE_APPEND);
                }
            }
        } while ($tot > 0);
        $message = 'img body update end'.PHP_EOL;
        $this->info($message);
        //保存日志
        if($this->isCommandLogs === true){
            file_put_contents($this->commandLogsFile,$message,FILE_APPEND);
        }
    }


    /**
     * 下载列表页的图片,并列新数据库
     */
    public function litpicImg($typeId)
    {
        $content = null;
        $message = null;
        $minId = 0;
        $take = 10;

        do {
            $addonarchives = DB::connection($this->dbName)->table($this->tableName)->select('id','litpic')->where('typeid', $typeId)->where('is_litpic', -1)->where('id','>',$minId)->take($take)->get();
            $tot = count($addonarchives);

            foreach ($addonarchives as $key => $value) {
                $minId = $value->id;
                $message = date('Y-m-d H:i:s')." this is litpic_upload {$key}/{$tot} -- typeid is {$typeId} -- aid is {$value->id}".PHP_EOL;
                $this->info($message);

                //得到所有图片链接
                $marest = preg_match('/^http(s)?(.*?)/is', $value->litpic, $matchs);
                //数据不完整,则删除这条记录
                if ($marest === 0) {
                    //如果图片格式不正确则更新这条记录
                    continue;
                }
                //将网络图片上传到七牛云
                $file = $this->imgUpload($value->litpic);
                if($file){
                    $message .= "{$typeId} litpic aid {$value->id} upload qiniu success !!".PHP_EOL;
                    $this->info($message);
                }else{
                    $message .= "{$typeId} litpic aid {$value->id} upload qiniu fail !!".PHP_EOL;
                    $this->error($message);
                    //保存日志
                    if($this->isCommandLogs === true){
                        file_put_contents($this->commandLogsFile,$message,FILE_APPEND);
                    }
                    //图片上传不成功,跳过
                    continue;
                }

                //更新数据库
                $ossImg = rtrim(config('filesystems.disks.qiniu.domains.default'),'/').'/'.ltrim($file,'/').config('qiniu.qiniu_data.qiniu_postfix');
                $rest = DB::connection($this->dbName)->table($this->tableName)->where('id', $value->id)->update(['litpic' => $ossImg, 'is_litpic' => 0]);
                if ($rest) {
                    $message .= "{$typeId} litpic aid {$value->id} gather table update success !!".PHP_EOL;
                    $this->info($message);
                } else {
                    $message .= "{$typeId} litpic aid {$value->id} gather table update fail !!".PHP_EOL;
                    $this->error($message);
                }
                //保存日志
                if($this->isCommandLogs === true){
                    file_put_contents($this->commandLogsFile,$message,FILE_APPEND);
                }
            }
        } while ($tot > 0);
        $message = 'img ltipic update end'.PHP_EOL;
        $this->error($message);
        //保存日志
        if($this->isCommandLogs === true){
            file_put_contents($this->commandLogsFile,$message,FILE_APPEND);
        }
    }

}
