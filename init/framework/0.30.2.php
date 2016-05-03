<?php
/*
 * Add IP column to redirects table
 */
sql_query('ALTER TABLE `blogs` CHANGE COLUMN `thumb_x`  `thumb_x`  INT(11) NOT NULL;');
sql_query('ALTER TABLE `blogs` CHANGE COLUMN `thumb_y`  `thumb_y`  INT(11) NOT NULL;');
sql_query('ALTER TABLE `blogs` CHANGE COLUMN `wide_x`   `wide_x`   INT(11) NOT NULL;');
sql_query('ALTER TABLE `blogs` CHANGE COLUMN `wide_y`   `wide_y`   INT(11) NOT NULL;');
sql_query('ALTER TABLE `blogs` CHANGE COLUMN `large_x`  `large_x`  INT(11) NOT NULL;');
sql_query('ALTER TABLE `blogs` CHANGE COLUMN `large_y`  `large_y`  INT(11) NOT NULL;');
sql_query('ALTER TABLE `blogs` CHANGE COLUMN `medium_x` `medium_x` INT(11) NOT NULL;');
sql_query('ALTER TABLE `blogs` CHANGE COLUMN `medium_y` `medium_y` INT(11) NOT NULL;');
sql_query('ALTER TABLE `blogs` CHANGE COLUMN `small_x`  `small_x`  INT(11) NOT NULL;');
sql_query('ALTER TABLE `blogs` CHANGE COLUMN `small_y`  `small_y`  INT(11) NOT NULL;');
?>
