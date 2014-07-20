<?php
sql_index_exists ('mailer_mailings', 'title'    ,  'ALTER TABLE `mailer_mailings` DROP INDEX `title`;');
sql_index_exists ('mailer_mailings', 'seo_title',  'ALTER TABLE `mailer_mailings` DROP INDEX `seotitle`;');

sql_column_exists('mailer_mailings', 'title'    ,  'ALTER TABLE `mailer_mailings` CHANGE COLUMN `title`    `name`    VARCHAR(32) NOT NULL;');
sql_column_exists('mailer_mailings', 'seotitle' ,  'ALTER TABLE `mailer_mailings` CHANGE COLUMN `seotitle` `seoname` VARCHAR(32) NOT NULL;');
sql_column_exists('mailer_mailings', 'addedby'  , '!ALTER TABLE `mailer_mailings` ADD    COLUMN `addedby`            INT(11)         NULL AFTER `addedon`;');

sql_index_exists ('mailer_mailings', 'name'     , '!ALTER TABLE `mailer_mailings` ADD INDEX  (`name`);');
sql_index_exists ('mailer_mailings', 'seoname'  , '!ALTER TABLE `mailer_mailings` ADD UNIQUE (`seoname`);');
sql_index_exists ('mailer_mailings', 'addedby'  , '!ALTER TABLE `mailer_mailings` ADD INDEX  (`addedby`);');

sql_index_exists ('mailer_mailings', 'seoname'  , '!ALTER TABLE `mailer_mailings` ADD CONSTRAINT `addedby_name` UNIQUE (`addedby`, `name`);');

sql_foreignkey_exists ('mailer_mailings', 'fk_mailer_mailings_addedby', '!ALTER TABLE `mailer_mailings` ADD CONSTRAINT `fk_mailer_mailings_addedby` FOREIGN KEY (`addedby`) REFERENCES `users` (`id`) ON DELETE RESTRICT;');
?>