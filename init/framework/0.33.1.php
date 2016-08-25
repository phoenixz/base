<?php
/*
 * Add white label domain support
 */
sql_column_exists('users', 'domain', '!ALTER TABLE `users` ADD COLUMN `domain` VARCHAR(128) NULL AFTER `password`');
?>
