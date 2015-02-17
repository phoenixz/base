<?php
    /*
     *
     */
    if($e->getCode() == 1049){
        if(!empty($retry)){
            static $retry = true;

            pdo_error_init($e, $_CONFIG['db']);
            return sql_init();
        }
    }

    $e = new bException('sql_init(): Failed', $e);

    try{
        load_libs('pdo_error');
        return pdo_error($e, $_CONFIG['db'], null, isset_get($GLOBALS[$sql]));

    }catch(Exception $e){
        throw new bException('sql_init(): Failed', $e);
    }
?>