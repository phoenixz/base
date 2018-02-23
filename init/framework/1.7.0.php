<?php
/*
 * Correct statistics table missing columns
 */
sql_column_exists('statistics', 'resource1', '!ALTER TABLE `statistics` ADD COLUMN `resource1` INT(11) NULL');
sql_column_exists('statistics', 'resource2', '!ALTER TABLE `statistics` ADD COLUMN `resource2` INT(11) NULL');
?>
