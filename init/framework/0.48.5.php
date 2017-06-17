<?php
/*
 * Users table now supports user titles, user priority
 */
sql_column_exists('users', 'title'   , '!ALTER TABLE `users` ADD COLUMN `title`    VARCHAR(24) NULL AFTER `domain`');
sql_column_exists('users', 'priority', '!ALTER TABLE `users` ADD COLUMN `priority` INT(11) NULL AFTER `roles_id`');
sql_index_exists ('users', 'priority', '!ALTER TABLE `users` ADD KEY    `priority` (`priority`)');
?>
