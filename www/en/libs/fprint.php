<?php
/*
 * Fprint library
 *
 * This is a front-end library for the fprintd deamon
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */



/*
 * Initialize the library
 * Automatically executed by libs_load()
 */
function fprint_library_init(){
    try{
        if(!file_exists('/var/lib/fprint/')){
            throw new bException(tr('fprint_library_init(): fprintd application data found, it it probably is not installed. Please fix this by executing "sudo apt-get install fprintd" on the command line'), 'warning/not-exists');
        }

        load_config('fprint');

    }catch(Exception $e){
        throw new bException('fprint_library_init(): Failed', $e);
    }
}



/*
 * Register the fingerprint with the fprintd deamon
 */
function fprint_enroll($users_id, $finger = 'auto'){
    global $_CONFIG;

    try{
        $finger = fprint_verify_finger($finger);
        fprint_kill();

        $results = safe_exec('sudo timeout '.$_CONFIG['fprint']['timeouts']['enroll'].' fprintd-enroll '.($finger ? '-f '.$finger.' ' : '').$users_id);
        $result  = array_pop($results);

        if($result == 'Enroll result: enroll-completed'){
            return true;
        }

        throw new bException(tr('fprint_enroll(): Enroll failed with ":error"', array(':error' => $result)), 'failed');

    }catch(Exception $e){
        fprint_handle_exception($e, $users_id);
        throw new bException('fprint_enroll(): Failed', $e);
    }
}



/*
 * Verify a fingerprint
 */
function fprint_verify($user, $finger = 'auto'){
    global $_CONFIG;

    try{
        load_libs('user');
        $dbuser = user_get($user);

        if(!$dbuser){
            throw new bException(tr('fprint_verify(): Specified user ":user" does not exist', array(':user' => $user)), 'not-exist');
        }

        if(!$dbuser['fingerprint']){
            throw new bException(tr('fprint_verify(): User ":user" has no fingerprint registered', array(':user' => name($dbuser))), 'warning/empty');
        }

        $finger  = fprint_verify_finger($finger);
        fprint_kill();

        $results = safe_exec('sudo timeout '.$_CONFIG['fprint']['timeouts']['verify'].' fprintd-verify '.($finger ? '-f '.$finger.' ' : '').$user);
        $result  = array_pop($results);

        log_file(tr('Started fprintd-verify process for user ":user"', array(':user' => $user)), 'fprint');
        log_file($results, 'fprint');

        if($result == 'Verify result: verify-match (done)'){
            return true;
        }

        return false;

    }catch(Exception $e){
        fprint_handle_exception($e, $user);
        throw new bException('fprint_verify(): Failed', $e);
    }
}



/*
 * List available users registered in the fprint database
 */
function fprint_list_users(){
    try{
        $results = scandir('/var/lib/fprint');
        return $results;

    }catch(Exception $e){
        throw new bException('fprint_list_users(): Failed', $e);
    }
}



/*
 * List available finger prints
 */
function fprint_list($users){
    try{
        $results = safe_exec('sudo fprintd-list'.str_force($users, ' '));
        return $results;

    }catch(Exception $e){
        throw new bException('fprint_list(): Failed', $e);
    }
}



/*
 * Delete specified fingerprint
 */
function fprint_delete($user){
    try{
        if(!file_exists('/var/lib/fprint/'.$user)){
            throw new bException(tr('fprint_delete(): Specified user ":user" does not exist', array(':user' => $user)), 'not-exist');
        }

        /*
         * Delete the directory for this user completely
         */
        file_delete('/var/lib/fprint/'.$user, false, true);

    }catch(Exception $e){
        throw new bException('fprint_delete(): Failed', $e);
    }
}



/*
 *
 */
function fprint_kill(){
    try{
        load_libs('cli');
        return cli_pkill('fprintd', 15, true);

    }catch(Exception $e){
        throw new bException('fprint_kill(): Failed', $e);
    }
}



/*
 *
 */
function fprint_process(){
    try{
        load_libs('cli');
        return cli_pgrep('fprintd');

    }catch(Exception $e){
        throw new bException('fprint_kill(): Failed', $e);
    }
}



/*
 *
 */
function fprint_verify_finger($finger){
    try{
        switch($finger){
            case 'auto':
                return '';

            case 'left-thumb':
                // FALLTHROUGH
            case 'left-index-finger':
                // FALLTHROUGH
            case 'left-middle-finger':
                // FALLTHROUGH
            case 'left-ring-finger':
                // FALLTHROUGH
            case 'left-little-finger':
                // FALLTHROUGH
            case 'right-thumb':
                // FALLTHROUGH
            case 'right-index-finger':
                // FALLTHROUGH
            case 'right-middle-finger':
                // FALLTHROUGH
            case 'right-ring-finger':
                // FALLTHROUGH
            case 'right-little-finger':
                return $finger;

            default:
                throw new bException('fprint_verify_finger(): Unknown finger ":finger" specified. Please specify one of "left-thumb, left-index-finger, left-middle-finger, left-ring-finger, left-little-finger, right-thumb, right-index-finger, right-middle-finger, right-ring-finger, right-little-finger"', 'unknown');
        }

    }catch(Exception $e){
        throw new bException('fprint_verify_finger(): Failed', $e);
    }
}



/*
 * Show the finger print reader page, and prepare $_SESSION['fprint']
 */
function fprint_page_show($users_id, $html_flash_class){
    try{
        if(empty($_SESSION['fprint']['results'])){
            if(!$users_id){
                throw new bException(tr('fprint_page_show(): No users_id specified'), 'not-specified');
            }

            if(!is_numeric($users_id)){
                throw new bException(tr('fprint_page_show(): Invalid users_id ":users_id" specified', array(':users_id' => $users_id)), 'invalid');
            }

            load_libs('user');
            $user = user_get($users_id);

            if(!$user){
                throw new bException(tr('fprint_verify(): Specified user ":user" does not exist', array(':user' => $users_id)), 'not-exist');
            }

            if(!$user['fingerprint']){
                throw new bException(tr('fprint_verify(): User ":user" has no fingerprint registered', array(':user' => name($user))), 'warning/empty');
            }

            $params = array('file'     => uniqid(),
                            'users_id' => $user['id']);

            fprint_kill();
            $params['pid'] = run_background('/base/fprint authenticate -Q -C '.$params['users_id'], 'tmp/fprint/'.$params['file']);

            if($params['pid'] === false){
                /*
                 * Wut? Why no PID?
                 */
                throw new bException(tr('fprint_page_show(): The fprint script is already running'), 'process-runs');
            }

            $_SESSION['fprint'] = $params;

        }else{
            /*
             * Finger print scan finished
             */
            $params = $_SESSION['fprint'];
        }

        $params['html_flash_class'] = $html_flash_class;
        $params['return']           = true;

        return page_show('fingerprint', $params);

    }catch(Exception $e){
        throw new bException('fprint_page_show(): Failed', $e);
    }
}



/*
 * Try to handle fprint exceptions
 */
function fprint_handle_exception($e, $user){
    try{
         $data = $e->getData();

        if($data){
            $data = array_pop($data);

            if(strstr($data, 'Failed to discover prints') !== false){
                /*
                 * Only counds for verify!
                 * Do NOT send previous exception, generate a new one, its just a simple warning!
                 */
                throw new bException(tr('fprint_handle_exception(): Finger print data missing for user ":user"', array(':user' => name($user))), 'warning/missing');
            }

            if(strstr($data, 'No devices available') !== false){
                /*
                 * Do NOT send previous exception, generate a new one, its just a simple warning!
                 */
                throw new bException(tr('fprint_handle_exception(): No finger print scanner devices found'), 'warning/no-device');
            }
        }

        if($e->getCode() == 124){
            throw new bException(tr('fprint_handle_exception(): finger print scan timed out'), 'warning/timeout');
        }

   }catch(Exception $e){
        throw new bException('fprint_handle_exception(): Failed', $e);
    }
}
?>
