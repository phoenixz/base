<?php
/*
 *
 */
try{
    file_delete($script_file, true);
    log_console(tr('Cleaning up temporary script file ":script"', array(':script' => $script_file)), 'VERYVERBOSE/cyan');

}catch(Exception $e){
    /*
     * Failed to delete the temporary script file
     */
    log_console(tr('script_exec(): Failed to clean up executed temporary script copy path ":file", probably a parrallel process added new content there?', array(':file' => $script_file)), 'yellow');
}
?>
