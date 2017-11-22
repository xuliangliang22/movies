<?php
namespace App\Console\Commands\Mytraits;

use App\Common\Lcs;
use QL\QueryList;

trait BaiduStatus
{
    /**
     * 通过标题判断，文章是否被百度收录
     * @param $title
     */
    public function baiduJudge($title)
    {
        try {
            $lcs = new Lcs();
            $data = null;
            $url = 'https://www.baidu.com/s?ie=utf-8&f=3&rsv_bp=0&rsv_idx=1&tn=baidu&wd=' . urlencode($title);

            $data = QueryList::Query($url, array(
                'title' => ['#content_left .result h3', 'text']
            ))->data;
            if ($data) {
                foreach ($data as $key => $value) {
                    $rest = $lcs->getSimilar($title, $value['title']);
                    if (ceil($rest * 100) > 60 || strpos($value['title'], $title) !== false) {
                        return false;
                    }
                }
            }
            return true;
        } catch (\Exception $e) {
            echo "baidu status exception {$e->getMessage()} file {$e->getFile()} line {$e->getLine()}" . PHP_EOL;
            return false;
        } catch (\ErrorException $e) {
            echo "baidu status error exception {$e->getMessage()} file {$e->getFile()} line {$e->getLine()}" . PHP_EOL;
            return false;
        }
    }
}



















