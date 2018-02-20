<?php
try{
    if($trace_offset === null){
        if(PLATFORM_HTTP){
            $trace_offset = 5;

        }else{
            $trace_offset = 4;
        }
    }

    if(!debug()){
        return $data;
    }

    show($data, $trace_offset);
    die();

}catch(Exception $e){
    throw new bException(tr('showdie(): Failed'), $e);
}
?>