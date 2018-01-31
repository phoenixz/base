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
        log_console(tr('Executing command ":command"', array(':command' => $command)), 'cyan');
    }

    if(substr($command, -1, 1) == '&'){
        /*
         * Background commands cannot use "exec()" because that one will always wait for the exit code
         */
        $lastline = exec(substr($command, 0, -1).' > /dev/null 2>&1 & echo $!', $output, $exitcode);

    }else{
        $lastline = exec($command.($route_errors ? ' 2>&1' : ''), $output, $exitcode);
    }

    if(VERBOSE){
        foreach($output as $line){
            log_console($output);
        }
    }

    if($exitcode){
        if(!in_array($exitcode, array_force($ok_exitcodes))){
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
