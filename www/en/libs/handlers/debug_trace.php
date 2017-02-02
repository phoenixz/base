<?php
try{
    if(!debug()){
        return array();
    }

    $filters = array_force($filters);
    $trace   = array();

    foreach(debug_backtrace() as $part){
        foreach($filters as $filter){
            unset($part[$filter]);
        }

        $trace[] = $part;
    }

    return $trace;

}catch(Exception $e){
    throw new bException('debug_trace(): Failed', $e);
}
?>