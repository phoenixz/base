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
function cache_read($key = null, $group = null){
    global $_CONFIG;

    try{
        if(!$key){
            $key = $_SERVER['REQUEST_URI'].(empty($_SESSION['mobile']['device']) ? '' : '_m');
        }

        $key = LANGUAGE.cache_key_hash($key);

        switch($_CONFIG['cache']['method']){
            case 'file':
                return cache_read_file($key, $group);

            case 'memcached':
                if($group){
                    $group = unslash($group);
                }

                return mc_get($key, $group);

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
 * Read from cache file.
 * File must exist and not have filemtime + max_age > now
 */
function cache_read_file($key, $group = null){
    global $_CONFIG;

    try{
        if($group){
            $group = slash($group);
        }

        if(!file_exists($file = ROOT.'data/cache/'.$group.$key)){
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
function cache_write($value, $key = null, $group = null){
    global $_CONFIG;

    try{
        if(!$key){
            $key = $_SERVER['REQUEST_URI'].(empty($_SESSION['mobile']['device']) ? '' : '_m');
        }

        $key = LANGUAGE.cache_key_hash($key);

        switch($_CONFIG['cache']['method']){
            case 'file':
                return cache_write_file($value, $key, $group);

            case 'memcached':
                return mc_put($value, $key, $group, $_CONFIG['cache']['max_age']);

            case false:
                /*
                 * Cache has been disabled
                 */
                return $value;

            default:
                throw new bException('cache_write(): Unknown cache method "'.str_log($_CONFIG['cache']['method']).'" configured in $_CONFIG[cache][method]', 'unknown');
        }

    }catch(Exception $e){
        throw new bException('cache_write(): Failed', $e);
    }
}



/*
 * Write to cache file
 */
function cache_write_file($value, $key, $group = null){
    try{
        if($group){
            $group = slash($group);
        }

        $file = ROOT.'data/cache/'.$group.$key;

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

            default:
                throw new bException(tr('Unknown key hash "%hash%" configured in $_CONFIG[hash][key_hash]', array('%hash%' => $_CONFIG['cache']['key_hash'])), 'unknown');
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
function cache_showpage($key = null, $namespace = 'htmlpage', $die = true){
    global $_CONFIG;

    try{
        if(true or $_CONFIG['cache']['method']){
            if(!$key){
                $key = $_SERVER['REQUEST_URI'].(empty($_SESSION['mobile']['device']) ? '' : '_m');
            }

            /*
             * First try to apply HTTP ETag cache test
             */
            http_cache_test();

            if($page = cache_read($key, $namespace)){
                http_headers(null, strlen($page));
                echo $page;

                if($die){
                    die();
                }
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
function cache_clear($key = null, $group = null){
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
?>
