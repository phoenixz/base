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
function twilio_load($phone, $auto_install = true){
    global $_CONFIG;

    try{
        /*
         * Load Twilio library
         * If Twilio isnt available, then try auto install
         */
        $file = ROOT.'libs/external/twilio/Services/Twilio.php';

        if(!file_exists($file)){
            log_console('twilio_load(): Twilio API library not found', 'notinstalled');

            if(!$auto_install){
                throw new bException(tr('twilio_load(): Twilio API library file ":file" was not found', array(':file' => $file)), 'notinstalled');
            }

            twilio_install();

            if(!file_exists($file)){
                throw new bException(tr('twilio_load(): Twilio API library file ":file" was not found, and auto install seems to have failed', array(':file' => $file)), 'notinstalled');
            }
        }

        include($file);

        /*
         * Get Twilio object with account data for the specified phone number
         */
        $account = sql_get('SELECT `twilio_accounts`.`accounts_id`,
                                   `twilio_accounts`.`accounts_token`

                            FROM   `twilio_numbers`

                            JOIN   `twilio_accounts`
                            ON     `twilio_accounts`.`id` = `twilio_numbers`.`accounts_id`

                            WHERE  `twilio_numbers`.`number` = :number',

                            array(':number' => $phone));

        if(!$account){
            throw new bException(tr('twilio_load(): No Twilio account found for phone number ":phone"', array(':phone' => $phone)), 'not-exist');
        }

        return new Services_Twilio($account['accounts_id'], $account['accounts_token']);

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
                $phone = sql_get('SELECT `name` FROM `twilio_numbers` WHERE `number` = :number', 'name', array(':number' => $phone));
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
        return sql_get('SELECT `number` FROM `twilio_numbers` WHERE `number` = :number', 'number', array(':number' => $phone));

    }catch(Exception $e){
        throw new bException('twilio_verify_source_phone(): Failed', $e);
    }
}



/*
 * Send an SMS or MMS message through twilio
 */
function twilio_send_message($message, $to, $from = null){
    global $_CONFIG;
    static $twilio;

    try{
        if(empty($twilio)){
            $twilio = twilio_load();
        }

        $source = sql_get('SELECT `number` FROM `twilio_numbers` WHERE `number` = :number', 'number', array(':number' => $from));

        if(!$source){
            throw new bException(tr('twilio_send_message(): Specified source phone ":from" is not known', array(':from' => $from)), 'unknown');
        }

        if(is_array($message)){
            /*
             * This is an MMS message
             */
            if(empty($message['message'])){
                throw new bException(tr('twilio_send_message(): No message specified'), 'not-specified');
            }

            if(empty($message['media'])){
                throw new bException(tr('twilio_send_message(): No media specified'), 'not-specified');
            }

            return $twilio->account->messages->sendMessage($source, $to, $message['message'], $message['media']);
        }

        /*
         * Send a normal SMS message
         */
        return $twilio->account->messages->sendMessage($source, $to, $message);

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
