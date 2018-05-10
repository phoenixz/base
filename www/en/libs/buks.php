<?php
/*
 * BUKS library
 *
 * This is the BUKS, Base Unified Key System, library. Its loosely based on
 * the Linux LUKS system
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 */



/*
 * Initialize the library
 * Automatically executed by libs_load()
 */
function buks_library_init(){
    try{
        load_libs('openssl');
under_construction();

    }catch(Exception $e){
        throw new bException('buks_library_init(): Failed', $e);
    }
}



/*
 * Return a decrypted BUKS key for the specified section and users_id using the
 * specified password
 */
function buks_get_key($section, $users_id, $password){
    try{
        $key = sql_query('SELECT `key` FROM `buks` WHERE `section` = :section AND `users_id` = :users_id AND `status` IS NULL', true, array('section' => $section, ':users_id' => $users_id));

        if(!$key){
            throw new bException(tr('buks_get_key(): No key found for section ":section" and user "users_id"', array(':users_id' => $users_id)), 'not-found');
        }

        $key = openssl_decrypt($key, $password);

        if(!$key){
            throw new bException(tr('buks_get_key(): Empty key found for section ":section" and user "users_id"', array(':users_id' => $users_id)), 'invalid');
        }

        return $key;

    }catch(Exception $e){
        throw new bException('buks_get_key(): Failed', $e);
    }
}



/*
 * Add a new buks key for the specified section for the specified user
 */
function buks_add_key($section, $password, $users_id, $existing_password = null, $existing_users_id = null){
    try{
        $exists = sql_get('SELECT `id` FROM `buks` WHERE `section` = :section AND `users_id` = :users_id', array(':section' => $section, ':users_id' => $users_id));

        if($exists){
            throw new bException(tr('buks_add_key(): Buks key already exists for users_id ":users_id", section ":section"', array(':users_id' => $users_id, ':section' => $section)), 'exists');
        }

        if($existing_users_id){
            /*
             * Get the BUKS key from an existing user with its password
             */
            $key = buks_get_key($section, $existing_users_id, $existing_password);

        }else{
            /*
             * Create a new BUKS key
             */
            $exists = sql_get('SELECT `id` FROM `buks` WHERE `section` = :section LIMIT 1', array(':section' => $section));

            if($exists){
                throw new bException(tr('buks_add_key(): Buks key already exists for section ":section"', array(':users_id' => $users_id)), 'exists');
            }

            $key = hash('sha512', uniqid(PROJECT.openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc')), true));
        }

        $key = openssl_simple_encrypt($key, $password);

        sql_query('INSERT INTO `buks` (`createdby`, `users_id`, `section`, `key`)
                   VALUES             (:createdby , :users_id , :section , :key )',

                   array(':createdby'   => isset_get($_SESSION['user']['id']),
                         ':users_id'    => $users_id,
                         ':section'     => $section,
                         ':key'         => $key));

    }catch(Exception $e){
        throw new bException('buks_add_key(): Failed', $e);
    }
}



/*
 * Update the password for all buks keys for the specified user
 */
function buks_update_password($old_password, $new_password, $users_id = null){
    try{
        if(!$users_id){
            $users_id = $_SESSION['user']['id'];
        }

        $sections = sql_query();

        while($section = sql_fetch($sections)){
            $key = buks_get_key($section, $users_id, $old_password);
            $key = open_ssl_encrypt($key, $new_password);

            $update->execute(array(':section'  => $section,
                                   ':users_id' => $users_id));
        }


    }catch(Exception $e){
        throw new bException('buks_update_password(): Failed', $e);
    }
}



/*
 *
 */
function buks_encrypt($data, $section, $password, $user = null){
    try{
        /*
         * Get users_id from specified user
         */
        if(!$user){
            $user     = $_SESSION['user']['id'];
            $users_id = $_SESSION['user']['id'];

        }elseif(is_numeric($user)){
            $users_id = $user;

        }else{
            /*
             * Lookup in the buks users configuration list
             */
            if(empty($_CONFIG['buks']['users'][$user])){
                throw new bException(tr('buks_encrypt(): Unknown user ":user" specified', array(':user' => $user)), 'unknown');
            }

            $users_id = sql_query('SELECT `id` FROM `users` WHERE `username` = :username', array(':username' => $user));

            if(!$users_id){
                throw new bException(tr('buks_encrypt(): Specified buks user ":user" does not exist', array(':user' => $user)), 'not-exist');
            }

            $password = $_CONFIG['buks']['users'][$user];
        }

        /*
         * Get the target key for this user
         */
        $key = buks_get_key($section, $users_id, $password);

        if($key){
            throw new bException(tr('buks_encrypt(): User id ":user" does not have the ":key" key', array(':user' => $user, ':key' => $section)), 'not-exist');
        }

        return openssl_simple_encrypt($key, $data);

    }catch(Exception $e){
        throw new bException('buks_encrypt(): Failed', $e);
    }
}



/*
 *
 */
function buks_decrypt($data, $section, $password, $user = null){
    try{
        /*
         * Get users_id from specified user
         */
        if(!$user){
            $user     = $_SESSION['user']['id'];
            $users_id = $_SESSION['user']['id'];

        }elseif(is_numeric($user)){
            $users_id = $user;

        }else{
            /*
             * Lookup in the buks users configuration list
             */
            if(empty($_CONFIG['buks']['users'][$user])){
                throw new bException(tr('buks_encrypt(): Unknown user ":user" specified', array(':user' => $user)), 'unknown');
            }

            $users_id = sql_query('SELECT `id` FROM `users` WHERE `username` = :username', array(':username' => $user));

            if(!$users_id){
                throw new bException(tr('buks_encrypt(): Specified buks user ":user" does not exist', array(':user' => $user)), 'not-exist');
            }

            $password = $_CONFIG['buks']['users'][$user];
        }

        /*
         * Get the target key for this users_id
         */
        $key = buks_get_key($section, $users_id, $password);

        if($key){
            throw new bException(tr('buks_encrypt(): User id ":user" does not have the ":key" key', array(':user' => $user, ':key' => $section)), 'not-exists');
        }

        return openssl_decrypt($key, $data);

    }catch(Exception $e){
        throw new bException('buks_decrypt(): Failed', $e);
    }
}
?>
