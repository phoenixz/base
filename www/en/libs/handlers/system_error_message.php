<?php
/*
 * Handler code for error_message() function
 */
try{
    /*
     * Set some default message codes
     */
    array_params($messages);
    array_default($messages, 'validation', $e);
    array_default($messages, 'captcha'   , $e);

    if(debug()){
        if($e instanceof bException){
            return $e->getMessages();
        }

        if($e instanceof Exception){
            return $e->getMessage();
        }

        throw new bException(tr('error_message(): Specified $e is not an exception object'), 'invalid');

    }elseif(empty($messages[$e->getCode()])){
        if(!$default){
            return tr('Something went wrong, please try again');
        }

        return $default;
    }

    return $messages[$e->getCode()];

}catch(Exception $e){
    throw new bException('error_message(): Failed', $e);
}
?>
