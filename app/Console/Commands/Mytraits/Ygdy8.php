<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/2 0002
 * Time: 下午 3:55
 */
namespace App\Console\Commands\Mytraits;

use Illuminate\Support\Facades\DB;
use QL\QueryList;

trait Ygdy8
{
    public $curl;
    public $listInfo;
    public $listNum;
    public $contentNum;

    public function MovieInit()
    {
        $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'curl' . DIRECTORY_SEPARATOR . 'curl.php';
        require_once $path;
        $this->curl = new \curl();
    }

    /**
     * 保存电影电视剧列表页
     */
    public function movieList($start, $pageTot, $baseListUrl, $isNew = false)
    {
        try {

            if (strrpos($baseListUrl, '_') !== false) {
                $url = substr($baseListUrl, 0, strrpos($baseListUrl, '_'));
            } else {
                $url = substr($baseListUrl, 0, strrpos($baseListUrl, '/')) . '/';
            }

            //取出最大的时间
            $maxTime = DB::connection($this->dbName)->table($this->tableName)->where('typeid', $this->typeId)->where('is_post', 0)->max('m_time');

            for ($i = $start; $i <= $pageTot; $i++) {
                $this->listInfo = $i . '-' . $pageTot . '-' . $baseListUrl . '-' . $isNew;
                //cli
                if (config('qiniu.qiniu_data.is_cli')) {
                    $this->info("this is page {$i} maxtime {$maxTime}");
                }
                if ($i == 1) {
                    $listUrl = substr($url, 0, strrpos($url, '/')) . '/index.html';
                } else {
                    $listUrl = $url . '_' . $i . '.html';
                }
                $list = $this->getList($listUrl);
//            dd($list);

                //保存进数据库中去
                foreach ($list as $key => $value) {
                    $rs = null;
                    $rest = DB::connection($this->dbName)->table($this->tableName)->where('typeid', $this->typeId)->where('title_hash', md5($value['title']))->first();
                    if ($rest) {
                        if ($isNew === true) {
                            $isNewType = 'update';
                            //判断时间,更新的时候不需要判断名字的重复
                            if (strtotime($maxTime) >= strtotime($value['m_time'])) {
//                                break 2;
                                continue;
                            }
                            //更新这样记录的下载链接,将is_con=-1,down_link = '',is_update=-1//default 0
                            $rs = DB::connection($this->dbName)->table($this->tableName)->where('id', $rest->id)->update(['down_link' => '', 'm_time' => $value['m_time'], 'is_con' => -1, 'is_update' => -1]);

                        } else {
                            continue;
                        }
                    } else {
                        //不是更新的时候判断名字的重复
                        $isNewType = 'save';
                        $listSaveArr = [
                            'title' => trim($value['title']),
                            'title_hash' => md5(trim($value['title'])),
                            'con_url' => $value['con_url'],
                            'm_time' => $value['m_time'],
                            'typeid' => $this->typeId,
                        ];
//                        dd($listSaveArr);
                        $rs = DB::connection($this->dbName)->table($this->tableName)->insert($listSaveArr);
                    }

                    if ($rs) {
                        $this->listNum++;
                        //cli
                        if (config('qiniu.qiniu_data.is_cli')) {
                            $this->info($value['title'] . ' list ' . $isNewType . ' success');
                        }
                    } else {
                        //cli
                        if (config('qiniu.qiniu_data.is_cli')) {
                            $this->info($value['title'] . ' list ' . $isNewType . ' fail');
                        }
                    }
                }
            }
            $this->info('list save end');
        } catch (\ErrorException $e) {
            $this->info('list error exception ' . $e->getMessage());
            $listInfoArr = explode('-', $this->listInfo);
            if ($listInfoArr[1] - $listInfoArr[0] < 2) {
                return;
            } else {
                $this->movieList($listInfoArr[0], $listInfoArr[1], $listInfoArr[2], $listInfoArr[3]);
            }
        } catch (\Exception $e) {
            $this->info('list exception ' . $e->getMessage());
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
        $list = null;
        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = parse_url($url, PHP_URL_HOST);
        $host = $scheme . '://' . $host;

        $list = QueryList::Query($url, array(
            'title' => array('.ulink:last()', 'html'),
            'con_url' => array('.ulink:last()', 'href'),
            'm_time' => array('tr:eq(2) td:eq(1)', 'text') //影片更新的时间
        ), '.co_content8 table', 'utf-8', 'gbk', true)->getData(function ($item) use ($host) {
            $marest = preg_match('/《(.*?)》/i', $item['title'], $matchs);
            if ($marest === 1) {
                $item['title'] = $matchs[1];
            } else {
                $item['title'] = '';
            }
            if (strpos($item['title'], '/') !== false) {
                $item['title'] = strstr($item['title'], '/', true);
            }
            $item['con_url'] = $host . $item['con_url'];
            $m_time = explode("\n", str_replace("\r", '', $item['m_time']));
            $m_time = explode('：', $m_time[0])[1];
            $item['m_time'] = $m_time;
            return $item;
        });
        return $list;
    }




    /**
     * 使用node去格式化下载链接
     */
    public function nodeDownLink()
    {
        //node自动更新下载链接
        //->where('down_link','not like','%thunder://%')
        $isNoDownLinks = DB::connection($this->dbName)->table($this->tableName)->where('typeid', $this->typeId)->where('down_link', 'not like', '%thunder://%')->where(function ($query) {
            $query->where('is_post', -1)
                ->orWhere('is_update', -1);
        })->get();
        $tot = count($isNoDownLinks);
//        dd($tot);

        foreach ($isNoDownLinks as $key => $value) {
            //cli
            if(config('qiniu.qiniu_data.is_cli')) {
                $this->info("node parse down_link {$key}/{$tot} id -- {$value->id}");
            }
            //log
            if ($this->isCommandLogs === true) {
                $command = "node parse down_link {$key}/{$tot} id -- $value->id\n";
                file_put_contents($this->commandLogsFile, $command, FILE_APPEND);
            }
            $url = config('qiniu.qiniu_data.node_url') . '?aid=' . $value->id . '&down_link=' . urlencode($value->down_link);
            $this->curl->runSmall($url);
        }
        //cli
        if(config('qiniu.qiniu_data.is_cli')) {
            $this->info("node parse down_link end");
        }
        //log
        if ($this->isCommandLogs === true) {
            $command = "node parse down_link end\n";
            file_put_contents($this->commandLogsFile, $command, FILE_APPEND);
        }
    }

}

