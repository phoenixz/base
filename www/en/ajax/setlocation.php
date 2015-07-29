<?php
    include_once(dirname(__FILE__).'/../libs/startup.php');

    try{
        //load_libs('googlemaps,json');
        //
        //if(empty($_GET['latitude']) or !is_numeric($_GET['latitude']) or empty($_GET['longitude']) or !is_numeric($_GET['longitude'])){
        //    throw new bException('Invalid location parameters specified');
        //}
        //
        //$_SESSION['location'] = array('components' => array());
        //
        //$locations = googlemaps_reverse_geocoding($_GET['latitude'], $_GET['longitude']);
        //$address   = array();
        //$city      = array();
        //
        //if(empty($locations[0])){
        //    /*
        //     * No location found for the specified lat/long
        //     */
        //    unset($_SESSION['location']);
        //    throw new bException('No location data found', 'notfound');
        //}
        //
        ///*
        // * Get all address components
        // */
        //foreach($locations[0]['address_components'] as $component){
        //    $_SESSION['location']['components'][$component['types'][0]] = array('long_name'  => $component['long_name'],
        //                                                                        'short_name' => $component['short_name']);
        //}
        //
        //$_SESSION['location']['type']     = $locations[0]['geometry']['location_type'];
        //
        //$_SESSION['location']['geometry'] = array('latitude'  => $locations[0]['geometry']['location']['latitude'],
        //                                          'longitude' => $locations[0]['geometry']['location']['longitude'],
        //                                          'viewport'  => $locations[0]['geometry']['viewport']);
        //
        ///*
        // * Drop heavy array
        // */
        //unset($locations);
        //
        ///*
        // * Build city formatted address
        // */
        //foreach($_SESSION['location']['components'] as $key => $value){
        //    switch($key){
        //        case 'country':
        //            $city['a']       = $value['short_name'];
        //            $address['a']    = $value['short_name'];
        //            $country['code'] = $value['short_name'];
        //            $country['id']   = sql_get('SELECT `id` FROM `geo_countries` WHERE `code` = :code', 'id', array(':code' => $value['short_name']));
        //            break;
        //
        //        case 'administrative_area_level_1':
        //            $city['b']    = $value['short_name'];
        //            $address['b'] = $value['short_name'];
        //            break;
        //
        //        case 'administrative_area_level_2':
        //            if($_SESSION['location']['components']['locality'] != $value){
        //                $city['c']    = $value['short_name'];
        //                $address['c'] = $value['short_name'];
        //            }
        //
        //            break;
        //
        //        case 'locality':
        //            $city['d']    = $value['short_name'];
        //            $address['d'] = $value['short_name'];
        //            break;
        //
        //        case 'neighborhood':
        //            $address['f'] = $value['short_name'];
        //            break;
        //
        //        case 'route':
        //            if(empty($address['g'])){
        //                $address['g'] = $value['short_name'];
        //
        //            }else{
        //                $address['g'] = $address['g'].' '.$value['short_name'];
        //            }
        //            break;
        //
        //        case 'street_number':
        //            if(empty($address['g'])){
        //                $address['g'] = $value['short_name'];
        //
        //            }else{
        //                $address['g'] = $value['short_name'].' '.$address['g'];
        //            }
        //            break;
        //
        //        case 'postal_code':
        //            $address['e'] = $value['short_name'];
        //            break;
        //
        //        default:
        //            /*
        //             * Ignore
        //             */
        //            log_error('Unknown location component type "'.str_log($key).'" encountered');
        //    }
        //}
        //
        //krsort($city);
        //krsort($address);
        //
        //$_SESSION['location']['city']    = implode(', ', $city);
        //$_SESSION['location']['address'] = implode(', ', $address);
        //$_SESSION['location']['country'] = $country;

        load_libs('geo,json,googlemaps,json');
        load_config('geo');

        if(empty($_GET['latitude']) or !is_numeric($_GET['latitude']) or empty($_GET['longitude']) or !is_numeric($_GET['longitude'])){
            throw new bException('Invalid location parameters specified');
        }

        $_SESSION['location'] = array('components' => array());

// :TEST: This is Leon de los aldamas
//$_GET['latitude']=21.1219138;
//$_GET['longitude']=-101.6660115;

// :TEST: This is Queretaro de Santiago
//$_GET['latitude']=20.612137;
//$_GET['longitude']=-100.4069873;

// :TEST: This is Henderson / Las Vegas
//$_GET['latitude']=36.0671636;
//$_GET['longitude']=-115.0288261;

        switch($_CONFIG['geo']['lookup']){
            case 'geonames':
                $_SESSION['location']['detected'] = array('latitude'  => $_GET['latitude'],
                                                          'longitude' => $_GET['longitude']);

                $_SESSION['location']['city']     = geo_get_nearest_city($_GET['latitude'], $_GET['longitude']);
                $_SESSION['location']['state']    = c_get_state($_SESSION['location']['city']['states_id']);
                $_SESSION['location']['country']  = c_get_country($_SESSION['location']['state']['countries_id']);
                break;

            case 'googlemaps':
                $locations = googlemaps_reverse_geocoding($_GET['latitude'], $_GET['longitude']);
                $locations = $locations[2]['address_components'];

                $city    = str_convert_accents($locations[0]['long_name']);
                $state   = str_convert_accents($locations[2]['long_name']);
                $country = str_convert_accents($locations[3]['long_name']);

                /*
                 * Try to find the google indicated country
                 */
                $_SESSION['location']['country'] = c_get_country($country);

                if(!$_SESSION['location']['country']){
                    $_SESSION['location']['country'] = c_get_country('%'.$country.'%');

                    if(!$_SESSION['location']['country']){
                        throw new bException(tr('Indicated country "%country%" was not found', array('%country%' => $country)), 'notexists');
                    }
                }
        //show($_SESSION['location']['country']);

                /*
                 * Try to find the google indicated state
                 */
                $_SESSION['location']['state'] = c_get_state($state, $_SESSION['location']['country']['id']);

                if(!$_SESSION['location']['state']){
                    $_SESSION['location']['state'] = c_get_state('%'.$state.'%', $_SESSION['location']['country']['id']);

                    if(!$_SESSION['location']['state']){
                        throw new bException(tr('Indicated state "%state%" was not found', array('%state%' => $state)), 'notexists');
                    }
                }
        //show($_SESSION['location']['state']);

                /*
                 * Try to find the google indicated city
                 */
                $_SESSION['location']['city'] = c_get_city($city, $_SESSION['location']['state']['id']);

                if(!$_SESSION['location']['city']){
                    $_SESSION['location']['city'] = c_get_state('%'.$state.'%', $_SESSION['location']['state']['id']);

                    if(!$_SESSION['location']['city']){
                        throw new bException(tr('Indicated city "%city%" was not found', array('%city%' => $state)), 'notexists');
                    }
                }
        //show($_SESSION['location']['city']);

                $_SESSION['location']['detected'] = array('latitude'  => $_GET['latitude'],
                                                          'longitude' => $_GET['longitude']);
                break;

            default:
                throw new bException(tr('Unknown geo lookup method "%method%" specified', array('%method%' => $_CONFIG['geo']['lookup'])), 'unknown');
        }


// :TEST:
//showdie($_SESSION['location']);

        c_store_location();

        json_reply(c_domain_location(LANGUAGE, $_SESSION['location']['state']['seoname'], $_SESSION['location']['city']['seoname']).'.html', 'REDIRECT');

// :TEST:
//        json_reply(c_domain_location(LANGUAGE, '', ''), 'REDIRECT');

    }catch(Exception $e){
        json_error($e);
    }
?>
