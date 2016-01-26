<?php
/*
 * Memcached library
 *
 * This library file contains functions to access memcached. It supports namespaces by keeping track of all variables
 * with namespaces in a separate array that contains the name of that namespace. This is VERY far from ideal, but the
 * best it can do. If this behaviour is not desired, then simply ensure that all keys have no namespace specified
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Johan Geuze, Sven Oostenbrink <support@svenoostenbrink.com>
 */



if(!class_exists('Memcached')){
    throw new bException(tr('php module "memcached" appears not to be installed. Please install the module first. On Ubuntu and alikes, use "sudo sudo apt-get -y install php5-memcached; sudo php5enmod memcached" to install and enable the module., on Redhat and alikes use ""sudo yum -y install php5-memcached" to install the module. After this, a restart of your webserver or php-fpm server might be needed'), 'not_available');
}



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
            $namespace = mc_add_to_namespace($namespace).'_';
        }

        if($expiration_time === null){
            /*
             * Use default cache expire time
             */
            $expiration_time = $_CONFIG['memcached']['expire_time'];
        }

        $GLOBALS['memcached']->set($_CONFIG['memcached']['prefix'].mc_namespace($namespace).$key, $value, $expiration_time);

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
            $namespace = mc_add_to_namespace($namespace).'_';
        }

        if($expiration_time === null){
            /*
             * Use default cache expire time
             */
            $expiration_time = $_CONFIG['memcached']['expire_time'];
        }

        if(!$GLOBALS['memcached']->add($_CONFIG['memcached']['prefix'].mc_namespace($namespace).$key, $value, $expiration_time)){
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
            $namespace = mc_add_to_namespace($namespace).'_';
        }

        if($expiration_time === null){
            /*
             * Use default cache expire time
             */
            $expiration_time = $_CONFIG['memcached']['expire_time'];
        }

        if(!$GLOBALS['memcached']->replace($_CONFIG['memcached']['prefix'].mc_namespace($namespace).$key, $value, $expiration_time)){

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
        return $GLOBALS['memcached']->get($_CONFIG['memcached']['prefix'].mc_namespace($namespace).$key);

    }catch(Exception $e){
        throw new bException('mc_get(): Failed', $e);
    }
}



/*
 * Delete the specified key or namespace
 */
function mc_delete($key, $namespace = null){
    global $_CONFIG;

    try {
        mc_connect();

        if(!$key){
            if(!$namespace){

            }

            /*
             * Delete the entire namespace
             */
            return mc_namespace($namespace, true);
        }

        return $GLOBALS['memcached']->delete($_CONFIG['memcached']['prefix'].mc_namespace($namespace).$key);

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
        throw new bException('mc_clear(): Failed', $e);
    }
}



/*
 * Increment the value of the specified key
 */
function mc_increment($key, $namespace = null){
    global $_CONFIG;

    try {
        mc_connect();
        $GLOBALS['memcached']->increment($_CONFIG['memcached']['prefix'].mc_namespace($namespace).$key);

    }catch(Exception $e){
        throw new bException('mc_increment(): Failed', $e);
    }
}



/*
 * Return a key for the namespace. We don't use the namespace itself as part of the key because
 * with an alternate key, its very easy to invalidate namespace keys by simply assigning a new
 * value to the namespace key
 */
function mc_namespace($namespace, $delete = false){
    try{
        if(!$namespace){
            return '';
        }

        $key = mc_get('ns:'.$namespace);

        if(!$key){
            $key = (string) microtime(true);
            mc_add($key, 'ns:'.$namespace);

        }elseif($delete){
            /*
             * "Delete" the key by incrementing (and so, changing) the value of the namespace key.
             * Since this will change the name of all keys using this namespace, they are no longer
             * accessible and with time will be dumped automatically by memcached to make space for
             * newer keys.
             */
            try{
                mc_increment($namespace);
                $key = mc_get('ns:'.$namespace);

            }catch(Exception $e){
                /*
                 * Increment failed, so in all probability the key did not exist. It could have been
                 * deleted by a parrallel process, for example
                 */
                switch($e->getCode()){
                    case '':
// :TODO: Implement correctly. For now, just notify
                    default:
                        notify($e);
                }
            }
        }

        return $key;

    }catch(Exception $e){
        throw new bException('mc_namespace(): Failed', $e);
    }
}
?>
