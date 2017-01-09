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
load_libs('json');



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
 *
 */
function api_authenticate($apikey){
    global $_CONFIG;

    try{
        if($_CONFIG['production']){
            /*
             * This is a production platform, only allow JSON API key
             * authentications over a secure connection
             */
            if($_CONFIG['protocol'] !== 'https://'){
                throw new bException(tr('api_authenticate(): No API key authentication allowed on unsecure connections over non HTTPS connections'), 'not-allowed');
            }
        }

        if(empty($apikey)){
            throw new bException(tr('api_authenticate(): No auth key specified'), 'not-specified');
        }

        /*
         * Authenticate using the supplied key
         */
        if(empty($_CONFIG['api']['apikey'])){
            /*
             * Check in database if the authorization key exists
             */
            $user = sql_get('SELECT * FROM `users` WHERE `api_key` = :apikey', array(':apikey' => $apikey));

            if(!$user){
                throw new bException(tr('api_authenticate(): Specified apikey is not valid'), 'access-denied');
            }

        }else{
            /*
             * Use one system wide API key
             */
            if($apikey !== $_CONFIG['api']['apikey']){
                throw new bException(tr('api_authenticate(): Specified auth key is not valid'), 'access-denied');
            }
        }

        /*
         * Yay, auth worked, create session and send client the session token
         */
        session_destroy();
        session_start();
        session_regenerate_id();
        session_reset_domain();

        sql_query('INSERT INTO `api_sessions` (`createdby`, `ip`, `apikey`)
                   VALUES                     (:createdby , :ip , :apikey )',

                   array('createdby' => isset_get($_SESSION['user']['id']),
                         'ip'        => $_SERVER['REMOTE_ADDR'],
                         'apikey'    => $apikey));

        $_SESSION['user'] = $user;
        $_SESSION['api']  = array('session_start' => time(),
                                  'session_id'    => sql_insert_id());

        return session_id();

    }catch(Exception $e){
        throw new bException('api_authenticate(): Failed', $e);
    }
}



/*
 *
 */
function api_start_session(){
    global $_CONFIG;

    try{
        /*
         * Check session token
         */
        if(empty($_POST['PHPSESSID'])){
            throw new bException(tr('api_start_session(): No auth key specified'), 'not-specified');
        }

        /*
         * Yay, we have an actual token, create session!
         */
        session_write_close();
        session_id($_POST['PHPSESSID']);
        session_start();

        if(empty($_SESSION['api']['session_start'])){
            /*
             * Not a valid session!
             */
            session_destroy();
            session_reset_domain();

            json_reply(tr('api_start_session(): Specified token ":token" has no session', array(':token' => $_POST['PHPSESSID'])), 'signin');
        }

        return session_id();

    }catch(Exception $e){
        throw new bException('api_start_session(): Failed', $e);
    }
}



/*
 *
 */
function api_close_session(){
    global $_CONFIG;

    try{
        /*
         * Yay, we have an actual token, create session!
         */
        sql_query('UPDATE `api_sessions`

                   SET    `closedon` = NOW()

                   WHERE  `id`       = :id',

                   array('id' => isset_get($_SESSION['api']['sessions_id'])));

        session_destroy();
        session_reset_domain();
        return true;

    }catch(Exception $e){
        throw new bException('api_close_session(): Failed', $e);
    }
}



/*
 * Register session open or close in the api_session database table
 */
function api_call($call, $result = null){
    static $time, $id;

    try{
        if($result){
            sql_query('UPDATE `api_calls`

                       SET    `time`   = :time,
                              `result` = :result

                       WHERE  `id`     = :id',

                       array(':time'   => microtime(true) - $time,
                             ':result' => $result,
                             ':id'     => $id));

        }else{
            sql_query('INSERT INTO `api_calls` (`sessions_id`, `call`)
                       VALUES                  (:sessions_id , :call )',

                       array('sessions_id' => isset_get($_SESSION['user']['id']),
                             'ip'          => $_SESSION['api']['session_id'],
                             'apikey'      => $apikey));

            $time = microtime(true);
            $id   = sql_insert_id();
        }

    }catch(Exception $e){
        throw new bException('api_session(): Failed', $e);
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

        if(empty($_SESSION['api']['session_keys'][$api])){
            try{
                /*
                 * Auto authenticate
                 */
                $apikey = $_CONFIG['api']['list'][$api]['apikey'];
                $json   = curl_get(array('url'            => str_starts_not($_CONFIG['api']['list'][$api]['baseurl'], '/').'/authenticate',
                                         'posturlencoded' => true,
                                         'getheaders'     => false,
                                         'post'           => array('PHPSESSID' => $apikey)));

                if(!$json){
                    throw new bException(tr('api_call_base(): Authentication on API ":api" returned no response', array(':api' => $api)), 'not-exist');
                }

                $result = json_decode_custom($json['data']);

                if(isset_get($result['result']) !== 'OK'){
                    throw new bException(tr('api_call_base(): Authentication on API ":api" returned result ":result"', array(':result' => $result['result'])), 'failed', $result);
                }

                if(empty($result['data']['token'])){
                    throw new bException(tr('api_call_base(): Authentication on API ":api" returned ok result but no token'), 'failed');
                }

                $_SESSION['api']['session_keys'][$api] = $result['data']['token'];
                $signin = true;

            }catch(Exception $e){
                throw new bException(tr('api_call_base(): Failed to authenticate'), $e);
            }
        }

        $data['PHPSESSID'] = $_SESSION['api']['session_keys'][$api];

        $json = curl_get(array('url'            => str_starts_not($_CONFIG['api']['list'][$api]['baseurl'], '/').str_starts($call, '/'),
                               'posturlencoded' => true,
                               'getheaders'     => false,
                               'post'           => $data));

        if(!$json){
            throw new bException(tr('api_call_base(): API call ":call" on ":api" returned no response', array(':api' => $api, ':call' => $call)), 'not-response');
        }

        $result = json_decode_custom($json['data']);

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
show(isset_get($json));
show(isset_get($result));
showdie($e);
        throw new bException('api_call_base(): Failed', $e);
    }
}
?>
