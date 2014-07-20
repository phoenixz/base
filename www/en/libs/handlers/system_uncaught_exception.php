<?php
/*
 * This is the uncaught exception handler
 */
static $count, $code, $messages;

/*
 * Count the # of uncaught exceptions.
 * A second one would be caused by the handling of a previous uncaught exception
 */
$count++;

try{
    if(!defined('PLATFORM') or !defined('ENVIRONMENT')){
        if(!defined('LANGUAGE')){
            error_log('No language detected');
            echo "No language detected\n";

        }else{
            error_log('No platform or environment detected');
            echo "No platform or environment detected\n";
        }

        if($die){
            die($die);
        }
    }

    require_once(dirname(__FILE__).'/../debug.php');

    $code = 'error/'.$e->getCode();

    if($e instanceof lsException){
        $messages = $e->getMessages();

    }else{
        $messages = array($e->getMessage());
    }

    if($e->getCode() === 'alreadyrunning'){
// :TODO: Normally we SHOULD show something, no? maybe make it configurable by command line?
        die();
    }

    if($count > 1){
        if(ENVIRONMENT == 'production'){
            notify('error', "UNCAUGHT EXCEPTION [".$code."]\n".implode("\n", $messages));
        }
    }

    if((PLATFORM != 'shell') and (ENVIRONMENT == 'production')){
// :TODO:SVEN:20130717: Add notifications!
        page_maintenance('Uncaught Exception "'.str_log($code).'" with message "'.$e->getMessage().'"', false, $e);

    }else{
        log_screen('* '.tr('Uncaught exception').' *', $code);

        if($e instanceof lsException){
            foreach($messages as $key => $message){
                log_screen($key.': '.$message, $code);
            }

        }else{
            log_screen('0: '.$e->getMessage(), $code);
        }


        if(($e instanceof lsException) and ($data = $e->getData())){
            show('');
            show('ERROR DATA:');

            if(is_scalar($data)){
                show('[SCALAR] '.$data, false, true);

            }elseif(is_array($data) and (count($data) < 50)){
                show($data, false, true);

            }else{
                load_libs('json');
                show(json_encode_custom($data), false, true);
            }
        }

        if($die){
            die($die);
        }
    }

}catch(Exception $e){
    if(function_exists('show')){
        show('uncaught_exception(): Failed');
        showdie($e);

    }else{
        try{
            notify('error', "UNCAUGHT EXCEPTION HANDLING FAILED[".$e->getCode()."]\n".implode("\n", $e->getMessages()));

        }catch(Exception $e){
            /*
             * Ahw fuck it.. Notifications failed as well
             */
        }

        if(ENVIRONMENT == 'production'){
            log_screen(tr('Maintenance, we will be right back!'), 'uncaughtexception', 'red');

        }else{
            echo "uncaught_exception(): Failed\n";
            echo "<pre>\n";
            print_r($e);
        }

        die();
    }
}
?>
