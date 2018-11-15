<?php
/*
 * Add missing colum employees_id for table users
 */
sql_column_exists('users', 'employees_id', '!ALTER TABLE `users` ADD COLUMN `employees_id` INT(11) NULL AFTER `roles_id`;');
sql_index_exists ('users', 'employees_id', '!ALTER TABLE `users` ADD INDEX  `employees_id` (`employees_id`);');

sql_foreignkey_exists('users', 'fk_users_employees_id' , '!ALTER TABLE `users` ADD CONSTRAINT `fk_users_employees_id` FOREIGN KEY (`employees_id`) REFERENCES `employees` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;');
?>