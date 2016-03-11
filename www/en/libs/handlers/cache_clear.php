<?php
/*
 * Clear all cache or portions of the cache
 */
global $_CONFIG;

try{
    if($key){
        $key = cache_key_hash($key);
    }

    switch($_CONFIG['cache']['method']){
        case 'file':
            load_libs('file');

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
    throw new bException('cache_clear(): Failed', $e);
}
?>
