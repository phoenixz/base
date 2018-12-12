<?php
/*
 * Detections library
 *
 * This library contains functions to detect information from remote clients
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 */



/*
 * Sets client info in $_SESSION and returns it
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package detect
 * @see detect_client()
 * @see detect_mobile()
 * @see detect_location()
 * @see detect_language()
 *
 * @return void
 */
function detect(){
    global $core;

    try{
        /*
         * Detect what client we are dealing with
         * Detect at what location client is
         * Detect at what location client is
         * Detect what language client wants. Redirect if needed
         */
        $client   = detect_client();
        $mobile   = detect_mobile();
        $location = detect_location();
        $language = detect_language();

        $core->register('session', array('client'   => $client,
                                         'mobile'   => $mobile,
                                         'location' => $location,
                                         'language' => $language));

    }catch(Exception $e){
        throw new bException('detect(): Failed', $e);
    }
}



/*
 * Detects client data and returns it
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package detect
 *
 * @return void
 */
function detect_client(){
    global $_CONFIG;

    try{

        if(PLATFORM_CLI){
            /*
             * This is a shell, there is no client
             */
            throw new bException(tr('detect_client(): This function cannot be run from a cli shell'), 'invalid');
        }

        /*
         * This is a web client
         */
        $client = array('js'      => false,
                        'old'     => false,
                        'version' => '0');

        /*
         * Is this a spider / crawler / searchbot?
         */
        if(isset($_SERVER['HTTP_USER_AGENT']) and preg_match('/(Google|msnbot|Rambler|Yahoo|AbachoBOT|accoona|AcioRobot|ASPSeek|CocoCrawler|Dumbot|FAST-WebCrawler|GeonaBot|Gigabot|Lycos|MSRBOT|Scooter|AltaVista|IDBot|eStyle|Scrubby|facebookexternalhit|InternetSeer|NodePing|MJ12bot)/i', $_SERVER['HTTP_USER_AGENT'], $matches)){
            /*
             * Crawler!
             */
            $client['type']  = 'crawler';
            $client['brand'] = strtolower($matches[1]);
            $client['info']  = false;

        }else{
            /*
             * Browser!
             * Now determine browser capabilities
             */
            if(isset($_SERVER['HTTP_USER_AGENT']) and function_exists('get_browser')){
                try{
                    /*
                     * Ensure browscap file exist
                     */
                    $browscap_file = ini_get('browscap');

                    if(!$browscap_file){
                        throw new bException(tr('detect_client(): No browscap file configured'), 'not-specified');
                    }

                    if(!file_exists($browscap_file)){
                        throw new bException(tr('detect_client(): Configured browscap file ":file" does not exist', array(':file' => $browscap_file)), 'not-exist');
                    }

                    $ua = get_browser(null, true);
                    array_ensure($ua, 'majorver,minorver');

                }catch(Exception $e){
                    /*
                     * Invalid browscap configuration. Nothing to do here really
                     * but notify debs
                     */
                    notify($e);
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
                $client['brand'] = strtolower(isset_get($ua['browser'], ''));

                if(isset($ua['crawler']) and $ua['crawler']){
                    $client['type']    = 'crawler';

                }else{
                    $client['js']      = isset_get($ua['javascript'], false);
                    $client['type']    = 'browser';
                    $client['version'] = isset_get($ua['version']   , 0);
                    $client['old']     = false;

                    switch($client['brand']){
                        case 'firefox':
                            $client['old'] = ($ua['majorver'] and ($ua['majorver'] < 3));
                            break;

                        case 'chrome':
                            $client['old'] = ($ua['majorver'] and ($ua['majorver'] < 10));
                            break;

                        case 'safari':
                            $client['old'] = ($ua['majorver'] and ($ua['majorver'] < 4));
                            break;

                        case 'ie':
                            $client['old'] = ($ua['majorver'] and ($ua['majorver'] < 9));
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

            $client['info'] = $ua;
            unset($ua);
        }

        return $client;

    }catch(Exception $e){
        throw new bException('detect_client(): Failed', $e);
    }
}



/*
 * Detects client location and returns it
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package detect
 *
 * @return void
 */
function detect_location(){
    global $_CONFIG;

    try{
        if(PLATFORM_CLI){
            /*
             * This is a shell, there is no client
             */
            throw new bException(tr('detect_location(): This function cannot be run from a cli shell'), 'invalid');
        }

        if(!$_CONFIG['location']['detect']){
            return false;
        }

        load_libs('geo');
        return geo_location_from_ip();

    }catch(Exception $e){
        throw new bException('detect_location(): Failed', $e);
    }
}



/*
 * Sets language info in $_SESSION and returns it
 */
function detect_language(){
    global $_CONFIG;

    try{
        /*
         * Validate that the default configured language is supported by the system
         */
        if($_CONFIG['language']['supported']){
            if(empty($_CONFIG['language']['supported'][$_CONFIG['language']['default']])){
                throw new bException(tr('detect_language(): Invalid language ":language" specified as default language, see $_CONFIG[language][default]', array(':language' => $_CONFIG['language']['default'])), 'invalid');
            }
        }

        if(PLATFORM_CLI){
            /*
             * This is a shell, there is no client
             */
            throw new bException(tr('detect_language(): This function cannot be run from a cli shell'), 'invalid');
        }

        if(!$_CONFIG['language']['detect']){
            return $_CONFIG['language']['default'];
        }

        try{
            if(empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])){
                if(empty($_CONFIG['location'])){
                    /*
                     * Location has not been detected, so language cannot be detected either!
                     */
                    notify('language-detect-failed', 'developers', tr('Failed to detect langugage because the clients location could not be detected. This might be a configuration issue prohibiting the detection of the client location'));
                    $language = $_CONFIG['language']['default'];
                    return $language;
                }

                if(empty($location['country']['id'])){
                    $language = $_CONFIG['language']['default'];

                }else{
                    $language = sql_get('SELECT `languages` FROM `geo_countries` WHERE `id` = :id', true, array(':id' => $location['country']['id']));
                }

            }else{
                $language = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
            }

            $language = str_until($language, ',');
            $language = trim(str_until($language, '-'));

            /*
             * Is the requested language supported?
             */
            if($_CONFIG['language']['supported']){
                if(empty($_CONFIG['language']['supported'][$language])){
                    /*
                     * Not supported, fall back on default
                     */
                    $language = $_CONFIG['language']['default'];
                }

            }else{
                /*
                 * Not a multilingual system, use default
                 */
                $language = $_CONFIG['language']['default'];
            }

        }catch(Exception $e){
            notify($e);

            if(empty($language)){
                $language = $_CONFIG['language']['default'];
            }
        }

        return $language;

    }catch(Exception $e){
        throw new bException('detect_language(): Failed', $e);
    }
}



/*
 * Detects mobile client
 *
 * On first site access, if device is mobile, but site is not, this function will redirect to mobile version
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package mobile
 * @see detect()
 *
 * @return void
 */
function detect_mobile(){
    global $_CONFIG;

    try{
        if(PLATFORM != 'http'){
            return false;
        }

        /*
        * Detect mobile data (or get from cache)
        */
        $mobile = array('site' => false);

        /*
         * Detect if is mobile client or not
         */
        $useragent = strtolower(isset_get($_SERVER['HTTP_USER_AGENT'], ''));

        if(preg_match('/android.+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od|ad)|iris|kindle|lge |maemo|meego.+mobile|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i', $useragent) ||
            preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(di|rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr($useragent, 0, 4))){
            /*
             * This is a mobile device. Is it a known device? (iphone, ipad, ipod, android, or blackberry  are "known")
             */
            if(preg_match('/(ip(?:hone|od|ad)|android|blackberry)/i', $useragent, $matches)){
                if(($matches[0] == 'ipad') and !$_CONFIG['mobile']['tablets']){
                    /*
                    * IPad is not mobile!
                    */
                    $mobile['device'] = false;

                }else{
                    /*
                    * IPad is not mobile, BUT by config considered so anyway for testing purposes!
                    */
                    $mobile['device'] = $matches[0];
                }

            }else{
                /*
                * Its not a known device..
                */
                $mobile['device'] = 'unknown';
            }

        }else{
            /*
            * This is not a mobile device
            */
            $mobile['device'] = false;
            return false;
        }

        /*
        * Reset mobile enabled configuration to limited access
        */
// :WTF:
        if($_CONFIG['mobile']['enabled'] = has_rights('mobile')){
            /*
             * Autoredirect to mobile site?
             */
            if($_CONFIG['mobile']['auto_redirect']){
                $mobile['site'] = true;
            }
        }

        return $mobile;

    }catch(Exception $e){
        throw new bException('detect_language(): Failed', $e);
    }
}
?>