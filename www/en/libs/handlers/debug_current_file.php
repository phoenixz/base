<?php
$backtrace = debug_backtrace();

if(!isset($backtrace[$trace + 1])){
    return 'no_current_file';
}

return isset_get($backtrace[$trace + 1]['file']);
?>