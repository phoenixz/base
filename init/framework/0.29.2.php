<?php
/*
 * Improve default image sizes
 */
sql_column_exists('blogs', 'thumbs_x',  'ALTER TABLE `blogs` CHANGE COLUMN `thumbs_x` `thumb_x` INT(11) NOT NULL;');
sql_column_exists('blogs', 'thumbs_y',  'ALTER TABLE `blogs` CHANGE COLUMN `thumbs_y` `thumb_y` INT(11) NOT NULL;');

sql_column_exists('blogs', 'images_x',  'ALTER TABLE `blogs` CHANGE COLUMN `images_x` `large_x` INT(11) NOT NULL;');
sql_column_exists('blogs', 'images_y',  'ALTER TABLE `blogs` CHANGE COLUMN `images_y` `large_y` INT(11) NOT NULL;');

sql_column_exists('blogs', 'wide_x'  , '!ALTER TABLE `blogs` ADD COLUMN `wide_x`   INT(11) NOT NULL AFTER `thumb_y`;');
sql_column_exists('blogs', 'wide_y'  , '!ALTER TABLE `blogs` ADD COLUMN `wide_y`   INT(11) NOT NULL AFTER `wide_x`;');

sql_column_exists('blogs', 'medium_x', '!ALTER TABLE `blogs` ADD COLUMN `medium_x` INT(11) NOT NULL AFTER `large_y`;');
sql_column_exists('blogs', 'medium_y', '!ALTER TABLE `blogs` ADD COLUMN `medium_y` INT(11) NOT NULL AFTER `medium_x`;');

sql_column_exists('blogs', 'small_x' , '!ALTER TABLE `blogs` ADD COLUMN `small_x`  INT(11) NOT NULL AFTER `medium_y`;');
sql_column_exists('blogs', 'small_y' , '!ALTER TABLE `blogs` ADD COLUMN `small_y`  INT(11) NOT NULL AFTER `small_x`;');

/*
 * Rename all images from big > large!
 */
$files = safe_exec('find '.ROOT.'data/content/photos -name "*_big.jpg"');

foreach($files as $file){
    rename($file, str_replace('_big.', '_large.', $file));
}

$files = safe_exec('find '.ROOT.'data/content/photos -name "*_big@2x.jpg"');

foreach($files as $file){
    unlink($file);
    symlink(str_replace('_big@2x', '_large', $file), str_replace('_big@2x.', '_large@2x.', $file));

}
?>
