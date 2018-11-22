<?php
/*
 * Shortlink library
 *
 * This library contains functions to create shortlinks with multiple providers
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 * @category Function reference
 * @package shortlink
 */



/*
 * Initialize the library, automatically executed by libs_load()
 *
 * NOTE: This function is executed automatically by the load_libs() function and does not need to be called manually
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package shortlink
 *
 * @return void
 */
function shortlink_library_init(){
    try{
        load_libs('json');
        load_config('shortlink');

    }catch(Exception $e){
        throw new bException('shortlink_library_init(): Failed', $e);
    }
}



/*
 * Validates if the specified provider exists and returns it. If no provider was specified, the default provider will be returned
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package shortlink
 * @see shortlink_create()
 * @see shortlink_create() Used to convert the sitemap entry dates
 * @version 1.22.0: Added function
 *
 * @param string $provider The provider that should be validate, or no provider (in which case the default )
 * @return Either the specified provider, or if no provider was specified, the default provider
 */
function shortlink_get_provider($provider = null){
    global $_CONFIG;

    try {
        switch($provider){
            case 'default':
                throw new bException(tr('shortlink_get_provider(): Unknown provider ":provider" specified', array(':provider' => $provider)), 'unknown');

            case '':
                /*
                 * No provider specified, use the default provider, and validate
                 * that it exists (Hey, somebody can make a typo!)
                 */
                $provider = $_CONFIG['shortlink']['default'];
                // FALLTHROUGH

            default:
                if(empty($_CONFIG['shortlink'][$provider])){
                    throw new bException(tr('shortlink_get_provider(): Unknown provider ":provider" specified', array(':provider' => $provider)), 'unknown');
                }
        }

        return $provider;

    }catch(Exception $e) {
        throw new bException('shortlink_get_access_token(): Failed', $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package shortlink
 * @see shortlink_create()
 * @see shortlink_create() Used to convert the sitemap entry dates
 * @version 1.22.0: Added function
 *
 * @return
 */
function shortlink_get_access_token($provider = null){
    global $_CONFIG;

    try {
        $provider = shortlink_get_provider($provider);

        switch($provider){
            case 'capmega':
                $results = curl_get(array('url'      => 'https://api.capmega.com/oauth/access_token',
                                          'user_pwd' => $_CONFIG['shortlink']['capmega']['account']));

            case 'bitly':
                $results = curl_get(array('url'      => 'https://api-ssl.bitly.com/oauth/access_token',
                                          'user_pwd' => $_CONFIG['shortlink']['bitly']['account'],
                                          'method'   => 'post'));
        }

        return $results;

    }catch(Exception $e) {
        throw new bException('shortlink_get_access_token(): Failed', $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package shortlink
 * @see empty_install()
 * @see shortlink_create() Used to convert the sitemap entry dates
 * @version 1.22.0: Added function
 *
 * @param string $url
 * @param string $provider
 * @return string a shortlink URL from the specified provider for the specified URL
 */
function shortlink_create($url, $provider = null) {
    try{
        $token = shortlink_get_access_token($provider);

        switch($provider){
            case 'capmega':
under_construction();

            case 'bitly':
                $result = curl_get(array('url'     => 'https://api-ssl.bitly.com/v4/bitlinks?access_token='.$token,

                                         'post'    => json_encode(array('long_url' => $url)),

                                         'headers' => array('Authorization: Bearer {$token}',
                                                            'Content-Type: application/json',
                                                            'Content-Length: '.strlen($json_string))));

                $result = json_decode_custom($result);

                if(empty($result['link'])){
                    throw new bException(tr('shortlink_create(): Invalid response received from provider "bitly" for the specified URL ":url"', array(':url' => $url)), 'invalid');
                }

                return $result['link'];
        }

    }catch(Exception $e){
        throw new bException('shortlink_create(): Failed', $e);
    }
}