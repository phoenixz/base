<?php
/*
 * Add support for parrallel tasks execution for parents_id dependant tasks and
 * PID storage
 */
sql_column_exists('tasks', 'parrallel', '!ALTER TABLE `tasks` ADD COLUMN `parrallel` TINYINT(1) NULL AFTER `parents_id`');
sql_column_exists('tasks', 'pid'      , '!ALTER TABLE `tasks` ADD COLUMN `pid`       INT(11)    NULL AFTER `parrallel`');
?>
