<?php
/*
 * Fprint library
 *
 * This is a front-end library for the fprintd deamon
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */



fprint_init();



/*
 *
 */
function fprint_init(){
    try{
        if(!file_exists('/var/lib/fprint/')){
            throw new bException(tr('fprint_init(): fprintd application data found, it it probably is not installed. Please fix this by executing "sudo apt-get install fprintd" on the command line'), 'not-exists');
        }

    }catch(Exception $e){
        throw new bException('fprint_init(): Failed', $e);
    }
}



/*
 * Register the fingerprint with the fprintd deamon
 */
function fprint_enroll($users_id, $finger = 'auto'){
    try{
        $finger  = fprint_verify_finger($finger);
        $results = safe_exec('sudo fprintd-enroll '.($finger ? '-f '.$finger.' ' : '').$users_id);
        $result  = array_pop($results);

        if($result == 'Enroll result: enroll-completed'){
            return true;
        }

        throw new bException(tr('fprint_enroll(): Enroll failed with ":error"', array(':error' => $result)), 'failed');

    }catch(Exception $e){
        throw new bException('fprint_enroll(): Failed', $e);
    }
}



/*
 * Verify a fingerprint
 */
function fprint_verify($user, $finger = 'auto'){
    try{
        $finger  = fprint_verify_finger($finger);
        $results = safe_exec('sudo fprintd-verify '.($finger ? '-f '.$finger.' ' : '').$user);
        $result  = array_pop($results);

        if($result == 'Verify result: verify-match (done)'){
            return true;
        }

        return false;

    }catch(Exception $e){
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
?>
