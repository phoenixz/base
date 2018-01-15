<?php
try{
    $args = str_from ($cmd, ' ');
    $cmd  = str_until($cmd, ' ');
    $path = dirname($cmd);
    $path = slash($path);
    $cmd  = basename($cmd);

    load_libs('process');

    if($path == './'){
        $path = ROOT.'scripts/';

    }elseif(str_ends_not(str_starts_not($path, '/'), '/') == 'base'){
        $path = ROOT.'scripts/base/';
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
    file_ensure_path(ROOT.'data/log');

//showdie(sprintf('nohup %s >> '.ROOT.'data/log/%s 2>&1 & echo $! > %s', $path.$cmd.' '.$args, $log, ROOT.'data/run-background/'.$cmd));
    if($log){
        exec(sprintf('nohup %s >> '.ROOT.'data/log/%s 2>&1 & echo $! > %s', $path.$cmd.' '.$args, $log, ROOT.'data/run-background/'.$cmd));

    }else{
        exec(sprintf('nohup %s > /dev/null 2>&1 & echo $! > %s', $path.$cmd.' '.$args, ROOT.'data/run-background/'.$cmd));
    }

    return exec(sprintf('cat %s; rm %s', ROOT.'data/run-background/'.$cmd.' ', ROOT.'data/run-background/'.$cmd));

}catch(Exception $e){
    throw new bException('run_background(): Failed', $e);
}
?>