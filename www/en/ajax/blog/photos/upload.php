<?php
include_once(dirname(__FILE__).'/../../../libs/startup.php');

try{
    load_libs('admin,json,file,image,upload,blogs');

    $user  = rights_or_access_denied('admin', '/admin/signin.php', 'json');
    $photo = blogs_photos_upload($_FILES['files'], $_POST);

    /*
     * Get image dimensions
     */
    try{
        $image = getimagesize(ROOT.'www/en/photos/'.$photo['photo'].'_big.jpg');

    }catch(Exception $e){
        $image = false;
    }

    if(!$image){
        $image = array(tr('Invalid image'), tr('Invalid image'));
    }

    json_reply(array('html' => '<div class="form-group photo" id="photo'.$photo['id'].'">
                                    <a target="_blank" href="'.blogs_photo_url($photo['photo'], 'small').'">
                                        <img class="col-md-1 control-label" src="'.blogs_photo_url($photo['photo'], 'small').'" />
                                    </a>
                                    <div class="col-md-11 blogpost">
                                        <textarea class="blogpost photo description form-control" placeholder="'.tr('Description of this photo').'"></textarea>
                                        <p>
                                            (Dimensions '.$image[0].' X '.$image[1].')
                                            <a <a class="mb-xs mt-xs mr-xs btn btn-primary blogpost photo up button">'.tr('Up').'</a>
                                            <a <a class="mb-xs mt-xs mr-xs btn btn-primary blogpost photo down button">'.tr('Down').'</a>
                                            <a <a class="mb-xs mt-xs mr-xs btn btn-primary blogpost photo delete button">'.tr('Delete this photo').'</a>
                                        </p>
                                    </div>
                                </div>'));

}catch(Exception $e){
    switch($e->getCode()){
        case UPLOAD_ERR_INI_SIZE:
            // FALLTHROUGH
        case UPLOAD_ERR_FORM_SIZE:
            json_error(tr('Uploaded image is too big'));

        case UPLOAD_ERR_NO_FILE:
            json_error(tr('No image was uploaded'));

        case UPLOAD_ERR_PARTIAL:
            // FALLTHROUGH
        case UPLOAD_ERR_NO_TMP_DIR:
            // FALLTHROUGH
        case UPLOAD_ERR_CANT_WRITE:
            // FALLTHROUGH
        case UPLOAD_ERR_EXTENSION:
            json_error(tr('Error, please try again later "'.str_log(isset_get($_POST['id'])).'" specified'));

        case 'unknown':
            json_error(tr('Unknown blog post "'.str_log(isset_get($_POST['id'])).'" specified'));

        case 'notspecified':
            json_error(tr('No blog post specified'));

        case 'accessdenied':
            json_error(tr('You cannot upload a photo to this blog post, the post is not yours'));

        default:
            json_error(tr('Something went wrong, please try again'));
    }
}
?>
