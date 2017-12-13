<?php
/**
 *  HTML转换为文本
 * @param    string $str 需要转换的字符串
 */
if (!function_exists('SpHtml2Text')) {
    function SpHtml2Text($str)
    {
        $str = preg_replace("/<sty(.*)\\/style>|<scr(.*)\\/script>|<!--(.*)-->/isU", "", $str);
        $alltext = "";
        $start = 1;
        for ($i = 0; $i < strlen($str); $i++) {
            if ($start == 0 && $str[$i] == ">") {
                $start = 1;
            } else if ($start == 1) {
                if ($str[$i] == "<") {
                    $start = 0;
                    $alltext .= " ";
                } else if (ord($str[$i]) > 31) {
                    $alltext .= $str[$i];
                }
            }
        }
        $alltext = str_replace("　", " ", $alltext);
        $alltext = preg_replace("/&?([^;&]*)(;|&)/", "", $alltext);
        $alltext = preg_replace("/[ ]+/s", " ", $alltext);
        return $alltext;
    }
}

/**
 * 去除一些特殊字符
 */
if (!function_exists('_filterSpuerChar')) {
    function _filterSpuerChar($str)
    {
        $str = str_replace('`', '', $str);
        $str = str_replace('·', '', $str);
        $str = str_replace('~', '', $str);
        $str = str_replace('!', '', $str);
        $str = str_replace('！', '', $str);
        $str = str_replace('@', '', $str);
        $str = str_replace('#', '', $str);
        $str = str_replace('$', '', $str);
        $str = str_replace('￥', '', $str);
        $str = str_replace('%', '', $str);
        $str = str_replace('^', '', $str);
        $str = str_replace('……', '', $str);
        $str = str_replace('&', '', $str);
        $str = str_replace('*', '', $str);
        $str = str_replace('(', '', $str);
        $str = str_replace(')', '', $str);
        $str = str_replace('（', '', $str);
        $str = str_replace('）', '', $str);
        $str = str_replace('-', '', $str);
        $str = str_replace('_', '', $str);
        $str = str_replace('——', '', $str);
        $str = str_replace('+', '', $str);
        $str = str_replace('=', '', $str);
        $str = str_replace('|', '', $str);
        $str = str_replace('\\', '', $str);
        $str = str_replace('[', '', $str);
        $str = str_replace(']', '', $str);
        $str = str_replace('【', '', $str);
        $str = str_replace('】', '', $str);
        $str = str_replace('{', '', $str);
        $str = str_replace('}', '', $str);
        $str = str_replace(';', '', $str);
        $str = str_replace('；', '', $str);
        $str = str_replace(':', '', $str);
        $str = str_replace('：', '', $str);
        $str = str_replace('\'', '', $str);
        $str = str_replace('"', '', $str);
        $str = str_replace('“', '', $str);
        $str = str_replace('”', '', $str);
        $str = str_replace(',', '', $str);
        $str = str_replace('，', '', $str);
        $str = str_replace('<', '', $str);
        $str = str_replace('>', '', $str);
        $str = str_replace('《', '', $str);
        $str = str_replace('》', '', $str);
        $str = str_replace('.', '', $str);
        $str = str_replace('。', '', $str);
        $str = str_replace('/', '', $str);
        $str = str_replace('、', '', $str);
        $str = str_replace('?', '', $str);
        $str = str_replace('？', '', $str);
        $str = str_replace(' ', '', $str);
        $str = str_replace('　', '', $str);
        return trim($str);
    }
}


/**
 * 随机分类
 */
if(!function_exists('_getRandType'))
{
    function _getRandType()
    {
        $typeArr = [
            '动作',
            '喜剧',
            '爱情',
            '冒险',
            '文艺',
            '惊悚',
            '青春',
            '战争',
            '女性',
            '科幻',
            '家庭',
            '警匪',
            '体育',
            '神话',
            '武侠',
            '乡村',
            '传记',
            '灾难',
            '剧情',
            '经典',
            '系列',
        ];
        shuffle($typeArr);
        return implode(',',array_slice($typeArr,0,2));
    }
}


/**
 * 随机生成国内IP
 *
 * @return string
 */
if(!function_exists('getRandIp')) {
    function getRandIp()
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

/**
 * 得到一个文件的后缀
 */
if(!function_exists('getExt')) {
    function getExt($path)
    {
        $ext = null;
        if (preg_match('/^http(s)?(.*?)/is', $path)) {
            $path = parse_url($path, PHP_URL_PATH);
        }
        $ext = @pathinfo($path, PATHINFO_EXTENSION);
        return $ext;
    }
}

/**
 * 去除css样式
 */
if(!function_exists('removeCss')) {
    function removeCss($html)
    {
        $pattern = array('/id\s*=\s*["\'](.*?)["\']/is', '/class\s*=\s*["\'](.*?)["\']/is', '/style\s*=\s*["\'](.*?)["\']/is', '/max-width\s*=\s*["\'](.*?)["\']/is', '/min-width\s*=\s*["\'](.*?)["\']/is', '/max-height\s*=\s*["\'](.*?)["\']/is', '/min-height\s*=\s*["\'](.*?)["\']/is', '/data-(.*?)\s*=\s*["\'](.*?)["\']/is', '/width\s*=\s*["\'](.*?)["\']/is', '/height\s*=\s*["\'](.*?)["\']/is', '/src\s*=\s*["\']\/\/(.*?)["\']/is');
        $replace = array('', '', '', '', '', '', '', '', '', '', 'src="http://$1"');
        $html = preg_replace($pattern, $replace, $html);
        return $html;
    }
}


