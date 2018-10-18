<?php
/*
 * Fix servers table, createdby may be NULL
 */
sql_query('ALTER TABLE `users`
           CHANGE COLUMN `latitude` `latitude` VARCHAR(20) NULL DEFAULT NULL ,
           CHANGE COLUMN `longitude` `longitude` VARCHAR(20) NULL DEFAULT NULL');
?>
