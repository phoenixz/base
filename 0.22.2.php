<?php
/*
 * Add retina image configuration to blogs
 */
sql_column_exists('blogs', 'retina', '!ALTER TABLE `blogs` ADD COLUMN `retina` TINYINT(1) AFTER `images_y`');
?>