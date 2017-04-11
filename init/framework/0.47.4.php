<?php
/*
 * Add "level" to blogs posts, which will be used as previous priority column
 */
sql_column_exists('blogs_posts', 'level', '!ALTER TABLE `blogs_posts` ADD COLUMN `level` INT(11) NOT NULL AFTER `priority`');
sql_index_exists ('blogs_posts', 'level', '!ALTER TABLE `blogs_posts` ADD INDEX  `level` (`level`)');


sql_query('ALTER TABLE `blogs_posts` CHANGE COLUMN `priority` `priority` INT(11) NOT NULL');
sql_index_exists ('blogs_posts', 'priority', 'ALTER TABLE `blogs_posts` DROP KEY `priority`');

/*
 * Ensure that all priorities are unique per blog
 */
$blogs  = sql_query('SELECT `id`, `name` FROM `blogs`');
$update = sql_prepare('UPDATE `blogs_posts` SET `priority` = :priority WHERE `id` = :id');

while($blog = sql_fetch($blogs)){
    cli_log(tr('Updating priorities for blog ":blog"', array(':blog' => $blog['name'])));

    $priority = 1;
    $posts    = sql_query('SELECT `id`, `name` FROM `blogs_posts` WHERE `blogs_id` = :blogs_id ORDER BY `createdon` ASC', array(':blogs_id' => $blog['id']));

    while($post = sql_fetch($posts)){
        cli_dot(1);
        $update->execute(array(':id'       => $post['id'],
                               ':priority' => $priority++));
    }

    cli_dot(false);
}

sql_query('ALTER TABLE `blogs_posts` ADD UNIQUE KEY `priority` (`priority`, `blogs_id`)');
?>
