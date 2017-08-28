<?php
/*
 * SMS library
 *
 * This library is the generic SMS interface library
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
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
 *
 */
function sms_get_conversation($phone_local, $phone_remote, $type, $repliedon_now = false){
    global $_CONFIG;

    try{
        $phone_local  = sms_full_phones($phone_local);
        $phone_remote = sms_full_phones($phone_remote);

        /*
         * Determine the local and remote phones
         */
        if(twilio_numbers_get($phone_remote)){
            if(twilio_numbers_get($phone_local)){
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
            sql_query('INSERT INTO `sms_conversations` (`phone_local`, `phone_remote`, `type`, `repliedon`)
                       VALUES                          (:phone_local , :phone_remote , :type , '.($repliedon_now ? 'NOW()' : 'NULL').')',

                       array(':type'         => $type,
                             ':phone_local'  => $phone_local,
                             ':phone_remote' => $phone_remote));

            $conversation = array('id'            => sql_insert_id(),
                                  'last_messages' => '');
        }

        return $conversation;

    }catch(Exception $e){
        throw new bException('sms_get_conversation(): Failed', $e);
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
            $message = str_replace('%datetime%', system_date_format($datetime), $_CONFIG['twilio']['conversations']['message_dates']).$message;
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
?>
