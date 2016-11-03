<?php
    /*
     *
     */
    switch($e->getCode()){
        case 1049:
            if(!empty($retry)){
                static $retry = true;

                sql_error_init($e, $_CONFIG['db'][$connector], $connector);
                return sql_init();
            }

            break;

        case 'notspecified':
            throw new bException('sql_init(): Failed', $e);
    }

    $e = new bException('sql_init(): Failed', $e);

    if(!is_string($connector)){
        throw new bException(tr('sql_init(): Specified database connector ":connector" is invalid, must be a string', array(':connector' => $connector)), 'invalid');
    }

    if(empty($_CONFIG['db'][$connector])){
        throw new bException(tr('sql_init(): Specified database connector ":connector" has not been configured', array(':connector' => $connector)), 'not-exist');
    }

    try{
        load_libs('sql_error');
        return sql_error($e, $_CONFIG['db'][$connector], null, isset_get($GLOBALS['sql_'.$connector]));

    }catch(Exception $e){
        throw new bException('sql_init(): Failed', $e);
    }
?>