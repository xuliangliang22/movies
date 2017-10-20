<?php

namespace App\Console\Commands\Caiji;

use DiDom\Query;
use Illuminate\Console\Command;
use QL\QueryList;

class Test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'caiji:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '用于测试';


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
        //post
        $rest = [];
        $url = 'http://fanyi.baidu.com/v2transapi';
        $cates = [
            '库阿语',
            '阿姆哈拉语',
            '阿拉伯语',
            '亚拉姆语',
            '阿萨姆语',
            '巴斯克语',
            '巴伐利亚语',
            '白俄罗斯语',
            '本巴语',
            '孟加拉语',
            '保加利亚语',
            '保加利亚楚瓦什语',
            '柬埔寨语',
            '加泰罗尼亚语',
            '宿务语',
            '齐切瓦语',
            '汉语',
            '克里奥尔语',
            '克罗地亚语',
            '匈牙利语',
            '冰岛语',
            '伊多语',
            '伊博语',
            '印尼语',
            '拉丁国际语',
            '意大利语',
            '日语',
            '齐切瓦语',
            '卡纳达语',
            '哈萨克语',
            '吉士巴语',
            '韩语',
            '老挝语',
            '拉丁语',
            '马达加斯加',
            '马拉雅拉姆语',
            '迪维西语',
            '马拉地语',
            '蒙古语',
            '印第安语',
        ];

        foreach ($cates as $key=>$value) {
            $this->info($key);
            $params = array(
                'from' => 'zh',
//                'to' => 'en',
                'to' => 'de',
                'query' => $value,
                'transtype' => 'translang',
                'simple_means_flag' => '3',
            );
            $ql = QueryList::run('Request', [
                'http' => [
                    'target' => $url,
                    'referrer' => 'http://fanyi.baidu.com/',
                    'method' => 'POST',
                    'params' => $params,
                    //等等其它http相关参数，具体可查看Http类源码
                ],
            ]);
            $data = $ql->setQuery(array())->getHtml();
            $data = json_decode($data, true);
            $rest[] = $data['trans_result']['data'][0]['dst'];
        }
        var_export($rest);
    }
}

