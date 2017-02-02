<?php
$backtrace = debug_backtrace();

if(!isset($backtrace[$trace + 2])){
    return 'no_current_file';
}

return $backtrace[$trace + 2]['file'];
?>