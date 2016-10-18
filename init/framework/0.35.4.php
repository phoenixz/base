<?php
/*
 * Add support for user keywords
 */
sql_column_exists('users', 'keywords', '!ALTER TABLE `users` ADD COLUMN `keywords` VARCHAR(255) NULL AFTER `type`');
?>
