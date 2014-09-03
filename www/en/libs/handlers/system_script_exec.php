<?php
global $_CONFIG;

try{
    load_libs('file');
    $quiet = true;

    /*
     * Process command line arguments
     */
    if(!$argv){
        $argv = array();

    }elseif(!is_array($argv)){
        if(!is_string($argv)){
            throw new lsException('script_exec(): Invalid argv specified. Should either be an array or a space delimited string');
        }

        $argv = explode(' ', $argv);
    }

    $GLOBALS['argv'] = $argv;
    $GLOBALS['argc'] = count($argv);

    /*
     * Detect the path depth for this script, since the copy
     * needs to have the exact depth as well to avoid errors
     * on loading the startup.php library
     */
    if(strpos($script, '/')){
        $length = substr_count($script, '/');

    }else{
        $length = 0;
    }

    /*
     * Assign temporary file, and copy the script there without the hashbang header
     * Then include it to execute it.
     *
     * IMPORTANT! These scripts are location sensitive and as such can ONLY run in the local tmp path!
     */
    $_script_exec_file = file_assign_target(ROOT.'tmp/', false, false, $length);
    $_script_exec_file = file_copy_tree(ROOT.'scripts/'.$script, ROOT.'tmp/'.$_script_exec_file, "#!/usr/bin/php\n", '', '.php');

    log_console('Executing script "'.str_log($script).'" as "'.$_script_exec_file.'"', 'script_exec', 'white');

    try{
        /*
         * Execute the script (by its tempfile)
         */
        include($_script_exec_file);

    }catch(Exception $e){
        if($e->code){
            if(!is_array($ok_exitcodes)){
                if(!$ok_exitcodes){
                    $ok_exitcodes = array();

                }else{
                    if(!is_string($ok_exitcodes) and !is_numeric($ok_exitcodes)){
                        throw new lsException('script_exec(): Invalid ok_exitcodes specified, should be either CSV string or array');
                    }

                    $ok_exitcodes = explode(',', $ok_exitcodes);
                }
            }

            if(!in_array($e->code, $ok_exitcodes)){
// :TODO: Remove following line, it was not sending error output from the preceding script
//                    throw new lsException('script_exec(): Script "'.str_log($script).'" failed with code "'.str_log($e->code).'"', $e->code, null);
                throw new lsException('script_exec(): Script "'.str_log($script).'" failed with code "'.str_log($e->code).'"', $e);
            }
        }
    }

    /*
     * Cleanup
     */
    try{
        file_clear_path($_script_exec_file);

    }catch(Exception $e){
        /*
         * This is a minor problem, really..
         */
        log_message('script_exec(): Failed to clean up executed temporary script copy path "'.$_script_exec_file.'", probably a parrallel process added new content there?', 'error/failed', 'yellow');
    }

    if(isset($return)){
        return $return;
    }

}catch(Exception $e){
    throw new lsException('script_exec(): Failed to execute "'.str_log($script).'"', $e);
}
?>
