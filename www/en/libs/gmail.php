<?php
/*
 * GMAIL library
 *
 * This library contains functions to related to gmail email pharsing
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Johan Geuze, Sven Oostenbrink <support@ingiga.com>
 */



/*
 * Extracts and returns the gmail forwarding code and source email address from
 * gmail forwarding email
 */
function gmail_get_vcode($data){
    try{
        /*
         * Attemps to find google verification codes
         */
        if(strstr($data['from'],'forwarding-noreply@google.com')){
            preg_match_all('/: \d{9}/', $data['text'], $matches_code);
            preg_match_all('/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}\b/', $data['subject'], $matches_from);

            /*
             * Returns code and email address
             */
            return array('code' => substr(isset_get($matches_code[0][0]), 2),
                         'from' => isset_get($matches_from[0][0]));
        }

    }catch(Exception $e){
        throw new bException(tr('gmail_get_vcode(): Failed'), $e);
    }
}
?>