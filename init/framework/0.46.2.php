<?php
/*
 * twilio numbers modify number of characters in colum number
 */
sql_index_exists ('twilio_numbers', 'number',  'ALTER TABLE `twilio_numbers` DROP INDEX `number`');
sql_column_exists('twilio_numbers', 'number',  'ALTER TABLE `twilio_numbers` MODIFY `number` VARCHAR(14) NOT NULL');
sql_index_exists ('twilio_numbers', 'number', '!ALTER TABLE `twilio_numbers` ADD INDEX `number` (`number`)');

?>