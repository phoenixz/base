<?php
/*
 * Hostile access detection library
 *
 * This library contains functions to detect and respond to hostile access to servers
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 */


// :TODO: ADD FOLLOWING RULES TO APACHE CONFIGURATION OF ALL SITES AND DEFAULT .htaccess FILES!!

/*
ServerTokens Prod
ServerSignature Off

RewriteCond %{QUERY_STRING} \=PHP[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12} [NC]
RewriteRule .* - [F]
*/



/*
 * Detect hostile URL's and auto block the offending IP
 */
function detect_hostile_urls(){
    try{
/*
?=PHPE9568F36-D428-11d2-A769-00AA001ACF42
?=PHPE9568F35-D428-11d2-A769-00AA001ACF42
?=PHPB8B5F2A0-3C92-11d3-A3A9-4C7B08C10000
?=PHPE9568F34-D428-11d2-A769-00AA001ACF42
*/
    }catch(Exception $e){
        throw new bException('detect_hostile_urls(): Failed', $e);
    }
}



/*
 * Detect certain amount of hostile IP's in one /8 or /16 block, and
 * csf block the entire block if so
 */
function detect_hostile_ip_block(){
    try{

    }catch(Exception $e){
        throw new bException('detect_hostile_ip_block(): Failed', $e);
    }
}



?>
