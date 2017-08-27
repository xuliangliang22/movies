<?php

namespace App\Console\Commands\Caiji;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use QL\QueryList;

class Baike extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'caiji:baike {db_name}{table_name}{type_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '得到百科第一条内容';

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
        $dbName = $this->argument('db_name');
        $tableName = $this->argument('table_name');
        $typeId = $this->argument('type_id');

        $minId = 0;
        $take = 10;

        do {
            $movies = DB::connection($dbName)->table($tableName)->where('id', '>', $minId)->where('typeid', $typeId)->whereNull('body')->take($take)->get();
            $tot = count($movies);
            if ($tot < 1) {
                //cli
                $this->error('no content to baike');
                break;
            }

            foreach ($movies as $key=>$value){
                $minId = $value->id;
                $this->info("{$key}/{$tot} -- {$value->title}");

                $url = 'https://baike.baidu.com/search?word=' . urlencode($value->title) . '&pn=0&rn=0&enc=utf8';
                $data = QueryList::Query($url,array(
                    'body'=>array('.search-list .result-summary:first()','text'),
                ))->data;
                if(isset($data[0]) === false){
                    continue;
                }
                //更新数据库
                $rest = DB::connection($dbName)->table($tableName)->where('id',$value->id)->update(['body'=>$data[0]['body']]);
                if($rest){
                    $this->info("baike save success !");
                }else{
                    $this->error("baike save fail !");
                }
            }
        } while ($tot > 0);
    }
}

