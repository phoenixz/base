<?php
try{
    $args = str_from ($cmd, ' ');
    $cmd  = str_until($cmd, ' ');
    $path = dirname($cmd);
    $path = slash($path);
    $cmd  = basename($cmd);

    load_libs('process,file');

    if($path == './'){
        $path = ROOT.'scripts/';

    }elseif(str_starts_not($path, '/') == 'base/'){
        $path = ROOT.'scripts/base/';

    }else{
        throw new bException(tr('run_background(): Invalid path ":path" specified. Only scripts in ROOT/scripts/ may be run using this function', array(':path' => $path)), 'security');
    }

    if($single and process_runs($cmd)){
        return false;
    }

    if(!file_exists($path.$cmd)){
        throw new bException(tr('run_background(): Specified command ":cmd" does not exists', array(':cmd' => $path.$cmd)), 'not-exist');
    }

    if(!is_file($path.$cmd)){
        throw new bException(tr('run_background(): Specified command ":cmd" is not a file', array(':cmd' => $path.$cmd)), 'notfile');
    }

    if(!is_executable($path.$cmd)){
        throw new bException(tr('run_background(): Specified command ":cmd" is not executable', array(':cmd' => $path.$cmd)), 'notexecutable');
    }

    if($log === true){
        $log = $cmd;
    }

    load_libs('file');
    file_ensure_path(ROOT.'data/run-background');

    if(!strstr($args, '--env') and !strstr($args, '-E')){
        /*
         * Command doesn't have environment specified. Specify it using the current environment
         */
        $args .= ' --env '.ENVIRONMENT;
    }

    if($log){
        if(substr($log, 0, 3) === 'tmp'){
            /*
             * Log output to the TMP directory instead of the normal log output
             */
            $log = TMP.str_starts_not(substr($log, 3), '/');

        }else{
            $log = ROOT.'data/log/'.$log;
        }

        $command = sprintf('(nohup %s >> %s 2>&1 & echo $! >&3) 3> %s', $path.$cmd.' '.$args, $log, ROOT.'data/run-background/'.$cmd);

        file_ensure_path(dirname($log));
        exec($command);

    }else{
        $command = sprintf('(nohup %s > /dev/null 2>&1 & echo $! >&3) 3> %s', $path.$cmd.' '.$args, ROOT.'data/run-background/'.$cmd);
        exec($command);
    }
// :DEBUG: Leave the next line around, it will be useful..
//showdie($command);

    return exec(sprintf('cat %s; rm %s', ROOT.'data/run-background/'.$cmd.' ', ROOT.'data/run-background/'.$cmd));

}catch(Exception $e){
    throw new bException('run_background(): Failed', $e);
}
?>