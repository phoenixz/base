<?php
/*
 * Empty library
 *
 * This is an empty template library file
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>
 */



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
        throw new lsException('twilio_install(): Failed', $e);
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
                throw new lsException('twilio_load(): Twilio API library file "'.str_log($file).'" was not found', 'notinstalled');
            }

            twilio_install();

            if(!file_exists($file)){
                throw new lsException('twilio_load(): Twilio API library file "'.str_log($file).'" was not found, and auto install seems to have failed', 'notinstalled');
            }
        }

        include($file);

        load_config('twilio');

        if(!$accountstoken){
            $accountstoken = $_CONFIG['twilio']['accounts_token'];
        }

        if(!$accountsid){
            $accountsid = $_CONFIG['twilio']['accounts_id'];
        }

        return new Services_Twilio($accountsid, $accountstoken);

    }catch(Exception $e){
        throw new lsException('twilio_load(): Failed', $e);
    }
}
?>
