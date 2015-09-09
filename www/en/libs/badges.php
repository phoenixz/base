<?php
/*
 * Badges Library
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>
 */

load_config('badges');

function badge_process_user($user, $badge = null){
    try{

        /*
         * Check if there is no given badge
         */
        if(!$badge){
            $badge = $_CONFIG['badges']['default'];
        }

        /*
         * Check if given type is in CONFIG[badges]
         */
        if(empty($_CONFIG['badges']['types'][$badge])){
            throw new bException(tr('badge_process_user(): Unkown badge "%type%"', array('%type%' => str_log($badge))), 'unknown');
        }

        /*
         * UPDATE user table
         */
        sql_query('UPDATE `users`

                   SET    `badge` = :badge

                   WHERE  `id`    = :id',

                   array(':badge' => $_CONFIG['badges']['types'][$badge],
                         ':id'    => $user['id']));

        /*
         * INSERT into badge table
         */
        sql_query('INSERT INTO `badges` (`createdby`, `status`, `user_id`)
                   VALUES               (:createdby , :status , :user_id )',

                   array(':createdby' => $user['id'],
                         ':status'    => '',
                         ':user_id'   => $user['id']));

    }catch(Exception $e){
        throw new bException("badge_process_user(): Failed ", $e);
    }
}

function badge_process_users(){
    try{

    }catch(Exception $e){
        throw new bException('badge_process_users(): Failed', $e);
    }
}

function add_specific_badge(){
    try{

    }catch(Exception $e){
        throw new bException('add_specific_badge(): Failed', $e);
    }
}

function remove_specific_badge(){
    try{

    }catch(Exception $e){
        throw new bException('remove_specific_badge(): Failed', $e);
    }
}

?>
