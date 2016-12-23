<?php
/*
 * GEOIP library
 *
 * This library contains functions to manage GEO IP detection
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Johan Geuze, Sven Oostenbrink <support@ingiga.com>
 */



/*
 * Expects $data array from email pool fuction
 */
function get_gmail_vcode($data){
    /*
     * Attemps to find google verification codes
     */
    if (strpos($data['from'],'forwarding-noreply@google.com') !== false) {
        preg_match_all('/: \d{9}/', $data['text'], $matches_code);
        preg_match_all('/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}\b/', $data['subject'], $matches_from);

        /*
        * Returns code and email address
        */
        return array('code' => substr(isset_get($matches_code[0][0]), 2),
                     'from' => isset_get($matches_from[0][0]));
    }
}
?>