<?php

namespace App\Console\Commands\Caiji;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use XuLiangLiang\Search\Factory;
use App\Console\Commands\Mytraits\Common;

class BaiduLitpic extends Command
{
    use Common;
    /**
     * The name and signature of the console command.
     *
     * @var string
     * @param qiniu_dir_name = tvs/imgs
     */
    protected $signature = 'caiji:baidulitpic {type_id} {key_word_suffix?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '下载百度的图片';

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
        //
        $typeId = $this->argument('type_id');
        //搜索关键词的后缀，找出更精确的图片
        $keyWordSuffix = empty($this->argument('key_word_suffix')) ? '' : $this->argument('key_word_suffix');

        $this->savePath = config('admin.upload.directory.image').$typeId ;

        $message = null;
        $minId = 0;
        $take = 10;

        $factory = new Factory();
        $factory->getSelfObj('baidupic');
        $baiduobj = $factory->factoryObj;

        do {
            $conArr = DB::connection($this->dbName)->table($this->tableName)->select('id','title')->where('typeid', $typeId)->where('is_litpic', -1)->where('id','>',$minId)->take($take)->get();
            $tot = count($conArr);

            foreach ($conArr as $key => $value) {
                $minId = $value->id;
                $message = date('Y-m-d H:i:s')."baidu litpic {$key}/{$tot} aid {$value->id}".PHP_EOL;
                $this->info($message);

                //得到百度图片数组
                $ret = $baiduobj->getPic($value->title . ' ' . $keyWordSuffix,array('size'=>2));
                if (empty($ret)) {
//                throw new \Exception("{$keyWord} litpic baidu is not exists");
                    //删除这条记录
                    DB::connection($this->dbName)->table($this->tableName)->where('id',$value->id)->delete();
                    $message .= "baidu litpic is not exists";
                    $this->error($message);
                    continue;
                }

                $file = false;
                foreach ($ret as $k => $v) {
                    if(stripos($v,'jpg') === false || stripos($v,'jpeg') === false){
                        continue;
                    }
                    $file = $this->imgUpload($v);
                    if($file){
                        break;
                    }
                }
                if($file === false){
                    //百度图片没有上传成功,则删除这条记录
                    DB::connection($this->dbName)->table($this->tableName)->where('id',$value->id)->delete();
                    $message .= "baidu litpic upload to qiniu fail !!";
                    $this->error($message);
                    continue;
                }
                //否则更新数据库
                $ossImg = rtrim(config('filesystems.disks.qiniu.domains.default'),'/').'/'.ltrim($file,'/').config('qiniu.qiniu_data.qiniu_postfix');;
                $rest = DB::connection($this->dbName)->table($this->tableName)->where('id',$value->id)->update(['litpic'=>$ossImg,'is_litpic'=>0]);
                if($rest){
                    $message .= "baidu litpic upload to qiniu and update db success !!";
                    $this->info($message);
                }else{
                    $message .= "baidu litpic upload to qiniu and update db fail !!";
                    $this->error($message);
                }
                //保存日志
                if($this->isCommandLogs === true){
                    file_put_contents($this->commandLogsFile,$message,FILE_APPEND);
                }
            }
        }while($tot > 0);
        $message = 'baidu litpic update end !';
        $this->info($message);
        //保存日志
        if($this->isCommandLogs === true){
            file_put_contents($this->commandLogsFile,$message,FILE_APPEND);
        }

    }
}
