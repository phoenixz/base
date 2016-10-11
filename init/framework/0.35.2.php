<?php
/*
 * Upgrade rights sytem to be able to use larger rights names
 */
sql_query('ALTER TABLE `rights`       CHANGE COLUMN `name` `name` VARCHAR(32) NOT NULL');
sql_query('ALTER TABLE `users_rights` CHANGE COLUMN `name` `name` VARCHAR(32) NOT NULL');
?>
