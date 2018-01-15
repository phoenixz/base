<?php
try{
    global $_CONFIG;

    load_libs('mobile');

    if(!$_CONFIG['client_detect']) {
        $_SESSION['client'] = false;
        return mobile_detect();
    }

    if(!isset($_SESSION['client'])){
        if(PLATFORM_CLI){
            /*
             * This is a shell.
             */
            $_SESSION['client'] = array('js'      => false,
                                        'old'     => false,
                                        'type'    => 'shell',
                                        'brand'   => $_SERVER['SHELL'],
                                        'version' => '0',
                                        'info'    => false);

        }else{
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
        }
    }

    return mobile_detect();

}catch(Exception $e){
    throw new bException('client_detect(): Failed', $e);
}
?>
