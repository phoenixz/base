<?php
$backtrace = debug_backtrace();

if(!isset($backtrace[$trace + 3])){
    return 'no_current_function';
}

return $backtrace[$trace + 3]['function'];
?>