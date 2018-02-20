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
    if(!PLATFORM_HTTP){
        throw new bException('html_only(): This can only be done over HTML', 'htmlonly');
    }
}



/*
 *
 */
function html_echo($html){
    global $_CONFIG;

    try{
        if(ob_get_contents()){
            if($_CONFIG['production']){
                throw new bException(tr('html_echo(): Output buffer is not empty'), 'not-empty');
            }

            log_console(tr('html_echo(): Output buffer is not empty'), 'yellow');
        }

        echo $html;
        die();

    }catch(Exception $e){
        throw new bException('html_echo(): Failed', $e);
    }
}



/*
 *
 */
function html_safe($html){
    try{
        return htmlentities($html);

    }catch(Exception $e){
        throw new bException('html_safe(): Failed', $e);
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
 * Bundles CSS or JS files togueter, gives them a md5 substr to 16 chacarter name
 */
function html_bundler($type){
    global $_CONFIG, $core;

    if(!$_CONFIG['cdn']['bundler']['enabled']) return false;

    try{
        $realtype                  = $type;
        $core->register[$realtype] = array_keys($core->register[$realtype]);

        switch($type){
            case 'css':
                $prefix = '';
                break;

            case 'js_header':
                $prefix = '<';
                $type   = 'js';
                break;

            case 'js_footer':
                $prefix = '>';
                $type   = 'js';
                break;

            case 'js':
                $default = ($_CONFIG['cdn']['js']['load_delayed'] ? 'js_footer' : 'js_header');

                foreach($core->register[$type] as &$file){
                    switch($file[0]){
                        case '<':
                            /*
                             * Header
                             */
                            $core->register['js_header'][substr($file, 1)] = substr($file, 1);
                            break;

                        case '>':
                            /*
                             * Footer
                             */
                            $core->register['js_footer'][substr($file, 1)] = substr($file, 1);
                            break;

                        default:
                            /*
                             * Default
                             */
                            $core->register[$default][$file] = $file;
                    }
                }

                $core->register['js'] = array();

                /*
                 * Bundle header and footer javascript files separately
                 */
                if(!empty($core->register['js_header'])) html_bundler('js_header');
                if(!empty($core->register['js_footer'])) html_bundler('js_footer');
                return null;

            default:
                throw new bException(tr('html_bundler(): Unknown type ":type" specified', array(':type' => $type)), 'unknown');
        }

        /*
         * Prepare bundle information
         */
        $ext         = ($_CONFIG['cdn']['min'] ? '.min.'.$type : '.'.$type);
        $bundle      =  substr(md5(str_force($core->register[$realtype])), 1, 16);
        $admin_path  = ($core->callType('admin') ? 'admin/' : '');
        $path        =  ROOT.'www/en/'.$admin_path.'pub/'.$type.'/';
        $bundle_file =  $path.'bundle-'.$bundle.$ext;

        /*
         * If we don't find an existing bundle file, then procced with the concatination process
         */
        if($_CONFIG['cache']['method'] and file_exists($bundle_file)){
            if((filemtime($bundle_file) + $_CONFIG['cdn']['bundler']['max_age']) < time()){
                /*
                 * This file is too old, dump and retry
                 */
                file_delete($bundle_file);
                return html_bundler($type);
            }

            $core->register[$type] = array();

        }else{
            /*
             * Generate new bundle
             */
            load_libs('file');
            file_ensure_path($path.'bundle-');

            foreach($core->register[$realtype] as $key => &$file){
                /*
                 * Check for @imports
                 */
                $orgfile = $file;
                $file    = $path.$file.$ext;

                if(!file_exists($file)){
                    notify('bundler-file/not-exist', tr('The bundler ":type" file ":file" does not exist', array(':type' => $type, ':file' => $file)), 'developers');
                    continue;
                }

                $data = file_get_contents($file);
                unset($core->register[$realtype][$key]);

                switch($type){
                    case 'js':
                        /*
                         * Prevent issues with JS files that do not end in ;
                         */
                        $data .= ';';
                        break;

                    case 'css':
// :TODO: ADD SUPPORT FOR RECURSIVE @IMPORT STATEMENTS!! What if the files that are imported with @import contain @import statements themselves!?!?!?
                        if(preg_match_all('/@import.+?;/', $data, $matches)){
                            foreach($matches[0] as $match){
                                /*
                                 * Inline replace each @import with the file
                                 * contents
                                 */
//                                if(preg_match('/@import\s?(?:url\()?((?:"?.+?"?)|(?:\'.+?\'))\)?/', $match)){
                                if(preg_match('/@import\s"|\'.+?"|\'/', $match)){
// :TODO: What if specified URLs are absolute? WHat if start with either / or http(s):// ????
                                    $import = str_cut($match, '"', '"');

                                    if(!file_exists($path.$import)){
                                        notify('bundler-file/not-exist', tr('The bundler ":type" file ":import" @imported by file ":file" does not exist', array(':type' => $type, ':import' => $import, ':file' => $file)), 'developers');
                                        $import = '';

                                    }else{
                                        $import = file_get_contents($path.$import);
                                    }

                                }elseif(preg_match('/@import\surl\(.+?\)/', $match)){
// :TODO: What if specified URLs are absolute? WHat if start with either / or http(s):// ????
                                    /*
                                     * This is an external URL. Get it locally
                                     * as a temp file, then include
                                     */
                                    $import = str_cut($match, '(', ')');
                                    $import = slash(dirname($file)).unslash($import);

                                    if(!file_exists($import)){
                                        notify('bundler-file/not-exist', tr('The bundler ":type" file ":import" @imported by file ":file" does not exist', array(':type' => $type, ':import' => $import, ':file' => $file)), 'developers');
                                        $import = '';

                                    }else{
                                        $import = file_get_contents($import);
                                    }
                                }

                                $data = str_replace($match, $import, $data);
                            }
                        }

                        $count = substr_count($orgfile, '/');

                        if(!$count){
                            /*
                             * No URL rewriting required, this file is directly
                             * in /css or /js, and not in a sub dir
                             */
                            continue;
                        }

                        if(preg_match_all('/url\((.+?)\)/', $data, $matches)){
                            /*
                             * Rewrite all URL's to avoid relative URL's failing
                             * for files in sub directories
                             *
                             * e.g.:
                             *
                             * The bundle file is /pub/css/bundle-1.css,
                             * includes a css file /pub/css/foo/bar.css,
                             * bar.css includes an image 1.jpg that is in the
                             * same directory as bar.css with url("1.jpg")
                             *
                             * In the bundled file, this should become
                             * url("foo/1.jpg")
                             */
                            foreach($matches[1] as $url){
                                if(strtolower(substr($url, 0, 5)) == 'data:'){
                                    /*
                                     * This is inline data, nothing we can do so
                                     * ignore
                                     */
                                    continue;
                                }

                                if(substr($url, 0, 1) == '/'){
                                    /*
                                     * Absolute URL, we can ignore these since
                                     * they already point towards the correct
                                     * path
                                     */
                                }

                                if(preg_match('/https?:\/\//', $url)){
                                    /*
                                     * Absolute domain, ignore because we cannot
                                     * fix anything here
                                     */
                                    continue;
                                }

                                $data = str_replace($url, '"'.str_repeat('../', $count).$url.'"', $data);
                            }
                        }
                }

                file_append($bundle_file, $data);
            }

            unset($file);
            $core->register[$realtype] = array();

            if($_CONFIG['cdn']['network']['enabled']){
                load_libs('cdn');
                cdn_add_object($bundle_file);
            }
        }

        $core->register[$type][$prefix.'bundle-'.$bundle] = true;

    }catch(Exception $e){
        throw new bException('html_bundler(): Failed', $e);
    }
}



/*
 * Store libs for later loading
 */
function html_load_css($files = '', $media = null){
    global $_CONFIG, $core;

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

        foreach($files as $file){
            $core->register['css'][$file] = array('min'   => $min,
                                                  'media' => $media);
        }

}catch(Exception $e){
        throw new bException('html_load_css(): Failed', $e);
    }
}



/*
 * Display libs in header
 *
 * Allows force loading of .min files (ie style.min instead of style), when 'min' is configured on, it won't duplicate the .min
 */
function html_generate_css(){
    global $_CONFIG, $core;

    try{
        if(!empty($_CONFIG['cdn']['css']['post'])){
            $core->register['css']['post'] = array('min'   => $_CONFIG['cdn']['min'],
                                                   'media' => (is_string($_CONFIG['cdn']['css']['post']) ? $_CONFIG['cdn']['css']['post'] : ''));
        }

        $retval = '';
        $min    = $_CONFIG['cdn']['min'];

        html_bundler('css');

        foreach($core->register['css'] as $file => $meta){
            if(!$file) continue;

            $html = '<link rel="stylesheet" type="text/css" href="'.cdn_domain((($_CONFIG['whitelabels']['enabled'] === true) ? $_SESSION['domain'].'/' : '').'css/'.($min ? str_until($file, '.min').'.min.css' : $file.'.css')).'">';

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
    global $_CONFIG, $core;

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

            $core->register['js'][$file] = $data;
        }

    }catch(Exception $e){
        throw new bException('html_load_js(): Failed', $e);
    }
}



/*
 * Display libs in header and or footer
 *
 * Allows force loading of .min files (ie script.min instead of script), when 'min' is configured on, it won't duplicate the .min
 */
function html_generate_js(){
    global $_CONFIG, $core;

    try{
        /*
         * Shortcut to JS configuration
         */
        $js     =  $_CONFIG['cdn']['js'];
        $min    = ($_CONFIG['cdn']['min'] ? '.min' : '');
        $retval = '';
        $footer = '';

        html_bundler('js');

        /*
         * Set load_delayed javascript from this point on to add directly to the $core->register[footer]
         */
        $_CONFIG['cdn']['js']['load_delayed'] = false;

        /*
         * Set to load default JS libraries
         */
if(isset($js['default_libs']) and empty($_CONFIG['production'])){
throw new bException('WARNING: $_CONFIG[js][default_libs] CONFIGURATION FOUND! THIS IS NO LONGER SUPPORTED! JS LIBRARIES SHOULD ALWAYS BE LOADED USING html_load_js() AND JS SCRIPT ADDED THROUGH html_script()', 'obsolete');
}

        if(empty($core->register['js'])){
            /*
             * There are no libraries to load
             */
            return '';
        }

        /*
         * Load JS libraries
         */
        foreach($core->register['js'] as $file => $data){
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

                $html = '<script'.(!empty($data['option']) ? ' '.$data['option'] : '').' type="text/javascript" src="'.cdn_domain((($_CONFIG['whitelabels']['enabled'] === true) ? $_SESSION['domain'].'/' : '').'js/'.($min ? $file.$min : str_until($file, '.min').$min).'.js').'"></script>';
            }

            ///*
            // * Add the scripts with IE only filters?
            // */
            //if(isset_get($data['ie'])){
            //    $html = html_iefilter($html, $data['ie']);
            //
            //}else{
            //    $html = $html."\n";
            //}

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

        $core->register['js'] = array();

        /*
         * Should all JS scripts be loaded at the end (right before the </body> tag)?
         * This may be useful for site startup speedups
         */
        if(!empty($footer)){
            $core->register['footer'] = $footer.$core->register['footer'].$core->register('script_delayed');
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
    global $_CONFIG, $core;

    try{
        array_params($meta);
        array_params($params, 'title');

        array_default($params, 'http'          , 'html');
        array_default($params, 'captcha'       , false);
        array_default($params, 'doctype'       , '<!DOCTYPE html>');
        array_default($params, 'html'          , '<html lang="'.LANGUAGE.'">');
        array_default($params, 'body'          , '<body>');
        array_default($params, 'title'         , isset_get($meta['title']));
        array_default($params, 'links'         , '');
        array_default($params, 'extra'         , '');
        array_default($params, 'favicon'       , true);
        array_default($params, 'amp'           , false);
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

        if(!empty($params['amp'])){
            $params['links'] .= '<link rel="amphtml" href="'.domain('/amp'.$_SERVER['REQUEST_URI']).'">';
        }

        if(!empty($params['canonical'])){
            $params['links'] .= '<link rel="canonical" href="'.$params['canonical'].'">';
        }

//:DELETE: Above is already a meta-viewport
        //if(!empty($_CONFIG['bootstrap']['enabled'])){
        //    array_ensure($meta, 'viewport', $_CONFIG['bootstrap']['viewport']);
        //}

        /*
         * Add meta tag no-index for non production environments and admin pages
         */
        if(!$_CONFIG['production'] or $_CONFIG['noindex']){
           $meta['robots'] = 'noindex';
        }

        $title = html_title($meta['title']);
        unset($meta['title']);

        $retval =  $params['doctype'].
                   $params['html'].'
                   <head>'.
                  '<meta http-equiv="Content-Type" content="text/html;charset="'.$_CONFIG['charset'].'">'.
                  '<title>'.$title.'</title>';

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

                $retval .= '<link '.implode(' ', $sections).'>';
            }
        }

        foreach($params['prefetch_dns'] as $prefetch){
            $retval .= '<link rel="dns-prefetch" href="//'.$prefetch.'">';
        }

        foreach($params['prefetch_files'] as $prefetch){
            $retval .= '<link rel="prefetch" href="'.$prefetch.'">';
        }

        unset($prefetch);

        if(!empty($core->register['header'])){
            $retval .= $core->register['header'];
        }

        $retval .= html_generate_css().
                   html_generate_js();

        /*
         * Add required fonts
         */
        if(!empty($params['fonts'])){
            foreach($params['fonts'] as $font){
                $retval .= '<link href="'.$font.'" rel="stylesheet" type="text/css">';
            }
        }

        /*
         * Add all other meta tags
         * Only add keywords with contents, all that have none are considerred
         * as false, and do-not-add
         */
        foreach($meta as $keyword => $content){
            $retval .= '<meta name="'.$keyword.'" content="'.$content.'">';
        }

        if(!empty($params['properties'])){
            foreach($params['properties'] as $property => $content){
                $retval .= '<meta property="'.$property.'" content="'.$content.'">';
            }
        }

        $retval .= html_favicon($params['favicon']).$params['extra'];
        $retval .= '</head>'.$params['body'];

        if($_CONFIG['security']['csrf']['enabled'] === 'force'){
            /*
             * Always add a CSRF for ajax
             */
            $csrf  = set_csrf_code('ajax_');
            $html .= '<input type="hidden" id="ajax_csrf" name="ajax_csrf" value="'.$csrf.'">';
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('html_header(): Failed', $e);
    }
}



/*
 * Generate and return the HTML footer
 */
function html_footer(){
    global $_CONFIG, $core;

    try{
        if($core->register['footer']){
            return $core->register['footer'].'</body></html>';
        }

        return '</body></html>';

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
    global $_CONFIG, $core;

    try{
        if(!PLATFORM_HTTP){
            throw new bException('html_flash(): This function can only be executed on a webserver!');
        }

        if(!isset($_SESSION['flash'])){
            /*
             * Nothing to see here!
             */
            return '';
        }

        if(!is_array($_SESSION['flash'])){
            /*
             * $_SESSION['flash'] should always be an array. Don't crash on minor detail, just correct and continue
             */
            $_SESSION['flash'] = array();
// :TODO: MAKE THIS A NOTIFICATION!
            notify('invalid $_SESSION[flash]', tr('html_flash(): Invalid flash structure in $_SESSION array, it should always be an array but it is a ":type". Be sure to always use html_flash_set() to add new flash messages', array(':type' => gettype($_SESSION['flash']))), 'developers');
        }

        $retval = '';

        foreach($_SESSION['flash'] as $id => $flash){
            array_default($flash, 'class', null);

            if($flash['class'] and ($flash['class'] != $class)){
                continue;
            }

            array_default($flash, 'title', null);
            array_default($flash, 'type' , null);
            array_default($flash, 'html' , null);
            array_default($flash, 'text' , null);

            unset($flash['class']);

            switch($type = strtolower($flash['type'])){
                case 'info':
                    break;

                case 'information':
                    break;

                case 'success':
                    break;

                case 'error':
                    break;

                case 'warning':
                    break;

                case 'attention':
                    break;

                case 'danger':
                    break;

                default:
                    $type = 'error';
// :TODO: NOTIFY OF UNKNOWN HTML FLASH TYPE
            }

            if(!debug()){
                /*
                 * Don't show "function_name(): " part of message
                 */
                $flash['html'] = trim(str_from($flash['html'], '():'));
                $flash['text'] = trim(str_from($flash['text'], '():'));
            }

            /*
             * Set the indicator that we have added flash texts
             */
            switch($_CONFIG['flash']['type']){
                case 'html':
                    /*
                     * Either text or html could have been specified, or both
                     * In case both are specified, show both!
                     */
                    foreach(array('html', 'text') as $type){
                        if($flash[$type]){
                            if($flash['title']){
                                $flash[$type] = $flash['title'].': '.$flash[$type];
                            }

                            $retval .= tr($_CONFIG['flash']['html'], array(':message' => $flash[$type], ':type' => $flash['type'], ':hidden' => ''), false);
                        }
                    }

                    break;

                case 'sweetalert':
                    if($flash['html']){
                        /*
                         * Show specified html
                         */
                        $sweetalerts[] = array_remove($flash, 'text');
                    }

                    if($flash['text']){
                        /*
                         * Show specified text
                         */
                        $sweetalerts[] = array_remove($flash, 'html');
                    }

                    break;

                default:
                    throw new bException(tr('html_flash(): Unknown html flash type ":type" specified. Please check your $_CONFIG[flash][type] configuration', array(':type' => $_CONFIG['flash']['type'])), 'unknown');
            }

            $core->register['flash'] = true;
            unset($_SESSION['flash'][$id]);
        }

        switch($_CONFIG['flash']['type']){
            case 'html':
// :TODO: DONT USE tr() HERE!!!!
                /*
                 * Add an extra hidden flash text box that can respond for jsFlashMessages
                 */
                return $retval.tr($_CONFIG['flash']['html'], array(':message' => '', ':type' => '', ':hidden' => ' hidden'), false);

            case 'sweetalert':
                load_libs('sweetalert');

                switch(count(isset_get($sweetalerts))){
                    case 0:
                        /*
                         * No alerts
                         */
                        return '';

                    case 1:
                        return html_script(sweetalert(array_pop($sweetalerts)));

                    default:
                        /*
                         * Multiple modals, show a queue
                         */
                        return html_script(sweetalert_queue(array('modals' => $sweetalerts)));
                }
        }

    }catch(Exception $e){
        throw new bException('html_flash(): Failed', $e);
    }
}



/*
 * Show a flash message with the specified message
 */
function html_flash_set($params, $type = 'info', $class = null){
    global $_CONFIG;

    try{
        if(!PLATFORM_HTTP){
            throw new bException('html_flash_set(): This function can only be executed on a webserver!');
        }

        if(!$params){
            /*
             * Wut? no message?
             */
            throw new bException(tr('html_flash_set(): No messages specified'), 'not-specified');
        }

        /*
         * Ensure session flash data consistency
         */
        if(empty($_SESSION['flash'])){
            $_SESSION['flash'] = array();
        }

        if(is_object($params)){
            $object = $params;
            $params = array('class' => $class, // Done for backward compatibility
                            'title' => tr('Oops'));

            if($object instanceof bException){
                if(!$class){
                    $class = $type;
                }

                if(str_until($object->getCode(), '/') == 'warning'){
                    $params['type'] = 'warning';
                    $params['html'] = $object->getMessage();
                    $object->setCode(str_replace('/', '', str_replace('warning', '', $object->getCode())));

                }elseif($object->getCode() == 'validation'){
                    $params['type'] = 'warning';
                    $params['html'] = $object->getMessage();

                }elseif($object->getCode() == 'unknown'){
                    $params['type'] = 'warning';
                    $params['html'] = $object->getMessage();

                }else{
                    $params['type'] = 'error';

                    if(debug()){
                        $params['html'] = $object->getMessage();

                    }else{
                        /*
                         * This may or may not contain messages that are confidential.
                         * All bExceptions thrown by functions will contain the function name like function():
                         * If (): is detected in the primary message, assume it must be confidential
                         * If PHP error is detected in the primary message, assume it must be confidential
                         * Any other messages are generated by the web pages themselves and should be
                         * considered ok to show on production sites
                         */
                        $messages       = $object->getMessages();
                        $params['html'] = current($messages);

                        if(preg_match('/^[a-z_]+\(\): /', $params['html']) or preg_match('/PHP ERROR [\d+] /', $params['html'])){
                            $params['html'] = tr('Something went wrong, please try again later');
                            notify('html_flash/bException', tr('html_flash_set(): Received bException ":code" with message trace ":trace"', array(':code' => $params['type'], ':trace' => $params['html'])), 'developers');

                        }else{
                            /*
                             * Show all messages until a function(): message is found, those are considered to be
                             * confidential and should not be shown on production websites
                             */
                            foreach($messages as $id => $message){
                                if(!empty($delete) or preg_match('/^[a-z_]+\(\): /', $message) or preg_match('/PHP ERROR [\d+] /', $message)){
                                    unset($messages[$id]);
                                    $delete = true;
                                }
                            }

                            unset($delete);
                            $params['html'] = implode('<br>', $messages);
                        }
                    }
                }

            }elseif($object instanceof Exception){
                if(!$class){
                    $class = $type;
                }

                if($object->getCode() == 'validation'){
                    $params['type'] = 'warning';
                    $params['html'] = $object->getMessage();

                }else{
                    $params['type'] = 'error';

                    if(debug()){
                        $params['html'] = $object->getMessage();

                    }else{
                        /*
                         * Non bExceptions basically are caused by PHP and should basically not ever happen.
                         * These should also be considdered confidential and their info should never be
                         * displayed in production sites
                         */
                        $params['html'] = tr('Something went wrong, please try again later');
                        notify('html_flash/Exception', tr('html_flash_set(): Received PHP exception class ":class" with code ":code" and message ":message"', array(':class' => get_class($object), ':code' => $object->getCode(), ':message' => $object->getMessage())), 'developers');
                    }
                }

            }else{
                $params['type'] = 'error';
                $params['html'] = tr('Something went wrong, please try again later');
                notify('html_flash/object', tr('html_flash_set(): Received PHP object with class ":class" and content ":content"', array(':class' => get_class($object), ':content' => print_r($object->getMessage(), true))), 'developers');
            }
        }

        /*
         * Backward compatibility
         */
        if(!is_array($params)){
            $params = array('title' => str_capitalize($type),
                            'html'  => $params,
                            'type'  => $type,
                            'class' => $class);
        }

        /*
         * Backward compatibility as well
         */
        if(empty($params['html']) and empty($params['text']) and empty($params['title'])){
            if($_CONFIG['production']){
                throw new bException(tr('Invalid html_flash_set() call data ":data", should contain at least "text" or "html" or "title"!', array(':data' => $params)), 'invalid');
            }

            notify('invalid html flash set', $params, 'developers');
            return html_flash_set(implode(',', $params), $type, $class);
        }

        if(empty($params['title'])){
            $params['title'] = str_capitalize($params['type']);
        }

        $_SESSION['flash'][] = $params;

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
                if((isset_get($message['class']) == $class) or ($message['class'] == '*')){
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
        array_params ($params);
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
        array_default($params, 'tabindex'    , 0);

        if(!$params['name']){
            throw new bException(tr('html_select(): No name specified'), 'not-specified');
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
            return '<select'.$params['multiple'].($params['tabindex'] ? ' tabindex="'.$params['tabindex'].'"' : '').($params['id'] ? ' id="'.$params['id'].'_disabled"' : '').' name="'.$params['name'].'" '.($class ? ' class="'.$class.'"' : '').($params['extra'] ? ' '.$params['extra'] : '').' readonly disabled>'.
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
        $params['autosubmit'] = str_replace('[', '\\\\[', $params['autosubmit']);
        $params['autosubmit'] = str_replace(']', '\\\\]', $params['autosubmit']);

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
        array_params ($params);
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

                    $retval  .= '<option'.($params['class'] ? ' class="'.$params['class'].'"' : '').''.((($params['selected'] !== null) and ($key === $params['selected'])) ? ' selected' : '').' value="'.html_safe($key).'"'.$option_data.'>'.html_safe($value).'</option>';
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

                        $retval  .= '<option'.($params['class'] ? ' class="'.$params['class'].'"' : '').''.(($row[0] === $params['selected']) ? ' selected' : '').' value="'.html_safe($row[0]).'"'.$option_data.'>'.html_safe($row[1]).'</option>';
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
    global $_CONFIG, $core;

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
            $retval = '<script type="'.$type.'" src="'.cdn_domain().'js/'.substr($script, 1).'"'.($option ? ' '.$option : '').'></script>';

        }else{
            $retval = '<script type="'.$type.'"'.($option ? ' '.$option : '').">\n".
                            $script.
                      '</script>';
        }

        if($ie){
            $retval = html_iefilter($retval, $ie);
        }

        if(!$_CONFIG['cdn']['js']['load_delayed']){
            return $retval;
        }

        /*
         * SCRIPT tags are added all at the end of the page for faster loading
         * (and to avoid problems with jQuery not yet being available)
         */
        if(empty($core->register('script_delayed'))){
            $core->register['script_delayed']  = $retval;

        }else{
            $core->register['script_delayed'] .= $retval;
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
    global $_CONFIG, $core;

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
            if($core->callType('mobile')){
                if(!$params['mobile_icon']){
                    $params['mobile_icon'] = cdn_domain('img/mobile/favicon.png');
                }

                return '<link rel="apple-touch-icon'.($params['precomposed'] ? '-precompsed' : '').'"'.($sizes ? ' sizes="'.$sizes.'"' : '').' href="'.$params['mobile_icon'].'" />';

            }else{
                if(empty($params['icon'])){
                    $params['icon'] = cdn_domain('img/favicon.png');
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
            try{
                $image = sql_get('SELECT `width`,
                                         `height`

                                  FROM   `html_img_cache`

                                  WHERE  `url`       = :url
                                  AND    `createdon` > NOW() - INTERVAL 1 DAY
                                  AND    `status`    IS NULL',

                                  array(':url' => $src));

            }catch(Exception $e){
                notify($e);
                $image = null;
            }

            if($image){
                /*
                 * We have that information cached, yay!
                 */
                $width  = $image['width'];
                $height = $image['height'];

            }else{
                try{
                    $url      = preg_match('/^ftp|https?/i', $src);
                    $file_src = $src;

                    if(strstr($file_src, domain(''))){
                        $url      = false;
                        $file_src = str_from($file_src, domain(''));
                    }

                    if($url){
                        /*
                         * Image comes from a domain, fetch to temp directory to analize
                         */
                        load_libs('file');

                        try{
                            $file  = file_move_to_target($file_src, TMP, false, true);
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
                            notify('image does not exist', tr('html_img(): Specified image ":src" does not exist', array(':src' => $file_src)), 'developers');
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
                        if(file_exists(ROOT.'www/en/'.$file_src)){
                            $image = getimagesize(ROOT.'www/en/'.$file_src);

                        }else{
                            /*
                             * Image doesn't exist.
                             */
                            log_console(tr('html_img(): image ":src" does not exist', array(':src' => $file_src, ':width' => $width, ':height' => $height)), 'yellow');
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
                    log_console(tr('html_img(): image ":src" has invalid dimensions with width ":width" and height ":height"', array(':src' => $src, ':width' => $width, ':height' => $height)), 'yellow');

                }else{
                    try{
                        sql_query('INSERT INTO `html_img_cache` (`status`, `url`, `width`, `height`)
                                   VALUES                       (:status , :url , :width , :height )

                                   ON DUPLICATE KEY UPDATE `status`    = NULL,
                                                           `createdon` = NOW()',

                                   array(':url'    => $src,
                                         ':width'  => $width,
                                         ':height' => $height,
                                         ':status' => $status));

                    }catch(Exception $e){
                        notify($e);
                    }
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
function html_video($params){
    global $_CONFIG;

    try{
        array_ensure($params, 'src,width,height,more,type');
        array_default($params, 'controls', true);

        if(!$_CONFIG['production']){
            if(!$params['src']){
                throw new bException(tr('html_video(): No video src specified'), 'not-specified');
            }
        }

// :INVESTIGATE: Is better getting default width and height dimensions like in html_img()
// But in this case, we have to use a external "library" to get this done
// Investigate the best option for this!
        if(!$params['width']){
            throw new bException(tr('html_video(): No width specified'), 'not-specified');
        }

        if(!is_natural($params['width'])){
            throw new bException(tr('html_video(): Invalid width ":width" specified', array(':width' => $params['width'])), 'invalid');
        }

        if(!$params['height']){
            throw new bException(tr('html_video(): No height specified'), 'not-specified');
        }

        if(!is_natural($params['height'])){
            throw new bException(tr('html_video(): Invalid height ":height" specified', array(':height' => $params['height'])), 'invalid');
        }

        /*
         * Videos can be either local or remote
         * Local videos either have http://thisdomain.com/video, https://thisdomain.com/video, or /video
         * Remote videos must have width and height specified
         */
        if(substr($params['src'], 0, 7) == 'http://'){
            $protocol = 'http';

        }elseif($protocol = substr($params['src'], 0, 8) == 'https://'){
            $protocol = 'https';

        }else{
            $protocol = '';
        }

        if(!$protocol){
            /*
             * This is a local video
             */
            $params['src']  = ROOT.'www/en'.str_starts($params['src'], '/');
            $params['type'] = mime_content_type($params['src']);

        }else{
            if(preg_match('/^'.str_replace('/', '\/', str_replace('.', '\.', domain())).'\/.+$/ius', $params['src'])){
                /*
                 * This is a local video with domain specification
                 */
                $params['src']  = ROOT.'www/en'.str_starts(str_from($params['src'], domain()), '/');
                $params['type'] = mime_content_type($params['src']);

            }elseif(!$_CONFIG['production']){
                /*
                 * This is a remote video
                 * Remote videos MUST have height and width specified!
                 */
                if(!$params['height']){
                    throw new bException(tr('html_video(): No height specified for remote video'), 'not-specified');
                }

                if(!$params['width']){
                    throw new bException(tr('html_video(): No width specified for remote video'), 'not-specified');
                }

                switch($params['type']){
                    case 'mp4':
                        $params['type'] = 'video/mp4';
                        break;

                    case 'flv':
                        $params['type'] = 'video/flv';
                        break;

                    case '':
                        /*
                         * Try to autodetect
                         */
                        $params['type'] = 'video/'.str_rfrom($params['src'], '.');
                        break;

                    default:
                        throw new bException(tr('html_video(): Unknown type ":type" specified for remote video', array(':type' => $params['type'])), 'unknown');
                }
            }
        }

        /*
         * Build HTML
         */
        $html = '   <video width="'.$params['width'].'" height="'.$params['height'].'" '.($params['controls'] ? 'controls ' : '').''.($params['more'] ? ' '.$params['more'] : '').'>
                        <source src="'.$params['src'].'" type="'.$params['type'].'">
                    </video>';

        return $html;

    }catch(Exception $e){
        if(!$_CONFIG['production']){
            throw new bException('html_video(): Failed', $e);
        }

        notify($e);
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
    global $_CONFIG;

    try{
        if(!$_CONFIG['cdn']['min']){
            /*
             * Don't do anything. This way, on non debug systems, where this is
             * used to minify HTML output, we can still see normal HTML that is
             * a bit more readable.
             */
            return $html;
        }

        load_libs('minify');
        return minify_html($html);

    }catch(Exception $e){
        throw new bException(tr('html_minify(): Failed'), $e);
    }
}



/*
 * Generate and return a randon name for the specified $name, and store the
 * link between the two under "group"
 */
function html_translate($name){
    try{
        $translation = '__'.$name.'__'.substr(unique_code('sha256'), 0, 16);

        return $translation;

    }catch(Exception $e){
        throw new bException(tr('html_translate(): Failed'), $e);
    }
}



/*
 * Return the $_POST value for the translated specified key
 */
function html_untranslate(){
    try{
        $count = 0;

        foreach($_POST as $key => $value){
            if(substr($key, 0, 2) == '__'){
                $_POST[str_until(substr($key, 2), '__')] = $_POST[$key];
                unset($_POST[$key]);
                $count++;
            }
        }

        return $count;

    }catch(Exception $e){
        throw new bException(tr('html_untranslate(): Failed'), $e);
    }
}



/*
 *
 */
function html_form($action, $method, $class, $name = '', $csrf_check = true){
    try{
        /*
         * Avoid people fucking around
         */
        if(isset($_SESSION['csrf']) and (count($_SESSION['csrf']) >= 20)){
            /*
             * Too many csrf, so too many post requests open. Just dump all and
             * start from scratch
             */
            array_shift($_SESSION['csrf']);
        }

        if($csrf_check){
            $csrf = set_csrf_code();
            $csrf = '<input type="hidden" name="csrf" value="'.$csrf.'">';

        }else{
            $csrf = '';
        }

        foreach(array('name', 'method', 'action', 'class') as $key){
            if(!$$key) continue;
            $keys[] = $key.'="'.$$key.'"';
        }

        $form = '<form '.implode(' ', $keys).'>'.$csrf;

        return $form;

    }catch(Exception $e){
        throw new bException('html_form(): Failed', $e);
    }
}
?>
