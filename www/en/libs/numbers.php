<?php
/*
 * Limits the specified number between specified values
 *
 * These functions do not have a prefix
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>
 */



/*
 *
 */
function range_limit($number, $max, $min = null){
    if(is_numeric($max)){
        if($number > $max){
            return $max;
        }
    }

    if(is_numeric($min)){
        if($number < $min){
            return $min;
        }
    }

    return $number;
}



 /*
 * Format integer into Kilo / Mega / Giga Byte
 */
function bytes($value, $unit = 'auto', $precision = 2) {
    return bytes_convert($value, $unit, $precision, true);
}



/*
 * Convert specified amount explicitly to specified multiplier
 */
function bytes_convert($amount, $unit = 'auto', $precision = 2, $add_suffix = false){
    /*
     * Possibly shift parameters
     */
    if(is_bool($precision)){
        $precision  = 0;
        $add_suffix = $precision;
    }

    if(!$amount){
        $amount = 0;
    }

    $amount = str_replace(',', '', $amount);

    if(!is_numeric($amount)){
        /*
         * Calculate back to bytes
         */
        if(!preg_match('/(\d+(?:\.\d+)?)(\w{1,3})/', $amount, $matches))  {
            throw new bException('bytes_convert(): Specified amount "'.$amount.'" is not a valid byte amount. Format should be either n, or nKB, nKiB, etc');
        }

        switch(strtolower($matches[2])){
            case 'b':
                /*
                 * Just bytes
                 */
                $amount = $matches[1];
                break;

            case 'kb':
                /*
                 * Kilo bytes
                 */
                $amount = $matches[1] * 1000;
                break;

            case 'kib':
                /*
                 * Kibi bytes
                 */
                $amount = $matches[1] * 1024;
                break;

            case 'mb':
                /*
                 * Mega bytes
                 */
                $amount = $matches[1] * 1000000;
                break;

            case 'mib':
                /*
                 * Mibi bytes
                 */
                $amount = $matches[1] * 1048576;
                break;

            case 'gb':
                /*
                 * Giga bytes
                 */
                $amount = $matches[1] * 1000000 * 1000;
                break;

            case 'gib':
                /*
                 * Gibi bytes
                 */
                $amount = $matches[1] * 1048576 * 1024;
                break;

            case 'tb':
                /*
                 * Tera bytes
                 */
                $amount = $matches[1] * 1000000 * 1000000;
                break;

            case 'tib':
                /*
                 * Tibi bytes
                 */
                $amount = $matches[1] * 1048576 * 1048576;
                break;

            default:
                throw new bException('bytes_convert(): Specified suffix "'.$suffix.'" on amount "'.$amount.'" is not a valid. Should be one of b, or KB, KiB, mb, mib, etc');
        }
    }

    /*
     * We can only have an integer amount of bytes
     */
    $amount = ceil($amount);

    if(strtolower($unit) == 'auto'){
        /*
         * Auto determine what unit to use
         */
        if($amount > 1048576){
            if($amount > (1048576 * 1024)){
                if($amount > (1048576 * 1048576)){
                    $unit = 'tb';

                }else{
                    $unit = 'gb';
                }

            }else{
                $unit = 'mb';
            }

        }else{
            $unit = 'kb';
        }
    }

    /*
     * Convert to requested unit
     */
    switch(strtolower($unit)){
        case 'b':
            /*
             * Just bytes
             */
            break;

        case 'kb':
            /*
             * Kilo bytes
             */
            $amount = $amount / 1000;
            break;

        case 'kib':
            /*
             * Kibi bytes
             */
            $amount = $amount / 1024;
            break;

        case 'mb':
            /*
             * Mega bytes
             */
            $amount = $amount / 1000000;
            break;

        case 'mib':
            /*
             * Mibi bytes
             */
            $amount = $amount / 1048576;
            break;

        case 'gb':
            /*
             * Giga bytes
             */
            $amount = $amount / 1000000 / 1000;
            break;

        case 'gib':
            /*
             * Gibi bytes
             */
            $amount = $amount / 1048576 / 1024;
            break;

        case 'tb':
            /*
             * Tera bytes
             */
            $amount = $amount / 1000000 / 1000000;
            break;

        case 'tib':
            /*
             * Tibi bytes
             */
            $amount = $amount / 1048576 / 1048576;
            break;

        default:
            throw new bException('bytes_convert(): Specified unit "'.$unit.'" is not a valid. Should be one of b, or KB, KiB, mb, mib, etc');
    }

    $amount = number_format(round($amount, $precision), $precision);

    if(!$add_suffix){
        return $amount;
    }

    /*
     * Return amount with correct suffix.
     */
    switch(strlen($unit)){
        case 1:
            return $amount.'b';

        case 2:
            return $amount.strtoupper($unit);

        case 3:
            return $amount.strtoupper($unit[0]).strtolower($unit[1]).strtoupper($unit[2]);
    }
}



/*
 *
 */
function human_readable($number, $thousand = 1000, $decimals = 0){
    try{
        if($number > pow($thousand, 5)){
            return number_format($number / pow($thousand, 5), $decimals).'P';
        }

        if($number > pow($thousand, 4)){
            return number_format($number / pow($thousand, 4), $decimals).'T';
        }

        if($number > pow($thousand, 3)){
            return number_format($number / pow($thousand, 3), $decimals).'G';
        }

        if($number > pow($thousand, 2)){
            return number_format($number / pow($thousand, 2), $decimals).'M';
        }

        if($number > pow($thousand, 1)){
            return number_format($number / pow($thousand, 1), $decimals).'K';
        }

        return number_format($number, $decimals);

    }catch(Exception $e){
        throw new bException('human_readable(): Failed', $e);
    }
}
?>
