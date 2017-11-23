<?php
/*
 * Add unique index for html_img to ensure no double entries will be added
 * Rename table to html_img_cache, which is a more appropriate name
 */
if(sql_table_exists('html_img')){
    sql_query('ALTER TABLE `html_img` MODIFY COLUMN `url` VARCHAR(180)');

    sql_index_exists('html_img', 'url', 'ALTER TABLE `html_img` DROP KEY `url`');

    sql_query('TRUNCATE `html_img`');
    sql_query('ALTER TABLE `html_img` ADD UNIQUE KEY `url` (`url`)');
    sql_query('RENAME TABLE `html_img` TO `html_img_cache`');
}
?>
