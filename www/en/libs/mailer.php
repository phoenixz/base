<?php
/*
 * Mailer library
 *
 * This library contains mailer functions, like scheduling mailings,
 * starting mailings, stopping mailings, showing access from mailing mails,
 * etc.
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 */



/*
 * Create a new mailing
 */
function mailer_insert($params){
    try{
        array_params ($params);
        array_default($params, 'content', null);
        array_default($params, 'starton', null);
        array_default($params, 'status' , 'stopped');
        array_default($params, 'image'  , 'logo.png');
        array_default($params, 'from'   , null);
        array_default($params, 'to'     , null);


        foreach(array('name', 'subject', 'users') as $key){
            if(empty($params[$key])){
                throw new bException('mailer_insert(): No "'.str_log($key).'" specified');
            }
        }

        if(mailer_get($params, 'id')){
            throw new bException('mailer_insert(): A mailer with the name "'.$params['name'].'" already exists', 'exists');
        }

        if($params['starton'] and ($params['status'] != 'stopped')){
            throw new bException('mailer_insert(): Both starton and start were specified, please specify only one', 'invalid');
        }

        if(empty($params['from_name'])){
            throw new bException('mailer_insert(): No from_name specified', 'not-specified');
        }

        if(empty($params['from_email'])){
            throw new bException('mailer_insert(): No from_email specified', 'not-specified');
        }

        load_libs('json,seo');

        $params['seoname'] = seo_generate_unique_name($params['name'], 'mailer_mailings', null, 'seoname');

        if(empty($params['content'])){
            $params['content'] = $params['seoname'];
        }

        /*
         * Validate from / to
         */
        $params['from'] = array_force($params['from']);
        $params['to']   = array_force($params['to']);

        if(count($params['from']) != count($params['to'])){
            throw new bException('mailer_insert(): The element count specified in "from" ('.count($params['from']).'), does not match the element count specified in "to" ('.count($params['to']).')', 'invalid');
        }

        /*
         * Validate content file
         */
        if(!file_exists(ROOT.'data/content/'.LANGUAGE.'/mailer/template.html')){
            throw new bException('mailer_insert(): Template file "'.ROOT.'data/content/'.LANGUAGE.'/mailer/template.html" does not exist', 'not-exist');
        }

        $params['content'] = cfm($params['content']);

        if(!file_exists($file = ROOT.'data/content/'.LANGUAGE.'/mailer/'.$params['content'].'.html')){
            throw new bException('mailer_insert(): Specified content file "'.$params['content'].'.html'.'" does not exist in email content path "'.ROOT.'data/content/'.LANGUAGE.'/mailer/'.'"', 'not-exist');
        }

        /*
         * Ensure that all markers have been specified
         * If not, load_content() will later exception during mailing
         */
        if(preg_match_all('/(###.+?###)/imus', file_get_contents($file), $matches)){
            /*
             * Most codes are done automatically
             */
            $matches = array_filter_values($matches[0], '###MAILERCODE###,###TRACE###,###TONAME###,###BODY###,###UNSUBSCRIBE###,###DOMAIN###,###SITENAME###,###ENVIRONMENT###,###CODE###,###NAME###,###USERNAME###,###EMAIL###');

            $missing['arguments'] = array_diff($matches, $params['from']);
            $missing['content']   = array_diff($params['from'], $matches);

            if(count($missing['content'])){
                throw new bException('mailer_insert(): Content file contains markers "'.implode(',', $missing['content']).'" that have not been defined in arguments', 'missing');
            }

            //if(count($missing['arguments'])){
            //    throw new bException('mailer_insert(): Arguments contains markers "'.implode(',', $missing['arguments']).'" that have not been defined in content file ', 'missing');
            //}
        }

        $params['to']      = json_encode_custom($params['to']);
        $params['from']    = json_encode_custom($params['from']);
        $params['starton'] = new DateTime($params['starton']);

        sql_query('INSERT INTO `mailer_mailings` (`starton`, `status`, `name`, `seoname`, `subject`, `content_file`, `from`, `to`, `image`, `from_name`, `from_email`)
                   VALUES                        (:starton , :status , :name , :seoname , :subject , :content      , :from , :to , :image , :from_name , :from_email )',

                   array(':status'     => $params['status'],
                         ':starton'    => $params['starton']->format('Y-m-d H:i:s'),
                         ':name'       => $params['name'],
                         ':seoname'    => $params['seoname'],
                         ':subject'    => $params['subject'],
                         ':from'       => $params['from'],
                         ':to'         => $params['to'],
                         ':image'      => $params['image'],
                         ':content'    => $params['content'],
                         ':from_name'  => $params['from_name'],
                         ':from_email' => $params['from_email']));

        $mailings_id = sql_insert_id();

        mailer_add_users($params['users'], $mailings_id);

        return $mailings_id;

    }catch(Exception $e){
        throw new bException('mailer_insert(): Failed', $e);
    }
}



/*
 * Create a new mailing
 */
function mailer_add_users($users, $mailing, $validate_mailing = true){
    try{
        /*
         * Ensure that specified mailing exists.
         */
        if($validate_mailing and is_numeric($mailing)){
            $mailings_id = $mailing;

        }else{
            $mailings_id = mailer_get($mailing, 'id');
        }

        if($mailings_id < 1){
            throw new bException('mailer_add_users(): Specified mailing "'.str_log($mailing).'" does not exist', 'not-exist');
        }

        $count = 0;

        /*
         * Add all users to this mailing?
         */
        if($users === 'all'){
            $r = sql_query('SELECT `id` FROM `users`');

            while($users_id = sql_fetch($r, 'id')){
                $count += mailer_add_users($users_id, $mailings_id, false);
            }

            return $count;
        }

        /*
         * Only add specified users
         */
        foreach(array_force($users) as $users_id){
            $name     = $users_id;
            $users_id = sql_get('SELECT `id`, `mailings`

                                 FROM   `users`

                                 WHERE  `id`       = :id
                                 OR     `name`     = :name
                                 OR     `email`    = :email
                                 OR     `username` = :username',

                                array(':id'       => $users_id,
                                      ':name'     => $users_id,
                                      ':email'    => $users_id,
                                      ':username' => $users_id));

            if(!$users_id){
                log_console('mailer_add_users(): User "'.str_log($name).'" not found', 'yellow');
                continue;
            }

            if(!$users_id['mailings']){
                log_console('mailer_add_users(): User "'.str_log($name).'" does not allow mailings', 'yellow');
                continue;
            }

            $users_id = $users_id['id'];

            /*
             * Only add each user once to each mailing!
             */
            if(!sql_get('SELECT `id` FROM `mailer_recipients` WHERE `mailings_id` = :mailings_id AND `users_id` = :users_id', array(':mailings_id' => $mailings_id, ':users_id' => $users_id), 'id')){

                sql_query('INSERT INTO `mailer_recipients` (`mailings_id`, `users_id`, `code`)
                           VALUES                          (:mailings_id , :users_id , :code )',

                           array(':mailings_id' => $mailings_id,
                                 ':users_id'    => $users_id,
                                 ':code'        => str_random(16)));
                $count++;
            }
        }

        return $count;

    }catch(Exception $e){
        throw new bException('mailer_add_users(): Failed', $e);
    }
}



/*
 * Unsubscribe specified user from all current and future mailings
 * Ensure to block the user also from currently running mailings
 */
function mailer_unsubscribe($user, $validate_user = true){
    try{
        if($validate_user){
            load_libs('user');
            $user = user_get($user, 'id');
        }

        sql_query('UPDATE `users` SET `mailings` = 0 WHERE `id` = :id', array(':id' => $user));

        sql_query('UPDATE `mailer_recipients`

                   SET    `status`    = "blocked",
                          `updatedon` = NOW()

                   WHERE  `users_id`  = :users_id
                   AND    `status` IS NULL', array(':users_id' => $user));

    }catch(Exception $e){
        throw new bException('mailer_unsubscribe(): Failed', $e);
    }
}



/*
 * Return requested data for specified mailer
 */
function mailer_get($params, $columns = false){
    try{
        array_params($params, 'name', 'id');

        foreach(array('id', 'name') as $key){
            if(isset_get($params[$key])){
                $where[]           = '`'.$key.'` = :'.$key;
                $execute[':'.$key] = $params[$key];
            }
        }

        if(empty($where)){
            throw new bException('mailer_get() No valid mailer columns specified (either id, and or name)', 'not-specified');
        }

        return sql_get('SELECT '.($columns ? $columns : '*').'

                        FROM   `mailer_mailings`

                        WHERE  '.implode(' OR ', $where), $columns, $execute);

    }catch(Exception $e){
        throw new bException('mailer_get(): Failed', $e);
    }
}



/*
 * Return an array of all running mailings
 */
function mailer_list($status = null, $columns = '`id`, `name`'){
    try{
        user_or_signin();

        switch($status){
            case '':
                // Fallthrough
            case 'started':
                // Fallthrough
            case 'stopped':
                break;

            default:
                throw new bException('mailer_list(): Unknown status "'.str_log($status).'" specified', 'unknown');
        }

        if(empty($_SESSION['user']['admin'])){
            $where[]             = '`addedby` = :addedby';
            $execute[':addedby'] = $_SESSION['user']['id'];
        }

        if($status){
            $where[]            = ':status = :status';
            $execute[':status'] = $status;
        }

        if(!isset($where)){
            $where   = ' WHERE '.implode(',', $where);

        }else{
            $where   = '';
            $execute = null;
        }

        return sql_list('SELECT '.$columns.' FROM `mailer_mailings`'.$where, $execute);

    }catch(Exception $e){
        throw new bException('mailer_list(): Failed', $e);
    }
}



/*
 * Try to send mails from any running mailing
 */
function mailer_send($count = null, $wait = null, $test = false){
    global $_CONFIG;

    try{
        $sent = 0;

        if(!$count){
            $count = $_CONFIG['mailer']['sender']['count'];
        }

        if(!$wait){
            $wait = $_CONFIG['mailer']['sender']['wait'];
        }

        load_libs('mail,json,user');

        /*
         * Get list of currently running mailings
         */
        $r = sql_query('SELECT `id`,
                               `subject`,
                               `content_file`,
                               `from`,
                               `to`,
                               `from_email`,
                               `from_name`

                        FROM   `mailer_mailings`

                        WHERE  `status` = "started"');

        while($mailing = sql_fetch($r)){
            try{
                $mailing['to']   = json_decode_custom($mailing['to']);
                $mailing['from'] = json_decode_custom($mailing['from']);
$mailing['language'] = 'en';

                $mailing['from'][] = '###CODE###';
                $mailing['from'][] = '###NAME###';
                $mailing['from'][] = '###USERNAME###';
                $mailing['from'][] = '###EMAIL###';
                //$mailing['from'][] = '###CITY###';
                //$mailing['from'][] = '###STATE###';
                //$mailing['from'][] = '###COUNTRY###';

                /*
                 * Prepare template
                 */
                $total_recipients = sql_get('SELECT COUNT(`id`) AS count

                                             FROM   `mailer_recipients`

                                             WHERE  `mailer_recipients`.`mailings_id` = '.$mailing['id'].'
                                             AND    `mailer_recipients`.`status`      IS NULL', 'count');

                if($total_recipients <= 0){
                    /*
                     * This mailing has no (more) recipients. Mark it as finished
                     */
                    sql_query('UPDATE `mailer_mailings`

                               SET    `status`     = "finished",
                                      `updatedon`  = NOW(),
                                      `finishedon` = NOW()

                               WHERE  `id`         = '.$mailing['id']);

                    continue;
                }

                $recipients = sql_list('SELECT `mailer_recipients`.`id`,
                                               `mailer_recipients`.`code`,
                                               `users`.`username`,
                                               `users`.`name`,
                                               `users`.`email`

                                        FROM   `mailer_recipients`

                                        JOIN   `users`
                                        ON     `mailer_recipients`.`users_id`    = `users`.`id`
                                        AND    `mailer_recipients`.`status`      IS NULL

                                        WHERE  `mailer_recipients`.`mailings_id` = :mailings_id LIMIT '.cfi($count),

                                        array(':mailings_id' => $mailing['id']));

                foreach($recipients as $recipients_id => $recipient){
                    try{
                        $mailing['to'][] = $recipient['code'];
                        $mailing['to'][] = name($recipient);
                        $mailing['to'][] = $recipient['username'];
                        $mailing['to'][] = $recipient['email'];

                        /*
                         * Get the email body with the code embedded
                         */
                        $body = load_content('mailer/'.$mailing['content_file'], $mailing['from'], $mailing['to'], $mailing['language'], false, false);

                        /*
                         * Send the email
                         */
                        $mail = array('mailer_code' => str_random(16),
                                      'to_name'     => name($recipient),
                                      'to_email'    => $recipient['email'],
                                      'from_name'   => $mailing['from_name'],
                                      'from_email'  => $mailing['from_email']);

                        if(!$test){
                            mail_send_templated_email($mail, $mailing['subject'], $body, $mailing['language'], 'mailer/template');
                        }

                        $sent++;

                        /*
                         * Mark this recipient as being sent a mail
                         */
                        sql_query('UPDATE `mailer_recipients`
                                   SET    `status`    = "sent",
                                          `updatedon` = NOW(),
                                          `senton`    = NOW(),
                                          `code`      = :code
                                   WHERE  `id`        = :recipients_id',

                                   array(':recipients_id' => $recipients_id,
                                         ':code'          => $mail['mailer_code']));

                        if(--$total_recipients <= 0){
                            /*
                             * This mailing has no more recipients. Mark it as finished
                             */
                            sql_query('UPDATE `mailer_mailings`
                                       SET    `status`     = "finished",
                                              `updatedon`  = NOW(),
                                              `finishedon` = NOW()
                                       WHERE  `id`         = '.$mailing['id']);
                        }

                        if($count and --$count <= 0){
                            /*
                             * We've reached maximum amount of mails to send, stop now
                             */
                            break;
                        }

                        if($wait){
                            /*
                             * Wait between sending each mail
                             */
                            usleep($wait);
                        }

                    }catch(Exception $e){
                        if($e->getCode() == 'missingmarkers'){
                            /*
                             * This mailing is missing markers, and should be canceled all together
                             */
                            throw($e);
                        }

                        /*
                         * Mark this recipient as having failed
                         */
                        log_console('Mailer recipient "'.$mailing['id'].'" of mailing "'.$mailing['id'].'" failed with "'.$e->getMessage().'"', 'yellow');

                        sql_query('UPDATE `mailer_recipients`
                                   SET    `status`    = "failed",
                                          `updatedon` = NOW()
                                   WHERE  `id`        = :id', array(':id' => $recipients_id));
                    }
                }

            }catch(Exception $e){
                /*
                 * Mark this recipient as having failed
                 */
                log_console('Mailer "'.$mailing['id'].'" failed with "'.$e->getMessage().'"', 'yellow');

                sql_query('UPDATE `mailer_mailings`
                           SET    `status`    = "failed",
                                  `updatedon` = NOW()
                           WHERE  `id`        = :id', array(':id' => $mailing['id']
//                                  `info`      = :info,
//                                                            ':info' => ''
                                                            ));
            }
        }

        return $sent;

    }catch(Exception $e){
        throw new bException('mailer_insert(): Failed', $e);
    }
}



/*
 * A mailing email has been viewed
 * This basically means that somebody accessed some content (this would
 * usually be an image) that contains a mailing code, so that we know the
 * email has been viewed
 */
function mailer_get_recipient($code, $status = null){
    try{
        if($status){
            $execute = array(':code' => $code, ':status' => $status);

        }else{
            $execute = array(':code' => $code);
        }

        return sql_get('SELECT `mailer_recipients`.`id`, `mailer_recipients`.`users_id`, `mailer_mailings`.`image`

                        FROM   `mailer_recipients`

                        JOIN   `mailer_mailings`
                        ON     `mailer_mailings`.`id`       = `mailer_recipients`.`mailings_id`

                        WHERE  `mailer_recipients`.`code`   = :code '.
                        ($status ? 'AND    `mailer_recipients`.`status` = :status' : ''), $execute);

    }catch(Exception $e){
        throw new bException('mailer_get_recipient(): Failed', $e);
    }
}



/*
 * A mailing email has been viewed
 * This basically means that somebody accessed some content (this would
 * usually be an image) that contains a mailing code, so that we know the
 * email has been viewed
 */
function mailer_viewed($code){
    try{
        $recipient = mailer_get_recipient($code, 'sent');

        if(!$recipient){
            throw new bException('mailer_viewed(): Specified code "'.str_log($code).'" does not exist', 'not-exist');
        }

        sql_query('INSERT INTO `mailer_views` (`recipients_id`, `ip`, `host`, `referrer`)
                   VALUES                     (:recipients_id , :ip , :host , :referrer )',

                   array(':recipients_id' => $recipient['id'],
                         ':ip'            => ip2long($_SERVER['REMOTE_ADDR']),
                         ':host'          => isset_get($_SERVER['REMOTE_HOST']),
                         ':referrer'      => isset_get($_SERVER['HTTP_REFERER'])));

        return $recipient['image'];

    }catch(Exception $e){
        throw new bException('mailer_viewed(): Failed for code "'.str_log($code).'"', $e);
    }
}



/*
 * Start specified mailers
 */
function mailer_start($mailers){
    try{
        return mailer_status($mailers, 'started');

    }catch(Exception $e){
        throw new bException('mailer_start(): Failed', $e);
    }
}



/*
 * Stop specified mailers
 */
function mailer_stop($mailers){
    try{
        return mailer_status($mailers, 'stopped');

    }catch(Exception $e){
        throw new bException('mailer_stop(): Failed', $e);
    }
}



/*
 * Delete specified mailers
 */
function mailer_delete($mailers){
    try{
        return mailer_status($mailers, 'deleted');

    }catch(Exception $e){
        throw new bException('mailer_delete(): Failed', $e);
    }
}



/*
 * Cancel specified mailers
 */
function mailer_cancel($mailers){
    try{
        return mailer_status($mailers, 'canceled');

    }catch(Exception $e){
        throw new bException('mailer_cancel(): Failed', $e);
    }
}



/*
 * Set specified status for specified mailers
 */
function mailer_status($mailers, $status){
    try{
        switch($status){
            case 'started':
                // FALLTHROUGH
            case 'stopped':
                $execute = array(':status' => $status);
                break;

            case 'deleted':
                break;

            case 'finished':
                throw new bException('mailer_status(): Invalid status "'.str_log($status).'" specified, this status cannot be set', 'invalid');

            default:
                throw new bException('mailer_status(): Unknown status "'.str_log($status).'" specified', 'unknown');
        }

        switch(str_force($mailers)){
            case 'all':
                /*
                 * Update all status "started" to "stopped
                 */
                switch($status){
                    case 'started':
                        /*
                         * Dont start mailers that are already started
                         * (this would update the `updatedon` column)
                         */
                        $where = ' AND `status` != "started"';
                        break;

                    case 'stopped':
                        /*
                         * Only stop mailers that are started
                         */
                        $where = ' AND `status` = "started"';
                        break;

                    case 'deleted':
                        throw new bException('mailer_status(): Cannot delete all mailers!', 'invalid');
                }

                break;

            case 'auto':
                /*
                 * "auto" mailers only works with starting mailers
                 * Automatically start all mailers that are programmed todo so.
                 */
                if($status != 'started'){
                    throw new bException('mailer_status(): "auto" mailers can only be used in combination with status "started"', 'invalid');
                }

                $where = ' AND `starton` < NOW() AND `status` != "started"';
                break;

            default:
                /*
                 * Set status for specific mailers
                 */
                $count   = 0;
                $mailers = array_force($mailers);

                if(empty($mailers)){
                    throw new bException('mailer_status(): No mailer names specified');
                }

                foreach($mailers as $mailer){
                    if(empty($column)){
                        if(is_numeric($mailer)){
                            $column = 'id';

                        }else{
                            $column = 'name';
                        }
                    }

                    $execute[':mailer'.$count++] = $mailer;
                }

                if($status == 'deleted'){
                    return sql_query('DELETE FROM `mailer_mailings` WHERE `'.$column.'` IN ('.implode(',', array_keys($execute)).')', $execute)->rowCount();
                }

                $where = ' AND `'.$column.'` IN ('.implode(',', array_keys($execute)).')';
        }

        return sql_query('UPDATE `mailer_mailings`

                          SET    `updatedon` = NOW(),'.
                                 (($status == 'started') ? '`startedon` = NOW(),' : '').
                                '`status`    = :status

                          WHERE  `status`   != "finished"'.$where, $execute)->rowCount();

    }catch(Exception $e){
        throw new bException('mailer_status(): Failed', $e);
    }
}



/*
 * Returns HTML <img> tag pointing towards an image with the specified code
 */
function mailer_access_image($code, $alt = 'Logo'){
    return html_img(domain('/logoimgs/'.$code.'.jpg'), $alt);
}



/*
 * Return the amount of recipients with the specified status (or status "all", so no status) for the specified mailing
 */
function mailer_get_recipientcount($mailings_id, $status = 'all'){
    try{
        /*
         * Always filter on mailings_id
         */
        $where   = '`mailings_id` = :mailings_id';
        $execute = array(':mailings_id' => $mailings_id);

        /*
         * Validate status
         */
        switch($status){
            case 'all':
                break;

            case '':
                $status = null;
                // FALLTHROUGH
            case 'sent':
                // FALLTHROUGH
            case 'failed':
                $where .= ' AND `status` = :status';
                $execute[':status'] = $status;
                break;

            default:
                throw new bException('mailer_get_recipientcount(): Unknown status "'.str_log($status).'" specified', 'unknown');
        }

        return sql_get('SELECT COUNT(`id`) AS count

                        FROM   `mailer_recipients`

                        WHERE  '.$where,

                       'count',

                       $execute);

    }catch(Exception $e){
        throw new bException('mailer_get_usercount(): Failed', $e);
    }
}
?>
