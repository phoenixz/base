<?php
/*
 * Internet library, contains all kinds of internet related functions
 *
 * These functions do not have a prefix
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>, Johan Geuze
 */



/*
 * Get IPv4 from IPv6
 * Kindly provided by http://stackoverflow.com/questions/12435582/php-serverremote-addr-shows-ipv6
 * Rewritten for use in Base by Sven Oostenbrink
 */
function ip_v6_v4($ipv6){
    try{
        /*
         * Known prefix
         */
        $v4mapped_prefix_hex = '00000000000000000000ffff';
        $v4mapped_prefix_bin = hex2bin($v4mapped_prefix_hex);

        /*
         * Parse
         */
        $addr     = $_SERVER['REMOTE_ADDR'];
        $addr_bin = inet_pton($addr);

        if($addr_bin === false){
            /*
             * IP Unparsable? How did they connect?
             */
            throw new bException(tr('IP address ":ip" is invalid', array(':ip' => $ipv6)), 'invalid');
        }

        /*
         * Check prefix
         */
        if(substr($addr_bin, 0, strlen($v4mapped_prefix_bin)) == $v4mapped_prefix_bin){
            /*
             * Strip prefix
             */
            $addr_bin = substr($addr_bin, strlen($v4mapped_prefix_bin));
        }

        /*
         * Convert back to printable address in canonical form
         */
        $ipv4 = inet_ntop($addr_bin);
        return $ipv4;

    }catch(Exception $e){
        throw new bException(tr('ip_v6_v4(): Failed'), $e);
    }
}



/*
 * Returns 4 if the specified (or if not specified, current) IP address is ipv4
 * Returns 6 if the specified (or if not specified, current) IP address is ipv6
 */
function detect_ip_version($version = null){
    try{
        if(!$version){
            $version = $_SERVER['REMOTE_ADDR'];
        }

        return strpos($version, ':') ? 6 : 4;

    }catch(Exception $e){
        throw new bException(tr('detect_ip_version(): Failed'), $e);
    }
}



/*
 * Returns true if specified (or if not, current) IP address is ipv6
 */
function is_ipv6($version = null){
    try{
        return detect_ip_version($version) === 6;

    }catch(Exception $e){
        throw new bException(tr('is_ipv6(): Failed'), $e);
    }
}



/*
 * Correct domain name
 */
function domain_correct($count){
    $_SERVER['SERVER_NAME'] = substr($_SERVER['SERVER_NAME'], $count);
    $_SERVER['HTTP_HOST']   = substr($_SERVER['HTTP_HOST']  , $count);
}



/*
 * Check if a domainname is valid
 */
function is_valid_domain_name($domain_name) {
    $pieces = explode('.', $domain_name);

    foreach($pieces as $piece) {
        if (!preg_match('/^[a-z\d][a-z\d-]{0,62}$/i', $piece) or preg_match('/-$/', $piece)) {
            return false;
        }
    }

    return true;
}



/*
 * Return a stripped domain name, no bullshit around it.
 */
function inet_get_domain($strip = array('www', 'dev', 'm')){
    try{
        if(in_array(str_until($_SERVER['SERVER_NAME'], '.'), array_force($strip))){
            return str_from($_SERVER['SERVER_NAME']);
        }

        return $_SERVER['SERVER_NAME'];

    }catch(Exception $e){
        throw new bException(tr('inet_get_domain(): Failed'), $e);
    }
}



/*
 * Get subdomain from domain (when knowing what the domain is)
 */
function inet_get_subdomain($domain = null, $strip = array('www', 'dev', 'm')){
    global $_CONFIG;

    try{
        if(!$domain){
            $domain = $_SERVER['SERVER_NAME'];
        }

        $subdomain = str_until($domain, '.');

        if(in_array(str_until($_SERVER['SERVER_NAME'], '.'), array_force($strip))){
            return false;
        }

        return $subdomain;

    }catch(Exception $e){
        throw new bException(tr('inet_get_subdomain(): Failed'), $e);
    }
}



/*
 * Ensure that the specified URL has a (or the specified) protocol
 */
function ensure_protocol($url, $protocol = 'http', $force = false){
    if(preg_match('/^(\w{2,6}):\/\//i', $url, $matches)){
        /*
         * The URL has a protocol
         */
        if($force and ($matches[1] != $protocol)){
            /*
             * It has not the protocol it should have, force protocol
             */
            return $protocol.'://'.str_from($url, '://');
        }

        return $url;
    }

    /*
     * The URL does not have a protocol. Add one now.
     */
    if(substr($url, 0, 2) == '//'){
        return $protocol.':'.$url;

    }else{
        return $protocol.'://'.$url;
    }
}



/*
 * Add specified query to the specified URL and return
 */
function url_add_query($url){
    try{
        $queries = func_get_args();
        unset($queries[0]);

        if(!$queries){
            throw new bException('url_add_query(): No queries specified');
        }

        foreach($queries as $query){
            if(!$query) continue;

            if(is_string($query) and strstr($query, '&')){
                $query = explode('&', $query);
            }

            if(is_array($query)){
                foreach($query as $part){
                    $url = url_add_query($url, $part);
                }

                continue;
            }

            if($query === true){
                $query = $_SERVER['QUERY_STRING'];
            }

            $url = str_ends_not($url, '?');

            if(!preg_match('/.+?=.*?/', $query)){
                throw new bException(tr('url_add_query(): Invalid query ":query" specified. Please ensure it has the "key=value" format', array(':query' => $query)), 'invalid');
            }

            $key = str_until($query, '=');

            if(strpos($url, '?') === false){
                /*
                 * This URL has no query yet, begin one
                 */
                $url .= '?'.$query;

            }elseif(strpos($url, $key.'=') !== false){
                /*
                 * The query already exists in the specified URL, replace it.
                 */
                $replace = str_cut($url, $key.'=', '&');
                $url     = str_replace($key.'='.$replace, $key.'='.str_from($query, '='), $url);

            }else{
                /*
                 * Append the query to the URL
                 */
                $url = str_ends($url, '&').$query;
            }
        }

        return $url;

    }catch(Exception $e){
        throw new bException('url_add_query(): Failed', $e);
    }
}



/*
 * Add specified query to the specified URL and return
 */
function url_remove_keys($url, $keys){
    try{
        $query = str_from($url , '?');
        $url   = str_until($url, '?');
        $query = explode('&', $query);

        foreach($query as $id => $kv){
            foreach(array_force($keys) as $key){
                if(str_until($kv, '=') == $key){
                    unset($query[$id]);

                    /*
                     * Don't break in case the specified key exists twice in the URL (might happen somehow)
                     */
                }
            }
        }

        if($query){
            return $url.'?'.implode('&', $query);
        }

        return $url;

    }catch(Exception $e){
        throw new bException('url_remove_keys(): Failed', $e);
    }
}



/*
 * Return information about a domain
 *
 * See http://en.wikipedia.org/wiki/List_of_DNS_record_types for more information
 */
function inet_dig($domain, $section = false){
    try{
        $data   = str_from(shell_exec('dig '.cfm($domain).' ANY'), 'ANSWER: ');

        if(str_until($data, ',') == '0'){
            throw new bException('inet_dig(): Specified domain "'.str_log($domain).'" was not found', 'notfound');
        }

        $data   = str_cut($data, "ANSWER SECTION:\n", "\n;;");
        $retval = array();

        if($section){
            /*
             * If specific sections were requested,
             * then store those lowercased in an array for easy lookup
             */
            $section = array_flip(array_force(strtolower(str_force($section))));
        }

        /*
         * Get A record
         */
        if(!$section or isset($section['a'])){
            preg_match_all('/'.cfm($domain).'.\s+\d+\s+IN\s+A\s+(\d+\.\d+\.\d+\.\d+)/imsu', $data, $matches);
            if(!empty($matches[1])){
                $retval['A'] = $matches[1];
            }
        }

        /*
         * Get MX record
         */
        if(!$section or isset($section['mx'])){
            preg_match_all('/'.cfm($domain).'.\s+\d+\s+IN\s+MX\s+(.+?)\n/imsu', $data, $matches);
            if(!empty($matches[1])){
                $retval['MX'] = $matches[1];
            }
        }

        /*
         * Get TXT record
         */
        if(!$section or isset($section['txt'])){
            preg_match_all('/'.cfm($domain).'.\s+\d+\s+IN\s+TXT\s+(.+?)\n/imsu', $data, $matches);
            if(!empty($matches[1])){
                $retval['TXT'] = $matches[1];
            }
        }

        /*
         * Get SOA record
         */
        if(!$section or isset($section['soa'])){
            preg_match_all('/'.cfm($domain).'.\s+\d+\s+IN\s+SOA\s+(.+?)\n/imsu', $data, $matches);
            if(!empty($matches[1])){
                $retval['SOA'] = $matches[1];
            }
        }

        /*
         * Get CNAME record
         */
        if(!$section or isset($section['cname'])){
            preg_match_all('/'.cfm($domain).'.\s+\d+\s+IN\s+CNAME\s+(\d+\.\d+\.\d+\.\d+)\n/imsu', $data, $matches);
            if(!empty($matches[1])){
                $retval['CNAME'] = $matches[1];
            }
        }

        /*
         * Get NS record
         */
        if(!$section or isset($section['ns'])){
            preg_match_all('/'.cfm($domain).'.\s+\d+\s+IN\s+NS\s+(.+?)\n/imsu', $data, $matches);
            if(!empty($matches[1])){
                $retval['NS'] = $matches[1];
            }
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('inet_dig(): Failed', $e);
    }
}



/*
 * Here be wrapper monsters for obsolete functions
 */
function get_domain($strip = array('www', 'dev', 'm')){
    return inet_get_domain($strip);
}



/*
 *
 */
function inet_get_client_data(){
    try{
        /*
         * Fetch user data
         * Get email, IPv4, IPv6, user_agent, reverse host, provider, longitude, latitude, referrer
         */
        load_libs('geoip');

        $client['ip']           = $_SERVER['REMOTE_ADDR'];
        $client['email']        = isset_get($_GET['email']);
        $client['user_agent']   = isset_get($_SERVER['HTTP_USER_AGENT']);
        $client['referrer']     = isset_get($_SERVER['HTTP_REFERER']);
        $client['reverse_host'] = gethostbyaddr($client['ip']);

        $geodata = geoip_get($client['ip']);

        $client['latitude']  = isset_get($geodata['latitude']);
        $client['longitude'] = isset_get($geodata['longitude']);

        //if(empty($client['email']) and $_CONFIG['production']){
        //    header("HTTP/1.0 404 Not Found");
        //    die('404 - Not Found');
        //}



        /*
         * Detect browser
         */
        $oses = array('mac'     => 'desktop',
                      'linux'   => 'desktop',
                      'android' => 'mobile',
                      'iphone'  => 'mobile',
                      'windows' => 'desktop');

        if(empty($_SESSION['client'])){
            $user_agent = strtolower($client['user_agent']);
            $browsers   = array('chrome',
                                'firefox',
                                'msie 10',
                                'msie 9',
                                'msie 8',
                                'msie 7',
                                'opera');

            foreach($browsers as $browser){
                if(strstr($user_agent, $browser)){
                    $client['browser'] = $browser;
                    break;
                }
            }

            if(empty($client['browser'])){
                $client['browser'] = 'unknown';
            }

            /*
             * Detect operating system
             */
            $user_agent = strtolower($client['user_agent']);

            foreach($oses as $os => $platform){
                if(strstr($user_agent, $os)){
                    $client['os']       = $os;
                    $client['platform'] = $platform;
                    break;
                }
            }

        }else{
            /*
             * Get the information from $_SESSION[client]
             */
            $client['browser']  = strtolower(isset_get($_SESSION['client']['info']['browser']));
            $client['os']       = strtolower(isset_get($_SESSION['client']['info']['platform']));
            $client['platform'] = (empty($_SESSION['client']['info']['ismobiledevice']) ? 'desktop' : 'mobile');
        }

        if(empty($client['os'])){
            $client['os']       = 'unknown';
            $client['platform'] = 'unknown';
        }

        return $client;

    }catch(Exception $e){
        throw new bException('inet_get_client_data(): Failed', $e);
    }
}
?>
