<?php
/*
 * Regex library
 *
 * This library contains functions that return regular expressions to recognize
 * certain data patterns correctly.
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 */


/*
 * Return regex to detect emails 99.99% correctly
 * See http://emailregex.com/
 */
function regex_email($simple){
    try{
        return '[-a-z0-9~!$%^&*_=+}{\'?]+(?:\.[-a-z0-9~!$%^&*_=+}{\'?]+)*@(?:[a-z0-9_][-a-z0-9_]*(?:\.[-a-z0-9_]+)*\.(?:aero|arpa|biz|com|coop|edu|gov|info|int|mil|museum|name|net|org|pro|travel|mobi|[a-z][a-z])|(?:[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}))(?::[0-9]{1,5})?';

    }catch(Exception $e){
        throw new bException('regex_email(): Failed', $e);
    }
}
?>
