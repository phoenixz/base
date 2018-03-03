<?php
/*
 * Add support for original_id, which can be used for modified copies of files,
 * and will point towards the original file
 */
sql_column_exists('storage_files', 'originals_id', '!ALTER TABLE `storage_files` ADD COLUMN `originals_id` INT(11) NULL AFTER `files_id`');
sql_index_exists ('storage_files', 'originals_id', '!ALTER TABLE `storage_files` ADD KEY    `originals_id` (`originals_id`)');
?>
