<?php
try{
    global $_CONFIG;

    if(PLATFORM == 'shell'){
        /*
         * This is a shell, there is no client
         */
        throw new bException(tr('detect_location(): This function cannot be run from a cli shell'), 'invalid');
    }

    if(!$_CONFIG['location']['detect']){
        $_SESSION['location'] = array();
        return false;
    }

    load_libs('geo');
    $_SESSION['location'] = geo_location_from_ip();

    return $_SESSION['location'];

}catch(Exception $e){
    throw new bException('detect_location(): Failed', $e);
}
?>
