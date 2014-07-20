<?php
/*
 * Add and correct user data
 */
sql_column_exists('users', 'date_validated', '!ALTER TABLE `users` ADD  COLUMN `date_validated` DATETIME    NULL AFTER `validated`');
sql_column_exists('users', 'admin'         , '!ALTER TABLE `users` ADD  COLUMN `admin`          INT(1)      NULL AFTER `date_validated`');
sql_column_exists('users', 'latitude'      , '!ALTER TABLE `users` ADD  COLUMN `latitude`       FLOAT(10,6) NULL AFTER `admin`');
sql_column_exists('users', 'longitude'     , '!ALTER TABLE `users` ADD  COLUMN `longitude`      FLOAT(10,6) NULL AFTER `latitude`');

sql_column_exists('users', 'birthday'      , '!ALTER TABLE `users` ADD  COLUMN `birthday`       DATE        NULL AFTER `gender`;');

sql_column_exists('users', 'bd_day'        ,  'ALTER TABLE `users` DROP COLUMN `bd_day`;');
sql_column_exists('users', 'bd_month'      ,  'ALTER TABLE `users` DROP COLUMN `bd_month`;');
sql_column_exists('users', 'bd_year'       ,  'ALTER TABLE `users` DROP COLUMN `bd_year`;');

sql_index_exists ('users', 'admin'         , '!ALTER TABLE `users` ADD  INDEX(`admin`)');
sql_index_exists ('users', 'latitude'      , '!ALTER TABLE `users` ADD  INDEX(`latitude`)');
sql_index_exists ('users', 'longitude'     , '!ALTER TABLE `users` ADD  INDEX(`longitude`)');
sql_index_exists ('users', 'birthday'      , '!ALTER TABLE `users` ADD  INDEX(`birthday`)');

/*
 * Fix mailings_mailer indexing
 */
sql_index_exists ('mailer_mailings', 'title'        ,  'ALTER TABLE `mailer_mailings` DROP INDEX `title`');
sql_index_exists ('mailer_mailings', 'title'        , '!ALTER TABLE `mailer_mailings` ADD UNIQUE(`title`)');

sql_column_exists('mailer_mailings', 'header'       ,  'ALTER TABLE `mailer_mailings` CHANGE COLUMN `header` `subject` varchar(255) NOT NULL;');
sql_column_exists('mailer_mailings', 'template_file',  'ALTER TABLE `mailer_mailings` DROP COLUMN `template_file`;');
sql_column_exists('mailer_mailings', 'image'        , '!ALTER TABLE `mailer_mailings` ADD  COLUMN `image` VARCHAR(255) NOT NULL AFTER `to`;');

sql_table_exists('mailer_access', 'RENAME TABLE `mailer_access` TO `mailer_views`;');
sql_table_exists('mailer_viewed', 'RENAME TABLE `mailer_viewed` TO `mailer_views`;');

sql_column_exists('mailer_views'   , 'host'         , '!ALTER TABLE `mailer_views`    ADD  COLUMN `host`  VARCHAR(255)     NULL AFTER `ip`;');
?>