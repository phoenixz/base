<?php
/*
 * Add verbose option to the tasks table
 */
sql_column_exists('tasks', 'verbose', '!ALTER TABLE `tasks` ADD COLUMN `verbose` TINYINT(1) NOT NULL AFTER `time_limit`');
?>