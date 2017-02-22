<?php
/*
 * Rename cdn_objects to cdn_files, rename "url" to "file" since thats what it
 * is
 */
if(sql_table_exists('cdn_objects')){
    sql_foreignkey_exists('cdn_objects', 'fk_cdn_objects_projects_id', 'ALTER TABLE `cdn_objects` DROP FOREIGN KEY `fk_cdn_objects_projects_id`');

    sql_index_exists ('cdn_objects', 'url' ,  'ALTER TABLE `cdn_objects` DROP KEY `url`');
    sql_column_exists('cdn_objects', 'url' ,  'ALTER TABLE `cdn_objects` CHANGE COLUMN `url` `file` VARCHAR(128) NOT NULL');
    sql_index_exists ('cdn_objects', 'file', '!ALTER TABLE `cdn_objects` ADD UNIQUE KEY `file` (`file`)');

    sql_table_exists('cdn_files', 'DROP TABLE `cdn_files`');
    sql_query('RENAME TABLE `cdn_objects` TO `cdn_files`');

    sql_foreignkey_exists('cdn_files', 'fk_cdn_cdn_files_projects_id', 'ALTER TABLE `cdn_files` ADD CONSTRAINT `fk_cdn_cdn_files_projects_id` FOREIGN KEY (`projects_id`) REFERENCES `cdn_projects` (`id`) ON DELETE RESTRICT;');
}
?>
