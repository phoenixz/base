<?php
/*
 * Clear all cache or portions of the cache
 */
global $_CONFIG;

if($key){
    $key = cache_key_hash($key);
}

try{
    /*
     * Clear normal cache
     */
    load_libs('file');

    switch($_CONFIG['cache']['method']){
        case 'file':

            if($group){
                if($key){
                    /*
                     * Delete only one cache file, and attempt to clear empty directories as possible
                     */
                    file_clear_path(ROOT.'data/cache/'.slash($group).$key);

                }else{
                    /*
                     * Delete specified group
                     */
                    file_delete_tree(ROOT.'data/cache/'.$group);
                }

            }elseif($key){
                /*
                 * Delete only one cache file, and attempt to clear empty directories as possible
                 */
                file_clear_path(ROOT.'data/cache/'.$key);

            }else{
                /*
                 * Delete all cache
                 */
                safe_exec('rm '.ROOT.'/data/cache/ -rf');
            }

            file_ensure_path(ROOT.'data/cache');
            log_console(tr('Cleared file caches from path ":path"', array(':path' => 'ROOT/data/cache')));
            break;

        case 'memcached':
            /*
             * Clear all keys from memcached
             */
            if($group){
                mc_delete(null, $group);

            }elseif($key){
                mc_delete($key, $group);

            }else{
                mc_clear();
            }

            log_console(tr('Cleared memchached caches from servers ":servers"', array(':servers' => $_CONFIG['memcached']['servers'])));
            break;

        case false:
            /*
             * Cache has been disabled, ignore
             */
            break;

        default:
            throw new bException(tr('cache_clear(): Unknown cache method "%method%" specified', array('%method%' => str_log($_CONFIG['cache']['method']))), 'unknown');
    }

}catch(Exception $e){
    notify('cache-clear/failed', $e);
    log_error($e);
}



/*
 * Clear bundler caches
 */
try{
    file_delete(ROOT.'www/en/pub/js/bundle*');
    file_delete(ROOT.'www/en/pub/css/bundle*');
    file_delete(ROOT.'www/en/admin/pub/js/bundle*');
    file_delete(ROOT.'www/en/admin/pub/css/bundle*');

    log_console(tr('Cleared bundler caches from paths ":path"', array(':path' => 'ROOT/pub/js/bundle,ROOT/pub/css/bundle')));

}catch(Exception $e){
    notify('cache-clear/failed', $e);
    log_error($e);
}

log_database('Cleared all caches', 'clearcache');
?>
