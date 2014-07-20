<?php
sql_column_exists('mailer_mailings', 'from_name', '!ALTER TABLE `mailer_mailings` ADD COLUMN `from_name` VARCHAR(64)    NOT NULL AFTER `title`;');
sql_index_exists ('mailer_mailings', 'from_name', '!ALTER TABLE `mailer_mailings` ADD INDEX (`from_name`);');

sql_column_exists('mailer_mailings', 'from_email', '!ALTER TABLE `mailer_mailings` ADD COLUMN `from_email` VARCHAR(128) NOT NULL AFTER `title`;');
sql_index_exists ('mailer_mailings', 'from_email', '!ALTER TABLE `mailer_mailings` ADD INDEX (`from_email`);');
?>