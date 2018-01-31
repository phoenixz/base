<?php
/*
 * BUKS library
 *
 * This is the BUKS, Base Unified Key System, library. Its loosely based on
 * the Linux LUKS system
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */



buks_init();



/*
 *
 */
function buks_init(){
    try{
        load_libs('openssl');
under_construction();

    }catch(Exception $e){
        throw new bException('buks_init(): Failed', $e);
    }
}



/*
 *
 */
function buks_encrypt($data, $name, $password, $users_id = null){
    try{
        if(!$users_id){
            $users_id = $_SESSION['user']['id'];
        }

        /*
         * Get the target key for this user
         */
        $key = sql_get('SELECT `key` FROM `buks` WHERE `user_id` = :user_id AND `name` = :name', true, array(':users_id' => $users_id, ':name' => $name));

        if($key){
            throw new bException(tr('buks_encrypt(): User id ":users_id" does not have the ":key" key', array(':users_id' => $users_id, ':key' => $name)), $e);
        }

        return openssl_encrypt($key, $data);

    }catch(Exception $e){
        throw new bException('buks_encrypt(): Failed', $e);
    }
}



/*
 *
 */
function buks_decrypt($name, $password, $data){
    try{
        if(!$users_id){
            $users_id = $_SESSION['user']['id'];
        }

        /*
         * Get the target key for this user
         */
        $key = sql_get('SELECT `key` FROM `buks` WHERE `user_id` = :user_id AND `name` = :name', true, array(':users_id' => $users_id, ':name' => $name));

        if($key){
            throw new bException(tr('buks_encrypt(): User id ":users_id" does not have the ":key" key', array(':users_id' => $users_id, ':key' => $name)), $e);
        }

        return openssl_decrypt($key, $data);

    }catch(Exception $e){
        throw new bException('buks_decrypt(): Failed', $e);
    }
}



/*
 *
 */
function buks_create($name, $current_password, $new_password){
    try{

    }catch(Exception $e){
        throw new bException('buks_encrypt(): Failed', $e);
    }
}
?>
