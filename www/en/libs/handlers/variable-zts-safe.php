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
    $variable = print_r($variable, true);
}

if(is_array($variable) or (is_object($variable) and (($variable instanceof Exception) or ($variable instanceof Error)))){
    foreach($variable as $key => &$value){
        if($key === 'object'){
            $value = print_r($value, true);

        }else{
            $value = variable_zts_safe($value, $level);
        }
    }

}elseif(is_object($variable)){
    $variable = print_r($variable, true);
}

unset($value);
return $variable;
?>