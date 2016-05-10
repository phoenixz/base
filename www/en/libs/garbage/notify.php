<?php
/*
 *
 *
 * Written and Copyright by Sven Oostenbrink
 */


/*
 * Send error notifications
 */
function notifyerror($message, $code = 'unknown'){
    global $_CONFIG;
return;

    try{
        notify($_CONFIG['domain']['www']. ' ERROR', $message, -1, $_CONFIG['notifications']['error']);

    }catch(bException $e){
        /*
         * Notification failed
         */
        throw new bException('notifyerror(): Failed', $e);
    }
}



/*
 * Notify specified group of people
 */
function notify($group, $subject, $message){
    global $_CONFIG;

    if($group == 'all'){
        $developers = array();

        foreach($_CONFIG['notifications'] as $group){
            $developers = array_merge($developers, $group);
        }

    }else{
        $developers = $_CONFIG['notifications']['developers'];
    }

    array_unique($developers);

    /*
     * Send notifications
     */
    foreach($developers as $email){
        mail($email, $subject, $message, "From: estasuper@estasuper.com\r\nReply-To: noreply@estasuper.com");
    }
}



/*
 * Send notifications to specified destinations
 */
function notify($subject, $message, $code = 'unknown', $destinations = null){
    global $_CONFIG;
return;
    try{
        if(isset($GLOBALS['debug']) and $GLOBALS['debug']){
            flasherror($message);
            die('DIED');
        }

        if($code == 'nonotify'){
            return;
        }

        foreach($destinations as $user => $methods){
            foreach($methods as $method){
                switch($method){
                    case 'email':
                        notifymail($_CONFIG['notifications']['list'][$user][$method], $subject, $message);
                        break;

                    case 'prowl':
                        notify_prowl($user, $subject, $message, $priority);
                        break;

                    default:
                        throw new isException('notify(): Unknown notification method "'.$method. '" specified', 'nonotify', $e);
                }

            }
        }

    }catch(bException $e){
        /*
         * Notification failed
         */
        throw new isException('notify(): Failed', $e);
    }
}



/*
 * Send a prowl notification
 */
function notify_prowl($user, $subject, $message, $priority){
    global $_CONFIG;
    static $prowl;

    try{
        include_once('class.php-prowl.php');

        if($prowl){
            $prowl = new Prowl();
            $prowl->setDebug($GLOBALS['debug']);
            $prowl->setApiKey($_CONFIG['notifications']['list'][$user]['prowl']);
        }


        $application = $_CONFIG['project'];
        $url         = $_CONFIG['domain']['www'];

        return $prowl->add($application, $subject, $priority, $description, $url);

    }catch(bException $e){
        throw new bException('notify_prowl(): Failed', $e);
    }
}



/*
 * Send email
 */
function notifymail($user, $subject, $message, $priority){
    try{
        global $_CONFIG;

        $user = $_CONFIG['notifications']['list'][$user]['email'];

        mail($user, $subject, $message);

    }catch(bException $e){
        throw new bException('notifymail(): Failed', $e);
    }
}


?>
