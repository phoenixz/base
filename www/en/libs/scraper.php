<?php
/*
 * Empty library
 *
 * This is an empty template library file
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>
 */



load_config('scraper');



/*
 * Get and return the HTML from the specified URL
 */
function scraper_url_fetch($params){
    global $_CONFIG;

    try{
        array_params($params);
        array_default($params, 'url'          , '');
        array_default($params, 'nocache'      , false);
        array_default($params, 'background'   , false);
        array_default($params, 'proxy'        , false);
        array_default($params, 'getheaders'   , false);
        array_default($params, 'httpheaders'  , true);
        array_default($params, 'timeout'      , $_CONFIG['scraper']['timeout'] * ($params['proxy'] ? 2 : 1));
        array_default($params, 'expires'      , $_CONFIG['scraper']['expires']);
        array_default($params, 'cache_results', true);
        array_default($params, 'priority'     , 5);

        if(!$params['nocache']){
            $retval = sql_get('SELECT `id`,
                                      `http_code`,
                                      `targets_id`,
                                      `data`

                               FROM   `scraper_urls`

                               WHERE  `url`     = :url
                               AND   (`expires` IS NULL
                               OR     `expires` > NOW())', array(':url' => $params['url']));
        }

        if(!empty($retval)){
            /*
             * Got the URL from cache. Done!
             */
            $retval['cache'] = true;
            return $retval;
        }

        /*
         * We don't have the URL in cache, or it expired, or we don't use cache. Either way,
         */
        try{
            $data = curl_get($params);

        }catch(Exception $e){
            $data = $e->getData();

            if(VERBOSE and (PLATFORM == 'shell')){
                switch($data['status']['hhtp_code']){
                    case 404:
                        cli_log(tr('URL ":url" was not found (404)', array(':url' => $params['url'])), 'yellow');
                        break;

                    default:
                        cli_log(tr('URL ":url" failed with HTTP ":http" and error message ":e"', array(':url' => $params['url'], ':http' => str_log(isset_get($data['status']['http_code'])), ':e' => $e->getMessage())), 'yellow');
                }
            }
        }

        if(!$data['data'] and VERBOSE and (PLATFORM == 'shell')){
            cli_log(tr('URL ":url" returned no data', array(':url' => $params['url'])), 'yellow');
        }

        if($params['cache_results']){
            sql_query('INSERT INTO `scraper_urls` (`createdby`, `expires`                       , `priority`, `url`, `http_code`, `data`)
                       VALUES                     (:createdby , NOW() + INTERVAL :expires SECOND, :priority , :url , :http_code , :data )',

                       array(':createdby' => isset_get($_SESSION['user']['id']),
                             ':expires'   => $params['expires'],
                             ':http_code' => $data['status']['http_code'],
                             ':priority'  => $params['priority'],
                             ':url'       => $params['url'],
                             ':data'      => $data['data']));
        }

        return array('id'         => null,
                     'cache'      => false,
                     'targets_id' => null,
                     'data'       => $data['data'],
                     'http_code'  => $data['status']['http_code']);

    }catch(Exception $e){
        throw new bException(tr('scraper_url_fetch(): Failed'), $e);
    }
}



/*
 *
 */
function scraper_update_url(){

}



/*
 *
 */
function scraper_set_target($url, $id){
    try{
        sql_query('UPDATE `scraper_urls`

                   SET    `targets_id` = :id

                   WHERE  `url` = :url',

                   array(':id'  => $id,
                         ':url' => $url));

    }catch(Exception $e){
        throw new bException(tr('scraper_set_target(): Failed'), $e);
    }
}
?>
