<?php
    /*
     *
     */
    if($e->getCode() == 1049){
        if(!empty($retry)){
            static $retry = true;

            pdo_error_init($e, $_CONFIG['db'][$connector], $connector);
            return sql_init();
        }
    }

    $e = new bException('sql_init(): Failed', $e);

    if(empty($_CONFIG['db'][$connector])){
        throw new bException(tr('sql_init(): Specified database connector "%connector%" has not been configured', array('%connector%' => $connector)), 'notexist');
   }

    try{
        load_libs('pdo_error');
        return pdo_error($e, $_CONFIG['db'][$connector], null, isset_get($GLOBALS['sql_'.$connector]));

    }catch(Exception $e){
        throw new bException('sql_init(): Failed', $e);
    }
?>
