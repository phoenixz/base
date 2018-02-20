<?php
$backtrace = debug_backtrace();

if(!isset($backtrace[$trace + 1])){
    return -1;
}

return isset_get($backtrace[$trace + 1]['line']);
?>