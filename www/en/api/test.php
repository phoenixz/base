<?php
/*
 * Webdom API
 */
require_once(__DIR__.'/../libs/startup.php');
load_libs('json');



/*
 * Process requests
 */
try{
    switch(isset_get($_GET['method'])){
        case 'auth':
            json_reply(array('token' => json_authenticate(isset_get($_GET['key']))));
            break;

        case 'close':
            json_stop_session(isset_get($_GET['token']));
            json_reply();
            break;

        case 'get_message':
            json_start_session(isset_get($_GET['token']));
            json_reply(array('email'   => 'info@capmega.com',
                             'message' => 'This is just a test reply'));
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
