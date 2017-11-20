<?php

namespace App\Console\Commands\Caiji\News;

use Illuminate\Console\Command;
use App\Console\Commands\Mytraits\Common;
use App\Console\Commands\Mytraits\Toutiao;
use Illuminate\Support\Facades\DB;
use QL\QueryList;

class ToutiaoUpdate extends Command
{
    use Common;
    use Toutiao;
    /**
     * The name and signature of the console command.
     *
     * @var string
     * //page_tot 获得列表页的深度
     */
    protected $signature = 'caiji:news_toutiao_update {depth}{type_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '采集头条首页信息';

    public $typeId;
    public $channelId = 1;
    public $sleepTime = 3;
    //列表页循环深度
    public $depth;

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
        //typeid = 24
        //得到首页公众号,再进公众号采集文章
        //只判断标题是否重复,其他不用关心,只采集前三页的内容
        $this->depth = $this->argument('depth');
        $this->typeId = $this->argument('type_id');
        $this->getList();
        //保存图片
        $this->call('xiazai:img',['action'=>'litpic','type_id'=>$this->typeId]);
        //删除图片不成功的记录
        DB::connection($this->dbName)->table($this->tableName)->where('typeid',$this->typeId)->where('is_litpic',-1)->delete();
        //上传dede
        $this->call('send:dedenewpost', ['channel_id' => $this->channelId, 'typeid' => $this->typeId]);

        // if (file_exists($this->dedeSendStatusFile)) {
        //     //更新列表页
        //     $this->info("更新列表页");
        //     $this->call('dede:makehtml',['type'=>'list','typeid'=>$this->typeId]);
        // }
        $this->info("上线部署完成!");
    }


    public function getList()
    {
        $maxBehotTime = 0;
        $surl = 'http://www.toutiao.com/api/pc/feed/?category=news_entertainment&max_behot_time=[max_behot_time]';
        //得到这条命令
        $message = date('Y-m-d H:i:s') . "\ncaiji:news_y3600_update {$this->depth} {$this->typeId} \n the link is {$surl} \n";
        $this->info($message);
        $tot = 0;
        try {
            do {
                $url = str_replace('[max_behot_time]', $maxBehotTime, $surl);
                //换ip
                $ip = getRandIp();
                $ql = QueryList::run('Request', [
                    'target' => $url,
                    'referrer' => 'http://www.toutiao.com/ch/news_entertainment/',
                    'method' => 'GET',
                    'CLIENT-IP:' . $ip,
                    'X-FORWARDED-FOR:' . $ip,
                    'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.8; rv:21.0) Gecko/20100101 Firefox/21.0',
                    //等等其它http相关参数，具体可查看Http类源码
                ]);
                $html = $ql->setQuery([])->getHtml();
                $html = json_decode($html, true);
                if (empty($html['data'])) {
                    break;
                }
                foreach ($html['data'] as $key => $value) {
                    if (isset($value['chinese_tag']) === false || stripos($value['chinese_tag'], '娱乐') === false || $value['has_gallery'] === true || isset($value['media_url']) === false) {
                        continue;
                    }
                    //去获得列表页,与内容详情页
                    $url = 'http://www.toutiao.com/' . $value['media_url'];
                    $this->getContent($url);
                    $this->info('toutiao getlist save success !!');
                }
                $maxBehotTime = $html['next']['max_behot_time'];
                $tot ++;
            } while ($tot > 5);
            $this->info('toutiao 娱乐首页公众号文章更新采集完成!!');
        }catch (\Exception $e){
            $this->error('exception '.$e->getMessage());
            return;
        }catch (\ErrorException $e){
            $this->error('error exception '.$e->getMessage());
            return;
        }
    }

   /**
    * 得到列表页的内容
    */
    public function getContent($url)
    {
        $maxBehotTime = 0;
        $url = trim($url, '/');
        $artUrl = 'http://www.toutiao.com/c/user/article/?page_type=1&user_id=[user_id]&max_behot_time=[max_behot_time]&count=20';
        $userId = substr($url, strrpos($url, '/') + 1);
        $artUrl = str_replace('[user_id]', $userId, $artUrl);

        //初始化列表页循环深度
        $i = 0;
        try {
            do {
                $ip = getRandIp();
                $relArtUrl = str_replace('[max_behot_time]', $maxBehotTime, $artUrl);
                $ql = QueryList::run('Request', [
                    'target' => $relArtUrl,
                    'method' => 'GET',
                    'CLIENT-IP:' . $ip,
                    'X-FORWARDED-FOR:' . $ip,
                    'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.8; rv:21.0) Gecko/20100101 Firefox/21.0',
                    //等等其它http相关参数，具体可查看Http类源码
                ]);
                $html = $ql->setQuery([])->getHtml();
                $html = json_decode($html, true);
                $maxBehotTime = $html['next']['max_behot_time'];
                if (empty($html['data'])) {
                    break;
                }

                $body = null;
                foreach ($html['data'] as $key => $value) {
                    //判断文章是否存在
                    $isAlready = DB::connection($this->dbName)->table($this->tableName)->where('title_hash', md5($value['title']))->first();
                    if (count($isAlready) > 0) {
                        $this->error("artlist {$value['title']} is already!!");
                        continue;
                    }

                    //内容页链接
                    $conUrl = 'http://www.toutiao.com' . $value['source_url'];
                    //article
                    if(isset($value['has_video']) === true && $value['has_video']){
                        //如果是视频则跳出
                        continue;
                    }elseif(parse_url($value['display_url'], PHP_URL_HOST) == 'temai.snssdk.com') {
                        //手机
                        $this->info("获得文章图片内容 {$conUrl}");
                        $body = $this->gettemai($conUrl);
                    } elseif (parse_url($value['display_url'], PHP_URL_HOST) == 'toutiao.com' || parse_url($value['display_url'], PHP_URL_HOST) == 'book.zongheng.com') {
                        //pc
                        $this->info("获得文章图片内容 {$conUrl}");
                        $body = $this->getText($conUrl);
                    }
                    $litpic = '';
                    if (isset($value['image_url']) === true) {
                        if (strpos($value['image_url'], '//') == 0) {
                            $litpic = 'http:' . $value['image_url'];
                        } else {
                            $litpic = $value['image_url'];
                        }
                    }
                    //目前只将文章的公众号保存下来
                    if ($body) {
                        $saveArr = [
                            'title' => $value['title'],
                            'title_hash' => md5($value['title']),
                            'litpic' => $litpic,
                            'down_link' => $value['abstract'],
                            'body' => str_replace('头条', '', $body),
                        ];
                        $other = [
                            'typeid' => $this->typeId,
                            'con_url' => $conUrl,
                            'is_con' => 0,
                            'is_douban' => 0,
                            'm_time' => date('Y-m-d H:i:s'),
                        ];
                        $saveArr = array_merge($saveArr, $other);
                        //保存到数据库中
                        $rest = DB::connection($this->dbName)->table($this->tableName)->insert($saveArr);
                        if ($rest) {
                            $this->info('toutiao gather save success !!');
                        } else {
                            $this->error('toutiao gather save success !!');
                        }
                    }
                }
//            //休息一下吧
                $i++;
                sleep($this->sleepTime);
            } while ($i < $this->depth);
            $this->info('toutiao gather save end !!');
        }catch (\Exception $e){
            $this->error('exception '.$e->getMessage());
            return;
        }catch (\ErrorException $e){
            $this->error('error exception '.$e->getMessage());
            return;
        }
   }


}
