<?php
include_once(dirname(__FILE__).'/../../../libs/startup.php');

try{
    load_libs('admin,json,file,image,upload,blogs');

    $user   = right_or_redirect('admin', '/admin/signin.php', 'json');
    $result = blogs_photos_upload($_FILES['files'], $_POST);

    json_reply(array('html' => '<div class="blogpost photo" id="photo'.$result['id'].'">
                                    <img src="'.blogs_photo_url($result['photo'], true).'" />
                                    <textarea class="blogpost photo description" placeholder="'.tr('Description of this photo').'"></textarea>
                                    <a class="blogpost photo delete button">'.tr('Delete this photo').'</a><br />
                                </div>'));

}catch(Exception $e){
    switch($e->getCode()){
        case 'unknown':
            json_error(tr('Unknown blog post "'.str_log(isset_get($_POST['id'])).'" specified'));
            break;

        case 'notspecified':
            json_error(tr('No blog post specified'));
            break;

        case 'accessdenied':
            json_error(tr('You cannot upload a photo to this blog post, the post is not yours'));
            break;

        default:
            json_error(tr('Something went wrong, please try again'));
    }
}
?>
