<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/2 0002
 * Time: 下午 3:55
 */
namespace App\Console\Commands\Mytraits;

use DiDom\Query;
use Illuminate\Support\Facades\DB;
use QL\QueryList;

trait Tv2017
{
    public $curl;
    public $listInfo;
    public $listNum;
    public $contentNum;

    public function MovieInit()
    {
        if (empty($this->curl)) {
            $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'curl' . DIRECTORY_SEPARATOR . 'curl.php';
            require_once $path;
            $this->curl = new \curl();
        }
    }

    /**
     * 保存电影电视剧列表页
     */
    public function movieList($start, $pageTot, $baseListUrl, $isNew = false)
    {
        try {

            $url = substr($baseListUrl, 0, strrpos($baseListUrl, '.'));

            for ($i = $start; $i <= $pageTot; $i++) {
                $this->listInfo = $i . '-' . $pageTot . '-' . $baseListUrl . '-' . $isNew;
                //$this->info("this is page {$i}");
                if ($i == 1) {
                    $listUrl = $url . '.html';
                } else {
                    $listUrl = $url . '_' . $i . '.html';
                }
                $list = $this->getList($listUrl);

                //保存进数据库中去
                foreach ($list as $key => $value) {
//                    dd($value);
                    $rs = null;
                    $rest = DB::connection($this->dbName)->table($this->tableName)->where('typeid', $this->typeId)->where('title_hash', md5($value['title']))->first();
                    if ($rest) {
                        //如果存在则退出
                        continue;
                    } else {
                        //不是更新的时候判断名字的重复
                        $isNewType = 'save';
                        $listSaveArr = [
                            'title' => trim($value['title']),
                            'title_hash' => md5(trim($value['title'])),
                            'con_url' => $value['con_url'],
                            'typeid' => $this->typeId,
                            'body' => $value['body'],
                            'actors' => str_replace('/', ',', $value['actors']),
                            'litpic' => $value['litpic'],
                            'grade' => mt_rand(0, 10),
                            'is_body' => 0,
                            'is_douban' => 0,
                        ];
//                        dd($listSaveArr);
                        $rs = DB::connection($this->dbName)->table($this->tableName)->insert($listSaveArr);
                    }

                    if ($rs) {
                        $this->listNum++;
                        //$this->info($value['title'] . ' list ' . $isNewType . ' success');
                    } else {
                        //$this->info($value['title'] . ' list ' . $isNewType . ' fail');
                    }
                }
            }
            //$this->info('list save end');
        } catch (\ErrorException $e) {
            $this->info('jindian movies error exception ' . $e->getMessage() . "\n");
            $listInfoArr = explode('-', $this->listInfo);
            if ($listInfoArr[1] - $listInfoArr[0] < 2) {
                return;
            } else {
                $this->movieList($listInfoArr[0], $listInfoArr[1], $listInfoArr[2], $listInfoArr[3]);
            }
        } catch (\Exception $e) {
            $this->info('jindian movies exception ' . $e->getMessage() . "\n");
            $listInfoArr = explode('-', $this->listInfo);
            if ($listInfoArr[1] - $listInfoArr[0] < 2) {
                return;
            } else {
                $this->movieList($listInfoArr[0], $listInfoArr[1], $listInfoArr[2], $listInfoArr[3]);
            }
        }
    }

    /**
     * 采集列表页
     */
    public function getList($url)
    {
        $host = 'http://www.2015tt.com';

        $content = QueryList::Query($url, array(
            'title' => array('h5 a', 'text'),
            'litpic' => array('.play-pic img', 'src'),
            'con_url' => array('h5 a', 'href'),
            'actors' => array('.actor', 'text', '-em'),
            'body' => array('.plot', 'text', '-em'),
        ), '#contents li', 'utf-8', 'gbk', true)->getData(function ($item) use ($host) {
            $item['litpic'] = $host . $item['litpic'];
            $item['con_url'] = $host . $item['con_url'];
            return $item;
        });
        return $content;
    }


    /**
     * 采信内容页
     * @param  $type 1.movie(下载电影) 2.other(只下载链接)
     */
    public function getContent()
    {
        try {
            do {
                $take = 10;
                $arc = DB::connection($this->dbName)->table($this->tableName)->where('id', '>', $this->aid)->where('is_con', -1)->where('typeid', $this->typeId)->take($take)->orderBy('id')->get();
//                dd($arc);
                $tot = count($arc);

                foreach ($arc as $key => $value) {
                    $this->aid = $value->id;
                    //$this->info("{$key}/{$tot} id is {$value->id} url is {$value->con_url}");

                    //得到保存的数组
//                    $conSaveArr = $this->getConSaveArr($value->con_url);
                    $conSaveArr = $this->getConSaveArr($value->con_url);
                    if (!$conSaveArr) {
                        DB::connection($this->dbName)->table($this->tableName)->where('id', $value->id)->update(['is_con' => 0]);
                        continue;
                    }
                    $conSaveArr = implode(',', $conSaveArr);
//                    dd($conSaveArr);
//                    print_r($conSaveArr) . "\n";

                    $rest = DB::connection($this->dbName)->table($this->tableName)->where('id', $value->id)->update(['down_link' => $conSaveArr]);
                    if ($rest) {
                        $this->contentNum++;
                        DB::connection($this->dbName)->table($this->tableName)->where('id', $value->id)->update(['is_con' => 0]);
                        //$this->info('save con success');
                    } else {
                        //$this->error('save con fail');
                    }
                }
            } while ($tot > 0);
        } catch (\ErrorException $e) {
            $this->info('get content error exception ' . $e->getMessage());
            $this->getContent($this->aid);
        } catch (\Exception $e) {
            $this->info('get content exception ' . $e->getMessage());
            $this->getContent($this->aid);
        }
        //电视剧需要更新,还要再添加一个字段
        //$this->info('save con end');
        $this->aid = 0;
        //删除下载链接为空的数据
        DB::connection($this->dbName)->table($this->tableName)->whereNull('down_link')->delete();
    }


    /**
     * @param $url 内容页的网址链接
     */
    public function getConSaveArr($url)
    {
        $content = QueryList::Query($url, array(
            'down_link' => array('.down_url', 'value'),
        ), '', 'utf-8', 'gbk', true)->getData(function ($item) {
            return $item['down_link'];
        });
        return $content;
    }
}

