<?php
$backtrace = debug_backtrace();

if(!isset($backtrace[$trace])){
    return 'no_current_file';
}

return $backtrace[$trace]['file'];
?>