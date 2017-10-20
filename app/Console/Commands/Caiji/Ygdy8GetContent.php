<?php

namespace App\Console\Commands\Caiji;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use QL\QueryList;
use App\Console\Commands\Mytraits\Common;

class Ygdy8GetContent extends Command
{
    use Common;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'caiji:ygdy8_get_content {type_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '得到阳光电影网的内容页信息';

    public $typeId;

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
        $this->typeId = $this->argument('type_id');
        $this->getContent();
    }

    /**
     * 采信内容页
     * @param  $type 1.movie(下载电影) 2.other(只下载链接)
     */
    public function getContent($minId = 0)
    {
        $take = 10;
        $message = null;

        try {
            do {
                $arc = DB::connection($this->dbName)->table($this->tableName)->select('id','con_url','is_update')->where('id', '>', $minId)->where('is_con', -1)->where('typeid', $this->typeId)->take($take)->get();
                $tot = count($arc);

                foreach ($arc as $key => $value) {
                    $minId = $value->id;
                    $message = date('Y-m-d H:i:s')." {$key}/{$tot} id is {$value->id} url is {$value->con_url}".PHP_EOL;
                    $this->info($message);

                    //得到保存的数组
                    $conSaveArr = $this->getConSaveArr($value->con_url);
                    if(empty($conSaveArr)){
                        continue;
                    }
                    if ($value->is_update == -1) {
                        unset($conSaveArr['litpic']);
                    }
                    $conSaveArr['is_con'] = 0;
                    $rest = DB::connection($this->dbName)->table($this->tableName)->where('id', $value->id)->update($conSaveArr);
                    if ($rest) {
                        $message .= "con save success !!".PHP_EOL;
                        $this->info($message);
                    } else {
                        $message .= "con save fail !!".PHP_EOL;
                        $this->info($message);
                    }
                    //保存日志
                    if($this->isCommandLogs === true){
                        file_put_contents($this->commandLogsFile,$message,FILE_APPEND);
                    }
                }
            } while ($tot > 0);
        } catch (\ErrorException $e) {
            $message = "con error exception ".$e->getMessage().PHP_EOL;
            $this->error($message);
            //保存日志
            if($this->isCommandLogs === true){
                file_put_contents($this->commandLogsFile,$message,FILE_APPEND);
            }
            $this->getContent($minId);
        } catch (\Exception $e) {
            $message = "con exception ".$e->getMessage().PHP_EOL;
            $this->error($message);
            //保存日志
            if($this->isCommandLogs === true){
                file_put_contents($this->commandLogsFile,$message,FILE_APPEND);
            }
            $this->getContent($minId);
        }
        $message = "con save end !!";
        $this->info($message);
        //保存日志
        if($this->isCommandLogs === true){
            file_put_contents($this->commandLogsFile,$message,FILE_APPEND);
        }

        //删除新增的数据is_con = -1,is_update = -1继续执行
        DB::connection($this->dbName)->table($this->tableName)->where('typeid',$this->typeId)->where('is_con', -1)->where('is_update',0)->delete();
        //isupdate,更新已经存在数据
        $this->conUpdate();
    }


    /**
     * 判断已经更新的电视节目下载链接是否已经下载
     */
    public function conUpdate()
    {
        $message = null;
        $tot = 0;
        do
        {
            $data = DB::connection($this->dbName)->table($this->tableName)->select('id','con_url')->where('typeid',$this->typeId)->where('is_con', -1)->where('is_update',-1)->get();
            if(count($data) < 1 || $tot > 3){
                break;
            }

            foreach ($data as $key=>$value){
                $message = date('Y-m-d H:i:s')."再一次is_update下载链接更新".PHP_EOL;
                $this->info($message);
                $con = $this->getConSaveArr($value->con_url);
                if($con){
                    //更新数据库
                    $rest = DB::connection($this->dbName)->table($this->tableName)->where('id',$value->id)->update([
                        'down_link'=>$con['down_link'],
                        'is_con' => 0,
                        'is_update' => 0,
                    ]);
                    if($rest)
                    {
                        $message .= "更新下载链接成功!";
                        $this->info($message);
                    }else{
                        $message .= "更新下载链接失败!";
                        $this->error($message);
                    }
                    //保存日志
                    if($this->isCommandLogs === true){
                        file_put_contents($this->commandLogsFile,$message,FILE_APPEND);
                    }
                }
            }
            $tot ++;
        }while(true);
        $message = 'is_update再次更新完成!!';
        //删除全部不成功的下载链接
        DB::connection($this->dbName)->table($this->tableName)->where('typeid',$this->typeId)->where('is_con', -1)->orWhere('is_update',-1)->delete();
        //保存日志
        if($this->isCommandLogs === true){
            file_put_contents($this->commandLogsFile,$message,FILE_APPEND);
        }
    }

    /**
     * 只得到下载链接
     */
    public function getConSaveArr($url)
    {
        $restArr = [];
        $this->curl->add()->opt_targetURL($url)->done();
        $this->curl->run();
        $data = $this->curl->get();
        $this->curl->free();
        $data = mb_convert_encoding($data, 'utf-8', 'gbk,gb2312,big5,ASCII,unicode,utf-16,ISO-8859-1');
        $data = preg_replace('/<meta(.*?)>/is','',$data);

        $content = QueryList::Query($data, array(
            'litpic' => array('img:first()', 'src'),
        ), '.co_content8')->getData(function ($item) {
            if (strlen($item['litpic']) > 250) {
                $item['litpic'] = '';
            }
            return $item['litpic'];
        });

        $content2 = QueryList::Query($data, array(
            'down_link' => array('', 'href'),
        ), '#Zoom table a')->getData(function ($item) {
            return $item['down_link'];
        });

        if (empty($content) === false) {
            $restArr['litpic'] = $content[0];
        }
        if (empty($content2) === false) {
            $restArr['down_link'] = implode(',', $content2);
        }

        return $restArr;
    }
}
