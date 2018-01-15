<?php
/*
 * Detections library
 *
 * This library contains functions to detect information from remote clients
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */


/*
 * Sets client info in $_SESSION and returns it
 */
function detect_client(){
    try{
        global $_CONFIG;

        load_libs('mobile');

        if(PLATFORM_CLI){
            /*
             * This is a shell, there is no client
             */
            throw new bException(tr('detect_client(): This function cannot be run from a cli shell'), 'invalid');
        }

        if(!$_CONFIG['client']['detect']){
            $_SESSION['client'] = false;
            return mobile_detect();
        }

        /*
         * This is a web client
         */
        $_SESSION['client'] = array('js'      => false,
                                    'old'     => false,
                                    'version' => '0');

        /*
         * Is this a spider / crawler / searchbot?
         */
        if(isset($_SERVER['HTTP_USER_AGENT']) and preg_match('/(Google|msnbot|Rambler|Yahoo|AbachoBOT|accoona|AcioRobot|ASPSeek|CocoCrawler|Dumbot|FAST-WebCrawler|GeonaBot|Gigabot|Lycos|MSRBOT|Scooter|AltaVista|IDBot|eStyle|Scrubby|facebookexternalhit|InternetSeer|NodePing)/i', $_SERVER['HTTP_USER_AGENT'], $matches)){
            /*
             * Crawler!
             */
            $_SESSION['client']['type']  = 'crawler';
            $_SESSION['client']['brand'] = strtolower($matches[1]);
            $_SESSION['client']['info']  = false;

        }else{
            /*
             * Browser!
             * Now determine browser capabilities
             */
            if(isset($_SERVER['HTTP_USER_AGENT']) and function_exists('get_browser')){
                try{
                    $ua = get_browser(null, true);
                    array_ensure($ua, 'majorver,minorver');

                }catch(Exception $e){
                    log_console($e);
                }

            }

            /*
             * W/O HTTP_USER_AGENT or get_browser there is no detection, so fill in data manually
             * Also, get_browser() might have failed
             */
            if(!isset($ua)){
                $ua = array('browser'    => 'unknown',
                            'majorver'   => 0,
                            'minorver'   => 0,
                            'version'    => '0.0',
                            'javascript' => true);

            }else{
                $_SESSION['client']['brand'] = strtolower(isset_get($ua['browser'], ''));

                if(isset($ua['crawler']) and $ua['crawler']){
                    $_SESSION['client']['type']    = 'crawler';

                }else{
                    $_SESSION['client']['js']      = isset_get($ua['javascript'], false);
                    $_SESSION['client']['type']    = 'browser';
                    $_SESSION['client']['version'] = isset_get($ua['version']   , 0);
                    $_SESSION['client']['old']     = false;

                    switch($_SESSION['client']['brand']){
                        case 'firefox':
                            $_SESSION['client']['old'] = ($ua['majorver'] and ($ua['majorver'] < 3));
                            break;

                        case 'chrome':
                            $_SESSION['client']['old'] = ($ua['majorver'] and ($ua['majorver'] < 10));
                            break;

                        case 'safari':
                            $_SESSION['client']['old'] = ($ua['majorver'] and ($ua['majorver'] < 4));
                            break;

                        case 'ie':
                            $_SESSION['client']['old'] = ($ua['majorver'] and ($ua['majorver'] < 9));
                            break;

                        case 'unknown':
                            // FALLTHROUGH

                        default:
                            /*
                            * Probably a crawler?
                            */
                        // :TODO: Implement
                    }
                }
            }

            $_SESSION['client']['info'] = $ua;
            unset($ua);
        }

        return mobile_detect();

    }catch(Exception $e){
        throw new bException('detect_client(): Failed', $e);
    }
}



/*
 * Sets location info in $_SESSION and returns it
 */
function detect_location(){
    try{
        global $_CONFIG;

        if(PLATFORM_CLI){
            /*
             * This is a shell, there is no client
             */
            throw new bException(tr('detect_location(): This function cannot be run from a cli shell'), 'invalid');
        }

        if(!$_CONFIG['location']['detect']){
            $_SESSION['location'] = array();
            return false;
        }

        load_libs('geo');
        $_SESSION['location'] = geo_location_from_ip();

        return $_SESSION['location'];

    }catch(Exception $e){
        throw new bException('detect_location(): Failed', $e);
    }
}



/*
 * Sets language info in $_SESSION and returns it
 */
function detect_language(){
    try{
        global $_CONFIG;

        if(PLATFORM_CLI){
            /*
             * This is a shell, there is no client
             */
            throw new bException(tr('detect_language(): This function cannot be run from a cli shell'), 'invalid');
        }

        if(!$_CONFIG['language']['detect']){
            $_SESSION['language'] = $_CONFIG['language']['default'];
            return $_SESSION['language'];
        }

        try{
            if(empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])){
                if(empty($_CONFIG['location'])){
                    /*
                     * Location information is not available, detect location information
                     * first
                     */
                    detect_location();
                }

                if(empty($_CONFIG['location'])){
                    /*
                     * Location could not be detected, so language cannot be detected either!
                     */
                    notify('language-detect-failed', tr('Failed to detect langugage because the clients location could not be detected. This might be a configuration issue prohibiting the detection of the client location', 'developers'));
                    $_SESSION['language'] = $_CONFIG['language']['default'];
                    return $_SESSION['language'];
                }

               $language = sql_get(' SELECT `languages` FROM `geo_countries` WHERE `id` = :id', true, array(':id' => $_SESSION['location']['country']['id']));

            }else{
                $language = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
            }

            $language = str_until($language, ',');
            $language = trim(str_until($language, '-'));

            /*
             * Is the requested language available?
             */
            if(empty($_CONFIG['language']['supported'][$language])){
                $language = $_CONFIG['language']['default'];

                if(empty($_CONFIG['language']['supported'][$language])){
                    throw new bException(tr('detect_language(): Invalid language ":language" specified as default language, see $_CONFIG[language][default]', array(':language' => $language)), 'invalid');
                }
            }

            $_SESSION['language'] = $language;

        }catch(Exception $e){
            notify($e);
        }

        return $_SESSION['language'];

    }catch(Exception $e){
        throw new bException('detect_language(): Failed', $e);
    }
}
?>