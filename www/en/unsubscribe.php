<?php
require_once(dirname(__FILE__).'/libs/startup.php');

html_load_css();
html_load_js();

if(empty($_GET['code'])){
    /*
     * A code must ALWAYS be specified!
     */
    redirect();
}

if(empty($_GET['confirm'])){
    $html = tr('If you wish to unsubscribe from ###DOMAIN###, please click <a href="###HERE###">HERE</a>', array('###HERE###', '###DOMAIN###'), array(current_domain('/unsubscribe.php?code='.$_GET['code'].'&confirm=yes'), $_CONFIG['domain']));

}else{
    try{
        load_libs('mailer,html');

        $recipient = mailer_get_recipient($_GET['code']);

        mailer_unsubscribe($recipient['users_id']);

        html_flash_set(tr('You have been unsubscribed from base project.'));
        redirect();

    }catch(Exception $e){
showdie($e);
        html_flash_set(tr('We\'re sorry, something went wrong and we could not unsubscribe you at this time. Please try again later'), 'error');
        redirect();
    }
}


$params = array('title'       => 'Base | Unsubscribe');
$meta   = array('description' => 'Unsubscribe from the base project',
                'keywords'    => 'base,unsubscribe');

echo html_header($params, $meta).
     $html.
     html_footer();
?>
