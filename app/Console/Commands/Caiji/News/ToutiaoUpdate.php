<?php

namespace App\Console\Commands\Caiji\News;

use Illuminate\Console\Command;

class ToutiaoUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'caiji:news_toutiao';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '采集头条首页信息';

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
    }
}
