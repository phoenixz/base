<?php
/*
 * Add support for parrallel tasks execution for parents_id dependant tasks
 */
sql_column_exists('tasks', 'parrallel', '!ALTER TABLE `tasks` ADD COLUMN `parrallel` TINYINT(1) NULL AFTER `parents_id`');
?>
