<?php
/*
 * Fix servers table, createdby may be NULL
 */
sql_query('ALTER TABLE `users`
           CHANGE COLUMN `latitude`  `latitude`  FLOAT(14, 14) NULL DEFAULT NULL,
           CHANGE COLUMN `longitude` `longitude` FLOAT(14, 14) NULL DEFAULT NULL');
?>
