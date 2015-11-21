<?php
/*
 * Memcached library
 *
 * This library file contains functions to access memcached
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Johan Geuze, Sven Oostenbrink <support@svenoostenbrink.com>
 */



/*
 * Connect to the memcached server
 */
function mc_connect(){
    global $_CONFIG;

    try {
        if(empty($GLOBALS['memcached'])){
            /*
             * Memcached disabled?
             */
            if(!$_CONFIG['memcached']){
                $GLOBALS['memcached'] = false;

            }else{
                $failed               = 0;
                $GLOBALS['memcached'] = new Memcached;

                /*
                 * Connect to all memcached servers, but only if no servers were added yet
                 * (this should normally be the case)
                 */
                if(!$GLOBALS['memcached']->getServerList()){
                    $GLOBALS['memcached']->addServers($_CONFIG['memcached']['servers']);
                }

                /*
                 * Check connection status of memcached servers
                 * (To avoid memcached servers being down and nobody knows about it)
                 */
        //:TODO: Maybe we should check this just once every 10 connects or so? is it really needed?
                try{
                    foreach($GLOBALS['memcached']->getStats() as $server => $server_data){
                        if($server_data['pid'] < 0){
                            /*
                             * Could not connect to this memcached server. Notify, and remove from the connections list
                             */
                            $failed++;
                            notify('nomemcachedserver', 'Failed to connect to memcached server "'.str_log($server).'"');
                            log_error('Failed to connect to memcached server "'.str_log($server).'"', 'memcachedconnectfail');
                        }
                    }

                }catch(Exception $e){
                    /*
                     * Server status check failed, I think its safe
                     * to assume that no memcached server is working.
                     * Fake "all severs failed" so that memcached won't
                     * be used
                     */
                    $failed = count($_CONFIG['memcached']['servers']);
                }

                if($failed >= count($_CONFIG['memcached']['servers'])){
                    /*
                     * All memcached servers failed to connect!
                     * Send error notification
                     */
                    notify('nomemcachedserver', 'Failed to connect to all ('.count($_CONFIG['memcached']['servers']).') configured memcached servers');
                    throw new bException(tr('Failed to connect to all "%count%" configured memcached servers', array('%count%' => count($_CONFIG['memcached']['servers']))), 'memcachedconnectfail');
                }
            }
        }

        return $GLOBALS['memcached'];

    }catch(Exception $e){
        throw new bException('mc_connect(): failed', $e);
    }
}



/*
 *
 */
function mc_put($value, $key, $namespace = null, $expiration_time = null){
    global $_CONFIG;

    try {
        mc_connect();

        if($namespace){
            $namespace = mc_namespace($namespace).'_';
        }

// :DELETE: memcached accepts objects directly
        //if(is_scalar($value)){
        //    $value = $value;
        //
        //} else {
        //    load_libs('json');
        //    $value = json_encode_custom($value);
        //}

        if($expiration_time === null){
            /*
             * Use default cache expire time
             */
            $expiration_time = $_CONFIG['memcached']['expire_time'];
        }

        $GLOBALS['memcached']->set($_CONFIG['memcached']['prefix'].$namespace.$key, $value, $expiration_time);

        return $value;

    }catch(Exception $e){
        throw new bException('mc_put(): failed', $e);
    }
}



/*
 *
 */
function mc_add($value, $key, $namespace = null, $expiration_time = null){
    global $_CONFIG;

    try {
        mc_connect();

        if($namespace){
            $namespace = mc_namespace($namespace).'_';
        }

// :DELETE: memcached accepts objects directly
        //if(is_scalar($value)){
        //    $value = $value;
        //
        //} else {
        //    load_libs('json');
        //    $value = json_encode_custom($value);
        //}

        if($expiration_time === null){
            /*
             * Use default cache expire time
             */
            $expiration_time = $_CONFIG['memcached']['expire_time'];
        }

        if(!$GLOBALS['memcached']->add($_CONFIG['memcached']['prefix'].$namespace.$key, $value, $expiration_time)){

        }

        return $value;

    }catch(Exception $e){
        throw new bException('mc_add(): failed', $e);
    }
}



/*
 *
 */
function mc_replace($value, $key, $namespace = null, $expiration_time = null){
    global $_CONFIG;

    try {
        mc_connect();

        if($namespace){
            $namespace = mc_namespace($namespace).'_';
        }

// :DELETE: memcached accepts objects directly
        //if(is_scalar($value)){
        //    $value = $value;
        //
        //} else {
        //    load_libs('json');
        //    $value = json_encode_custom($value);
        //}

        if($expiration_time === null){
            /*
             * Use default cache expire time
             */
            $expiration_time = $_CONFIG['memcached']['expire_time'];
        }

        if(!$GLOBALS['memcached']->replace($_CONFIG['memcached']['prefix'].$namespace.$key, $value, $expiration_time)){

        }

        return $value;

    }catch(Exception $e){
        throw new bException('mc_replace(): failed', $e);
    }
}



/*
 *
 */
function mc_get($key, $namespace = null){
    global $_CONFIG;

    try {
        mc_connect();

        if($namespace){
            $namespace = mc_namespace($namespace).'_';
        }

        //get data from memcached, and json_decode if needed
        $value = $GLOBALS['memcached']->get($_CONFIG['memcached']['prefix'].$namespace.$key);

// :DELETE: memcached accepts objects directly
        //if(str_is_json($value)){
        //    load_libs('json');
        //    $value = json_decode_custom($value, true);
        //}

        return $value;

    }catch(Exception $e){
        throw new bException('mc_get(): Failed', $e);
    }
}



/*
 * Delete the specified key
 */
function mc_delete($key, $namespace = null){
    global $_CONFIG;

    try {
        mc_connect();

        if(!$key){
            if(!$namespace){
                throw new bException('mc_delete(): No key or namespace specified', $e);
            }

            $namespace = str_ends_not($namespace, '_');
            mc_namespace($namespace, true);

        }else{
            if($namespace = mc_get('namespace: '.$key)){
                return $namespace;
            }

            $GLOBALS['memcached']->delete($_CONFIG['memcached']['prefix'].$namespace.$key);
        }

    }catch(Exception $e){
        throw new bException('mc_delete(): Failed', $e);
    }
}



/*
 * clear the entire memcache
 */
function mc_clear($delay = 0){
    global $_CONFIG;

    try {
        mc_connect();
        $GLOBALS['memcached']->flush($delay);

    }catch(Exception $e){
        throw new bException('mc_delete(): Failed', $e);
    }
}



/*
 * Set or get the namespace for the specified key
 */
function mc_namespace($key, $reset = false){
    global $_CONFIG;
    static $keys = array();

    try{
        if(!$reset){
            if(!empty($keys[$key])){
                return $keys[$key];
            }

            if($namespace = mc_get('namespace: '.$key)){
                $keys[$key] = $namespace;
                return $namespace;
            }
        }

        $namespace  = $key.'.'.time();
        $keys[$key] = $namespace;

        return mc_add($key, $namespace);

    }catch(Exception $e){
        throw new bException('mc_namespace(): Failed', $e);
    }
}



/*
 * Wrapper
 */
//:OBSOLETE: Remove this function about a month after 20150430
function mc_del($key, $namespace){
    return mc_delete($key, $namespace);
}
?>
