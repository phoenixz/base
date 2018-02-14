<?php
/*
 * Fix default column in drivers_devices, since this (with the unique index)
 * effectively would allow only two devices per type
 */
sql_query('ALTER TABLE `drivers_devices` MODIFY COLUMN `default` TINYINT(1) NULL');
?>
