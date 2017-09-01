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
        $url = 'http://www.ygdy8.net/html/tv/rihantv/20170606/54163.html';

        require_once  app_path('Console/Commands/curl').'/curl.php';
        $curl = new \curl();
        $curl->add()->opt_targetURL($url)->done();
        $curl->run();
        $info = $curl->getAll();
        $info = $info['body'];

        $info = mb_convert_encoding($info,'utf-8','gbk,gb2312,big5,ASCII');
        echo $info;

    }
}
