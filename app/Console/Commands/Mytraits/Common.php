<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/10/19 0019
 * Time: 上午 11:08
 */
namespace App\Console\Commands\Mytraits;

use zgldh\QiniuStorage\QiniuStorage;

trait Common
{
    protected $curl = null;

    protected $dbName;
    protected $tableName;

    protected $commandLogsPath;
    protected $commandLogsFile;
    protected $isCommandLogs;

    protected $dedeSendStatusFile;

    /**
     * 初始化
     */
    protected function initBegin()
    {
        if($this->curl === null){
            $path = app_path('Console/Commands/curl/curl.php');
            require_once $path;
            $this->curl = new \curl();
        }

        //采集的数据库名与表名
        $this->dbName = config('qiniu.qiniu_data.db_name');
        $this->tableName = config('qiniu.qiniu_data.table_name');

        //log
        $this->commandLogsPath = config('qiniu.qiniu_data.command_logs_path');
        $this->commandLogsFile = $this->commandLogsPath.'/'.config('qiniu.qiniu_data.command_logs_file');
        $this->isCommandLogs = config('qiniu.qiniu_data.is_command_logs');

        if (!is_dir($this->commandLogsPath)) {
            mkdir(public_path('command_logs'), 0755, true);
        }

        //生成一个提交或更新的状态文件
        $dedeSendStatusDir = config('qiniu.qiniu_data.dede_send_status_dir');
        if(!is_dir($dedeSendStatusDir)){
            mkdir($dedeSendStatusDir,0755,true);
        }
        $this->dedeSendStatusFile = $dedeSendStatusDir.'/'.config('qiniu.qiniu_data.dede_send_status_file');
        if(file_exists($this->dedeSendStatusFile)){
            unlink($this->dedeSendStatusFile);
        }
    }

    /**
     * 上传图片
     */
    protected function imgUpload($url)
    {
        $file = null;
        $disk = QiniuStorage::disk('qiniu');

        //将网络图片上传到七牛云
        if(preg_match('/^https(.*?)/is',$url)){
            $sslt = 2;
        }else{
            $sslt = 1;
        }
        $this->curl->add()->opt_targetURL($url,$sslt)->done();
        $this->curl->run();
        $data = $this->curl->getAll();
        $this->curl->free();

        if($data['info']['http_code'] == '200' && stripos($data['info']['content_type'],'image') !== false && (integer)$data['info']['size_download'] > 1024){
            //获得文件后缀
            $ext = substr($data['info']['content_type'], stripos($data['info']['content_type'], '/')+1);
            if($ext == 'jpeg'){
                $ext = 'jpg';
            }
            $file = $this->savePath . '/' . md5($url) . '.' . $ext;

            //如果不存在则才会上传
            if(!$disk->exists($file)){
                $content = $data['body'];
                $rest = $disk->put($file, $content);
                //上传成功去判断大小
                if($rest){
                    $size = $disk->size($file);
                    if($size < 1024){
                        //删除
                        $disk->delete($file);
                        $file = null;
                    }
                }else{
                    //上传没有成功
                    $file = null;
                }
            }
        }
        return $file;
    }
}















