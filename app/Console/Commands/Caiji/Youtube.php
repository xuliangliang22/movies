<?php

namespace App\Console\Commands\Caiji;

use Illuminate\Console\Command;
use QL\QueryList;

class Youtube extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'caiji:youtube';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '下载youtube视频';

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
        //文件保存位置
        $savePath = '/mnt/ytbe/'.date('Ymd');
        if(is_dir($savePath) === false){
            mkdir($savePath,0755,true);
        }
        $urls = [
        ];
        $tot = count($urls);
        foreach ($urls as $key=>$value){
            $this->info("youtube {$key}/{$tot}");

            $data = $this->getvideoUrl($value);
            if(isset($data['link']) || empty($data['link'])){
                //
                $this->error('下载链接没有获取到');
                continue;
            }
            if(isset($data['title']) || empty($data['title'])){
                $data['title'] = md5($value);
            }
            $saveFile = $savePath.'/'.$data['title'].'.mp4';
            $rest = $this->down($data['link'],$saveFile);
            if($rest['code'] == 200){
                $this->info("youtube {$value} down success");
            }else{
                $this->error("youtube {$value} doen fail");
            }
            sleep(3);
        }
        $this->info("youtube down end");
    }


    /**
     * 下载链接
     */
    public function down($url,$saveFile)
    {
        $fp = fopen($saveFile,'rb');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_NOBODY, false);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, '180');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Connection' => 'keep-alive',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.87 Safari/537.36'
        ));
        curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        fclose($fp);
        return $info;
    }


    /**
     * @param $url
     * @return mixed
     */
    public function getvideoUrl($url)
    {
        $durl = 'http://y2mate.com/analyze/ajax';
        $ql = QueryList::run('Request',[
            'target' => $durl,
            'method' => 'POST',
            'params' => ['url'=>$url,'ajax'=>1],
            //等等其它http相关参数，具体可查看Http类源码
            'timeout' =>'180'
        ]);
        $data = $ql->setQuery([
            'title' => ['.caption b','text'],
            'link' => ['.txt-center:eq(0) a','href'],
        ])->data;

        return $data;
    }
}
