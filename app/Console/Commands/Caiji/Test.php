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
      $str = <<<STR
         <p >%s</p>\n
      <p >电影</p>\n
      <p ></p>\n
      <p >1905电影网讯 对于影迷们来说，是个很特别的存在。曾经的紫霞仙子一梦经年，迄今仍然是很多男青年心中的最佳情人。身着一袭轻纱的她，回眸一笑定格了电影史的经典一刻。</p>\n
      <p ></p>\n
      <p >%s</p>\n
      <p ></p>\n
      <p >但这种定格，对演员本身却未见得是一件十全十美的事情，起码在大话西游之后，朱茵似乎便再也无令人记忆深刻的作品和形象出现了。尤其港影北上以来，就更是鲜少见到朱茵出现在大荧幕之上。于是乎，这部《二次初恋》也打起了女神归来的金字招牌。但这个算盘却再一次打空，正如很多试图挖掘“旧”明星们残存价值的尝试一样，网友们纷纷发出了“朱茵是不是缺钱了？”的疑问。可以说，《二次初恋》这部作品，无论从影片本身而言，还是论及朱茵的颜值与表演，都无法让当下的观众们觉得满意。</p>\n
      <p ></p>\n
      <p >%s、</p>\n
      <p >影片剧照大多被打上了柔光</p>\n
      <p></p>\n
      <p >《二次初恋》讲了个什么样的故事呢？一对中年夫妻闹别扭，不经意间丈夫重返年轻，重新与妻子谈了一场“恋爱“，两人最终达成了和解，珍惜眼下的幸福。这故事看上去很熟悉，的确，重返过去的奇幻套路，在很多电影中都屡见不鲜，经典如《重返十七岁》、等等，都用到了相似的叙事模式。相似不是问题，套路也不是原罪。相似的作品多，说明观众吃这一套故事讲述法则，毕竟谁都有一个永葆青春的梦想，不然也就不会唱那首了。</p>\n
      <p ></p>\n
      <p >%s</p>\n
      <p >丈夫重返年轻与妻子“再度”恋爱</p>\n
      <p></p>\n
      <p >回到过去这种叙述方式，内里涵盖了两个关键点，首先自然是对现状的不满，唯有你不乐意当下，你才会想去寻找变化，这时候过去或者未来变成为解决问题的可能时空。《二次初恋》中，朱茵扮演的叶兰和扮演的路建国结婚20载，却最终承担不起生活中琐碎而冲突累累。路建国又看到自己儿子青春叛逆，为了泡妹子而逃学，家庭之外，在妻女面前他是西装革履的成功人士，但实际上却失业开黑车谋生，种种压力让他不堪其扰，最终在午夜时分踏上一趟充满魔幻力量的列车，化身成了年轻时的自己。这个段落虽然老套，但却很真实，中年男性的去势焦虑表现得很完整，性魅力丧失之后的家庭危机，青春期儿子的“弑父”渴望，这些电影中的经典母题被浓缩在了影片前十分钟的短暂段落里，铺陈显得相当不够。</p>\n
      <p ></p>\n
      <p >%s</p>\n
      <p >王志飞饰演片中失意潦倒的中年丈夫</p>\n
      <p></p>\n
      <p >不过这也可以理解，毕竟影片的主角并非王志飞，而是佯装成路建国侄子的年轻版路建国——路大民，扮演者是年轻的鲜肉。眼下，鲜肉当道，导演们看中的是在高清摄影机下可以被清晰展现的颜值，但颜值已经被证明了多次，它并非是具有票房生产力的有效元素，不然就不会有那么多小鲜肉主演的扑街影片了。《二次初恋》里的路大民，就展现出了这种尴尬，生硬的肢体和面部表情，很难诠释一个有着20岁面孔四十岁心理的复杂角色，更别提身份伪装所应该造成的各种细微表现力。</p>\n
      <p ></p>\n
      <p >%s</p>\n
      <p >年轻的杜天皓未能演出40岁的复杂心理</p>\n
      <p></p>\n
      <p >而影片的问题还在于，变年轻并非是变了个人，路大民却像是一个天生属于当下的弄潮儿一般，先锋的发型、潮流的服装、前沿的妆容等等，这些手段无非是想要强调杜天皓的偶像魅力，但问题在于他与路建国这个角色是很脱节的。</p>\n
      <p></p>\n
      <p >《二次初恋》类型影片的另一个关键点，还有路建国们在重回过去的过程中，一要寻找回在中年迷失的自己，二则是要重获佳人的芳心。影片在此倒是着墨不少，路大民各种荷尔蒙展示的特写场景，舞蹈场景多的就像是杜天皓的个人舞蹈汇报演出，但这些场景真的都是叙事的必要部分么？恐怕并不竟然，无非依然还是创作者的投机心态作祟罢了，用更多的类似场景来博得更多迷妹们的观影可能。这显然占据了本该重点描写的路大民的内心波折过程，影片无一例外都用相当外化的表现手段书写了路大民如何重获叶兰的爱情，以及如何与自己的儿子营造出和谐的父子关系，各种欢快的碎片化片段，拼贴在一起，却玩着玩着就解决了自己、夫妻、以及父子之间的危机，这种叙述恐怕并不能让人满意，本该有那么一点的深度也荡然无存。</p>\n
      <p ></p>\n
      <p >%s</p>\n
      <p >杜天皓、斗舞，影片将重心放错了位置</p>\n
      <p></p>\n
      <p >《二次初恋》最让普通观众难以接受的，恐怕是不那么和谐的配音，过于正气和播报腔的配音，让影片中的角色一张嘴就令观众坐立难安，连最基本的口型都对不上，创作者的诚意何在。</p>\n
      <p></p>\n
      <p >导演说她想拍摄的是一个女人从20岁到40岁的心路历程，但就影片的呈现来看，朱茵反倒是个配角，其无非重新挖掘了丈夫的“魅力”，回归了家庭，就个体本身而言，毫无成长可言。影片的高潮场景，或许是叶兰和侄子那一段“优美”的舞蹈，但对观众而言，不管怎样，不知情的叶兰是在和自己的侄子共舞，那么影片那暧昧的用光、迷离的色调以及两位演员之间亲密的接触，是不是会引发在伦理边缘游走的质疑呢？</p>\n
      <p ></p>\n
      <p >%s</p>\n
      <p >影片人物角色身份或许会引发伦理方面的质疑</p>\n
      <p></p>\n
      <p >《二次初恋》用极度相似于《重返十七岁》的壳子，却没能在接地气、趣味性、新鲜的桥段以及现实的广度上做出自己的成就，反倒浅薄的可怕。根源还在于创作者们的有一笔赚一笔的欲望作祟罢了，正如影片对重庆这座城市单一的展演一样，肤浅的很。</p>\n
      <p></p>\n
      <p >最后，还是想对现在的朱茵说，与其在大荧幕上让年华已逝的自己接受苛刻观众对自己容貌、演技的挑刺，倒不如多参加点综艺节目，既能被一众年轻明星抬爱成女神，又能打扮得美美的展示“真实”的自我
STR;

        $text = '<img src = "http://image11.m1905.cn/uploadfile/2017/0902/20170902102625454609.jpg" title="迅雷电影下载_2017最新电影电视剧_感恩教师节——细数电影世界里形形色色的老师们电影全解码(转载)" alt="迅雷电影下载_2017最新电影电视剧_感恩教师节——细数电影世界里形形色色的老师们电影全解码(转载)">,<img src = "http://image13.m1905.cn/uploadfile/2017/0902/20170902102849335789.jpg" title="迅雷电影下载_2017最新电影电视剧_感恩教师节——细数电影世界里形形色色的老师们电影全解码(转载)" alt="迅雷电影下载_2017最新电影电视剧_感恩教师节——细数电影世界里形形色色的老师们电影全解码(转载)">,<img src = "http://image11.m1905.cn/uploadfile/2017/0902/20170902103012836004.jpg" title="迅雷电影下载_2017最新电影电视剧_感恩教师节——细数电影世界里形形色色的老师们电影全解码(转载)" alt="迅雷电影下载_2017最新电影电视剧_感恩教师节——细数电影世界里形形色色的老师们电影全解码(转载)">,<img src = "http://image13.m1905.cn/uploadfile/2017/0902/20170902103135418382.jpg" title="迅雷电影下载_2017最新电影电视剧_感恩教师节——细数电影世界里形形色色的老师们电影全解码(转载)" alt="迅雷电影下载_2017最新电影电视剧_感恩教师节——细数电影世界里形形色色的老师们电影全解码(转载)">,<img src = "http://image13.m1905.cn/uploadfile/2017/0902/20170902103344864610.jpg" title="迅雷电影下载_2017最新电影电视剧_感恩教师节——细数电影世界里形形色色的老师们电影全解码(转载)" alt="迅雷电影下载_2017最新电影电视剧_感恩教师节——细数电影世界里形形色色的老师们电影全解码(转载)">,<img src = "http://image13.m1905.cn/uploadfile/2017/0902/20170902103532274360.jpg" title="迅雷电影下载_2017最新电影电视剧_感恩教师节——细数电影世界里形形色色的老师们电影全解码(转载)" alt="迅雷电影下载_2017最新电影电视剧_感恩教师节——细数电影世界里形形色色的老师们电影全解码(转载)">,<img src = "http://image11.m1905.cn/uploadfile/2017/0902/20170902103950914508.jpg" title="迅雷电影下载_2017最新电影电视剧_感恩教师节——细数电影世界里形形色色的老师们电影全解码(转载)" alt="迅雷电影下载_2017最新电影电视剧_感恩教师节——细数电影世界里形形色色的老师们电影全解码(转载)">,<img src = "http://image14.m1905.cn/uploadfile/2017/0902/20170902103838316104.jpg" title="迅雷电影下载_2017最新电影电视剧_感恩教师节——细数电影世界里形形色色的老师们电影全解码(转载)" alt="迅雷电影下载_2017最新电影电视剧_感恩教师节——细数电影世界里形形色色的老师们电影全解码(转载)">';


        $str = explode('%s',$str);
        $last = array_pop($str);
        $text = explode(',',$text);
//        dd(count($str),count($text));

        foreach ($str as $key=>&$value){
           $value = $value.$text[$key];
        }
        $str = implode('',$str);
        $str = $str.$last;
        echo $str;
    }
}
