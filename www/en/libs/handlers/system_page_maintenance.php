<?php
global $core;

try{
    error_log('['.PROJECT.']['.(SUBENVIRONMENT ? SUBENVIRONMENT : 'NOSUBENVIRONMENT').'][MAINTENANCE] '.$reason);
    log_database('['.PROJECT.']['.(SUBENVIRONMENT ? SUBENVIRONMENT : 'NOSUBENVIRONMENT').'][MAINTENANCE] '.$reason, 'MAINTENANCE');

    if($core->callType('admin')){
        page_show('admin/maintenance', true, $force, $data);

    }else{
        page_show('maintenance', true, $force, $data);
    }

}catch(Exception $e){
    throw new bException('page_maintenance(): Failed', $e);
}
?>
