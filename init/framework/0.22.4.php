<?php
/*
 * Users "name" column can be empty, so NULL is allowed
 */
sql_query('ALTER TABLE `users` CHANGE COLUMN `name` `name` VARCHAR(255) NULL');
?>