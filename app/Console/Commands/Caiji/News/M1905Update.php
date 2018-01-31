<?php

namespace App\Console\Commands\Caiji\News;

use Illuminate\Console\Command;
use App\Console\Commands\Mytraits\DedeLogin;
use App\Console\Commands\Mytraits\Common;
use App\Console\Commands\Mytraits\BaiduStatus;
use App\Console\Commands\Mytraits\M1905;

class M1905Update extends Command
{
    use Common;
    use DedeLogin;
    use BaiduStatus;
    use M1905;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'caiji:news_m1905_update {page_start}{page_tot}{type_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '更新采集m1905影评信息';

    public $typeId;
    public $channelId = 1;

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
        $pageStart = $this->argument('page_start');
        $pageTot = $this->argument('page_tot');
        $this->typeId = $this->argument('type_id');

        $this->info('【' . date('Y-m-d H:i:s') . '】 news m1905 start');

        //typeid 24
        $url = 'http://www.1905.com/api/content/index.php?m=converged&a=comment&page=%s&pagesize=20';
        $this->movieList($pageStart, $pageTot, $url);
        $this->getContent();
        //下载图片
        $this->litpicDownload();
        //提交到dede后台
        $this->dedeartPost();
        $this->info('【' . date('Y-m-d H:i:s') . '】 news m1905 end');
    }
}

