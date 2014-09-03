<?php
try{
    $command = mb_trim($command);

    if(substr($command, -1, 1) == '&'){
        /*
         * Background commands cannot use "exec()" because that one will always wait for the exit code
         */
        $lastline = exec('> /dev/null '.substr($command, 0, -1).' 2>/dev/null &', $output, $exitcode);

    }else{
        $lastline = exec($command.($route_errors ? ' 2>&1' : ''), $output, $exitcode);
    }

    if($exitcode){
        if(!is_array($ok_exitcodes)){
            if(!$ok_exitcodes){
                $ok_exitcodes = array();

            }else{
                if(!is_string($ok_exitcodes) and !is_numeric($ok_exitcodes)){
                    throw new bException('safe_exec(): Invalid ok_exitcodes specified, should be either CSV string or array');
                }

                $ok_exitcodes = explode(',', $ok_exitcodes);
            }
        }

        if(!in_array($exitcode, $ok_exitcodes)){
            throw new bException('safe_exec(): Command "'.str_log($command).'" failed with exit code "'.str_log($exitcode).'", and output "'.print_r($output, true).'"', $exitcode, null, $output);
        }
    }

    return $output;

}catch(Exception $e){
    throw new bException('safe_exec(): Failed', $e);
}
?>
