<?php
global $_CONFIG, $core;

try{
    if($trace_offset === null){
        if(PLATFORM_HTTP){
            $trace_offset = 3;

        }else{
            $trace_offset = 2;
        }
    }

    if(!debug()){
        return $data;
    }

    /*
     * First cleanup data
     */
    if(is_array($data)){
        $data = array_hide($data, 'GLOBALS,%password,ssh_key');
    }

    $retval = '';

    if(PLATFORM_HTTP){
        http_headers(200, 0);
    }

    if($_CONFIG['production']){
        if(!debug()){
            return '';
        }

// :TODO:SVEN:20130430: This should NEVER happen, send notification!
    }

    if(PLATFORM_HTTP){
        if(empty($core->register['debug_plain'])){
            switch($core->callType()){
                case 'api':
                    // FALLTHROUGH
                case 'ajax':
                    /*
                     * If JSON, CORS requests require correct headers!
                     */
                    http_headers(null, 0);

                    echo "\n".tr('DEBUG SHOW (:file@:line) ', array(':file' => current_file($trace_offset - 1), ':line' => current_line($trace_offset - 1)))."\n";
                    print_r($data)."\n";
                    break;

                default:
                    echo debug_html($data, tr('Unknown'), $trace_offset);
            }

        }else{
            echo "\n".tr('DEBUG SHOW (:file@:line) ', array(':file' => current_file($trace_offset), ':line' => current_line($trace_offset)))."\n";
            print_r($data)."\n";
        }

    }else{
        if(is_scalar($data)){
            $retval .= ($quiet ? '' : tr('DEBUG SHOW (:file@:line) ', array(':file' => current_file($trace_offset), ':line' => current_line($trace_offset)))).$data."\n";

        }else{
            /*
             * Sort if is array for easier reading
             */
            if(is_array($data)){
                ksort($data);
            }

            if(!$quiet){
                $retval .= tr('DEBUG SHOW (:file@:line) ', array(':file' => current_file($trace_offset), ':line' => current_line($trace_offset)))."\n";
            }

            $retval .= print_r(variable_zts_safe($data), true);
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