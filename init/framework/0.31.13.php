<?php
/*
 * Add leaders_id support
 */

sql_column_exists('contactus', 'phones' , '!ALTER TABLE `contactus` ADD COLUMN `phones`  VARCHAR(100) NOT NULL AFTER `name`;');
sql_column_exists('contactus', 'company', '!ALTER TABLE `contactus` ADD COLUMN `company` VARCHAR(128) NOT NULL AFTER `name`;');
?>
