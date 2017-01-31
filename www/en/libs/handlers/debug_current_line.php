<?php
$backtrace = debug_backtrace();

if(!isset($backtrace[$trace])){
    return -1;
}

return $backtrace[$trace]['line'];
?>