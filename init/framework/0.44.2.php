<?php
/*
 * Fix the various table issues in ads_campaigns
 */
sql_foreignkey_exists('ads_campaigns', 'fk_ads_campaigns_clusters_id', 'ALTER TABLE `ads_campaigns` DROP FOREIGN KEY `fk_ads_campaigns_clusters_id`');

sql_index_exists ('ads_campaigns', 'clusters_id', 'ALTER TABLE `ads_campaigns` DROP COLUMN `clusters_id`');
sql_column_exists('ads_campaigns', 'clusters_id', 'ALTER TABLE `ads_campaigns` DROP COLUMN `clusters_id`');

sql_column_exists('ads_campaigns', 'image_ttl', '!ALTER TABLE `ads_campaigns` ADD COLUMN `image_ttl` INT(11)     NOT NULL');
sql_column_exists('ads_campaigns', 'class'    , '!ALTER TABLE `ads_campaigns` ADD COLUMN `class`     VARCHAR(16)     NULL');

sql_column_exists('ads_images', 'animation'  , '!ALTER TABLE `ads_images` ADD COLUMN `animation`   VARCHAR(16)     NULL');
sql_column_exists('ads_images', 'platform'   , '!ALTER TABLE `ads_images` ADD COLUMN `platform`    VARCHAR(16)     NULL');
sql_column_exists('ads_images', 'priority'   , '!ALTER TABLE `ads_images` ADD COLUMN `priority`    INT(11)     NOT NULL');
sql_column_exists('ads_images', 'clusters_id', '!ALTER TABLE `ads_images` ADD COLUMN `clusters_id` INT(11)         NULL');

sql_index_exists ('ads_images', 'priority'   , '!ALTER TABLE `ads_images` ADD INDEX (`priority`)');
sql_index_exists ('ads_images', 'platform'   , '!ALTER TABLE `ads_images` ADD INDEX (`platform`)');
sql_column_exists('ads_images', 'clusters_id', '!ALTER TABLE `ads_images` ADD INDEX (`clusters_id`)');

sql_foreignkey_exists('ads_images', 'fk_ads_images_clusters_id', '!ALTER TABLE `ads_images` ADD CONSTRAINT `fk_ads_images_clusters_id` FOREIGN KEY (`clusters_id`) REFERENCES `forwarder_clusters` (`id`) ON DELETE RESTRICT;');

sql_query('ALTER TABLE `ads_campaigns` CHANGE COLUMN `seoname` `seoname` VARCHAR(64) NULL DEFAULT NULL');
?>
