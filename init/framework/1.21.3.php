<?php
/*
 * Fix servers table, createdby may be NULL
 */
sql_query('ALTER TABLE `users`
           CHANGE COLUMN `latitude`  `latitude`  DOUBLE(14, 14) NULL DEFAULT NULL,
           CHANGE COLUMN `longitude` `longitude` DOUBLE(14, 14) NULL DEFAULT NULL');
?>
