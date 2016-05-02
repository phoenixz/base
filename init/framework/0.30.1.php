<?php
/*
 * Add IP column to redirects table
 */
sql_column_exists('redirects', 'ip' , '!ALTER TABLE `redirects` ADD COLUMN `ip` VARCHAR(15) NOT NULL;');
sql_index_exists ('redirects', 'ip' , '!ALTER TABLE `redirects` ADD INDEX (`ip`);');

sql_query('ALTER TABLE `redirects` CHANGE COLUMN `createdby` `createdby` INT(11) NULL;');
?>
