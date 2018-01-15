<?php
try{
    if(!debug()){
        return array();
    }

    $filters = array_force($filters);
    $trace   = array();
    $skipped = false;

    foreach(debug_backtrace() as $part){
        if(!$skipped){
            /*
             * Remove the debug_trace() call from the trace list
             */
            $skipped = true;
            continue;
        }

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