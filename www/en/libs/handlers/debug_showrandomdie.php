<?php
if(!debug()){
    return $data;
}

show($data, $return, $quiet, $trace_offset);

if(mt_rand(0, 5) > 4){
    die();
}
?>