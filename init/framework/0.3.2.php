<?php
sql_column_exists('users', 'gp_id'   , '!ALTER TABLE `users` ADD COLUMN `gp_id`    BIGINT(20)   NULL AFTER `fb_token`');
sql_column_exists('users', 'gp_token', '!ALTER TABLE `users` ADD COLUMN `gp_token` VARCHAR(255) NULL AFTER `gp_id`');

sql_column_exists('users', 'tw_id'   , '!ALTER TABLE `users` ADD COLUMN `tw_id`    BIGINT(20)   NULL AFTER `gp_token`');
sql_column_exists('users', 'tw_token', '!ALTER TABLE `users` ADD COLUMN `tw_token` VARCHAR(255) NULL AFTER `tw_id`');

sql_column_exists('users', 'ms_id'   , '!ALTER TABLE `users` ADD COLUMN `ms_id`    BIGINT(20)   NULL AFTER `tw_token`');
sql_column_exists('users', 'ms_token', '!ALTER TABLE `users` ADD COLUMN `ms_token` VARCHAR(255) NULL AFTER `ms_id`');

sql_column_exists('users', 'yh_id'   , '!ALTER TABLE `users` ADD COLUMN `yh_id`    BIGINT(20)   NULL AFTER `ms_token`');
sql_column_exists('users', 'yh_token', '!ALTER TABLE `users` ADD COLUMN `yh_token` VARCHAR(255) NULL AFTER `yh_id`');
?>
