<?php
/*
 * GEO library
 *
 * This contains all kinds of geography related functions
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */


// :DELETE: These codes can now be found in the database
///*
// * Return US states with their codes
// */
//function geo_us_states(){
//    try{
//        return array('al' => 'alabama',
//                     'ak' => 'alaska',
//                     'az' => 'arizona',
//                     'ar' => 'arkansas',
//                     'ca' => 'california',
//                     'co' => 'colorado',
//                     'ct' => 'connecticut',
//                     'de' => 'delaware',
//                     'dc' => 'columbia', //district of columbia
//                     'fl' => 'florida',
//                     'ga' => 'georgia',
//                     'hi' => 'hawaii',
//                     'id' => 'idaho',
//                     'il' => 'illinois',
//                     'in' => 'indiana',
//                     'ia' => 'iowa',
//                     'ks' => 'kansas',
//                     'ky' => 'kentucky',
//                     'la' => 'louisiana',
//                     'me' => 'maine',
//                     'md' => 'maryland',
//                     'ma' => 'massachusetts',
//                     'mi' => 'michigan',
//                     'mn' => 'minnesota',
//                     'ms' => 'mississippi',
//                     'mo' => 'missouri',
//                     'mt' => 'montana',
//                     'ne' => 'nebraska',
//                     'nv' => 'nevada',
//                     'nh' => 'new hampshire',
//                     'nj' => 'new jersey',
//                     'nm' => 'new mexico',
//                     'ny' => 'new york',
//                     'nc' => 'north carolina',
//                     'nd' => 'north dakota',
//                     'oh' => 'ohio',
//                     'ok' => 'oklahoma',
//                     'or' => 'oregon',
//                     'pa' => 'pennsylvania',
//                     'ri' => 'rhode island',
//                     'sc' => 'south carolina',
//                     'sd' => 'south dakota',
//                     'tn' => 'tennessee',
//                     'tx' => 'texas',
//                     'ut' => 'utah',
//                     'vt' => 'vermont',
//                     'va' => 'virginia',
//                     'wa' => 'washington',
//                     'wv' => 'west virginia',
//                     'wi' => 'wisconsin',
//                     'wy' => 'wyoming');
//
//    }catch(Exception $e){
//        throw new bException('geo_us_states(): Failed', $e);
//    }
//}



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
 * Return specified column (or all) for the specified country
 */
function geo_countries_get($country, $column = false){
    try{
        $country = sql_get_id_or_name($country, true, true);

        if(!$column){
            $columns = '*';

        }else{
            $columns = cfm($column);
        }

        return sql_get('SELECT '.$columns.' FROM geo_countries WHERE '.$country['where'], $column, $country['execute']);

    }catch(bException $e){
        throw new bException('geo_countries_get() Failed', $e);
    }
}



/*
 * Return specified column (or all) for the specified state
 */
function geo_states_get($state, $column = false){
    try{
        $state = sql_get_id_or_name($state);

        if(!$column){
            $columns = '*';

        }else{
            $columns = cfm($column);
        }

        return sql_get('SELECT '.$columns.' FROM geo_states WHERE '.$state['where'], $column, $state['execute']);

    }catch(bException $e){
        throw new bException('geo_states_get() Failed', $e);
    }
}



/*
 * Return specified column (or all) for the specified city
 */
function geo_cities_get($city, $column = false){
    try{
        $city = sql_get_id_or_name($city);

        if(!$column){
            $columns = '*';

        }else{
            $columns = cfm($column);
        }

        return sql_get('SELECT '.$columns.' FROM geo_cities WHERE '.$city['where'], $column, $city['execute']);

    }catch(bException $e){
        throw new bException('geo_cities_get() Failed', $e);
    }
}



/*
 * Return the closest city to the specified latitude / longitude
 */
function geo_get_nearest_city($latitude, $longitude, $filters = null, $columns = '`id`, `name`, `seoname`, `states_id`, `latitude`, `longitude`'){
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
                                 DISTANCE(`latitude`, `longitude`, :latitude, :longitude) AS distance

                        FROM     `geo_cities`

                        '.$where.'

                        ORDER BY `distance`

                        LIMIT 1',

                        $execute);

    }catch(bException $e){
        throw new bException('geo_get_nearest_city() Failed', $e);
    }
}
?>
