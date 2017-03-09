<?php
/*
 * Array library
 *
 * This library file contains extra array functions
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */



/*
 * Return the next key right after specified $key
 */
function array_next_key(&$array, $currentkey, $delete = false){
    try{
        foreach($array as $key => $value){
            if(isset($next)){
                if($delete){
                    unset($array[$key]);
                }

                return $key;
            }

            if($key === $currentkey){
                if($delete){
                    unset($array[$key]);
                }

                $next = true;
            }
        }


        if(!empty($next)){
            /*
             * The currentvalue was found, but it was at the end of the array
             */
            throw new bException(tr('array_next_key(): Found currentkey ":value" but it was the last item in the array, there is no next', array(':value' => str_log($currentvalue))), '');
        }

    }catch(Exception $e){
        throw new bException('array_next_key(): Failed', $e);
    }
}



/*
 * Return the next key right after specified $key
 *
 * If the specified key is not found, $currentvalue will be returned.
 */
function array_next_value(&$array, $currentvalue, $delete = false, $restart = false){
    try{
        foreach($array as $key => $value){
            if(isset($next)){
                if($delete){
                    unset($array[$key]);
                }

                return $value;
            }

            if($value === $currentvalue){
                if($delete){
                    unset($array[$key]);
                }

                $next = true;
            }
        }

        if(!$restart){
            /*
             * The currentvalue was found, but it was at the end of the array
             */
            throw new bException(tr('array_next_value(): Option ":value" does not have a value specified', array(':value' => str_log($currentvalue))), 'invalid');
        }

        reset($array);
        return current($array);

    }catch(Exception $e){
        throw new bException('array_next_value(): Failed', $e);
    }
}



/*
 * Set the default value for the specified key of the specified array if it does not exist
 */
function array_default(&$source, $key, $default){
    try{
        if(isset($source[$key])){
            return false;
        }

        $source[$key] = $default;

        return $default;

    }catch(Exception $e){
        if(!is_array($source)){
            throw new bException(tr('array_default(): Specified source is not an array'), 'invalid');
        }

        throw new bException('array_default(): Failed', $e);
    }
}



/*
 * Ensure that the specified keys are available. If not, exception
 */
function array_key_check($source, $keys){
    try{
        if(!is_array($source)){
            throw new bException(tr('array_key_check(): Specified source should be an array, but is a ":type"', array(':type' => gettype($source))), 'invalid');
        }

        foreach(array_force($keys) as $key){
            if(!array_key_exists($key, $source)){
                throw new bException(tr('array_key_check(): Key ":key" was not specified in array', array(':key' => str_log($key))), 'not_specified');
            }
        }

    }catch(Exception $e){
        if($e->getCode() == 'not_specified'){
            throw $e;
        }

        throw new bException('array_key_check(): Failed', $e);
    }
}



/*
 * Make sure the array is cleared, but with specified keys available
 */
function array_clear(&$array, $keys, $value = null){
    try{
        $array = array();
        return array_ensure($array, $keys, $value);

    }catch(Exception $e){
        throw new bException('array_clear(): Failed', $e);
    }
}



/*
 * Make sure the specified keys are available on the array
 */
function array_ensure(&$array, $keys, $value = null){
    try{
        if(!is_array($array)){
            $array = array();
        }

        if(!is_array($keys)){
            if(!is_string($keys)){
                throw new bException(tr('array_ensure(): Invalid $keys specified. Should be either a numeric array or a CSV string'));
            }

            $keys = explode(',', $keys);
        }

        foreach($keys as $key){
            if(!array_key_exists($key, $array)){
                $array[$key] = $value;
            }
        }

        return $array;

    }catch(Exception $e){
        throw new bException('array_ensure(): Failed', $e);
    }
}



/*
 * Return an array from the given object, recursively
 */
function array_from_object($object, $recurse = true){
    try{
        if(!is_object($object)){
            throw new bException(tr('array_from_object(): Specified variable is not an object'));
        }

        $retval = array();

        foreach($object as $key => $value){
            if(is_object($value) and $recurse){
                $value = array_from_object($value, true);
            }

            $retval[$key] = $value;
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('array_from_object(): Failed', $e);
    }
}



/*
 * Return a random value from the specified array
 */
function array_random_value($array){
    try{
        return $array[array_rand($array)];

    }catch(Exception $e){
        throw new bException('array_random_value(): Failed', $e);
    }
}

// :DEPRECATED: Use the above function
function array_get_random($array){
    try{
        if(empty($array)){
            throw new bException(tr('array_get_random(): The specified array is empty'), 'empty');
        }

        return $array[array_rand($array)];

    }catch(Exception $e){
        throw new bException('array_get_random(): Failed', $e);
    }
}



/*
 * Implode the array with keys
 */
function array_implode_with_keys($source, $row_separator, $key_separator = ':', $auto_quote = false, $recurse = true){
    try{
        if(!is_array($source)){
            throw new bException(tr('array_implode_with_keys(): Specified source is not an array but an ":type"', array(':type' => gettype($source))));
        }

        $retval = array();

        foreach($source as $key => $value){
            if(is_array($value)){
                /*
                 * Recurse?
                 */
                if(!$recurse){
                    throw new bException(tr('array_implode_with_keys(): Specified source contains sub arrays and recurse is not enabled'));
                }

                $retval[] .= $key.$key_separator.$row_separator.array_implode_with_keys($value, $row_separator, $key_separator, $auto_quote, $recurse);

            }else{
                if($auto_quote){
                    $retval[] .= $key.$key_separator.str_auto_quote($value);

                }else{
                    $retval[] .= $key.$key_separator.$value;
                }
            }
        }

        return implode($row_separator, $retval);

    }catch(Exception $e){
        throw new bException('array_implode_with_keys(): Failed', $e);
    }
}



/*
 *
 */
function array_merge_complete(){
    try{
        $arguments = func_get_args();

        if(count($arguments) < 2){
            throw new bException('array_merge_complete(): Specify at least 2 arrays');
        }

        $retval = array();
        $count  = 0;

        foreach($arguments as $argk => $argv){
            $count++;

            if(!is_array($argv)){
                throw new bException(tr('array_merge_complete(): Specified argument ":count" is not an array', array(':count' => str_log($count))));
            }

            foreach($argv as $key => $value){
                if(is_array($value) and array_key_exists($key, $retval) and is_array($retval[$key])){
                    $retval[$key] = array_merge_complete($retval[$key], $value);

                }else{
                    $retval[$key] = $value;
                }
            }
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('array_merge_complete(): Failed', $e);
    }
}



/*
 * Specified variable may be either string or array, but ensure that its returned as an array.
 */
function array_force($source, $separator = ','){
    try{
        if(!$source){
            return array();
        }

        if(!is_array($source)){
            if(!is_string($source)){
                if(!is_numeric($source)){
                    throw new bException(tr('array_force(): Specified source is neither array or string or numeric'), 'invalid');
                }

                return array($source);
            }

            return explode($separator, $source);
        }

        return $source;

    }catch(Exception $e){
        throw new bException('array_force(): Failed', $e);
    }
}



/*
 * If specified params is not an array, then make it an array with the current value under the specified string_key
 * If numeric_key is set, and params is numeric, then use the numeric key instead
 */
function array_params(&$params, $string_key = false, $numeric_key = false){
    try{
        if(empty($params)){
            $params = array();
        }

        if(is_array($params)){
            return true;
        }

        if($numeric_key and is_numeric($params)){
            $params = array($numeric_key => $params);

        }elseif($string_key){
            $params = array($string_key => $params);

        }else{
            $params = array();
        }

        return false;

    }catch(Exception $e){
        throw new bException('array_params(): Failed', $e);
    }
}



/*
 * Limit the specified array to the specified amount of entries
 */
function array_limit($source, $count, $return_source = true){
    try{
        if(!is_array($source)){
            throw new bException(tr('array_limit(): Specified source is not an array'));
        }

        if(!is_numeric($count) or ($count < 0)){
            throw new bException(tr('array_limit(): Specified count is not valid'));
        }

        $retval = array();

        while(count($source) > $count){
            $retval[] = array_pop($source);
        }

        if($return_source){
            return $source;
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('array_limit(): Failed', $e);
    }
}



/*
 *
 */
function array_filter_values($source, $values){
    try{
        if(!is_array($source)){
            throw new bException(tr('array_filter_values(): Specified source is not an array'), 'invalid');
        }

        foreach(array_force($values) as $value){
            if(($key = array_search($value, $source)) !== false){
                unset($source[$key]);
            }
        }

        return $source;

    }catch(Exception $e){
        throw new bException('array_filter_values(): Failed');
    }
}



/*
 * Return an array with the amount of values where each value name is $base_valuename# and # is a sequential number
 */
function array_sequential_values($count, $base_valuename){
    try{
        if(!is_numeric($count) or ($count < 1)){
            throw new bException(tr('array_sequential_values(): Invalid count specified. Make sure count is numeric, and greater than 0'), 'invalid');
        }

        for($i = 0; $i < $count; $i++){
            $retval[] = $base_valuename.$i;
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('array_sequential_values(): Failed', $e);
    }
}



/*
 * Return the source array with the keys all replaced by sequential values based on base_keyname
 */
function array_sequential_keys($source, $base_keyname){
    try{
        if(!is_array($source)){
            throw new bException(tr('array_sequential_keys(): Specified source is an ":type", but it should be an array', array(':type' => gettype($source))), 'invalid');
        }

        $i      = 0;
        $retval = array();

        foreach($source as $value){

            $retval[$base_keyname.$i++] = $value;
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('array_sequential_keys(): Failed', $e);
    }
}



/*
 * Return the source array with the specified keys kept, all else removed.
 */
function array_keep($source, $keys){
    try{
        $retval = array();

        foreach(array_force($keys) as $key){
            if(array_key_exists($key, $source)){
                $retval[$key] = $source[$key];
            }
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('array_keep(): Failed', $e);
    }
}



/*
 * Return the source array with the specified keys removed.
 */
function array_remove($source, $keys){
    try{
        foreach(array_force($keys) as $key){
            unset($source[$key]);
        }

        return $source;

    }catch(Exception $e){
        throw new bException('array_remove(): Failed', $e);
    }
}



/*
 * Return all array parts from (but without) the specified key
 */
function array_from(&$source, $from_key, $delete = false, $skip = true){
    try{
        if(!is_array($source)){
            throw new bException(tr('array_from(): Specified source is an ":type", but it should be an array', array(':type' => gettype($source))), 'invalid');
        }

        $retval = array();
        $add    = false;

        foreach($source as $key => $value){
            if(!$add){
                if($key == $from_key){
                    if($delete){
                        unset($source[$key]);
                    }

                    $add = true;

                    if($skip){
                        /*
                         * Do not include the key itself, skip it
                         */
                        continue;
                    }

                }else{
                    continue;
                }
            }

            $retval[$key] = $value;

            if($delete){
                unset($source[$key]);
            }
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('array_from(): Failed', $e);
    }
}



/*
 * Return all array parts until (but without) the specified key
 */
function array_until($source, $until_key, $delete = false){
    try{
        if(!is_array($source)){
            throw new bException(tr('array_until(): Specified source is an ":type", but it should be an array', array(':type' => gettype($source))), 'invalid');
        }

        $retval = array();

        foreach($source as $key => $value){
            if($key == $until_key){
                break;
            }

            $retval[$key] = $value;

            if($delete){
                unset($source[$key]);
            }
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('array_until(): Failed', $e);
    }
}



/*
 * Merge two arrays together, using the values of array1 as keys, and the values of array2 as values
 */
function array_merge_keys_values($keys, $values){
    try{
        if(!is_array($keys)){
            throw new bException(tr('array_merge_keys_values(): Specified keys variable is an ":type", but it should be an array', array(':type' => gettype($keys))), 'invalid');
        }

        if(!is_array($values)){
            throw new bException(tr('array_merge_keys_values(): Specified values variable is an ":type", but it should be an array', array(':type' => gettype($values))), 'invalid');
        }

        $retval = array();

        foreach($keys as $key){
            if(!isset($next)){
                $next = true;
                $retval[$key] = reset($values);

            }else{
                $retval[$key] = next($values);
            }
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('array_merge_keys_values(): Failed', $e);
    }
}



/*
 * Prefix all keys in this array with the specified prefix
 */
function array_prefix($source, $prefix, $auto = false){
    try{
        if(!is_array($source)){
            throw new bException(tr('array_prefix_keys(): Specified source is an ":type", but it should be an array', array(':type' => gettype($source))), 'invalid');
        }

        $count  = 0;
        $retval = array();

        foreach($source as $key => $value){
            if($auto){
                $retval[$prefix.$count++] = $value;

            }else{
                $retval[$prefix.$key] = $value;
            }
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('array_prefix(): Failed', $e);
    }
}



/*
 * Return the array keys that has a STRING value that contains the specified keyword
 *
 * NOTE: Non string values will be quietly ignored!
 */
function array_find($array, $keyword){
    try{
        $retval = array();

        foreach($array as $key => $value){
            if(is_string($value)){
                if(strpos($value, $keyword) !== false){
                    $retval[$key] = $value;
                }
            }
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('array_find(): Failed', $e);
    }
}



/*
 * Copy all elements from source to target, and clean them up. Any columns specified in "skip" will be skipped
 */
function array_copy_clean($target, $source, $skip = 'id'){
    try{
        $skip = array_force($skip);

        foreach($source as $key => $value){
            if(in_array($key, $skip)) continue;

            if(is_string($value)){
                $target[$key] = mb_trim($value);

            }elseif($value !== null){
                $target[$key] = $value;
            }
        }

        return $target;

    }catch(Exception $e){
        throw new bException('array_copy_clean(): Failed', $e);
    }
}



/*
 * Return an array with all the values in the specified column
 */
function array_get_column($source, $column){
    try{
        $retval = array();

        foreach($source as $id => $value){
            if(array_key_exists($column, $value)){
                $retval[] = $value[$column];
            }
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('array_get_column(): Failed', $e);
    }
}



/*
 * Return the value of one of the first found key of the specified keys
 */
function array_extract_first($source, $keys){
    try{
        if(!is_array($source)){
            throw new bException(tr('array_extract(): Specified source is not an array'));
        }

        foreach(array_force($keys) as $key){
            if(!empty($source[$key])){
                return $source[$key];
            }
        }

    }catch(Exception $e){
        throw new bException('array_extract(): Failed', $e);
    }
}



/*
 * Check the specified array and ensure it has not too many elements (to avoid attack with processing foreach over 2000000 elements, for example)
 */
function array_max($source, $max = 20){
    if(count($source) > $max){
        throw new bException(tr('array_max(): Specified array has too many elements'), 'arraytoolarge');
    }

    return $source;
}



/*
 *
 */
function array_value_to_keys($source){
    try{
        if(!is_array($source)){
            throw new bException(tr('array_value_to_keys(): Specified source is not an array'));
        }

        $retval = array();

        foreach($source as $value){
            if(!is_scalar($value)){
                throw new bException(tr('array_value_to_keys(): Specified source array contains non scalar values, cannot use non scalar values for the keys'));
            }

            $retval[$value] = $value;
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('array_value_to_keys(): Failed', $e);
    }
}



/*
 *
 */
function array_filtered_merge(){
    try{
        $args = func_get_args();

        if(count($args) < 3){
            throw new bException(tr('array_filtered_merge(): Function requires at least 3 arguments: filter, source, merge, ...'), 'missing_argument');
        }

        $filter = array_shift($args);
        $source = array_shift($args);
        $source = array_remove($source, $filter);
        array_unshift($args, $source);

        return call_user_func_array('array_merge', $args);

    }catch(Exception $e){
        throw new bException('array_filtered_merge(): Failed', $e);
    }
}



/*
 * Return all elements from source1. If the value of one element is null, then try to return it from source2
 */
function array_not_null(&$source1, $source2){
    try{
        $modified = false;

        foreach($source1 as $key => $value){
            if($value === null){
                $source1[$key] = isset_get($source2[$key]);
                $modified      = true;
            }
        }

        return $modified;

    }catch(Exception $e){
        throw new bException('array_not_null(): Failed', $e);
    }
}



/*
 *
 */
function array_average($source){
    try{
        $total = 0;

        foreach($source as $key => $value){
            $total += $value;
        }

        return $total / count($source);

    }catch(Exception $e){
        throw new bException('array_average(): Failed', $e);
    }
}



/*
 * Return an array with values ranging from $min to $max
 */
function array_range($min, $max){
    try{
        if(!is_numeric($min)){
            throw new bException(tr('array_range(): Specified $min not numeric'), 'invalid');
        }

        if(!is_numeric($max)){
            throw new bException(tr('array_range(): Specified $max not numeric'), 'invalid');
        }

        if($min > $max){
            throw new bException(tr('array_range(): Specified $min is equal or larger than $max. Please ensure that $min is smaller'), 'invalid');
        }

        $retval = array();

        for($i = $min; $i <= $max; $i++){
            $retval[$i] = $i;
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('array_range(): Failed', $e);
    }
}



/*
 * Ensure that all array values
 */
function array_clean($source, $recursive = true){
    try{
        foreach($source as &$value){
            switch(gettype($value)){
                case 'integer':
                    // FALLTHROUGH
                case 'double':
                    // FALLTHROUGH
                case 'float':
                    $value = cfi($value);
                    break;

                case 'string':
                    $value = cfm($value);
                    break;

                case 'array':
                    if($recursive){
                        $value = array_clean($value, $recursive);
                    }

                    break;
            }
        }

        return $source;

    }catch(Exception $e){
        throw new bException('array_clean(): Failed', $e);
    }
}
?>
