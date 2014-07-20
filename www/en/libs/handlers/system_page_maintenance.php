<?php
try{
    error_log('['.PROJECT.']['.(SUBENVIRONMENT ? SUBENVIRONMENT : 'NOSUBENVIRONMENT').'][MAINTENANCE] '.$reason);
    log_database('['.PROJECT.']['.(SUBENVIRONMENT ? SUBENVIRONMENT : 'NOSUBENVIRONMENT').'][MAINTENANCE] '.$reason, 'MAINTENANCE');
    page_show('maintenance', true, $force, $data);

}catch(Exception $e){
    throw new lsException('page_maintenance(): Failed', $e);
}
?>
