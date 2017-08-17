<?php

namespace App\Console\Commands\Ca2722;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class Litpic extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ca2722:litpic';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '修改litpic名称';

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
        $this->modifyBodyPic();

    }


    /**
     * 更新缩略图地址
     */
    public function modifyLitpic()
    {
        $minId = 599;
        $take = 10;
        do
        {
            $arc = DB::connection('ca2722')->table('dede_archives')->where('id','>',$minId)->where('litpic','not like','%imageslim%')->orderBy('id','asc')->take($take)->get();
//            dd($arc);
            $tot = count($arc);
            foreach ($arc as $key=>$value)
            {
                $this->info("{$key}/{$tot} id is {$value->id}");
                $minId = $value->id;
                if(empty($value->litpic)){
                    continue;
                }
                $litpic = $value->litpic.'?imageslim';
                echo "===============================\n";
                echo $litpic."\n";
                echo "===============================\n";
                //更新数据库
                $rest = DB::connection('ca2722')->table('dede_archives')->where('id',$value->id)->update(['litpic'=>$litpic]);
                if($rest){
                    $this->info("success");
                }else{
                    $this->error("fail");
                }
            }
        }while($tot > 0);

        $this->info('end');

    }


    /**
     * 更新内容页的图片链接
     */
    public function modifyBodyPic()
    {
        $minId = 0;
        $take = 10;
        do
        {
            $arc = DB::connection('ca2722')->table('dede_addonarticle')->where('aid','>',$minId)->where('typeid','<>',23)->orderBy('aid','asc')->take($take)->get();
//            dd($arc);
            $tot = count($arc);
            foreach ($arc as $key=>$value)
            {
                $this->info("{$key}/{$tot} id is {$value->aid}");
                $minId = $value->aid;

                //得到所有图片链接
                $marest = preg_match_all('/<img\s*src=["\'](.*?)["\'][^>]*>/is', $value->body, $matchs);
                //没有找到图片
                if ($marest === 0) {
                    continue;
                }
//                dd($matchs);
                $replace = array();
                foreach ($matchs[1] as $k=>$v){
                    $replace[] = $v.'?imageslim';
                }
                $body = str_replace($matchs[1],$replace,$value->body);
                $body = preg_replace('/<img(.*)src=""(.*)>/isU','',$body);
                echo "===============================\n";
                echo $body."\n";
                echo "===============================\n";
                //更新数据库
                $rest = DB::connection('ca2722')->table('dede_addonarticle')->where('aid',$value->aid)->update(['body'=>$body]);
                if($rest){
                    $this->info("success");
                }else{
                    $this->error("fail");
                }
            }
        }while($tot > 0);

        $this->info('end');


    }
}
