<?php
/*
 * Fix users_reference_codes column users_id
 */
sql_foreignkey_exists ('users_reference_codes', 'fk_users_reference_codes_users_id', 'ALTER TABLE `users_reference_codes` DROP FOREIGN KEY `fk_users_reference_codes_users_id`');

sql_query('DELETE FROM `users_reference_codes` WHERE `users_id` IS NULL');
sql_query('DELETE FROM `users_reference_codes` WHERE `users_id` = 0');
sql_query('ALTER TABLE `users_reference_codes` MODIFY COLUMN `users_id` INT(11) NOT NULL');

sql_foreignkey_exists('users_reference_codes', 'fk_users_reference_codes_users_id', '!ALTER TABLE `users_reference_codes` ADD CONSTRAINT `fk_users_reference_codes_users_id` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`) ON DELETE CASCADE');
?>
