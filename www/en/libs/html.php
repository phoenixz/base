<?php
/*
 * HTML library, containing all sorts of HTML functions
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>
 */



/*
 * Only allow execution on shell scripts
 */
function html_only(){
    if(PLATFORM != 'apache'){
        throw new lsException('html_only(): This can only be done over HTML', 'htmlonly');
    }
}



/*
 * Generate and return the HTML footer
 */
function html_iefilter($html, $filter){
    if(!$filter){
        return $html;
    }

    if($mod = str_until(str_from($filter, '.'), '.')){
        return "\n<!--[if ".$mod.' IE '.str_rfrom($filter, '.')."]>\n\t".$html."\n<![endif]-->\n";

    }elseif($filter == 'ie'){
        return "\n<!--[if IE ]>\n\t".$html."\n<![endif]-->\n";
    }

    return "\n<!--[if IE ".str_from($filter, 'ie')."]>\n\t".$html."\n<![endif]-->\n";
}



/*
 * Standard Pagination
 */
// Why the blooody fuck are there eval() calls in this function?!!?!? REWRITE!
function html_paging($current_page, $total_pages, $url_function) {
    if($GLOBALS['page_is_mobile']){
        $pages_ba = 0;

    }else{
        $pages_ba = 3;
    }

    $prev_page  = $current_page - 1;
    $next_page  = $current_page + 1;
    $first_page = 0;
    $last_page  = $total_pages;

    $html= '<div class="hPaging">
            <div class="Paging">';

    //Previous Page
    if($prev_page >= 0) {
        $html .= '<a class="PagingPrev" href="'.eval('return '.str_replace('###PAGE###', $prev_page, $url_function)).'">'.tr('Previous').'</a>';
    }

    //Next page
    if($next_page < $total_pages) {
        $html .= '<a class="PagingNext" href="'.eval('return '.str_replace('###PAGE###', $next_page, $url_function)).'">'.tr('Next').'</a>';
    }

    $html .= '<p class="PagingContent">';

    //first page
    if($current_page > 3) {
        $html .= '<a href="'.eval('return '.str_replace('###PAGE###', 0, $url_function)).'">1</a>
                  <span>...</span>';
    }

    //3 pages before this one
    $a = 0;
    while($a < $pages_ba) {
        $a++;

        $page = $current_page - 4 + $a;

        if($page >= 0) {
            $html .= '<a href="'.eval('return '.str_replace('###PAGE###', $page, $url_function)).'">'.($page + 1).'</a>';
        }
    }

    //current page
    $html .= '<span>'.cfi($current_page + 1).'</span>';

    //3 pages after this one
    $a = 0;
    while($a < $pages_ba) {
        $a++;

        $page = $current_page + $a;

        if($page<$total_pages) {
            $html .= '<a href="'.eval('return '.str_replace('###PAGE###', $page, $url_function)).'">'.($page + 1).'</a>';
        }
    }

    //Last page if its not already covered by next or 3 pages after (Not on mobile)
    if(!$GLOBALS['page_is_mobile']){
        if(($total_pages > $page) and ($total_pages != $next_page)) {
            $html .= '<span>...</span>
                      <a href="'.eval('return '.str_replace('###PAGE###', $total_pages, $url_function)).'">'.($total_pages + 1).'</a>';
        }
    }

    $html .= '</p>
            </div>
        </div>';

    return $html;
}



/*
 *
 */
function html_init_css($min = null){
    global $_CONFIG;

    if($min === null){
        $min = $_CONFIG['cdn']['min'];
    }

    if($GLOBALS['page_is_admin']){
        /*
         * Use normal admin CSS
         */
        $GLOBALS['css'] = array('admin' => array('min' => $min, 'media' => null));

    }elseif($GLOBALS['page_is_mobile'] or empty($_CONFIG['bootstrap']['enabled'])){
        /*
         * Use normal, default CSS
         */
        $GLOBALS['css'] = array('style' => array('min' => $min, 'media' => null));

    }else{
        /*
         * Use bootstrap CSS
         */
        $GLOBALS['css'] = array('bootstrap'       => array('min' => $min, 'media' => null),
//                                'bootstrap-theme' => array('min' => $min, 'media' => null),
                                'style'           => array('min' => $min, 'media' => null));
    }
}



/*
 * Store libs for later loading
 */
function html_load_css($files = '', $media = null, $min = null){
    global $_CONFIG;

    if(!$files){
        $files = array();
    }

    if(!is_array($files)){
        if(!is_string($files)){
            throw new lsException('html_load_css(): Invalid files specification');
        }

        $files = explode(',', $files);
    }

    if($media and is_bool($media)){
        $min   = $media;
        $media = null;
    }

    if($min === null){
        $min = $_CONFIG['cdn']['min'];
    }

    if(empty($GLOBALS['css'])){
        html_init_css($min);
    }

    foreach($files as $file){
        if($file == 'style') continue;

        $GLOBALS['css'][$file] = array('min'   => $min,
                                       'media' => $media);
    }
}



/*
 * Display libs in header
 */
function html_generate_css(){
    global $_CONFIG;

    try{
        if(empty($GLOBALS['css'])){
            html_init_css();
        }

        if(!empty($_CONFIG['cdn']['css']['post']) and !$GLOBALS['page_is_admin']){
            $GLOBALS['css']['post'] = array('min' => $_CONFIG['cdn']['min'], 'media' => (is_string($_CONFIG['cdn']['css']['post']) ? $_CONFIG['cdn']['css']['post'] : ''));
        }

        $retval = '';

        foreach($GLOBALS['css'] as $file => $meta) {
            if(!$file) continue;

            $html = '<link rel="stylesheet" type="text/css" href="'.$_CONFIG['root'].'/pub/css/'.((SUBENVIRONMENT and (substr($file, 0, 5) != 'base/')) ? SUBENVIRONMENT.'/' : '').(!empty($GLOBALS['page_is_mobile']) ? 'mobile/' : '').$file.($meta['min'] ? '.min.css' : '.css').'"'.($meta['media'] ? ' media="'.$meta['media'].'"' : '').'>';

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
        throw new lsException('html_generate_css(): Failed', $e);
    }
}



/*
 * Store list of libs that should be loaded in the header
 *
 * $option may be either "async" or "defer", see http://www.w3schools.com/tags/att_script_async.asp
 */
function html_load_js($files = '', $min = null, $option = null, $ie = null){
    global $_CONFIG;

    if(!$files){
        $files = array();
    }

    if(!is_array($files)){
        if(!is_string($files)){
            throw new lsException('html_load_js(): Invalid files specification');
        }

        $files = explode(',', $files);
    }

    if($min === null){
        $min = $_CONFIG['cdn']['min'];
    }

    if(!isset($GLOBALS['js'])){
        $GLOBALS['js'] = array();
    }

    if(empty($GLOBALS['js'])){
        if($GLOBALS['page_is_mobile'] or empty($_CONFIG['bootstrap']['enabled'])){
            /*
             * Use normal, default JS
             */
            $GLOBALS['js'] = array('jquery' => array('min' => $min));

        }else{
            /*
             * Use bootstrap JS
             */
            $GLOBALS['js'] = array('bootstrap' => array('min' => $min),
                                   'jquery'    => array('min' => $min));
        }
    }

    foreach($files as $file){
        if(substr($file, 0, 4) != 'http') {
            /*
             * Compatibility code: ALL LOCAL JS FILES SHOULD ALWAYS BE SPECIFIED WITHOUT .js OR .min.js!!
             */
// :TODO: SEND EMAIL NOTIFICATIONS!
            if(substr($file, -3, 3) == '.js'){
                $file = substr($file, 0, -3);

            }elseif((substr($file, -3, 3) == '.js') or (substr($file, -7, 7) == '.min.js')){
                $file = substr($file, 0, -7);
            }

        }

        $data = array('min' => $min);

        if($option){
            $data['option'] = $option;
        }

        if($ie){
            $data['ie'] = $ie;
        }

        $GLOBALS['js'][$file] = $data;
    }
}



/*
 * Display libs in header
 */
function html_generate_js(){
    global $_CONFIG;

    try{
        if(!isset($GLOBALS['js'])){
            return '';
        }

        /*
         * Shortcut to JS configuration
         */
        $js     = $_CONFIG['cdn']['js'];
        $min    = $_CONFIG['cdn']['min'];

        $libs   = array();
        $retval = '';

        /*
         * Set to load default JS libraries
         */
        foreach($js['default_libs'] as $lib){
            if($lib == 'base/jquery'){
                $lib .= $js['jquery_version'];
            }

            $libs[$lib] = array('min' => $min);
        }

        /*
         * Load JS libraries
         */
        foreach(array_merge($libs, $GLOBALS['js']) as $file => $data) {
            if(!$file)            continue;
            if($file == 'jquery') continue;

            if(substr($file, 0, 4) == 'http') {
                /*
                 * These are external scripts
                 */
                $html = '<script'.(!empty($data['option']) ? ' '.$data['option'] : '').' type="text/javascript" src="'.$file.'"></script>';

            } else {
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

                $html = '<script'.(!empty($data['option']) ? ' '.$data['option'] : '').' type="text/javascript" src="'.$_CONFIG['root'].'/pub/js/'.$file.($data['min'] ? '.min.js' : '.js').'"></script>';
            }

            /*
             * Add the scripts with IE only filters?
             */
            if(isset_get($data['ie'])){
                $retval .= html_iefilter($html, $data['ie']);

            }else{
                $retval .= $html."\n";
            }
        }

        if(!empty($js['load_delayed'])){
            /*
             * Load all JS scripts at the end (right before the </body> tag)
             * This may be useful for site startup speedups
             */
            $GLOBALS['footer'] = $retval.isset_get($GLOBALS['footer'], '');
            $retval            = '';
        }

        return $retval;

    }catch(Exception $e){
        throw new lsException('html_generate_js(): Failed', $e);
    }
}



/*
 * Generate and return the HTML header
 */
function html_header($params = null, $meta = array()){
    global $_CONFIG;

    try{
        array_params($params, 'title');
        array_default($params, 'http'   , 'html');
        array_default($params, 'doctype', 'html');
        array_default($params, 'html'   , 'html');
        array_default($params, 'body'   , '<body>');
        array_default($params, 'title'  , null);
        array_default($params, 'meta'   , $meta);
        array_default($params, 'link'   , array());
        array_default($params, 'extra'  , '');
        array_default($params, 'favicon', true);

        if(empty($params['meta']['description'])){
            throw new lsException('html_header(): No header meta description specified (SEO!)');
        }

        if(empty($params['meta']['keywords'])){
            throw new lsException('html_header(): No header meta keywords specified (SEO!)');
        }

        if(!empty($_CONFIG['meta'])){
            /*
             * Add default configured meta tags
             */
            $params['meta'] = array_merge($_CONFIG['meta'], $params['meta']);
        }

        if(!empty($_CONFIG['bootstrap']['enabled'])){
            array_ensure($params['meta'], 'viewport', $_CONFIG['bootstrap']['viewport']);
        }

        $params['title'] = html_title($params['title']);

        $retval = "<!DOCTYPE ".$params['doctype'].">\n".
                  "<".$params['html'].">\n".
                  "<head>\n".
                  "<meta http-equiv=\"Content-Type\" content=\"text/html;charset=".$_CONFIG['charset']."\">\n".
                  "<title>".$params['title']."</title>\n".
                      html_generate_css().
                      html_generate_js();

        /*
         * Add required fonts
         */
        if(!empty($_CONFIG['cdn']['fonts'])){
            foreach($_CONFIG['cdn']['fonts'] as $font){
                $retval .= "<link href=\"".$font."\" rel=\"stylesheet\" type=\"text/css\">\n";
            }
        }

        /*
         * Add all other meta tags
         * Only add keywords with contents, all that have none are considerred
         * as false, and do-not-add
         */
        foreach($params['meta'] as $keyword => $contents){
            if($contents){
                $retval .= "<meta name=\"".$keyword."\" content=\"".$contents."\">\n";
            }
        }

        $retval .= html_favicon($params['favicon']).$params['extra'];

        /*
         * Add viewport meta tag for mobile devices
         */
        if(!empty($_SESSION['mobile'])){
            if(!empty($_CONFIG['mobile']['viewport'])){
                $retval .= $_CONFIG['mobile']['viewport']."\n";
            }
        }

        if($params['http']){
            load_libs('http');
            http_start($params['http']);
        }

        return $retval."</head>\n".
                       $params['body']."\n";

    }catch(Exception $e){
        throw new lsException('html_header(): Failed', $e);
    }
}



/*
 * Generate and return the HTML footer
 */
function html_footer(){
    global $_CONFIG;

    if(empty($_CONFIG['cdn']['js']['load_delayed'])){
        $retval = '';

    }else{
        $retval = isset_get($GLOBALS['footer'], '');
    }

    return $retval."</body>\n</html>";
}



/*
 * Generate and return the HTML footer
 */
function html_title($params){
    global $_CONFIG;

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

        throw new lsException('html_title(): Invalid title specified');
    }

    /*
     * Do a search / replace on all specified items to create correct title
     */
    foreach($params as $key => $value){
        $title = str_replace($key, $value, $title);
    }

    return $title;
}



/*
 * Show a flash message with the specified message
 */
function html_flash($messages = '', $type = 'info', $basicmessage = null){
    global $_CONFIG;

    try{
        if(PLATFORM != 'apache'){
            throw new lsException('html_flash(): This function can only be executed on a webserver!');
        }

        if(!is_array($messages)){
            if(!$messages){
                $messages = array();

            }else{
                $messages = array($messages);
            }
        }

        /*
         * Maybe a message set in session?
         */
        if(!empty($_SESSION['flash'])){
            if(!is_array($_SESSION['flash'])){
                /*
                 * FAILSAFE: $_SESSION['flash'] should always be an array but we don't want to crash on triviality
                 */
                $_SESSION['flash'] = array($_SESSION['flash']);
            }

            /*
             * Add the stored session flash messages on the top of the messes list
             */
            $messages = array_merge($_SESSION['flash'], $messages);
            unset($_SESSION['flash']);
        }

        /*
         * Empty message will only add a hidden empty flash box which can be used later by $.flashMessage()!
         */
        if(empty($messages)){
            return '<div id="jsFlashMessage" class="'.$_CONFIG['flash']['css_name'].' '.$_CONFIG['flash']['prefix'].'" style="display:none;"></div>';
        }

        $retval = '';

        foreach($messages as $message){
            if(is_object($message) and $message instanceof Exception){
                $message = array('type'    => 'error',
                                 'message' => trim(str_from($message->getMessage(), '():')),
                                 'basic'   => $basicmessage);
            }

            if(is_array($message)){
                /*
                 * The message contains what type and basic (usually this comes from $_SESSION[flash]
                 */
                $usetype  = $message['type'];
                $usebasic = $message['basic'];
                $message  = $message['message'];

            }else{
                $usetype  = $type;
                $usebasic = $basicmessage;
            }

            if($usebasic === null){
                $usebasic = tr('Something went wrong, please try again later');
            }

            $message = ((($usetype == 'error') and !debug() and $usebasic and ($usebasic !== true)) ? $usebasic : $message);

            switch(strtolower($usetype)){
                case 'info':
                    $usetype = 'information';
                    // FALLTHROUGH

                case 'information':
                    break;

                case 'success':
                    break;

                case 'error':
                    break;

                case 'warning':
                    $usetype = 'attention';
                    // FALLTHROUGH

                case 'attention':
                    break;

                default:
                    throw new lsException('html_flash(): Unknown flash type "'.str_log($usetype).'" specified. Please specify one of "info" or "success" or "attention" or "error"', 'flash/unknown');
            }

    //        $retval .= '<div class="sys_bg sys_'.$usetype.'"></div><div class="'.$_CONFIG['flash']['css_name'].' sys_'.$usetype.'">'.$message.'</div>';
            $retval .= '<div class="'.$_CONFIG['flash']['css_name'].' '.$_CONFIG['flash']['prefix'].$usetype.'">'.$_CONFIG['flash']['button'].$message.'</div>';
        }

        return $retval;

    }catch(Exception $e){
        throw new lsException('html_flash(): Failed', $e);
    }
}



/*
 * Show a flash message with the specified message
 */
function html_flash_set($messages, $type = 'info', $basicmessage = null){
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
        $messages = array($messages);
    }

    foreach($messages as $message){
        if(is_object($message) and $message instanceof Exception){
            $type    = 'error';
            $message = $message->getMessage();
            $message = (strstr($message, '():') ? trim(str_from($message, '():')) : $message);
            $basic   = $basicmessage;
        }

        $_SESSION['flash'][] = array('type'    => $type,
                                     'basic'   => $basicmessage,
                                     'message' => $message);
    }
}




/*
 * Return an HTML <select> list
 */
function html_select($params, $selected = null, $name = '', $none = '', $class = '', $option_class = '', $disabled = false) {
    try{
        array_params ($params, 'resource');
        array_default($params, 'class'       , $class);
        array_default($params, 'disabled'    , $disabled);
        array_default($params, 'name'        , $name);
        array_default($params, 'id'          , $params['name']);
        array_default($params, 'none'        , not_empty($none, tr('None selected')));
        array_default($params, 'empty'       , not_empty($none, tr('None available')));
        array_default($params, 'option_class', $option_class);
        array_default($params, 'selected'    , $selected);
        array_default($params, 'bodyonly'    , false);
        array_default($params, 'autosubmit'  , false);
        array_default($params, 'onchange'    , '');
        array_default($params, 'id_column'   , 'id');

        array_default($params, $params['id_column'], $params['name']);

        if(!$params['name']){
            throw new lsException('html_select(): No name specified');
        }

        if(is_numeric($params['disabled'])){
            if(!$params['resource']){
                $params['disabled'] = true;

            }else{
                $params['disabled'] = (($params['resource']->rowCount() + ($params['name'] ? 1 : 0)) <= $params['disabled']);
            }
        }

        if($params['bodyonly']){
            return html_select_body($params);
        }

        if($params['empty']){
            $empty           = $params['empty'];
            $params['empty'] = true;
        }

        /*
         * <select> class should not be applied to <option>
         */
        $class = $params['class'];
        unset($params['class']);

        if(!$body = html_select_body($params)){
            /*
             * Select body is empty, contains no entries.
             * Add the empty entry, and disable the select
             */
            //if(!empty($empty)){
            //    $body  = '<option'.($class ? ' class="'.$class.'"' : '').' value="">'.$empty.'</option>';
            //    $params['readonly'] = true;
            //}
        }

        if($params['disabled']){
            /*
             * Add a hidden element with the name to ensure that multiple selects with [] will not show holes
             */
            $retval = '<select'.($params[$params['id_column']] ? ' id="'.$params['id'].'_disabled"' : '').' name="'.$params['name'].'" '.($class ? ' class="'.$class.'"' : '').($params['disabled'] ? ' disabled' : '').'>'.
                      $body.'</select><input type="hidden" name="'.$params['name'].'" >';
        }else{
            $retval = '<select'.($params[$params['id_column']] ? ' id="'.$params['id'].'"' : '').' name="'.$params['name'].'" '.($class ? ' class="'.$class.'"' : '').($params['disabled'] ? ' disabled' : '').'>'.
                      $body.'</select>';
        }

        if($params['onchange']){
            /*
             * Execute the JS code for an onchange
             */
            return $retval.html_script('$("#'.$params['id'].'").onsubmit(function(){ '.$params['onchange'].' });');

        }elseif(!$params['autosubmit']){
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
        return $retval.html_script('$("'.$params['autosubmit'].'").change(function(){ $(this).closest("form").submit(); });');

    }catch(Exception $e){
        throw new lsException('html_select(): Failed', $e);
    }
}



/*
 * Return the body of an HTML <select> list
 */
function html_select_body($params, $selected = null, $none = '', $class = '', $auto_select = true) {
    try{
        array_params ($params, 'resource');
        array_default($params, 'class'      , $class);
        array_default($params, 'none'       , not_empty($none, tr('None selected')));
        array_default($params, 'empty'      , not_empty($none, tr('None available')));
        array_default($params, 'selected'   , $selected);
        array_default($params, 'auto_select', $auto_select);
        array_default($params, 'id_column'  , 'id');

        if($params['none']){
            $retval = '<option'.($params['class'] ? ' class="'.$params['class'].'"' : '').''.(!$params['selected'] ? ' selected' : '').' value="">'.$params['none'].'</option>';

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
                    $notempty = true;
                    $retval  .= '<option'.($params['class'] ? ' class="'.$params['class'].'"' : '').''.(($key == $params['selected']) ? ' selected' : '').' value="'.$key.'">'.$value.'</option>';
                }

            }elseif(is_object($params['resource'])){
                if(!($params['resource'] instanceof PDOStatement)){
                    throw new lsException(tr('html_select_body(): Specified resource object is not an instance of PDOStatement'), 'invalidresource');
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
                while($row = sql_fetch($params['resource'])){
                    $notempty = true;

                    /*
                     * To avoid select problems with "none" entries, empty id column values are not allowed
                     */
                    if(!$row[$params['id_column']]){
                        $row[$params['id_column']] = str_random(8);
                    }

                    $retval  .= '<option'.($params['class'] ? ' class="'.$params['class'].'"' : '').''.(($row[$params['id_column']] === $params['selected']) ? ' selected' : '').' value="'.$row[$params['id_column']].'">'.$row['name'].'</option>';
                }

            }else{
                throw new lsException(tr('html_select_body(): Specified resource "'.str_log($params['resource']).'" is neither an array or resource'), 'invalidresource');
            }

        }elseif($params['resource'] !== false){
            throw new lsException('html_select_body(): No valid resource specified');
        }


        if(empty($notempty)){
            /*
             * No conent (other than maybe the "none available" entry) was added
             */
            if($params['empty'] === true){
                /*
                 * Return empty body so that the html_select() function can ensure the select box will be disabled
                 */
                return '';
            }

            if($params['empty']){
            }
        }

        return $retval;

    }catch(Exception $e){
        throw new lsException('html_select_body(): Failed', $e);
    }
}



/*
 * Generate and return the HTML footer
 *
 * $option maybe either "async" or "defer", see http://www.w3schools.com/tags/att_script_async.asp
 */
function html_script($script, $jquery_ready = true, $option = null, $type = null, $ie = false){
    global $_CONFIG;

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
        $retval = '<script type="'.$type.'" src="/pub/js/'.substr($script, 1).'"'.($option ? ' '.$option : '').'></script>';

    }else{
        $retval = '<script type="'.$type.'"'.($option ? ' '.$option : '').">\n".
                        $script.
                  '</script>';
    }

    if($ie){
        $retval = html_iefilter($retval, $ie);
    }

    if(!empty($_CONFIG['cdn']['js']['load_delayed'])){
        /*
         * Add all <script> at the end of the page
         */
        $GLOBALS['footer'] = isset_get($GLOBALS['footer'], '')."\n".$retval;
        $retval = '';
    }

    return $retval;
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
                    $params['mobile_icon'] = $_CONFIG['root'].'/pub/img'.(SUBENVIRONMENTNAME ? '/'.SUBENVIRONMENTNAME : '').'/mobile/favicon.png';
                }

                return '<link rel="apple-touch-icon'.($params['precomposed'] ? '-precompsed' : '').'"'.($sizes ? ' sizes="'.$sizes.'"' : '').' href="'.$params['mobile_icon'].'" />';

            }else{
                if(!$params['icon']){
                    $params['icon'] = $_CONFIG['root'].'/pub/img'.(SUBENVIRONMENTNAME ? '/'.SUBENVIRONMENTNAME : '').'/favicon.png';
                }

                return '<link rel="shortcut icon" type="image/x-icon"'.($sizes ? ' sizes="'.$sizes.'"' : '').'  href="'.$params['icon'].'" />';
            }
        }

    }catch(Exception $e){
        throw new lsException('html_favicon(): Failed', $e);
    }
}



/*
 * Create HTML for an HTML step process bar
 */
function html_list($params, $selected = ''){
    try{
        if(!is_array($params)){
            throw new lsException('html_list(): Specified params is not an array', 'invalid');
        }

        if(empty($params['steps']) or !is_array($params['steps'])){
            throw new lsException('html_list(): params[steps] is not specified or not an array', 'invalid');
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
        throw new lsException('html_list(): Failed', $e);
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
        throw new lsException('html_status_select(): Failed', $e);
    }
}
?>
