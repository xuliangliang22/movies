<?php

namespace App\Console\Commands\Caiji\Movie;

use Illuminate\Console\Command;
use App\Console\Commands\Mytraits\DedeLogin;
use App\Console\Commands\Mytraits\Common;
use App\Console\Commands\Mytraits\BaiduStatus;
use App\Console\Commands\Mytraits\Ygdy8;

class Ygdy8List extends Command
{
    use Common;
    use DedeLogin;
    use BaiduStatus;
    use Ygdy8;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'caiji:movie_ygdy8_list {page_start} {page_tot} {typeid}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '采集阳光电影吧下面的所有影视内容';

    protected $typeId;
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
        //图片保存网站根路径,随着环境的改变这里需要改变
        $urls = [
            //大陆电影
            '13' => 'http://www.ygdy8.com/html/gndy/china/list_4_2.html',
            //大陆电视剧
            '17' => 'http://www.ygdy8.net/html/tv/hytv/list_71_2.html',
            //欧美电影
            '15' => 'http://www.ygdy8.net/html/gndy/oumei/list_7_2.html',
            //欧美电视剧
            '19' => 'http://www.ygdy8.net/html/tv/oumeitv/list_9_2.html',
            //日韩电影
            '14' => 'http://www.ygdy8.net/html/gndy/rihan/list_6_2.html',
            //日韩电视剧
            '18' => 'http://www.ygdy8.net/html/tv/rihantv/list_8_2.html',
        ];

        $start = $this->argument('page_start');
        $pageTot = $this->argument('page_tot');
        $this->typeId = $this->argument('typeid');

        $this->info('【'.date('Y-m-d H:i:s').'】 ygdy8 list start typeid '.$this->typeId);
        //列表
        $this->movieList($start,$pageTot,$urls[$this->typeId]);
        $this->info('【'.date('Y-m-d H:i:s').'】 ygdy8 list end typeid '.$this->typeId);
    }
}
