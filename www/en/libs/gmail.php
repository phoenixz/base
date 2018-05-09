<?php
/*
 * GMAIL library
 *
 * This library contains functions to related to gmail email pharsing
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Johan Geuze, Sven Oostenbrink <support@capmega.com>
 */



/*
 * Extracts and returns the gmail forwarding code and source email address from
 * gmail forwarding email
 */
function gmail_get_forward_code($email){
    try{
        /*
         * Attemps to find google verification codes
         */
        load_libs('regex');
        preg_match_all('/'.regex_email(true).'/i', $email['text']   , $matches_from);
        preg_match_all('/\d{9}/'                 , $email['subject'], $matches_code);

        if(!$matches_from[0]){
            throw new bException(tr('gmail_get_forward_code(): Could not find gmail forwarder address in specified email text'), 'not-found');
        }

        if(!$matches_code[0]){
            throw new bException(tr('gmail_get_forward_code(): Could not find gmail forwarding code in specified email text'), 'not-found');
        }

        /*
         * Returns code and email address
         */
        return array('code'   => substr(isset_get($matches_code[0][0]), 2),
                     'source' => isset_get($matches_from[0][0]),
                     'target' => isset_get($matches_from[0][1]));

    }catch(Exception $e){
        throw new bException(tr('gmail_get_forward_code(): Failed'), $e);
    }
}
?>