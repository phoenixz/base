<?php
/*
 * Badges Library
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>
 */
load_config('badges');


/*
 * Process one user
 */
function badge_process_user($user_id){
    global $_CONFIG;
    try{
        /*
         * Get user info
         */
        $user = sql_get('SELECT `users`.`id`,
                                `users`.`createdon`

                         FROM   `users`

                         WHERE  `users`.`id` = :user_id',

                         array(':user_id' => $user_id));

        /*
         * Get badges from user
         */
        $badges = sql_list('SELECT    `badges`.`id`,
                                      `badges`.`'.LANGUAGE.'` AS badge_name

                            FROM      `badges`

                            LEFT JOIN `users`
                            ON        `users`.`id`       = `badges`.`user_id`

                            WHERE     `badges`.`user_id` = :user_id',

                            array(':user_id' => $user_id));

        if(empty($badges)){
            /*
             * This means user does not have any badges
             */
            badge_add($user_id, 'new');

        }else{
            foreach($badges as $badge){
// :TODO: According to the badges of a user and also the date createdon
//        of a user, add or remove badges.
            }
        }
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



/*
 * Add one badge
 */
function badge_add($user_id, $badge){
    global $_CONFIG;
    try{
        /*
         * Check if given type is in CONFIG[badges]
         */
        if(empty($_CONFIG['badges'][$badge])){
            throw new bException(tr('badge_add(): Unkown badge "%type%"', array('%type%' => str_log($badge))), 'unknown');
        }

        /*
         * Insert new badge
         */
        sql_query('INSERT INTO `badges` (`user_id`, `'.LANGUAGE.'`, `'.LANGUAGE.'_seo`, `'.LANGUAGE.'_description`)
                   VALUES               (:user_id , :badge_name   , :badge_seoname    , :badge_description        )',

                   array(':user_id'           => $user_id,
                         ':badge_name'        => $badge,
                         ':badge_seoname'     => $_CONFIG['badges'][$badge]['seoname'],
                         ':badge_description' => $_CONFIG['badges'][$badge]['description']));

    }catch(Exception $e){
        throw new bException('badge_add(): Failed', $e);
    }
}



/*
 * Remove badge
 */
function badge_remove($user_id, $badge){
    global $_CONFIG;
    try{
        /*
         * Check if given type is in CONFIG[badges]
         */
        if(empty($_CONFIG['badges'][$badge])){
            throw new bException(tr('badge_remove(): Unkown badge "%type%"', array('%type%' => str_log($badge))), 'unknown');
        }

        /*
         * Delete badge
         */
        sql_query('UPDATE `badges`

                   SET    `badges`.`status`       = :status

                   WHERE  `badges`.`user_id`      = :user_id
                   AND    `badges`.`'.LANGUAGE.'` = :badge_name',

                   array(':status'     => "deleted",
                         ':user_id'    => $user_id,
                         ':badge_name' => $badge));

    }catch(Exception $e){
        throw new bException('badge_remove(): Failed', $e);
    }
}

?>
