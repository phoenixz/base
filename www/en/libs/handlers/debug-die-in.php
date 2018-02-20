<?php
static $counter = 1;

if(!$message){
    $message = tr('Terminated process because die counter reached "%count%"');
}

if($counter++ >= $count){
    die(str_ends(str_replace('%count%', $count, $message), "\n"));
}
?>