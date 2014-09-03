<?php
/*
 * Cachelibrary
 *
 * This library file contains caching functions
 *
 * Written and Copyright by Sven Oostenbrink
 */



/*
 * Try to read specified name object from cache
 */
function cache_read($name){
    try{
        if(!file_exists($path = ROOT.'data/cache/')){
            mkdir($path);
        }

        if(!file_exists($path.$name)){
            return false;
        }

        return file_get_contents($path.$name);

    }catch(Exception $e){
        throw new bException('cache_read(): Failed', $e);
    }
}



/*
 * Write specified name object to cache
 */
function cache_write($name, $data){
    try{
        if(!file_exists($path = ROOT.'data/cache/')){
            mkdir($path);
        }

        file_put_contents($path.$name, $data);

        return $data;

    }catch(Exception $e){
        throw new bException('cache_write(): Failed', $e);
    }
}



/*
 * Clear the cache directory
 */
function cache_clear(){
    try{
        load_libs('file');

        file_delete_tree($path = ROOT.'data/cache/');
        mkdir($path);

    }catch(Exception $e){
        throw new bException('cache_clear(): Failed', $e);
    }
}
?>
