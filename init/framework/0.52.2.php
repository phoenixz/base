<?php
/*
 * Users can now independantly specify their timezones
 */
sql_column_exists('users', 'timezone', '!ALTER TABLE `users` ADD COLUMN `timezone` VARCHAR(32) NULL');
?>
