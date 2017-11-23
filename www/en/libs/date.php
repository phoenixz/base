<?php
/*
 *
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>, Johan Geuze
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

        $params['resource'] = sql_query('SELECT   LCASE(SUBSTR(`Name`, 7)) AS `id`,
                                                        SUBSTR(`Name`, 7)  AS `name`

                                         FROM     `mysql`.`time_zone_name`

                                         WHERE    `Name` LIKE "posix%"

                                         ORDER BY `id`');

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
        return sql_get('SELECT `Time_zone_id` FROM `mysql`.`time_zone_name` WHERE LCASE(`Name`) = :Name', array(':Name' => 'posix/'.strtolower($timezone)));

    }catch(Exception $e){
        throw new bException(tr('date_timezones_exists(): Failed'), $e);
    }
}
?>
