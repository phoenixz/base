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
        load_libs('sms');
        $phones = sms_full_phones($phones);
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
 * Verify that the specified phone number exists
 */
function twilio_verify_source_phone($phone){
    global $_CONFIG;

    try{
        load_libs('sms');
        $phone = sms_full_phones($phone);

        if(isset($_CONFIG['twilio']['sources'][$phone])){
            return $phone;
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
        if(empty($twilio)){
            $twilio = twilio_load();
        }

        if(empty($_CONFIG['twilio']['sources'][$from])){
            throw new bException('Specified source phone "'.str_log($from).'" is not known', 'unknown');
        }

        if(!$from){
            reset($_CONFIG['twilio']['sources']);
            $from = key($_CONFIG['twilio']['sources']);
        }

        return $twilio->account->messages->sendMessage($from, $to, $message);

    }catch(Exception $e){
        throw new bException(tr('twilio_send_message(): Failed'), $e);
    }
}
?>
