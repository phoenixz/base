<?php
/*
 * Default characterset and collations have changed. Ensure that database and tables follow current settings
 */
sql_column_exists('blogs_posts', 'parents_id', '!ALTER TABLE `blogs_posts` ADD COLUMN `parents_id` INT(11) NULL;');
sql_index_exists ('blogs_posts', 'parents_id', '!ALTER TABLE`blogs_posts` ADD INDEX (`parents_id`);');

sql_foreignkey_exists('blogs_posts', 'fk_blogs_posts_parents_id' , '!ALTER TABLE `blogs_posts` ADD CONSTRAINT `fk_blogs_posts_parents_id` FOREIGN KEY (`parents_id`) REFERENCES `blogs_posts` (`id`) ON DELETE RESTRICT;');
?>
