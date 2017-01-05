<?php
/*
 * Add "sources_id" column
 */
sql_column_exists('dictionary', 'sources_id', '!ALTER TABLE `dictionary` ADD COLUMN `sources_id` INT(11) NULL AFTER `translation`');
sql_index_exists ('dictionary', 'sources_id', '!ALTER TABLE `dictionary` ADD UNIQUE(`sources_id`)');

sql_foreignkey_exists('dictionary', 'fk_dictionary_sources_id', '!ALTER TABLE `dictionary` ADD CONSTRAINT `fk_dictionary_sources_id` FOREIGN KEY (`sources_id`) REFERENCES `dictionary` (`id`) ON DELETE RESTRICT;');
?>