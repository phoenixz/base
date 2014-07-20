<?php
/*
 * Add SEOkeywords to blogs to avoid extra queries
 */
sql_column_exists('blogs_posts', 'seokeywords', '!ALTER TABLE `blogs_posts` ADD COLUMN `seokeywords` VARCHAR(255) NOT NULL AFTER `keywords`');

/*
 * Fix blogs photos table
 */
sql_foreignkey_exists ('blogs_images', 'fk_blogs_images_createdby'     ,  'ALTER TABLE `blogs_images` DROP FOREIGN KEY `fk_blogs_images_createdby`');
sql_foreignkey_exists ('blogs_images', 'fk_blogs_images_blogs_posts_id',  'ALTER TABLE `blogs_images` DROP FOREIGN KEY `fk_blogs_images_blogs_posts_id`');

sql_table_exists ('blogs_images', 'RENAME TABLE `blogs_images` TO `blogs_photos`');
sql_column_exists('blogs_photos', 'url', 'ALTER TABLE `blogs_photos` CHANGE COLUMN `url` `file` VARCHAR(255) NOT NULL');

sql_foreignkey_exists('blogs_photos', 'fk_blogs_photos_createdby'      , '!ALTER TABLE `blogs_photos` ADD CONSTRAINT `fk_blogs_photos_createdby`      FOREIGN KEY (`createdby`)      REFERENCES `users`       (`id`) ON DELETE RESTRICT;');
sql_foreignkey_exists('blogs_photos', 'fk_blogs_photos_blogs_posts_id' , '!ALTER TABLE `blogs_photos` ADD CONSTRAINT `fk_blogs_photos_blogs_posts_id` FOREIGN KEY (`blogs_posts_id`) REFERENCES `blogs_posts` (`id`) ON DELETE RESTRICT;');
?>
