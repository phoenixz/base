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
 * Ensure that the remote IP is on the API whitelist and
 */
function api_whitelist(){
    global $_CONFIG;

    try{
        if(empty($_CONFIG['api']['whitelist'])){
            if(!in_array($_SERVER['REMOTE_ADDR'], $_CONFIG['api']['whitelist'])){
                $block = true;
            }
        }

        if(empty($block) and !empty($_CONFIG['api']['blacklist'])){
            if(in_array($_SERVER['REMOTE_ADDR'], $_CONFIG['api']['blacklist'])){
                $block = true;
            }
        }

        if(isset($block)){
            throw new bException(tr('api_whitelist(): The IP ":ip" is not allowed access', array(':ip' => $_SERVER['REMOTE_ADDR'])), 'access-denied');
        }

        return true;

    }catch(Exception $e){
        throw new bException('api_encode(): Failed', $e);
    }
}



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
function api_call_base($api, $call, $data = array()){
    global $_CONFIG;

    try{
        load_libs('curl,json');
        load_config('api');

        if(empty($api)){
            throw new bException(tr('api_call_base(): No API specified'), 'not-specified');
        }

        if(empty($_CONFIG['api']['list'][$api])){
            throw new bException(tr('api_call_base(): Specified API ":api" does not exist', array(':api' => $api)), 'not-exist');
        }

        $url    = $_CONFIG['api']['list'][$api]['baseurl'].$call;
        $apikey = $_CONFIG['api']['list'][$api]['apikey'];

        if(empty($_SESSION['api']['session_keys'][$api])){
            try{
                /*
                 * Auto authenticate
                 */
                $result = curl_get(array('url'            => str_starts_not($_CONFIG['api']['list'][$api]['baseurl'], '/').'/authenticate',
                                         'posturlencoded' => true,
                                         'getheaders'     => false,
                                         'post'           => array('PHPSESSID' => $apikey)));

                if(!$result){
                    throw new bException(tr('api_call_base(): Authentication on API ":api" returned no response', array(':api' => $api)), 'not-exist');
                }

                $result = json_decode_custom($result['data']);

                if(isset_get($result['result']) !== 'OK'){
                    throw new bException(tr('api_call_base(): Authentication on API ":api" returned result ":result"', array(':result' => $result['result'])), 'failed', $result);
                }

                if(empty($result['token'])){
                    throw new bException(tr('api_call_base(): Authentication on API ":api" returned ok result but no token'), 'failed');
                }

                $_SESSION['api']['session_keys'][$api] = $result['token'];
                $signin = true;

            }catch(Exception $e){
                throw new bException(tr('api_call_base(): Failed to authenticate'), $e);
            }
        }

        $data['PHPSESSID'] = $_SESSION['api']['session_keys'][$api];

        $result = curl_get(array('url'            => str_starts_not($_CONFIG['api']['list'][$api]['baseurl'], '/').str_starts($call, '/'),
                                 'posturlencoded' => true,
                                 'getheaders'     => false,
                                 'post'           => $data));

        if(!$result){
            throw new bException(tr('api_call_base(): API call ":call" on ":api" returned no response', array(':api' => $api, ':call' => $call)), 'not-response');
        }

        $result = json_decode_custom($result['data']);

        switch(isset_get($result['result'])){
            case 'OK':
                /*
                 * All ok!
                 */
                return isset_get($result['data']);

            case 'SIGNIN':
                /*
                 * Session key is not valid
                 * Remove session key, signin, and try again
                 */
                if(isset($signin)){
                    /*
                     * Oops, we already tried to signin, and that signin failed
                     * with a signin request which, in this case, would cause
                     * endless recursion
                     */
                    throw new bException(tr('api_call_base(): API call ":call" on ":api" required auto signin but that failed with a request to signin as well. Stopping to avoid endless signin loop', array(':api' => $api, ':call' => $call)), 'failed');
                }

                unset($_SESSION['api']['session_keys'][$api]);
                return api_call_base($api, $call, $data);

            default:
                throw new bException(tr('api_call_base(): API call ":call" on ":api" returned result ":result"', array(':api' => $api, ':call' => $call, ':result' => $result['result'])), 'failed', $result);
        }

    }catch(Exception $e){
show(isset_get($result));
showdie($e);
        throw new bException('api_call_base(): Failed', $e);
    }
}
?>
