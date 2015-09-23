<?php
/*
 * Add website data for users
 */
sql_column_exists('users' , 'website', '!ALTER TABLE `users` ADD COLUMN `website` VARCHAR(255) NULL');
?>
