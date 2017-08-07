<?php

namespace App\Console\Commands\Caiji;

use Illuminate\Console\Command;

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
     * 得到网站icp信息
     */
    const PREFIX = 'http://www.alexa.cn/';
    const WHOIS = 'http://www.alexa.cn/api/who_is/get?token=';
    const SERVER = 'http://www.alexa.cn/api/server/get?token=';

    protected $agents = [
        'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_8_4 rv:6.0; en-US) AppleWebKit/532.4.1 (KHTML, like Gecko) Version/5.0.3 Safari/532.4.1',
        'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_7_1 rv:4.0; sl-SI) AppleWebKit/532.5.5 (KHTML, like Gecko) Version/4.0.2 Safari/532.5.5',
        'Opera/8.16 (X11; Linux x86_64; en-US) Presto/2.11.335 Version/12.00',
        'Mozilla/5.0 (X11; Linux i686) AppleWebKit/5341 (KHTML, like Gecko) Chrome/36.0.820.0 Mobile Safari/5341',
        'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/5351 (KHTML, like Gecko) Chrome/39.0.860.0 Mobile Safari/5351',
        'Mozilla/5.0 (X11; Linux i686; rv:7.0) Gecko/20160924 Firefox/36.0',
        'Opera/9.48 (Windows NT 5.0; en-US) Presto/2.10.278 Version/10.00',
        'Opera/8.24 (Windows NT 5.1; en-US) Presto/2.12.181 Version/12.00',
        'Opera/9.64 (X11; Linux x86_64; en-US) Presto/2.12.342 Version/12.00',
        'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 5.2; Trident/3.0)',
        'Mozilla/5.0 (compatible; MSIE 6.0; Windows NT 5.01; Trident/3.0)',
        'Mozilla/5.0 (compatible; MSIE 5.0; Windows NT 5.1; Trident/4.1)',
        'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 5.2; Trident/3.1)',
        'Mozilla/5.0 (X11; Linux x86_64; rv:6.0) Gecko/20130804 Firefox/36.0',
        'Mozilla/5.0 (Windows CE) AppleWebKit/5310 (KHTML, like Gecko) Chrome/39.0.834.0 Mobile Safari/5310',
        'Mozilla/5.0 (Windows; U; Windows NT 5.0) AppleWebKit/532.40.6 (KHTML, like Gecko) Version/5.0 Safari/532.40.6',
        'Mozilla/5.0 (X11; Linux x86_64; rv:6.0) Gecko/20131025 Firefox/37.0',
        'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 5.0; Trident/4.0)',
        'Mozilla/5.0 (X11; Linux i686; rv:7.0) Gecko/20100728 Firefox/37.0',
        'Mozilla/5.0 (compatible; MSIE 6.0; Windows NT 6.2; Trident/4.0)',
    ];

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
    }

}
