<?php
/*
 *
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>, Johan Geuze
 */



/*
 * Returns the diference in times with the pointed precision
 */
function time_difference($start, $stop, $precision = 'auto', $decimals = 2){
    try{
        $time  = cf($stop) - cf($start);

        switch($precision){
            case 'second':
                // FALLTHROUGH
            case 'seconds':
                $time = number_format($time, $decimals);
                return str_plural($time, tr(':time second', array(':time' => $time)), tr(':time seconds', array(':time' => $time)));

            case 'minute':
                // FALLTHROUGH
            case 'minutes':
                $time = number_format($time / 60, $decimals);
                return str_plural($time, tr(':time minute', array(':time' => $time)), tr(':time minutes', array(':time' => $time)));

            case 'hour':
                // FALLTHROUGH
            case 'hours':
                $time = number_format($time / 3600, $decimals);
                return str_plural($time, tr(':time hour', array(':time' => $time)), tr(':time hours', array(':time' => $time)));

            case 'day':
                // FALLTHROUGH
            case 'days':
                $time = number_format($time / 86400, $decimals);
                return str_plural($time, tr(':time day', array(':time' => $time)), tr(':time days', array(':time' => $time)));

            case 'week':
                // FALLTHROUGH
            case 'weeks':
                $time = number_format($time / 604800, $decimals);
                return str_plural($time, tr(':time week', array(':time' => $time)), tr(':time weeks', array(':time' => $time)));

            case 'month':
                //FALLTHROUGH
            case 'months':
                /*
                 * NOTE: Month is assumed 30 days!
                 */
                $time    = number_format($time / 2592000, $decimals);
                return str_plural($time, tr(':time month', array(':time' => $time)), tr(':time months', array(':time' => $time)));

            case 'year':
                // FALLTHROUGH
            case 'years':
                /*
                 * NOTE: Year is assumed 365 days!
                 */
                $time    = number_format($time / 31536000, $decimals);
                return str_plural($time, tr(':time year', array(':time' => $time)), tr(':time years', array(':time' => $time)));

            case 'auto':
                if($time < 60){
                    /*
                     * Seconds
                     */
                    return time_difference($start, $stop, 'seconds', $decimals);
                }

                if($time / 60 < 60){
                    /*
                     * Minutes
                     */
                    return time_difference($start, $stop, 'minutes', $decimals);
                }

                if($time / 3600 < 24){
                    /*
                     * Hours
                     */
                    return time_difference($start, $stop, 'hours', $decimals);
                }

                if($time / 86400 < 7){
                    /*
                     * Days
                     */
                    return time_difference($start, $stop, 'days', $decimals);
                }

                if($time / 604800 < 52){
                    /*
                     * Weeks
                     */
                    return time_difference($start, $stop, 'weeks', $decimals);
                }

                if($time / 2592000 < 12){
                    /*
                     * Months
                     */
                    return time_difference($start, $stop, 'months', $decimals);
                }

                /*
                 * Years
                 */
                return time_difference($start, $stop, 'years', $decimals);

            default:
                throw new bException(tr('time_difference(): Unknown precision ":precision" specified', array(':precision' => $precision)), 'unknown');
        }

    }catch(Exception $e){
        throw new bException('time_difference(): Failed', $e);
    }
}



/*
 * Returns "... days and hours ago" string.
 *
 * $original should be the original date and time in Unix format
 */
function time_ago($original){
    try{
        /*
         * Common time periods as an array of arrays
         */
        $periods = array(array(60 * 60 * 24 * 365 , tr('year')),
                         array(60 * 60 * 24 * 30  , tr('month')),
                         array(60 * 60 * 24 * 7   , tr('week')),
                         array(60 * 60 * 24       , tr('day')),
                         array(60 * 60            , tr('hour')),
                         array(60                 , tr('minute')));

        $today = time();
        $since = $today - $original; // Find the difference of time between now and the past

        // Loop around the periods, starting with the biggest
        for ($i = 0, $j = count($periods); $i < $j; $i++) {
            $seconds = $periods[$i][0];
            $name    = $periods[$i][1];

            // Find the biggest whole period
            if (($count = floor($since / $seconds)) != 0){
                break;
            }
        }

        $output = ($count == 1) ? '1 '.$name : "$count {$name}s";

        if ($i + 1 < $j) {
            // Retrieving the second relevant period
            $seconds2 = $periods[$i + 1][0];
            $name2    = $periods[$i + 1][1];

            // Only show it if it's greater than 0
            if (($count2 = floor(($since - ($seconds * $count)) / $seconds2)) != 0){
                $output .= ($count2 == 1) ? ', 1 '.$name2 : ", $count2 {$name2}s";
            }
        }

        return $output;

    }catch(Exception $e){
        throw new bException('time_ago(): Failed', $e);
    }
}



/*
 * Validates the given time.
 * Can check either hh:mm:ss, or hh:mm
 * Can check both 12H or 24H format
 */
function time_validate($time, $format = false, $separator = ':'){
    try{
        $time = trim($time);

        /*
         * Check for 12 hours format
         */
        if(!$format or ($format = '12')){
            if(preg_match('/^(0?\d|(?:1(?:0|1)))\s?'.$separator.'\s?((?:0?|[1-5])\d)(?:\s?'.$separator.'\s?((?:0?|[1-5])\d)|)\s*(am|pm)$/i', $time, $matches)){
                return array('time'    => $matches[1].$separator.$matches[2].($matches[3] ? $separator.$matches[3] : '').' '.strtoupper($matches[4]),
                             'format'  => '12',
                             'hours'   => $matches[1],
                             'minutes' => $matches[2],
                             'seconds' => $matches[3],
                             'period'  => strtoupper($matches[4]));
            }
        }

        /*
         * Check for 24 hours format
         */
        if(!$format or ($format = '24')){
            if(preg_match('/^((?:0?|1)\d|(?:2[0-3]))\s?'.$separator.'\s?((?:0?|[1-5])\d)(?:\s?'.$separator.'\s?((?:0?|[1-5])\d)|)$/', $time, $matches)){
                return array('time'    => $matches[1].$separator.$matches[2].(isset_get($matches[3]) ? $separator.$matches[3] : ''),
                             'format'  => '24',
                             'hours'   => $matches[1],
                             'minutes' => $matches[2],
                             'seconds' => isset_get($matches[3]));
            }
        }

        if($format){
            /*
             * The time format is either not valid at all, or not valid for the specifed 12H or 24H format
             */
            throw new bException('time_validate(): Specified time ":time" is not a valid ":format" format time', array(':time' => $time, ':format' => $format), 'invalid');
        }

        /*
         * The time format is not valid
         */
        throw new bException(tr('time_validate(): Specified time ":time" is not a valid time format', array(':time' => $time)), 'invalid');

    }catch(Exception $e){
        throw new bException('time_validate(): Failed', $e);
    }
}



/*
 * Format the specified time to 12H or 24H
 */
function time_format($time, $format = 24, $separator = ':'){
    $time = time_validate($time);

    switch($format){
        case 12:
            /*
             * Go for 12H format
             */
            if($time['format'] == '12'){
                return $time['time'];
            }

            if($time['hours'] > 11){
                $time['hours']  -= 12;
                $time['period']  = 'PM';

            }else{
                $time['period']  = 'AM';
            }

            if($time['seconds'] === null){
                return $time['hours'].$separator.$time['minutes'].' '.$time['period'];
            }

            return $time['hours'].$separator.$time['minutes'].$separator.$time['seconds'].' '.$time['period'];

        case 24:
            /*
             * Go for 24H format
             */
            if($time['format'] == '24'){
                return $time['time'];
            }

            if($time['period'] == 'PM'){
                $time['hours'] += 12;
            }

            if($time['seconds'] === null){
                return $time['hours'].$separator.$time['minutes'];
            }

            return $time['hours'].$separator.$time['minutes'].$separator.$time['seconds'];

        default:
            throw bException(tr('time_format(): Unknown format ":format" specified', array(':format' => $format)), 'unknown');
    }
}
?>
