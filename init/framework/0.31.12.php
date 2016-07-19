<?php
/*
 * Add leaders_id support
 */
sql_column_exists('users', 'leaders_id', '!ALTER TABLE `users` ADD COLUMN `leaders_id` INT(11)');
sql_index_exists ('users', 'leaders_id', '!ALTER TABLE `users` ADD INDEX (`leaders_id`)');
sql_foreignkey_exists('users', 'fk_users_leaders_id', '!ALTER TABLE `users` ADD CONSTRAINT `fk_users_leaders_id` FOREIGN KEY (`leaders_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT;');
?>
