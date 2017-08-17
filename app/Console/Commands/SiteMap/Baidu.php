<?php

namespace App\Console\Commands\SiteMap;

use Illuminate\Console\Command;

class Baidu extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sitemap:baidu';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '百度自动提交链接';

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
        //
        $fileName = 'jingdiandianying';
//        $fileName = 'oumei';

        $host = 'www.ca2722.com';
        $baseDir = 'D:/iis/htdocs/mayunca2722/';
        $tmpDir = 'dianying/'.$fileName;

        $savePath = public_path().DIRECTORY_SEPARATOR.'site_map_baidu';
        if(!is_dir($savePath))
        {
            mkdir($savePath,0755);
        }
        $saveFile = $savePath.DIRECTORY_SEPARATOR.$fileName.'.txt';

//        $this->saveUrls($host,$baseDir,$tmpDir,$saveFile);
        $this->siteCurl($saveFile);

    }

    public function saveUrls($host,$baseDir,$tmpDir,$saveFile)
    {
        $localDir = $baseDir.$tmpDir;

        $files = scandir($localDir);
        $tot = count($files);
        if($tot < 3){
            $this->info("{$localDir} empty !");
            exit;
        }
        foreach ($files as $fkey => $file) {
            $this->info("{$fkey}/{$tot}");

            if ($file === '.' || $file === '..') {
                continue;
            }
            file_put_contents($saveFile,$host.'/'.$tmpDir.'/'.$file."\n",FILE_APPEND);
        }

        $this->info("{$localDir} site map save end");

    }



    public function siteCurl($file)
    {
        $urls = file($file,FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $tot = count($urls);
        if($tot > 2000){
//            $chunk = ceil($tot/10);
            $urls = array_chunk($urls,1000);
        }else{
            $urls = array('0'=>$urls);
        }
        foreach ($urls as $key=>$value) {
            $api = 'http://data.zz.baidu.com/urls?site=www.ca2722.com&token=1DnWflPHJyJ3QkqS';
            $ch = curl_init();
            $options = array(
                CURLOPT_URL => $api,
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POSTFIELDS => implode("\n", $value),
                CURLOPT_HTTPHEADER => array('Content-Type: text/plain'),
            );
            curl_setopt_array($ch, $options);
            $result = curl_exec($ch);
            echo $result;
        }
    }
}
