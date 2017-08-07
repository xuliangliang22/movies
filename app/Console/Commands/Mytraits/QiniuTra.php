<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/2 0002
 * Time: 下午 9:34
 */
namespace App\Console\Commands\Mytraits;


trait QiniuTra
{
    /**
     * 得到文件后缀名
     * @param $url
     * @return string
     */
    public function getExt($file)
    {
        $ext = substr($file, strripos($file, '.'));
        if (in_array($ext, array('.jpg', '.jpeg', '.png', '.gif')) === false) {
            $ext = '.jpg';
        }
        return $ext;
    }


    /**
     * 判断是否为图片
     * @param $data
     * @return bool
     */
    public function judgeImg($data)
    {
        $rest = false;
        if (file_exists($data) && filesize($data) > 20 * 1024 && @getimagesize($data) !== false) {
            //更新数据库信息
            $rest = true;
        }
        return $rest;
    }
}