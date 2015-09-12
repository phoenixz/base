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
        $badges = sql_list('SELECT `users_badges`.`id`,
                                   `users_badges`.`en`

                            FROM   `users_badges`

                            WHERE  `users_badges`.`user_id` = :user_id',

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
        $year  = 1;
        $days  = 365;

        do{
            if(($dtime >= $days*$year) and !in_array('year'.$year, $badges)){
                badge_add($user_id, 'year'.$year);
            }
            $year += 1;
// :TODO: use badge_remove to remove the unneeded badges
// For example if a user has now year2 then remove year1
        }while($year <= 3);
// :INVESTIGATE: which will be STOP condition ?

    }catch(Exception $e){
        throw new bException("badge_process_user(): Failed ", $e);
    }
}




/*
 * Process all users
 */
function badge_process_users(){
    try{
        $r = sql_query('SELECT `id` FROM `users` WHERE `status` IS NULL');

        while($user = sql_fetch($r)){
            try{
                badge_process_user($user['id']);

            }catch(Exception $e){
                /*
                 * A user failed, must be notified but the process
                 * has to continue
                 */
// :TODO: Notify!
                log_database(tr('Failed to process user %user%', array('%user%' => $user['id'])), 'error');
                continue;
            }
        }

    }catch(Exception $e){
        throw new bException('badge_process_users(): Failed', $e);
    }
}




/*
 * Add one badge to a user
 */
function badge_add($user_id, $badge_name){
    try{
        if(empty($user_id)){
            throw new bException('badge_add(): user_id is empty');
        }

        if(empty($badge_name)){
            throw new bException('badge_add(): badge_name is empty');
        }

        /*
         * Get the badge info
         */
        $badge = sql_get('SELECT `id`
                                 `en`,
                                 `en_seo`,
                                 `en_description`,
                                 `es`,
                                 `es_seo`,
                                 `es_description`,
                                 `nl`,
                                 `nl_seo`,
                                 `nl_description`

                          FROM   `badges`

                          WHERE  `badges`.`en` = :badge_name',

                          array(':badge_name' => $badge_name));

        /*
         * Add one badge to a user
         */
        sql_query('INSERT INTO `users_badges` (`user_id`, `badge_id`, `en`, `en_image`, `en_seo`, `en_description`, `es`, `es_image`, `es_seo`, `es_description`, `nl`, `nl_image`, `nl_seo`, `nl_description`)
                   VALUES                     (:user_id , :badge_id , :en , :en_image , :en_seo , :en_description , :es , :es_image , :es_seo , :es_description , :nl , :nl_image , :nl_seo , :nl_description )',

                   array(':user_id'        => $user_id,
                         ':badge_id'       => $badge['id'],
                         ':en'             => $badge['en'],
                         ':en_image'       => $badge['en_image'],
                         ':en_seo'         => $badge['en_seo'],
                         ':en_description' => $badge['en_description'],
                         ':es'             => $badge['es'],
                         ':es_image'       => $badge['es_image'],
                         ':es_seo'         => $badge['es_seo'],
                         ':es_description' => $badge['es_description'],
                         ':nl'             => $badge['nl'],
                         ':nl_image'       => $badge['nl_image'],
                         ':nl_seo'         => $badge['nl_seo'],
                         ':nl_description' => $badge['nl_description']));

    }catch(Exception $e){
        throw new bException('badge_add(): Failed', $e);
    }
}




/*
 * Remove badge from a user
 */
function badge_remove($user_id, $badge_name){
    try{
        if(empty($user_id)){
            throw new bException('badge_remove(): user_id is empty');
        }

        if(empty($badge_name)){
            throw new bException('badge_remove(): badge_name is empty');
        }

        /*
         * Delete badge from a user
         */
        sql_query('DELETE FROM `users_badges`

                   WHERE       `users_badges`.`user_id` = :user_id
                   AND         `users_badges`.`en`      = :badge_name',

                   array(':user_id'    => $user_id,
                         ':badge_name' => $badge_name));

    }catch(Exception $e){
        throw new bException('badge_remove(): Failed', $e);
    }
}




/*
 * Generate html for badges
 */
function badge_html($user_id){
// :TODO: Actually this function must write the html code to DB `users`.`badges_cached`
    try{
        /*
         * Load the badges of the user
         */
        $badges = sql_list('SELECT `users_badges`.`'.LANGUAGE.'_image`,
                                   `users_badges`.`'.LANGUAGE.'_seo`

                            FROM   `users_badges`

                            WHERE  `users_badges`.`user_id` = :user_id',

                            array(':user_id' => $user_id));

        $html = '<div class="badges_top">
                    <ul>';

        if(!empty($badges)){
            foreach($badges as $badge){
// :TODO: Load badge image
                $html .= '<li>
                            '.html_img().'
                          </li>';
            }
        }

        $html .= '  </ul>
                 </div>';

    }catch(Exception $e){
        throw new bException('badge_html(): Failed', $e);
    }
}
?>
