<?php
/*
 *
 */
sql_index_exists('blogs_key_values', 'blogs_posts_id_2'  ,  'ALTER TABLE `blogs_key_values` DROP INDEX `blogs_posts_id_2`');
sql_index_exists('blogs_key_values', 'blogs_posts_id_key', '!ALTER TABLE `blogs_key_values` ADD  INDEX `blogs_posts_id_key` (`blogs_posts_id`,`key`)');

/*
 * Drop the unique blogs_posts name and seoname indices since they are too large with mb4_utf character sets
 */
sql_index_exists('blogs_posts', 'name'   , 'ALTER TABLE `blogs_posts` DROP INDEX `name`');
sql_index_exists('blogs_posts', 'seoname', 'ALTER TABLE `blogs_posts` DROP INDEX `seoname`');

/*
 * Since blog posts are now created upon get, they may have no name or seoname, so support NULL
 */
sql_query('ALTER TABLE `blogs_posts` CHANGE COLUMN `name`      `name`      VARCHAR(255) NULL');
sql_query('ALTER TABLE `blogs_posts` CHANGE COLUMN `seoname`   `seoname`   VARCHAR(255) NULL');

sql_query('UPDATE `blogs_posts` SET `name`    = NULL WHERE `name`    = ""');
sql_query('UPDATE `blogs_posts` SET `seoname` = NULL WHERE `seoname` = ""');


/*
 * Since blogs posts can be created by command line and command line no longer supports sessions, createdby may be NULL
 */
sql_query('ALTER TABLE `blogs_posts` CHANGE COLUMN `createdby` `createdby` INT(11) NULL');
sql_query('ALTER TABLE `blogs_media` CHANGE COLUMN `createdby` `createdby` INT(11) NULL');

/*
 * Since we're using 4 byte UTF8, mb4_utf8, we cannot have full sized UNIQUE index (too large).
 * Just place a manual index on a reasonable amount of the seoname
 */
sql_index_exists('blogs_posts', 'seoname', '!ALTER TABLE `blogs_posts` ADD INDEX(`seoname` (80))');

/*
 * key_values store should allow keys up to 32 characters
 */
sql_query('ALTER TABLE `blogs_key_values` CHANGE COLUMN `key`    `key`    VARCHAR(32) NULL');
sql_query('ALTER TABLE `blogs_key_values` CHANGE COLUMN `seokey` `seokey` VARCHAR(32) NULL');

/*
 * Since keywords are always updated upon each blog post update, it doesn't make much sense that it has its own "createdby", it can share it with the blog post.
 */
sql_foreignkey_exists('blogs_keywords', 'fk_blogs_keywords_createdby', 'ALTER TABLE `blogs_keywords` DROP FOREIGN KEY `fk_blogs_keywords_createdby`');
sql_index_exists     ('blogs_keywords', 'createdby'                  , 'ALTER TABLE `blogs_keywords` DROP INDEX  `createdby`');
sql_column_exists    ('blogs_keywords', 'createdby'                  , 'ALTER TABLE `blogs_keywords` DROP COLUMN `createdby`');

/*
 * Add blogs_id to all these tables so its easy (more like possible) to process keyworkds, key_value store and media for entire blogs
 */
sql_column_exists('blogs_media'     , 'blogs_id', '!ALTER TABLE `blogs_media`      ADD COLUMN `blogs_id` INT(11) NOT NULL AFTER `createdby`');
sql_column_exists('blogs_keywords'  , 'blogs_id', '!ALTER TABLE `blogs_keywords`   ADD COLUMN `blogs_id` INT(11) NOT NULL AFTER `createdon`');
sql_column_exists('blogs_key_values', 'blogs_id', '!ALTER TABLE `blogs_key_values` ADD COLUMN `blogs_id` INT(11) NOT NULL AFTER `createdon`');

sql_index_exists ('blogs_media'     , 'blogs_id', '!ALTER TABLE `blogs_media`      ADD INDEX (`blogs_id`)');
sql_index_exists ('blogs_keywords'  , 'blogs_id', '!ALTER TABLE `blogs_keywords`   ADD INDEX (`blogs_id`)');
sql_index_exists ('blogs_key_values', 'blogs_id', '!ALTER TABLE `blogs_key_values` ADD INDEX (`blogs_id`)');

/*
 * scraper_urls table will now support targets_id, so
 */
sql_column_exists('scraper_urls', 'targets_id', '!ALTER TABLE `scraper_urls` ADD COLUMN `targets_id` INT(11) NULL');
?>
