<?php
/*
 * Fix group / category structures
 * Ass "assigned_to_id" column which allows the post to be assigned to users
 */
sql_column_exists     ('blogs_posts', 'seogroup'                     , '!ALTER TABLE `blogs_posts` ADD COLUMN `seogroup`    VARCHAR(64) NUll AFTER `category`');
sql_column_exists     ('blogs_posts', 'seocategory'                  , '!ALTER TABLE `blogs_posts` ADD COLUMN `seocategory` VARCHAR(64) NUll AFTER `blogs_id`');

sql_index_exists      ('blogs_posts', 'seogroup'                     , '!ALTER TABLE `blogs_posts` ADD  INDEX (`seogroup`)');
sql_index_exists      ('blogs_posts', 'seocategory'                  , '!ALTER TABLE `blogs_posts` ADD  INDEX (`seocategory`)');

sql_foreignkey_exists ('blogs_posts', 'fk_blogs_posts_group'         , '!ALTER TABLE `blogs_posts` ADD CONSTRAINT `fk_blogs_posts_group`    FOREIGN KEY (`seogroup`)    REFERENCES `blogs_categories` (`seoname`) ON DELETE RESTRICT;');
sql_foreignkey_exists ('blogs_posts', 'fk_blogs_posts_category'      , '!ALTER TABLE `blogs_posts` ADD CONSTRAINT `fk_blogs_posts_category` FOREIGN KEY (`seocategory`) REFERENCES `blogs_categories` (`seoname`) ON DELETE RESTRICT;');

sql_foreignkey_exists ('blogs_posts', 'fk_blogs_posts_groups_id'     ,  'ALTER TABLE `blogs_posts` DROP FOREIGN KEY `fk_blogs_posts_groups_id`');
sql_foreignkey_exists ('blogs_posts', 'fk_blogs_posts_categories_id' ,  'ALTER TABLE `blogs_posts` DROP FOREIGN KEY `fk_blogs_posts_categories_id`');

sql_index_exists      ('blogs_posts', 'group'                        ,  'ALTER TABLE `blogs_posts` DROP INDEX  `group`');
sql_index_exists      ('blogs_posts', 'category'                     ,  'ALTER TABLE `blogs_posts` DROP INDEX  `category`');

sql_index_exists      ('blogs_posts', 'groups_id'                    ,  'ALTER TABLE `blogs_posts` DROP INDEX  `groups_id`');
sql_index_exists      ('blogs_posts', 'categories_id'                ,  'ALTER TABLE `blogs_posts` DROP INDEX  `categories_id`');

sql_column_exists     ('blogs_posts', 'groups_id'                    ,  'ALTER TABLE `blogs_posts` DROP COLUMN `groups_id`');
sql_column_exists     ('blogs_posts', 'categories_id'                ,  'ALTER TABLE `blogs_posts` DROP COLUMN `categories_id`');

sql_column_exists     ('blogs_posts', 'assigned_to_id'               , '!ALTER TABLE `blogs_posts` ADD COLUMN `assigned_to_id` INT(11) NUll AFTER `blogs_id`');
sql_index_exists      ('blogs_posts', 'assigned_to_id'               , '!ALTER TABLE `blogs_posts` ADD  INDEX (`assigned_to_id`)');
sql_foreignkey_exists ('blogs_posts', 'fk_blogs_posts_assigned_to_id', '!ALTER TABLE `blogs_posts` ADD CONSTRAINT `fk_blogs_posts_assigned_to_id` FOREIGN KEY (`assigned_to_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT;');

sql_column_exists     ('blogs_categories', 'assigned_to_id'                          , '!ALTER TABLE `blogs_categories` ADD COLUMN `assigned_to_id` INT(11) NUll AFTER `blogs_id`');
sql_index_exists      ('blogs_categories', 'assigned_to_id'                          , '!ALTER TABLE `blogs_categories` ADD  INDEX (`assigned_to_id`)');
sql_foreignkey_exists ('blogs_categories', 'fk_blogs_categories_posts_assigned_to_id', '!ALTER TABLE `blogs_categories` ADD CONSTRAINT `fk_blogs_categories_posts_assigned_to_id` FOREIGN KEY (`assigned_to_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT;');



/*
 * Ensure correct seogroup / seocategory
 */
$count = 0;
$r     = sql_query('SELECT `id`, `group`, `category` FROM `blogs_posts`');
$p     = sql_prepare('UPDATE `blogs_posts` SET `seogroup` = :seogroup, `seocategory` = :seocategory WHERE `id` = :id');

while($post = sql_fetch($r)){
    if($count++ > 10){
        $count = 0;
        log_console('.', '', 'green', false);
    }

    $seogroup    = sql_get('SELECT `seoname` FROM `blogs_categories` WHERE `name` = :name', 'seoname', array(':name' => $post['group']));
    $seocategory = sql_get('SELECT `seoname` FROM `blogs_categories` WHERE `name` = :name', 'seoname', array(':name' => $post['category']));

    $p->execute(array(':id'          => $post['id'],
                      ':seogroup'    => $seogroup,
                      ':seocategory' => $seocategory));
}

log_console('Done', '', 'green');
?>
