<?php

namespace App\Console\Commands\Caiji;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use QL\QueryList;

class DongGatherLutub extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'caiji:donggatherlutub';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '采集http://www.lutub.com/category/lutuzhuanyong网站动态图数据';

    /**
     * 栏目id
     */
    protected $typeid = 6;

    /**
     * 网站标识
     */
    protected $netFlag = 'lutub';

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
        $pageTot = 591;
        $url = 'http://www.lutub.com/category/taotuheji/xieedongtaitu/';
        $this->getList($pageTot, $url);
//        $this->getContent();
    }


    /**
     * 得到列表页全部
     */
    public function getList($pageTot, $url)
    {
        for ($i = 1; $i <= $pageTot; $i++) {
            if ($i == 1) {
                $listUrl = $url;
            } else {
                $listUrl = $url . 'page/' . $i;
            }

            $ip = $this->genRandIp();
            //多线程扩展
            QueryList::run('Multi', [
                //待采集链接集合
                'list' => [$listUrl],
                'curl' => [
                    'opt' => array(
                        //这里根据自身需求设置curl参数
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => false,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_AUTOREFERER => true,
                        CURLOPT_HTTPHEADER => [
                            'User-Agent: Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/29.0.1547.59 QQ/8.9.20026.201 Safari/537.36',
                            'CLIENT-IP:' . $ip,
                            'X-FORWARDED-FOR:' . $ip,
                        ],
                        //........
                    ),
                    //设置线程数
                    'maxThread' => 1,
                    //设置最大尝试数
                    'maxTry' => 3
                ],
                'success' => function ($a) {
                    //采集规则
                    $reg = array(
                        //采集文章标题
                        'title' => array('.article h2', 'text'),
                        'litpic' => array('.thumbnail img', 'src'),
                        'con_url' => array('.thumbnail a', 'href'),
                    );
                    $rang = '#post_container li';
                    $ql = QueryList::Query($a['content'], $reg, $rang);
                    $list = $ql->getData(function ($item) {
                        if (preg_match('/^http(.*?)/i', $item['litpic']) === 0) {
                            $item['litpic'] = 'http:' . $item['litpic'];
                        }
                        return $item;
                    });
                    dd($data);

                    //保存到数据库中去
                    foreach ($list as $key => $value) {
                        $saveArr = [
                            'typeid' => $this->typeid,
                            'title' => $value['title'],
                            'litpic' => $value['litpic'],
                            'con_url' => $value['con_url'],
                            'net_flag' => $this->netFlag,
                            'created_at' => date('Y-m-d H:i:s'),
                        ];
                        $rest = DB::table('dong_gather')->insert($saveArr);
                        if ($rest) {
                            $this->info('insert dong list gather success');
                        }
                    }
                }
            ]);
        }
        $this->info('insert dong list gather end');
    }


    /**
     * 得到内容页的内容
     */
    public function getContent()
    {
        $take = 10;

        do {
            $archives = DB::table('dong_gather')->where('is_con', -1)->where('net_flag',$this->netFlag)->take($take)->get();

            $tot = count($archives);
            foreach ($archives as $key => $value) {
                $this->info("{$key}/{$tot} con url is {$value->con_url}");
                $value->con_url = 'http://www.lutub.com/3015.html';
                $contentPageTot = QueryList::Query($value->con_url, array(
                    'page_tot' => array('', 'text'),
                ), '.pagelist a')->getData(function ($item) {
                    return $item['page_tot'];
                });
                if (empty($contentPageTot) === true) {
                    $contentPageTot = 1;
                } else {
                    sort($contentPageTot, SORT_NUMERIC);
                    $contentPageTot = array_pop($contentPageTot);
                }

                $imgStr = '';
                for ($i = 1; $i <= $contentPageTot; $i++) {
                    if ($i == 1) {
                        $con_url = $value->con_url;
                    } else {
                        $con_url = $value->con_url . '/' . $i;
                    }
                    $img = QueryList::Query($con_url, array(
                        'img' => array('.context img', 'src'),
                    ))->data;
                    if (empty($img) === false) {
                        $oneImgStr = $img[0]['img'];
                        if (preg_match('/^http(.*?)/i', $oneImgStr) === 0) {
                            $oneImgStr = 'http:' . $oneImgStr;
                        }
                        $imgStr .= '<p><img src="' . $oneImgStr . '"/></p>';
                    }
                }
                //更新数据库
                $rest = DB::table('dong_gather')->where('id', $value->id)->update(['body' => $imgStr, 'is_con' => 0]);
                if ($rest) {
                    $this->info('this aid is ' . $value->id . ' content save success');
                } else {
                    $this->info('this aid is ' . $value->id . ' content save fail');
                }
            }
        } while ($tot > 0);
        $this->info('this content save end');
    }

    /**
     * 随机生成国内IP
     *
     * @return string
     */
    public function genRandIp()
    {
        $ipMap = array(
            array('607649792', '608174079'), //36.56.0.0-36.63.255.255
            array('1038614528', '1039007743'), //61.232.0.0-61.237.255.255
            array('1783627776', '1784676351'), //106.80.0.0-106.95.255.255
            array('2035023872', '2035154943'), //121.76.0.0-121.77.255.255
            array('2078801920', '2079064063'), //123.232.0.0-123.235.255.255
            array('-1950089216', '-1948778497'), //139.196.0.0-139.215.255.255
            array('-1425539072', '-1425014785'), //171.8.0.0-171.15.255.255
            array('-1236271104', '-1235419137'), //182.80.0.0-182.92.255.255
            array('-770113536', '-768606209'), //210.25.0.0-210.47.255.255
            array('-569376768', '-564133889'), //222.16.0.0-222.95.255.255
        );
        $rand = mt_rand(0, 9);
        $ip = long2ip(mt_rand($ipMap[$rand][0], $ipMap[$rand][1]));
        return $ip;
    }
}
