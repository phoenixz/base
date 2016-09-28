<?php
/*
 * HTML library, containing all sorts of HTML functions
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */



/*
 * Only allow execution on shell scripts
 */
function html_only(){
    if(PLATFORM != 'http'){
        throw new bException('html_only(): This can only be done over HTML', 'htmlonly');
    }
}



/*
 * Generate and return the HTML footer
 */
function html_iefilter($html, $filter){
    try{
        if(!$filter){
            return $html;
        }

        if($mod = str_until(str_from($filter, '.'), '.')){
            return "\n<!--[if ".$mod.' IE '.str_rfrom($filter, '.')."]>\n\t".$html."\n<![endif]-->\n";

        }elseif($filter == 'ie'){
            return "\n<!--[if IE ]>\n\t".$html."\n<![endif]-->\n";
        }

        return "\n<!--[if IE ".str_from($filter, 'ie')."]>\n\t".$html."\n<![endif]-->\n";

    }catch(Exception $e){
        throw new bException('html_iefilter(): Failed', $e);
    }
}



/*
 * Store libs for later loading
 */
function html_load_css($files = '', $media = null){
    global $_CONFIG;

    try{
        if(!$files){
            $files = array();
        }

        if(!is_array($files)){
            if(!is_string($files)){
                throw new bException('html_load_css(): Invalid files specification');
            }

            $files = explode(',', $files);
        }

        $min = $_CONFIG['cdn']['min'];

        if(empty($GLOBALS['css'])){
            $GLOBALS['css'] = array();
        }

        foreach($files as $file){
            $GLOBALS['css'][$file] = array('min'   => $min,
                                           'media' => $media);
        }

}catch(Exception $e){
        throw new bException('html_load_css(): Failed', $e);
    }
}



/*
 * Display libs in header
 */
function html_generate_css(){
    global $_CONFIG;

    try{
        if(empty($GLOBALS['css'])){
            $GLOBALS['css'] = array();
        }

// :DELETE: admin pages and mobile pages are no longer supported
//        if($GLOBALS['page_is_admin']){
//            /*
//             * Use normal admin CSS
//             */
//            $GLOBALS['css']['admin'] = array('media' => null);
//
//        }elseif($GLOBALS['page_is_mobile'] or empty($_CONFIG['bootstrap']['enabled'])){
//            /*
//             * Use normal, default CSS
//             */
////            $GLOBALS['css']['style'] = array('media' => null);
//
//        }else{
//            /*
//             * Use bootstrap CSS
//             */
////            $GLOBALS['css'][$_CONFIG['bootstrap']['css']] = array('media' => null);
////            $GLOBALS['css']['style']                      = array('media' => null);
////            $GLOBALS['css'][''bootstrap-theme']           => array('media' => null),
//        }

        if(!empty($_CONFIG['cdn']['css']['post'])){
            $GLOBALS['css']['post'] = array('min' => $_CONFIG['cdn']['min'], 'media' => (is_string($_CONFIG['cdn']['css']['post']) ? $_CONFIG['cdn']['css']['post'] : ''));
        }

        $retval = '';
        $min    = $_CONFIG['cdn']['min'];

        foreach($GLOBALS['css'] as $file => $meta) {
            if(!$file) continue;

            $html = '<link rel="stylesheet" type="text/css" href="'.cdn_prefix('css/').(!empty($GLOBALS['page_is_mobile']) ? 'mobile/' : '').$file.($min ? '.min.css' : '.css').'"'.($meta['media'] ? ' media="'.$meta['media'].'"' : '').'>';

            if(substr($file, 0, 2) == 'ie'){
                $retval .= html_iefilter($html, str_until(str_from($file, 'ie'), '.'));

            }else{
                /*
                 * Hurray, normal stylesheets!
                 */
                $retval .= $html."\n";
            }
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('html_generate_css(): Failed', $e);
    }
}



/*
 * Store list of libs that should be loaded in the header
 *
 * $option may be either "async" or "defer", see http://www.w3schools.com/tags/att_script_async.asp
 */
function html_load_js($files = '', $option = null, $ie = null){
    global $_CONFIG;

    try{
        if(!$files){
            $files = array();
        }

        if(!is_array($files)){
            if(!is_string($files)){
                throw new bException('html_load_js(): Invalid files specification');
            }

            $files = explode(',', $files);
        }

        //if($min === null){
        //    $min = $_CONFIG['cdn']['min'];
        //}

        if(!isset($GLOBALS['js'])){
            $GLOBALS['js'] = array();
        }

        foreach($files as $file){
            if(substr($file, 0, 4) != 'http') {
                /*
                 * Compatibility code: ALL LOCAL JS FILES SHOULD ALWAYS BE SPECIFIED WITHOUT .js OR .min.js!!
                 */
// :TODO: SEND EMAIL NOTIFICATIONS IF THESE ARE FOUND!
                if(substr($file, -3, 3) == '.js'){
                    $file = substr($file, 0, -3);

                }elseif((substr($file, -3, 3) == '.js') or (substr($file, -7, 7) == '.min.js')){
                    $file = substr($file, 0, -7);
                }

            }

            $data = array();

            if($option){
                $data['option'] = $option;
            }

            if($ie){
                $data['ie'] = $ie;
            }

            $GLOBALS['js'][$file] = $data;
        }

    }catch(Exception $e){
        throw new bException('html_load_js(): Failed', $e);
    }
}



/*
 * Display libs in header and or footer
 */
function html_generate_js(){
    global $_CONFIG;

    try{
        /*
         * Shortcut to JS configuration
         */
        $js     = $_CONFIG['cdn']['js'];
        $min    = ($_CONFIG['cdn']['min'] ? '.min' : '');

        $libs   = array();
        $retval = '';
        $footer = '';

        /*
         * Set to load default JS libraries
         */
        foreach($js['default_libs'] as $lib){
            $libs[$lib] = array();
        }

        if(!empty($GLOBALS['js'])){
            $libs = array_merge($libs, $GLOBALS['js']);
        }

        $GLOBALS['js'] = $libs;

        if(empty($libs)){
            /*
             * There are no libraries to load
             */
            return '';
        }

        /*
         * Load JS libraries
         */
        foreach($libs as $file => $data) {
            if(!$file) continue;

            $check = str_rfrom(str_starts($file, '/'), '/');
            $file  = str_replace(array('<', '>'), '', $file);

// :TODO: jquery must also be able to be loaded from the footer
            //if($check == 'jquery')    continue; // jQuery js is always loaded in the header
            //if($check == 'bootstrap') continue; // bootstrap js is always loaded in the header

            if(substr($file, 0, 4) == 'http') {
                /*
                 * These are external scripts
                 */
                $html = '<script'.(!empty($data['option']) ? ' '.$data['option'] : '').' type="text/javascript" src="'.$file.'"></script>';

            }else{
                /*
                 * These are local scripts
                 *
                 * Check if linked libraries are enabled, and if so, if its part of any of these.
                 */
                if($js['use_linked']){
                    /*
                     * Since we may need to break out of multiple loops, keep track of break variable.
                     * Skip is used to check if a linked library has been sent or not and we can skip current library
                     */
                    $break = false;
                    $skip  = false;

                    foreach($js['linked'] as $linked => $libraries){
                        foreach($libraries as $library){
                            if($file == $library){
                                $break = true;

                                /*
                                 * This file is inside a linked library. Send the linked library instead
                                 */
                                if(!empty($js['linked'][$linked]['sent'])){
                                    /*
                                     * The linked library has already been sent
                                     */
                                    $skip  = true;

                                }else{
                                    $file = $linked;
                                }
                            }

                            if($break) break;
                        }

                        if($break) break;
                    }

                    if($skip) continue;
                }

                $html = '<script'.(!empty($data['option']) ? ' '.$data['option'] : '').' type="text/javascript" src="'.cdn_prefix('js/').$file.$min.'.js"></script>';
            }

            /*
             * Add the scripts with IE only filters?
             */
            if(isset_get($data['ie'])){
                $html = html_iefilter($html, $data['ie']);

            }else{
                $html = $html."\n";
            }

            if($check[0] == '>' or (!empty($js['load_delayed']) and ($check[0] != '<'))){
                /*
                 * Add this script in the footer
                 */
                $footer .= $html;

            }else{
                /*
                 * Add this script in the header
                 */
                $retval .= $html;
            }
        }

        /*
         * Should all JS scripts be loaded at the end (right before the </body> tag)?
         * This may be useful for site startup speedups
         */
        if(!empty($footer)){
            $GLOBALS['footer'] = $footer.isset_get($GLOBALS['footer'], '').isset_get($GLOBALS['script_delayed'], '');
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('html_generate_js(): Failed', $e);
    }
}



/*
 * Generate and return the HTML header
 */
function html_header($params = null, $meta = array()){
    global $_CONFIG;

    try{
        array_params($meta);
        array_params($params, 'title');

        array_default($params, 'http'          , 'html');
        array_default($params, 'captcha'       , false);
        array_default($params, 'doctype'       , 'html');
        array_default($params, 'html'          , '<html lang="'.LANGUAGE.'">');
        array_default($params, 'body'          , '<body>');
        array_default($params, 'title'         , isset_get($meta['title']));
        array_default($params, 'links'         , '');
        array_default($params, 'extra'         , '');
        array_default($params, 'favicon'       , true);
        array_default($params, 'prefetch_dns'  , $_CONFIG['prefetch']['dns']);
        array_default($params, 'prefetch_files', $_CONFIG['prefetch']['files']);

        if(!empty($params['js'])){
            html_load_js($params['js']);
        }

        /*
         * Load captcha javascript
         */
        if(!empty($_CONFIG['captcha']['type']) and $params['captcha']){
            switch($_CONFIG['captcha']['type']){
                case 'recaptcha':
                    html_load_js($_CONFIG['captcha']['recaptcha']['js-api']);
                    break;
            }
        }

        if(!empty($params['css'])){
            html_load_css($params['css']);
        }

        if(empty($meta['description'])){
            throw new bException(tr('html_header(): No header meta description specified for script "%script%" (SEO!)', array('%script%' => SCRIPT)), '');
        }

        if(empty($meta['keywords'])){
            throw new bException(tr('html_header(): No header meta keywords specified for script "%script%" (SEO!)', array('%script%' => SCRIPT)), '');
        }

        if(!empty($meta['noindex'])){
            $meta['robots'] = 'noindex';
            unset($meta['noindex']);
        }

        if(!empty($_CONFIG['meta'])){
            /*
             * Add default configured meta tags
             */
            $meta = array_merge($_CONFIG['meta'], $meta);
        }

        /*
         * Add viewport meta tag for mobile devices
         */
        if(!empty($_SESSION['mobile'])){
            if(empty($meta['viewport'])){
                $meta['viewport'] = isset_get($_CONFIG['mobile']['viewport']);
            }

            if(!$meta['viewport']){
                throw new bException(tr('html_header(): Meta viewport tag is not specified'), 'not-specified');
            }
        }

        if(!empty($params['canonical'])){
            $params['links'] .= '<link rel="canonical" href="'.$params['canonical']."\">\n";
        }

//:DELETE: Above is already a meta-viewport
        //if(!empty($_CONFIG['bootstrap']['enabled'])){
        //    array_ensure($meta, 'viewport', $_CONFIG['bootstrap']['viewport']);
        //}

        /*
         * Add meta tag no-index for non production environments and admin pages
         */
        if(!$_CONFIG['production'] || $_CONFIG['noindex']){
           $meta['robots'] = 'noindex';
        }

        $title = html_title($meta['title']);
        unset($meta['title']);

        $retval = "<!DOCTYPE ".$params['doctype'].">\n".
                  $params['html']."\n".
                  "<head>\n".
                  "<meta http-equiv=\"Content-Type\" content=\"text/html;charset=".$_CONFIG['charset']."\">\n".
                  "<title>".$title."</title>\n";

        unset($meta['title']);

        if(is_string($params['links'])){
            $retval .= $params['links'];

        }else{
// :OBSOLETE: Links specified as an array only adds more complexity, we're going to send it as plain HTML, and be done with the crap. This is still here for backward compatibility
            foreach($params['links'] as $data){
                $sections = array();

                foreach($data as $key => $value){
                    $sections[] = $key.'="'.$value.'"';
                }

                $retval .= '<link '.implode(' ', $sections).">\n";
            }
        }

        foreach($params['prefetch_dns'] as $prefetch){
            $retval .= '<link rel="dns-prefetch" href="//'.$prefetch."\">\n";
        }

        foreach($params['prefetch_files'] as $prefetch){
            $retval .= '<link rel="prefetch" href="'.$prefetch."\">\n";
        }

        unset($prefetch);

        if(!empty($GLOBALS['header'])){
            $retval .= $GLOBALS['header'];
        }

        $retval .= html_generate_css().
                   html_generate_js();

        /*
         * Add required fonts
         */
        if(!empty($_CONFIG['cdn']['fonts'])){
            if($_CONFIG['production'] or (empty($_CONFIG['cdn']['production_fonts']))){
                foreach($_CONFIG['cdn']['fonts'] as $font){
                    $retval .= "<link href=\"".$font."\" rel=\"stylesheet\" type=\"text/css\">\n";
                }
            }
        }

        /*
         * Add all other meta tags
         * Only add keywords with contents, all that have none are considerred
         * as false, and do-not-add
         */
        foreach($meta as $keyword => $content){
            $retval .= "<meta name=\"".$keyword."\" content=\"".$content."\">\n";
        }

        if(!empty($params['properties'])){
            foreach($params['properties'] as $property => $content){
                $retval .= "<meta property=\"".$property."\" content=\"".$content."\">\n";
            }
        }


        $retval .= html_favicon($params['favicon']).$params['extra'];


        return $retval."</head>\n".
                       $params['body']."\n";

    }catch(Exception $e){
        throw new bException('html_header(): Failed', $e);
    }
}



/*
 * Generate and return the HTML footer
 */
function html_footer(){
    global $_CONFIG;

    try{
        if(empty($GLOBALS['footer'])){
            return "</body>\n</html>";
        }

        return $GLOBALS['footer']."</body>\n</html>";

    }catch(Exception $e){
        throw new bException('html_footer(): Failed', $e);
    }
}



/*
 * Generate and return the HTML footer
 */
function html_title($params){
    global $_CONFIG;

    try{
        $title = $_CONFIG['title'];

        /*
         * If no params are specified then just return the given title
         */
        if(empty($params)){
            return $title;
        }

        /*
         * If the given params is a plain string then override the configured title with this
         */
        if(!is_array($params)){
            if(is_string($params)){
                return $params;
            }

            throw new bException('html_title(): Invalid title specified');
        }

        /*
         * Do a search / replace on all specified items to create correct title
         */
        foreach($params as $key => $value){
            $title = str_replace($key, $value, $title);
        }

        return $title;

    }catch(Exception $e){
        throw new bException('html_title(): Failed', $e);
    }
}



/*
 * Show a flash message with the specified message
 */
function html_flash($class = null){
    global $_CONFIG;

    try{
        if(PLATFORM != 'http'){
            throw new bException('html_flash(): This function can only be executed on a webserver!');
        }

        if(!isset($_SESSION['flash'])){
            /*
             * Auto create
             */
            $_SESSION['flash'] = array();
        }

        if(!is_array($_SESSION['flash'])){
            /*
             * $_SESSION['flash'] should always be an array. Don't crash on minor detail, just correct and continue
             */
// :TODO: Should this be an exception?
            log_error(tr('html_flash(): Invalid flash structure in $_SESSION array, it should always be an array but it is a "%type%". Be sure to always use html_flash_set() to add new flash messages', array('%type%' => gettype($_SESSION['flash']))), 'invalid');
            $_SESSION['flash'] = array();
        }

        $retval = '';

        foreach($_SESSION['flash'] as $id => $message){
            //if(is_object($message) and $message instanceof Exception){
            //    $message = array('type'    => 'error',
            //                     'message' => $message->getMessage(),
            //                     'class'   => $class);
            //}

            if(($class != $message['class']) and ($class != 'all')){
                continue;
            }

            /*
             * The message contains what type and basic (usually this comes from $_SESSION[flash]
             */
            $type     = $message['type'];
            $class    = $message['class'];
            $message  = $message['message'];

// :DELETE: Error messages must not always be surpressed!!
            //if(($type == 'error') and $_CONFIG['production']){
            //    $message = tr('Something went wrong, please try again later');
            //}

            switch($type = strtolower($type)){
                case 'info':
                    break;

                case 'information':
                    break;

                case 'success':
                    break;

                case 'error':
                    break;

                case 'danger':
                    break;

                case 'warning':
                    break;

                case 'attention':
                    break;

                default:
                    $type = 'error';
//                    throw new bException(tr('html_flash(): Unknown flash type ":type" specified. Please specify one of "info" or "success" or "attention" or "warning" or "error" or "danger"', array(':type' => $type)), 'flash/unknown');
// :TODO: Add notification
            }

            if(!debug()){
                /*
                 * Don't show "function_name(): " part of message
                 */
                $message = trim(str_from($message, '():'));
            }

            /*
             * Set the indicator that we have added flash messages
             */
            $retval .= tr($_CONFIG['flash']['html'], array(':message' => $message, ':type' => $type, ':hidden' => ''), false);

            $GLOBALS['flash'] = true;
            unset($_SESSION['flash'][$id]);
        }

        /*
         * Add an extra hidden flash message box that can respond for jsFlashMessages
         */
        return $retval.tr($_CONFIG['flash']['html'], array(':message' => '', ':type' => '', ':hidden' => ' hidden'), false);

    }catch(Exception $e){
        throw new bException('html_flash(): Failed', $e);
    }
}



/*
 * Show a flash message with the specified message
 */
function html_flash_set($messages, $type = 'info', $class = null){
    global $_CONFIG;

    try{
        if(!$messages){
            /*
             * Wut? no message?
             */
            return false;
        }

        /*
         * Ensure session flash data consistency
         */
        if(empty($_SESSION['flash'])){
            $_SESSION['flash'] = array();

        }elseif(!is_array($_SESSION['flash'])){
            $_SESSION['flash'] = array($_SESSION['flash']);
        }

        if(!is_array($messages)){
            if(is_object($messages) and $messages instanceof Exception){
                $type     = (($type == 'warning') ? 'warning' : 'error');
                $messages = $messages->getMessage();
                $messages = (strstr($messages, '():') ? trim(str_from($messages, '():')) : $messages);
            }

            if(is_string($messages) and (strpos($messages, "\n") !== false)){
                $messages = explode("\n", $messages);

            }else{
                $messages = array($messages);
            }
        }

        foreach($messages as $message){
            $_SESSION['flash'][] = array('type'    => $type,
                                         'class'   => $class,
                                         'message' => $message);
        }

    }catch(Exception $e){
        throw new bException('html_flash_set(): Failed', $e);
    }
}



/*
 * Returns true if there is an HTML message with the specified class
 */
function html_flash_class($class = null){
    try{
        if(isset($_SESSION['flash'])){
            foreach($_SESSION['flash'] as $message){
                if($message['class'] == $class){
                    return true;
                }
            }
        }

        return false;

    }catch(Exception $e){
        throw new bException('html_flash_class(): Failed', $e);
    }
}



/*
 * Returns HTML for an HTML anchor link <a> that is safe for use with target
 * _blank
 *
 * For vulnerability info:
 * See https://dev.to/ben/the-targetblank-vulnerability-by-example
 * See https://mathiasbynens.github.io/rel-noopener/
 *
 * For when to use _blank anchors:
 * See https://css-tricks.com/use-target_blank/
 */
function html_a($params){
    try{
        array_params ($params, 'href');
        array_default($params, 'name'  , '');
        array_default($params, 'target', '');
        array_default($params, 'rel'   , '');

        switch($params['target']){
            case '_blank':
                $params['rel'] .= ' noreferrer noopener';
                break;
        }

        if(empty($params['href'])){
            throw new bException('html_a(): No href specified', 'not-specified');
        }

        if($params['name']){
            $params['name'] = ' name="'.$params['name'].'"';
        }

        if($params['class']){
            $params['class'] = ' class="'.$params['class'].'"';
        }

        $retval = '<a href="'.$params['href'].'"'.$params['name'].$params['class'].$params['rel'].'">';

        return $retval;

    }catch(Exception $e){
        throw new bException('html_a(): Failed', $e);
    }
}



/*
 * Return HTML for a submit button
 * If the button should not cause validation, then use "no_validation" true
 */
function html_submit($params, $class = ''){
    static $added;

    try{
        array_params ($params, 'value');
        array_default($params, 'name'         , 'dosubmit');
        array_default($params, 'class'        , $class);
        array_default($params, 'no_validation', false);
        array_default($params, 'value'        , 'submit');

        if($params['no_validation']){
            $params['class'] .= ' no_validation';

            if(empty($added)){
                $added  = true;
                $script = html_script('$(".no_validation").click(function(){ $(this).closest("form").find("input,textarea,select").addClass("ignore"); $(this).closest("form").submit(); });');
            }
        }

        if($params['class']){
            $params['class'] = ' class="'.$params['class'].'"';
        }

        if($params['value']){
            $params['value'] = ' value="'.$params['value'].'"';
        }

        $retval = '<input type="submit" id="'.$params['name'].'" name="'.$params['name'].'"'.$params['class'].$params['value'].'>';

        return $retval.isset_get($script);

    }catch(Exception $e){
        throw new bException('html_submit(): Failed', $e);
    }
}



/*
 * Return an HTML <select> list
 */
function html_select_submit($params){
    try{
        array_params ($params);
        array_default($params, 'name'      , 'dosubmit');
        array_default($params, 'autosubmit', true);
        array_default($params, 'buttons'   , array());
        array_default($params, 'none'      , tr('Select action'));

        $params['resource'] = $params['buttons'];

        return html_select($params);

    }catch(Exception $e){
        throw new bException('html_select_submit(): Failed', $e);
    }
}



/*
 * Return an HTML <select> list
 */
function html_select($params){
    static $count = 0;

    try{
        array_params ($params, 'resource');
        array_default($params, 'class'       , '');
        array_default($params, 'option_class', '');
        array_default($params, 'disabled'    , false);
        array_default($params, 'name'        , '');
        array_default($params, 'id'          , $params['name']);
        array_default($params, 'none'        , tr('None selected'));
        array_default($params, 'empty'       , tr('None available'));
        array_default($params, 'option_class', '');
        array_default($params, 'extra'       , '');
        array_default($params, 'selected'    , null);
        array_default($params, 'bodyonly'    , false);
        array_default($params, 'autosubmit'  , false);
        array_default($params, 'onchange'    , '');
        array_default($params, 'hide_empty'  , false);
        array_default($params, 'autofocus'   , false);
        array_default($params, 'multiple'    , false);

        if(!$params['name']){
            throw new bException('html_select(): No name specified');
        }

        if($params['autosubmit']){
            if($params['class']){
                $params['class'] .= ' autosubmit';

            }else{
                $params['class']  = 'autosubmit';
            }
        }

        if(empty($params['resource'])){
            if($params['hide_empty']){
                return '';
            }

            $params['resource'] = array();

// :DELETE: Wut? What exactly was this supposed to do? doesn't make any sense at all..
            //if(is_numeric($params['disabled'])){
            //    $params['disabled'] = true;
            //
            //}else{
            //    if(is_array($params['resource'])){
            //        $params['disabled'] = ((count($params['resource']) + ($params['name'] ? 1 : 0)) <= $params['disabled']);
            //
            //    }elseif(is_object($params['resource'])){
            //        $params['disabled'] = (($params['resource']->rowCount() + ($params['name'] ? 1 : 0)) <= $params['disabled']);
            //
            //    }elseif($params['resource'] === null){
            //        $params['disabled'] = true;
            //
            //    }else{
            //        throw new bException(tr('html_select(): Invalid resource of type "%type%" specified, should be either null, an array, or a PDOStatement object', array('%type%' => gettype($params['resource']))), 'invalid');
            //    }
            //}
        }

        if($params['bodyonly']){
            return html_select_body($params);
        }

        /*
         * <select> class should not be applied to <option>
         */
        $class = $params['class'];
        $params['class'] = $params['option_class'];

        $body = html_select_body($params);

        if(substr($params['id'], -2, 2) == '[]'){
            $params['id'] = substr($params['id'], 0, -2).$count++;
        }

        if($params['multiple']){
            $params['multiple'] = ' multiple="multiple"';

        }else{
            $params['multiple'] = '';
        }

        if($params['disabled']){
            /*
             * Add a hidden element with the name to ensure that multiple selects with [] will not show holes
             */
            return '<select'.$params['multiple'].($params['id'] ? ' id="'.$params['id'].'_disabled"' : '').' name="'.$params['name'].'" '.($class ? ' class="'.$class.'"' : '').($params['extra'] ? ' '.$params['extra'] : '').' readonly disabled>'.
                    $body.'</select><input type="hidden" name="'.$params['name'].'" >';
        }else{
            $retval = '<select'.$params['multiple'].($params['id'] ? ' id="'.$params['id'].'"' : '').' name="'.$params['name'].'" '.($class ? ' class="'.$class.'"' : '').($params['disabled'] ? ' disabled' : '').($params['autofocus'] ? ' autofocus' : '').($params['extra'] ? ' '.$params['extra'] : '').'>'.
                      $body.'</select>';
        }

        if($params['onchange']){
            /*
             * Execute the JS code for an onchange
             */
            $retval .= html_script('$("#'.$params['id'].'").change(function(){ '.$params['onchange'].' });');

        }

        if(!$params['autosubmit']){
            /*
             * There is no onchange and no autosubmit
             */
            return $retval;

        }elseif($params['autosubmit'] === true){
            /*
             * By default autosubmit on the id
             */
            $params['autosubmit'] = '#'.$params['id'];
        }

        /*
         * Autosubmit on the specified selector
         */
        return $retval.html_script('$("'.$params['autosubmit'].'").change(function(){ $(this).closest("form").find("input,textarea,select").addClass("ignore"); $(this).closest("form").submit(); });');

    }catch(Exception $e){
        throw new bException('html_select(): Failed', $e);
    }
}



/*
 * Return the body of an HTML <select> list
 */
function html_select_body($params) {
    global $_CONFIG;

    try{
        array_params ($params, 'resource');
        array_default($params, 'class'         , '');
        array_default($params, 'none'          , tr('None selected'));
        array_default($params, 'empty'         , tr('None available'));
        array_default($params, 'selected'      , null);
        array_default($params, 'auto_select'   , true);
        array_default($params, 'data_resources', null);

        if(!$_CONFIG['production'] and (!empty($params['data_key']) or !empty($params['data_resource']))){
            if(!empty($params['data_key'])){
                throw new bException(tr('html_select_body(: data_key was specified, which is obsolete. Specify the data_key in data_resources[data_key => array(), data_key => array()]'), 'obsolete');
            }

            if(!empty($params['data_resource'])){
                throw new bException(tr('html_select_body(: data_resource was specified, which is obsolete. Use data_resources instead'), 'obsolete');
            }
        }

        if($params['data_resources'] and !is_array($params['data_resources'])){
            throw new bException(tr('html_select_body(): Invalid data_resource specified, should be an array, but received a ":gettype"', array(':gettype' => gettype($params['data_resources']))), 'invalid');
        }

        if($params['none']){
            $retval = '<option'.($params['class'] ? ' class="'.$params['class'].'"' : '').''.(($params['selected'] === null) ? ' selected' : '').' value="">'.$params['none'].'</option>';

        }else{
            $retval = '';
        }

        if($params['resource']){
            if(is_array($params['resource'])){
                if($params['auto_select'] and ((count($params['resource']) == 1) and !$params['none'])){
                    /*
                     * Auto select the only available element
                     */
                    $params['selected'] = array_keys($params['resource']);
                    $params['selected'] = array_shift($params['selected']);
                }

                /*
                 * Process array resource
                 */
                foreach($params['resource'] as $key => $value){
                    $notempty    = true;
                    $option_data = '';

                    if($params['data_resources']){
                        foreach($params['data_resources'] as $data_key => $resource){
                            if(!empty($resource[$key])){
                                $option_data .= ' data-'.$data_key.'="'.$resource[$key].'"';
                            }
                        }
                    }

                    $retval  .= '<option'.($params['class'] ? ' class="'.$params['class'].'"' : '').''.((($params['selected'] !== null) and ($key === $params['selected'])) ? ' selected' : '').' value="'.$key.'"'.$option_data.'>'.$value.'</option>';
                }

            }elseif(is_object($params['resource'])){
                if(!($params['resource'] instanceof PDOStatement)){
                    throw new bException(tr('html_select_body(): Specified resource object is not an instance of PDOStatement'), 'invalidresource');
                }

                if($params['auto_select'] and ($params['resource']->rowCount() == 1)){
                    /*
                     * Auto select the only available element
                     */
// :TODO: Implement
                }

                /*
                 * Process SQL resource
                 */
                try{
                    while($row = sql_fetch($params['resource'], false, PDO::FETCH_NUM)){
                        $notempty    = true;
                        $option_data = '';

                        /*
                         * To avoid select problems with "none" entries, empty id column values are not allowed
                         */
                        if(!$row[0]){
                            $row[0] = str_random(8);
                        }

                        /*
                         * Add data- in this option?
                         */
                        if($params['data_resources']){
                            foreach($params['data_resources'] as $data_key => $resource){
                                if(!empty($resource[$key])){
                                    $option_data = ' data-'.$data_key.'="'.$resource[$key].'"';
                                }
                            }
                        }

                        $retval  .= '<option'.($params['class'] ? ' class="'.$params['class'].'"' : '').''.(($row[0] === $params['selected']) ? ' selected' : '').' value="'.$row[0].'"'.$option_data.'>'.$row[1].'</option>';
                    }

                }catch(Exception $e){

                    throw $e;
                }

            }else{
                throw new bException(tr('html_select_body(): Specified resource "'.str_log($params['resource']).'" is neither an array or resource'), 'invalidresource');
            }
        }


        if(empty($notempty)){
            /*
             * No conent (other than maybe the "none available" entry) was added
             */
            if($params['empty']){
                $retval = '<option'.($params['class'] ? ' class="'.$params['class'].'"' : '').' selected value="">'.$params['empty'].'</option>';
            }

            /*
             * Return empty body (though possibly with "none" element) so that the html_select() function can ensure the select box will be disabled
             */
            return $retval;
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('html_select_body(): Failed', $e);
    }
}



/*
 * Generate and return the HTML footer
 *
 * $option maybe either "async" or "defer", see http://www.w3schools.com/tags/att_script_async.asp
 */
function html_script($script, $jquery_ready = true, $option = null, $type = null, $ie = false){
    global $_CONFIG;

    try{
        if(is_bool($type)){
            $jquery_ready = $type;
            $type         = null;
        }

        if(is_null($type)){
            $type = 'text/javascript';
        }

        /*
         * Event wrapper
         *
         * On what event should this script be executed? Eithere boolean true for standard "document ready" or your own jQuery
         *
         * If false, no event wrapper will be added
         */
        if($jquery_ready){
            if($jquery_ready === true){
                $jquery_ready = '$(document).ready(function(e){ %script% });';
            }

            $script = str_replace('%script%', $script, $jquery_ready);
        }

        if(substr($script, 0, 1) == '>'){
            $retval = '<script type="'.$type.'" src="'.cdn_prefix().'js/'.substr($script, 1).'"'.($option ? ' '.$option : '').'></script>';

        }else{
            $retval = '<script type="'.$type.'"'.($option ? ' '.$option : '').">\n".
                            $script.
                      '</script>';
        }

        if($ie){
            $retval = html_iefilter($retval, $ie);
        }

        if(empty($_CONFIG['cdn']['js']['load_delayed'])){
            return $retval;
        }

        /*
         * SCRIPT tags are added all at the end of the page for faster loading
         * (and to avoid problems with jQuery not yet being available)
         */
        if(empty($GLOBALS['script_delayed'])){
            $GLOBALS['script_delayed']  = $retval;

        }else{
            $GLOBALS['script_delayed'] .= $retval;
        }

        return '';

    }catch(Exception $e){
        throw new bException('html_script(): Failed', $e);
    }
}



/*
 * Return favicon HTML
 */
function html_favicon($icon = null, $mobile_icon = null, $sizes = null, $precomposed = false){
    global $_CONFIG;

    try{
        array_params($params, 'icon');
        array_default($params, 'mobile_icon', $mobile_icon);
        array_default($params, 'sizes'      , $sizes);
        array_default($params, 'precomposed', $precomposed);

        if(!$params['sizes']){
            $params['sizes'] = array('');

        }else{
            $params['sizes'] = array_force($params['sizes']);
        }

        foreach($params['sizes'] as $sizes){
            if($GLOBALS['page_is_mobile']){
                if(!$params['mobile_icon']){
                    $params['mobile_icon'] = cdn_prefix('img/mobile/favicon.png');
                }

                return '<link rel="apple-touch-icon'.($params['precomposed'] ? '-precompsed' : '').'"'.($sizes ? ' sizes="'.$sizes.'"' : '').' href="'.$params['mobile_icon'].'" />';

            }else{
                if(empty($params['icon'])){
                    $params['icon'] = cdn_prefix('img/favicon.png');
                }

                return '<link rel="icon" type="image/x-icon"'.($sizes ? ' sizes="'.$sizes.'"' : '').'  href="'.$params['icon'].'" />';
            }
        }

    }catch(Exception $e){
        throw new bException('html_favicon(): Failed', $e);
    }
}



/*
 * Create HTML for an HTML step process bar
 */
function html_list($params, $selected = ''){
    try{
        if(!is_array($params)){
            throw new bException('html_list(): Specified params is not an array', 'invalid');
        }

        if(empty($params['steps']) or !is_array($params['steps'])){
            throw new bException('html_list(): params[steps] is not specified or not an array', 'invalid');
        }

        array_default($params, 'selected'    , $selected);
        array_default($params, 'class'       , '');
        array_default($params, 'disabled'    , false);
        array_default($params, 'show_counter', false);
        array_default($params, 'use_list'    , true);

        if(!$params['disabled']){
            if($params['class']){
                $params['class'] = str_ends($params['class'], ' ');
            }

            $params['class'].'hover';
        }

        if($params['use_list']){
            $retval = '<ul'.($params['class'] ? ' class="'.$params['class'].'"' : '').'>';

        }else{
            $retval = '<div'.($params['class'] ? ' class="'.$params['class'].'"' : '').'>';
        }

        /*
         * Get first and last keys.
         */
        end($params['steps']);
        $last  = key($params['steps']);

        reset($params['steps']);
        $first = key($params['steps']);

        $count = 0;

        foreach($params['steps'] as $name => $data){
            $count++;

            $class = $params['class'].(($params['selected'] == $name) ? ' selected active' : '');

            if($name == $first){
                $class .= ' first';

            }elseif($name == $last){
                $class .= ' last';

            }else{
                $class .= ' middle';
            }

            if($params['show_counter']){
                $counter = '<strong>'.$count.'.</strong> ';

            }else{
                $counter = '';
            }

            if($params['use_list']){
                if($params['disabled']){
                    $retval .= '<li'.($class ? ' class="'.$class.'"' : '').'><a href="" class="nolink">'.$counter.$data['name'].'</a></li>';

                }else{
                    $retval .= '<li'.($class ? ' class="'.$class.'"' : '').'><a href="'.$data['url'].'">'.$counter.$data['name'].'</a></li>';
                }

            }else{
                if($params['disabled']){
                    $retval .= '<a'.($class ? ' class="nolink'.($class ? ' '.$class : '').'"' : '').'>'.$counter.$data['name'].'</a>';

                }else{
                    $retval .= '<a'.($class ? ' class="'.$class.'"' : '').' href="'.$data['url'].'">'.$counter.$data['name'].'</a>';
                }

            }
        }

        if($params['use_list']){
            return $retval.'</ul>';
        }

        return $retval.'</div>';

    }catch(Exception $e){
        throw new bException('html_list(): Failed', $e);
    }
}



/*
 *
 */
function html_status_select($params){
    try{
        array_params ($params, 'name');
        array_default($params, 'name'    , 'status');
        array_default($params, 'none'    , '');
        array_default($params, 'resource', false);
        array_default($params, 'selected', '');

        return html_select($params);

    }catch(Exception $e){
        throw new bException('html_status_select(): Failed', $e);
    }
}



/*
 *
 */
function html_form(){
    return '<input type="hidden" name="dosubmit" value="1">';
}



/*
 *
 */
function html_hidden($source, $key = 'id'){
    try{
        return '<input type="hidden" name="'.$key.'" value="'.isset_get($source[$key]).'">';

    }catch(Exception $e){
        throw new bException('html_hidden(): Failed', $e);
    }
}



// :OBSOLETE: This is now done in http_headers
///*
// * Create the page using the custom library c_page function and add content-length header and send HTML to client
// */
//function html_send($params, $meta, $html){
//    $html = c_page($params, $meta, $html);
//
//    header('Content-Length: '.mb_strlen($html));
//    echo $html;
//    die();
//}



/*
 * Create and return an img tag that contains at the least src, alt, height and width
 * If height / width are not specified, then html_img() will try to get the height / width
 * data itself, and store that data in database for future reference
 */
function html_img($src, $alt, $width = null, $height = null, $more = ''){
    global $_CONFIG;
    static $images;

    try{
        if(!$src){
            /*
             * No image at all?
             */
            if($_CONFIG['production']){
                /*
                 * On production, just notify and ignore
                 */
                notify('no-image', tr('No src for image with alt text ":alt"', array(':alt' => $alt)), 'development');
                return '';
            }

            throw new bException(tr('html_img(): No src for image with alt text ":alt"', array(':alt' => $alt)), 'no-image');
        }

// :DELETE: All projects should be updated to conform with the new html_img() function and then this check should be dumped with the garbage
        if(!$width and $height and !is_numeric($height)){
            if(!$_CONFIG['production'] and $_CONFIG['system']['obsolete_exception']){
                throw new bException(tr('html_img(): Update html_img() argument order'), 'obsolete');
            }

            $more   = $height;
            $height = 0;
        }

        if(!$_CONFIG['production']){
            if(!$src){
                throw new bException(tr('html_img(): No image src specified'), 'not-specified');
            }

            if(!$alt){
                throw new bException(tr('html_img(): No image alt text specified for src ":src"', array(':src' => $src)), 'not-specified');
            }

        }else{
            if(!$src){
                notify('no_img_source', tr('html_img(): No image src specified'), 'developers');
            }

            if(!$alt){
                notify('no_img_alt', tr('html_img(): No image alt text specified for src ":src"', array(':src' => $src)), 'developers');
            }
        }

        if(($width === null) or ($height === null)){
            /*
             * Try to get width / height from image.
             */
            if($image = sql_get('SELECT `width`, `height` FROM `html_img` WHERE `url` = :url AND `createdon` > NOW() - INTERVAL 1 DAY AND `status` IS NULL', array(':url' => $src))){
                /*
                 * We have that information cached, yay!
                 */
                $width  = $image['width'];
                $height = $image['height'];

            }else{
                try{
                    if(preg_match('/^ftp|https?/i', $src)){

                        /*
                         * Image comes from a domain, fetch to temp directory to analize
                         */
                        load_libs('file');

                        try{
                            $file  = file_move_to_target($src, TMP, false, true);
                            $image = getimagesize(TMP.$file);

                        }catch(Exception $e){
                            switch($e->getCode()){
                                case 404:
                                    // FALLTHROUGH
                                case 403:
                                    break;

                                default:
                                    throw $e;
                            }

                            /*
                             * Image doesnt exist
                             */
                            log_error(tr('html_img(): image ":src" does not exist', array(':src' => $src)), 'not-exist');
                            $image[0] = -1;
                            $image[1] = -1;
                        }

                        if(!empty($file)){
                            file_delete(TMP.$file);
                            file_delete(dirname(TMP.$file));
                        }

                    }else{
                        /*
                         * Local image. Analize directly
                         */
                        if(file_exists(ROOT.'www/en/'.$src)){
                            $image = getimagesize(ROOT.'www/en/'.$src);

                        }else{
                            /*
                             * Image doesn't exist.
                             */
                            log_error(tr('html_img(): image ":src" does not exist', array(':src' => $src, ':width' => $width, ':height' => $height)), 'invalid');
                            $image[0] = -1;
                            $image[1] = -1;
                        }
                    }

                    $width  = $image[0];
                    $height = $image[1];
                    $status = null;

                }catch(Exception $e){
                    notify('imgnotexist', tr('html_img(): The image with src ":src" does not exist or is not an image', array(':src' => $src)), 'developers');

                    $width  = 0;
                    $height = 0;
                    $status = $e->getCode();
                }

                if(!$height or !$width){
                    log_error(tr('html_img(): image ":src" has invalid dimensions with width ":width" and height ":height"', array(':src' => $src, ':width' => $width, ':height' => $height)), 'invalid');

                }else{
                    sql_query('INSERT INTO `html_img` (`status`, `url`, `width`, `height`)
                               VALUES                 (:status , :url , :width , :height )

                               ON DUPLICATE KEY UPDATE `status`    = NULL,
                                                       `createdon` = NOW()',

                               array(':url'    => $src,
                                     ':width'  => $width,
                                     ':height' => $height,
                                     ':status' => $status));
                }
            }
        }

        if($height){
            $height = ' height="'.$height.'"';

        }else{
            $height = '';
        }

        if($width){
            $width = ' width="'.$width.'"';

        }else{
            $width = '';
        }

        return '<img src="'.$src.'" alt="'.$alt.'"'.$width.$height.($more ? ' '.$more : '').'>';

    }catch(Exception $e){
        throw new bException('html_img(): Failed', $e);
    }
}



/*
 * Create and return a video container that has at the least src, alt, height and width
 */
function html_video($src, $type = null, $height = 0, $width = 0, $more = ''){
    global $_CONFIG;

    try{
        if(!$_CONFIG['production']){
            if(!$src){
                throw new bException(tr('html_video(): No video src specified'), 'not-specified');
            }
        }

        /*
         * Videos can be either local or remote
         * Local videos either have http://thisdomain.com/video, https://thisdomain.com/video, or /video
         * Remote videos must have width and height specified
         */
        if(substr($src, 0, 7) == 'http://'){
            $protocol = 'http';

        }elseif($protocol = substr($src, 0, 8) == 'https://'){
            $protocol = 'https';

        }else{
            $protocol = '';
        }

        if(!$protocol){
            /*
             * This is a local video
             */
            $file  = ROOT.'www/en'.str_starts($src, '/');

        }else{
            if(preg_match('/^'.$protocol.':\/\/(?:www\.)?'.str_replace('.', '\.', domain()).'\/.+$/ius', $src)){
                /*
                 * This is a local video with domain specification
                 */
                $file  = ROOT.'www/en'.str_starts(str_from($src, domain()), '/');

            }elseif(!$_CONFIG['production']){
                /*
                 * This is a remote video
                 * Remote videos MUST have height and width specified!
                 */
                if(!$height){
                    throw new bException(tr('html_video(): No height specified for remote video'), 'not-specified');
                }

                if(!$width){
                    throw new bException(tr('html_video(): No width specified for remote video'), 'not-specified');
                }

                if(!$type){
                    throw new bException(tr('html_video(): No type specified for remote video'), 'not-specified');
                }
            }
        }

        if(!$height or !$width){
// :INVESTIGATE: Is better getting default width and height dimensions like in html_img()
// But in this case, we have to use a external "library" to get this done
// Investigate the best option for this!
        }

// This is the temporarily solution for dimensions.
        /*
         * If no height was given, let the browser display
         * with the original height of the video
         */
        if(!$height){
            $height = '';

        }elseif(is_numeric($height)){
            $height = 'height="'.$height.'"';

        }else{
            log_error(tr('html_video(): Specified height "%height%" is not numeric', array('%height%' => $height)), 'not_numeric');
        }

        /*
         * If no width was given, let the browser display
         * with the original width of the video
         */
        if(!$width){
            $width = '';

        }elseif(is_numeric($width)){
            $width = 'width="'.$width.'"';

        }else{
            log_error(tr('html_video(): Specified width "%width%" is not numeric', array('%width%' => $width)), 'not_numeric');
        }

        /*
         * Get the file type
         */
        if(!$type){
            $type = mime_content_type($file);
        }

        return '<video '.$width.' '.$height.' controls '.($more ? ' '.$more : '').'>
                    <source src="'.$src.'" type="'.$type.'">
                </video>';


    }catch(Exception $e){
        throw new bException('html_video(): Failed', $e);
    }
}



/*
 * Show the specified page
 */
function page_show($pagename, $die = true, $force = false, $data = null) {
    global $_CONFIG;

    try{
        if(!empty($GLOBALS['page_is_ajax'])){
            // Execute ajax page
            return include(ROOT.'www/'.LANGUAGE.'/ajax/'.$pagename.'.php');

        }elseif(!empty($GLOBALS['page_is_ajax'])){
                $prefix = 'ajax/';

        }else{
            $prefix = '';
        }

        include(ROOT.'www/'.LANGUAGE.'/'.$prefix.$pagename.'.php');

        if($die){
            die();
        }

    }catch(Exception $e){
        throw new bException(tr('page_show(): Failed to show page "%page%"', array('%page%' => str_log($pagename))), $e);
    }
}



/*
 *
 */
function html_autosuggest($params){
    static $sent = array();

    try{
        array_params($params);
        array_default($params, 'class'            , '');
        array_default($params, 'input_class'      , '');
        array_default($params, 'name'             , '');
        array_default($params, 'id'               , $params['name']);
        array_default($params, 'placeholder'      , '');
        array_default($params, 'required'         , false);
        array_default($params, 'value'            , '');
        array_default($params, 'source'           , '');
        array_default($params, 'maxlength'        , '');
        array_default($params, 'filter_selector'  , '');
        array_default($params, 'selector'         , 'form.autosuggest');

        $retval = ' <div class="autosuggest'.($params['class'] ? ' '.$params['class'] : '').'">
                        <input autocomplete="off" spellcheck="false" role="combobox" dir="ltr" '.($params['input_class'] ? 'class="'.$params['input_class'].'" ' : '').'type="text" name="'.$params['name'].'" id="'.$params['id'].'" placeholder="'.$params['placeholder'].'" data-source="'.$params['source'].'" value="'.$params['value'].'"'.($params['filter_selector'] ? ' data-filter-selector="'.$params['filter_selector'].'"' : '').($params['maxlength'] ? ' maxlength="'.$params['maxlength'].'"' : '').($params['required'] ? ' required' : '').'>
                        <ul>
                        </ul>
                    </div>';

        if(empty($sent[$params['selector']])){
            /*
             * Add only one autosuggest start per selector
             */
            $sent[$params['selector']] = true;
            $retval                   .= html_script('$("'.$params['selector'].'").autosuggest();');
        }

        html_load_js('base/autosuggest');

        return $retval;

    }catch(Exception $e){
        throw new bException(tr('html_autosuggest(): Failed'), $e);
    }
}



/*
 * This function will minify the given HTML by removing double spaces, and strip white spaces before and after tags (except space)
 * Found on http://stackoverflow.com/questions/6225351/how-to-minify-php-page-html-output, rewritten for use in base project
 */
function html_minify($html, $full = false){
    try{
        if(debug()){
            /*
             * Don't do anything. This way, on non debug systems, where this is
             * used to minify HTML output, we can still see normal HTML that is
             * a bit more readable.
             */
            return $html;
        }

        /*
         * Shorten multiple whitespace sequences; keep new-line
         * characters because they matter in JS!!!
         */
        $search [] = '/([\t ])+/s';

        /*
         * Remove leading and trailing spaces
         */
        $search [] = '/^([\t ])+/m';
        $search [] = '/([\t ])+$/m';

        /*
         * Remove JS line comments (simple only);
         * do NOT remove lines containing URL (e.g. 'src="http://server.com/"')!!!
         */
        $search [] = '~//[a-zA-Z0-9 ]+$~m';

        /*
         * Remove quotes from HTML attributes that does not contain spaces;
         * keep quotes around URLs!
         * $1 and $4 insert first white-space character found before/after attribute
         */
        $search [] = '~([\r\n\t ])?([a-zA-Z0-9]+)="([a-zA-Z0-9_/\\-]+)"([\r\n\t ])?~s';

        /*
         * Replace array for the above searches
         */
        $replace[] = ' ';
        $replace[] = '';
        $replace[] = '';
        $replace[] = '';
        $replace[] = '$1$2=$3$4';

        if($full){
            /*
             * Remove empty lines (sequence of line-end and white-space characters)
             */
            $search [] = '/[\r\n]+([\t ]?[\r\n]+)+/s';
            $replace[] = '\n';

            /*
             * Remove empty lines (between HTML tags);
             * cannot remove just any line-end characters
             * because in inline JS they can matter!
             */
            $search [] = '/\>[\r\n\t ]+\</s';
            $replace[] = '><';

            /*
             * Remove tabs before and after HTML tags
             */
            $search[]  = '/\>[^\S ]+/s';
            $search[]  = '/[^\S ]+\</s';

            /*
             * Replace array for the above searches
             */
            $replace[] = '>';
            $replace[] = '<';

            /*
             * Remove new-line after JS's line end (only most obvious and safe cases)
             */
            $search [] = '/\),[\r\n\t ]+/s';
            $replace[] = '),';

            /*
             * Remove "empty" lines containing only JS's
             * block end character;
             * join with next line (e.g. "}\n}\n</script>" --> "}}</script>"
             */
            $search [] = '/}[\r\n\t ]+/s';
            $search [] = '/}[\r\n\t ]+,[\r\n\t ]+/s';

            /*
             * Replace array for the above searches
             */
            $replace[] = '}';
            $replace[] = '}';

            /*
             * Remove new-line after JS's function or condition start;
             * join with next line
             */
            $search [] = '/\)[\r\n\t ]?{[\r\n\t ]+/s';
            $search [] = '/,[\r\n\t ]?{[\r\n\t ]+/s';

            /*
             * Replace array for the above searches
             */
            $replace[] = '){';
            $replace[] = ',{';
        }

// :DELETE: This search replace won't work for <script> tags or inline commentaries //
        //$search = array(
        //    '/\>[^\S ]+/s',  // strip whitespaces after tags, except space
        //    '/[^\S ]+\</s',  // strip whitespaces before tags, except space
        //    '/(\s)+/s'       // shorten multiple whitespace sequences
        //);
        //
        //$replace = array(
        //    '>',
        //    '<',
        //    '\\1'
        //);

        return preg_replace($search, $replace, $html);

    }catch(Exception $e){
        throw new bException(tr('html_minify(): Failed'), $e);
    }
}
?>
