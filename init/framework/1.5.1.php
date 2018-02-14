<?php
/*
 * Fix default column in drivers_devices, since this (with the unique index)
 * effectively would allow only two devices per type
 */
sql_query('ALTER TABLE `drivers_devices` MODIFY COLUMN `default` TINYINT(1) NULL');
sql_query('ALTER TABLE `drivers_devices` MODIFY COLUMN `string`  VARCHAR(96) NULL');

sql_column_exists('drivers_devices', 'seostring', '!ALTER TABLE `drivers_devices` ADD COLUMN `seostring` VARCHAR(96) NULL');
sql_index_exists ('drivers_devices', 'seostring', '!ALTER TABLE `drivers_devices` ADD INDEX  `seostring` (`seostring`)');
?>
