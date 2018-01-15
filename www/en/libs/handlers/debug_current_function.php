<?php
$backtrace = debug_backtrace();

if(!isset($backtrace[$trace + 2])){
    return 'no_current_function';
}

return $backtrace[$trace + 2]['function'];
?>