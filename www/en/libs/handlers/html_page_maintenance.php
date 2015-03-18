<?php
try{
    error_log   ('['.PROJECT.']['.(SUBENVIRONMENT ? SUBENVIRONMENT : 'NOSUBENVIRONMENT').'][MAINTENANCE] '.$reason);
    log_database('['.PROJECT.']['.(SUBENVIRONMENT ? SUBENVIRONMENT : 'NOSUBENVIRONMENT').'][MAINTENANCE] '.$reason, 'MAINTENANCE');

    if($GLOBALS['page_is_admin']){
        page_show('admin/500', true, $force, $data);

    }else{
        page_show('500'      , true, $force, $data);
    }

}catch(Exception $e){
    throw new bException('page_maintenance(): Failed', $e);
}
?>
