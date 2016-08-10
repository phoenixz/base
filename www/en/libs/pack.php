<?php
/*
 * Pack library
 *
 * This library contains functions to manage compressed files like zip, bzip2, rar, etc.
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */



/*
 * Pack with bzip2
 */
function pack_bzip2($source, $target = null){
    try{
        array_params($source, 'source');
        array_default($source, 'target', $target);

        safe_exec('bzip2 '.$source['source'].' '.$source['target']);

    }catch(Exception $e){
        throw new bException('pack_bzip2(): Failed', $e);
    }
}



/*
 * Unpack with bzip2
 */
function unpack_bzip2($source, $target = null){
    try{
        array_params($source, 'source');
        array_default($source, 'target', $target);

        safe_exec('unbzip2 '.$source['source'].' '.$source['target']);

    }catch(Exception $e){
        throw new bException('unpack_bzip2(): Failed', $e);
    }
}



/*
 * Pack with rar
 */
function pack_rar($source, $target = null){
    try{
        array_params($source, 'source');
        array_default($source, 'target', $target);

        safe_exec('rar a '.$source['source'].' '.$source['target']);

    }catch(Exception $e){
        throw new bException('pack_rar(): Failed', $e);
    }
}



/*
 * Unpack with rar
 */
function unpack_rar($source, $target = null){
    try{
        array_params($source, 'source');
        array_default($source, 'target', $target);

        safe_exec('unrar x '.$source['source'].' '.$source['target']);

    }catch(Exception $e){
        throw new bException('unpack_rar(): Failed', $e);
    }
}



/*
 * Pack with gzip
 */
function pack_gzip($source){
    try{
        array_params($source, 'source');
        array_default($source, 'target', $target);

        safe_exec('gzip '.$source['source'].' '.$source['target']);

        return $source['source'].'.gz';

    }catch(Exception $e){
        throw new bException('pack_gzip(): Failed', $e);
    }
}



/*
 * Unpack with gzip
 */
function unpack_gzip($source){
    try{
        array_params($source, 'source');
        array_default($source, 'target', $target);

        safe_exec('gunzip '.$source['source']);

        return substr($source['source'], 0, -3);

    }catch(Exception $e){
        throw new bException('unpack_gzip(): Failed', $e);
    }
}
?>
