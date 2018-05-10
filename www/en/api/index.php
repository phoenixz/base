<?php
/*
 * Webdom API
 *
 * auth:        http://api.escortdetectives.com.l.capmega.com/auth/hg56h7j56iujew45tyj57
 * close:       http://api.escortdetectives.com.l.capmega.com/get_message/hg56h7j56iujew45tyj57/op15qgko25jua9r6chpdp66ek4
 * get_message: http://api.escortdetectives.com.l.capmega.com/get_message/hg56h7j56iujew45tyj57/op15qgko25jua9r6chpdp66ek4
 * add_escort:  http://api.escortdetectives.com.l.capmega.com/get_message/hg56h7j56iujew45tyj57/op15qgko25jua9r6chpdp66ek4
 */
require_once(__DIR__.'/../libs/startup.php');
load_libs('json');

/*
 * Process requests
 */
try{
    switch(isset_get($_GET['method'])){
        case 'auth':
            json_reply(array('token' => json_authenticate(isset_get($_GET['PHPSESSID']))));
            break;

        case 'test':
            json_start_session();
            json_reply();
            break;

        case 'close':
            json_stop_session();
            json_reply();
            break;

        case 'get_message':
            json_start_session();
            json_reply(array('email'   => 'info@capmega.com',
                             'message' => 'This is just a test reply'));
            break;

        case 'add_listing':
            json_start_session();
            load_libs('escorts');
            //add listing
            break;

        case 'add_media':
            //add photos & video
            break;

        case '':
            throw new bException(tr('No "method" specified'), 'not-specifed');

        default:
            throw new bException(tr('Unknown method ":method" specified', array(':method' => $_GET['method'])), 'not-specifed');
    }

}catch(Exception $e){
    json_error($e);
}
?>
