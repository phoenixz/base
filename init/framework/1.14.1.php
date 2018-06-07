<?php
/*
 * Rename "forwards" table to "forwarding" to avoid naming confusion
 * Fix forwards table to ensure correct structure
 */
if(sql_table_exists('forwards')){
    if(sql_table_exists('forwardings')){
        sql_query('DROP TABLE `forwardings`');
    }

    sql_query('RENAME TABLE `forwards` TO `forwardings`');
}

sql_column_exists('forwardings', 'meta_id', '!ALTER TABLE `forwardings` ADD COLUMN `meta_id` INT(11) NULL AFTER `createdby`');
sql_index_exists ('forwardings', 'meta_id', '!ALTER TABLE `forwardings` ADD KEY    `meta_id` (`meta_id`)');
sql_foreignkey_exists('forwardings', 'fk_forwardings_meta_id', '!ALTER TABLE `forwardings` ADD CONSTRAINT `fk_forwardings_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE;');

sql_index_exists ('forwardings', 'modifiedon', 'ALTER TABLE `forwardings` DROP KEY    `modifiedon`');
sql_index_exists ('forwardings', 'modifiedby', 'ALTER TABLE `forwardings` DROP KEY    `modifiedby`');

sql_column_exists('forwardings', 'modifiedon', 'ALTER TABLE `forwardings` DROP COLUMN `modifiedon`');
sql_column_exists('forwardings', 'modifiedby', 'ALTER TABLE `forwardings` DROP COLUMN `modifiedby`');
?>
