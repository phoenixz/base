<?php
/*
 * Fix "name" column in users table
 */
sql_index_exists('users', 'name', 'ALTER TABLE `users` DROP INDEX `name`');

/*
 * Fix wtf table design
 */
sql_query('ALTER TABLE `password_reset` CHANGE `id` `id` INT(11) NOT NULL AUTO_INCREMENT');
?>
