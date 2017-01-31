<?php
$backtrace = debug_backtrace();

if(!isset($backtrace[$trace + 1])){
    return 'no_current_function';
}

return $backtrace[$trace + 1]['function'];
?>