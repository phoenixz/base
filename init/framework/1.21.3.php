<?php
/*
 * Fix users longitude / latitude storage
 */
sql_query('ALTER TABLE `users`
           CHANGE COLUMN `latitude`  `latitude`  DECIMAL(18, 15) NULL DEFAULT NULL,
           CHANGE COLUMN `longitude` `longitude` DECIMAL(18, 15) NULL DEFAULT NULL');
?>