<?php

namespace App\Console\Commands\Caiji;

use Illuminate\Console\Command;

class News extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'caiji:news';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '采集新闻内容';

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
        $url = 'http://www.y3600.com/news/index_2.html';
    }

    public function getList($url)
    {

    }
}
