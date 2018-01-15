<?php
/*
 * Fix social media storage structure
 */
log_console('Fixing users - social media accounts storage structure');

sql_index_exists('users', 'fb_id', 'ALTER TABLE `users` DROP INDEX `fb_id`');
sql_index_exists('users', 'gp_id', 'ALTER TABLE `users` DROP INDEX `gp_id`');
sql_index_exists('users', 'ms_id', 'ALTER TABLE `users` DROP INDEX `ms_id`');
sql_index_exists('users', 'tw_id', 'ALTER TABLE `users` DROP INDEX `tw_id`');
sql_index_exists('users', 'yh_id', 'ALTER TABLE `users` DROP INDEX `yh_id`');

sql_column_exists('users', 'fb_id', 'ALTER TABLE `users` DROP COLUMN `fb_id`');
sql_column_exists('users', 'gp_id', 'ALTER TABLE `users` DROP COLUMN `gp_id`');
sql_column_exists('users', 'ms_id', 'ALTER TABLE `users` DROP COLUMN `ms_id`');
sql_column_exists('users', 'tw_id', 'ALTER TABLE `users` DROP COLUMN `tw_id`');
sql_column_exists('users', 'yh_id', 'ALTER TABLE `users` DROP COLUMN `yh_id`');

sql_column_exists('users', 'fb_token'               , 'ALTER TABLE `users` DROP COLUMN `fb_token`');
sql_column_exists('users', 'gp_token'               , 'ALTER TABLE `users` DROP COLUMN `gp_token`');
sql_column_exists('users', 'ms_token_authentication', 'ALTER TABLE `users` DROP COLUMN `ms_token_authentication`');
sql_column_exists('users', 'ms_token_access'        , 'ALTER TABLE `users` DROP COLUMN `ms_token_access`');
sql_column_exists('users', 'tw_token'               , 'ALTER TABLE `users` DROP COLUMN `tw_token`');
sql_column_exists('users', 'yh_token'               , 'ALTER TABLE `users` DROP COLUMN `yh_token`');

sql_query('ALTER TABLE `users_social` MODIFY COLUMN `identifier` BIGINT(20) UNSIGNED');
?>
