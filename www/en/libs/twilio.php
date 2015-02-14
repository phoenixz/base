<?php
/*
 * This is the twilio API library
 *
 * This library contains helper functions for the twilio API
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>
 */



load_config('twilio');



/*
 * Configuration tests only to be ran in debug mode
 */
if(debug()){
    try{
        foreach($_CONFIG['twilio']['sources'] as $phone => $name){
            if(!is_numeric($phone)){
                throw new bException('twilio(): Specified phone number "'.str_log($phone).'" is invalid, it should contain no formatting, no spaces, only numbers');
            }
        }

    }catch(Exception $e){
        throw new bException('twilio(): Library init failed, please check your twilio configuration', $e);
    }
}



/*
 * Install twilio using PEAR
 */
function twilio_install(){
    try{
        load_libs('file');

        log_console('twilio_install(): Installing Twilio library', 'install');

        /*
         * Make sure target exists and that we have no left over garbage from previous attempts
         */
        file_ensure_path(ROOT.'libs/external');
        file_delete(TMP.'twilio_install.zip');
        file_delete_tree(ROOT.'libs/external/twilio-php-master');
        file_delete_tree(ROOT.'libs/external/twilio');

        /*
         * Get library zip, unzip it to target, and cleanup
         */
        copy('https://github.com/twilio/twilio-php/archive/master.zip', TMP.'twilio_install.zip');
        safe_exec('unzip '.TMP.'twilio_install.zip -d '.ROOT.'libs/external');
        rename(ROOT.'libs/external/twilio-php-master', ROOT.'libs/external/twilio');
        unlink(TMP.'twilio_install.zip');

    }catch(Exception $e){
        throw new bException('twilio_install(): Failed', $e);
    }
}



/*
 * Load twilio base library
 */
function twilio_load($accountsid = null, $accountstoken = null, $auto_install = true){
    global $_CONFIG;

    try{
        $file = ROOT.'libs/external/twilio/Services/Twilio.php';

        if(!file_exists($file)){
            log_console('twilio_load(): Twilio API library not found', 'notinstalled');

            if(!$auto_install){
                throw new bException('twilio_load(): Twilio API library file "'.str_log($file).'" was not found', 'notinstalled');
            }

            twilio_install();

            if(!file_exists($file)){
                throw new bException('twilio_load(): Twilio API library file "'.str_log($file).'" was not found, and auto install seems to have failed', 'notinstalled');
            }
        }

        include($file);

        if(!$accountstoken){
            $accountstoken = $_CONFIG['twilio']['accounts_token'];
        }

        if(!$accountsid){
            $accountsid = $_CONFIG['twilio']['accounts_id'];
        }

        return new Services_Twilio($accountsid, $accountstoken);

    }catch(Exception $e){
        throw new bException('twilio_load(): Failed', $e);
    }
}



/*
 * Return (if possible) a name for the phone
 */
function twilio_name_phones($phones){
    global $_CONFIG;

    try{
        $phones = twilio_full_phones($phones);
        $phones = array_force($phones);

        foreach($phones as &$phone){
            if(isset($_CONFIG['twilio']['sources'][$phone])){
                $phone = $_CONFIG['twilio']['sources'][$phone];
            }
        }

        return str_force($phones, ', ');

    }catch(Exception $e){
        throw new bException('twilio_name_phones(): Failed', $e);
    }
}



/*
 *
 */
function twilio_get_conversation($phone_local, $phone_remote){
    global $_CONFIG;

    try{
        $phone_local  = twilio_full_phones($phone_local);
        $phone_remote = twilio_full_phones($phone_remote);

        /*
         * Determine the local and remote phones
         */
        if(empty($_CONFIG['twilio']['sources'][$phone_local])){
            $tmp          = $phone_remote;
            $phone_remote = $phone_local;
            $phone_local  = $tmp;

            unset($tmp);
        }

        /*
         * Find an existing conversation for the specified phones
         */
        $conversation = sql_get('SELECT `id`, `last_messages` FROM `twilio_conversations` WHERE `phone_remote` = :phone_remote AND `phone_local` = :phone_local', array(':phone_local' => $phone_local, ':phone_remote' => $phone_remote));

        if(!$conversation){
            /*
             * This phone combo has no conversation yet, create it now.
             */
            sql_query('INSERT INTO `twilio_conversations` (`phone_local`, `phone_remote`)
                       VALUES                             (:phone_local , :phone_remote )',

                       array(':phone_local'  => $phone_local,
                             ':phone_remote' => $phone_remote));

            $conversation = array('id'            => sql_insert_id(),
                                  'last_messages' => '');
        }

        return $conversation;

    }catch(Exception $e){
        throw new bException('twilio_get_conversation(): Failed', $e);
    }
}



/*
 * Update the specified conversation with the specified message
 */
function twilio_update_conversation($conversation, $direction, $message, $replied){
    global $_CONFIG;

    try{
        load_libs('json');

        if(empty($conversation['id'])){
            throw new bException('twilio_update_conversation(): No conversation id specified', 'notspecified');
        }

        if(empty($direction)){
            throw new bException('twilio_update_conversation(): No conversation direction specified', 'notspecified');
        }

        if(empty($message)){
            throw new bException('twilio_update_conversation(): No conversation message specified', 'notspecified');
        }

        if($conversation['last_messages']){
            $conversation['last_messages'] = json_decode_custom($conversation['last_messages']);

            /*
             * Ensure the conversation does not pass the max size
             */
            if(count($conversation['last_messages']) >= $_CONFIG['twilio']['conversations']['size']){
                array_pop($conversation['last_messages']);
            }

        }else{
            $conversation['last_messages'] = array();
        }

        array_unshift($conversation['last_messages'], array('direction' => $direction, 'message' => $message));

        $conversation['last_messages'] = json_encode_custom($conversation['last_messages']);

        if($replied){
            sql_query('UPDATE `twilio_conversations`

                       SET    `last_messages` = :last_messages,
                              `modifiedon`    = NOW(),
                              `replied`       = NOW()

                       WHERE  `id`            = :id',

                       array(':id'            => $conversation['id'],
                             ':last_messages' => $conversation['last_messages']));

        }else{
            sql_query('UPDATE `twilio_conversations`

                       SET    `last_messages` = :last_messages,
                              `modifiedon`    = NOW(),
                              `replied`       = NULL

                       WHERE  `id`            = :id',

                       array(':id'            => $conversation['id'],
                             ':last_messages' => $conversation['last_messages']));
        }

    }catch(Exception $e){
        throw new bException('twilio_update_conversation(): Failed', $e);
    }
}



/*
 * Return a phone number that always includes a country code
 */
function twilio_full_phones($phones){
    global $_CONFIG;

    try{
        $phones = array_force($phones);

        foreach($phones as &$phone){
            $phone = trim($phone);

            if(substr($phone, 0, 1) == '+'){
                /*
                 * Phone has a country code
                 */
                continue;
            }

            /*
             * Assume this is a US phone, return with +1
             */
            $phone = '+'.$_CONFIG['twilio']['defaults']['country_code'].$phone;
        }

        return str_force($phones, ',');

    }catch(Exception $e){
        throw new bException('twilio_full_phones(): Failed', $e);
    }
}
?>
