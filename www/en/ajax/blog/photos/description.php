<?php
include_once(dirname(__FILE__).'/../../../libs/startup.php');

try{
    load_libs('admin,json,blogs');

    if(empty($_POST['id'])){
        throw new bException(tr('No photo specified'));
    }

    $user = rights_or_redirect('admin', '/admin/signin.php', 'json');

    blogs_photo_description($user, $_POST['id'], isset_get($_POST['desc'], ''));
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
            json_error(tr('You cannot change this photo description, it is not yours'));
            break;

        default:
            json_error(tr('Something went wrong, please try again'));
    }
}
?>
