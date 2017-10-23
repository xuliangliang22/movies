<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/10/21 0021
 * Time: 下午 4:15
 */
namespace App\Console\Commands\Mytraits;
use QL\QueryList;

trait Toutiao
{
    /**
     * 获得文章图片内容
     * @param $url
     */
    public function getText($url)
    {
        $body = null;
        $ip = getRandIp();
        $ql = QueryList::run('Request',[
            'target' => $url,
            'method' => 'GET',
            'CLIENT-IP:'.$ip,
            'X-FORWARDED-FOR:'.$ip,
            'user_agent'=>'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.8; rv:21.0) Gecko/20100101 Firefox/21.0',
            //等等其它http相关参数，具体可查看Http类源码
        ]);
        $html = $ql->setQuery([])->getHtml();
        $marest = preg_match('/articleInfo:(.*?)\{(.*?)content:(.*?)\{/is',$html,$matchs);
        if($marest > 0) {
            $body = substr($matchs[3],2);
            $body = htmlspecialchars_decode($body);
            //去除所有的图片链接
            $body = preg_replace('/<img(.*?)>/is','',$body);
            $body = trim(strstr($body,'.replace',true),'\'');
        }else{
            $data = QueryList::Query($html,array(
               'body' => array('.article-content','text','p -img -a script')
            ))->data;
            if(isset($data[0]['body']) === true){
                $body = $data[0]['body'];
            }
        }
        $body = removeCss($body);
        return $body;
    }


    /**
     *  得到手机端内容详情
     * @param $url
     * @return mixed|null
     */
    public function gettemai($url)
    {
        $body = null;
        $ip = getRandIp();
        $ql = QueryList::run('Request',[
            'target' => $url,
            'method' => 'GET',
            'CLIENT-IP:'.$ip,
            'X-FORWARDED-FOR:'.$ip,
            'user_agent'=>'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.8; rv:21.0) Gecko/20100101 Firefox/21.0',
            //等等其它http相关参数，具体可查看Http类源码
        ]);

        $data = $ql->setQuery([
            'body'=>['#gallery','text','p -img -a -script -.tit'],
        ])->data;

        if(isset($data[0]['body']) === true) {
            $body = removeCss($data[0]['body']);
        }
        return $body;
    }
}







