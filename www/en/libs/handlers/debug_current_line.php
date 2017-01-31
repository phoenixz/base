<?php
$backtrace = debug_backtrace();

if(!isset($backtrace[$trace + 2])){
    return -1;
}

return $backtrace[$trace + 2]['line'];
?>