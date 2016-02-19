<?php
/*
 * Remove useless data from html_img table
 */
sql_column_exists('html_img', 'modifiedon', 'ALTER TABLE `html_img` DROP COLUMN `modifiedon`');
?>
