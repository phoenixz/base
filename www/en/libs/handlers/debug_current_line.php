<?php
$backtrace = debug_backtrace();

if(!isset($backtrace[$trace + 2])){
    return -1;
}

return isset_get($backtrace[$trace + 2]['line']);
?>