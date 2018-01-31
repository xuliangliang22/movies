<?php

namespace App\Console\Commands\Caiji\Movie;

use Illuminate\Console\Command;
use App\Console\Commands\Mytraits\DedeLogin;
use App\Console\Commands\Mytraits\Common;
use App\Console\Commands\Mytraits\BaiduStatus;
use App\Console\Commands\Mytraits\Ygdy8;

class Ygdy8Content extends Command
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
    protected $signature = 'caiji:movie_ygdy8_content {typeid}';

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
        $this->typeId = $this->argument('typeid');

        $this->info('【'.date('Y-m-d H:i:s').'】 ygdy8 content start typeid '.$this->typeId);
        //内容
        $this->getContent();
        $this->info('【'.date('Y-m-d H:i:s').'】 ygdy8 content end typeid '.$this->typeId);
    }
}
