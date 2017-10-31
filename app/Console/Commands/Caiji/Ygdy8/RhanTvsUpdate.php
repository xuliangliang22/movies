<?php

namespace App\Console\Commands\Caiji\Ygdy8;

use Illuminate\Console\Command;
use App\Console\Commands\Mytraits\Ygdy8;
use App\Console\Commands\Mytraits\DedeLogin;
use App\Console\Commands\Mytraits\Common;

class RhanTvsUpdate extends Command
{
    use Common;
    use Ygdy8;
    use DedeLogin;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'caiji:ygdy8_rhantvs_update {page_start}{page_tot}{type_id}{--queue=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '跟踪采集阳光电影8的日韩电视剧信息';

    public $typeId;
    public $channelId = 17;


    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->initBegin();

    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $queueName = $this->option('queue');
        $pageStart = $this->argument('page_start');
        $pageTot = $this->argument('page_tot');
        $this->typeId = $this->argument('type_id');

        // max_page_tot = 35 typeid = 18
        $url = 'http://www.ygdy8.net/html/tv/rihantv/list_8_2.html';
        //得到这条命令logs
        $message = date('Y-m-d H:i:s') . "\ncaiji:ygdy8_dalumovies_update {$pageStart} {$pageTot} {$this->typeId} {$queueName} \n the link is {$url} \n";
        $this->info($message);

        //得到所有的列表页
        //olist任务调度需要用到的参数
        if ($queueName == 'all' || $queueName == 'list' || $queueName == 'olist') {
            $this->movieList($pageStart, $pageTot, $url);
            //logs
            $message .= "列表页采集完成,一共 {$this->listNum} 条! \n";
            $this->info($message);
            //列表页为空
            if($this->listNum < 1){
                $message .= "列表页为空,结束! ".PHP_EOL;
                $this->info($message);
            }
            //日志
            if($this->isCommandLogs === true) {
                file_put_contents($this->commandLogsFile, $message, FILE_APPEND);
            }
            if ($queueName == 'list' || $this->listNum < 1) {
                exit;
            }
        }

        //其余剩下的操作
        // php artisan caiji:ygdy8_get_content 18(type_id)
        $this->runOther($queueName);
    }
}



