<?php
/*
 * GEO library
 *
 * This contains all kinds of geography related functions
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */



/*
 * Get HTML countries select list
 */
function geo_location_from_ip($ip = null) {
    try{
        if(!$ip){
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        load_libs('geoip');

        $location         = geoip_get('187.163.219.201');
        $location['city'] = geo_get_nearest_city($location['latitude'], $location['longitude']);

        if($location['city']){
            $location['state']   = sql_get('SELECT `id`,
                                                   `code`,
                                                   `name`,
                                                   `seoname`

                                            FROM   `geo_countries`

                                            WHERE  `id` = :id', array(':id' => $location['city']['states_id']));

            $location['country'] = sql_get('SELECT `id`,
                                                   `code`,
                                                   `name`,
                                                   `seoname`

                                            FROM   `geo_countries`

                                            WHERE  `id` = :id', array(':id' => $location['city']['countries_id']));
        }

        return $location;

    }catch(Exception $e){
        throw new bException('geo_location_from_ip(): Failed', $e);
    }
}



/*
 * Get HTML countries select list
 */
function geo_countries_select($params) {
    try{
        array_params ($params);
        array_default($params, 'class'       , '');
        array_default($params, 'disabled'    , false);
        array_default($params, 'id_column'   , 'id');
        array_default($params, 'name'        , 'countries_id');
        array_default($params, 'none'        , tr('Select a country'));
        array_default($params, 'option_class', '');

        $cache_key = serialize($params);

        if($retval = cache_read($cache_key)){
            return $retval;
        }

        /*
         * If only one country is available, then select it automatically
         */
        $params['resource'] = sql_query('SELECT `'.$params['id_column'].'` AS `id`, `name` FROM `geo_countries` ORDER BY `name` ASC');

        return cache_write(html_select($params), $cache_key);

    }catch(Exception $e){
        throw new bException('geo_countries_select(): Failed', $e);
    }
}



/*
 * Get HTML states select list
 */
function geo_states_select($params) {
    try{
        array_params ($params);
        array_default($params, 'selected'        , '');
        array_default($params, 'class'           , '');
        array_default($params, 'disabled'        , false);
        array_default($params, 'id_column'       , 'id');
        array_default($params, 'name'            , 'states_id');
        array_default($params, 'none'            , tr('Select a state'));
        array_default($params, 'option_class'    , '');
        array_default($params, 'countries_column', 'countries_id');

        $cache_key = serialize($params);

        if($retval = cache_read($cache_key)){
            return $retval;
        }

        /*
         * Only show cities if a state has been selected
         */
        if(empty($params[$params['countries_column']])){
            /*
             * Don't show any cities at all
             */
            $params['resource'] = null;

        }else{
            /*
             * If only one state is available, then select it automatically
             */
            $params['resource'] = sql_query('SELECT `'.$params['id_column'].'` AS `id`, `name` FROM `geo_states` WHERE countries_id = :countries_id  ORDER BY `name` ASC', array(':countries_id' => $params['countries_id']));
        }

        return cache_write(html_select($params), $cache_key);

    }catch(Exception $e){
        throw new bException('geo_states_select(): Failed', $e);
    }
}



/*
 * Get HTML cities select list
 */
function geo_cities_select($params) {
    try{
        array_params ($params);
        array_default($params, 'selected'     , '');
        array_default($params, 'class'        , '');
        array_default($params, 'disabled'     , '');
        array_default($params, 'id_column'    , 'id');
        array_default($params, 'value_column' , 'name');
        array_default($params, 'name'         , '');
        array_default($params, 'none'         , tr('Select a city'));
        array_default($params, 'option_class' , '');
        array_default($params, 'states_column', 'states_id');

        $cache_key = serialize($params);

        if($retval = cache_read($cache_key)){
            return $retval;
        }

        /*
         * Only show cities if a state has been selected
         */
        if(empty($params[$params['states_column']])){
            /*
             * Don't show any cities at all
             */
            $params['resource'] = null;

        }else{
            $params['resource'] = sql_query('SELECT `'.$params['id_column'].'` AS `id`, `'.$params['value_column'].'` FROM `geo_cities` WHERE `states_id` = :states_id ORDER BY `name` ASC', array(':states_id' => $params['states_id']));
        }

        return cache_write(html_select($params), $cache_key);

    }catch(Exception $e){
        throw new bException('geo_cities_select(): Failed', $e);
    }
}



/*
 * Return the closest city to the specified latitude / longitude
 */
function geo_get_nearest_city($latitude, $longitude, $filters = null, $columns = '`id`, `name`, `seoname`, `counties_id`, `states_id`, `countries_id`, `latitude`, `longitude`'){
    global $_CONFIG;

    try{
        load_config('geo');

        $execute = array(':latitude'  => $latitude,
                         ':longitude' => $longitude);

        if(!$filters){
            $filters = $_CONFIG['geo']['cities']['filters'];
        }

        if($filters){
            foreach($filters as $key => $value){
                switch($key){
                    case 'status':
                        $where[]         = ' `status` IS NULL ';
                        break;

                    case 'min_population':
                        $execute[':min'] = $value;
                        $where[]         = ' `population` > :min ';
                        break;

                    case 'max_population':
                        $execute[':max'] = $value;
                        $where[]         = ' `population` < :max ';
                        break;

                    case 'feature_code':
                        $in              = sql_in(array_force($value), ':fc');
                        $execute         = array_merge($execute, $in);
                        $where[]         = ' `feature_code` IN ('.implode(',', array_keys($in)).') ';
                        break;

                    default:
                        throw new bException(tr('geo_get_nearest_city(): Unknown filter "%filter%" specified', array('%filter%' => str_log($key))), 'unknown');
                }
            }
        }

        if(!empty($where)){
            $where = ' WHERE ('.implode($_CONFIG['geo']['cities']['filter_type'], $where).')';

        }else{
            $where = '';
        }

        return sql_get('SELECT   '.$columns.',
                                 BASE_DISTANCE(`latitude`, `longitude`, :latitude, :longitude) AS `distance`

                        FROM     `geo_cities`

                        '.$where.'

                        ORDER BY `distance`

                        LIMIT    1',

                        $execute);

    }catch(bException $e){
        throw new bException('geo_get_nearest_city() Failed', $e);
    }
}
?>
