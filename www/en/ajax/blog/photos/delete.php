<?php
include_once(dirname(__FILE__).'/../../../libs/startup.php');

try{
    load_libs('admin,json,file,upload');

    $user = right_or_redirect('admin', '/signin.php', 'json');

    if(empty($_POST['id'])){
        throw new bException('ajax/blog/photos/delete: No photo specified', 'notspecified');
    }

    $photo   = sql_get('SELECT `blogs_photos`.`id`, `blogs_photos`.`file`

                        FROM   `blogs_photos`

                        JOIN   `blogs_posts`
                        ON     `blogs_posts`.`id`        = `blogs_photos`.`blogs_posts_id`

                        WHERE  `blogs_photos`.`id`       = '.cfi($_POST['id']));

    if(empty($photo['id'])){
        throw new bException('ajax/blog/photos/delete: Unknown photo_id "'.str_log($_POST['id']).'" specified', 'unknown');
    }

    if(($photo['createdby'] != $_SESSION['user']['id']) and !has_right('god')){
        throw new bException('ajax/blog/photos/delete: This photo does not belong to you.', 'accessdenied');
    }

    sql_query('DELETE FROM `blogs_photos`
               WHERE       `id` = :id',

              array(':id' => cfi($photo['id'])));

    /*
     * Delete files
     */
    file_clear_path(ROOT.'www/photos/'.$photo['file'].'_small.jpg');
    file_clear_path(ROOT.'www/photos/'.$photo['file'].'_big.jpg');
    json_reply();

}catch(Exception $e){
    switch($e->getCode()){
        case 'unknown':
            json_error(tr('Unknown photo id specified'));
            break;

        case 'notspecified':
            json_error(tr('No photo id specified'));
            break;

        case 'accessdenied':
            json_error(tr('You cannot delete this photo, it is not yours'));
            break;

        default:
            json_error(tr('Something went wrong, please try again'));
    }
}
?>
