<?php
/*
 * This is the twilio API library
 *
 * This library contains helper functions for the twilio API
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */



load_config('twilio');



/*
 * Configuration tests only to be ran in debug mode
 */
if(debug()){
    try{
        foreach($_CONFIG['twilio']['accounts'] as $account => $data){
            foreach($data['sources'] as $phone => $name){
                if(!is_numeric($phone)){
                    throw new bException(tr('twilio(): Specified phone number ":phone" from account ":account" is invalid. It should contain no formatting and no spaces and only numbers', array(':account' => str_log($account), ':phone' => str_log($phone))), 'invalid');
                }
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
        $perms = file_ensure_writable(ROOT.'libs');

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

        if($perms){
            /*
             * Return libs directory to original mode
             */
            chmod(ROOT.'libs', $perms);
        }

    }catch(Exception $e){
        throw new bException('twilio_install(): Failed', $e);
    }
}



/*
 * Load twilio base library
 */
function twilio_load($account = null, $auto_install = true){
    global $_CONFIG;

    try{
        if(!$account){
            /*
             * Use the first account as the default account
             */
            reset($_CONFIG['twilio']['accounts']);
            $account = key($_CONFIG['twilio']['accounts']);

        }elseif(is_numeric($account)){
            foreach($_CONFIG['twilio']['accounts'] as $account_email => $data){
                if(!empty($data['sources'][$account])){
                    $account = $account_email;
                }
            }

            if(is_numeric($account)){
                /*
                 * Specified number is not found in any account
                 */
                throw new bException(tr('twilio_load(): Specified phone number ":number" was not found in any Twilio account', array(':number' => str_log($account))), 'not-exist');
            }
        }

        $file = ROOT.'libs/external/twilio/Services/Twilio.php';

        if(!file_exists($file)){
            log_console('twilio_load(): Twilio API library not found', 'notinstalled');

            if(!$auto_install){
                throw new bException(tr('twilio_load(): Twilio API library file ":file" was not found', array(':file' => str_log($file))), 'notinstalled');
            }

            twilio_install();

            if(!file_exists($file)){
                throw new bException(tr('twilio_load(): Twilio API library file ":file" was not found, and auto install seems to have failed', array(':file' => str_log($file))), 'notinstalled');
            }
        }

        include($file);

        if(empty($_CONFIG['twilio']['accounts'][$account])){
            throw new bException(tr('twilio_load(): Specified Twilio account ":account" does not exist', array(':account' => str_log($account))), 'not-exist');
        }

        return new Services_Twilio($_CONFIG['twilio']['accounts'][$account]['accounts_id'], $_CONFIG['twilio']['accounts'][$account]['accounts_token']);

    }catch(Exception $e){
        throw new bException('twilio_load(): Failed', $e);
    }
}



/*
 * Return (if possible) a name for the phone
 */
function twilio_name_phones($phones, $non_numeric = null){
    global $_CONFIG;

    try{
        load_libs('sms');
        $phones = sms_full_phones($phones);
        $phones = array_force($phones);

        foreach($phones as &$phone){
            if(!is_numeric($phone)){
                if($non_numeric){
                    $phone = $non_numeric;
                }

            }else{
                foreach($_CONFIG['twilio']['accounts'] as $account => $data){
                    if(isset($data['sources'][$phone])){
                        $phone = $_CONFIG['twilio']['accounts'][$account]['sources'][$phone];
                        break;
                    }
                }
            }
        }

        return str_force($phones, ', ');

    }catch(Exception $e){
        throw new bException('twilio_name_phones(): Failed', $e);
    }
}



/*
 * Verify that the specified phone number exists
 */
function twilio_verify_source_phone($phone){
    global $_CONFIG;

    try{
        load_libs('sms');
        $phone = sms_full_phones($phone);

        foreach($_CONFIG['twilio']['accounts'] as $account => $data){
            if(isset($data['sources'][$phone])){
                return $phone;
            }
        }

        return null;

    }catch(Exception $e){
        throw new bException('twilio_verify_source_phone(): Failed', $e);
    }
}



/*
 *
 */
function twilio_send_message($message, $to, $from = null){
    global $_CONFIG;
    static $twilio;

    try{
        foreach($_CONFIG['twilio']['accounts'] as $account => $data){
            if(!empty($data['sources'][$from])){
                if(empty($twilio)){
                    $twilio = twilio_load($account);
                }
            }
        }

        if(!$twilio){
            throw new bException(tr('Specified source phone ":from" is not known', array(':from' => str_log($from))), 'unknown');
        }

// :DELETE: From now on, the "from" number is obligatory
        //if(!$from){
        //    reset($_CONFIG['twilio']['sources']);
        //    $from = key($_CONFIG['twilio']['sources']);
        //}

        return $twilio->account->messages->sendMessage($from, $to, $message);

    }catch(Exception $e){
        throw new bException(tr('twilio_send_message(): Failed'), $e);
    }
}



/*
 *
 */
function twilio_add_image($message_id, $url, $mimetype){
    try{
        sql_query('INSERT INTO `sms_images` (`sms_messages_id`, `url`, `mimetype`)
                   VALUES                   (:sms_messages_id , :url , :mimetype )',

                   array(':sms_messages_id' => $message_id,
                         ':mimetype'        => $mimetype,
                         ':url'             => $url));

        run_background('base/sms getimages');

    }catch(Exception $e){
        throw new bException(tr('twilio_add_image(): Failed'), $e);
    }
}



/*
 *
 */
function twilio_download_image($message_id, $url){
    try{
        $file = file_move_to_target($url, ROOT.'data/sms/images');

        sql_query('UPDATE `sms_images`

                   SET    `downloaded`      = NOW(),
                          `file`            = :file

                   WHERE  `sms_messages_id` = :sms_messages_id
                   AND    `url`             = :url',

                   array(':sms_messages_id' => $message_id,
                         ':url'             => $url,
                         ':file'            => $file));

    }catch(Exception $e){
        throw new bException(tr('twilio_download_image(): Failed'), $e);
    }
}
?>
