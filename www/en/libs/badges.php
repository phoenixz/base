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
        $user = sql_get('SELECT `users`.`createdon`

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

        /*
         * Set dates
         */
        $user_date    = new DateTime(system_date_format($user['createdon'], 'date'));
        $current_date = new DateTime();
        $dtime        = $current_date->diff($user_date);
        $dtime        = $dtime->d;

        /*
         * Look for which badges must the user have
         * according to the number of days
         */
        $badge = '';

        if($dtime >= 365){
            /*
             * User has more than a year
             */
           $badge = 'one_year';
        }

        if($dtime >= 30){
            /*
             * User has more than a month
             */
            $badge = 'one_month';
        }

        if($dtime < 30){
            /*
             * User is new
             */
            $badge = 'new';
        }

        if(!empty($badge)){
            badge_removes($user_id, $badge, $badges);
// :TODO: implement a function like "badge_adds"
// That can check if user has already that badge
// if he already has then do nothing if not add badge
// badge_add($user_id, $badge);
        }

    }catch(Exception $e){
        throw new bException("badge_process_user(): Failed ", $e);
    }
}




/*
 * Process all users
 */
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




/*
 * Remove unnecesary badges
 */
function badge_removes($user_id, $badge, $badges){
    global $_CONFIG;
    try{
        /*
         * Check if given type is in CONFIG[badges]
         */
        if(empty($_CONFIG['badges'][$badge])){
            throw new bException(tr('badge_removes(): Unkown badge "%type%"', array('%type%' => str_log($badge))), 'unknown');
        }

        if(!empty($_CONFIG['badges'][$badge]['not_need']) and !empty($badges)){
            foreach($_CONFIG['badges'][$badge]['not_need'] as $not_need){
                if(in_array($not_need, $badges)){
                    badge_remove($user_id, $not_need);
                }
            }
        }

    }catch(Exception $e){
        throw new bException('badge_removes(): Failed', $e);
    }
}

?>
