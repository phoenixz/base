<?php
/*
 * Handler code for script_exec() function
 *
 *
 */
// :TODO: This will fail for PLATFORM apache since $arguments will not be defined! Implement fix for that
global $_CONFIG, $core, $argv, $argc;

try{
    load_libs('file');
    $quiet = true;
    $argv  = $GLOBALS['argv'];

    /*
     * Process command line arguments
     */
    if(!$arguments){
        $arguments = array();

    }elseif(!is_array($arguments)){
        if(!is_string($arguments)){
            throw new bException(tr('script_exec(): Invalid argv specified. Should either be an array or a space delimited string'), 'invalid');
        }

        $arguments = explode(' ', $arguments);
    }

    $GLOBALS['argv'] = $arguments;
    $GLOBALS['argc'] = count($arguments);

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
     * Ensure libs symlink is available
     */
    clearstatcache();

    if(!file_exists(ROOT.'data/libs') and !is_readable(ROOT.'data/libs')){
        if(is_link(ROOT.'data/libs')){
            /*
             * Wut? The data/libs symlink is broken?
             * Delete and start over
             */
            log_console(tr('data/libs symlink broken, fixing issue'), 'warning');
            file_delete(ROOT.'data/libs');
        }

        symlink('../www/en/libs', ROOT.'data/libs');
    }

    /*
     * Assign temporary file, and copy the script there without the hashbang header
     * Then include it to execute it.
     *
     * IMPORTANT! These scripts are location sensitive and as such can ONLY run in the local tmp path!
     */
    $_script_exec_file = file_assign_target(TMP, false, false, $length);
    $_script_exec_file = file_copy_tree(ROOT.'scripts/'.$script, TMP.$_script_exec_file, "#!/usr/bin/php\n", '', '.php');

    $core->register('shutdown_script_clean', array($_script_exec_file));
    log_console(tr('Executing script ":script" as ":as"', array(':script' => $script, ':as' => $_script_exec_file)), 'VERBOSE/cyan');

    /*
     * Store the list of scripts being executed, just in case script_exec() is
     * executed by multiple scripts
     */
    if(empty($core->register['scripts'])){
        $core->register['scripts'] = array(SCRIPT, $script);

    }else{
        $core->register['scripts'][SCRIPT] = $script;
    }

    try{
        /*
         * Execute the script (by its tempfile)
         */
        include($_script_exec_file);
        array_pop($core->register['scripts']);

    }catch(Exception $e){
        if(!$e->getCode()){
            throw $e;
        }

        if(!is_array($ok_exitcodes)){
            if(!$ok_exitcodes){
                $ok_exitcodes = array();

            }else{
                if(!is_string($ok_exitcodes) and !is_numeric($ok_exitcodes)){
                    throw new bException(tr('script_exec(): Invalid ok_exitcodes specified, should be either CSV string or array'), $e);
                }

                $ok_exitcodes = explode(',', $ok_exitcodes);
            }
        }

        if(!in_array($e->getCode(), $ok_exitcodes)){
            throw new bException(tr('script_exec(): Script ":script" failed with code ":code"', array(':script' => $script, ':code' => $e->getCode())), $e);
        }
    }

    /*
     * Cleanup
     *
     * Delete the TMP script and the data/libs symlink
     */
    script_clean($_script_exec_file);

    $GLOBALS['argv'] = $argv;

    if(isset($return)){
        return $return;
    }

}catch(Exception $e){
    log_console(tr('Script ":script" threw the following exception', array(':script' => $script)), 'error');
    log_console($e);

    $GLOBALS['argv'] = $argv;

    if($_script_exec_file){
        script_clean($_script_exec_file);
    }

    throw new bException(tr('script_exec(): Failed to execute ":script"', array(':script' => $script)), $e);
}
?>
