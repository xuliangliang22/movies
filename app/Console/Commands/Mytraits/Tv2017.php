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
    public $listNum;

    /**
     * 保存电影电视剧列表页
     */
    public function movieList($start, $pageTot, $baseListUrl)
    {
        $languages = config('qiniu.qiniu_data.dede_languages');
        try {
            $url = substr($baseListUrl, 0, strrpos($baseListUrl, '.'));
            for ($i = $start; $i <= $pageTot; $i++) {
                if ($i == 1) {
                    $listUrl = $url . '.html';
                } else {
                    $listUrl = $url . '_' . $i . '.html';
                }
                $list = $this->getList($listUrl);

                //保存进数据库中去
                foreach ($list as $key => $value) {
                    shuffle($languages);

                    $rest = DB::connection($this->dbName)->table($this->tableName)->where('typeid', $this->typeId)->where('title_hash', md5(trim($value['title'])))->first();
                    if ($rest) {
                        //如果存在则退出
                        continue;
                    } else {
                        //不是更新的时候判断名字的重复
                        $listSaveArr = [
                            'title' => trim($value['title']),
                            'title_hash' => md5(trim($value['title'])),
                            'con_url' => $value['con_url'],
                            'typeid' => $this->typeId,
                            'body' => $value['body'],
                            'actors' => str_replace('/', ',', $value['actors']),
                            'litpic' => $value['litpic'],
                            'grade' => mt_rand(0, 10),
                            'is_douban' => 0,
                            //
//                            'director'=>'',
                            'myear' => date('Y'),
                            'lan_guage' => implode(',', array_slice($languages, 0, rand(1, 2))),
                            'types' => '家庭,伦理,社会,爱情',
                        ];
                        $rs = DB::connection($this->dbName)->table($this->tableName)->insert($listSaveArr);
                        if ($rs) {
                            $this->listNum++;
                        }
                    }
                }
            }
            //$this->info('list save end');
        } catch (\ErrorException $e) {
            $message = 'jindian movies error exception ' . $e->getMessage() . PHP_EOL;
            $this->error($message);
            //日志
            if ($this->isCommandLogs === true) {
                file_put_contents($this->commandLogsFile, $message, FILE_APPEND);
            }
            return;
        } catch (\Exception $e) {
            $message = 'jindian movies exception ' . $e->getMessage() . PHP_EOL;
            $this->error($message);
            //日志
            if ($this->isCommandLogs === true) {
                file_put_contents($this->commandLogsFile, $message, FILE_APPEND);
            }
            return;
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
            $item['actors'] = preg_replace('/&#\d+;/', '', $item['actors']);
            $item['title'] = preg_replace('/&#\d+;/', '', $item['title']);
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
        $minId = 0;
        $take = 10;
        $message = null;

        do {
            $arc = DB::connection($this->dbName)->table($this->tableName)->select('id', 'con_url')->where('is_con', -1)->where('typeid', $this->typeId)->where('id', '>', $minId)->take($take)->get();
            $tot = count($arc);

            foreach ($arc as $key => $value) {
                $minId = $value->id;
                $message = date('Y-m-d H:i:s') . "{$key}/{$tot} id is {$value->id} url is {$value->con_url}" . PHP_EOL;
                $this->info($message);
                //得到保存的数组
                $conSaveArr = $this->getConSaveArr($value->con_url);
                if (!$conSaveArr) {
                    DB::connection($this->dbName)->table($this->tableName)->where('id', $value->id)->delete();
                    continue;
                }
                $conSaveArr = implode(',', $conSaveArr);

                $rest = DB::connection($this->dbName)->table($this->tableName)->where('id', $value->id)->update(['down_link' => $conSaveArr, 'is_con' => 0]);
                if ($rest) {
                    $message .= '经典电影内容保存成功!';
                    $this->info($message);
                } else {
                    $message .= '经典电影内容保存失败!';
                    $this->info($message);
                }
                //日志
                if ($this->isCommandLogs === true) {
                    file_put_contents($this->commandLogsFile, $message, FILE_APPEND);
                }
            }
        } while ($tot > 0);
        $message = "经典电影 content save end ";
        //日志
        if ($this->isCommandLogs === true) {
            file_put_contents($this->commandLogsFile, $message, FILE_APPEND);
        }
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

