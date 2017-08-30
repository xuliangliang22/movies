<?php

namespace App\Console\Commands\Caiji;

use DiDom\Query;
use Illuminate\Console\Command;
use QL\QueryList;

class Test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'caiji:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '用于测试';


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

        //ABTEST=0|1504001491|v17; IPLOC=CN3206; SUID=A7E643313921940A0000000059A53DD3; SUV=1504001490684743; browerV=3; osV=1
        $url = 'https://www.sogou.com/web?query='.urlencode('qq头像大全').'&_asf=www.sogou.com';
//        $cookie = $this->getCookie();

        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch,CURLOPT_NOBODY,false);
        curl_setopt($ch,CURLOPT_HTTPHEADER,array(
            'Host:www.sogou.com',
            'Referer:https://www.sogou.com/',
            'User-Agent:Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.90 Safari/537.36',
//            'Cookie:'.$cookie,
            'Cookie:ABTEST=3|1504003556|v17; IPLOC=CN3206; SUID=A7E643313921940A0000000059A545E4; PHPSESSID=u5jgdjaf47u46dlb17mfe0v4v3; SUIR=1504003556; SUV=00BB41173143E6A759A545E4FABFB911; SNUID=7D3D98EADADE8C39C61F8A51DBAA771A',
        ));
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,2);

        $info = curl_exec($ch);
        curl_close($ch);


        $data = QueryList::Query($info,array(
            'word'=>array('','text'),
        ),'#hint_container a')->data;

        dd($data);


    }


    //得到cookie
    public function getCookie()
    {
        $url = 'https://www.sogou.com/';
        $ch = curl_init();

        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch,CURLOPT_HEADER,true);
        curl_setopt($ch,CURLOPT_NOBODY,false);
        curl_setopt($ch,CURLOPT_HTTPHEADER,array(
            'User-Agent:Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.90 Safari/537.36',
        ));

        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,2);

        $info = curl_exec($ch);
        curl_close($ch);

        $header = explode("\r\n\r\n",$info,2);
        $header = $header[0];
        $header = array_filter(explode("\r\n",$header));
        $cookie = [];
        foreach ($header as $key=>$value){
            if(stripos($value,'Set-Cookie') !== false){
                list($k,$v) = explode(':',$value,2);
                $v = strstr($v,';',true);
                $cookie[] = trim($v);
            }
        }
        $cookie = implode(';',$cookie);
        return $cookie;
    }

    public function hFormat($header)
    {
        $cookie = [];
        $header = array_filter(explode("\r\n",$header));
        foreach ($header as $key=>$value){
            if(stripos($value,'Set-Cookie') !== false){
                list($k,$v) = explode(':',$value,2);
                $v = strstr($v,';',true);
                $cookie[] = trim($v);
            }
        }
        return $cookie;
    }

}
