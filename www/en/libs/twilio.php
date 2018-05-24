<?php
/*
 * This is the twilio API library
 *
 * This library contains helper functions for the twilio API
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 */



/*
 * Initialize the library
 * Automatically executed by libs_load()
 */
function twilio_library_init(){
    try{
        load_config('twilio');

    }catch(Exception $e){
        throw new bException('twilio_library_init(): Failed', $e);
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
        if(filter_var($phone, FILTER_VALIDATE_EMAIL)){
            $account = sql_get('SELECT `twilio_accounts`.`accounts_id`,
                                       `twilio_accounts`.`accounts_token`

                                FROM   `twilio_accounts`

                                WHERE  `twilio_accounts`.`email` = :email',

                                array(':email' => $phone));

        }else{
            $account = sql_get('SELECT `twilio_accounts`.`accounts_id`,
                                       `twilio_accounts`.`accounts_token`

                                FROM   `twilio_numbers`

                                JOIN   `twilio_accounts`
                                ON     `twilio_accounts`.`id` = `twilio_numbers`.`accounts_id`

                                WHERE  `twilio_numbers`.`number` = :number',

                                array(':number' => $phone));
        }

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
                $label = sql_get('SELECT `name` FROM `twilio_numbers` WHERE `number` = :number', 'name', array(':number' => $phone));

                if($label){
                    $phone = $label;
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
        $source = sql_get('SELECT `number` FROM `twilio_numbers` WHERE `number` = :number', 'number', array(':number' => $from));

        if(!$source){
            throw new bException(tr('twilio_send_message(): Specified source phone ":from" is not known', array(':from' => $from)), 'unknown');
        }

        if(empty($twilio)){
            $twilio = twilio_load($source);
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



/*
 *
 */
function twilio_groups_validate($group){
    try{
        load_libs('validate');

        $v = new validate_form($group, 'name,description');
        $v->isNotEmpty ($group['name']    , tr('No twilios name specified'));
        $v->hasMinChars($group['name'],  2, tr('Please ensure the twilio name has at least 2 characters'));
        $v->hasMaxChars($group['name'], 32, tr('Please ensure the twilio name has less than 32 characters'));
        $v->isRegex    ($group['name'], '/^[a-z-]{2,32}$/', tr('Please ensure the twilio name contains only lower case letters, and dashes'));

        $v->isNotEmpty ($group['description']      , tr('No twilio description specified'));
        $v->hasMinChars($group['description'],    2, tr('Please ensure the twilio description has at least 2 characters'));
        $v->hasMaxChars($group['description'], 2047, tr('Please ensure the twilio description has less than 2047 characters'));

        if(is_numeric(substr($group['name'], 0, 1))){
            $v->setError(tr('Please ensure that the name does not start with a number'));
        }

        /*
         * Does the twilio phone number already exist?
         */
        if(empty($group['id'])){
            if($id = sql_get('SELECT `id` FROM `twilio_groups` WHERE `name` = :name', array(':name' => $group['name']))){
                $v->setError(tr('The group ":group" already exists with id ":id"', array(':group' => $group['name'], ':id' => $id)));
            }

        }else{
            if($id = sql_get('SELECT `id` FROM `twilio_groups` WHERE `name` = :name AND `id` != :id', array(':name' => $group['name'], ':id' => $group['id']))){
                $v->setError(tr('The group ":group" already exists with id ":id"', array(':group' => $group['name'], ':id' => $id)));
            }
        }

        $v->isValid();

        return $group;

    }catch(Exception $e){
        throw new bException(tr('twilio_groups_validate(): Failed'), $e);
    }
}



/*
 * Returns twilio group from database
 */
function twilio_groups_get($group){
    try{
        if(!$group){
            throw new bException(tr('twilio_groups_get(): No twilio specified'), 'not-specified');
        }

        if(!is_scalar($group)){
            throw new bException(tr('twilio_groups_get(): Specified twilio ":group" is not scalar', array(':group' => $group)), 'invalid');
        }

        $retval = sql_get('SELECT    `twilio_groups`.`id`,
                                     `twilio_groups`.`name`,
                                     `twilio_groups`.`status`,
                                     `twilio_groups`.`description`,

                                     `createdby`.`name`   AS `createdby_name`,
                                     `createdby`.`email`  AS `createdby_email`,
                                     `modifiedby`.`name`  AS `modifiedby_name`,
                                     `modifiedby`.`email` AS `modifiedby_email`

                           FROM      `twilio_groups`

                           LEFT JOIN `users` AS `createdby`
                           ON        `twilio_groups`.`createdby`  = `createdby`.`id`

                           LEFT JOIN `users` AS `modifiedby`
                           ON        `twilio_groups`.`modifiedby` = `modifiedby`.`id`

                           WHERE     `twilio_groups`.`id`         = :twilio
                           OR        `twilio_groups`.`name`       = :twilio',

                           array(':twilio' => $twilio));

        return $retval;

    }catch(Exception $e){
        throw new bException('twilio_groups_get(): Failed', $e);
    }
}



/*
 *
 */
function twilio_accounts_validate($account){
    try{
        load_libs('validate');

        $v = new validate_form($account, 'email,accounts_id,accounts_token');
        $v->isNotEmpty($account['email'], tr('No twilios name specified'));
        $v->isEmail($account['email']   ,  2, tr('Please ensure the twilio name has at least 2 characters'));

        $v->isNotEmpty($account['accounts_id']     , tr('No twilio account description specified'));
        $v->hasMinChars($account['accounts_id'], 32, tr('Please ensure the twilio description has at least 2 characters'));
        $v->hasMaxChars($account['accounts_id'], 40, tr('Please ensure the twilio description has less than 2047 characters'));

        $v->isNotEmpty($account['accounts_token']     , tr('No Account token specified'));
        $v->hasMinChars($account['accounts_token'], 32, tr('Please ensure the account\'s description has at least 2 characters'));
        $v->hasMaxChars($account['accounts_token'], 40, tr('Please ensure the twilio description has less than 2047 characters'));


        /*
         * Does the twilio already exist?
         */
        if(empty($account['id'])){
            if($id = sql_get('SELECT `id` FROM `twilio_accounts` WHERE `email` = :email', array(':email' => $account['email']))){
                $v->setError(tr('The Twilio account ":account" already exists with id ":id"', array(':account' => $account['email'], ':id' => $id)));
            }

        }else{
            if($id = sql_get('SELECT `id` FROM `twilio_accounts` WHERE `email` = :email AND `id` != :id', array(':email' => $account['email'], ':id' => $account['id']))){
                $v->setError(tr('The Twilio account ":account" already exists with id ":id"', array(':account' => $account['email'], ':id' => $id)));
            }
        }

        $v->isValid();

        return $account;

    }catch(Exception $e){
        throw new bException(tr('twilio_accounts_validate(): Failed'), $e);
    }
}



/*
 *
 */
function twilio_accounts_get($account){
    try{
        if(!$account){
            throw new bException(tr('twilio_accounts_get(): No twilio account specified'), 'not-specified');
        }

        if(!is_scalar($account)){
            throw new bException(tr('twilio_accounts_get(): Specified twilio account ":account" is not scalar', array(':account' => $right)), 'invalid');
        }

        $retval = sql_get('SELECT    `twilio_accounts`.`id`,
                                     `twilio_accounts`.`email`,
                                     `twilio_accounts`.`accounts_id`,
                                     `twilio_accounts`.`accounts_token`,
                                     `twilio_accounts`.`status`,

                                     `createdby`.`name`   AS `createdby_name`,
                                     `createdby`.`email`  AS `createdby_email`,
                                     `modifiedby`.`name`  AS `modifiedby_name`,
                                     `modifiedby`.`email` AS `modifiedby_email`

                           FROM      `twilio_accounts`

                           LEFT JOIN `users` AS `createdby`
                           ON        `twilio_accounts`.`createdby`  = `createdby`.`id`

                           LEFT JOIN `users` AS `modifiedby`
                           ON        `twilio_accounts`.`modifiedby` = `modifiedby`.`id`

                           WHERE     `twilio_accounts`.`id`   = :account
                           OR        `twilio_accounts`.`email` = :account',

                           array(':account' => $account));

        return $retval;

    }catch(Exception $e){
        throw new bException('twilio_accounts_get(): Failed', $e);
    }
}



/*
 *
 */
function twilio_numbers_validate($number, $old_twilio = null){
    try{
        load_libs('validate');

        if($old_twilio){
            $number = array_merge($old_twilio, $number);
        }

        $v = new validate_form($number, 'email,accounts_id,account_token');
        $v->isNotEmpty  ($number['name']    , tr('No name specified'));
        $v->hasMinChars ($number['name'],  2, tr('Please ensure the number name has at least 2 characters'));

        $v->isNotEmpty ($number['number']    , tr('No number description specified'));
        $v->hasMinChars($number['number'], 12, tr('Please ensure the number has at least 12 digits'));
        $v->isPhonenumber($number['number']  , tr('Please ensure the number is telphone number valid'));

        $v->isNotEmpty($number['accounts_id'], tr('No account specified'));
        $v->isNumeric ($number['accounts_id'], tr('Invalid account specified'));

        if($number['groups_id']){
            $v->isNumeric($number['groups_id'], tr('Invalid group specified'));

        }else{
            $number['groups_id'] = null;
        }

        /*
         * Does the twilio already exist?
         */
        if(empty($number['id'])){
            $id = sql_get('SELECT `id`

                           FROM   `twilio_numbers`

                           WHERE  `name`   = :name
                           OR     `number` = :number',

                          'id', array(':name'   => $number['name'],
                                      ':number' => $number['number']));

            if($id){
                $v->setError(tr('The twilio number ":number" or name ":name" already exists with id ":id"', array(':name' => $number['name'], ':number' => $number['number'], ':id' => $id)));
            }

        }else{
            $id = sql_get('SELECT `id`

                           FROM   `twilio_numbers`

                           WHERE (`name`   = :name
                           OR     `number` = :number)
                           AND    `id`    != :id',

                          'id', array(':id'     => $number['id'],
                                      ':name'   => $number['name'],
                                      ':number' => $number['number']));

            if($id){
                $v->setError(tr('The twilio number ":number" or name ":name" already exists with id ":id"', array(':name' => $number['name'], ':number' => $number['number'], ':id' => $id)));
            }
        }

        $v->isValid();

        return $number;

    }catch(Exception $e){
        throw new bException(tr('twilio_numbers_validate(): Failed'), $e);
    }
}



/*
 *
 */
function twilio_numbers_get($number){
    try{
        if(!$number){
            throw new bException(tr('twilio_numbers_get(): No number specified'), 'not-specified');
        }

        if(!is_scalar($number)){
            throw new bException(tr('twilio_numbers_get(): Specified twilio number ":number" is not scalar', array(':number' => $number)), 'invalid');
        }

        $retval = sql_get('SELECT   `twilio_numbers`.`id`,
                                    `twilio_numbers`.`name`,
                                    `twilio_numbers`.`number`,
                                    `twilio_numbers`.`accounts_id`,
                                    `twilio_numbers`.`groups_id`,
                                    `twilio_numbers`.`status`

                           FROM     `twilio_numbers`

                           WHERE    `twilio_numbers`.`name`   = :name
                           OR       `twilio_numbers`.`number` = :number',

                           array(':name'   => $number,
                                 ':number' => $number));

        return $retval;

    }catch(Exception $e){
        throw new bException('twilio_numbers_get(): Failed', $e);
    }
}
?>
