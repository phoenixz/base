<?php
/*
 * Email library
 *
 * This library can be used to manage emails
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>
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
 */



load_config('email');

if(!function_exists('imap_open')){
    throw new bException(tr('php module "imap" appears not to be installed. Please install the module first. On Ubuntu and alikes, use "sudo apt-get -y install php5-imap; sudo php5enmod imap" to install and enable the module., on Redhat and alikes use ""sudo yum -y install php5-imap" to install the module'), 'not_available');
}



/*
 * Send a new email
 */
// :IMPLEMENT: Add support for database users
// :IMPLEMENT: Add support for non basic authentication which is more secure! See https://developers.google.com/email/xoauth2_protocol#the_sasl_xoauth2_mechanism
// https://developers.google.com/api-client-library/php/start/installation
function email_connect($username){
    global $_CONFIG;
    static $connections = array();

    try{
        if(!empty($connections[$username])){
            /*
             * Return cached connection
             */
            return $connections[$username];
        }

        $userdata = email_get_user($username, 'users');

        $connections[$username] = imap_open($_CONFIG['email']['imap'], $userdata['email'], $userdata['pass'], null, 1, array('DISABLE_AUTHENTICATOR' => array('NTLM', 'GSSAPI')));

        return $connections[$username];

    }catch(Exception $e){
        throw new bException('email_connect(): Failed', $e);
    }
}



/*
 * Poll for new emails
 */
function email_list($usernames, $criteria = 'ALL'){
    try{
        $retval = array();

        foreach(array_force($usernames) as $username){
            $inbox  = email_connect($username);
            $emails = imap_search($inbox, $criteria);

            $retval[$username] = array();

            if($emails){
                rsort($emails);

                /* for every email... */
                foreach($emails as $email) {
                    /*
                     * get information specific to this email
                     */
                    $data         = imap_fetch_overview($inbox, $email, 0);
                    $data         = array_shift($data);
                    $data         = array_from_object($data);

                    $data['text'] = imap_fetchbody($inbox, $email, 1.1);
                    $data['html'] = imap_fetchbody($inbox, $email, 1.2);

                    if(!$data['text']){
                        $data['text'] = imap_fetchbody($inbox, $email, 1);
                    }

                    $data['text'] = trim(imap_qprint($data['text']));
                    $data['text'] = str_replace("\r", '', $data['text']);
                    $data['html'] = trim(imap_qprint($data['html']));
                    $data['html'] = str_replace("\r", '', $data['html']);

                    $retval[$username][] = $data;
                }
            }
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('email_poll(): Failed', $e);
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
        $conversation = sql_get('SELECT `id`, `last_messages` FROM `email_conversations` WHERE ((`to` = :to AND `from` = :from) OR (`to` = :from AND `from` = :to) AND (`subject` = :subject OR `subject` = :resubject))',

                                 array(':to'        => $email['to'],
                                       ':from'      => $email['from'],
                                       ':subject'   => $email['subject'],
                                       ':resubject' => 'RE: '.$email['subject']));

        if(!$conversation){
            /*
             * This is a new conversation
             */
            sql_query('INSERT INTO `email_conversations` (`subject`, `from`, `to`)
                       VALUES                            (:subject , :from , :to )',

                       array(':to'      => $email['to'],
                             ':from'    => $email['from'],
                             ':subject' => $email['subject']));

            $conversation = array('id'            => sql_insert_id(),
                                  'last_messages' => '');
        }

        return $conversation;

    }catch(Exception $e){
        throw new bException('email_get_conversation(): Failed', $e);
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

        if(empty($direction)){
            throw new bException('email_update_conversation(): No conversation direction specified', 'notspecified');
        }

        if(($direction != 'sent') and ($direction != 'received')){
            throw new bException(tr('email_update_conversation(): Invalid conversation direction "%direction%" specified', array('%direction%' => $direction)), 'notspecified');
        }

        if(empty($email['conversation'])){
            throw new bException(tr('email_update_conversation(): Specified email "%subject%" does not contain a conversation', array('%subject%' => $email['subject'])), 'notspecified');
        }

        if(empty($email['id'])){
            throw new bException(tr('email_update_conversation(): Specified email "%subject%" has no database id', array('%subject%' => $email['subject'])), 'notspecified');
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
            $email['text'] = str_replace('%datetime%', system_date_format($email['date']), $_CONFIG['email']['conversations']['message_dates']).$email['text'];
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

        if($direction == 'send'){
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
        throw new bException('email_update_conversation(): Failed', $e);
    }
}



/*
 *
 */
function email_insert_message($email, $direction){
    try{
        $email['conversation'] = email_get_conversation($email);
        $email['reply_to_id']  = email_get_reply_to_id($email);
        $email['users_id']     = email_get_users_id($email);

        switch($direction){
            case 'sent':
                sql_query('INSERT INTO `email_messages` (`direction`, `conversations_id`, `reply_to_id`, `from`, `to`, `users_id`, `date`, `subject`, `text`, `html`)
                           VALUES                       (:direction , :conversations_id , :reply_to_id , :from , :to , :users_id , :date , :subject , :text , :html )',

                           array(':direction'        => $direction,
                                 ':conversations_id' => $email['conversation']['id'],
                                 ':reply_to_id'      => $email['reply_to_id'],
                                 ':from'             => $email['from'],
                                 ':to'               => $email['to'],
                                 ':users_id'         => $email['users_id'],
                                 ':date'             => $email['date'],
                                 ':subject'          => $email['subject'],
                                 ':text'             => $email['text'],
                                 ':html'             => $email['html']));
                break;

            case 'received':
                sql_query('INSERT INTO `email_messages` (`direction`, `conversations_id`, `reply_to_id`, `from`, `to`, `users_id`, `date`, `message_id`, `size`, `uid`, `msgno`, `recent`, `flagged`, `answered`, `deleted`, `seen`, `draft`, `udate`, `subject`, `text`, `html`)
                           VALUES                       (:direction , :conversations_id , :reply_to_id , :from , :to , :users_id , :date , :message_id , :size , :uid , :msgno , :recent , :flagged , :answered , :deleted , :seen , :draft , :udate , :subject , :text , :html )',

                           array(':direction'        => $direction,
                                 ':conversations_id' => $email['conversation']['id'],
                                 ':reply_to_id'      => $email['reply_to_id'],
                                 ':from'             => $email['from'],
                                 ':to'               => $email['to'],
                                 ':users_id'         => $email['users_id'],
                                 ':date'             => $email['date'],
                                 ':message_id'       => $email['message_id'],
                                 ':size'             => $email['size'],
                                 ':uid'              => $email['uid'],
                                 ':msgno'            => $email['msgno'],
                                 ':recent'           => $email['recent'],
                                 ':flagged'          => $email['flagged'],
                                 ':answered'         => $email['answered'],
                                 ':deleted'          => $email['deleted'],
                                 ':seen'             => $email['seen'],
                                 ':draft'            => $email['draft'],
                                 ':udate'            => $email['udate'],
                                 ':subject'          => $email['subject'],
                                 ':text'             => $email['text'],
                                 ':html'             => $email['html']));
                break;

            default:
                throw new bException(tr('email_insert_message(): Unknown direction "%direction%" specified', array('%direction%' => $direction)), 'unknown');
        }

        $email['id'] = sql_insert_id();

        return $email;

    }catch(Exception $e){
        throw new bException('email_insert_message(): Failed', $e);
    }
}



/*
 *
 */
function email_get_reply_to_id($email){
    try{
        return null;

    }catch(Exception $e){
        throw new bException('email_get_reply_to_id(): Failed', $e);
    }
}



/*
 *
 */
function email_get_users_id($email){
    try{
        return null;

    }catch(Exception $e){
        throw new bException('email_get_users_id(): Failed', $e);
    }
}



/*
 * Send a new email
 */
function email_send($email){
    global $_CONFIG;

    try{
        $mail = email_load_phpmailer();
        $data = email_get_user($email['from']);

        $mail->IsSMTP(); // send via SMTP
        $mail->SMTPAuth   = true; // turn on SMTP authentication
        $mail->SMTPSecure = 'tls';
show($email);
showdie($data);

        $mail->Host       = $_CONFIG['email']['smtp']['host'];
        $mail->Port       = $_CONFIG['email']['smtp']['port'];

        $mail->From       = $email['from'];
        $mail->FromName   = $email['from_name'];
        $mail->AddReplyTo($email['from'], $email['from_name']);

        $mail->AddAddress($email['to'], isset_get($email['to_name']));

//        $mail->WordWrap = 50; // set word wrap

        if($data['is_alias']){
            $mail->Username = $data['real']['email'];
            $mail->Password = $data['real']['pass'];

        }else{
            $mail->Username = $data['email'];
            $mail->Password = $data['pass'];
        }

        if(empty($email['html'])){
            $mail->IsHTML(true);
            $mail->Body = $email['html'];

        }else{
            $mail->IsHTML(false);
            $mail->Body = $email['text'];
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
            throw new bException(tr('email_send(): Failed because "%error%"',  array('%error%' => $mail->ErrorInfo)), 'mailfail');
        }

    }catch(Exception $e){
        throw new bException('email_send(): Failed', $e);
    }
}



/*
 *
 */
function email_from_list($subset = null){
    global $_CONFIG;

    try{
        if(empty($_CONFIG['email']['users'])){
            /*
             * Get list from database
             */
//:IMPLEMENT:

        }else{
            switch($subset){
                case 'aliases':
                    $array = array_keys($_CONFIG['email']['aliases']);
                    return array_combine($array, $array);

                case 'users':
                    $array = array_keys($_CONFIG['email']['users']);
                    return array_combine($array, $array);

                case '':
                    $users   = array_keys($_CONFIG['email']['users']);
                    $aliases = array_keys($_CONFIG['email']['aliases']);

                    return array_merge(array_combine($aliases, $aliases), array_combine($users, $users));

                default:
                    throw new bException(tr('Unknown subset "%subset%" specified', array('%subset%' => $subset)), 'unknown');
            }
        }

    }catch(Exception $e){
        throw new bException('email_from_list(): Failed', $e);
    }
}



/*
 *
 */
function email_from_exists($email){
    global $_CONFIG;

    try{
        if(empty($_CONFIG['email']['users'])){
            /*
             * Get list from database
             */
//:IMPLEMENT:

        }else{
            if(!empty($_CONFIG['email']['aliases'][$email])){
                return $_CONFIG['email']['aliases'][$email];
            }

            return !empty($_CONFIG['email']['users'][$email]);
        }

    }catch(Exception $e){
        throw new bException('email_from_exists(): Failed', $e);
    }
}



/*
 * Load the phpmailer library. Auto install if its not available
 */
function email_load_phpmailer(){
    try{
        if(!file_exists(ROOT.'/libs/ext/PHPMailer/PHPMailerAutoload.php')){
            log_console('email_load_phpmailer(): phpmailer not found, installing now', 'install');

            /*
             * Install it first
             */
            load_libs('file');
            $file = file_temp('phpmailer.zip');
            $path = slash(dirname($file));

            file_put_contents($file, fopen('https://github.com/PHPMailer/PHPMailer/archive/master.zip', 'r'));
            safe_exec('cd '.$path.'; unzip '.$file);
            rename($path.'PHPMailer-master/', ROOT.'libs/ext/PHPMailer');
            safe_exec('rm '.$path.' -rf ');
        }

        load_libs('ext/PHPMailer/PHPMailerAutoload');

        return new PHPMailer();

    }catch(Exception $e){
        throw new bException('email_from_exists(): Failed', $e);
    }
}



/*
 * Validates the specified email array and returns correct email data
 */
function email_validate($email){
    try{
        load_libs('validate');
        $v = new validate_form($_POST, 'body,subject,to,from');

        $v->isNotEmpty('to'     , tr('Please specify an email destination'));
        $v->isNotEmpty('from'   , tr('Please specify an email source'));
        $v->isNotEmpty('subject', tr('Please specify an email subject'));
        $v->isNotEmpty('subject', tr('Please write something in the email'));

        if(!email_from_exists($email['from'])){
            $v->setError(tr('Specified source email "%email%" does not exist', array('%email%' => str_log($email['from']))));
        }

        $v->isValid();

        $email['date'] = date('Y-m-d H:i:s');

        if(strpos($email['to'], '<') !== false){
            $email['to_name'] = trim(str_until($email['to'], '<'));
            $email['to']      = trim(str_cut($email['to'], '<', '>'));

        }else{
            $email['to_name'] = '';
        }

        if(strpos($email['from'], '<') !== false){
            $email['from_name'] = trim(str_until($email['to'], '<'));
            $email['from']      = trim(str_cut($email['to'], '<', '>'));

        }else{
            $email['from_name'] = '';
        }

        if(str_is_html($email['body'])){
            $email['html'] = $email['body'];
            $email['text'] = strip_tags($email['body']);

        }else{
            $email['text'] = $email['body'];
        }

        return $email;

    }catch(Exception $e){
        throw new bException('email_validate(): Failed', $e);
    }
}



/*
 * Return userdata for the specified username
 */
function email_get_user($email, $subset = null){
    global $_CONFIG;

    try{
        /*
         * Ensure we have only email address
         */
        if(strpos($email, '<') !== false){
            $email = str_cut($email, '<', '>');
        }

        if(empty($_CONFIG['email']['users'])){
            /*
             * Use database users
             */
//:TODO: Implement

        }else{
            /*
             * Use configured users
             */
            if(!empty($_CONFIG['email']['users'][$email]) and (!$subset or ($subset === 'users'))){
                $user             = $_CONFIG['email']['users'][$email];
                $user['is_alias'] = false;

            }elseif(!empty($_CONFIG['email']['aliases'][$email]) and (!$subset or ($subset === 'aliases'))){
                $user = $_CONFIG['email']['aliases'][$email];

                if(empty($_CONFIG['email']['users'][$user['realmail']])){
                    /*
                     * This alias has a realmail that does not exist
                     */
                    throw new bException(tr('email_get_user(): The email alias "%email%" has a realmail "%realmail%" that does not exist in subset "%subset%"', array('%email%' => $email, '%realmail%' => $user['realmail'], '%subset%' => $subset)), 'invalid');
                }

                $user['is_alias']      = true;
                $user['real']          = $_CONFIG['email']['users'][$user['realmail']];
                $user['real']['email'] = $user['realmail'];

                unset($user['realmail']);

            }else{
                throw new bException(tr('email_get_user(): Specified username "%username%" does not exist in subset "%subset%"', array('%username%' => str_log($email), '%subset%' => $subset)), 'notexist');
            }
        }

        $user['email'] = $email;

        return $user;

    }catch(Exception $e){
        throw new bException(tr('email_get_user(): Failed'), $e);
    }
}
?>