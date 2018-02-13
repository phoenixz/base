<?php
global $core;

try{
    if(empty($core->register['ready'])){
        throw new bException(tr('safe_exec(): Startup has not yet finished and base is not ready to start working properly. safe_exec() may not be called until configuration is fully loaded and available'), 'invalid');
    }



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
    log_console(tr('Executing command ":command"', array(':command' => $command)), 'VERBOSE/cyan');

    if(substr($command, -1, 1) == '&'){
        /*
         * Background commands cannot use "exec()" because that one will always wait for the exit code
         */
        $lastline = exec(substr($command, 0, -1).' > /dev/null 2>&1 & echo $!', $output, $exitcode);

    }else{
        $lastline = exec($command.($route_errors ? ' 2>&1' : ''), $output, $exitcode);
    }



    /*
     *
     */
    if(VERBOSE){
        foreach($output as $line){
            log_console($output);
        }
    }



    /*
     *
     */
    if($exitcode){
        if(!in_array($exitcode, array_force($ok_exitcodes))){
            load_libs('json');

            throw new bException(tr('safe_exec(): Command ":command" failed with exit code ":exitcode", see attached data for output', array(':command' => $command, ':exitcode' => $exitcode)), $exitcode, $output);
        }
    }

    return $output;

}catch(Exception $e){
    if(!isset($output)){
        $output = '*** COMMAND HAS NOT YET BEEN EXECUTED ***';
    }

    $e->setData($output);

    throw new bException('safe_exec(): Failed', $e);
}
?>
