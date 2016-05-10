<?php
/*
 * Email column can now also be null because only either a username or email
 * must be supplied
 */
sql_table_exists('email_users', 'RENAME TABLE `email_users` TO `email_accounts`');

sql_column_exists('email_conversations', 'email_accounts_id', '!ALTER TABLE `email_conversations` ADD COLUMN `email_accounts_id` INT(11) NULL AFTER `users_id`');
sql_column_exists('email_messages'     , 'email_accounts_id', '!ALTER TABLE `email_messages`      ADD COLUMN `email_accounts_id` INT(11) NULL AFTER `users_id`');

sql_index_exists ('email_conversations', 'email_accounts_id', '!ALTER TABLE `email_conversations` ADD INDEX (`email_accounts_id`)');
sql_index_exists ('email_messages'     , 'email_accounts_id', '!ALTER TABLE `email_messages`      ADD INDEX (`email_accounts_id`)');

sql_foreignkey_exists('email_conversations', 'fk_email_conversations_email_accounts_id', '!ALTER TABLE `email_conversations` ADD CONSTRAINT `fk_email_conversations_email_accounts_id` FOREIGN KEY (`email_accounts_id`) REFERENCES `email_accounts` (`id`) ON DELETE RESTRICT;');
sql_foreignkey_exists('email_messages'     , 'fk_email_messages_email_accounts_id'     , '!ALTER TABLE `email_messages`      ADD CONSTRAINT `fk_email_messages_email_accounts_id` FOREIGN KEY (`email_accounts_id`)      REFERENCES `email_accounts` (`id`) ON DELETE RESTRICT;');
?>
