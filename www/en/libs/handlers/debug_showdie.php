<?php
if(!debug()){
    return $data;
}

show($data, $trace_offset);
die();
?>