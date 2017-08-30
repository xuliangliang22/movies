<?php

namespace App\Console\Commands\Send;

use Illuminate\Console\Command;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;
use Qiniu\Storage\BucketManager;
use App\Console\Commands\Mytraits\QiniuTra;

class QiniuImgsUp extends Command
{
    /**
     * @param local_dir 本地文件夹路径
     * @qiniu_dir  下传七牛去的文件名前的路径 //movies/imgs/17071515,前后不需要加/
     *
     * @var string
     *
     */
    protected $signature = 'send:qiniuimgs {local_dir} {qiniu_dir}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '七牛上传下载文件';

    protected $bucket;
    protected $accessKey;
    protected $secretKey;

    //日志保存路径
    public $commandLogsFile;
    //是否开启日志
    public $isCommandLogs;


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


        $this->commandLogsFile = config('qiniu.qiniu_data.command_logs_file');
        $this->isCommandLogs = config('qiniu.qiniu_data.is_command_logs');
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $localDir = $this->argument('local_dir');
        $qiniuDir = $this->argument('qiniu_dir');

        $files = scandir($localDir);
        $tot = count($files);
        if($tot < 3){
//            $this->info("{$localDir} empty !");
            echo "{$localDir} empty !";
            exit;
        }
        foreach ($files as $fkey => $file) {
            $this->info("{$fkey}/{$tot}");
            $filePath = rtrim($localDir,'/') . '/' . $file;
            $key = trim($qiniuDir, '/') . '/' . $file;

            if ($file === '.' || $file === '..') {
                continue;
            }
            $this->qiniuUpload($filePath, $key);
        }
    }


    /**
     * 七牛去上传文件
     * @param $filePath 　要上传文件的本地路径
     * @param $key 　上传到七牛后保存的文件名
     */
    public function qiniuUpload($filePath, $key)
    {
        // 构建鉴权对象
        $auth = new Auth($this->accessKey, $this->secretKey);
        // 生成上传 Token
        $token = $auth->uploadToken($this->bucket);
        // 初始化 UploadManager 对象并进行文件的上传
        $uploadMgr = new UploadManager();
        // 调用 UploadManager 的 putFile 方法进行文件的上传
        list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);
        $this->info("\n====> putFile result: \n");
        if ($err !== null) {
            //失败
            $this->error('fail');
            if($this->isCommandLogs === true) {
                file_put_contents($this->commandLogsFile, var_export($err, true)."\n", FILE_APPEND);
            }
            exit;
//            var_dump($err);
        } else {
            $this->info('success');
//            var_dump($ret);
            if($this->isCommandLogs === true) {
                file_put_contents($this->commandLogsFile, var_export($ret, true)."\n", FILE_APPEND);
            }
        }
    }


    /**
     * 七牛去文件下载
     * @param $baseUrl 构造成私有空间的域名/key的形式
     * http://ojnhba94s.bkt.clouddn.com/dedea67/17071115/00d6189f2b50c00b24f451141e439a30.jpg?e=1499778463&token=ki9_ISgjTf55l9a8xh1kgUTnl9QYEhr4V_vwxqky:T0EkZUF-Uq-5_ xCdWhRL5zPtyvk=
     * @return  返回一个私密带token的下载链接
     */
    public function qiniuDown($baseUrl)
    {
        // 构建鉴权对象
        $auth = new Auth($this->accessKey, $this->secretKey);
        $authUrl = $auth->privateDownloadUrl($baseUrl);
        echo $authUrl;
    }


}
