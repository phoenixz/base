<?php
/*
 * Add thumbnail sizes for blogs
 */
sql_column_exists('blogs', 'thumbs_x', '!ALTER TABLE `blogs` ADD COLUMN `thumbs_x` INT(11) NULL');
sql_column_exists('blogs', 'thumbs_y', '!ALTER TABLE `blogs` ADD COLUMN `thumbs_y` INT(11) NULL');
sql_column_exists('blogs', 'images_x', '!ALTER TABLE `blogs` ADD COLUMN `images_x` INT(11) NULL');
sql_column_exists('blogs', 'images_y', '!ALTER TABLE `blogs` ADD COLUMN `images_y` INT(11) NULL');
?>