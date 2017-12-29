<?php
/*
 * htpasswd library
 *
 * This library can be used to manage .htpasswd files
 *
 * This library uses the htpasswd command line utility
 * Password encryption is always done using bcrypt (-B option)
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */



/*
 * Validate the specified htpassd filename
 */
function htpasswd_validate($file){
    try{
        $basefile = basename($file);

        if($basefile != '.htpasswd'){
            throw new bException(tr('htpasswd_validate(): Specified file ":file" is not a .htpasswd file', array(':file' => $file)), 'invalid');
        }

    }catch(Exception $e){
        throw new bException('htpasswd_validate(): Failed', $e);
    }
}



/*
 * Create htpasswd file with the specified data
 */
function htpasswd_create($file, $username, $password){
    try{
        htpasswd_validate($file);

    }catch(Exception $e){
        throw new bException('htpasswd_create(): Failed', $e);
    }
}



/*
 * Update the htpasswd file with the specified user
 */
function htpasswd_update($file){
    try{
        htpasswd_validate($file);

    }catch(Exception $e){
        throw new bException('htpasswd_update(): Failed', $e);
    }
}



/*
 * Create htpasswd file with the specified data
 */
function htpasswd_list($file){
    try{
        htpasswd_validate($file);

    }catch(Exception $e){
        throw new bException('htpasswd_list(): Failed', $e);
    }
}



/*
 * Authenticate the specified username with the specified password using the specified .htpasswd file
 */
function htpasswd_authenticate($file, $username, $password){
    try{
        htpasswd_validate($file);

    }catch(Exception $e){
        throw new bException('htpasswd_authenticate(): Failed', $e);
    }
}
?>
