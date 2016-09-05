<?php
/*
 * Whitelabel domains should have seoname
 */
sql_column_exists('domains', 'seoname', '!ALTER TABLE `domains` ADD COLUMN `seoname` VARCHAR(64) NOT NULL AFTER `name`');
sql_index_exists ('domains', 'seoname', '!ALTER TABLE `domains` ADD UNIQUE(`seoname`)');

sql_foreignkey_exists ('domains', 'fk_domains_users_id',  'ALTER TABLE `domains` DROP FOREIGN KEY `fk_domains_users_id`');

sql_query('ALTER TABLE `domains` DROP INDEX `users_id`');
sql_query('ALTER TABLE `domains` ADD  INDEX(`users_id`)');

sql_foreignkey_exists('domains', 'fk_domains_users_id', '!ALTER TABLE `domains` ADD CONSTRAINT `fk_domains_users_id` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT;');

load_libs('seo');
sql_prepare('UPDATE `domains` SET `seoname` = NULL');

$domains = sql_query('SELECT `id`, `name` FROM `domains`');
$update  = sql_prepare('UPDATE `domains` SET `seoname` = :seoname WHERE `id` = :id');

while($domain = sql_fetch($domains)){
    $update->execute(array(':id'      => $domain['id'],
                           ':seoname' => seo_unique($domain['name'], 'domains')));
}
?>
