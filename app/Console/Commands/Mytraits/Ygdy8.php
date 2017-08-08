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

    public function MovieInit()
    {
        if (empty($this->curl)) {
            $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'curl' . DIRECTORY_SEPARATOR . 'curl.php';
            include $path;
            $this->curl = new \curl();
        }
    }

    /**
     * 保存电影电视剧列表页
     */
    public function movieList($pageTot, $baseListUrl, $isNew = false)
    {
        if (strrpos($baseListUrl, '_') !== false) {
            $url = substr($baseListUrl, 0, strrpos($baseListUrl, '_'));
        } else {
            $url = substr($baseListUrl, 0, strrpos($baseListUrl, '/')) . '/';
        }

        //取出最大的时间
        $maxTime = DB::connection($this->dbName)->table($this->tableName)->where('typeid', $this->typeId)->max('m_time');

        for ($i = 1; $i <= $pageTot; $i++) {
            $this->info("this is page {$i}");
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
                            break 2;
                        }
                        //更新这样记录的下载链接,将is_con=-1,down_link = '',is_update=-1//default 0
                        $rs = DB::connection($this->dbName)->table($this->tableName)->where('id', $rest->id)->update(['down_link' => '', 'is_con' => -1, 'is_update' => -1]);

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
                    $rs = DB::connection($this->dbName)->table($this->tableName)->insert($listSaveArr);
                }

                if ($rs) {
                    $this->info($value['title'] . ' list ' . $isNewType . ' success');
                } else {
                    $this->info($value['title'] . ' list ' . $isNewType . ' fail');
                }
            }
        }
        $this->info('list save end');
    }

    /**
     * 采集列表页
     */
    public function getList($url)
    {
        $list = null;
        $host = 'http://www.ygdy8.net';

        $this->curl->add()->opt_targetURL($url)->done();
        $this->curl->run();
        $html = $this->curl->getAll();
        $html = $html['body'];
        $html = iconv('gb2312', 'utf-8//IGNORE', $html);
//        dd($html);

        $list = QueryList::Query($html, array(
            'title' => array('.ulink:last()', 'html'),
            'con_url' => array('.ulink:last()', 'href'),
            'm_time' => array('tr:eq(2) td:eq(1)', 'text') //影片更新的时间
        ), '.co_content8 table')->getData(function ($item) use ($host) {
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
//        dd($list);
        return $list;
    }


    /**
     * 采信内容页
     * @param  $type 1.movie(下载电影) 2.other(只下载链接)
     */
    public function getContent($type, $isNew = false)
    {
//        $url = 'http://www.ygdy8.net/html/gndy/dyzz/20170625/54313.html';
        $name = '';
        switch ($type) {
            case 'movie':
                $name = 'getConSaveArr';
                break;
            case 'other':
                $name = 'getOtherConSaveArr';
                break;
        }
        try {
            do {
                $take = 10;
                $arc = DB::connection($this->dbName)->table($this->tableName)->where('id', '>', $this->aid)->where('is_con', -1)->where('typeid', $this->typeId)->take($take)->orderBy('id')->get();
//                $arc = DB::connection($this->dbName)->table($this->tableName)->where('typeid',$this->typeId)->where('body','like','%src=""%')->take($take)->get();
//                dd($arc);
                $tot = count($arc);

                foreach ($arc as $key => $value) {
                    $this->aid = $value->id;
                    $this->info("{$key}/{$tot} id is {$value->id} url is {$value->con_url}");

                    //得到保存的数组
//                    $conSaveArr = $this->getConSaveArr($value->con_url);
                    $conSaveArr = $this->{$name}($value->con_url);
                    if (!$conSaveArr) {
                        continue;
                    }
                    if ($isNew === true && $value->is_update = -1) {
                        unset($conSaveArr['litpic']);
                    }
                    print_r($conSaveArr);
                    $rest = DB::connection($this->dbName)->table($this->tableName)->where('id', $value->id)->update($conSaveArr);
                    if ($rest) {
                        DB::connection($this->dbName)->table($this->tableName)->where('id', $value->id)->update(['is_con' => 0]);
                        $this->info('save con success');
                    } else {
                        $this->error('save con fail');
                    }
                }
            } while ($tot > 0);
        } catch (\ErrorException $e) {
            $this->info('get content error exception ' . $e->getMessage());
//            $this->call('caiji:movieygdy8',['aid'=>$this->aid]);
            $this->getContent($type, $this->aid);
        } catch (\Exception $e) {
            $this->info('get content exception ' . $e->getMessage());
//            $this->call('caiji:movieygdy8',['aid'=>$this->aid]);
            $this->getContent($type, $this->aid);
        }
        //电视剧需要更新,还要再添加一个字段
        $this->info('save con end');
        $this->aid = 0;
        //删除下载链接为空的数据
        DB::connection($this->dbName)->table($this->tableName)->whereNull('down_link')->delete();
    }


    /**
     * 得到内容页的保存数组,以◎分割
     * @param $url 内容页的网址链接
     */
    public function getConSaveArr($url)
    {
        $restArr = array();

        $this->curl->add()->opt_targetURL($url)->done();
        $this->curl->run();
        $html = $this->curl->getAll();
        $html = $html['body'];
        $html = iconv('gb2312', 'utf-8//IGNORE', $html);
        $html2 = QueryList::Query($html, array(), '.co_content8')->getHtml();
        if (empty($html2)) {
            return null;
        }
//        dd($html);

        $content = QueryList::Query($html, array(
            'litpic' => array('img:first()', 'src'),
            'down_link' => array('table', 'html'),
            'html' => array('#Zoom', 'text', '-table'),
            'con_pic' => array('#Zoom img:last()', 'src')
        ), '.co_content8')->getData(function ($item) {
//            $html = preg_replace('/onclick(.*?)"(.*?)"/is','',$item['html']);
            if (strlen($item['litpic']) > 250) {
                $item['litpic'] = '';
            }
            if (strlen($item['con_pic']) > 250) {
                $item['con_pic'] = '';
            }

            $item['down_link'] = QueryList::Query($item['down_link'], array(
                'down_link' => array('a', 'href'),
            ))->getData(function ($item) {
                return urldecode($item['down_link']);
            });

            $html = strtr($item['html'], array("\r" => '', "\n" => ''));
            if (empty($html) === true) {
                $marest = preg_match('/<div id="Zoom">(.*?)<table/is', $html, $matchs);
                if ($marest === 0) {
                    $html = '';
                } else {
                    $html = strip_tags($matchs[1]);
                }
            }
            if (mb_strpos($html, '下载地址', 0, 'utf-8') !== false) {
                $html = strstr($html, '【下载地址', true);
            } else {
                $html = strstr($html, 'ftp://', true);
            }

            if (strpos($html, '◎') !== false) {
                $item['html'] = array_filter(explode('◎', $html));
            } elseif (strpos($html, '◆') !== false) {
                $item['html'] = array_filter(explode('◆', $html));
            } elseif (strpos($html, '[') !== false) {
                $item['html'] = array_filter(explode('[', $html));
            } else {
                return false;
            }
            return $item;
        });
        if (!$content[0]) {
            return false;
        }
//        print_r($content);
//        dd($content);
        foreach ($content[0]['html'] as $key => $value) {
            //添加了评分值
            $value = trim($value);
            if (mb_strpos($value, '评分') !== false) {
                $marest = preg_match('/\d+(\.\d+)?/', $value, $matchs);
                if ($marest === 1) {
                    $restArr['grade'] = $matchs[0];
                } else {
                    $restArr['grade'] = 5;
                }
            }
//            $lastPosition = mb_strrpos($value, '　', 0, 'utf-8');
            if (strpos($value, '：') !== false) {
                $lastPosition = mb_strpos($value, '：', 0, 'utf-8');
            } else {
                $lastPosition = mb_strrpos($value, '　', 0, 'utf-8');
            }
            if ($lastPosition === false) {
                continue;
            }
            $prefix = strtr(mb_substr($value, 0, $lastPosition, 'utf-8'), array('　' => '', ' ' => ''));
            $vrest = strtr(mb_substr($value, $lastPosition + 1, null, 'utf-8'), array('　' => '', ' ' => ''));
            echo $prefix . PHP_EOL;
            echo $vrest . PHP_EOL;

            switch ($prefix) {
                case '译名':
                    if (isset($restArr['mname']) === true) {
                        break;
                    }
                    if (strpos($vrest, '/') !== false) {
                        $vrest = explode('/', $vrest);
                        $vrest = $vrest[0];
                    }
                    $restArr['mname'] = $vrest;
                    break;
                case '片名':
                    if (isset($restArr['mname']) === true) {
                        break;
                    }
                    if (strpos($vrest, '/') !== false) {
                        $vrest = explode('/', $vrest);
                        $vrest = $vrest[0];
                    }
                    $restArr['mname'] = $vrest;
                    break;
                case '年代':
                    if (isset($restArr['myear']) === true || strlen($vrest) > 20) {
                        break;
                    }
                    $restArr['myear'] = $vrest;
                    break;
                case '类别':
                    if (isset($restArr['types']) === true || strlen($vrest) > 250) {
                        break;
                    }
                    $restArr['types'] = str_replace('/', ',', $vrest);
                    break;
                case '语言':
                    if (isset($restArr['lan_guage']) === true || strlen($vrest) > 50) {
                        break;
                    }
                    $restArr['lan_guage'] = str_replace('/', ',', $vrest);
                    break;
                case '集数':
                    preg_match('/\d+/', $vrest, $matchs);
                    $restArr['episode_nums'] = $matchs[0];
                    break;
                default:
                    $value = str_replace('　', '', $value);
                    if (mb_stripos($prefix, '导演') !== false && isset($restArr['director']) === false) {
//                        $director = '';
                        $director = str_replace('导演', '', $value);

                        $marest = preg_match_all('/[\x{4e00}-\x{9fa5}]+/u', $director, $matchs);
                        if ($marest) {
                            $director = $matchs[0];
                            $director = implode(',', $director);
                        } else {
                            if (strpos($director, '/') !== false) {
                                $director = explode('/', $director);
                                $director = $director[0];
                            }
                        }

                        if (strlen($director) > 10) {
                            $director = mb_substr($director, 0, 10);
                        }
                        $restArr['director'] = $director;
                    } elseif ((mb_stripos($prefix, '主演') !== false || mb_stripos($prefix, '演员') !== false) && isset($restArr['actors']) === false) {
//                        $actors = '';
//                        $value = str_replace(array('主演','演员'), array('',''), $value);
                        $marest = preg_match_all('/[\x{4e00}-\x{9fa5}]+/u', $vrest, $matchs);
                        if ($marest) {
                            $actors = $matchs[0];
                            $actors = array_slice($actors, 0, 5);
                            $actors = implode(',', $actors);
//                            if (strlen($actors) > 250) {
//                                break;
//                            }
                        } else {
                            $actors = preg_split('/\.+/', $vrest);
                            $actors = array_slice($actors, 0, 5);
                            $actors = implode(',', $actors);
                        }
                        $restArr['actors'] = $actors;
                    } elseif (mb_strpos($prefix, '简介') !== false && isset($restArr['body']) === false) {
                        $body = $vrest;
                        if (isset($content[0]['con_pic']) === true && empty($content[0]['con_pic']) === false) {
                            $mname = isset($restArr['mname']) ? $restArr['mname'] : '';
                            $body .= '<img src="' . $content[0]['con_pic'] . '" alt="' . $mname . '">';
                        }
                        $restArr['body'] = $body;
                    }
                    break;
            }
        }
//        dd(222);
//        dd($restArr);
//        print_r($restArr);
        unset($restArr['mname']);
        $restArr = array_merge($restArr, ['litpic' => $content[0]['litpic'], 'down_link' => implode(',', $content[0]['down_link'])]);
        return $restArr;
    }


    /**
     * 只得到下载链接
     */
    public function getOtherConSaveArr($url)
    {
        $this->curl->add()->opt_targetURL($url)->done();
        $this->curl->run();
        $html = $this->curl->getAll();
        $html = $html['body'];
        $html = iconv('gb2312', 'utf-8//IGNORE', $html);

        $content = QueryList::Query($html, array(
            'litpic' => array('img:first()', 'src'),
            'down_link' => array('table', 'html'),
        ), '.co_content8')->getData(function ($item) {
            if (strlen($item['litpic']) > 250) {
                $item['litpic'] = '';
            }

            $item['down_link'] = QueryList::Query($item['down_link'], array(
                'down_link' => array('a', 'href'),
            ))->getData(function ($item) {
                return urldecode($item['down_link']);
            });
            return $item;
        });

        $restArr = array(
            'litpic' => $content[0]['litpic'],
            'down_link' => implode(',', $content[0]['down_link'])
        );
        return $restArr;
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
            $this->info("parse down_link {$key}/{$tot}");
            $url = config('qiniu.qiniu_data.node_url') . '?aid=' . $value->id . '&down_link=' . urlencode($value->down_link);
            $this->curl->runSmall($url);
        }
        $this->info("parse down_link end");
    }


    /**
     * 将更新的数据替换到dede后台
     */
    public function dedeDownLinkUpdate()
    {
        $isUpdate = false;

        $dedeDownLinkUpdateUrl = config('qiniu.qiniu_data.dede_url') . 'myplus/down_link_update.php';
        $isNoDownLinks = DB::connection($this->dbName)->table($this->tableName)->where('typeid', $this->typeId)->where('is_update', -1)->get();
        $tot = count($isNoDownLinks);

        foreach ($isNoDownLinks as $key => $value) {
            $this->info("{$key}/{$tot} id is {$value->id}");
            //echo $value->down_link."\n";
            //先登录
            $rest = $this->dedeLogin($this->dedeUrl . 'login.php', $this->dedeUser, $this->dedePwd);

            if ($rest) {
                $this->curl->add()
                    ->opt_targetURL($dedeDownLinkUpdateUrl)
                    ->opt_sendHeader('Cookie', $this->cookie)
                    ->opt_sendPost('typeid', $value->typeid)
                    ->opt_sendPost('title', $value->title)
                    ->opt_sendPost('down_link', $value->down_link)
                    ->done('post');
                $this->curl->run();
                $content = $this->curl->getAll();
                $body = explode("\r\n\r\n", $content['body'], 2);
                if (isset($body[1]) && $body[1] == 'update ok') {
                    $isUpdate = true;
                    //更新数据库is_update
                    DB::connection($this->dbName)->table($this->tableName)->where('id', $value->id)->update(['is_update' => 0]);
                    $this->info("dede down_link update {$value->title} update ok !");
                } else {
                    //没有更新成功,也将is_update更新为0
                    DB::connection($this->dbName)->table($this->tableName)->where('id', $value->id)->update(['is_update' => 0]);
                    $this->error("dede down_link update {$value->title} update fail !");
                }
            } else {
                $this->error("dede down_link update login fail !");
            }
        }
        $this->info("dede down_link update end !");
        return $isUpdate;
    }


    /**
     * dede生成栏目页
     */
    public function makeLanmu()
    {
        $url = $this->dedeUrl . 'makehtml_list_action.php?typeid=' . $this->typeId . '&maxpagesize=50&upnext=1';
        //dd($url);
        $this->dedeLogin($this->dedeUrl . 'login.php', $this->dedeUser, $this->dedePwd);
        $this->curl->add()
            ->opt_targetURL($url)
            ->opt_sendHeader('cookie', $this->cookie)
            ->done('get');
        $this->curl->run();
        $content = $this->curl->getAll();
        if (mb_strpos($content['body'], '栏目列表更新',0, 'utf-8') !== false) {
            $this->info("{$this->typeId}  lanmu list make success !");
        } else {
            $this->error("{$this->typeId}  lanmu list make fail !");
        }
    }
}

