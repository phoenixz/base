<?php
/*
 * Fix various possible blog DB issues
 */
sql_query('DELETE      `blogs_posts`
           FROM        `blogs_posts`

           LEFT JOIN   `blogs_posts` AS `siblings`
           ON          `siblings`.`masters_id` = `blogs_posts`.`id`

           WHERE       `blogs_posts`.`masters_id` IS NOT NULL
           AND         `siblings`.`masters_id`    IS NULL');

$delete = sql_prepare('DELETE FROM `blogs_media` WHERE `id` = :id');
$files  = sql_query('SELECT `blogs_media`.`id`,
                            `blogs_media`.`file`

                     FROM   `blogs_media`

                     JOIN   `blogs_posts`
                     ON     `blogs_posts`.`id`     = `blogs_media`.`blogs_posts_id`

                     WHERE  `blogs_posts`.`status` = "_new"
                     AND    `blogs_posts`.`masters_id` IS NULL');

if($files->rowCount()){
    While($file = sql_fetch($files)){
        cli_dot();
        file_delete($file['file']);
        $delete->execute(array(':id' => $file['id']));
    }

    cli_dot(false);
}

sql_query('DELETE FROM `blogs_posts` WHERE `status` = "_new" AND `masters_id` IS NULL');
sql_query('UPDATE `blogs_posts` SET `masters_id` = `id` WHERE `masters_id` IS NULL');
?>
