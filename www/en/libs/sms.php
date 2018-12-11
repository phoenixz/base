<?php
/*
 * SMS library
 *
 * This library is the generic SMS interface library
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 */
load_config('sms');



/*
 * Send SMS
 */
function sms_send_message($message, $to, $from = null){
    global $_CONFIG;

    try{
        if($from === 'crmtext'){
            $provider = $from;

        }else{
            $provider = 'twilio';
        }

        switch($provider){
            case 'crmtext':
                load_libs('crmtext');
                return crmtext_send_message($message, $to);

            case 'twilio':
                load_libs('twilio');
                return twilio_send_message($message, $to, $from);

            default:
                throw new bException(tr('sms_send(): Unknown preferred SMS provider "%provider%" specified, check your configuration $_CONFIG[sms][prefer]', array('%provider%' => $_CONFIG['sms']['prefer'])), 'unknown');
        }

    }catch(Exception $e){
        throw new bException('sms_send(): Failed', $e);
    }
}



/*
 * Get the conversation between the specified local and remote phone numbers
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sms
 *
 * @param string $phone_local
 * @param string $phone_remote
 * @param string $type
 * @param integer $createdon
 * @param integer $repliedon_now
 * @return array
 */
function sms_get_conversation($phone_local, $phone_remote, $type, $createdon = null, $repliedon_now = false){
    global $_CONFIG;

    try{
        $phone_local  = sms_full_phones($phone_local);
        $phone_remote = sms_full_phones($phone_remote);

        /*
         * Determine the local and remote phones
         */
        if(twilio_get_number($phone_remote)){
            if(twilio_get_number($phone_local)){
                /*
                 * The remote number and local numbers are both locally known
                 * numbers. We can onlyh assume the order is correct, so don't
                 * do anything
                 */
            }else{
                /*
                 * The remote number is actually a locally known number
                 */
                $tmp          = $phone_remote;
                $phone_remote = $phone_local;
                $phone_local  = $tmp;

                unset($tmp);
            }
        }

        /*
         * Find an existing conversation for the specified phones
         */
        $conversation = sql_get('SELECT `id`,
                                        `last_messages`

                                 FROM   `sms_conversations`

                                 WHERE  `phone_remote` = :phone_remote
                                 AND    `phone_local`  = :phone_local
                                 AND    `type`         = :type',

                                 array(':type'         => $type,
                                       ':phone_local'  => $phone_local,
                                       ':phone_remote' => $phone_remote));

        if(!$conversation){
            /*
             * This phone combo has no conversation yet, create it now.
             */
            $blocked = sql_get('SELECT `id` FROM `sms_blocks` WHERE `number` = :number AND `status` IS NULL', true, array(':number' => $phone_remote));

            sql_query('INSERT INTO `sms_conversations` (`createdon`, `modifiedon`, `status`, `phone_local`, `phone_remote`, `type`, `repliedon`)
                       VALUES                          (:createdon , :modifiedon , :status , :phone_local , :phone_remote , :type , :repliedon )',

                       array(':type'         => $type,
                             ':createdon'    => $createdon,
                             ':modifiedon'   => $createdon,
                             ':repliedon'    => ($repliedon_now ? $createdon : null),
                             ':status'       => ($blocked       ? 'blocked'  : null),
                             ':phone_local'  => $phone_local,
                             ':phone_remote' => $phone_remote));

            $conversation = array('id'            => sql_insert_id(),
                                  'last_messages' => '');
        }

        return $conversation;

    }catch(Exception $e){
        throw new bException(tr('sms_get_conversation(): Failed for numbers local ":local", remote ":remote"', array(':local' => $phone_local, ':remote' => $phone_remote)), $e);
    }
}



/*
 * Update the specified conversation with the specified message
 */
function sms_update_conversation($conversation, $messages_id, $direction, $message, $datetime, $replied){
    global $_CONFIG;

    try{
        load_libs('json');

        if(empty($conversation['id'])){
            throw new bException('sms_update_conversation(): No conversation id specified', 'not-specified');
        }

        if(empty($direction)){
            throw new bException('sms_update_conversation(): No conversation direction specified', 'not-specified');
        }

        if(empty($message)){
            throw new bException('sms_update_conversation(): No conversation message specified', 'not-specified');
        }

        /*
         * Decode the current last_messages
         */
        if($conversation['last_messages']){
            try{
                $conversation['last_messages'] = json_decode_custom($conversation['last_messages']);

            }catch(Exception $e){
                /*
                 * Ups, JSON decode failed!
                 */
                $conversation['last_messages'] = array(array('id'        => null,
                                                             'message'   => tr('Failed to decode messages'),
                                                             'direction' => 'unknown'));
            }

            /*
             * Ensure the conversation does not pass the max size
             */
            if(count($conversation['last_messages']) >= $_CONFIG['twilio']['conversations']['size']){
                array_pop($conversation['last_messages']);
            }

        }else{
            $conversation['last_messages'] = array();
        }

        /*
         * Add message timestamp to each message?
         */
        if($_CONFIG['twilio']['conversations']['message_dates']){
            $message = str_replace('%datetime%', date_convert($datetime), $_CONFIG['twilio']['conversations']['message_dates']).$message;
        }

        /*
         * Are there MMS images?
         */
        $images = sql_list('SELECT `id`, `file`, `url` FROM `sms_images` WHERE `sms_messages_id` = :sms_messages_id', array(':sms_messages_id' => $messages_id));

        if($images){
            foreach($images as $image){

                $message = html_img(($image['file'] ? $image['file'] : $image['url']), tr('MMS image'), 28, 28, 'class="mms"').$message;
            }
        }

        /*
         * Add new message. Truncate each message by N% to ensure that the conversations last_message string does not surpass 1024 characters
         */
        array_unshift($conversation['last_messages'], array('id'        => $messages_id,
                                                            'direction' => $direction,
                                                            'message'   => $message));

        $last_messages  = json_encode_custom($conversation['last_messages']);
        $message_length = strlen($last_messages);

        while($message_length > 4000){
            /*
             * The JSON string is too large to be stored, reduce the amount of messages and try again
             */
            array_pop($conversation['last_messages']);
            $last_messages  = json_encode_custom($conversation['last_messages']);
            $message_length = strlen($last_messages);
        }

        if($replied){
            sql_query('UPDATE `sms_conversations`

                       SET    `last_messages` = :last_messages,
                              `direction`     = "send",
                              `modifiedon`    = NOW(),
                              `repliedon`     = NOW()

                       WHERE  `id`            = :id',

                       array(':id'            => $conversation['id'],
                             ':last_messages' => $last_messages));

        }else{
            sql_query('UPDATE `sms_conversations`

                       SET    `last_messages` = :last_messages,
                              `direction`     = "received",
                              `modifiedon`    = NOW(),
                              `repliedon`     = NULL

                       WHERE  `id`            = :id',

                       array(':id'            => $conversation['id'],
                             ':last_messages' => $last_messages));
        }

    }catch(Exception $e){
        throw new bException('sms_update_conversation(): Failed', $e);
    }
}



/*
 * Return a phone number that always includes a country code
 */
function sms_full_phones($phones){
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
            if(is_numeric($phone)){
                $phone = '+'.$_CONFIG['twilio']['defaults']['country_code'].$phone;
            }
        }

        return str_force($phones, ',');

    }catch(Exception $e){
        throw new bException('sms_full_phones(): Failed', $e);
    }
}



/*
 * Return a phone number that guaranteed contains no country code
 */
// :TODO: Add support for other countries than the US
function sms_no_country_phones($phones){
    global $_CONFIG;

    try{
        $phones = array_force($phones);

        foreach($phones as &$phone){
            $phone = trim($phone);

            if(substr($phone, 0, 1) != '+'){
                /*
                 * Phone has no country code
                 */
                continue;
            }

            /*
             * Assume this is a US phone, return with +1
             */
            if(substr($phone, 1, 1) == '1'){
                $phone = substr($phone, 2);
            }
        }

        return str_force($phones, ',');

    }catch(Exception $e){
        throw new bException('sms_full_phones(): Failed', $e);
    }
}



/*
 *
 */
function sms_select_source($name, $selected, $provider, $class){
    global $_CONFIG;

    try{
        load_config('twilio');

        $resource = array();

        foreach($_CONFIG['twilio']['accounts'] as $account => $data){
            $resource = array_merge($resource, $data['sources']);
        }

        $sources = array('name'     => $name,
                         'class'    => $class,
                         'none'     => tr('Select number'),
                         'selected' => $selected,
                         'resource' => $resource);

        if(isset_get($provider) == 'crmtext'){
            $sources['resource']['crmtext']  = tr('Shortcode');
            $sources['selected']             = 'crmtext';
        }

        return html_select($sources);

    }catch(Exception $e){
        throw new bException('sms_select_source(): Failed', $e);
    }
}



/*
 * Block the specified phone number. Blocked numbers will no longer show up as new conversations, though they may still be updated
 *
 * @param string $phone_number The phone number to be blocked
 * @return integer The amount of phone numbers that were actually blocked
 */
function sms_block($phone_numbers, $status = null){
    try{
        /*
         * First block the number
         */
        $count         = 0;
        $phone_numbers = sms_full_phones($phone_numbers);
        $insert        = sql_prepare('INSERT INTO `sms_blocks` (`createdby`, `meta_id`, `status`, `number`)
                                      VALUES                   (:createdby , :meta_id , :status , :number )

                                      ON DUPLICATE KEY UPDATE `id` = `id`');

        foreach(array_force($phone_numbers) as $phone_number){
            $insert->execute(array(':createdby' => isset_get($_SESSION['user']['id']),
                                   ':meta_id'   => meta_action(),
                                   ':status'    => $status,
                                   ':number'    => $phone_number));

            $count += sql_affected_rows($insert);

            /*
             * Now update all conversations so that this number in history is blocked as well
             */
            sql_query('UPDATE `sms_conversations` SET `repliedon` = NOW(), `status` = "blocked" WHERE `phone_remote` = :phone_remote', array(':phone_remote' => $phone_number));
        }

        return $count;

    }catch(Exception $e){
        throw new bException('sms_block(): Failed', $e);
    }
}



/*
 * Unblock the specified phone number. Unblocked numbers will show up as normal again
 *
 * @param string $phone_number The phone number to be unblocked
 * @return integer The amount of phone numbers that were actually unblocked
 */
function sms_unblock($phone_numbers, $status = null){
    try{
        /*
         * First unblock the number
         */
        $count         = 0;
        $phone_numbers = sms_full_phones($phone_numbers);
        $in            = sql_in($phone_numbers);

        $numbers       = sql_query('SELECT `id`, `meta_id`, `number` FROM `sms_blocks` WHERE `number` IN ('.implode(', ', array_keys($in)).')', $in);

        $delete        = sql_prepare('UPDATE `sms_blocks`

                                      SET    `status` = "removed"

                                      WHERE  `number` = :number');

        while($number = sql_fetch($numbers)){
            meta_action($number['meta_id'], 'removed');

            $delete->execute(array(':number' => $number['number']));

            $count += sql_affected_rows($delete);

            /*
             * Now update all conversations so that this number in history is unblocked as well
             */
            sql_query('UPDATE `sms_conversations` SET `status` = NULL WHERE `phone_remote` = :phone_remote', array(':phone_remote' => $number['number']));
        }

        return $count;

    }catch(Exception $e){
        throw new bException('sms_unblock(): Failed', $e);
    }
}
?>
