<?php
/*
 * Translate library
 *
 * This library will translate the given (english) texts to the current (or requested) languages
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>
 */


/*
 *
 */
function translate($text, $language = null){
    try{
        if(!$language){
            $language = LANGUAGE;
        }

        return $text;

    }catch(Exception $e){
        throw new lsException('translate(): Failed', $e);
    }
}
?>
