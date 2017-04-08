<?php
if(!defined('PHP_ZTS')){
    return $variable;
}

if(++$level > 20){
    /*
     * Recursion level reached, until here, no further!
     */
    return '***  Resource limit reached! ***';
}

if(is_resource($variable)){
    return '*** Variable is resource ***';
}

if(is_array($variable) or is_object($variable)){
    foreach($variable as $key => &$value){
        if($key === 'object'){
            return '*** Not showing objects due to possible segfaults ***';

        }else{
            $value = variable_zts_safe($value, $level);
        }
    }
}

unset($value);
return $variable;
?>