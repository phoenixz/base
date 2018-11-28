<?php
/*
 * GEO library
 *
 * This contains all kinds of geography related functions
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 */



/*
 * Initialize the library. Automatically executed by libs_load(). Will automatically load the ssh library configuration
 *
 * @auhthor Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package geo
 *
 * @return void
 */
function geo_library_init(){
    try{
        load_config('geo');

    }catch(Exception $e){
        throw new bException('geo_library_init(): Failed', $e);
    }
}



/*
 * Get HTML countries select list
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package geo
 *
 * @param $params
 */
function geo_countries_select($params) {
    try{
        array_params ($params);
        array_default($params, 'class'       , '');
        array_default($params, 'disabled'    , false);
        array_default($params, 'autosubmit'  , true);
        array_default($params, 'id_column'   , 'seoname');
        array_default($params, 'name'        , 'seocountry');
        array_default($params, 'none'        , tr('Select a country'));
        array_default($params, 'empty'       , tr('No countries available'));
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
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package geo
 *
 * @param $params
 */
function geo_states_select($params) {
    try{
        array_params ($params);
        array_default($params, 'selected'        , '');
        array_default($params, 'class'           , '');
        array_default($params, 'disabled'        , false);
        array_default($params, 'autosubmit'      , true);
        array_default($params, 'id_column'       , 'seoname');
        array_default($params, 'name'            , 'seostate');
        array_default($params, 'none'            , tr('Select a state'));
        array_default($params, 'empty'           , tr('No states available'));
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
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package geo
 *
 * @param $params
 */
function geo_cities_select($params) {
    try{
        array_params ($params);
        array_default($params, 'selected'     , '');
        array_default($params, 'class'        , '');
        array_default($params, 'disabled'     , '');
        array_default($params, 'id_column'    , 'seoname');
        array_default($params, 'value_column' , 'name');
        array_default($params, 'name'         , 'seocity');
        array_default($params, 'none'         , tr('Select a city'));
        array_default($params, 'empty'        , tr('No cities available'));
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
 * Get information about the specified country from the database and return it
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package geo
 *
 * @param $country
 * @return mixed
 */
function geo_get_country($country, $single_column = false){
    try{
        if(is_numeric($country)){
            $where   = ' WHERE `id` = :id AND `status` IS NULL';
            $execute = array(':id' => $country);

        }else{
            $where   = ' WHERE `seoname` = :seoname AND `status` IS NULL';
            $execute = array(':seoname' => $country);
        }

        if($single_column){
            if($single_column === true){
                $single_column = 'id';
            }

            $country = sql_get('SELECT `'.$single_column.'` FROM `geo_countries` '.$where, true, $execute);

        }else{
            $country = sql_get('SELECT `id`,
                                       `updatedon`,
                                       `geonames_id`,
                                       `continents_id`,
                                       `timezones_id`,
                                       `code`,
                                       `iso_alpha2`,
                                       `iso_alpha3`,
                                       `iso_numeric`,
                                       `fips_code`,
                                       `tld`,
                                       `currency`,
                                       `currency_name`,
                                       `phone`,
                                       `postal_code_format`,
                                       `postal_code_regex`,
                                       `languages`,
                                       `neighbours`,
                                       `equivalent_fips_code`,
                                       `latitude`,
                                       `longitude`,
                                       `alternate_names`,
                                       `name`,
                                       `seoname`,
                                       `capital`,
                                       `areainsqkm`,
                                       `population`,
                                       `moddate`,
                                       `status`

                                FROM   `geo_countries`'.

                                $where,

                                $execute);
        }

        return $country;

    }catch(Exception $e){
        throw new bException('geo_get_country(): Failed', $e);
    }
}



/*
 * Get information about the specified country from the database and return it
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package geo
 *
 * @param $country
 * @return mixed
 */
function geo_get_state($state, $country = null, $single_column = false){
    try{
        if(is_numeric($state)){
            $where   = ' WHERE `id` = :id AND `status` IS NULL';
            $execute = array(':id' => $state);

        }else{
            $where   = ' WHERE `seoname` = :seoname AND `status` IS NULL';
            $execute = array(':seoname' => $state);
        }

        if($country){
            if(is_numeric($country)){
                $country = geo_get_country($country, 'code');

            }elseif(is_string($country)){
                if(strlen($country) != 2){
                    $country = geo_get_country($country, 'code');
                }

            }else{
                throw new bException(tr('geo_get_state(): Invalid country ":country" specified', array(':country' => $country)), 'invalid');
            }

            $where  .= ' AND `country_code` = :country_code';
            $execute[':country_code'] = $country;
        }

        if($single_column){
            if($single_column === true){
                $single_column = 'id';
            }

            $state = sql_get('SELECT `'.$single_column.'` FROM `geo_states` '.$where, true, $execute);

        }else{
            $state = sql_get('SELECT `id`,
                                     `geonames_id`,
                                     `countries_id`,
                                     `country_code`,
                                     `timezones_id`,
                                     `code`,
                                     `name`,
                                     `seoname`,
                                     `alternate_names`,
                                     `latitude`,
                                     `longitude`,
                                     `population`,
                                     `elevation`,
                                     `admin1`,
                                     `admin2`,
                                     `moddate`,
                                     `status`,
                                     `filter`

                              FROM   `geo_states`'.

                              $where,

                              $execute);
        }

        return $state;

    }catch(Exception $e){
        throw new bException('geo_get_state(): Failed', $e);
    }
}



/*
 * Get information about the specified country from the database and return it
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package geo
 *
 * @param $country
 * @return mixed
 */
function geo_get_city($city, $state = null, $country = null, $single_column = false){
    try{
        if(is_numeric($city)){
            $where   = ' WHERE `id` = :id AND `status` IS NULL';
            $execute = array(':id' => $city);

        }else{
            if($state){
                if(!is_numeric($state)){
                    $state = geo_get_state($state, $country, 'id');
                }

                $where   = 'WHERE  `seoname`   = :seoname
                            AND    `states_id` = :states_id
                            AND    `status`    IS NULL';

                $execute = array(':seoname'   => $city,
                                 ':states_id' => $state);

            }else{
                $where   = 'WHERE  `seoname` = :seoname
                            AND    `status`  IS NULL';

                $execute = array(':seoname' => $city);
            }

            if($country){
                if(is_numeric($country)){
                    $country = geo_get_country($country, 'code');

                }elseif(is_string($country)){
                    if(strlen($country) != 2){
                        $country = geo_get_country($country, 'code');
                    }

                }else{
                    throw new bException(tr('geo_get_state(): Invalid country ":country" specified', array(':country' => $country)), 'invalid');
                }

                $where .= ' AND `country_code` = :country_code';
                $execute[':country_code'] = $country;
            }
        }

        if($single_column){
            if($single_column === true){
                $single_column = 'id';
            }

            $city = sql_get('SELECT `'.$single_column.'`

                             FROM   `geo_cities` '.

                             $where,

                             true, $execute);

        }else{
            $city = sql_get('SELECT `id`,
                                    `updatedon`,
                                    `is_city`,
                                    `geonames_id`,
                                    `counties_id`,
                                    `states_id`,
                                    `countries_id`,
                                    `country_code`,
                                    `name`,
                                    `seoname`,
                                    `alternate_names`,
                                    `alternate_country_codes`,
                                    `latitude`,
                                    `longitude`,
                                    `elevation`,
                                    `admin1`,
                                    `admin2`,
                                    `population`,
                                    `timezones_id`,
                                    `timezone`,
                                    `feature_code`,
                                    `moddate`,
                                    `status`

                             FROM   `geo_cities`'.

                             $where,

                             $execute);
        }

        return $city;

    }catch(Exception $e){
        throw new bException('geo_get_city(): Failed', $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package geo
 *
 * @param $ip
 */
function geo_get_city_from_ip($ip = null, $filters = null, $single_column = false){
    global $_CONFIG;

    try{
        if(!$ip){
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        load_libs('geoip');

        $geo = geoip_get($ip);

        if($geo){
            return geo_get_city_from_location($geo['latitude'], $geo['longitude'], $filters, $single_column);
        }

        /*
         * geoip_get() failed to detect a city, go to the default city
         */
        return geo_get_city($_CONFIG['geo']['detect']['default']['city'], $_CONFIG['geo']['detect']['default']['state'], $_CONFIG['geo']['detect']['default']['country']);

    }catch(Exception $e){
        throw new bException('geo_get_city_from_ip(): Failed', $e);
    }
}



/*
 * Return the closest country to the specified latitude / longitude
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package geo
 *
 * @param $latitude
 * @param $longitude
 * @return
 */
function geo_get_country_from_location($latitude, $longitude, $single_column = false){
    global $_CONFIG;

    try{
        if($single_column){
            $country = sql_get('SELECT   `'.$single_column.'`,
                                         BASE_DISTANCE(`latitude`, `longitude`, :latitude, :longitude) AS `distance`

                                FROM     `geo_countries`

                                WHERE    `status` IS NULL

                                ORDER BY `distance`

                                LIMIT 1',

                                array(':latitude'  => $latitude,
                                      ':longitude' => $longitude));

            return $country[$single_column];
        }

        $country =  sql_get('SELECT   `id`,
                                      `updatedon`,
                                      `geonames_id`,
                                      `continents_id`,
                                      `timezones_id`,
                                      `code`,
                                      `iso_alpha2`,
                                      `iso_alpha3`,
                                      `iso_numeric`,
                                      `fips_code`,
                                      `tld`,
                                      `currency`,
                                      `currency_name`,
                                      `phone`,
                                      `postal_code_format`,
                                      `postal_code_regex`,
                                      `languages`,
                                      `neighbours`,
                                      `equivalent_fips_code`,
                                      `latitude`,
                                      `longitude`,
                                      `alternate_names`,
                                      `name`,
                                      `seoname`,
                                      `capital`,
                                      `areainsqkm`,
                                      `population`,
                                      `moddate`,
                                      `status`,

                                      BASE_DISTANCE(`latitude`, `longitude`, :latitude, :longitude) AS `distance`

                             FROM     `geo_countries`

                             WHERE    `status` IS NULL

                             ORDER BY `distance`

                             LIMIT    1',

                          array(':latitude'  => $latitude,
                                ':longitude' => $longitude));

        return $country;

    }catch(bException $e){
        throw new bException('geo_get_country_from_location() Failed', $e);
    }
}



/*
 * Return the closest state to the specified latitude / longitude
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package geo
 *
 * @param $latitude
 * @param $longitude
 */
function geo_get_state_from_location($latitude, $longitude, $single_column = false){
    global $_CONFIG;

    try{
        if($single_column){
            $state = sql_get('SELECT     `'.$single_column.'`,
                                         BASE_DISTANCE(`latitude`, `longitude`, :latitude, :longitude) AS `distance`

                                FROM     `geo_states`

                                WHERE    `status` IS NULL

                                ORDER BY `distance`

                                LIMIT 1',

                                array(':latitude'  => $latitude,
                                      ':longitude' => $longitude));

            return $state[$single_column];
        }

        $state =  sql_get('SELECT   `id`,
                                    `geonames_id`,
                                    `countries_id`,
                                    `country_code`,
                                    `timezones_id`,
                                    `code`,
                                    `name`,
                                    `seoname`,
                                    `alternate_names`,
                                    `latitude`,
                                    `longitude`,
                                    `population`,
                                    `elevation`,
                                    `admin1`,
                                    `admin2`,
                                    `moddate`,
                                    `status`,
                                    `filter`,

                                    BASE_DISTANCE(`latitude`, `longitude`, :latitude, :longitude) AS `distance`

                           FROM     `geo_states`

                           WHERE    `status` IS NULL

                           ORDER BY `distance`

                           LIMIT    1',

                           array(':latitude'  => $latitude,
                                 ':longitude' => $longitude));

        return $state;

    }catch(bException $e){
        throw new bException('geo_get_state_from_location() Failed', $e);
    }
}



/*
 * Return the closest city to the specified latitude / longitude
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package geo
 *
 * @param $latitude
 * @param $longitude
 * @return
 */
function geo_get_city_from_location($latitude, $longitude, $filters = null, $single_column = false){
    global $_CONFIG;

    try{
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
                        throw new bException(tr('geo_get_city_from_location(): Unknown filter ":filter" specified', array(':filter' => $key)), 'unknown');
                }
            }
        }

        if(!empty($where)){
            $where = ' WHERE ('.implode($_CONFIG['geo']['cities']['filter_type'], $where).')';

        }else{
            $where = '';
        }

        if($single_column){
            $city = sql_get('SELECT   `'.$single_column.'`,
                                      BASE_DISTANCE(`latitude`, `longitude`, :latitude, :longitude) AS `distance`

                             FROM     `geo_cities` '.$where.'

                             ORDER BY `distance`

                             LIMIT 1',

                             $execute);

            return $city[$single_column];
        }

        $city =  sql_get('SELECT   `id`,
                                   `updatedon`,
                                   `is_city`,
                                   `geonames_id`,
                                   `counties_id`,
                                   `states_id`,
                                   `countries_id`,
                                   `country_code`,
                                   `name`,
                                   `seoname`,
                                   `alternate_names`,
                                   `alternate_country_codes`,
                                   `latitude`,
                                   `longitude`,
                                   `elevation`,
                                   `admin1`,
                                   `admin2`,
                                   `population`,
                                   `timezones_id`,
                                   `timezone`,
                                   `feature_code`,
                                   `moddate`,
                                   `status`,

                                    BASE_DISTANCE(`latitude`, `longitude`, :latitude, :longitude) AS `distance`

                          FROM     `geo_cities`

                          '.$where.'

                          ORDER BY `distance`

                          LIMIT    1',

                          $execute);

        return $city;

    // :DELETE: This check will not work, since the center of the correct country MAY be further away than the center of the neighbouring country
        ///*
        // * Validate that the city we have is in the right country and state.
        // * If not, then we have a city with longitude / latitude in a different
        // * country than the longitude / latitude from the country. This means
        // * that the country where this longitude / latitude is in is not added
        // * into the geo_cities list
        // */
        //$country_code = geo_get_country_from_location($latitude, $longitude, 'code');
        //$states_id    = geo_get_state_from_location($latitude, $longitude, 'id');
        //
        //if($city['country_code'] != $country_code){
        //    /*
        //     * The located city is in the wrong country
        //     */
        //    log_file(tr('Location ":latitude,:longitude" detection gave city ":city" in country ":country", but that latitude,longitude should be in country ":actual_country"', array(':latitude' => $latitude, ':longitude' => $longitude, ':city' => $city['name'], ':country' => $city['country_code'], ':actual_country' => $country_code)));
        //    return geo_get_city($_CONFIG['geo']['detect']['default']['city'], $_CONFIG['geo']['detect']['default']['state'], $_CONFIG['geo']['detect']['default']['country']);
        //}
        //
        //if($city['states_id'] != $states_id){
        //    /*
        //     * The located city is in the wrong state
        //     */
        //    log_file(tr('Location ":latitude,:longitude" detection gave city ":city" in state ":state", but that latitude,longitude should be in state ":actual_state"', array(':latitude' => $latitude, ':longitude' => $longitude, ':city' => $city['name'], ':state' => $city['states_id'], ':actual_state' => $states_id)));
        //    return geo_get_city($_CONFIG['geo']['detect']['default']['city'], $_CONFIG['geo']['detect']['default']['state'], $_CONFIG['geo']['detect']['default']['country']);
        //}
        //
        //log_file(tr('Location ":latitude,:longitude" detection gave city ":city" in country ":country"', array(':latitude' => $latitude, ':longitude' => $longitude, ':city' => $city['name'], ':country' => $city['country_code'])));
        //return $city;

    }catch(bException $e){
        throw new bException('geo_get_city_from_location() Failed', $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package geo
 *
 * @param $ip
 */
function geo_get_location_from_city($city){
    try{
        $location['city']    = $city;
        $location['state']   = geo_get_state($city['states_id']);
        $location['country'] = geo_get_country($city['countries_id']);

        return $location;

    }catch(Exception $e){
        throw new bException('geo_get_location_from_city(): Failed', $e);
    }
}



/*
 * Detect client location
 *
 * This function will send javascript code to the client that will request the
 * location from the browser. If this is allowed, the location will be sent to
 * the configured URL. If this is denied, the configured URL can attempt a GEOIP
 * location lookup
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package geo
 *
 * @param params $params
 * @return string The HTML javascript required for client location detection
 */
function geo_detect_client_location($params = null){
    global $_CONFIG;

    try{
        html_load_js('base/base');

        array_params($params);

        array_default($params, 'success_url'     , domain($_CONFIG['geo']['detect']['urls']['success']));
        array_default($params, 'success_callback', '');
        array_default($params, 'success_error'   , '');

        array_default($params, 'fail_url'     , domain($_CONFIG['geo']['detect']['urls']['fail']));
        array_default($params, 'fail_callback', '');
        array_default($params, 'fail_error'   , '');

        $html = html_script('
            $.geoLocation(function(location){
                            $.post("'.$params['success_url'].'", location)
                                .done(function(location){
                                    '.$params['success_callback'].'
                                })
                                .fail(function(){
                                    '.$params['success_error'].'
                                });
                          },
                          function(location){
                            $.post("'.$params['fail_url'].'")
                                .done(function(location){
                                    '.$params['fail_callback'].'
                                })
                                .fail(function(){
                                    '.$params['fail_error'].'
                                });
                          });');

        return $html;

    }catch(bException $e){
        throw new bException('geo_detect_client_location() Failed', $e);
    }
}




/*
 * Validate geo detection data.
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package geo
 *
 * @param params $geo
 * @return array
 */
function geo_validate($geo){
    try{
        load_libs('validate');

        if(isset($geo['coords'])){
            $geo = $geo['coords'];
        }

        $v = new validate_form($geo, 'latitude,longitude,accuracy');

        $v->isNumeric($geo['longitude'], tr('Invalid longitude ":longitude" specified', array(':longitude' => $geo['longitude'])));
        $v->isBetween($geo['longitude'], -180, 180, tr('Invalid longitude ":longitude" specified', array(':longitude' => $geo['longitude'])));

        $v->isNumeric($geo['latitude'], tr('Invalid latitude ":latitude" specified', array(':latitude' => $geo['latitude'])));
        $v->isBetween($geo['longitude'], -90, 90, tr('Invalid latitude ":latitude" specified', array(':latitude' => $geo['latitude'])));

        if($geo['accuracy']){
            $v->isNumeric($geo['accuracy'], tr('Invalid accuracy ":accuracy" specified', array(':accuracy' => $geo['accuracy'])));
            $v->isBetween($geo['accuracy'], 0, 100000, tr('Invalid accuracy ":accuracy" specified', array(':accuracy' => $geo['accuracy'])));

        }else{
            $geo['accuracy'] = null;
        }

        $v->isValid();

        return $geo;

    }catch(bException $e){
        throw new bException('geo_validate() Failed', $e);
    }
}




/*
 * Expand the specified geo data with city, state, and country information and store it in $_SESSION[location]
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package geo
 *
 * @param params $geo
 * @return array The specified $geo data, possibly expanded, if specified so
 */
function geo_set_session($geo, $expand_location = true){
    try{
        $geo = geo_validate($geo);

        if($expand_location){
            /*
             * Add city, state and country data
             */
            $city     = geo_get_city_from_location($geo['latitude'], $geo['longitude']);
            $location = geo_get_location_from_city($city);

            $_SESSION['location'] = array('city'      => $location['city'],
                                          'state'     => $location['state'],
                                          'country'   => $location['country'],
                                          'latitude'  => $geo['latitude'],
                                          'longitude' => $geo['longitude'],
                                          'accuracy'  => $geo['accuracy']);


        }else{
            $_SESSION['location'] = array('latitude'  => $geo['latitude'],
                                          'longitude' => $geo['longitude'],
                                          'accuracy'  => $geo['accuracy']);
        }

        return $_SESSION['location'];

    }catch(bException $e){
        throw new bException('geo_set_session() Failed', $e);
    }
}



/*
 * OBSOLETE FUNCTIONS
 */
function geo_get_nearest_city($latitude, $longitude, $filters = null, $single_column = false){
    try{
        return geo_get_city_from_location($latitude, $longitude, $filters, $single_column);

    }catch(bException $e){
        throw new bException('geo_get_nearest_city() Failed', $e);
    }
}
?>
