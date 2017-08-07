<?php

namespace App\Console\Commands\Send;

use Illuminate\Console\Command;
use Qiniu\Auth;
use Qiniu\Storage\BucketManager;
use App\Console\Commands\Mytraits\QiniuTra;

class QiniuImgsDel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     * @param type list || key
     * @param perfix 文件前缀,当type=key时，prefix为具体文件名称
     * @nums 数量
     */
    protected $signature = 'send:qiniuimgsdel {type}{prefix} {nums?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '删除七牛上面指定的文件';

    protected $bucket;
    protected $accessKey;
    protected $secretKey;


    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->bucket = config('qiniu.qiniu_data.bucket');
        $this->accessKey = config('qiniu.qiniu_data.access_key');
        $this->secretKey = config('qiniu.qiniu_data.secret_key');

    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
        $prefix = $this->argument('prefix');
        //这个有两个值，一个为list,一个为key
        $type = $this->argument('type');
        //如查nums为空则列出全部
        $nums = empty($this->argument('nums'))?'':$this->argument('nums');

        if($type == 'list') {
            do {
                $rest = $this->qiniuList($this->bucket, $prefix, '', $nums);
                if (empty($rest[0])) {
                    $this->error('not prefix ' . $prefix . ' files !');
                    break;
                }
                $tot = count($rest[0]);
                foreach ($rest[0] as $key => $value) {
                    $this->info("{$key}/{$tot}");
                    $this->qiniuDelte($this->bucket, $value['key']);
                }

            } while (true);
        }elseif ($type == 'key'){
            $this->qiniuDelte($this->bucket,$prefix);
        }
        $this->info('qiniu files del end !');
    }

    /**
     * 列出空间里的文件
     * @param $bucket
     * @param $prefix 前缀
     * @param $marker
     * @param $limit 取出多少条记录，如果为空则将前缀为$prefix全部取出
     */
    public function qiniuList($bucket, $prefix = '', $marker = '', $limit = 10)
    {
        $auth = new Auth($this->accessKey, $this->secretKey);
        $bucketMgr = new BucketManager($auth);
        // 要列取文件的公共前缀
//        $limit = 10;
//        list($iterms, $marker, $err) = $bucketMgr->listFiles($bucket, $prefix, $marker, $limit);
        $listArr = $bucketMgr->listFiles($bucket, $prefix, $marker, $limit);
        return $listArr;
    }


    /**
     * 删除空间里的文件
     * @param $bucket
     * @param $key
     */
    public function qiniuDelte($bucket, $key)
    {
        //初始化Auth状态
        $auth = new Auth($this->accessKey, $this->secretKey);
        //初始化BucketManager
        $bucketMgr = new BucketManager($auth);
        //删除$bucket 中的文件 $key
        $err = $bucketMgr->delete($bucket, $key);
        echo "\n====> delete $key : \n";
        if ($err !== null) {
            var_dump($err);
        } else {
            echo "Success! \n";
        }

    }
}
