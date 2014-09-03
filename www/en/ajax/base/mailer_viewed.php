<?php
    require_once(dirname(__FILE__).'/../../libs/startup.php');

    try{
        if(empty($_GET['code'])){
            throw new bException('ajax/base/mailer_access: No code specified');
        }

        load_libs('image,mailer');
        image_send(ROOT.'/pub/img/'.mailer_viewed($_GET['code']));

    }catch(Exception $e){
        page_404('html');
    }
?>
