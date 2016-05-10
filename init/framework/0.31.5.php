<?php
/*
 * Add column signin_count to keep track of amount of user signins
 * Add column redirect to limit user's access to only that specifed page
 */
sql_column_exists('users', 'last_login'  , 'ALTER TABLE `users` CHANGE COLUMN `last_login` `last_signin` DATETIME NULL');

sql_column_exists('users', 'signin_count', '!ALTER TABLE `users` ADD COLUMN `signin_count` INT(11)       NOT NULL AFTER `last_signin`');
sql_column_exists('users', 'redirect'    , '!ALTER TABLE `users` ADD COLUMN `redirect`     VARCHAR(255)      NULL AFTER `longitude`');
sql_column_exists('users', 'location'    , '!ALTER TABLE `users` ADD COLUMN `location`     VARCHAR(64)       NULL AFTER `redirect`');
sql_column_exists('users', 'description' , '!ALTER TABLE `users` ADD COLUMN `description`  VARCHAR(2047) NOT NULL AFTER `commentary`');
?>
