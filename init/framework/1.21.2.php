<?php
/*
 * Fix servers table, createdby may be NULL
 */
sql_query('ALTER TABLE `servers` MODIFY COLUMN `createdby` INT(11) NULL DEFAULT NULL');
?>