<?php
/*
 * Empty library
 *
 * This is an empty template library file
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */



/*
 * Encode the given data for use with BASE APIs
 */
function api_encode($data){
    try{
        if(is_array($data)){
            $data = str_replace('@', '\@', $data);

        }elseif(is_string($data)){
            foreach($listing as &$value){
                $value = str_replace('@', '\@', $value);
            }

            unset($value);

        }else{
            throw new bException(tr('api_encode(): Specified data is datatype ":type", only string and array are allowed', array(':type' => gettype($data))), $e);
        }

        return $data;

    }catch(Exception $e){
        throw new bException('api_encode(): Failed', $e);
    }
}



/*
 * Encode the given data from a BASE API back to its original form
 */
function api_decode($data){
    try{
        if(is_array($data)){
            $data = str_replace('\@', '@', $data);

        }elseif(is_string($data)){
            foreach($listing as &$value){
                $value = str_replace('\@', '@', $value);
            }

            unset($value);

        }else{
            throw new bException(tr('api_decode(): Specified data is datatype ":type", only string and array are allowed', array(':type' => gettype($data))), $e);
        }

        return $data;

    }catch(Exception $e){
        throw new bException('api_decode(): Failed', $e);
    }
}
?>
