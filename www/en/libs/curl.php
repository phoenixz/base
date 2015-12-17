<?php
/*
 * cURL library
 *
 * Functions used for cURL things
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Johan Geuze, Sven Oostenbrink <support@svenoostenbrink.com>
 */



load_config('curl');

if(!function_exists('curl_init')){
    throw new bException('PHP CURL module is not installed. Install PHP CURL on Ubuntu with "sudo apt-get install php5-curl", or on Redhat with "sudo yum install php-curl"');
}



 /*
  * Get data using an sven HTTP proxy server
  */
function curl_get_proxy($url, $file = '', $serverurl = null) {
    global $_CONFIG;

    try{
        if(!$serverurl){
            $serverurl = $_CONFIG['curl']['proxies'];
        }

        if(is_array($serverurl)){
            $serverurl = array_random_value($serverurl);
        }

        if(is_array($url)){
            throw new bException(tr('curl_get_proxy(): No URL specified'), 'notspecified');
        }

        if(!$serverurl){
            throw new bException(tr('curl_get_proxy(): No proxy server URL(s) specified'), 'notspecified');
        }

        if(VERBOSE){
            log_console(tr('Using proxy "%proxy%"', array('%proxy%' => str_cut(str_log($serverurl), '://', '/'))), 'curl_get_proxy()');
        }

        $data = curl_get(array('url'        => str_ends($serverurl, '?apikey='.$_CONFIG['curl']['apikey'].'&url=').urlencode($url),
                               'getheaders' => false,
                               'proxy'      => false));

        if(!trim($data['data'])){
            throw new bException(tr('curl_get_proxy(): Proxy returned no data. Is proxy correctly configured? Proxy domain resolves correctly?', array('%data%' => $data)), 'notspecified');
        }

        if(substr($data['data'], 0, 12) !== 'PROXY_RESULT'){
            throw new bException(tr('curl_get_proxy(): Proxy returned invalid data "%data%" from proxy "%proxy%". Is proxy correctly configured? Proxy domain resolves correctly?', array('%data%' => str_log($data), '%proxy%' => str_cut(str_log($serverurl), '://', '/'))), 'notspecified');
        }

        $data         = substr($data['data'], 12);
        $data         = json_decode_custom($data);
        $data['data'] = base64_decode($data['data']);

        if($file){
            /*
             * Write the data to the specified file
             */
            file_put_contents($file, $data['data']);
        }

        return $data;

    }catch(Exception $e){
        throw new bException('curl_get_proxy(): Failed', $e);
    }
}



/*
 * Returns a random IP from the pool of all IP's available on this computer
 * 127.0.0.1 will NOT be returned, all other IP's will
 */
// :TODO: ADD IPV6 SUPPORT!
function curl_get_random_ip($allowipv6 = false) {
    global $col;

    try{
        $result = implode("\n", safe_exec('/sbin/ifconfig'));

        if(!preg_match_all('/(?:addr|inet):(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}) /', $result, $matches)){
            throw new bException('curl_get_random_ip(): ifconfig returned no IPs');
        }

        if(!$matches or empty($matches[1])) {
            throw new bException('curl_get_random_ip(): No IP data found');
        }

        foreach($matches[1] as $ip){
//            if(!preg_match('/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/', $ip)){
//                if($allowipv6){
//throw new bException('curl_get_random_ip(): IPv6 support is not yet supported');
//                }
//
//                continue;
//            }

            if($ip == '127.0.0.1'){
                /*
                 * Doh, can't rip over localhost! :)
                 */
                continue;
            }

            $ips[] = $ip;
        }

        unset($matches);

        if(empty($ips)) {
            throw new bException('curl_get_random_ip(): No IPs found');
        }

        return $ips[array_rand($ips)];

    }catch(Exception $e){
        throw new bException('curl_get_random_ip(): Failed', $e);
    }
}



///*
// *
// */
//function curl_get_random_ip() {
//	global $col;
//
//    try{
//        $ips = explode("\n", safe_exec('/sbin/ifconfig|grep "Mask:255.255.255.0"|sed "s/^.*addr://"|sed "s/ .*//"'));
//
//        shuffle($ips);
//
//        if(!$ips) {
//            return '';
//        }
//
//        return $ips[0];
//
//    }catch(Exception $e){
//        throw new bException('curl_get_random_ip(): Failed', $e);
//    }
//}



/*
 * Get files from the internet
 */
function curl_get($params, $referer = null, $post = false, $options = array()){
    static $retry;
    global $_CONFIG;

    try{
        array_params($params, 'url');
        array_default($params, 'referer'        , $referer);
        array_default($params, 'useragent'      , $_CONFIG['curl']['user_agents']);
        array_default($params, 'post'           , $post);
        array_default($params, 'posturlencoded' , false);
        array_default($params, 'options'        , $options);
        array_default($params, 'ch'             , false);
        array_default($params, 'close'          , ($params['ch'] ? false : true));
        array_default($params, 'getdata'        , true);
        array_default($params, 'getstatus'      , true);
        array_default($params, 'cookies'        , true);
        array_default($params, 'file'           , false);
        array_default($params, 'getcookies'     , false);
        array_default($params, 'getheaders'     , true);
        array_default($params, 'followlocation' , true);
        array_default($params, 'httpheaders'    , true);
        array_default($params, 'content-type'   , false);
        array_default($params, 'cache'          , false);
        array_default($params, 'verbose'        , null);
        array_default($params, 'proxy'          , $_CONFIG['curl']['proxy']);
        array_default($params, 'simulation'     , false); // false, partial, or full
        array_default($params, 'sleep'          , 15);    // Sleep howmany seconds between retries
        array_default($params, 'retries'        ,  5);    // Retry howmany time on HTTP0 failures
        array_default($params, 'timeout'        , 10);    // # of seconds for cURL functions to execute
        array_default($params, 'connect_timeout', 10);    // # of seconds before connection try will fail

        if($params['proxy']){
            return curl_get_proxy($params['url'], $params['file']);
        }

        if($params['httpheaders'] === true){
            /*
             * Send headers that hide cURL
             */
            $params['httpheaders'] = array('Accept: text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5',
                                           'Cache-Control: max-age=0',
                                           'Connection: keep-alive',
                                           'Keep-Alive: 300',
                                           'Accept-Charset: utf-8,ISO-8859-1;q=0.7,*;q=0.7',
                                           'Accept-Language: en-us,en;q=0.5',
                                           'Pragma: ');

        }elseif($params['httpheaders'] and !is_array($params['httpheaders'])){
                throw new bException('curl_get(): Invalid headers specified');
        }

        if(empty($params['url'])){
            throw new bException('curl_get(): No URL specified');
        }

        /*
         * Use the already existing cURL data array
         */
        if(empty($params['curl'])){
            $retval = array('simulation' => $params['simulation']);

        }else{
            $retval               = $params['curl'];
            $params['ch']         = $params['curl']['ch'];
            $params['simulation'] = $params['curl']['simulation'];
            $params['close']      = false;
        }

        if(is_array($params['useragent'])){
            $params['useragent'] = array_get_random($params['useragent']);
        }

        /*
         * Use the already existing cURL connection?
         */
        if($params['ch']){
            /*
             * Use an existing cURL connection
             */
            $ch = $params['ch'];
            curl_setopt($ch, CURLOPT_URL, $params['url']);

        }else{
            /*
             * Create a new cURL connection
             */
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL           , $params['url']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT     , ($params['useragent'] ? $params['useragent'] : curl_get_random_user_agent()));
            curl_setopt($ch, CURLOPT_INTERFACE     , curl_get_random_ip());
            curl_setopt($ch, CURLOPT_TIMEOUT       , $params['timeout']);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $params['connect_timeout']);

            /*
             * Use cookies?
             */
            if(isset_get($params['cookies'])){
                load_libs('file');
                if(!isset_get($params['cookie_file'])){
                    $params['cookie_file'] = file_temp();
                }

                $retval['cookie_file'] = $params['cookie_file'];

                /*
                 * Make sure the specified cookie path exists
                 */
                file_ensure_path(dirname($params['cookie_file']));

                /*
                 * Set cookie options
                 */
                curl_setopt($ch, CURLOPT_COOKIEJAR     , $params['cookie_file']);
                curl_setopt($ch, CURLOPT_COOKIEFILE    , $params['cookie_file']);
                curl_setopt($ch, CURLOPT_COOKIESESSION , true);
            }
        }

        curl_setopt($ch, CURLOPT_VERBOSE       , not_empty($params['verbose'], null));
        curl_setopt($ch, CURLOPT_REFERER       , not_empty($params['referer'], null));
        curl_setopt($ch, CURLOPT_HEADER        , ($params['getcookies'] or $params['getheaders'] ?  1 : 0));
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, ($params['followlocation']                      ?  1 : 0));
        curl_setopt($ch, CURLOPT_MAXREDIRS     , ($params['followlocation']                      ? 50 : null));

        if($params['post'] !== false) {
            if($params['content-type']){
                curl_setopt($ch, CURLINFO_CONTENT_TYPE, $params['content-type']);
            }

            curl_setopt($ch, CURLOPT_POST, 1);

//            if($params['utf8']){
//                /*
//                 * Set UTF8 transfer header
//                 */
//                if(!$params['httpheaders']){
//                    $params['httpheaders'] = array();
//                }
//application/x-www-form-urlencoded
////                $params['httpheaders'][] = 'Content-Type: application/x-www-form-urlencoded; charset='.$_CONFIG['charset'].';';
////                $params['httpheaders'][] = 'Content-Type: application/x-www-form-urlencoded; charset='.$_CONFIG['charset'].';';
////                $params['httpheaders'][] = 'Content-Type: text/html; charset='.strtolower($_CONFIG['charset']).';';
//            }

            if($params['posturlencoded']){
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params['post']));

            }else{
                curl_setopt($ch, CURLOPT_POSTFIELDS, $params['post']);
            }

        }else{
            curl_setopt($ch, CURLOPT_POST, 0);
        }

        if($params['httpheaders'] !== false){
//show($params['httpheaders']);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $params['httpheaders']);
        }

        /*
         * Apply more cURL options
         */
        if($params['options']){
            foreach($params['options'] as $key => $value){
                curl_setopt($ch, $key, $value);
            }
        }

        if($params['cache']){
            if($retval = sql_get('SELECT `data` FROM `curl_cache` WHERE `url` = :url', 'data', array(':url' => $params['url']))){
                $retry = 0;

                load_libs('json');
                return json_decode_custom($retval);
            }
        }

        if($params['getdata']){
            if($params['simulation'] === false){
                $retval['data'] = curl_exec($ch);

            }elseif(($params['simulation'] === 'full') or ($params['simulation'] === 'partial')){
                $retval['data'] = $params['simulation'];

            }else{
                throw new bException('curl_get(): Unknown simulation type "'.str_log($params['simulation']).'" specified. Please use either false, "partial" or "full"', 'unknown');
            }
        }

        if($params['getstatus']){
            if($params['simulation']){
                $retval['status'] = array('http_code'  => 200,
                                          'simulation' => true);

            }else{
                $retval['status'] = curl_getinfo($ch);
            }
        }

        if($params['getcookies']){
            /*
             * get cookies
             */
            preg_match('/^Set-Cookie:\s*([^;]*)/mi', $retval['data'], $matches);

            if(empty($matches[1])){
                $retval['cookies'] = array();

            }else{
                parse_str($matches[1], $retval['cookies']);
            }
        }

        if($params['close']){
            /*
             * Close this cURL session
             */
            if(!empty($retval['cookie_file'])){
                file_delete($retval['cookie_file']);
            }

            unset($retval['cookie_file']);
            curl_close($ch);

        }else{
            $retval['ch']  = $ch;
            $retval['url'] = $params['url'];
        }

        if($params['cache']){
            load_libs('json');

            unset($retval['ch']);

            sql_query('INSERT INTO `curl_cache` (`users_id`, `url`, `data`)
                      VALUES                    (:users_id , :url , :data)

                      ON DUPLICATE KEY UPDATE `data` = :data;',

                      array(':users_id' => (empty($_SESSION['user']['id']) ? null : $_SESSION['user']['id']),
                            ':url'      => $params['url'],
                            ':data'     => json_encode_custom($retval)));
        }

        if($retval['status']['http_code'] != 200){
            load_libs('http');
            throw new bException('curl_get(): URL "'.str_log($params['url']).'" gave HTTP "'.str_log($retval['status']['http_code']).'"', 'HTTP'.$retval['status']['http_code'], null, $retval);
        }

        if($params['file']){
            file_put_contents($params['file'], $retval['data']);
        }

        $retry = 0;
        return $retval;

    }catch(Exception $e){
        if(($e->getCode() == 'HTTP0') and (++$retry <= $params['retries'])){
            /*
             * For whatever reason, connection gave HTTP code 0 which probably
             * means that the server died off completely. This again may mean
             * that the server overloaded. Wait for a few seconds, and try again
             * for a limited number of times
             *
             */
            sleep($params['sleep']);
            log_error('curl_get(): Got HTTP0 for url "'.str_log($params['url']).'", retry "'.str_log($retry).'"', 'HTTP0');
            return curl_get($params, $referer, $post, $options);
        }

        throw new bException('curl_get(): Failed to get url "'.str_log($params['url']).'"', $e);
    }
}



/*
 * Return random user agent
 */
function curl_get_random_user_agent() {
    $agents = array('Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322; Alexa Toolbar)',
                    'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.2.1) Gecko/20021204',
                    'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; WOW64; Trident/5.0)',
                    'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/29.0.1547.57 Safari/537.36');

    shuffle($agents);

    return $agents[0];
}

?>
