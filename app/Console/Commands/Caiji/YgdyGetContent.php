<?php

namespace App\Console\Commands\Caiji;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use QL\QueryList;

class YgdyGetContent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'caiji:ygdy_get_content {db_name} {table_name} {type_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '得到阳光电影网的内容页信息';

    public $curl;

    public $dbName;
    public $tableName;
    public $typeId;

    public $commandLogsFile;

    public $minId;

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
        $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'curl' . DIRECTORY_SEPARATOR . 'curl.php';
        require_once $path;
        $this->curl = new \curl();

        $this->dbName = $this->argument('db_name');
        $this->tableName = $this->argument('table_name');
        $this->typeId = $this->argument('type_id');

        $this->commandLogsFile = config('qiniu.qiniu_data.command_logs_file');

        $this->minId = 0;

        $this->getContent();

    }

    /**
     * 采信内容页
     * @param  $type 1.movie(下载电影) 2.other(只下载链接)
     */
    public function getContent()
    {
        try {
            $take = 10;
            do {
                $arc = DB::connection($this->dbName)->table($this->tableName)->where('id', '>', $this->minId)->where('is_con', -1)->where('typeid', $this->typeId)->take($take)->get();
                $tot = count($arc);

                foreach ($arc as $key => $value) {
                    $this->minId = $value->id;
                    //cli
                    if(config('qiniu.qiniu_data.is_cli')) {
                        $this->info("{$key}/{$tot} id is {$value->id} url is {$value->con_url}");
                    }

                    //得到保存的数组
                    $conSaveArr = $this->getConSaveArr($value->con_url);
                    if(empty($conSaveArr)){
                        continue;
                    }
                    if ($value->is_update == -1) {
                        unset($conSaveArr['litpic']);
                    }
                    //cli
                    if(config('qiniu.qiniu_data.is_cli')) {
                        print_r($conSaveArr);
                    }
                    $rest = DB::connection($this->dbName)->table($this->tableName)->where('id', $value->id)->update($conSaveArr);
                    if ($rest) {

                        DB::connection($this->dbName)->table($this->tableName)->where('id', $value->id)->update(['is_con' => 0]);
                        if(config('qiniu.qiniu_data.is_cli')) {
                            $this->info('save con success');
                        }
                    } else {
                        if(config('qiniu.qiniu_data.is_cli')) {
                            $this->error('save con fail');
                        }
                    }
                }
            } while ($tot > 0);
        } catch (\ErrorException $e) {
            $this->contentExcRun($e->getMessage(),$e->getFile(),$e->getLine());
        } catch (\Exception $e) {
            $this->contentExcRun($e->getMessage(),$e->getFile(),$e->getLine());
        }
        //cli
        if(config('qiniu.qiniu_data.is_cli')) {
            $this->info('save con end');
        }
        $this->minId = 0;
        //删除下载链接为空的数据
        DB::connection($this->dbName)->table($this->tableName)
            ->where('is_post', '=', -1)
            ->where(function ($query) {
                $query->whereNull('down_link')
                    ->orWhere('down_link', '');
            })->delete();

        //判断链接是否有空值,如果有空值则说明,编码没有替换好
        $isDownLinkNull = DB::connection($this->dbName)->table($this->tableName)
            ->where('typeid', $this->typeId)
            ->where(function ($query) {
                $query->whereNull('down_link')
                    ->orWhere('down_link', '');
            })
            ->get();
        if (count($isDownLinkNull) > 0) {
            $command = "下载链接为空,再次进行下载链接的采集 \n";
            file_put_contents($this->commandLogsFile, $command, FILE_APPEND);
            $this->getContent();
        }

        //logs,判断内容是否为空
        $isContent = DB::connection($this->dbName)->table($this->tableName)
            ->where('typeid', $this->typeId)
            ->where(function ($query) {
                $query->where('is_post', -1)
                    ->orWhere('is_update', -1);
            })
            ->get();
        if (count($isContent) < 1) {
            $command = "内容页为空,退出采集 \n";
            file_put_contents($this->commandLogsFile, $command, FILE_APPEND);
            exit;
        }
    }


    /**
     * 内容页采集异常处理方法
     */
    public function contentExcRun($message,$file,$line)
    {
        $this->info('get content error exception ' . $message.' file is '.$file .' line is '.$line);
        $this->getContent();
    }

    /**
     * 只得到下载链接
     */
    public function getConSaveArr($url)
    {
        $restArr = [];
        $this->curl->add()->opt_targetURL($url)->done();
        $this->curl->run();
        $data = $this->curl->getAll();
        $this->curl->free();
        $data = $data['body'];
        $data = mb_convert_encoding($data, 'utf-8', 'gbk,gb2312,big5,ASCII');

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
