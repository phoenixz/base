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
function mc_connect() {
    global $_CONFIG;

    try {
        if(isset($GLOBALS['memcached'])){
            /*
             * Memcached already initialized
             */
            return $GLOBALS['memcached'];
        }

        /*
         * Memcached disabled?
         */
        if(!$_CONFIG['memcached']){
            return $GLOBALS['memcached'] = false;
        }

        $failed = 0;
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
             * Disable memcached for this page load
             *
             * Send error notification
             */
            notify('nomemcachedserver', 'Failed to connect to all ('.count($_CONFIG['memcached']['servers']).') configured memcached servers');
            log_error('Failed to connect to all ('.count($_CONFIG['memcached']['servers']).') configured memcached servers', 'memcachedconnectfail');

            unset($GLOBALS['memcached']);
            return $GLOBALS['memcached'] = false;
        }

        return $GLOBALS['memcached'];

    }catch(Exception $e){
        throw new lsException('mc_connect(): failed', $e);
    }
}



/*
 *
 */
function mc_put($key, $value, $expiration_time = null) {
    global $_CONFIG;

    try {
        //connect if needed
        if(!mc_connect()){
            return false;
        }

// :DELETE: memcached accepts objects directly
        //if(is_scalar($value)) {
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

        $GLOBALS['memcached']->set($_CONFIG['memcached']['prefix'].$key, $value, $expiration_time);

    }catch(Exception $e){
        throw new lsException('mc_put(): failed', $e);
    }
}



/*
 *
 */
function mc_get($key) {
    global $_CONFIG;

    try {
        //connect if needed
        if(!mc_connect()){
            return false;
        }

        //get data from memcached, and json_decode if needed
        $value = $GLOBALS['memcached']->get($_CONFIG['memcached']['prefix'].$key);

// :DELETE: memcached accepts objects directly
        //if(str_is_json($value)) {
        //    load_libs('json');
        //    $value = json_decode_custom($value, true);
        //}

        return $value;

    }catch(Exception $e){
        throw new lsException('mc_get(): Failed', $e);
    }
}



/*
 *
 */
function mc_del($key) {
    global $_CONFIG;

    try {
        //connect if needed
        if(!mc_connect()){
            return false;
        }

        // Delete the key
        $GLOBALS['memcached']->delete($_CONFIG['memcached']['prefix'].$key);

    }catch(Exception $e){
        throw new lsException('mc_del(): Failed', $e);
    }
}
?>
