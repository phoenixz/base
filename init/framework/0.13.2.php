<?php
/*
 * Add blogs_id to blogs_keywords
 */
sql_column_exists    ('blogs_keywords', 'blogs_id'                  , '!ALTER TABLE `blogs_keywords` ADD COLUMN `blogs_id` INT(11) NULL AFTER `createdby`');
sql_index_exists     ('blogs_keywords', 'blogs_id'                  , '!ALTER TABLE `blogs_keywords` ADD INDEX(`blogs_id`)');
sql_foreignkey_exists('blogs_keywords', 'fk_blogs_keywords_blogs_id', '!ALTER TABLE `blogs_keywords` ADD CONSTRAINT `fk_blogs_keywords_blogs_id` FOREIGN KEY (`blogs_id`) REFERENCES `blogs` (`id`) ON DELETE CASCADE;');



/*
 *
 */
sql_column_exists('blogs_posts', 'featured_until', '!ALTER TABLE `blogs_posts` ADD COLUMN `featured_until` DATETIME NULL AFTER `seokeywords`');
sql_index_exists ('blogs_posts', 'featured_until', '!ALTER TABLE `blogs_posts` ADD INDEX(`featured_until`)');

sql_column_exists('blogs_posts', 'upvotes'  , '!ALTER TABLE `blogs_posts` ADD COLUMN `upvotes`   INT(11) NOT NULL AFTER `featured_until`');
sql_index_exists ('blogs_posts', 'upvotes'  , '!ALTER TABLE `blogs_posts` ADD INDEX (`upvotes`)');
sql_column_exists('blogs_posts', 'downvotes', '!ALTER TABLE `blogs_posts` ADD COLUMN `downvotes` INT(11) NOT NULL AFTER `upvotes`');
sql_index_exists ('blogs_posts', 'downvotes', '!ALTER TABLE `blogs_posts` ADD INDEX (`downvotes`)');

sql_column_exists('blogs_posts', 'comments' , '!ALTER TABLE `blogs_posts` ADD COLUMN `comments` INT(11) NOT NULL AFTER `downvotes`');
sql_index_exists ('blogs_posts', 'comments' , '!ALTER TABLE `blogs_posts` ADD INDEX (`comments`)');


/*
 * Setup the correct blogs_id for each keyword
 */
log_console('Fixing blogs_id for `blogs_keywords` table', '');

$count = 0;
$r     = sql_query('SELECT `blogs_keywords`.`id`,
                           `blogs_posts`.`blogs_id`

                    FROM      `blogs_keywords`

                    LEFT JOIN `blogs_posts`
                    ON        `blogs_posts`.`id` = `blogs_keywords`.`blogs_posts_id`');

while($keyword = sql_fetch($r)){
    sql_query('UPDATE `blogs_keywords`

               SET    `blogs_keywords`.`blogs_id` = :blogs_id

               WHERE  `blogs_keywords`.`id`       = :id',

               array(':id'       => $keyword['id'],
                     ':blogs_id' => $keyword['blogs_id']));

    if($count++ > 5){
        log_console('.', '', 'green', false);
    }
}

sql_query('ALTER TABLE `blogs_keywords` MODIFY COLUMN `blogs_id` INT(11) NOT NULL');

log_console('Done', '');
?>
