<?php
//these are functions that have been removed from other library files, need to discuss with sven

/*
 * Check and return if the current user has access to the specified page.
 *
 * Specify no rights to just see if this is a registered user.
 */
function has_access($rights = '', $user = null){
    global $_CONFIG;

    try{
        if(!is_array($rights)){
            if(!is_string($rights)){
                throw new lsException('has_access(): Invalid rights specified, must be either a CSV string, or an array');
            }

            $rights = str_explode(',', $rights);
        }

        if(empty($_SESSION['user']) and !$user){
            return false;
        }

        /*
         * Check rights
         */
        if(!$user){
            /*
             * Standard rights check for this user.
             * ALL requested rights must be mached
             */
            foreach($rights as $right){
                if(!isset($_SESSION['user']['rights'][$right])){
                    /*
                     * mmm, no direct access.. Is this god maybe?
                     */
                    if(!isset($_SESSION['user']['rights']['god']) and $right != 'devil'){
                        /*
                         * So this is not your holyness either.. Too bad, byeee!
                         */
                        return false;
                    }
                }
            }

        }else{
            /*
             * Check rights for specified user
             * ALL requested rights must be mached
             */
            $user = users_get($user, 'rights');

            foreach($rights as $right){
                if(!isset($user['rights'][$right])){
                    /*
                     * mmm, no direct access.. Is this god maybe?
                     */
                    if(!isset($user['rights']['god'])){
                        /*
                         * So this is not your holyness either.. Too bad, byeee!
                         */
                        return false;
                    }
                }
            }
        }

        return true;

    }catch(Exception $e){
        throw new lsException('has_access(): Failed', $e);
    }
}



/*
 * Check if the current user has access to the specified page. If not, redirect to redirect page
 */
function access_or_redirect($rights = '', $redirect = null){
    global $_CONFIG;

    try{
        if(!has_access($rights)){
            if($_CONFIG['sessions']['signin']['force']){
                $redirect = $_CONFIG['redirects']['signin'];
            }

            redirect($redirect);
        }

    }catch(Exception $e){
        throw new lsException('access_or_redirect(): Failed', $e);
    }
}

/*
 * Returns all rights for the specified user
 */
function get_rights($user){
    global $_CONFIG, $pdo;

    try{
        if(!is_numeric($user)){
            if(!is_string($user)){
                if(!is_array($user)){
                    throw new lsException('get_rights(): Invalid user specified, either user id, user name, or valid user array containing user id or email.');
                }

                if(empty($user['id'])){
                    if(empty($user['email'])){
                        throw new lsException('get_rights(): Invalid user array specified, user array must contain either id and or email.');
                    }

                    $user = $user['email'];

                }else{
                    $user = $user['id'];
                }
            }
        }

        /*
         * From here on, $user is either numeric (id) or string (email)
         */
        if(is_numeric($user)){
            $query   = 'SELECT `rights`.`id`, `rights`.`name`

                        FROM   `users_rights`

                        JOIN   `rights`
                        ON     `rights`.`id`             = `users_rights`.`rights_id`

                        WHERE  `users_rights`.`users_id` = :users_id';

            $execute = array(':users_id' => $user);

        }else{
            /*
             * Get rights by email
             */
            $query   = 'SELECT `rights`.`id`, `rights`.`name`

                        FROM   `users`

                        JOIN   `users_rights`
                        ON     `users_rights`.`users_id` = `users`.`id`

                        JOIN   `rights`
                        ON     `rights`.`id`             = `users_rights`.`rights_id`

                        WHERE  `users`.`email`           = :email';

            $execute = array(':email' => $user);
        }

        $rights = array();
        $r      = $pdo->prepare($query);

        $r->execute($execute);

        if(!$r->rowCount()){
            /*
             * Hey, whats this? no rights?
             */
            load_libs('users');

            if(!users_exists($user)){
                throw new lsException('get_rights(): Specified user "'.str_log($user).'" does not exist', 'notexist');
            }
        }

        while($right = $r->fetch(PDO::FETCH_ASSOC)){
            $rights[$right['id']] = $right['name'];
        }

        return $rights;


    }catch(Exception $e){
        throw new lsException('get_rights(): Failed', $e);
    }
}
/*
 * Returns a name for the supported system statusses
 */
function status_name($status){
    switch($status){
        case 0:
            return 'normal';

        case -1:
            return 'deleted';

        default:
            return 'unknown';
    }
}



/*
 * Returns if the specified user is a god user or not.
 */
function is_god($user = null){
    try{
        if($user === null){
            if(empty($_SESSION['user'])){
                /*
                 * There is no user, so cant be god :)
                 */
                return false;
            }

            /*
             * Check user of this session
             */
            $user = $_SESSION['user'];
        }

        if(is_array($user)){
            if(!empty($user['rights'])){
                return isset($user['rights']['god']);
            }
        }

        $user = users_get($user, 'all');
        return isset($user['rights']['god']);

    }catch(Exception $e){
        throw new lsException('is_god(): Failed', $e);
    }
}

/*
 * Returns if this client has (or has no) access to the specified section / system of the site. that has limited access
 */
function has_limited_access($config_setting, $limited_section, $site = null){
	global $_CONFIG;

	/*
	* Both true and false can continue as normal
	*/
	if($config_setting === true){
		return true;
	}

	if($config_setting === false){
		return false;
	}

	if($config_setting and ($config_setting != 'limited')){
		throw new lsException('has_limited_access(): Invalid $config_setting value "'.$config_setting.'" specified. $config_setting can only be TRUE, FALSE or "limited"');
	}

	/*
	* Section MUST be specified
	*/
	if(!$limited_section){
		throw new lsException('has_limited_access(): No $limited_section specified');
	}

	/*
	* Site can optionally be forced - not auto detected
	*/
	if(!$site){
		if(isset($_SESSION['website']['url'])){
			$site = $_SESSION['website']['url'];

		}else{
			$site = $_SERVER['SERVER_NAME'];
		}
	}

	if($site == 'user'){
		if(isset($_SESSION['user'])){
			$site = $_SESSION['user']['email'];

		}else{
			$site = '';
		}
	}

	if(isset($_CONFIG['limited'][$limited_section])){
		/*
		* Access granted when either the limited_section is true, or if $site is in its array
		*/
		if(($_CONFIG['limited'][$limited_section] === true) or in_array($site, $_CONFIG['limited'][$limited_section])){
			return 'limited';
		}
	}

	return false;
}

/*
 * If has no access, give a 404
 */
function limited_access_404($config_setting, $limited_section, $site = null){
    if(!has_limited_access($config_setting, $limited_section, $site)){
        redirect('404.php');
    }
}

/*
 * Detects mobile client and site
 *
 * On first site access, if device is mobile, but site is not, this function will redirect to mobile version
 */
function mobile_detect(){
	global $_CONFIG;

	// :DEBUG:SVEN:20121005: Uncomment next line to force recognize as mobile
	//$_SESSION['mobile'] = 'iphone';

	if(PLATFORM != 'apache'){
		$_SESSION['mobile']      = false;
		$_SESSION['mobile_site'] = false;
		return;
	}

	/*
	* Ensure server data is clean
	*/
	$_SERVER['SERVER_NAME'] = cfm($_SERVER['SERVER_NAME']);
	$_SERVER['HTTP_HOST']   = cfm($_SERVER['HTTP_HOST']);

	/*
	* Detect mobile site
	*/
	$_SESSION['mobile_site'] = (strtolower(substr($_SERVER['SERVER_NAME'], 0, 2)) == 'm.');

	/*
	* Detect mobile data (or get from cache)
	*/
	if(!isset($_SESSION['mobile'])){
		/*
		* Detect if is mobile client or not
		*/
		$useragent = strtolower($_SERVER['HTTP_USER_AGENT']);

		if(preg_match('/android.+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od|ad)|iris|kindle|lge |maemo|meego.+mobile|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i', $useragent) ||
			preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(di|rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(
		a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr($useragent, 0, 4))){
			/*
			* This is a mobile device. Is it a known device? (iphone, ipad, ipod, android, or blackberry  are "known")
			*/
			if(preg_match('/(ip(?:hone|od|ad)|android|blackberry)/i', $useragent, $matches)){
				if(($matches[0] == 'ipad') and !$_CONFIG['mobile']['ipad']){
					/*
					* IPad is not mobile!
					*/
					$_SESSION['mobile'] = false;

				}else{
					/*
					* IPad is not mobile, BUT by config considered so anyway for testing purposes!
					*/
					$_SESSION['mobile'] = array('device' => $matches[0]);
				}

			}else{
				/*
				* Its not a known device..
				*/
				$_SESSION['mobile'] = array('device' => 'unknown');
			}

		}else{
			/*
			* This is not a mobile device
			*/
			$_SESSION['mobile'] = false;
		}

		/*
		* Reset mobile enabled configuration to limited access
		*/
		$_CONFIG['mobile']['enabled'] = has_limited_access($_CONFIG['mobile']['enabled'], 'mobile');

		if(!$_SESSION['mobile_site']){
			/*
			* Since this is fist session access, if we're mobile but not on a mobile site yet, redirect to the mobile version
			*/
			if($_SESSION['mobile'] and !$_SESSION['mobile_site'] and $_CONFIG['mobile']['enabled'] and $_CONFIG['mobile']['auto_redirect']){
				/*
				* Reset mobile host names to normal hostnames. We'll know its mobile by $_SESSION['mobile_site']
				*/
				if(in_array(strtolower(substr($_SERVER['SERVER_NAME'], 0, 4)), array('www.', 'dev.'))){
					domain_correct(4);
				}

				redirect('http://m.'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
			}
		}
	}

	if($_SESSION['mobile_site']){
		/*
		* We need the mobile library
		*/
		include_once(ROOT.'/libs/mobile.php');

		/*
		* Reset mobile host names to normal hostnames. We'll know its mobile by $_SESSION['mobile_site']
		*/
		domain_correct(2);

		/*
		* Reset mobile enabled configuration to limited access
		*/
		$_CONFIG['mobile']['enabled'] = has_limited_access($_CONFIG['mobile']['enabled'], 'mobile');

		/*
		* Do we allow mobile at all?
		*/
		if(!$_CONFIG['mobile']['enabled']){
			/*
			* Hey, mobile sites are not allowed at all by config!! Redirect to full site
			*/
			redirect('http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
		}

		/*
		* Reset allownotmobile configuration to limited access
		*/
		$_CONFIG['mobile']['allownotmobile'] = has_limited_access($_CONFIG['mobile']['allownotmobile'], 'allownotmobile');

		/*
		* Do we allow MOBILE FOR NOT MOBILE DEVICES?
		*/
		if(!$_SESSION['mobile'] and !$_CONFIG['mobile']['allownotmobile'] and (!isset($_GET['mobile_demo']) or !$_GET['mobile_demo'])){
			/*
			* Hey, we're on the mobile site with a not mobile client, and config does not allow this, and we are also not demo showing! Bail! Bail! Bail!
			*/
			redirect('http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
		}

		/*
		* Okay, we're on mobile site, make sure we have the mobile system library loaded
		*/
		include_once(dirname(__FILE__).'/mobile/system.php');

	}elseif(in_array(strtolower(substr($_SERVER['SERVER_NAME'], 0, 4)), array('www.', 'dev.'))){
		/*
		* Reset www host names to normal hostnames.
		*/
		domain_correct(4);

		if($_SESSION['mobile']){
			/*
			* We need the mobile library
			*/
			include_once(ROOT.'/libs/mobile.php');
		}
	}
}

/*
 * Sets client info in $_SESSION and returns it
 */
function client_detect(){
	global $_CONFIG;

	if($_CONFIG['browser_detect']!=true) {
		return mobile_detect();
	}

	unset($_SESSION['client']);

	if(!isset($_SESSION['client'])){
		if(PLATFORM == 'shell'){
			/*
			* This is a shell.
			*/
			$_SESSION['client'] = array('js'      => false,
						'old'     => false,
						'type'    => 'shell',
						'brand'   => $_SERVER['SHELL'],
						'version' => '0');

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

			}else{
				/*
				* Browser!
				* Now determine browser capabilities
				*/
				if(isset($_SERVER['HTTP_USER_AGENT'])){
					try{
						$ua = get_browser(null, true);

					}catch(Exception $e){
						//                        if(strpos($e->getMessage(), 'PHP ERROR [2] "get_browser(): browscap ini directive not set') !== false){
						//                            /*
						//                             * Dumb fuck forgot to set the browscap directive
						//                             */
						//                            log_error('client_detect(): PHP ini value browsecap not set! Auto setting now, but this should be set in php.ini!', 'browsecap');
						//showrandomdie();
						//                            ini_set('browscap', ROOT.'data/lite_php_browscap.ini');
						//                            return client_detect();
						//                        }

						throw new lsException('client_detect(): Failed', $e);
					}

					}else{
						/*
						* W/O HTTP_USER_AGENT there is no detection!
						*/
						$ua = array('browser'    => 'unknown',
							'majorver'   => 0,
							'minorver'   => 0,
							'version'    => '0.0',
							'javascript' => true);
					}

					$_SESSION['client']['brand'] = strtolower($ua['browser']);

					if(isset($ua['crawler']) and $ua['crawler']){
						$_SESSION['client']['type']    = 'crawler';

					}else{
						$_SESSION['client']['js']      = $ua['javascript'];
						$_SESSION['client']['type']    = 'browser';
						$_SESSION['client']['version'] = $ua['version'];
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
		}
	}

	return mobile_detect();
}

/*
 * Returns true if current client is on an old browser
 */
function has_old_browser(){
	if(empty($_SESSION['client'])){
		if(!client_detect()){
			return false;
		}
	}

	return $_SESSION['client']['old'];
}

/*
 * Returns if we're on mobile or not.
 */
function mobile(){
	global $_CONFIG;

	if(!isset($_SESSION['mobile_site'])){
		/*
		* Huh? This should not be happening, SESSION[site] should always automatically be set on session start!
		* Fix: Detect again
		*/
		client_detect();
	}

	return $_SESSION['mobile_site'];
}

/*
 * Returns HTML code indicating if a SELECT OPTION should be selected or not
 */
function html_is_selected($value1, $value2){
	if(isset($value1)){
		if($value1 == $value2){
			return ' selected="selected" ';
		}
	}

	return '';
}



/*
 * Returns a correct HTML header
 */
function html_header($params = array(), $script = ''){
    global $_CONFIG;

    try{
        $title       = (empty($params['title']) ? $_CONFIG['name'] : $params['title']);
        $charset     = $_CONFIG['charset'];
        $platform    = 'normal';

        $metatags    = '';
        $scripts     = '';
        $stylesheets = '';

        if(isset($params['metatags'])){
            if(is_array($params['metatags'])){
                foreach($params['metatags'] as $meta_tag){
                    $metatags .= $meta_tag;
                }

            }else{
                $metatags = $params['metatags'];
            }
        }

        if(isset($params['scripts'])){
            if(is_array($params['scripts'])){
                foreach($params['scripts'] as $script_name){
                    $scripts .= '<script type="text/javascript" src="'.slash($_CONFIG['cdn'][$platform]['js']).$script_name.'"></script>';
                }

            }else{
                $scripts = '<script type="text/javascript" src="'.slash($_CONFIG['cdn'][$platform]['js']).$params['scripts'].'"></script>';
            }
        }

        if(isset($params['stylesheets'])){
            if(is_array($params['stylesheets'])){
                foreach($params['stylesheets'] as $style_name){
                    $stylesheets .= '<link rel="stylesheet" type="text/css" href="'.slash($_CONFIG['cdn'][$platform]['css']).$style_name.'" />';
                }

            }else{
                $stylesheets = '<link rel="stylesheet" type="text/css" href="'.slash($_CONFIG['cdn'][$platform]['css']).$params['stylesheets'].'" />';
            }
        }

        return '<!DOCTYPE html>
    <html>
        <head>
            <title>'.$title.'</title>
            <meta http-equiv="Content-Type" content="text/html; charset='.$charset.'" />'.
            $metatags.'
            <link rel="stylesheet" type="text/css" href="'.slash($_CONFIG['cdn'][$platform]['css']).'jquery.min.css" />
            <link rel="stylesheet" type="text/css" href="'.slash($_CONFIG['cdn'][$platform]['css']).'style.css" />'.
            $stylesheets.'
            <script type="text/javascript" src="'.slash($_CONFIG['cdn'][$platform]['js']).'jquery.min.js"></script>'.
            $scripts.
            $script.'
        </head>
        <body>';

    }catch(Exception $e){
        throw new lsException('html_header(): Failed', $e);
    }
}



/*
 * Returns a correct HTML footer
 */
function html_footer(){
    return '</body>
    </html>';
}



/*
 * Returns an HTML pager
 */
function html_pager($url, $count, $page){
    global $_CONFIG;

    try{
        load_libs('inet');

        if(!is_numeric($page) or $page < 1){
            $page = 1;
        }

        /*
         * Get amount of namespaces, and pages
         */
        $displaypages = $_CONFIG['paging']['pages'];
        $pages        = ceil($count / $_CONFIG['paging']['count']);

        if($displaypages > $count){
            $displaypages = $count;
        }

        $firstpage = $page - floor($displaypages / 2);

        if($pages > $count){
            $pages = $count;
        }

        if($firstpage < 1){
            $firstpage = 1;
        }

        $lastpage = $firstpage + $_CONFIG['paging']['pages'];

        if($lastpage > $pages){
            $lastpage  = $pages;
            $firstpage = $lastpage - ($pages < $displaypages ? $pages : $displaypages) + 1;

        }elseif($lastpage - $firstpage >= $displaypages){
            $lastpage = $firstpage + $displaypages - 1;
        }

        $retval = '<div class="pager">'.tr('Page').' <strong>'.$page.'</strong> '.tr('of').' <strong class="pager">'.$pages.'</strong></div><ul class="pager">';

        if($pages > 1){
            $retval .= '<li class="pager first"><a class="pager" href="'.url_add_query($url, 'page=1').'">'.tr('First').'</a><span class="pager separator">-</span></li>';

            for($i = $firstpage; $i <= $lastpage; $i++){
                $retval .= '<li class="pager next"><a class="pager" href="'.url_add_query($url, 'page='.$i).'">'.$i.'</a><span class="pager separator">-</span></li>';
            }

            $retval .= '<li class="pager last"><a class="pager" href="'.url_add_query($url, 'page='.$pages).'">'.tr('Last').'</a></li>';
        }

        return $retval.'</ul>';

    }catch(Exception $e){
        throw new lsException('html_pager(): Failed', $e);
    }
}



/*
 * Returns the source string either as a normal string, or as an input()
 */
function html_input_access($source, $rights, $name, $class = ''){
    if(!has_access($rights)){
        return $source;
    }

    return '<input type="text" name="'.$name.'" id="'.$name.'" value="'.$source.'"'.($class ? ' class="'.$class.'"' : '').'>';
}



/*
 * Returns the source string either as a normal string, or as an input()
 */
function html_gender_select_access($selected, $rights, $name, $class=''){
    if(!$selected){
        $selected = 0;
    }

    if(!has_access($rights)){
        $genders = array(0 => 'unknown',
                         1 => 'male',
                         2 => 'female',
                         3 => 'other');

        return $genders[$selected];
    }

    return html_gender_select($selected, $name, $class);
}



/*
 * Returns an HTML gender select box
 */
function html_gender_select($selected, $name, $class = ''){
    $html = '<select name="'.$name.'" id="'.$name.'"'.($class ? ' class="'.$class.'"' : '').'>';

    $genders = array(0 => 'unknown',
                     1 => 'male',
                     2 => 'female',
                     3 => 'other');

    foreach($genders as $id => $gender){
        $html .= '<option value="'.$id.'"'.($class ? ' class="'.$class.'"' : '').(($selected == $id) ? ' selected' : '').'>'.ucfirst($gender).'<option>';
    }

    return $html.'</select>';
}



/*
 * Return an HTML script tag
 */
function html_script($script){
    if((strtolower(substr($script, 0, 7)) == 'http://') or (strtolower(substr($script, 0, 8)) == 'https://')){
// :TODO: What about scripts on this server?!?!
        return '<script type="text/javascript" src="'.$script.'"></script>';
    }

    return '<script type="text/javascript">'.$script.'</script>';
}


?>