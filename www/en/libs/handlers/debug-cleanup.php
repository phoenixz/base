<?php
try{
    if(is_array($data)){
        foreach($data as $key => &$value){
            if(is_array($value)){
                $value = debug_cleanup($value);

            }elseif(strstr($key, 'pass') !== false){
                $value = '*** HIDDEN ***';

            }elseif(strstr($key, 'ssh_key') !== false){
                $value = '*** HIDDEN ***';
            }
        }

        unset($value);
    }

    return $data;

}catch(Exception $e){
    throw new bException(tr('debug_cleanup(): Failed'), $e);
}

?>