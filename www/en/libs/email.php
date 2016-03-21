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
    throw new bException(tr('php module "imap" appears not to be installed. Please install the module first. On Ubuntu and alikes, use "sudo apt-get -y install php5-imap; sudo php5enmod imap" to install and enable the module., on Redhat and alikes use ""sudo yum -y install php5-imap" to install the module. After this, a restart of your webserver or php-fpm server might be needed'), 'not_available');
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
        throw new bException(tr('email_connect(): Failed'), $e);
    }
}



/*
 * Poll for new emails
 */
function email_poll($usernames, $criteria = 'ALL'){
    try{
        $retval = array();

        foreach(array_force($usernames) as $username){
            try{
                log_console(tr('Polling email account "%account%"', array('%account%' => $username)), '');

                $inbox  = email_connect($username);
                $emails = imap_search($inbox, $criteria);

                $retval[$username] = array();

                if($emails){
                    rsort($emails);

                    /*
                     * Process every email
                     */
                    foreach($emails as $email) {
                        /*
                         * Get information specific to this email
                         */
                        $data = imap_fetch_overview($inbox, $email, 0);
                        $data = array_shift($data);
                        $data = array_from_object($data);

                        $data['text'] = imap_fetchbody($inbox, $email, 1.1);
                        $data['html'] = imap_fetchbody($inbox, $email, 1.2);

                        /*
                         * Get the images of the email
                         */
                        $data = email_get_images($inbox, $email, $data);

                        if(!$data['text']){
                            $data['text'] = imap_fetchbody($inbox, $email, 1);
                        }

                        $data['text'] = trim(imap_qprint($data['text']));
                        $data['text'] = str_replace("\r", '', $data['text']);
                        $data['html'] = trim(imap_qprint($data['html']));
                        $data['html'] = str_replace("\r", '', $data['html']);

                        $retval[$username][] = $data;
                        usleep(20000);
                    }

                    log_console(tr('Got "%count%" new mails for account "%account%"', array('%count%' => count($emails), '%account%' => $username)), '', 'purple');
                }

            }catch(bException $e){
                log_error(tr('Failed to poll email data for user "%user%"', array('%user%' => $username)));
            }
        }

        return $retval;

    }catch(Exception $e){
        throw new bException(tr('email_poll(): Failed'), $e);
    }
}



/*
 *
 */
function email_get_images($inbox, $email, $data){
    load_libs('file,image');

    try{
        /*
         * Extract the images of the emails if there are
         */
        $decode  = imap_fetchbody($inbox, $email , '');
        $num_img = substr_count($decode, "Content-Transfer-Encoding: base64");

        if($num_img > 0){
            $structure = imap_fetchstructure($inbox, $email);

            /*
             * Loop through each image
             */
            for($i = 0; $i < $num_img; $i++){
                try{
                    $section   = strval(2+$i);
                    $decode    = imap_fetchbody($inbox, $email, $section);
                    $img       = base64_decode($decode);

                    /*
                     * Get image type
                     */
                    $f         = finfo_open();
                    $mime_type = finfo_buffer($f, $img, FILEINFO_MIME_TYPE);

                    switch(str_from($mime_type, '/')){
                        case 'jpeg':
                            $extension = '.jpg';
                            break;

                        case 'png':
                            $extension = '.png';
                            break;

                        case 'gif':
                            $extension = '.gif';
                            break;

                        default:
                            /*
                             * This is not an image or not a valid format
                             */
                            throw new bException(tr('email_get_images(): Format ":format" not valid', array(':format' => str_from($mime_type, '/'))));
                    }

                    $file_name = time()."_".$i.$extension;

                    if(!empty($structure->parts[$i+1]->id)){
                        /*
                         * This is an inline image
                         */
                        $data['img'.$i]['cid']  = rtrim(trim($structure->parts[$i+1]->id, '<'), ">");

                    }else{
                        /*
                         * Attachment
                         */
                        $data['img'.$i]['cid']  = 'attachment';
                    }

                    $data['img'.$i]['file'] = $file_name;
                    file_put_contents(ROOT.'tmp/'.$file_name, $img);

                }catch(Exception $e){
                    /*
                     * An image failed, just continue
                     */
                    log_database(tr('Failed to process an image'), 'error');
                    continue;
                }
            }
        }

        return $data;

    }catch(Exception $e){
        throw new bException(tr('email_get_images(): Failed'), $e);
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

        $conversation = sql_get('SELECT `id`, `last_messages` FROM `email_conversations` WHERE ((`us` LIKE :us AND `them` LIKE :them) OR (`us` LIKE :them AND `them` LIKE :us) AND (`subject` = :subject OR `subject` = :resubject))',

                                 array(':us'        => '%'.$email['to'].'%',
                                       ':them'      => '%'.$email['from'].'%',
                                       ':subject'   => str_from($email['subject'], 'RE: '),
                                       ':resubject' => 'RE: '.$email['subject']));

        if(!$conversation){
            /*
             * This is a new conversation
             */
            sql_query('INSERT INTO `email_conversations` (`subject`, `them`, `us`)
                       VALUES                            (:subject , :them , :us )',

                       array(':us'      => $email['to'],
                             ':them'    => $email['from'],
                             ':subject' => $email['subject']));

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
            throw new bException(tr('email_update_conversation(): No conversation direction specified'), 'notspecified');
        }

        if(($direction != 'sent') and ($direction != 'received')){
            throw new bException(tr('email_update_conversation(): Invalid conversation direction ":direction:" specified', array(':direction' => $direction)), 'notspecified');
        }

        if(empty($email['conversation'])){
            throw new bException(tr('email_update_conversation(): Specified email ":subject" does not contain a conversation', array(':subject' => $email['subject'])), 'notspecified');
        }

        if(empty($email['id'])){
            throw new bException(tr('email_update_conversation(): Specified email ":subject" has no database id', array(':subject' => $email['subject'])), 'notspecified');
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
        throw new bException(tr('email_update_conversation(): Failed'), $e);
    }
}



/*
 *
 */
function email_update_message($email, $direction){
    try{
        $email['conversation'] = email_get_conversation($email);
        $email['reply_to_id']  = email_get_reply_to_id($email);
        $email['users_id']     = email_get_users_id($email);

        if(!empty($email['id'])){
            sql_query('UPDATE `email_messages`

                       SET    `direction`        = :direction,
                              `conversations_id` = :conversations_id,
                              `reply_to_id`      = :reply_to_id,
                              `from`             = :from,
                              `to`               = :to,
                              `users_id`         = :users_id,
                              `date`             = :date,
                              `subject`          = :subject,
                              `text`             = :text,
                              `html`             = :html,
                              `sent`             = :sent

                       WHERE  `id`               = :id',

                       array(':id'               => $email['id'],
                             ':direction'        => $direction,
                             ':conversations_id' => $email['conversation']['id'],
                             ':reply_to_id'      => $email['reply_to_id'],
                             ':from'             => $email['from'],
                             ':to'               => $email['to'],
                             ':users_id'         => $email['users_id'],
                             ':date'             => $email['date'],
                             ':subject'          => $email['subject'],
                             ':text'             => $email['text'],
                             ':sent'             => system_date_format($email['sent'], 'mysql')));

        }else{
            switch($direction){
                case 'sent':
                    sql_query('INSERT INTO `email_messages` (`direction`, `conversations_id`, `reply_to_id`, `from`, `to`, `users_id`, `date`, `subject`, `text`, `html`, `sent`                             )
                               VALUES                       (:direction , :conversations_id , :reply_to_id , :from , :to , :users_id , :date , :subject , :text , :html , '.($email['sent'] ? 'NOW()' : '').')',

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
                    throw new bException(tr('email_update_message(): Unknown direction "%direction%" specified', array('%direction%' => $direction)), 'unknown');
            }

            $email['id'] = sql_insert_id();
            email_check_images($email);
        }

        return $email;

    }catch(Exception $e){
        throw new bException(tr('email_update_message(): Failed'), $e);
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
        if(!empty($email['img0'])){
            $i = 0;
            file_ensure_path(ROOT.'data/email/images/'.$email['id'].'/');

            while(!empty($email['img'.$i])){
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
                rename(ROOT.'tmp/'.$email['img'.$i]['file'], ROOT.'data/email/images/'.$email['id'].'/'.$email['img'.$i]['file']);
                $i++;
            }
        }

    }catch(Exception $e){
        throw new bException(tr('email_check_images(): Failed'), $e);
    }
}



/*
 *
 */
function email_get_reply_to_id($email){
    try{
        return null;

    }catch(Exception $e){
        throw new bException(tr('email_get_reply_to_id(): Failed'), $e);
    }
}



/*
 *
 */
function email_get_users_id($email){
    try{
        return null;

    }catch(Exception $e){
        throw new bException(tr('email_get_users_id(): Failed'), $e);
    }
}



/*
 * Send a new email
 */
function email_send($params){
    global $_CONFIG;

    try{
        array_params($params);
        array_default($params, 'delayed'     , null);
        array_default($params, 'replace'     , true);
        array_default($params, 'conversation', true);

        /*
         * Add custom email header and footer
         */
        load_libs('user');

        if($params['replace']){
            $params['replace'] = array('%user%'   => user_name($_SESSION['user']),
                                       '%email%'  => isset_get($_SESSION['user']['email']),
                                       '%domain%' => domain());

            $header            = str_replace(array_keys($params['replace']), array_values($params['replace']), $_CONFIG['email']['header']);
            $footer            = str_replace(array_keys($params['replace']), array_values($params['replace']), $_CONFIG['email']['footer']);

            $params['text']    = $header.$params['text'].$footer;
        }

        if($params['delayed'] === null){
            /*
             *
             */
        }

        if($params['delayed']){
            /*
             * Don't send the email right now
             */
            $params['sent'] = null;

        }else{
            /*
             * Send the email right now
             */
            $mail    = email_load_phpmailer();
            $account = email_get_user($params['from']);

            $mail->IsSMTP();        // send via SMTP

            if(empty($params['smtp_host'])){
                /*
                 * Use the default SMTP configuration
                 */
                $mail->Host     = $_CONFIG['email']['smtp']['host'];
                $mail->Port     = $_CONFIG['email']['smtp']['port'];
                $mail->SMTPAuth = $_CONFIG['email']['smtp']['auth'];

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
                        throw new bException(tr('email_send(): Unknown global SMTP secure setting "%value%" for host "%host%". Use either false, "tls", or "ssl"', array('%value%' => $_CONFIG['email']['smtp']['secure'], '%host%' => $_CONFIG['email']['smtp']['host'])), 'unknow');
                }

            }else{
                /*
                 * Use user specific SMTP configuration
                 */
                $mail->Host     = $params['smtp_host'];
                $mail->Port     = isset_get($params['smtp_port']);
                $mail->SMTPAuth = $params['smtp_auth'];

                switch(isset_get($params['smtp_secure'])){
                    case '':
                        /*
                         * Don't use secure connection
                         */
                        break;

                    case 'ssl':
                        //FALLTHROUGH
                    case 'tls':
                        $mail->SMTPSecure = $params['smtp_secure'];
                        break;

                    default:
                        throw new bException(tr('email_send(): Unknown user specific SMTP secure setting "%value%" for host "%host%". Use either false, "tls", or "ssl"', array('%value%' => $params['smtp_secure'], '%host%' => $_CONFIG['email']['smtp']['host'])), 'unknow');
                }
            }

            $mail->From       = $params['from'];
            $mail->FromName   = $params['from_name'];
            $mail->AddReplyTo($params['from'], $params['from_name']);

            $mail->AddAddress($params['to'], isset_get($params['to_name']));

    //        $mail->WordWrap = 50; // set word wrap

            if($account['is_alias']){
                $mail->Username = $account['real']['email'];
                $mail->Password = $account['real']['pass'];

            }else{
                $mail->Username = $account['email'];
                $mail->Password = $account['pass'];
            }

            if(empty($params['html'])){
                $mail->IsHTML(false);
                $mail->Body = $params['text'];

            }else{
                $mail->IsHTML(true);
                $mail->Body = $params['html'];
            }

            $mail->Subject = $params['subject'];
            $mail->AltBody = $params['text'];

            if(!empty($params['attachments'])){
                foreach(array_force($params['attachments']) as $attachment){
    // :IMPLEMENT:
                //$mail->AddAttachment("/var/tmp/file.tar.gz"); // attachment
                //$mail->AddAttachment("/tmp/image.jpg", "new.jpg"); // attachment
                }
            }

            if(!$mail->Send()){
                throw new bException(tr('email_send(): Failed because "%error%"',  array('%error%' => $mail->ErrorInfo)), 'mailfail');
            }

            $params['sent'] = system_date_format(null, 'mysql');
        }

        if($params['conversation']){
            email_update_conversation($params, 'sent');
        }

    }catch(Exception $e){
        throw new bException(tr('email_send(): Failed'), $e);
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
        throw new bException(tr('email_from_list(): Failed'), $e);
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
        throw new bException(tr('email_from_exists(): Failed'), $e);
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
        throw new bException(tr('email_from_exists(): Failed'), $e);
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

        $email = email_prepare($email);

        return $email;

    }catch(Exception $e){
        throw new bException(tr('email_validate(): Failed'), $e);
    }
}



/*
 * Prepare the email with "date","from" and "to"
 */
function email_prepare($email){
    try{
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
        throw new bException(tr('email_prepare(): Failed'), $e);
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



/*
 * OBSOLETE
 *
 * These function wrappers only exist for compatibility, and may disappear at any time
 */
function email_insert_message($email, $direction){
    return email_update_message($email, $direction);
}



/*
 * This function inserts new emails on DB
 */
function email_insert($params){
    global $_CONFIG;

    try{
        array_params($params);
        array_default($params, 'template', $_CONFIG['email']['templates']['default']);
        array_default($params, 'subject' , $_CONFIG['email']['subject']);
        array_default($params, 'from'    , $_CONFIG['email']['from']);
        array_default($params, 'to'      , '');
        array_default($params, 'body'    , '');
        array_default($params, 'format'  , 'text');

        /*
         * Validations
         */
        if(empty($params['to'])){
            throw new bException(tr('email_insert(): No email receiver specified'));
        }

        if(empty($_CONFIG['email']['users'][$params['from']])){
            throw new bException(tr('email_insert(): Unkown sender "%sender%" specified', array('%sender%' => $params['from'])), 'unkown');
        }

        /*
         * Which format are we using?
         */
        if($params['format'] == 'html'){
            /*
             * Ensure that the specified type exists on configuration
             */
            if(empty($_CONFIG['email']['templates'][$params['template']])){
                throw new bException(tr('email_insert(): Unkown template "%template%"', array('%template%' => str_log($params['template']))), 'unkown');
            }

        }else{
            $params['template'] = '';
        }

        /*
         * Get user info
         */
        $user = sql_get('SELECT `id`,
                                `name`,
                                `username`,
                                `email`

                         FROM   `users`

                         WHERE  `email` = :email',

                         array(':email' => $params['to']));

        if(!$user){
            throw new bException(tr('email_insert(): Specified user "%user%" does not exist', array('%user%' => $params['to'])), 'notexist');
        }

        /*
         * Store the email on DB with the `status` = "new"
         */
        sql_query('INSERT INTO `emails` (`createdby`, `users_id`, `status`, `template`, `subject`, `from`, `to`, `body`, `format`)
                   VALUES               (:createdby , :users_id , "new"   , :template , :subject , :from , :to , :body , :format )',

                   array(':createdby' => $user['id'],
                         ':users_id'  => $user['id'],
                         ':template'  => $params['template'],
                         ':subject'   => $params['subject'],
                         ':from'      => $params['from'],
                         ':to'        => $params['to'],
                         ':body'      => $params['body'],
                         ':format'    => $params['format']));

        /*
         * Run the script to send the "new" emails
         */
        run_background('base/email -e '.ENVIRONMENT.' method send all', true, true);

    }catch(Exception $e){
        throw new bException(tr('email_insert(): Failed'), $e);
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
        $r = sql_query('SELECT    `emails`.`id`,
                                  `emails`.`status`,
                                  `emails`.`template`,
                                  `emails`.`subject`,
                                  `emails`.`from`,
                                  `emails`.`to`,
                                  `emails`.`body`,
                                  `emails`.`format`,
                                  `emails`.`users_id`,
                                  `users`.`name`     AS `user_name`,
                                  `users`.`username` AS `user_username`

                        FROM      `emails`
                        LEFT JOIN `users`
                        ON        `emails`.`users_id` = `users`.`id`

                        WHERE     `emails`.`status`   = "new"

                        LIMIT     20');

        /*
         * Prepare to send each email and then
         * update the `status` to "sent" and also update the `senton` date
         */
        while($email = sql_fetch($r)){
            /*
             * Build the body according to the format
             */
            if($email['format'] == 'html'){
                /*
                 * For html format, load the specified template
                 */
                $from           = array('###TONAME###' => (empty($email['user_name']) ? $email['user_username'] : $email['user_name']),
                                        '###BODY###'   => $email['body'],
                                        '###EMAIL###'  => $email['to'],
                                        '###DOMAIN###' => $_CONFIG['domain']);

                $email['body']  = load_content($_CONFIG['email']['templates'][$email['template']]['file'], $from, LANGUAGE);
            }

            /*
             * Prepare the email and return it on params variable
             */
            $params = email_prepare($email);

            /*
             * Set options
             */
            $params['conversation'] = false;

            if($email['format'] == 'html'){
                $params['replace'] = false;
            }

            try{
                /*
                 * Send the email
                 */
                email_send($params);

            }catch(Exception $e){
                /*
                 * Error ocurred ! ... Notify and continue sending emails
                 */
                log_database(tr('Failed to send email to user %user%', array('%user%' => $params['to'])), 'error');
                continue;

            }

            /*
             * The mail was sent, update the `status` and `senton`
             */
            sql_query('UPDATE `emails`

                       SET    `status` = "sent",
                              `senton` = NOW()

                       WHERE  `id`     = :id',

                       array(':id' => $email['id']));


            /*
             * Delay the process by 0.5 seconds
             */
            usleep(500);
        }

    }catch(Exception $e){
        throw new bException(tr('email_send_unsent(): Failed'), $e);
    }
}
?>
