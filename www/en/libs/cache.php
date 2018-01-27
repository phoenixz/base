<?php
/*
 * Cache library
 *
 * This library contains caching functions
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
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
        throw new bException(tr('Unknown cache method ":method" specified', array(':method' => $_CONFIG['cache']['method'])), 'unknown');
}



/*
 * Read from cache
 */
function cache_read($key = null, $namespace = null){
    global $_CONFIG, $core;

    try{
//        $key = SCRIPT.'_'.LANGUAGE.isset_get($_SESSION['user']['id']).'_'.$key;

        switch($_CONFIG['cache']['method']){
            case 'file':
                $key  = cache_key_hash($key);
                $data = cache_read_file($key, $namespace);
                break;

            case 'memcached':
                if($namespace){
                    $namespace = unslash($namespace);
                }

                $data = mc_get($key, $namespace);
                break;

            case false:
                /*
                 * Cache has been disabled
                 */
                return false;

            default:
                throw new bException(tr('cache_read(): Unknown cache method ":method" specified', array(':method' => $_CONFIG['cache']['method'])), 'unknown');
        }

        if(debug()){
            $data = str_replace(':query_count', $core->register('query_count'), $data);
        }

        return $data;

    }catch(Exception $e){
        throw new bException('cache_read(): Failed', $e);
    }
}



/*
 * Read from cache file.
 * File must exist and not have filemtime + max_age > now
 */
function cache_read_file($key, $namespace = null){
    global $_CONFIG;

    try{
        if($namespace){
            $namespace = slash($namespace);
        }

        if(!file_exists($file = ROOT.'data/cache/'.$namespace.$key)){
            return false;
        }

//show((filemtime($file) + $_CONFIG['cache']['max_age']));
//showdie(date('u'));
        if((filemtime($file) + $_CONFIG['cache']['max_age']) < date('u')){
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
function cache_write($value, $key = null, $namespace = null, $max_age = null){
    global $_CONFIG, $core;

    try{
        if(!$max_age){
            $max_age = $_CONFIG['cache']['max_age'];
        }

        switch($_CONFIG['cache']['method']){
            case 'file':
                $key = cache_key_hash($key);
                cache_write_file($value, $key, $namespace);
                break;

            case 'memcached':
                mc_put($value, $key, $namespace, $max_age);
                break;

            case false:
                /*
                 * Cache has been disabled
                 */
                return $value;

            default:
                throw new bException(tr('cache_write(): Unknown cache method ":method" specified', array(':method' => $_CONFIG['cache']['method'])), 'unknown');
        }

        if(debug()){
            $value = str_replace(':query_count', $core->register('query_count'), $value);
        }

        return $value;

    }catch(Exception $e){
        /*
         * Cache failed to write. Lets not die on this!
         *
         * Notify and continue without the cache
         */
        notify('cache_write_fail', tr('Failed write ":method" for key ":key"', array(':key' => $key, ':method' => $_CONFIG['cache']['method'])), 'development');
        return $value;
    }
}



/*
 * Write to cache file
 */
function cache_write_file($value, $key, $namespace = null){
    try{
        if($namespace){
            $namespace = slash($namespace);
        }

        $file = ROOT.'data/cache/'.$namespace.$key;

        file_ensure_path(dirname($file), 0770);
        file_put_contents($file, $value);
        chmod($file, 0660);

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
        try{
            get_hash($key, $_CONFIG['cache']['key_hash']);

        }catch(Exception $e){
            throw new bException(tr('Unknown key hash algorithm ":algorithm" configured in $_CONFIG[hash][key_hash]', array(':algorithm' => $_CONFIG['cache']['key_hash'])), $e);
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
 *
 */
function cache_showpage($key = null, $namespace = 'htmlpage', $etag = null){
    global $_CONFIG, $core;

    try{
        $core->register('page_cache_key', $key);

        if($_CONFIG['cache']['method']){
            /*
             * First try to apply HTTP ETag cache test
             */
            http_cache_test($etag);

            if($value = cache_read($key, $namespace)){
                http_headers(null, strlen($value));

                if(debug()){
                    $value = str_replace(':query_count', $core->register('query_count'), $value);
                }

                echo $value;
                die();
            }
        }

        return false;

    }catch(Exception $e){
        throw new bException('cache_showpage(): Failed', $e);
    }
}



/*
 * Clear the entire cache
 */
function cache_clear($key = null, $namespace = null){
    include('handlers/cache_clear.php');
}



/*
 * Return the total size of the cache
 */
function cache_size(){
    return include('handlers/cache_size.php');
}



/*
 * Return the total amount of files currently in cache
 */
function cache_count(){
    return include('handlers/cache_count.php');
}



/*
 * Return true if the file exists in cache and has not expired
 * Return false if the file does not exist, or was expired
 * If the file does exsit, but is expired, delete it to auto cleanup cache
 */
function cache_has_file($file, $max_age = null){
    global $_CONFIG;

    try{
        if(!$max_age){
            $max_age = $_CONFIG['cache']['max_age'];
        }

        if(!file_exists($file)){
            return false;
        }

        $mtime = filemtime($file);

        if((time() - $mtime) > $max_age){
            /*
             *
             */
            file_delete($file);
            return false;
        }

        return true;

    }catch(Exception $e){
        throw new bException(tr('cache_has_file(): Failed'), $e);
    }
}
?>
