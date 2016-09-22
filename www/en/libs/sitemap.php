<?php
/*
 * Sitemap library
 *
 * This library contains functions to scan the site and deploy sitemaps
 *
 * Idea taken from http://yoast.com/xml-sitemap-php-script/
 * Written by Sven Oostenbrink
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */



/*
 * Requires sitemap configuration
 */
load_config('sitemap');



/*
 * Scan the site, and create sitemap data in database
 */
function sitemap_scan($params = array()){
    global $_CONFIG;

    try{
        array_params ($params, 'language');
        array_default($params, 'status'   , null);
        array_default($params, 'recursive', false);
        array_default($params, 'languages', $_CONFIG['sitemap']['languages']);
        array_default($params, 'scan'     , $_CONFIG['sitemap']['scan']);
        array_default($params, 'show_skip', false);


        /*
         * Determine for what language we are going to scan sitemaps
         */
        if(empty($params['languages'])){
            $params['languages'] = $_CONFIG['language']['supported'];
        }


        /*
         * Verify that specified languages are supported
         */
        foreach($params['languages'] as $code => $language){
            if(empty($_CONFIG['language']['supported'][$code])){
                throw new bException('sitemap_scan(): Specified (or configured) scan language "'.str_log($code).'" is not supported in $_CONFIG[languages][supported]', 'notsupported');
            }
        }

        load_libs('curl');


        /*
         * Create a sitemap for each language
         */
        $count = 0;

        foreach($params['languages'] as $code => $language){
            log_console('Processing language "'.$language.'"', 'language');

            sql_query('INSERT INTO `sitemap_scans` (`createdby`, `language`)
                       VALUES                      (:createdby , :language )',

                       array(':createdby' => $_SESSION['user']['id'],
                             ':language'  => $code));

            $params['scans_id'] = sql_insert_id();

            foreach($params['scan'] as $url){
                $count += sitemap_scan_url($params, str_replace('%language%', $language, $url));
            }
        }

        log_console(tr('Scanned and added "%count%" URLs to the sitemap database for "%languages%" langauges', array('%count%' => $count, '%languages%' => count($params['languages']))), 'finished', 'green');

    }catch(Exception $e){
        throw new bException('sitemap_scan(): Failed', $e);
    }
}



/*
 * Create the robots.txt file
 */
function sitemap_robots($params = array(), $return = false){
    global $_CONFIG;

    try{
        array_params($params);

        $r   = sitemap_get_scan_resource($params, true);
// :TODO: Add support for user agent specification
        $txt = "Sitemap: ".$_CONFIG['protocol'].$_SESSION['domain']."/sitemap.xml\nUser-agent: *\n";

        while($url = sql_fetch($r)){
            $url['url'] = str_replace($_CONFIG['protocol'], '', str_replace($_SESSION['domain'], '', $url['url']));
            $txt       .= 'Disallow: '.$url['url']."\n";
        }

        if($return){
            return $txt;
        }

        /*
         * Send text
         */
        echo $txt;

    }catch(Exception $e){
        throw new bException('sitemap_robots(): Failed', $e);
    }
}



/*
 * Create a sitemap in XML format
 */
function sitemap_xml($params = array(), $return = false){
    global $_CONFIG;

    try{
        array_params($params);

        $r   = sitemap_get_scan_resource($params, false);
        $xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n".
               "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

        if(isset_get($_CONFIG['sitemap']['xls'])){
            $xml .= '<?xml-stylesheet type="text/xsl" href="'.SITEMAP_DIR_URL.$xsl.'"?>'."\n";
        }

        while($url = sql_fetch($r)){
            $url['modified'] = new DateTime($url['modified']);

            $xml .= "<url>\n".
                    '   <loc>'.domain('/'.$url['url'])."</loc>\n".
                    '   <lastmod>'.$url['modified']->format('Y-m-d\TH:i:sP')."</lastmod>\n".
                    ($_CONFIG['sitemap']['show_changefreq'] ? '   <changefreq>'.$url['changefreq']."</changefreq>\n" : '').
                    ($_CONFIG['sitemap']['show_priority']   ? '   <priority>'.$url['priority']."</priority>\n"       : '').
                    "</url>\n";
        }

        $xml .= '</urlset>';

        if($return){
            return $xml;
        }

        /*
         * Send XML header
         */
        header('Content-Type: application/xml');

        echo $xml;

    }catch(Exception $e){
        throw new bException('sitemap_xml(): Failed', $e);
    }
}



/*
 * Create a sitemap in HTML format
 */
function sitemap_html($params = array(), $return = false){
    global $_CONFIG;

    try{
        array_params($params);

        $r    = sitemap_get_scan_resource($params, false);
        $html = "<!DOCTYPE html>\n<html><head><title></title></head><body><ul>";

        while($url = sql_fetch($r)){
            $url['modified'] = new DateTime($url['modified']);

            $html .= "<li>\n".
                    '   <span>'.domain('/'.$url['url'])."</span>\n".
                    '   <span>'.$url['modified']->format('Y-m-d\TH:i:sP')."</span>\n".
                    ($_CONFIG['sitemap']['show_changefreq'] ? '   <span>'.$url['changefreq']."</span>\n" : '').
                    ($_CONFIG['sitemap']['show_priority']   ? '   <span>'.$url['priority']."</span>\n"   : '').
                    "</li>\n";
        }

        $html .= '</ul></body></html>';

        if($return){
            return $html;
        }

        /*
         * Send html header
         */
        echo $html;

    }catch(Exception $e){
        throw new bException('sitemap_html(): Failed', $e);
    }
}



/*
 * Create a sitemap in plain text format
 */
function sitemap_txt($params = array(), $return = false){
    global $_CONFIG;

    try{
        array_params($params);

        $r     = sitemap_get_scan_resource($params, false);
        $text  = '';

        while($url = sql_fetch($r)){
            $text .= $url['url']."\n";
        }

        if($return){
            return $text;
        }

        echo $text;

    }catch(Exception $e){
        throw new bException('sitemap_txt(): Failed', $e);
    }
}



/*
 * Get the data resource for the specified parameters
 */
function sitemap_get_scan_resource($params, $disallowed){
    try{
        array_params($params);
        array_default($params, 'language', LANGUAGE);

        $scan  = sql_get('SELECT   `id`,
                                   `createdon`

                          FROM     `sitemap_scans`

                          WHERE    `language` = :language

                          ORDER BY `createdon` DESC

                          LIMIT 1',

                          array(':language' => str_log($params['language'])));

        if(!$scan){
            throw new bException('sitemap_get_scan_resource(): No sitemap scans found', 'notfound');
        }

        $r = sql_query('SELECT `id`,
                               `status`,
                               `url`,
                               `type`,
                               `modified`,
                               `priority`,
                               `changefreq`,
                               `description`

                        FROM   `sitemap_data`

                        WHERE  `scans_id` = :scans_id
                        AND    `disallow` = :disallow',

                        array(':scans_id' => $scan['id'],
                              ':disallow' => ($disallowed ? 1 : 0)));

        if(!$r->rowCount()){
            throw new bException('sitemap_get_scan_resource(): Scan "'.$scan['id'].'" from date "'.$scan['createdon'].'" for language "'.str_log($params['language']).'" has no data', 'empty');
        }

        return $r;

    }catch(Exception $e){
        throw new bException('sitemap_get_scan_resource(): Failed', $e);
    }
}



/*
 * Scan the specified url, and if not ignored or disallowed, add it to the sitemap list.
 * After that, scan the url for urls and recursively add those as well.
 *
 * If specified url is empty, just the basic domain will be used
 */
function sitemap_scan_url($params, $scan_url, $recursive = true){
    global $_CONFIG;

    try{
        array_params($params);
        array_default($params, 'scans_id'        , null);
        array_default($params, 'ignore'          , $_CONFIG['sitemap']['ignore']);
        array_default($params, 'disallow'         , $_CONFIG['sitemap']['disallow']);
        array_default($params, 'priority'        , $_CONFIG['sitemap']['priority']);
        array_default($params, 'filetypes'       , $_CONFIG['sitemap']['filetypes']);
        array_default($params, 'modified'        , $_CONFIG['sitemap']['modified']);
        array_default($params, 'change_frequency', $_CONFIG['sitemap']['change_frequency']);

        $count = 0;

        if(strstr($scan_url, 'tel:') or strstr($scan_url, 'mailto:') or strstr($scan_url, 'ftp:') or strstr($scan_url, 'sftp:')){
            /*
             * These protocols should not end up in sitemaps
             */
            if($params['show_skip']){
                log_console('Skipping URL "'.str_log($scan_url).'", the URL protocol is not valid in sitemap', 'skip', 'yellow');
            }

            return 0;
        }

        $url = slash($_CONFIG['protocol'].$_SESSION['domain']).str_starts_not($scan_url, '/');


        /*
         * Should this file be ignored?
         */
        if(in_array($url, array_force($params['ignore']))){
            if($params['show_skip']){
                log_console('URL "'.str_log($url).'" is (completly or partually) on the ignore list, skipping', 'url', 'yellow');
            }

            return 0;
        }


        /*
         * disallowed?
         */
        $disallow = 0;

        foreach($params['disallow'] as $disallow_rule => $disallow_url){
            try{
                if(preg_match($disallow_rule, $url)){
                    /*
                     * This URL should be disallowed
                     */
                    if($params['show_skip']){
                        log_console(tr('Disallowing URL "%url%", it is being disallowed with rule "%rule%"', array('%url%' => $url, '%rule%' => str_log($disallow_rule))), 'skip', 'yellow');
                    }

                    $disallow     = 1;
                    $disallow_url = str_replace('%protocol%', $_CONFIG['protocol'], str_replace('%domain%', $_SESSION['domain'], $disallow_url));
                    break;
                }

            }catch(Exception $e){
                throw new bException('sitemap_scan_url(): Invalid regex "'.str_log($disallow_rule).'" specified for URL disallow', $e);
            }
        }


        /*
         * URL already scanned?
         */
        if(sql_get('SELECT `id` FROM `sitemap_data` WHERE `scans_id` = :scans_id AND `url` = :url', array(':scans_id' => $params['scans_id'], ':url' => ($disallow ? $disallow_url : $url)))){
            if($params['show_skip']){
                log_console(tr('Skipping URL "%url%", it has already been scanned in this cycle', array('%url%' => $url)), 'skip', 'yellow');
            }

            return 0;
        }


        /*
         * We dont have to check ignored and extensions if the URL is disallowed
         */
        if($disallow){
            $extension = strtolower(file_extension($url));

        }else{
            /*
             * Ignored?
             */
            foreach($params['ignore'] as $ignore){
                try{
                    if(preg_match($ignore, $url)){
                        /*
                         * This URL should be ignored
                         */
                        if($params['show_skip']){
                            log_console(tr('Skipping URL "%url%", it is being ignored with rule "%ignore%"', array('%url%' => $url, '%ignore%' => str_log($ignore))), 'skip', 'yellow');
                        }

                        return 0;
                    }

                }catch(Exception $e){
                    throw new bException('sitemap_scan_url(): Invalid regex "'.str_log($ignore).'" specified for URL ignore', $e);
                }
            }


            /*
             * Check whether the url has on of the extensions allowed
             */
            if(!$scan_url){
                log_console('Plain domain, skipping extension check since this is always allowed', 'allow', 'yellow');
                $extension = strtolower('html');

            }else{
                $extension = strtolower(file_extension($url));

                if(!in_array($extension, array_force($params['filetypes']))){
                    if($params['show_skip']){
                        log_console('URL "'.str_log($url).'" has an unknown extension, skipping', 'skip', 'yellow');
                    }

                    return 0;
                }
            }
        }


        /*
         * Fetch the url and check HTTP result, should be 200
         */
        log_console('Fetching url "'.str_log($url).'"', 'fetch');

        $http_code     = 200;
        $last_modified = null;

        $headers   = get_headers($url);

        foreach($headers as $header){
            if(strtoupper(substr($header, 0, 8)) == 'HTTP/1.1'){
                $http_code     = (integer) trim(str_until(trim(str_from($header, 'HTTP/1.1')), ' '));

            }elseif(strtolower(substr($header, 0, 14)) == 'last-modified:'){
                $last_modified = trim(str_from($header, ':'));
            }
        }

        if($http_code != 200){
            log_error('sitemap_scan_url(): Url "'.str_log($url).'" gave HTTP code "'.str_log($http_code).'"', 'http_'.$http_code);
            return 0;
        }


        /*
         * Get page data
         */
        $html = curl_get(array('url'        => $url,
                               'getheaders' => false));
        $html = $html['data'];


        /*
         * If its a PHP / HTML URL, try to get the meta description
         * Example line: <meta name="description" content="Welcome to CleanLab Template, a wonderful and premium product for multipurpose websites" />
         */
        if(($extension != 'html') and ($extension != 'php')){
            $type        = $extension;
            $description = '';

        }else{
            $type        = 'html';
            $description = str_until(str_from($html, 'name="description"'), '>');
            $description = trim(str_until(str_from($description, 'content="'), '"'));
        }


        /*
         * Does the page have a noindex meta tag? If so, place as a separate disallow
         */
        if(!$disallow){
            if(preg_match('/<meta\s+name="robots"\s+content="noindex"\s*\/?>/imus', $html)){
                $disallow     = 1;
                $disallow_url = $url;
            }
        }


        /*
         * Get last_modified date
         */
        switch($params['modified']){
            case 'auto':
                /*
                 * Use the url last modified date
                 */
                break;

            case 'current':
                /*
                 * Use the current date
                 */
                $last_modified = date('Y-m-d\TH:i:sP');
                break;

            default:
                /*
                 * Take the specified modification date
                 */
                $last_modified = $params['modified'];
                break;
        }


        /*
         * Store in database
         */
        if($disallow){
            log_console('Adding disallow URL "'.str_log($url).'"', 'url');

        }else{
            log_console('Adding URL "'.str_log($url).'"', 'url');
        }

        $last_modified = new DateTime($last_modified);

        sql_query('INSERT INTO `sitemap_data` (`scans_id`, `url`, `type`, `status`, `modified`, `disallow`, `priority`, `changefreq`, `description`)
                   VALUES                     (:scans_id , :url , :type , :status , :modified , :disallow , :priority , :changefreq , :description )',

                   array(':scans_id'    => $params['scans_id'],
                         ':url'         => ($disallow ? $disallow_url : $url),
                         ':type'        => $type,
                         ':disallow'    => $disallow,
                         ':status'      => $params['status'],
                         ':modified'    => $last_modified->format('Y-m-d\TH:i:sP'),
                         ':priority'    => $params['priority'],
                         ':changefreq'  => $params['change_frequency'],
                         ':description' => $description));

        /*
         * All done, now, should we check urls in the document and recurse?
         */
        if($recursive){
            $found = preg_match_all('/href="(.+?)"/imus', $html, $urls);

            log_console(tr('Processing "%found%" URLs found in the current URL', array('%found%' => $found)), 'recursing');

            foreach($urls[1] as $url){
                if(strstr($url, '://')){
                    if(str_until(str_from($url, '://'), '/') != $_SESSION['domain']){
                        /*
                         * This is a URL outside of our domain, skip
                         */
                        if($params['show_skip']){
                            log_console(tr('Skipping URL "%url%", its outside our domain "%domain%"', array('%url%' => $url, '%domain%' => $_SESSION['domain'])), 'skip', 'yellow');
                        }

                        continue;
                    }

                    $url = str_from(str_from($url, '://'), '/');
                }

                /*
                 * Recurse
                 */
                $count += sitemap_scan_url($params, $url, $recursive);
            }
        }

        return $count + 1;

    }catch(Exception $e){
        if(!empty($url)){
            throw new bException('sitemap_scan_url(): Failed with URL "'.str_log($url).'"', $e);
        }

        throw new bException('sitemap_scan_url(): Failed with URL "'.str_log($scan_url).'"', $e);
    }
}
?>
