<?php
global $_CONFIG;

try{
    if(!debug()){
        return $data;
    }

    $retval = '';

    if(PLATFORM == 'http'){
        http_headers(200, 0);
    }

    if($_CONFIG['production']){
        if(!debug()){
            return '';
        }

// :TODO:SVEN:20130430: This should NEVER happen, send notification!
    }

    if((PLATFORM == 'http') and empty($GLOBALS['debug_plain'])){
        /*
         * If JSON, CORS requests require correct headers!
         */
        if(!empty($GLOBALS['page_is_ajax'])){
            load_libs('http');
            http_headers(null, 0);
        }

        echo debug_html($data, tr('Unknown'), $trace_offset);

    }else{
        if(is_scalar($data)){
            $retval .= tr('DEBUG SHOW (%file%@%line%) ', array('%file%' => current_file($trace_offset), '%line%' => current_line($trace_offset))).$data."\n";

        }else{
            /*
             * Sort if is array for easier reading
             */
            if(is_array($data)){
                ksort($data);
            }

            $retval .= tr('DEBUG SHOW (%file%@%line%) ', array('%file%' => current_file($trace_offset), '%line%' => current_line($trace_offset)))."\n";
            $retval .= print_r($data, true);
            $retval .= "\n";
        }
    }

    echo $retval;
    return $data;

}catch(Exception $e){
    if($_CONFIG['production'] or debug()){
        /*
         * Show the error message with a conventional die() call
         */
        die($e->getMessage());
    }

    try{
        error_log(tr('show() failed with exception ":exception"', array(':exception' => $e->getMessage())));
        notify('show-exception', tr('show() failed with exception ":exception"', array(':exception' => $e->getMessage())), 'developers');

    }catch(Exception $e){
        /*
         * Sigh, if notify and error_log failed as well, then there is little to do but go on
         */

    }

    return '';
}
?>