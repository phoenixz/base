<?php
/*
 * Add medium images sizes for blogs
 */
sql_column_exists('blogs', 'medium_x', '!ALTER TABLE `blogs` ADD COLUMN `medium_x` INT(11) NULL');
sql_column_exists('blogs', 'medium_y', '!ALTER TABLE `blogs` ADD COLUMN `medium_y` INT(11) NULL');
?>
