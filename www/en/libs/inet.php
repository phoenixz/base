<?php
/*
 * Internet library, contains all kinds of internet related functions
 *
 * These functions do not have a prefix
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>, Johan Geuze
 */


/*
 * Send HTTP header
 */
function http($code){
    try{
        load_libs('http');
        http_header($code);

    }catch(Exception $e){
        throw new lsException('http(): Failed', $e);
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
function get_domain(){
    if(in_array(substr($_SERVER['SERVER_NAME'], 0, 4), array('www.', 'dev.'))){
        return cfm(substr($_SERVER['SERVER_NAME'], 4));

    }elseif(substr($_SERVER['SERVER_NAME'], 0, 2) == 'm.'){
        return cfm(substr($_SERVER['SERVER_NAME'], 2));

    }else{
        return cfm($_SERVER['SERVER_NAME']);
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
function url_add_query($url, $query){
    if(strpos($url, '?') === false){
        return $url.'?'.$query;
    }

    return $url.'&'.$query;
}



/*
 * Get subdomain from domain (when knowing what the domain is)
 */
function inet_get_subdomain($domain) {
    global $_CONFIG;

    $subdomain = str_until(str_replace($_CONFIG['domain'],'',$domain),'.');

    if($subdomain == 'www'){
        return '';
    }

    return $subdomain;
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
            throw new lsException('inet_dig(): Specified domain "'.str_log($domain).'" was not found', 'notfound');
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
        throw new lsException('inet_dig(): Failed', $e);
    }
}
?>
