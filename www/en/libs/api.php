<?php
/*
 * API library
 *
 *
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */



load_config('api');



/*
 * Encode the given data for use with BASE APIs
 */
function api_encode($data){
    try{
        if(is_array($data)){
            $data = str_replace('@', '\@', $data);

        }elseif(is_string($data)){
            foreach($listing as &$value){
                $value = str_replace('@', '\@', $value);
            }

            unset($value);

        }else{
            throw new bException(tr('api_encode(): Specified data is datatype ":type", only string and array are allowed', array(':type' => gettype($data))), $e);
        }

        return $data;

    }catch(Exception $e){
        throw new bException('api_encode(): Failed', $e);
    }
}



/*
 * Encode the given data from a BASE API back to its original form
 */
function api_decode($data){
    try{
        if(is_array($data)){
            $data = str_replace('\@', '@', $data);

        }elseif(is_string($data)){
            foreach($listing as &$value){
                $value = str_replace('\@', '@', $value);
            }

            unset($value);

        }else{
            throw new bException(tr('api_decode(): Specified data is datatype ":type", only string and array are allowed', array(':type' => gettype($data))), $e);
        }

        return $data;

    }catch(Exception $e){
        throw new bException('api_decode(): Failed', $e);
    }
}



/*
 * Make an API call to a BASE framework
 */
function api_call_base($api, $call, $data){
    global $_CONFIG;

    try{
        if(empty($api)){
            throw new bException(tr('api_call_base(): No API specified'), 'not-specified');
        }

        if(empty($_CONFIG['api']['list'][$api])){
            throw new bException(tr('api_call_base(): Specified API ":api" does not exist', array(':api' => $api)), 'not-exist');
        }

        $url    = $_CONFIG['api']['list'][$api]['url'].$call;
        $apikey = $_CONFIG['api']['list'][$api]['apikey'];

        if(empty($_SESSION['api']['session_keys'][$api])){
            /*
             * Authenticate first
             */
            $result = curl_exec(array('url'            => $url,
                                      'posturlencoded' => true,
                                      'getheaders'     => false,
                                      'post'           => array('PHPSESSID' => $apikey)));
showdie($result);
            if(!$result){
                throw new bException(tr('api_call_base(): Authentication on API ":api" returned no response', array(':api' => $api)), 'not-exist');
            }
        }


    }catch(Exception $e){
        throw new bException('api_call_base(): Failed', $e);
    }
}
?>
