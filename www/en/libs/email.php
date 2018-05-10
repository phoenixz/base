<?php
/*
 * Email library
 *
 * This library can be used to manage emails
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 *
 * Requires php-imap library
 * debian / ubuntu alikes : sudo apt-get -y install php5-imap openssl; sudo php5enmod imap
 * Redhat / Fedora alikes : sudo yum -y install php5-imap openssl
 *
 * Enable imap in email https://mail.google.com/mail/u/0/#settings/fwdandpop
 * Disable captcha https://accounts.google.com/DisplayUnlockCaptcha
 * First connection might be refused by email, and you may have to allow the connection here: https://security.google.com/settings/security/activity
 *
 * In case basic authentication is used, allow less secure apps in https://www.google.com/settings/security/lesssecureapps, see also https://support.google.com/accounts/answer/6010255?hl=en
 *
 * TO ADD USERS TO POSTFIX VIRTUAL USERS:
 * INSERT INTO `virtual_users` (`domain_id`, `password`, `email`) VALUES (1, ENCRYPT('the_password_here', CONCAT('$6$', SUBSTRING(SHA(RAND()), -16))), "user@domain.com");
 */



load_config('email');

if(!function_exists('imap_open')){
    throw new bException(tr('php module "imap" appears not to be installed. Please install the module first. On Ubuntu and alikes, use "sudo apt-get -y install php5-imap; sudo php5enmod imap" to install and enable the module., on Redhat and alikes use ""sudo yum -y install php5-imap" to install the module. After this, a restart of your webserver or php-fpm server might be needed'), 'not-available');
}



/*
 * Send a new email
 */
// :IMPLEMENT: Add support for non basic authentication which is more secure! See https://developers.google.com/email/xoauth2_protocol#the_sasl_xoauth2_mechanism
// https://developers.google.com/api-client-library/php/start/installation
function email_connect($userdata, $mail_box = null){
    global $_CONFIG;
    static $connections = array();

    try{
        if($mail_box){
            if($mail_box == 'inbox'){
                $mail_box = 'INBOX';

            }else{
                $mail_box = 'INBOX.'.$mail_box;
            }
        }

        $imap = str_until($userdata['imap'], '}').'}'.$mail_box;

        if(!empty($connections[$userdata['email'].$mail_box])){
            /*
             * Return cached connection
             */
            if(VERBOSE and PLATFORM_CLI){
                log_console(tr('Using cached IMAP connection for account ":email" mailbox ":mailbox"', array(':email' => $userdata['email'], ':mailbox' => $mail_box)));
            }

            return $connections[$userdata['email'].$mail_box];
        }

        /*
         * Get userdata and connect to imap
         */
// :TODO: array('DISABLE_AUTHENTICATOR' => array('NTLM', 'GSSAPI')) is hard coded, make this configurable as well!
        $connection = imap_open($imap, $userdata['email'], $userdata['password'], null, 1, array('DISABLE_AUTHENTICATOR' => array('NTLM', 'GSSAPI')));

        if(VERBOSE and PLATFORM_CLI){
            log_console(tr('Created IMAP connection for account  ":email" mailbox ":mailbox"', array(':email' => $userdata['email'], ':mailbox' => $mail_box)));
        }

        /*
         * Cache and return the connection
         */
        if(count($connections) >= $_CONFIG['email']['imap_cache']){
            array_shift($connections);
        }

        $connections[$imap] = $connection;

        return $connection;

    }catch(Exception $e){
        throw new bException(tr('email_connect(): Failed'), $e);
    }
}



/*
 * Poll for new emails
 */
function email_poll($params){
    global $_CONFIG;

    try{
        array_params($params);
        array_default($params, 'account'       , null);
        array_default($params, 'mail_box'      , null);
        array_default($params, 'criteria'      , 'ALL');
        array_default($params, 'delete'        , false);
        array_default($params, 'peek'          , false);
        array_default($params, 'internal'      , false);
        array_default($params, 'uid'           , false);
        array_default($params, 'character_set' , 'UTF-8');
        array_default($params, 'store'         , false);
        array_default($params, 'return'        , false);
        array_default($params, 'callbacks'     , array());
        array_default($params, 'return'        , false);
        array_default($params, 'forward_option', false);

        if($params['peek'] and $params['delete']){
            throw new bException(tr('email_poll(): Both peek and delete were specified, though they are mutually exclusive. Please specify one or the other'), 'conflict');
        }

        if(PLATFORM_CLI){
            log_console(tr('Polling email account ":account"', array(':account' => $params['account'])));

            if($params['peek']){
                log_console(tr('Using peek flag'));
            }
        }

        $userdata = email_get_client_account($params['account']);

        /*
         * Pre IMAP Fetch
         */
        execute_callback(isset_get($params['callbacks']['start']));

        $imap     = email_connect($userdata, $params['mail_box']);
        $mails    = imap_search($imap, $params['criteria'], SE_FREE, $params['character_set']);
        $retval   = array();

        /*
         * Post IMAP Fetch
         */
        $mails = execute_callback(isset_get($params['post_search']), $mails);

        if(!$mails){
            if(PLATFORM_CLI){
                log_console(tr('No emails found for account ":email"', array(':email' => $userdata['email'])), 'yellow');
            }

        }else{
            if(!$mails){
                log_console(tr('Callback "post_search" canceled email_poll'), 'yellow');
                return false;
            }

            if(PLATFORM_CLI){
                log_console(tr('Found ":count" mails for account ":email"', array(':count' => count($mails), ':email' => $userdata['email'])), 'green');
            }

            rsort($mails);

            /*
             * Set flags
             */
            $flags = ($params['peek'] ? FT_PEEK : null) or ($params['uid'] ? FT_UID : null) or ($params['internal'] ? FT_INTERNAL : null);

            /*
             * Process every email
             */
            foreach($mails as $mails_id){
                /*
                 * Get information specific to this email
                 */
                $mail = execute_callback(isset_get($params['callbacks']['pre_fetch']), $mails_id);

                if(!$mail){
                    log_console(tr('Callback "pre_fetch" canceled processing of email ":mails_id"', array(':mails_id' => $mails_id)), 'yellow');
                    continue;
                }

                $data = imap_fetch_overview($imap, $mails_id, 0);
                $data = array_shift($data);
                $data = array_from_object($data);

                if(VERBOSE and PLATFORM_CLI){
                    log_console(tr('Found mail ":subject"', array(':subject' => isset_get($data['subject']))));
                }


                /*
                 * - Source matches "To:" to "Inbox recibe in", this is usefull when there are aliases sending
                 * to and inbox and we want to modify the "To: " file acordingly
                 * - Target will leave it as it is
                 * - Account is usefull just to we can perform the same checks per account and not globally
                 */
                $delete       = $params['delete'];
                $delete_count = 0;

                if(empty($data['from']) or empty($data['to'])){
                        /*
                         * Apparently this is not an email
                         */
                        log_console(tr('Warning: email ":mail" appears not to be an email, skipping. Mail contains data ":data"', array(':mail' => $mail, ':data' => $data)), 'yellow');
                        /*
                         * This is likely and email we don't want so we manually mark it for deliton
                         */
                        $delete = true;

                }else{
                    if($userdata['email'] !== $data['to']){
                        switch($_CONFIG['email']['forward_option']){
                            case 'source':
                                $data['to'] = $userdata['email'];
                                break;

                            case 'target':
                                break;

                            case 'account':
                                /*
                                 * Per account settings
                                 */
                                switch($userdata['forward_option']){
                                    case 'source':
                                        $data['to'] = $userdata['email'];
                                        break;

                                    case 'target':
                                        break;

                                    default:
                                        throw new bException(tr('email_poll(): Unknown account forward_option ":option" specified', array(':option' => $params['forward_option'])), 'unknown');
                                }

                                break;

                            default:
                                throw new bException(tr('email_poll(): Unknown $_CONFIG[email][forward_option] ":option" specified', array(':option' => $_CONFIG['email']['forward_option'])), 'unknown');
                        }
                    }

                    $data['text'] = imap_fetchbody($imap, $mail, 1.1, $flags);
                    $data['html'] = imap_fetchbody($imap, $mail, 1.2, $flags);

                    if(!$data['text']){
                        $data['text'] = imap_fetchbody($imap, $mail, 1, $flags);
                    }

                    /*
                     * Get the images of the email
                     */
                    $data = email_get_attachments($imap, $mail, $data, $flags);

                    /*
                     * Decode the body text.
                     *
                     * NOTE: Do not use imap_qprint() but quoted_printable_decode()
                     * for this due to a bug in imap_qprint(). See
                     * http://php.net/manual/en/function.imap-qprint.php#4009 for
                     * more information
                     */
                    $data['text'] = trim(mb_strip_invalid(quoted_printable_decode($data['text'])));
                    $data['text'] = str_replace("\r", '', $data['text']);
                    $data['html'] = trim(mb_strip_invalid(imap_qprint($data['html'])));
                    $data['html'] = str_replace("\r", '', $data['html']);

                    $data = execute_callback(isset_get($params['callbacks']['post_fetch']), $data);

                    if(!$data){
                        log_console(tr('Callback "post_fetch" canceled processing of email ":mails_id"', array(':mails_id' => $mails_id)), 'yellow');
                        continue;
                    }

                    if($params['store']){
                        try{
                            if(VERBOSE AND PLATFORM_CLI){
                                log_console(tr('Processing email ":subject"', array(':subject' => $mail['subject'])));
                            }

                            $data                      = email_cleanup($data);
                            $data['users_id']          = $userdata['users_id'];
                            $data['email_accounts_id'] = $userdata['id'];

                            email_update_conversation($data, 'received');
                            $data = execute_callback(isset_get($params['callbacks']['post_update']), $data);

                            if(!$data){
                                log_console(tr('Callback "post_update" canceled processing of email ":mails_id"', array(':mails_id' => $mails_id)), 'yellow');
                                continue;
                            }

                        }catch(bException $e){
                            /*
                             * Continue working on the next mail
                             */
                            notify($e);
                            log_console(tr('Failed polling process'), 'yellow');
                        }
                    }

                    if($params['return']){
                        $retval[$mails_id] = $data;
                    }

                }

                if($delete){
                    $delete_count++;

                    imap_delete($imap, $mail);
                }

                execute_callback(isset_get($params['callbacks']['post_delete']), $mail);
            }

            if($delete_count){
                imap_expunge($imap);
            }

            execute_callback(isset_get($params['callbacks']['post_expunge']), $mails);

            if(VERBOSE and PLATFORM_CLI){
                log_console(tr('Processed ":count" new mails for account ":account"', array(':count' => count($mails), ':account' => $params['account'])));
            }

            sql_query('UPDATE `email_client_accounts` SET `last_poll` = NOW() WHERE `id` = :id', array(':id' => $userdata['id']));
        }

        return $retval;

    }catch(Exception $e){
        log_console(tr('Failed to poll email data for account ":account" because ":e"', array(':account' => $params['account'], ':e' => $e->getMessages())), 'yellow');
        throw new bException(tr('email_poll(): Failed'), $e);
    }
}



/*
 *
 */
function email_get_attachments($imap, $email, $data, $flags){
    try{
        /*
         * Extract the images of the emails if there are
         */
        load_libs('file,image');

        $decode = imap_fetchbody($imap, $email , '', $flags);
        $count  = substr_count($decode, "Content-Transfer-Encoding: base64");

        if(!$count){
            /*
             * Hhmm, there are no attachments, why are we here?
             */
            return $data;
        }

        $structure = imap_fetchstructure($imap, $email);

        /*
         * Loop through each image
         */
        for($i = 0; $i < $count; $i++){
            try{
                $section = strval(2 + $i);
                $decode  = imap_fetchbody($imap, $email, $section);
                $img     = base64_decode($decode);

                /*
                 * Get file type
                 */
                $f         = finfo_open();
                $mime_type = finfo_buffer($f, $img, FILEINFO_MIME_TYPE);
                $extension = '.'.str_from($mime_type, '/');

                switch($extension){
                    case '.jpeg':
                        $extension = '.jpg';
                        break;

                    default:
                        /*
                         * Assume mimetype is extension
                         */
                }

                $file_name = time().'_'.$i.$extension;

                if(!empty($structure->parts[$i + 1]->id)){
                    /*
                     * This is an inline image
                     */
                    $data['img'.$i]['cid'] = rtrim(trim($structure->parts[$i + 1]->id, '<'), '>');

                }else{
                    /*
                     * Attachment
                     */
                    $data['img'.$i]['cid'] = 'attachment';
                }

                $data['img'.$i]['file'] = $file_name;
                file_put_contents(TMP.$file_name, $img);

            }catch(Exception $e){
                /*
                 * An image failed, just continue
                 */
                log_database(tr('email_get_attachments(): Failed to process attachment ":attachment" from message ":message" from email ":email"', array(':attachment' => $i, ':message' => isset_get($data['subject']), ':email' => $email)), 'error');
                continue;
            }
        }

       return $data;

    }catch(Exception $e){
        throw new bException(tr('email_get_attachments(): Failed'), $e);
    }
}



/*
 * Get the specified email conversation
 *
 * A conversation is a collection of email messages, in order of date, that share the same sender, receiver, and subject (subject may contain "RE: ")
 */
function email_get_conversation($email){
    try{
        /*
         *
         */
        array_ensure($email, 'subject');

        $conversation = sql_get('SELECT `id`,
                                        `users_id`,
                                        `last_messages`,
                                        `email_accounts_id`

                                 FROM   `email_conversations`

                                 WHERE ((`us`      LIKE :us1     AND `them`    LIKE :them1)
                                 OR     (`us`      LIKE :them2   AND `them`    LIKE :us2)
                                 AND    (`subject` =    :subject OR  `subject` =    :resubject))',

                                 array(':us1'       => '%'.$email['to'].'%',
                                       ':them1'     => '%'.$email['from'].'%',
                                       ':us2'       => '%'.$email['to'].'%',
                                       ':them2'     => '%'.$email['from'].'%',
                                       ':subject'   => mb_trim(str_starts_not($email['subject'], 'RE:')),
                                       ':resubject' => str_starts($email['subject'], 'RE:')));

        if(!$conversation){
            /*
             * This is a new conversation
             */
            if(empty($email['email_accounts_id'])){
                $email['email_accounts_id'] = sql_get('SELECT `id` FROM `email_client_accounts` WHERE `email` = :email', 'id', array(':email' => $email['from']));

                if(!$email['email_accounts_id']){
                    $email['email_accounts_id'] = sql_get('SELECT `id` FROM `email_client_accounts` WHERE `email` = :email', 'id', array(':email' => $email['to']));
                }
            }

            sql_query('INSERT INTO `email_conversations` (`subject`, `them`, `us`, `email_accounts_id`, `users_id`)
                       VALUES                            (:subject , :them , :us , :email_accounts_id , :users_id )',

                       array(':us'                => $email['to'],
                             ':them'              => $email['from'],
                             ':users_id'          => isset_get($email['users_id']),
                             ':email_accounts_id' => $email['email_accounts_id'],
                             ':subject'           => (string) $email['subject']));

            $conversation = array('id'            => sql_insert_id(),
                                  'last_messages' => '');
        }

        return $conversation;

    }catch(Exception $e){
        throw new bException(tr('email_get_conversation(): Failed'), $e);
    }
}



/*
 * Update email conversation
 *
 * A conversation is a collection of email messages, in order of date, that share the same sender, receiver, and subject (subject may contain "RE: ")
 */
function email_update_conversation($email, $direction){
    global $_CONFIG;

    try{
        load_libs('json');

        $email = email_update_message($email, $direction);

        if(empty($direction)){
            throw new bException(tr('email_update_conversation(): No conversation direction specified'), 'not-specified');
        }

        if(($direction != 'sent') and ($direction != 'received')){
            throw new bException(tr('email_update_conversation(): Invalid conversation direction ":direction:" specified', array(':direction' => $direction)), 'not-specified');
        }

        if(empty($email['conversation'])){
            throw new bException(tr('email_update_conversation(): Specified email ":subject" does not contain a conversation', array(':subject' => $email['subject'])), 'not-specified');
        }

        if(empty($email['id'])){
            throw new bException(tr('email_update_conversation(): Specified email ":subject" has no database id', array(':subject' => $email['subject'])), 'not-specified');
        }

        /*
         * Get the conversation from the email
         */
        $conversation = $email['conversation'];

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
            if(count($conversation['last_messages']) >= $_CONFIG['email']['conversations']['size']){
                array_pop($conversation['last_messages']);
            }

        }else{
            $conversation['last_messages'] = array();
        }

        /*
         * Add message timestamp to each message?
         */
        if($_CONFIG['email']['conversations']['message_dates']){
            $email['text'] = str_replace('%datetime%', date_convert($email['date']), $_CONFIG['email']['conversations']['message_dates']).$email['text'];
        }

        /*
         * Add new message. Truncate each message by 10% to ensure that the conversations last_message string does not surpass 1024 characters
         */
        array_unshift($conversation['last_messages'], array('id'        => $email['id'],
                                                            'direction' => $direction,
                                                            'message'   => $email['text']));

        $last_messages  = json_encode_custom($conversation['last_messages']);
        $message_length = strlen($last_messages);

        while($message_length > 2048){
            /*
             * The JSON string is too large to be stored, reduce the size of the largest messages and try again
             */
            foreach($conversation['last_messages'] as $id => $message){
                $sizes[$id] = strlen($message['message']);
            }

            arsort($sizes);

            $size = reset($sizes);
            $id   = key($sizes);

            $conversation['last_messages'][$id]['message'] = substr($conversation['last_messages'][$id]['message'], 0, floor($sizes[$id] * .9));

            unset($message);
            unset($sizes);
            unset($size);

            $last_messages  = json_encode_custom($conversation['last_messages']);
            $message_length = strlen($last_messages);
        }

        if($direction == 'sent'){
            sql_query('UPDATE `email_conversations`

                       SET    `last_messages` = :last_messages,
                              `direction`     = "send",
                              `modifiedon`    = NOW(),
                              `repliedon`     = NOW()

                       WHERE  `id`            = :id',

                       array(':id'            => $conversation['id'],
                             ':last_messages' => $last_messages));

        }else{
            sql_query('UPDATE `email_conversations`

                       SET    `last_messages` = :last_messages,
                              `direction`     = "received",
                              `modifiedon`    = NOW(),
                              `repliedon`     = NULL

                       WHERE  `id`            = :id',

                       array(':id'            => $conversation['id'],
                             ':last_messages' => $last_messages));
        }

    }catch(Exception $e){
        throw new bException(tr('email_update_conversation(): Failed'), $e);
    }
}



/*
 *
 */
function email_update_message($email, $direction){
    try{
        $email['users_id']          = email_get_users_id($email);
        $email['email_accounts_id'] = email_get_accounts_id($email);
        $email['conversation']      = email_get_conversation($email);
        $email['reply_to_id']       = email_get_reply_to_id($email);

        if(empty($email['id']) and !empty($email['message_id'])){
            /*
             * Perhaps we already have this email, check the messages_id
             */
            $email['id'] = sql_get('SELECT `id` FROM `email_messages` WHERE `message_id` = :message_id', 'id', array('message_id' => $email['message_id']));
        }

        if(empty($email['id'])){
           switch($direction){
                case 'sent':
                    sql_query('INSERT INTO `email_messages` (`direction`, `conversations_id`, `reply_to_id`, `from`, `to`, `users_id`, `email_accounts_id`, `date`, `subject`, `text`, `html`, `sent`                             )
                               VALUES                       (:direction , :conversations_id , :reply_to_id , :from , :to , :users_id , :email_accounts_id , :date , :subject , :text , :html , '.($email['sent'] ? 'NOW()' : '').')',

                               array(':direction'         => $direction,
                                     ':conversations_id'  => isset_get($email['conversation']['id']),
                                     ':reply_to_id'       => isset_get($email['reply_to_id']),
                                     ':from'              => isset_get($email['from']),
                                     ':to'                => isset_get($email['to']),
                                     ':users_id'          => isset_get($email['users_id']),
                                     ':email_accounts_id' => isset_get($email['email_accounts_id']),
                                     ':date'              => isset_get($email['date'], date_convert(null, 'mysql')),
                                     ':subject'           => (string) isset_get($email['subject']),
                                     ':text'              => isset_get($email['text']),
                                     ':html'              => isset_get($email['html'])));
                    break;

                case 'received':
                    sql_query('INSERT INTO `email_messages` (`direction`, `conversations_id`, `reply_to_id`, `from`, `to`, `users_id`, `email_accounts_id`, `date`, `message_id`, `size`, `uid`, `msgno`, `recent`, `flagged`, `answered`, `deleted`, `seen`, `draft`, `udate`, `subject`, `text`, `html`)
                               VALUES                       (:direction , :conversations_id , :reply_to_id , :from , :to , :users_id , :email_accounts_id , :date , :message_id , :size , :uid , :msgno , :recent , :flagged , :answered , :deleted , :seen , :draft , :udate , :subject , :text , :html )',

                               array(':direction'         => $direction,
                                     ':conversations_id'  => isset_get($email['conversation']['id']),
                                     ':reply_to_id'       => isset_get($email['reply_to_id']),
                                     ':from'              => isset_get($email['from']),
                                     ':to'                => isset_get($email['to']),
                                     ':users_id'          => isset_get($email['users_id']),
                                     ':email_accounts_id' => isset_get($email['email_accounts_id']),
                                     ':date'              => isset_get($email['date'], date_convert(null, 'mysql')),
                                     ':message_id'        => isset_get($email['message_id']),
                                     ':size'              => isset_get($email['size']),
                                     ':uid'               => isset_get($email['uid']),
                                     ':msgno'             => isset_get($email['msgno']),
                                     ':recent'            => isset_get($email['recent']),
                                     ':flagged'           => isset_get($email['flagged']),
                                     ':answered'          => isset_get($email['answered']),
                                     ':deleted'           => isset_get($email['deleted']),
                                     ':seen'              => isset_get($email['seen']),
                                     ':draft'             => isset_get($email['draft']),
                                     ':udate'             => isset_get($email['udate']),
                                     ':subject'           => (string) isset_get($email['subject']),
                                     ':text'              => isset_get($email['text']),
                                     ':html'              => isset_get($email['html'])));
                    break;

                default:
                    throw new bException(tr('email_update_message(): Unknown direction "%direction%" specified', array('%direction%' => $direction)), 'unknown');
            }

            $email['id'] = sql_insert_id();
            email_check_images($email);

        }else{
            sql_query('UPDATE `email_messages`

                       SET    `direction`         = :direction,
                              `conversations_id`  = :conversations_id,
                              `reply_to_id`       = :reply_to_id,
                              `from`              = :from,
                              `to`                = :to,
                              `users_id`          = :users_id,
                              `email_accounts_id` = :email_accounts_id,
                              `date`              = :date,
                              `subject`           = :subject,
                              `text`              = :text,
                              `html`              = :html,
                              `sent`              = :sent

                       WHERE  `id`                = :id',

                       array(':id'                => $email['id'],
                             ':direction'         => $direction,
                             ':conversations_id'  => $email['conversation']['id'],
                             ':reply_to_id'       => $email['reply_to_id'],
                             ':from'              => $email['from'],
                             ':to'                => $email['to'],
                             ':users_id'          => $email['users_id'],
                             ':email_accounts_id' => $email['email_accounts_id'],
                             ':date'              => isset_get($email['date'], date_convert(null, 'mysql')),
                             ':subject'           => $email['subject'],
                             ':text'              => $email['text'],
                             ':html'              => $email['html'],
                             ':sent'              => (empty($email['sent']) ? null : date_convert($email['sent'], 'mysql'))));
        }

        return $email;

    }catch(Exception $e){
        throw new bException(tr('email_update_message(): Failed'), $e);
    }
}



/*
 *
 */
function email_cleanup($email){
    try{
        foreach($email as $key => &$value){
            if(is_scalar($value)){
                if(strstr($value, '?utf-8?B?')){
                    $value = base64_decode(str_from($value, '?utf-8?B?'));
                }
            }
        }

        if(strstr($email['to'], '<')){
            $email['to'] = str_cut($email['to'], '<', '>');
        }

        if(strstr($email['from'], '<')){
            $email['from'] = str_cut($email['from'], '<', '>');
        }

        return $email;

    }catch(Exception $e){
        throw new bException(tr('email_cleanup(): Failed'), $e);
    }
}



/*
 * Check if there are images for an email, insert them in the database
 * and move them to the correct location
 */
function email_check_images($email){
    try{
        /*
         * If there are images insert them into `email_files` table
         */
        $i = 0;

        $name   = str_until($email['to'], '@');
        $domain = str_from ($email['to'], '@');

        while(!empty($email['img'.$i])){
            if(empty($path)){
                $path = ROOT.'data/email/images/'.$domain.'/'.$name.'/'.$email['id'];
                file_ensure_path($path);
            }

            /*
             * Insert the image in the database
             */
            sql_query('INSERT INTO `email_files` (`email_messages_id`, `file_cid`, `file`)
                       VALUES                    (:email_messages_id , :file_cid , :file )',

                       array(':email_messages_id' => $email['id'],
                             ':file_cid'          => $email['img'.$i]['cid'],
                             ':file'              => $email['img'.$i]['file']));

            /*
             * Move the image to the correct location
             */
            rename(TMP.$email['img'.$i]['file'], ROOT.'data/email/images/'.$domain.'/'.$name.'/'.$email['id'].'/'.$email['img'.$i]['file']);
            $email['img'.$i]['file'] = ROOT.'data/email/images/'.$domain.'/'.$name.'/'.$email['id'].'/'.$email['img'.$i]['file'];

            $i++;
        }

    }catch(Exception $e){
        throw new bException(tr('email_check_images(): Failed'), $e);
    }
}



/*
 * Return the id of the last email for this conversation
 */
function email_get_reply_to_id($email){
    try{
        if(empty($email['conversation']['id'])){
            return null;
        }

        return sql_get('SELECT `id` FROM `email_messages` WHERE `conversations_id` = :conversations_id LIMIT 1', 'id', array(':conversations_id' => $email['conversation']['id']));

    }catch(Exception $e){
        throw new bException(tr('email_get_reply_to_id(): Failed'), $e);
    }
}



/*
 *
 */
function email_get_users_id($email){
    try{
        if(!empty($email['users_id'])){
            return $email['users_id'];
        }

        $r = sql_query('SELECT `id` FROM `users` WHERE `email` = :from OR `email` = :to', array(':from' => $email['from'], ':to' => $email['to']));

        switch($r->rowCount()){
            case 0:
                return null;

            case 1:
                return sql_fetch($r, 'id');
        }

        /*
         * This is a mail between two local users, yay!
         */
        return sql_get('SELECT `id` FROM `users` WHERE `email` = :from', 'id', array(':from' => $email['from']));

    }catch(Exception $e){
        throw new bException(tr('email_get_users_id(): Failed'), $e);
    }
}



/*
 *
 */
function email_get_accounts_id($email){
    try{
        if(!empty($email['email_accounts_id'])){
            return $email['email_accounts_id'];
        }

        $r = sql_query('SELECT `id` FROM `email_client_accounts` WHERE `email` = :from OR `email` = :to', array(':from' => $email['from'], ':to' => $email['to']));

        switch($r->rowCount()){
            case 0:
                return null;

            case 1:
                return sql_fetch($r, 'id');
        }

        /*
         * This is a mail between two local accounts, yay!
         */
        return sql_get('SELECT `id` FROM `email_client_accounts` WHERE `email` = :from', 'id', array(':from' => $email['from']));

    }catch(Exception $e){
        throw new bException(tr('email_get_accounts_id(): Failed'), $e);
    }
}



/*
 * Send a new email
 */
function email_send($email, $smtp = null){
    global $_CONFIG;

    try{
        array_params($email);
        array_default($email, 'delayed'     , true);
        array_default($email, 'conversation', true);
        array_default($email, 'validate'    , true);
        array_default($email, 'smtp_host'   , false);

        if($email['validate']){
            $email = email_validate($email);
        }

        if($email['delayed']){
            /*
             * Don't send the email right now. The email script can be used
             * later to send all delayed emails. This way, when a mail message
             * has been sent in a web page, the web page does not have to wait
             * for the mail to be sent, it will be sent in a background process
             */
            return email_delay($email);
        }

        /*
         * Send the email right now
         */
        $mail    = email_load_phpmailer();
        $account = email_get_client_account($email['from']);

        $mail->IsSMTP(); // send via SMTP

        if(empty($smtp)){
            /*
             * Use the default SMTP configuration
             */
            $mail->Host      = $_CONFIG['email']['smtp']['host'];
            $mail->Port      = $_CONFIG['email']['smtp']['port'];
            $mail->SMTPAuth  = $_CONFIG['email']['smtp']['auth'];
//            $mail->SMTPDebug = 2;

            switch(isset_get($_CONFIG['email']['smtp']['secure'])){
                case '':
                    /*
                     * Don't use secure connection
                     */
                    break;

                case 'ssl':
                    //FALLTHROUGH
                case 'tls':
                    $mail->SMTPSecure = $_CONFIG['email']['smtp']['secure'];
                    break;

                default:
                    throw new bException(tr('email_send(): Unknown global SMTP secure setting ":value" for host "%host%". Use either false, "tls", or "ssl"', array(':value' => $_CONFIG['email']['smtp']['secure'], '%host%' => $_CONFIG['email']['smtp']['host'])), 'unknow');
            }

        }else{
            /*
             * Use user specific SMTP configuration
             */
            $mail->Host     = $smtp['host'];
            $mail->Port     = isset_get($smtp['port'], 25);
            $mail->SMTPAuth = $smtp['auth'];

            switch(isset_get($smtp['secure'])){
                case '':
                    /*
                     * Don't use secure connection
                     */
                    break;

                case 'ssl':
                    //FALLTHROUGH
                case 'tls':
                    $mail->SMTPSecure = $smtp['secure'];
                    break;

                default:
                    throw new bException(tr('email_send(): Unknown user specific SMTP secure setting ":value" for host ":host". Use either false, "tls", or "ssl"', array(':value' => $smtp['secure'], ':host' => $_CONFIG['email']['smtp']['host'])), 'unknown');
            }
        }

        $mail->From     = $email['from'];
        $mail->FromName = isset_get($email['from_name']);

        $mail->AddReplyTo($email['from'], isset_get($email['from_name']));
        $mail->AddAddress($email['to']  , isset_get($email['to_name']));

//        $mail->WordWrap = 50; // set word wrap
        $mail->Username = $account['email'];
        $mail->Password = $account['password'];

        if(empty($email['html'])){
            $mail->IsHTML(false);
            $mail->Body = $email['text'];

        }else{
            $mail->IsHTML(true);
            $mail->Body = $email['html'];
        }

        $mail->Subject = $email['subject'];
        $mail->AltBody = $email['text'];

        if(!empty($email['attachments'])){
            foreach(array_force($email['attachments']) as $attachment){
// :IMPLEMENT:
            //$mail->AddAttachment("/var/tmp/file.tar.gz"); // attachment
            //$mail->AddAttachment("/tmp/image.jpg", "new.jpg"); // attachment
            }
        }

        if(!$mail->Send()){
            throw new bException(tr('email_send(): Failed because ":error"',  array(':error' => $mail->ErrorInfo)), 'mailfail');
        }

        $email['sent'] = date_convert(null, 'mysql');

        if($email['conversation']){
            email_update_conversation($email, 'sent');
        }

    }catch(Exception $e){
        throw new bException(tr('email_send(): Failed'), $e);
    }
}



/*
 *
 */
function email_from_exists($email){
    global $_CONFIG;

    try{
        /*
         * Validate email, extract it from "user <email>" if needed
         */
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
            $email = str_cut($email, '<', '>');

            if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
                throw new bException(tr('email_from_exists(): Specified "from" email address ":email" is not a valid email address', array(':email' => $email)), 'invalid');
            }
        }

        if(empty($_CONFIG['email']['users'])){
            /*
             * Get list from database
             */
            $account = sql_get('SELECT `id`, `email`, `status` FROM `email_client_accounts` WHERE `email` = :email', array(':email' => $email));

            if(!$account){
// :DELETE: _exists() functions should just return true or false, the entry exists or not
                //throw new bException(tr('email_from_exists(): Specified email address ":email" does not exist', array(':email' => $email)), 'not-exist');
                return false;
            }

            if($account['status']){
// :DELETE: _exists() functions should just return true or false, the entry exists or not
                //throw new bException(tr('email_from_exists(): Specified email address ":email" is currently not available', array(':email' => $email)), 'not-available');
                return false;
            }

            return true;

        }

        /*
         * Using the old (and obsoleted) hard configured emails
         */
        if(!empty($_CONFIG['email']['aliases'][$email])){
            return $_CONFIG['email']['aliases'][$email];
        }

        return !empty($_CONFIG['email']['users'][$email]);

    }catch(Exception $e){
        throw new bException(tr('email_from_exists(): Failed'), $e);
    }
}



/*
 * Load the phpmailer library. Auto install if its not available
 */
function email_load_phpmailer(){
    try{
        if(!file_exists(ROOT.'/libs/ext/PHPMailer/PHPMailerAutoload.php')){

            if(PLATFORM_CLI){
                log_console('email_load_phpmailer(): phpmailer not found, installing now');
            }

            /*
             * Install it first
             * Update parent directory file mode first to be sure its writable
             */
            load_libs('file');
            $file = file_temp('phpmailer.zip');
            $path = slash(dirname($file));

            file_put_contents($file, fopen('https://github.com/PHPMailer/PHPMailer/archive/master.zip', 'r'));
            safe_exec('cd '.$path.'; unzip '.$file);

            /*
             * Move PHPMailer into its required location. Ensure the path is writable.
             */
            file_execute_mode(ROOT.'libs/ext/PHPMailer', 0750, function(){ rename($path.'PHPMailer-master/', ROOT.'libs/ext/PHPMailer'); });

            safe_exec('rm '.$path.' -rf ');
        }

        load_libs('ext/PHPMailer/PHPMailerAutoload');

        return new PHPMailer();

    }catch(Exception $e){
        throw new bException(tr('email_load_phpmailer(): Failed'), $e);
    }
}



/*
 * Validates the specified email array and returns correct email data
 */
function email_validate($email){
    try{
        load_libs('validate');
        array_default($email, 'validate_sender', false);

        $v = new validate_form($email, 'body,subject,to,from');

        $v->isNotEmpty($email['to']     , tr('Please specify an email destination'));
        $v->isNotEmpty($email['from']   , tr('Please specify an email source'));
        $v->isNotEmpty($email['subject'], tr('Please specify an email subject'));
        $v->isNotEmpty($email['subject'], tr('Please write something in the email'));

        if(!email_from_exists($email['from'])){
            $v->setError(tr('Specified source email ":email" does not exist', array(':email' => $email['from'])));
        }

        switch(isset_get($email['format'])){
            case '':
                $email['format'] = 'text';
                // FALLTHROUGH
            case 'text':
                $email['html'] = $email['body'];
                $email['text'] = $email['body'];
                break;

            case 'html':
                $email['html'] = $email['body'];
                $email['text'] = strip_tags($email['body']);
                break;

            default:
                $v->setError(tr('Unkown format ":format" specified', array(':format' => $params['format'])));
        }

        $v->isValid();

        return email_prepare($email);

    }catch(Exception $e){
        throw new bException(tr('email_validate(): Failed'), $e);
    }
}



/*
 * Prepare the email with "date","from" and "to"
 */
function email_prepare($email){
    global $_CONFIG;

    try{
        array_default($email, 'replace' , true);
        array_default($email, 'header'  , isset_get($_CONFIG['email']['header']));
        array_default($email, 'footer'  , isset_get($_CONFIG['email']['footer']));
        array_default($email, 'template', '');

        /*
         * Which format are we using?
         */
        if(!empty($params['template'])){
            /*
             * Ensure that the specified type exists on configuration
             */
            if(empty($_CONFIG['email']['templates'][$params['template']])){
                throw new bException(tr('email_prepare(): Unkown template ":template"', array(':template' => $params['template'])), 'unkown');
            }

            $replace = array(':body' => $email['body']);

            $email['body'] = load_content($_CONFIG['email']['templates'][$email['template']]['file'], $replace, LANGUAGE);

        }else{
            $params['template'] = null;
        }

// :DELETE: I'm not even going to pretend I understand why this is needed, or what it is supposed to be used for...
        ///*
        // * Get user info
        // */
        //$user = sql_get('SELECT `id`,
        //                        `name`,
        //                        `username`,
        //                        `email`
        //
        //                 FROM   `users`
        //
        //                 WHERE  `email` = :email',
        //
        //                 array(':email' => $params['to']));
        //
        //if(!$user){
        //    if($params['require_user']){
        //        throw new bException(tr('email_delay(): Specified user ":user" does not exist', array(':user' => $params['to'])), 'not-exist');
        //    }
        //
        //    $user = array('id' => null);
        //}

        $email['date'] = date('Y-m-d H:i:s');

        /*
         * Add header / footer
         */
        if($email['header']){
            $email['text'] = $_CONFIG['email']['header'].$email['text'];
            $email['html'] = $_CONFIG['email']['header'].$email['html'];
        }

        if($email['footer']){
            $email['text'] = $email['text'].$_CONFIG['email']['footer'];
            $email['html'] = $email['html'].$_CONFIG['email']['footer'];
        }



        /*
         *
         */
        if(strpos($email['to'], '<') !== false){
            $email['to_name'] = trim(str_until($email['to'], '<'));
            $email['to']      = trim(str_cut($email['to'], '<', '>'));

        }else{
            $email['to_name'] = '';
        }

        if(strpos($email['from'], '<') !== false){
            $email['from_name'] = trim(str_until($email['from'], '<'));
            $email['from']      = trim(str_cut($email['from'], '<', '>'));

        }else{
            $email['from_name'] = '';
        }



        /*
         * Do search / replace over the email body
         */
        if($email['replace']){
            load_libs('user');

            switch(gettype($email['replace'])){
                case 'boolean':
                    $email['replace'] = array(//':toname' => (empty($email['user_name']) ? $email['user_username'] : $email['user_name']),
                                              ':user'   => name($_SESSION['user']),
                                              ':email'  => isset_get($_SESSION['user']['email']),
                                              ':domain' => domain());
                case 'array':
                    break;

                default:
                    throw new bException(tr('email_prepare(): Invalid "replace" specified, is a ":type" but should be either true, false, or an array containing the from => to values', array(':type' => gettype($email['replace']))), 'invalid');
            }

            $email['text'] = str_replace(array_keys($email['replace']), array_values($email['replace']), $email['text']);
        }

        return $email;

    }catch(Exception $e){
        throw new bException(tr('email_prepare(): Failed'), $e);
    }
}



/*
 * Return userdata for the specified username
 */
function email_get_account($email, $columns = null){
    try{
        /*
         * Ensure we have only email address
         * Get domain name
         */
        if(strpos($email, '<') !== false){
            $email = str_cut($email, '<', '>');
        }

        if(!$columns){
            $columns = '`email_client_accounts`.`id`,
                        `email_client_accounts`.`createdby`,
                        `email_client_accounts`.`createdon`,
                        `email_client_accounts`.`modifiedby`,
                        `email_client_accounts`.`modifiedon`,
                        `email_client_accounts`.`status`,
                        `email_client_accounts`.`domains_id`,
                        `email_client_accounts`.`users_id`,
                        `email_client_accounts`.`id` AS `email_accounts_id`,
                        `email_client_accounts`.`name`,
                        `email_client_accounts`.`email`,
                        `email_client_accounts`.`password`,
                        `email_client_accounts`.`poll_interval`,
                        `email_client_accounts`.`last_poll`,
                        `email_client_accounts`.`header`,
                        `email_client_accounts`.`footer`,
                        `email_client_accounts`.`description`,

                        `email_domains`.`domain`        AS `domain`,
                        `email_domains`.`header`        AS `domain_header`,
                        `email_domains`.`footer`        AS `domain_footer`,
                        `email_domains`.`poll_interval` AS `domain_poll_interval`,

                        `email_domains`.`smtp_host`,
                        `email_domains`.`smtp_port`,
                        `email_domains`.`imap`';
        }

        $retval = sql_get('SELECT    '.$columns.'

                           FROM      `email_client_accounts`

                           LEFT JOIN `email_domains`
                           ON        `email_domains`.`id` = `email_client_accounts`.`domains_id`

                           WHERE  `email` = :email',

                           array(':email' => $email));

        if(!$retval){
            throw new bException(tr('email_get_account(): Specified email ":email" does not exist', array(':email' => $email)), 'not-exist');
        }

        return $retval;

    }catch(Exception $e){
        throw new bException(tr('email_get_account(): Failed'), $e);
    }
}



/*
 * Return userdata for the specified username
 */
function email_get_client_account($email, $columns = null){
    try{
        /*
         * Ensure we have only email address
         * Get domain name
         */
        if(strpos($email, '<') !== false){
            $email = str_cut($email, '<', '>');
        }

        if(!$columns){
            $columns = '`email_client_accounts`.`id`,
                        `email_client_accounts`.`createdby`,
                        `email_client_accounts`.`createdon`,
                        `email_client_accounts`.`modifiedby`,
                        `email_client_accounts`.`modifiedon`,
                        `email_client_accounts`.`status`,
                        `email_client_accounts`.`domains_id`,
                        `email_client_accounts`.`users_id`,
                        `email_client_accounts`.`id` AS `email_client_accounts_id`,
                        `email_client_accounts`.`name`,
                        `email_client_accounts`.`email`,
                        `email_client_accounts`.`seoemail`,
                        `email_client_accounts`.`password`,
                        `email_client_accounts`.`poll_interval`,
                        `email_client_accounts`.`last_poll`,
                        `email_client_accounts`.`header`,
                        `email_client_accounts`.`footer`,
                        `email_client_accounts`.`description`,

                        `email_client_domains`.`seoname`       AS `domain`,
                        `email_client_domains`.`header`        AS `domain_header`,
                        `email_client_domains`.`footer`        AS `domain_footer`,
                        `email_client_domains`.`poll_interval` AS `domain_poll_interval`,

                        `email_client_domains`.`smtp_host`,
                        `email_client_domains`.`smtp_port`,
                        `email_client_domains`.`imap`';
        }

        $retval = sql_get('SELECT    '.$columns.'

                           FROM      `email_client_accounts`

                           LEFT JOIN `email_client_domains`
                           ON        `email_client_domains`.`id` = `email_client_accounts`.`domains_id`

                           WHERE  `seoemail` = :seoemail
                           OR     `email`    = :email',

                           array(':email'    => $email,
                                 ':seoemail' => $email));

        if(!$retval){
            throw new bException(tr('email_get_client_account(): Specified email ":email" does not exist', array(':email' => $email)), 'not-exist');
        }

        return $retval;

    }catch(Exception $e){
        throw new bException(tr('email_get_client_account(): Failed'), $e);
    }
}



/*
 * Return domain data for the specified username
 */
function email_get_domain($email_or_domain, $columns = null, $table = 'email_domains'){
    try{
        if(!$columns){
            $columns = '`id`,
                        `createdby`,
                        `createdon`,
                        `modifiedby`,
                        `modifiedon`,
                        `status`,
                        `name`,
                        `seoname`,
                        `poll_interval`,
                        `description`,
                        `smtp_host`,
                        `smtp_port`,
                        `imap`,
                        `header`,
                        `footer`';
        }

        /*
         * Ensure we have only email address
         * Get domain name
         */
        if(strpos($email_or_domain, '<') !== false){
            $email_or_domain = str_cut($email_or_domain, '<', '>');
        }

        $domain = str_from($email_or_domain, '@');

        $retval = sql_get('SELECT '.$columns.'

                           FROM   `'.$table.'`

                           WHERE  `seoname` = :seoname',

                           array(':seoname' => $domain));

        return $retval;

    }catch(Exception $e){
        throw new bException(tr('email_get_domain(): Failed'), $e);
    }
}



/*
 * Return domain data for the specified username
 */
function email_client_get_domain($email_or_domain, $columns = null){
    try{
        return email_get_domain($email_or_domain, $columns, 'email_client_domains');

    }catch(Exception $e){
        throw new bException(tr('email_client_get_domain(): Failed'), $e);
    }
}



/*
 * This function inserts new emails on DB
 */
function email_delay($email){
    global $_CONFIG;

    try{
        array_params($email);
        array_default($email, 'auto_start', isset_get($_CONFIG['email']['delayed']['auto_start']));

        /*
         * Store the email on DB with the `status` = "new"
         */
        sql_query('INSERT INTO `emails` (`createdby`, `users_id`, `status`, `subject`, `from`, `to`, `html`, `text`, `format`)
                   VALUES               (:createdby , :users_id , "new"   , :subject , :from , :to , :html , :text , :format )',

                   array(':createdby' => isset_get($_SESSION['user']['id']),
                         ':users_id'  => isset_get($email['users_id']),
                         ':subject'   => $email['subject'],
                         ':from'      => $email['from'],
                         ':to'        => $email['to'],
                         ':html'      => $email['html'],
                         ':text'      => $email['text'],
                         ':format'    => $email['format']));

        if($email['auto_start']){
            /*
             * Run the script to send the "new" emails
             */
            run_background('base/email --env '.ENVIRONMENT.' send', true, true);
        }

        return sql_insert_id();

    }catch(Exception $e){
        throw new bException(tr('email_delay(): Failed'), $e);
    }
}



/*
 * This function send the emails stored in DB with `status` = "new"
 */
function email_send_unsent(){
    global $_CONFIG;

    try{
        /*
         * Load the emails where status is "new"
         */
        $r = sql_query('SELECT    `emails`.`id` AS `emails_id`,
                                  `emails`.`status`,
                                  `emails`.`template`,
                                  `emails`.`subject`,
                                  `emails`.`from`,
                                  `emails`.`to`,
                                  `emails`.`text`,
                                  `emails`.`html`,
                                  `emails`.`format`,
                                  `emails`.`users_id`,
                                  `users`.`name`     AS `user_name`,
                                  `users`.`username` AS `user_username`

                        FROM      `emails`

                        LEFT JOIN `users`
                        ON        `emails`.`users_id` = `users`.`id`

                        WHERE     `emails`.`status`   = "new"

                        LIMIT     20');

        $p = sql_prepare('UPDATE `emails`

                          SET    `status` = "sent",
                                 `senton` = NOW()

                          WHERE  `id`     = :id');

        /*
         * Prepare to send each email and then
         * update the `status` to "sent" and also update the `senton` date
         */
        $count = 0;

        while($email = sql_fetch($r)){
            /*
             * Don't delay again, its already stored!
             * Don't validate again, its already processed and valid!
             */
            $count++;
            $email['delayed']  = false;
            $email['validate'] = false;

            try{
                /*
                 * Send the email
                 */
                email_send($email);

            }catch(Exception $e){
                /*
                 * Error ocurred ! ... Notify and continue sending emails
                 */
                if(PLATFORM_CLI){
                    log_console(tr('Failed to send email to user ":user" because ":e"', array(':user' => $email['to'], ':e' => $e->getMessage())), 'error');
                }

                log_database(tr('Failed to send email to user ":user" because ":e"', array(':user' => $email['to'], ':e' => $e->getMessage())), 'error');
                continue;
            }

            /*
             * The mail was sent, update the `status` and `senton`
             */
            $p->execute(array(':id' => $email['emails_id']));

            /*
             * Wait a little as to not be too heavy on system resources
             */
            usleep(500);
        }

        return $count;

    }catch(Exception $e){
        throw new bException(tr('email_send_unsent(): Failed'), $e);
    }
}



/*
 *
 */
function email_get_encryption_key(){
    global $_CONFIG;

    try{
        if(empty($_CONFIG['email']['encryption_key'])){
            throw new bException(tr('email_get_encryption_key(): $_CONFIG[email][encryption_key] has not been specified. Please specify a random key first'), 'not-specified');
        }

        return $_CONFIG['email']['encryption_key'];

    }catch(Exception $e){
        throw new bException(tr('email_get_encryption_key(): Failed'), $e);
    }
}



/*
 * Validate the data of the specified email-domain
 */
function email_validate_domain($domain, $table = 'email_domains'){
    try{
        load_libs('seo');
        $v = new validate_form($domain, 'name,imap,smpt_host,smtp_port,description,header,footer,poll_interval');

        $v->isNotEmpty  ($domain['name']    , tr('Please provide a name'));
        $v->hasMinChars ($domain['name'],  2, tr('Please ensure that the name has a minimum of 2 characters'));
        $v->hasMaxChars ($domain['name'], 96, tr('Please ensure that the name has a maximum of 96 characters'));

        if(strpos($domain['name'], ' ') !== false){
            $v->setError(tr('Please ensure that the domain name contains no spaces'));
        }

        $v->hasMaxChars ($domain['description'], 4096, tr('Please ensure that the description has a maximum of 4K characters'));
        $v->hasMaxChars ($domain['header']     , 4096, tr('Please ensure that the header has a maximum of 4K characters'));
        $v->hasMaxChars ($domain['footer']     , 4096, tr('Please ensure that the footer has a maximum of 4K characters'));

        $v->isNotEmpty ($domain['smtp_host'], tr('Please provide an SMTP host'));
        $v->isNotEmpty ($domain['smtp_port'], tr('Please provide an SMTP port'));
        $v->isNumeric  ($domain['smtp_port'], tr('Please ensure that the SMTP port is numeric'));
        $v->hasMaxChars($domain['smtp_host'], 128, tr('Please ensure that the name has a maximum of 128 characters'));

        $v->isNotEmpty ($domain['imap'], tr('Please provide an IMAP connection string'));
        $v->isRegex    ($domain['imap'], '/^\{[a-zA-Z0-9\.-]+?:\d{1,5}(?:\/imap\/ssl(?:\/novalidate-cert)?)?\}[A-Z]+$/', tr('Please provide valid a IMAP connection string, like {mail.domain.com:993/imap/ssl}INBOX'));

        if($domain['poll_interval'] === ''){
            $domain['poll_interval'] = null;
        }

        $v->isNatural  ($domain['poll_interval'], tr('Please provide a natural numeric poll interval'), 0);

        $domain['seoname'] = seo_unique($domain['name'], $table, $domain['id']);

        $v->isValid();

        return $domain;

    }catch(Exception $e){
        throw new bException(tr('email_validate_domain(): Failed'), $e);
    }
}



/*
 * Validate the data of the specified email-user
 */
function email_validate_account($account, $client){
    try{
        load_libs('seo');

        if($client){
            $client = '_client';
        }

        $v = new validate_form($account, 'name,email,password,description,header,footer,poll_interval');
        $v->isEmail($account['email']      , tr('Please provide a valid email'));
        $v->isNotEmpty($account['name']    , tr('Please provide a name'));
        $v->hasMinChars($account['name']   , 1, tr('Please ensure that the name has a minimum of 1 character'));
        $v->isNotEmpty($account['password'], tr('Please provide a password'));

        if(empty($account['domain'])){
            $v->setError(tr('Please specify a domain from the list'));
        }

        $v->hasMaxChars($account['description'], 4096, tr('Please ensure that the description has a maximum of 4K characters'), VALIDATE_ALLOW_EMPTY_NULL);
        $v->hasMaxChars($account['header']     , 4096, tr('Please ensure that the header has a maximum of 4K characters'), VALIDATE_ALLOW_EMPTY_NULL);
        $v->hasMaxChars($account['footer']     , 4096, tr('Please ensure that the footer has a maximum of 4K characters'), VALIDATE_ALLOW_EMPTY_NULL);

        $domain = sql_get('SELECT `id`, `status` FROM `email'.$client.'_domains` WHERE `seoname` = :seoname', array(':seoname' => $account['domain']));

        if(!$domain){
            $v->setError(tr('The specified domain ":domain" does not exist', array(':domain' => $account['domain'])));

        }elseif($domain['status']){
            /*
             * Domain is possibly deleted, or disabled
             */
            $v->setError(tr('The specified domain ":domain" is not available', array(':domain' => $account['domain'])));
        }

        $account['domains_id'] = $domain['id'];

        if(!$account['poll_interval']){
            $account['poll_interval'] = 0;
        }

        $v->isNatural($account['poll_interval'], tr('Please provide a natural numeric poll interval'), 0);

        $account['seoemail'] = seo_unique($account['email'], 'email'.$client.'_accounts', $account['id'], 'seoemail');

        $v->isValid();

        return $account;

    }catch(Exception $e){
        throw new bException(tr('email_validate_account(): Failed'), $e);
    }
}



/*
 * Delete a group of email messages
 */
function email_delete($params){
    try{
        array_params($params);
        array_default($params, 'account' , '');
        array_default($params, 'mail_box', '');
        array_default($params, 'criteria', '');
        array_default($params, 'filters' , array());

        if(!$params['account']){
            throw new bException(tr('email_delete(): No account specified'), 'not-specified');
        }

        if(!$params['mail_box']){
            throw new bException(tr('email_delete(): No mail_box specified'), 'not-specified');
        }

        if(!$params['criteria']){
            throw new bException(tr('email_delete(): No criteria specified'), 'not-specified');
        }

        if(!$params['filters']){
            throw new bException(tr('email_delete(): No filters specified'), 'not-specified');
        }

        if(PLATFORM_CLI){
            log_console(tr('Polling email account ":account"', array(':account' => $params['account'])));
        }

        if($params['filters']['old']){
            /*
             * When scanning for old messages, already filter for them to scan
             * less messages. Since imap_search doesn't work below day level,
             * we'll have to scan for the hour-minute-second level ourselves.
             */
            $params['filters']['old'] = strtoupper($params['filters']['old']);

            try{
                $date = new DateTime();
                $date->sub(new DateInterval('P'.$params['filters']['old']));

            }catch(Exception $e){
                throw new bException(tr('email_delete(): Invalid datetime interval ":interval" specified for the --old filter. See http://php.net/manual/en/dateinterval.construct.php on how to construct these.', array(':interval' => 'P'.$params['filters']['old'])), 'invalid');
            }

            $params['criteria'] .= ' SINCE '.$date->format('d-M-Y');
        }

        $count    = 0;
        $userdata = email_get_client_account($params['account']);
        $imap     = email_connect($userdata, $params['mail_box']);
        $mails    = imap_search($imap, $params['criteria']);
        $retval   = array();

        if(!$mails){
            if(PLATFORM_CLI){
                log_console(tr('No emails found for account ":email"', array(':email' => $userdata['email'])), 'yellow');
            }

        }else{
            if(PLATFORM_CLI){
                log_console(tr('Found ":count" mails for account ":email"', array(':count' => count($mails), ':email' => $userdata['email'])), 'green');
            }

            rsort($mails);

            /*
             * Process every email
             */
            foreach($mails as $mail){
                $delete = false;

                /*
                 * Get information specific to this email
                 */
                $data = imap_fetch_overview($imap, $mail, 0);
                $data = array_shift($data);
                $data = array_from_object($data);

                foreach($params['filters'] as $filter => $value){
                    switch($filter){
                        case 'seen':
                            if($mail['seen']) $delete = true;
                            break;

                        case 'old':
                            /*
                             * Detect old value format
                             */
                            $mail_date = new DateTime($data['date']);

                            if($mail_date < $date){
                                $delete = true;
                            }

                            break;
                    }

                    if($delete) break;
                }

                if($delete){
                    if(VERBOSE and PLATFORM_CLI){
                        log_console(tr('Marked mail ":subject" for deletion', array(':subject' => $data['subject'])));
                    }

                    imap_delete($imap, $mail);
                    $count++;
                }
            }

            if($delete){
                imap_expunge($imap);
            }

            if(VERBOSE and PLATFORM_CLI){
                log_console(tr('Deleted ":count" new mails for account ":account"', array(':count' => $count, ':account' => $params['account'])));
            }
        }

        return $count;

    }catch(Exception $e){
        throw new bException(tr('email_delete(): Failed'), $e);
    }
}



/*
 *
 */
function email_test_account($account, $mail_box = 'INBOX'){
    try{
throw new bException(tr('email_test(): This functionality is still under construction'), 'under-construction');
        $userdata = email_get_client_account($account);
        $imap     = email_connect($userdata, $mail_box);
        $mails    = imap_search($imap, 'UNSEEN', SE_FREE, 'UTF-8');

show($mails);
showdie($imap);
$mail = array_pop($mails);
$data = imap_fetch_overview($imap, $mail, 0);
$data = array_shift($data);
$data = array_from_object($data);
showdie($data);

        return count($mails);

    }catch(Exception $e){
showdie($e);
        throw new bException(tr('email_test_account(): Failed'), $e);
    }
}




/*
 * OBSOLETE FUNCTIONS FOLLOW BELOW
 */
function email_get_user($email, $columns = null){
    try{
        return email_get_client_account($email, $columns = null);

    }catch(Exception $e){
        throw new bException(tr('email_delete(): Failed'), $e);
    }
}

function email_validate_user($user){
    try{
        return email_validate_account($user);

    }catch(Exception $e){
        throw new bException(tr('email_validate_user(): Failed'), $e);
    }
}
?>
