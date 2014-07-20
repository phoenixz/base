<?php
try{
    /*
     * First find the global data path. For now, either same height as this
     * project, OR one up the filesystem tree
     */
    $path = ROOT.'../';
    if(!file_exists($path.'data/')){
        $path = ROOT.'../../';

        if(!file_exists($path.'data/')){
            $path = '/var/www/';

            if(!file_exists($path.'data/')){
                if(PLATFORM != 'shell'){
                    throw new lsException('get_global_data_path(): Global data path not found', 'notfound');
                }

                try{
                    log_console('Warning: Global data path not found. Normally this path should exist either 1 directory up, 2 directories up, or in /var/www/data', 'notfound', 'yellow');
                    log_console('Warning: If you are sure this simply does not exist yet, it can be created now automaticall. If it should exist already, then abort this script and check the location!', 'notfound', 'yellow');
                    $path = script_exec('base/init_global_data_path');

                    if(!file_exists($path)){
                        /*
                         * Something went wrong and it was not created anyway
                         */
                        throw new lsException('get_global_data_path(): ./script/base/init_global_data_path reported path "'.str_log($path).'" was created but it could not be found', 'failed');
                    }

                    /*
                     * Its now created!
                     * Strip "data/"
                     */
                    $path = slash($path);

                    if(substr($path, -5, 5) == 'data/'){
                        $path = substr($path, 0, -5);
                    }

                }catch(Exception $e){
                    throw new lsException('get_global_data_path(): Global data path not found, or init_global_data_path failed / aborted', 'notfound');
                }
            }
        }
    }

    $path .= 'data/';

    /*
     * Now check if the specified section exists
     */
    if($section and !file_exists($path.$section)){
        if(!$force){
            throw new lsException('get_global_data_path(): The specified section "'.str_log($section).'" does not exist in the found global data path "'.str_log($path).'"', 'notfound');
        }

        load_libs('file');
        file_ensure_path($path.$section);
    }

    if(!$retval = realpath($path.$section)){
        /*
         * Curious, the path exists, but realpath failed and returned false..
         */
        throw new lsException('The found global data path "'.str_log($path).'" is invalid (realpath returned false)', 'invalid');
    }

    return slash($retval);

}catch(lsException $e){
    throw new lsException('get_global_data_path(): Failed', $e);
}
?>
