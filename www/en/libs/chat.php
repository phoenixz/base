<?php
/*
 * Chat library
 *
 * This is a library to interface with the boom chat plugin
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */

load_config('chat');

/*
 * Add the specified user to the chat system
 *
| user_id          | int(11)      | NO   | PRI | NULL                    | auto_increment |
| user_name        | varchar(16)  | NO   |     | NULL                    |                |
| user_password    | varchar(60)  | NO   |     | NULL                    |                |
| user_email       | varchar(80)  | NO   |     | NULL                    |                |
| user_ip          | varchar(30)  | NO   |     | NULL                    |                |
| user_join        | int(12)      | NO   |     | NULL                    |                |
| last_action      | int(11)      | NO   |     | NULL                    |                |
| last_message     | varchar(500) | NO   |     | NULL                    |                |
| user_status      | int(1)       | NO   |     | 1                       |                |
| user_action      | int(1)       | NO   |     | 1                       |                |
| user_color       | varchar(10)  | NO   |     | user                    |                |
| user_rank        | int(1)       | NO   |     | 1                       |                |
| user_access      | int(1)       | NO   |     | 4                       |                |
| user_roomid      | int(6)       | NO   |     | 1                       |                |
| user_kick        | text         | NO   |     | NULL                    |                |
| user_mute        | varchar(16)  | NO   |     | NULL                    |                |
| mute_time        | int(12)      | NO   |     | NULL                    |                |
| user_flood       | int(1)       | NO   |     | NULL                    |                |
| user_theme       | varchar(16)  | NO   |     | Default                 |                |
| user_sex         | int(1)       | NO   |     | 0                       |                |
| user_age         | int(2)       | NO   |     | 0                       |                |
| user_description | text         | NO   |     | NULL                    |                |
| user_avatar      | varchar(50)  | NO   |     | default_avatar.png      |                |
| alt_name         | varchar(100) | NO   |     | NULL                    |                |
| upload_count     | int(11)      | NO   |     | 0                       |                |
| upload_access    | int(11)      | NO   |     | 1                       |                |
| user_sound       | int(1)       | NO   |     | 1                       |                |
| temp_pass        | varchar(40)  | NO   |     | 0                       |                |
| temp_time        | int(11)      | NO   |     | 0                       |                |
| user_tumb        | varchar(100) | NO   |     | default_avatar_tumb.png |                |
| guest            | int(1)       | NO   |     | 0                       |                |
| verified         | int(1)       | NO   |     | 1                       |                |
| valid_key        | varchar(64)  | NO   |     | NULL                    |                |
| user_ignore      | text         | NO   |     | NULL                    |                |
| first_check      | int(11)      | NO   |     | 0                       |                |
| join_chat        | int(11)      | NO   |     | 0                       |                |
| email_count      | int(1)       | NO   |     | 0                       |                |
| user_friends     | text         | NO   |     | NULL                    |                |
 */
function chat_start($user){
    global $_CONFIG;

    try{
        $user = sql_get('SELECT `user_name`, `user_password` FROM `users` WHERE `user_id` = :user_id', array(':user_id' => $user['id']), null, 'chat');

        setcookie('username', $user['user_name']    , time() + 86400, '/', ''.str_starts($_CONFIG['domain'], '.'));
        setcookie('password', $user['user_password'], time() + 86400, '/', ''.str_starts($_CONFIG['domain'], '.'));

        return '<iframe src="'.$_CONFIG['protocol'].'chat.'.$_CONFIG['domain'].'" frameborder="0" class="chat"></iframe>';

    }catch(Exception $e){
        throw new bException(tr('chat_start(): Failed'), $e);
    }
}



/*
 *
 */
function chat_end($userid){
    try{
        sql_query('UPDATE `users`

                   SET    `user_status` = :user_status

                   WHERE  `user_id`     = :userid',

                   array(':user_status' => 3,
                         ':userid'      => $userid),

                   null, 'chat');

    }catch(Exception $e){
        throw new bException(tr('chat_end(): Failed'), $e);
    }
}



/*
 *
 */
function chat_add_user($user){
    try{
        sql_query('INSERT INTO `users` (`user_id`, `user_name`, `user_email`, `user_password`, `alt_name`, `user_join`)
                   VALUES              (:user_id , :user_name , :user_email , :user_password , :alt_name , NOW()      )',

                   array(':user_id'       => $user['id'],
                         ':user_name'     => (empty($user['username']) ? $user['email'] : $user['username']),
                         ':alt_name'      => isset_get($user['name'], ''),
                         ':user_email'    => $user['email'],
                         ':user_password' => unique_code()),

                   null, 'chat');

        return sql_insert_id('chat');

    }catch(Exception $e){
        throw new bException(tr('chat_add_user(): Failed'), $e);
    }
}



/*
 *
 */
function chat_update_user($user){
    try{
        if(has_rights('admin', $user)){
            $rank = 5;

        }elseif(has_rights('moderator', $user)){
            $rank = 3;

        }else{
            $rank = 1;
        }

        $r = sql_query('UPDATE `users`

                        SET    `user_name`  = :user_name,
                               `user_email` = :user_email,
                               `alt_name`   = :alt_name,
                               `user_rank`  = :user_rank

                        WHERE  `user_id`    = :user_id',

                        array(':user_id'    => $user['id'],
                              ':user_name'  => (empty($user['username']) ? $user['email'] : $user['username']),
                              ':alt_name'   => isset_get($user['name'], ''),
                              ':user_email' => $user['email'],
                              ':user_rank'  => $rank),

                        null, 'chat');

        if(!$r->rowCount()){
            /*
             * This means either no data has been changed, or the specified ID doesn't exist.
             * The former is okay, the latter should never happen.
             */
            if(!sql_get('SELECT `user_id` FROM `users` WHERE `user_id` = :user_id', 'user_id', array(':user_id' => $user['id']), 'chat')){
                load_libs('user');
                throw new bException(tr('chat_update_user(): Specified user "%user%" does not exist', array('%user%' => user_name($user))), 'not-exist');
            }
        }

    }catch(Exception $e){
        throw new bException(tr('chat_update_user(): Failed'), $e);
    }
}



/*
 *
 */
function chat_update_rank($user){
    try{
        if(has_rights('god', $user)){
            $rank = 5;

        }elseif(has_rights('moderator', $user)){
            $rank = 3;

        }else{
            $rank = 1;
        }

        $r = sql_query('UPDATE `users`

                        SET    `user_rank` = :user_rank

                        WHERE  `user_id`   = :user_id',

                        array(':user_id'   => $user['id'],
                              ':user_rank' => $rank), null, 'chat');

        if(!$r->rowCount()){
            /*
             * This means either no data has been changed, or the specified ID doesn't exist.
             * The former is okay, the latter should never happen.
             */
            if(!sql_get('SELECT `user_id` FROM `users` WHERE `user_id` = :user_id', 'user_id', array(':user_id' => $user['id']))){
                load_libs('user');
                throw new bException(tr('chat_update_rank(): Specified user "%user%" does not exist', array('%user%' => user_name($user))), 'not-exist');
            }
        }

    }catch(Exception $e){
        throw new bException(tr('chat_update_rank(): Failed'), $e);
    }
}



/*
 * Ensure that all users in the igotit database exist in the chat database.
 * Those that exist in the chat database and do not exist in the igotit database should be removed
 * If users in igotit have status, then ensure that this status is reflected in chat as well
 */
function chat_sync_users($user, $log_console = false){
    try{
        /*
         * List all users form igotit site, and ensure they are in the chat
         */
        $r = sql_query('SELECT `id`, `name`, `user_name`, `email`, `status` FROM `users`');

        $s = sql_prepare('SELECT `user_id`, `user_name`, `user_email`, `user_status` FROM `users` WHERE `user_id` = :user_id');

        while($user = sql_fetch($r)){
            try{
                if(!$chat_user = $s->execute(array(':user_id' => $user['id']))){
                    chat_add_user($user);

                }else{
                    chat_update_user($user);
                }

            }catch(Exception $e){
                throw new bException(tr('chat_sync_users(): Failed to process user "%user%"', array('%user%' => user_name($user))), $e);
            }
        }

    }catch(Exception $e){
        throw new bException(tr('chat_sync_users(): Failed'), $e);
    }
}



/*
 * Update user avatar
 */
function chat_update_avatar($user, $avatar){
    try{
        $r = sql_query('UPDATE `users`

                        SET    `avatar`  = :avatar

                        WHERE  `user_id` = :user_id',

                        array(':user_id' => $user['id'],
                              ':avatar'  => $avatar), null, 'chat');

    }catch(Exception $e){
        throw new bException(tr('chat_update_avatar(): Failed'), $e);
    }
}
?>
