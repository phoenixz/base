<?php
/*
 * Add blog URL configuration
 */
sql_column_exists('blogs', 'url_template', '!ALTER TABLE `blogs` ADD COLUMN `url_template` VARCHAR(255) AFTER `seoname`');
?>
