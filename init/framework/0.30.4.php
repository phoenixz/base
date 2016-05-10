<?php
/*
 * Split emails table "body" column into "html" and "text" columns
 */
sql_index_exists ('emails', 'body',  'ALTER TABLE `emails` DROP INDEX (`body`);');
sql_column_exists('emails', 'body',  'ALTER TABLE `emails` DROP COLUMN `body`;');

sql_column_exists('emails', 'html', '!ALTER TABLE `emails` ADD COLUMN `html` text NOT NULL;');
sql_column_exists('emails', 'text', '!ALTER TABLE `emails` ADD COLUMN `text` text NOT NULL;');
?>
