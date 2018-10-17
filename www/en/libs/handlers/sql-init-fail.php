<?php
    /*
     *
     */
    switch($e->getCode()){
        case 1049:
            if(!empty($retry)){
                static $retry = true;
                global $_CONFIG;

                try{
                    $core->sql['core']->query('DROP DATABASE IF EXISTS `'.$connector['db'].'`;');
                    $core->sql['core']->query('CREATE DATABASE         `'.$connector['db'].'` DEFAULT CHARSET="'.$connector['charset'].'" COLLATE="'.$connector['collate'].'";');
                    $core->sql['core']->query('USE                     `'.$connector['db'].'`');
                    return true;

                }catch(Exception $e){
                    throw new bException('sql_init(): Failed', $e);
                }

                throw $e;
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
        return sql_error($e, $_CONFIG['db'][$connector], null, isset_get($core->sql[$connector]));

    }catch(Exception $e){
        throw new bException('sql_init(): Failed', $e);
    }
?>
