<?php
/*
 * persons_nams library
 *
 * This library contains functions to manage persons names
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */


/*
 *
 */
function persons_names_get($gender, $name_count, $lastname_count){
    try{
        $retval = '';

        switch(strtolower($gender)){
            case 'man':
                // FALLTHROUGH
            case 'boy':
                // FALLTHROUGH
            case 'male':
                $column = 'male';
                break;

            case 'woman':
                // FALLTHROUGH
            case 'girl':
                // FALLTHROUGH
            case 'female':
                $column = 'female';
                break;

            default:
                throw new bException('persons_names_get(): Unknown gender "'.str_log($gender).'" specified', 'unknown');
        }

        $names     = sql_list('SELECT `'.$column.'` FROM `persons_names` LIMIT '.$lastname_count, $column);
        $lastnames = sql_list('SELECT `'.$column.'` FROM `persons_names` LIMIT '.$lastname_count, $column);

        return $retval;

    }catch(Exception $e){
        throw new bException('persons_names_get(): Failed', $e);
    }
}
?>
