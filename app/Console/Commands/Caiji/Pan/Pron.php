<?php

namespace App\Console\Commands\Caiji\Pan;

use Illuminate\Console\Command;

class Pron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'caiji:pan_pron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '视频下载';

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
        $urls = array(
            $url = 'http://192.240.120.98//mp43/241314.mp4?st=fYa8_61bV72Jf-cSxHSRZw&e=1509667710',
//            $url = 'http://192.240.120.74//mp43/241316.mp4?st=8qF7PHylQkuhvS-re3gpxg&e=1509667826',
//            $url = 'http://192.240.120.106//mp43/241295.mp4?st=ndhsXGxN3aQkz_yp81oXrQ&e=1509667889',
//            $url = 'http://192.240.120.74//mp43/241210.mp4?st=F_xLKurZq3bvCBEYJCgO8w&e=1509667978',
        );
        $timeOut = 180;
        $sleepTime = 10;
        $tot = count($urls);

        if (preg_match('/WIN/', PHP_OS)) {
            $savePath = 'F:\videos';
        } else {
            $savePath = '/mnt/videos';
        }
        if (is_dir($savePath) === false) {
            mkdir($savePath, 0755, true);
        }
        foreach ($urls as $k => $url) {
            $this->info("{$k}/{$tot} url {$url}");

            $saveFile = $savePath . DIRECTORY_SEPARATOR . date('Ymd') . DIRECTORY_SEPARATOR . md5($url) . '.mp4';
            if (file($saveFile)) {
                $this->info("{$url} is already save");
                continue;
            }
            $ip = getRandIp();
            $fp = fopen($saveFile, 'wb');
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_NOBODY, false);
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeOut);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'client-ip: ' . $ip,
                'x-forwarded-for: ' . $ip,
                'Connection' => 'keep-alive',
                'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.87 Safari/537.36'
            ));

            curl_exec($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);
            fclose($fp);
            if ($info['http_code'] == '200') {
                $this->info("save success");
                print_r($info);
            } else {
                $this->info("save fail");
                dd($info);
            }
            sleep($sleepTime);
        }
    }
}
