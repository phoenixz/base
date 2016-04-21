<?php
/*
 * Fix UNIQUE (32) errors because the url many times is the same on the first 32 characters
 */
$tables = array('html_img',
                'blogs_posts',
                'curl_cache');

foreach($tables as $table){
    sql_index_exists($table, 'url',  'ALTER TABLE `'.$table.'` DROP INDEX `url`');
    sql_query('ALTER TABLE `'.$table.'` ADD INDEX (`url`)');
}
?>
