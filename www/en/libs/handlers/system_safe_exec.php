<?php
try{
    /*
     * Join all commands together
     */
    if(is_array($commands)){
        /*
         * Auto escape all arguments
         */
        foreach($commands as &$command){
            if(empty($first)){
                $first   = true;
                $command = mb_trim($command);
                continue;
            }

            $command = escapeshellarg($command);
        }

        unset($command);
        $command = implode(' ', $commands);

    }else{
        $command = mb_trim($commands);
    }



    /*
     *
     */
    if(VERBOSE){
        cli_log(tr('Executing command ":command"', array(':command' => $command)), 'cyan');
    }

    if(substr($command, -1, 1) == '&'){
        /*
         * Background commands cannot use "exec()" because that one will always wait for the exit code
         */
        $lastline = exec('> /dev/null '.substr($command, 0, -1).' 2>/dev/null &', $output, $exitcode);

    }else{
        $lastline = exec($command.($route_errors ? ' 2>&1' : ''), $output, $exitcode);
    }

    if(VERBOSE){
        foreach($output as $line){
            cli_log($output);
        }
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
            load_libs('json');

            $e =  new bException(json_encode_custom($output), $exitcode, null, $output);
            throw new bException('safe_exec(): Command "'.str_log($command).'" failed with exit code "'.str_log($exitcode).'", and output "'.json_encode_custom($output, true).'"', $e);
        }
    }

    return $output;

}catch(Exception $e){
    $e->setData($output);

    throw new bException('safe_exec(): Failed', $e);
}
?>
