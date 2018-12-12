<?php
/*
 *
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>, Johan Geuze
 */



/*
 *
 */
function date_relative($timestamp, $now = null, $periods = null){
    try{
        if(!$now){
            $now = time();
        }

        if(!$periods){
            $periods = array(10       => tr('Right now'),
                             86400    => tr('Today'),
                             604800   => tr('Last week'),
                             31536000 => tr('This year'));
        }

        usort($periods);

        foreach($periods as $time => $label){
            if($timestamp < $time){
                return $label;
            }
        }

    }catch(Exception $e){
        throw new bException(tr('date_relative(): Failed'), $e);
    }
}



/*
 * Return a random date
 */
function date_random($min = null, $max = null){
    try{
        if($min){
            $min = new DateTime(date_convert($min, 'y-m-d'));
            $min = $min->getTimestamp();

        }else{
            $min = 1;
        }

        if($max){
            $max = new DateTime(date_convert($max, 'y-m-d'));
            $max = $max->getTimestamp();

        }else{
            $max = 2147483647;
        }

        $timestamp  = mt_rand($min, $max);
        return date("Y-m-d", $timestamp);

    }catch(Exception $e){
        throw new bException(tr('date_random(): Failed'), $e);
    }
}



/*
 * Returns the HTML for a timezone selection HTML select
 */
function date_timezones_select($params = null){
    try{
        array_params($params);
        array_default($params, 'name', 'timezone');

        $params['resource'] = date_timezones_list();
        asort($params['resource']);

// :DELETE: Remove MySQL requirement because production users will not have access to "mysql" database
        //$params['resource'] = sql_query('SELECT   LCASE(SUBSTR(`Name`, 7)) AS `id`,
        //                                                SUBSTR(`Name`, 7)  AS `name`
        //
        //                                 FROM     `mysql`.`time_zone_name`
        //
        //                                 WHERE    `Name` LIKE "posix%"
        //
        //                                 ORDER BY `id`');

        return html_select($params);

    }catch(Exception $e){
        throw new bException(tr('date_timezones_select(): Failed'), $e);
    }
}



/*
 * Returns true if the specified timezone exists, false if not
 */
function date_timezones_exists($timezone){
    try{
        return isset_get(date_timezones_list()[strtolower($timezone)]);

    }catch(Exception $e){
        throw new bException(tr('date_timezones_exists(): Failed'), $e);
    }
}



/*
 * Returns a list of all timezones supported by PHP
 */
function date_timezones_list(){
    try{
        $list = array();

        foreach(timezone_abbreviations_list() as $abbriviation => $zones){
            foreach($zones as $timezone){
                if(empty($timezone['timezone_id'])) continue;

                $list[strtolower($timezone['timezone_id'])] = $timezone['timezone_id'];
            }
        }

        return $list;

    }catch(Exception $e){
        throw new bException(tr('date_timezones_list(): Failed'), $e);
    }
}



/*
 * Return the specified $date with the specified $interval applied.
 * If $date is null, the default date from date_convert() will be used
 * $interval must be a valid ISO 8601 specification (see http://php.net/manual/en/dateinterval.construct.php)
 * If $interval is "negative", i.e. preceded by a - sign, the interval will be subtraced. Else the interval will be added
 * Return date will be formatted according to date_convert() $format
 */
function date_interval($date, $interval, $format = null){
    try{
        $date = date_convert($date, 'd-m-Y');
        $date = new DateTime($date);

        if(substr($interval, 0, 1) == '-'){
            $date->sub(new DateInterval(substr($interval, 1)));

        }else{
            $date->add(new DateInterval($interval));
        }

        return date_convert($date, $format);

    }catch(Exception $e){
        throw new bException('date_interval(): Failed', $e);
    }
}
?>
