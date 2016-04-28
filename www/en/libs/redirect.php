<?php
/*
 * Redirect library
 *
 * This library can redirect
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>
 */



/*
 * Do a redirect for the specified code
 */
function redirect_from_code($code){
    try{
        $url = sql_get('SELECT `url` FROM `redirects` WHERE `code` = :code', 'url', array(':code' => $code));

        if(!$url){
            throw new bException(tr('redirect_from_code(): Specified code ":code" does not exist', array(':code' => $code)), 'notexists');
        }

        redirect($url);

    }catch(Exception $e){
        throw new bException('redirect_from_code(): Failed', $e);
    }
}



/*
 * Add a code and the URL it should redirect to
 */
function redirect_add_code($code, $url){
    try{
        sql_query('INSERT INTO `redirects` (`code`, `url`)
                   VALUES                  (:code , :url )',

                   array(':code' => $code,
                         ':url'  => $url));

       return $code;

    }catch(Exception $e){
        throw new bException('redirect_add_code(): Failed', $e);
    }
}
?>
