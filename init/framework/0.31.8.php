<?php
/*
 * Add support for mimetype data to sms_images
 */
sql_column_exists('sms_images', 'mimetype', '!ALTER TABLE `sms_images` ADD COLUMN `mimetype` VARCHAR(16) NULL AFTER `file`');
?>
