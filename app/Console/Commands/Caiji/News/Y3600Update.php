<?php

namespace App\Console\Commands\Caiji\News;

use Illuminate\Console\Command;
use App\Console\Commands\Mytraits\DedeLogin;
use App\Console\Commands\Mytraits\NewsY3600;
use App\Console\Commands\Mytraits\Common;
use App\Console\Commands\Mytraits\BaiduStatus;

class Y3600Update extends Command
{
    use Common;
    use DedeLogin;
    use BaiduStatus;
    use NewsY3600;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'caiji:news_y3600_update {page_start}{page_tot}{type_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '更新采集y3600新闻信息';

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
        //
        $pageStart = $this->argument('page_start');
        $pageTot = $this->argument('page_tot');
        $this->typeId = $this->argument('type_id');

        $this->info('【'.date('Y-m-d H:i:s').'】 news y3600 start');
        //typeid = 22
        $url = 'http://www.y3600.com/news/index.html';
        $this->movieList($pageStart, $pageTot, $url);
        $this->getContent();
        //下载图片
        $this->litpicDownload();
        //提交到dede后台
        $this->dedeartPost();
        $this->info('【'.date('Y-m-d H:i:s').'】 news y3600 end');
    }
}

