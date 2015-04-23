<?php
/*
 * Return the size of the cache
 */
global $_CONFIG;

try{
    switch($_CONFIG['cache']['method']){
        case 'file':
            load_libs('file,numbers');
            file_ensure_path(ROOT.'data/cache');
            return file_tree(ROOT.'data/cache', 'size');

        case 'memcached':
// :IMPLEMENT:
            break;

        case false:
            /*
             * Cache has been disabled
             */
            throw new bException(tr('cache_size(): Can not size for cache objects, cache has been disabled'), 'disabled');

        default:
            throw new bException(tr('cache_size(): Unknown cache method "%method%" specified',array('method' => str_log($_CONFIG['cache']['method']))), 'unknown');
    }

}catch(Exception $e){
    throw new bException('cache_size(): Failed', $e);
}
?>