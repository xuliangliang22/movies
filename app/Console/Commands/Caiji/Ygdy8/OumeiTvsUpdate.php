<?php

namespace App\Console\Commands\Caiji\Ygdy8;

use Illuminate\Console\Command;
use App\Console\Commands\Mytraits\Ygdy8;
use App\Console\Commands\Mytraits\DedeLogin;

class OumeiTvsUpdate extends Command
{
    use Ygdy8;
    use DedeLogin;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'caiji:ygdy8_oumeitvs_update {page_start}{page_tot}{type_id}{--queue=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '更新阳光电影8的欧美电视剧信息';

    public $typeId;
    public $channelId = 17;
    public $qiniuDir = 'tvs/imgs';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->MovieInit();
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

        // max_page_tot = 13 typeid = 19
        $url = 'http://www.ygdy8.net/html/tv/oumeitv/list_9_2.html';
        //得到这条命令logs
        if ($this->isCommandLogs === true) {
            $command = "=========================================\n";
            $command .= date('Y-m-d H:i:s') . "\ncaiji:ygdy8_oumeitvs_update  {$pageStart} {$pageTot} {$this->typeId}{$queueName} \n the link is {$url} \n";
            file_put_contents($this->commandLogsFile, $command, FILE_APPEND);
        }

        //得到所有的列表页
        //olist任务调度需要用到的参数
        if ($queueName === 'all' || $queueName == 'list' || $queueName == 'olist') {
            //logs
            if ($this->isCommandLogs === true) {
                $command = "开始采集列表页\n";
                file_put_contents($this->commandLogsFile, $command, FILE_APPEND);
            }

            $this->movieList($pageStart, $pageTot, $url, true);

            //logs
            if (empty($this->listNum)) {
                $this->listNum = 0;
            }
            echo "列表页采集完成,一共 {$this->listNum} 条! \n";
            if ($this->isCommandLogs === true) {
                $command = "列表页采集完成,一共 {$this->listNum} 条! \n\n";
                file_put_contents($this->commandLogsFile, $command, FILE_APPEND);
            }
            if ($queueName == 'list') {
                exit;
            }

            //
            if ($this->listNum < 1) {
                //logs
                if ($this->isCommandLogs === true) {
                    $command = "列表页为空,结束! \n\n";
                    file_put_contents($this->commandLogsFile, $command, FILE_APPEND);
                }
                exit;
            }
        }

        //其余剩下的操作
        // php artisan caiji:ygdy8_get_content 19(type_id)
        $keyWordSuffix = '电视剧';
        $this->runOther($queueName,$keyWordSuffix);

    }
}



