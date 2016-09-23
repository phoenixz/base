<?php
/*
 * Fix blog table, remove old garbage
 */
sql_foreignkey_exists('blogs', 'fk_blogs_rights_id',  'ALTER TABLE `blogs` DROP FOREIGN KEY `fk_blogs_rights_id`');

sql_index_exists ('blogs', 'language'   , 'ALTER TABLE `blogs` DROP INDEX  `language`');
sql_index_exists ('blogs', 'rights_id'  , 'ALTER TABLE `blogs` DROP INDEX  `rights_id`');
sql_index_exists ('blogs', 'seokeywords', 'ALTER TABLE `blogs` DROP INDEX  `seokeywords`');

sql_column_exists('blogs', 'language'   , 'ALTER TABLE `blogs` DROP COLUMN `language`');
sql_column_exists('blogs', 'rights_id'  , 'ALTER TABLE `blogs` DROP COLUMN `rights_id`');
sql_column_exists('blogs', 'seokeywords', 'ALTER TABLE `blogs` DROP COLUMN `seokeywords`');
?>
