<?php
/*
 * Fix blogs_posts category and seocategory columns
 */
sql_column_exists('blogs_key_values', 'seovalue', '!ALTER TABLE `blogs_key_values` ADD COLUMN `seovalue` VARCHAR(128) AFTER `value`');
sql_index_exists ('blogs_key_values', 'seovalue', '!ALTER TABLE `blogs_key_values` ADD INDEX(`seovalue`)');

log_console('Fixing `blogs_key_values`.`seovalue` column', 'init', '', false);
load_libs('seo');

$count = 0;
$r     = sql_query('SELECT DISTINCT `value` FROM `blogs_key_values`');
$p     = sql_prepare('UPDATE `blogs_key_values` SET `seovalue` = :seovalue WHERE `value` = :value');

while($value = sql_fetch($r)){
    if($count++ > 10){
        $count = 0;
        log_console('.', '', 'green', false);
    }

    $p->execute(array(':value'    => $value['value'],
                      ':seovalue' => seo_create_string($value['value'])));
}

log_console('done', '', 'green');
?>
