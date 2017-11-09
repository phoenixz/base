<?php
/*
 * Postman library
 *
 * This library is a front end functions library for the postman API library
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */



/*
 * Parse specified postman configuration
 */
function postman_get_environment($environment){
    global $_CONFIG;

    try{
        if(empty($environment)){
            $environment = $_CONFIG['metrics']['environment'];
        }

        return $environment;

    }catch(Exception $e){
        throw new bException('postman_get_environment(): Failed', $e);
    }
}



/*
 * Get, validate, and return configuration for the specified configuration
 */
function postman_get_config($environment){
    global $_CONFIG;

    try{
        if(empty($environment)){
            $environment = $_CONFIG['metrics']['environment'];
        }

        if(empty($_CONFIG['metrics']['environments'][$environment])){
            throw new bException(tr('postman_get_config(): Unknown metrics environment ":environment" specified', array(':environment' => $environment)), 'unknown');
        }

        $config = $_CONFIG['metrics']['environments'][$_CONFIG['metrics']['environment']];
        $config = postman_parse_config($config);

        /*
         * Validate configuration
         */
        if(empty($config['item'])){
            throw new bException(tr('postman_get_config(): Missing configuration section "item" in environment ":environment" configuration', array(':environment' => $target)), 'unknown');
        }

        if(empty($config['item'][0])){
            throw new bException(tr('postman_get_config(): Missing configuration section "item 0" in environment ":environment" configuration', array(':environment' => $target)), 'unknown');
        }

        if(empty($config['item'][0]['request'])){
            throw new bException(tr('postman_get_config(): Missing configuration section "item 0 request" in environment ":environment" configuration', array(':environment' => $target)), 'unknown');
        }

        if(empty($config['item'][0]['request']['url'])){
            throw new bException(tr('Missing configuration entry "url" in section "item 0 request" in environment ":environment" configuration', array(':environment' => $target)), 'unknown');
        }

        return $config;

    }catch(Exception $e){
        throw new bException('postman_get_config(): Failed', $e);
    }
}



/*
 * Parse specified postman configuration
 */
function postman_parse_config($string){
    try{
        load_libs('json');
        return json_decode_custom($string);

    }catch(Exception $e){
        throw new bException('postman_parse_config(): Failed', $e);
    }
}



/*
 * Build cURL compatible headers from postman header configuration
 */
function postman_get_headers($headers){
    try{
        if(empty($headers)){
            return null;

        }

        foreach($headers as $header){
            $retval[] = $header['key'].': '.$header['value'];
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('postman_get_headers(): Failed', $e);
    }
}



/*
 *
 */
function postman_get_urlencoded($data){
    try{
        load_libs('inet');
        $url = '';

        foreach($data as $value){
            $url = url_add_query($url, $value['key'].'='.$value['value']);
        }

        return $url;

    }catch(Exception $e){
        throw new bException('postman_get_urlencoded(): Failed', $e);
    }
}
?>
