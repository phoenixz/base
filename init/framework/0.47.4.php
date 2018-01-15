<?php
/*
 * Fixed blog_media hash column
 * Add "level" to blogs posts, which will be used as previous priority column
 */
sql_query('ALTER TABLE `blogs_media` CHANGE COLUMN `hash` `hash` VARCHAR(64) NOT NULL');

$medias = sql_query('SELECT `id`, `file` FROM `blogs_media`');
$update = sql_prepare('UPDATE `blogs_media` SET `hash` = :hash WHERE `id` = :id');

log_console(tr('Updating all blog media hash values. This might take a little while. NOTE: Each following dot represents one file'));

while($media = sql_fetch($medias)){
    if(empty($media['file'])) continue;
    cli_dot(1);

    $hash = '';
    $file = ROOT.'data/content/photos/'.$media['file'].'-original.jpg';

    if(file_exists($file)){
        $hash = hash('sha256', $file);
    }

    if($hash){
        $update->execute(array(':id'   => $media['id'],
                               ':hash' => $hash));
    }
}

cli_dot(false);


sql_column_exists('blogs_posts', 'level', '!ALTER TABLE `blogs_posts` ADD COLUMN `level` INT(11) NOT NULL AFTER `priority`');
sql_index_exists ('blogs_posts', 'level', '!ALTER TABLE `blogs_posts` ADD INDEX  `level` (`level`)');

sql_query('ALTER TABLE `blogs_posts` CHANGE COLUMN `priority` `priority` INT(11) NOT NULL');
sql_index_exists ('blogs_posts', 'priority', 'ALTER TABLE `blogs_posts` DROP KEY `priority`');

sql_query('UPDATE `blogs_posts` SET `level` = `priority`');

/*
 * Ensure that all priorities are unique per blog
 */
$blogs  = sql_query('SELECT `id`, `name` FROM `blogs`');
$update = sql_prepare('UPDATE `blogs_posts` SET `priority` = :priority WHERE `id` = :id');

while($blog = sql_fetch($blogs)){
    log_console(tr('Updating priorities for blog ":blog"', array(':blog' => $blog['name'])));

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
