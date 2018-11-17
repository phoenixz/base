<?php
global $core;

if($code === 'missing-module'){
    if($data === 'mb'){
        /*
         * VERY low level exception, the multibyte module is not installed. Die directly @startup
         */
        die($messages);
    }
}

$messages = array_force($messages, "\n");

if(is_object($code)){
    /*
     * Specified code is not a code but a previous exception. Get
     * history from previous exception and add new exception message
     */
    $e    = $code;
    $code = null;

    if($e instanceof bException){
        $this->messages = $e->getMessages();
        $this->data     = $e->getData();

    }else{
        if(!($e instanceof Exception)){
            throw new bException(tr('bException: Specified exception object for exception ":message" is not valid (either it is not an object or not an exception object)', array(':message' => $messages)), 'invalid');
        }

        $this->messages[] = $e->getMessage();
    }

    $orgmessage = $e->getMessage();
    $code       = $e->getCode();

}else{
    if(!is_scalar($code)){
        throw new bException(tr('bException: Specified exception code ":code" for exception ":message" is not valid (should be either scalar, or an exception object)', array(':code' => $code, ':message' => $messages)), 'invalid');
    }

    $orgmessage = reset($messages);
    $this->data = $data;
}

if(!$messages){
    throw new Exception(tr('bException: No exception message specified in file ":file" @ line ":line"', array(':file' => current_file(1), ':line' => current_line(1))));
}

if(!is_array($messages)){
    $messages = array($messages);
}

// :DELETE: Exceptions should only logged if uncaught, since only those matter. Caught exceptions have been handled by the system already
//        try{
//            /*
//             * Only log to file if core is available and config_ok (configuration is loaded correclty)
//             */
//            if(!empty($core) and !empty($core->register['ready'])){
//                foreach($messages as $message){
//                    log_file($message, 'exceptions');
//                }
//            }
//
//        }catch(Exception $f){
//            /*
//             * Exception database logging failed. Ignore, since from here on there is little to do
//             */
//
//// :TODO: Add notifications!
//        }

parent::__construct($orgmessage, null);
$this->code = (string) $code;

/*
 * If there are any more messages left, then add them as well
 */
if($messages){
    foreach($messages as $id => $message){
        $this->messages[] = $message;
    }
}
?>