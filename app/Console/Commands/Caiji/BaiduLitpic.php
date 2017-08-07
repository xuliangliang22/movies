<?php

namespace App\Console\Commands\Caiji;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use XuLiangLiang\Search\Factory;

class BaiduLitpic extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     * @param qiniu_dir_name = tvs/imgs
     */
    protected $signature = 'caiji:baidulitpic {qiniu_dir} {type_id} {key_word_suffix?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '下载百度的图片';

    protected $qiniuKey;
    protected $savePath;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->savePath = config('qiniu.qiniu_data.www_root');
        $this->qiniuKey = config('qiniu.qiniu_data.qiniu_dns');
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
        $qiniuDir = $this->argument('qiniu_dir');
        //搜索关键词的后缀，找出更精确的图片
        $keyWordSuffix = empty($this->argument('key_word_suffix'))?'':$this->argument('key_word_suffix');

        $this->savePath = rtrim($this->savePath,'/').'/'.date('ymd').$typeId.'/';
        $this->qiniuKey = rtrim($this->qiniuKey,'/').'/'.trim($qiniuDir,'/').'/'.date('ymd').$typeId.'/';
        if(!is_dir($this->savePath)){
            mkdir($this->savePath,0755,true);
        }

        DB::connection('dedea67')->table('gather_dedea67')->where('typeid',$typeId)->where('litpic','')->where('is_post',-1)->orderBy('id')->chunk(100,function ($conArr) use ($keyWordSuffix){
            $tot = count($conArr);

            $factory = new Factory();
            $factory->getSelfObj('baidupic');
            $baiduobj = $factory->factoryObj;

            $factory->getSelfObj('picdown');
            $picdownObj = $factory->factoryObj;

            foreach ($conArr as $key => $value) {
                echo "==============//===================\n";
                $this->info("{$key}/{$tot} id is {$value->id}");
                $ret = $baiduobj->getPic($value->title.' '.$keyWordSuffix);
                if (empty($ret)) {
//                throw new \Exception("{$keyWord} litpic baidu is not exists");
                    $this->error("{$value->title} litpic baidu is not exists");
                }

                foreach ($ret as $k=>$v) {
                    $imgUrl = $v;
                    $ext = substr($imgUrl, strripos($imgUrl, '.'));
                    if (in_array($ext, array('.jpg', '.jpeg', '.png', '.gif')) === false) {
                        $ext = '.jpg';
                    }
                    //本地保存路径
                    $fileName = $this->savePath . md5($imgUrl) . $ext;
                    $picdownObj->imgDown($fileName, $imgUrl);
                    if (file_exists($fileName) && filesize($fileName) > 20 * 1024 && @getimagesize($fileName) !== false) {
                        //更新数据库中的内容
                        //七牛云的路径
                        $fileName = str_replace($this->savePath, $this->qiniuKey, $fileName);
//                        $qiniuFile = rtrim($this->qiniuPath,'/') .'/'. trim($qiniuDir,'/') .'/' .ltrim($fileName,'/');
//                        dd($qiniuFile);
                        //更新数据库
                        $rest = DB::connection('dedea67')->table('gather_dedea67')->where('id', $value->id)->update(['litpic' => $fileName]);
                        if ($rest) {
                            $this->info('baidu litpic update success filename is '.$fileName.' !');
                            break;
                        } else {
                            $this->error('baidu litpic update fail !');
                        }
                    } else {
                        $this->error("{$fileName} is not a pic , deleted now !");
                        unlink($fileName);
                    }
                }
                echo "=================================\n";
//                dd(222);
            }
            //如果存在则取第一个然后下载
        });
        $this->info('baidu litpic update end !');
    }
}
