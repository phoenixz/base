<?php
/*
 * Add support for multiple reference numbers for users
 */
sql_query('DROP TABLE IF EXISTS `users_reference_codes`');

sql_query('CREATE TABLE `users_reference_codes` (`id`        INT(11)     NOT NULL AUTO_INCREMENT,
                                                 `createdon` TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                                 `users_id`  INT(11)         NULL,
                                                 `code`      VARCHAR(16) NOT NULL,

                                                 PRIMARY KEY `id`        (`id`),
                                                         KEY `createdon` (`createdon`),
                                                         KEY `users_id`  (`users_id`),
                                                         KEY `code`      (`code`),

                                                 CONSTRAINT `fk_users_reference_codes_users_id` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`) ON DELETE CASCADE

                                                ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');

/*
 * Now, move all current users reference numbers to the new users_reference_codes table
 */
if(sql_column_exists('users', 'reference_numbers')){
    load_libs('user');
    sql_query('TRUNCATE `users_reference_codes`');

    $users = sql_query('SELECT `id`, `reference_numbers` AS `reference_codes` FROM `users` WHERE `reference_numbers` IS NOT NULL AND `reference_numbers` != ""');

    while($user = sql_fetch($users)){
        try{
            user_update_reference_codes($user, true);
            cli_dot();

        }catch(Exception $e){
            log_console($e);
        }
    }

    cli_dot(false);
    sql_query('ALTER TABLE `users` DROP COLUMN `reference_numbers`');
}

sql_index_exists('users', 'reference_numbers', 'ALTER TABLE `users` DROP INDEX `reference_numbers`');
?>
