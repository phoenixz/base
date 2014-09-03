<?php
/*
 * Cache library
 *
 * This library contains caching functions
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>
 */



/*
 * Auto load the memcached or file library
 */
switch($_CONFIG['cache']['method']){
    case 'file':
        load_libs('file');
        break;

    case 'memcached':
        load_libs('memcached');
        break;

    case false:
        /*
         * Cache has been disabled
         */
        return false;

    default:
        throw new bException('cache library: Unknown cache method "'.str_log($_CONFIG['cache']['method']).'" configured', 'unknown');
}



/*
 * Read from cache
 */
function cache_read($key){
    global $_CONFIG;

    try{
        $key = cache_key_hash($key);

        switch($_CONFIG['cache']['method']){
            case 'file':
                return cache_read_file($key);

            case 'memcached':
                return mc_get($key);

            case false:
                /*
                 * Cache has been disabled
                 */
                return false;

            default:
                throw new bException('cache_read(): Unknown cache method "'.str_log($_CONFIG['cache']['method']).'" specified', 'unknown');
        }

    }catch(Exception $e){
        throw new bException('cache_read(): Failed', $e);
    }
}



/*
 * Read from cache file
 */
function cache_read_file($key){
    try{
        if(!file_exists($file = ROOT.'data/cache/'.$key)){
            return false;
        }

        return file_get_contents($file);

    }catch(Exception $e){
        throw new bException('cache_read_file(): Failed', $e);
    }
}



/*
 * Read to cache
 */
function cache_write($key, $value, $expire = null){
    global $_CONFIG;

    try{
        $key = cache_key_hash($key);

        switch($_CONFIG['cache']['method']){
            case 'file':
                return cache_write_file($key, $value);

            case 'memcached':
                return mc_put($key, $value, $expire);

            case false:
                /*
                 * Cache has been disabled
                 */
                return $value;

            default:
                throw new bException('cache_write(): Unknown cache method "'.str_log($_CONFIG['cache']['method']).'" specified', 'unknown');
        }

    }catch(Exception $e){
        throw new bException('cache_write(): Failed', $e);
    }
}



/*
 * Write to cache file
 */
function cache_write_file($key, $value){
    try{
        $file = ROOT.'data/cache/'.$key;

        file_ensure_path(dirname($file));
        file_put_contents($file, $value);

        return $value;

    }catch(Exception $e){
        throw new bException('cache_write_file(): Failed', $e);
    }
}



/*
 * Return a hashed key
 */
function cache_key_hash($key){
    global $_CONFIG;

    try{
        switch($_CONFIG['cache']['key_hash']){
            case false:
                /*
                 * Don't do key hashing
                 */
                break;

            case 'md5':
                $key = md5($key);
                break;

            case 'sha1':
                $key = sha1($key);
                break;
        }

        if($_CONFIG['cache']['key_interlace']){
            $interlace = substr($key, 0, $_CONFIG['cache']['key_interlace']);
            $key       = substr($key, $_CONFIG['cache']['key_interlace']);

            return str_interleave($interlace, '/').'/'.$key;
        }

        return $key;

    }catch(Exception $e){
        throw new bException('cache_key_hash(): Failed', $e);
    }
}



/*
 * Clear the entire cache
 */
function cache_clear(){
    global $_CONFIG;

    try{
        switch($_CONFIG['cache']['method']){
            case 'file':
                /*
                 * Delete all cache files
                 */
                break;

            case 'memcached':
                /*
                 * Clear all keys from memcached
                 */
                break;

            default:
                throw new bException('cache_clear(): Unknown cache method "'.str_log($_CONFIG['cache']['method']).'" specified', 'unknown');
        }

    }catch(Exception $e){
        throw new bException('cache_clear(): Failed', $e);
    }
}
?>
