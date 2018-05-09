<?php
/*
 * Formats library
 *
 * This library contains functions to format strings in the correct way for displaying
 * NOTE: These library functions do NOT verify the data!
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 */


/*
 * Return a correctly formatted phone number
 */
function formats_phone($phone){
    try{
        $phone = trim(str_replace(array(' ', '-', '.', '(', ')'), '', $phone));

        if($phone[0] == '+'){
            return substr($phone, 0, -10).' ('.substr($phone, -10, 3).') '.substr($phone, -7, 3).' '.substr($phone, -4, 4);

        }elseif(strlen($phone[0]) > 10){
            return '+'.substr($phone, 0, -10).' ('.substr($phone, -10, 3).') '.substr($phone, -7, 3).' '.substr($phone, -4, 4);

        }else{
            return '('.substr($phone, 0, 3).') '.substr($phone, 3, 3).' '.substr($phone, 6, 4);
        }

    }catch(Exception $e){
        throw new bException('formats_phone(): Failed', $e);
    }
}
?>
