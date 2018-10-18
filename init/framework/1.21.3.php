<?php
/*
 * Fix servers table, createdby may be NULL
 */
sql_query('ALTER TABLE `users`
           CHANGE COLUMN `latitude`  `latitude`  DOUBLE(10, 14) NULL DEFAULT NULL,
           CHANGE COLUMN `longitude` `longitude` DOUBLE(10, 14) NULL DEFAULT NULL');
?>
