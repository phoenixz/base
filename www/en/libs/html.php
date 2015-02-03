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
 * Pagination function, can create any type of HTML paging structure
 *
 * Example usage:
 * $html .= html_paging(array('html'    => '<div class="center mbottom50">
 *                                              <ul class="pagination clearfix reset-list">
 *                                                  %list%
 *                                              </ul>
 *                                          </div>',
 *                            'current' => isset_get($current_page, 1),
 *                            'count'   => sql_get('SELECT COUNT(`id`) AS count FROM `blogs_posts` '.$where, $execute, 'count'),
 *                            'active'  => 'class="active"',
 *                            'url'     => c_city_url($category['seoname'], $_GET['category'], '%page%'),
 *                            'page'    => '<li%active%><a href="%url%">%page%</a></li>',
 *                            'prev'    => '<li><a href="%url%">'.tr('Prev').'</a></li>',
 *                            'next'    => '<li><a href="%url%">'.tr('Next').'</a></li>',
 *                            'first'   => '<li><a href="%url%">'.tr('First').'</a></li>',
 *                            'last'    => '<li><a href="%url%">'.tr('Last').'</a></li>')).'
 *
 */
function html_paging($params){
    global $_CONFIG;

    try{
        array_params($params);

        array_default($params, 'current'       , isset_get($_GET['page']));
        array_default($params, 'prev_next'     , isset_get($_CONFIG['paging']['prev_next']));
        array_default($params, 'first_last'    , isset_get($_CONFIG['paging']['first_last']));
        array_default($params, 'show_pages'    , $_CONFIG['paging']['show_pages']);
        array_default($params, 'items_per_page', $_CONFIG['paging']['items_per_page']);
        array_default($params, 'hide_first'    , $_CONFIG['paging']['hide_first']);
        array_default($params, 'hide_single'   , $_CONFIG['paging']['hide_single']);

        array_key_check($params, 'current,show_pages,count,html,page,url'.($params['prev_next'] ? ',prev,next' : '').($params['first_last'] ? ',first,last' : ''));

        $page_count = ceil($params['count'] / $params['items_per_page']);
        $html       = $params['html'];
        $url        = $params['url'];
        $current    = $params['current'];
        $list       = '';

        if(($page_count <= 1) and $params['hide_single']){
            /*
             * There is only one page and we don't want to see a single page pager
             */
            return '';
        }

        if(!fmod($params['show_pages'], 2)){
            throw new bException('html_paging(): show_pages should always be an odd number (1, 3, 5, etc)', 'invalid');
        }

        if($page_count < $params['show_pages']){
            $params['show_pages'] = $page_count;
        }

        /*
         * Add the first button
         */
        if($params['first_last'] and ($current > 1)){
            $line_url = str_replace('%page%', ($params['hide_first'] ? '' : 1), $url);
            $list    .= str_replace('%page%', 1                               , str_replace('%url%', $line_url, $params['first']));
        }

        /*
         * Add the previous button
         */
        if($params['prev_next'] and ($current > 1)){
            $line_url = str_replace('%page%', ((($current == 2) and $params['hide_first']) ? '' : $current - 1), $url);
            $list    .= str_replace('%page%', 1                                                                , str_replace('%url%', $line_url, $params['prev']));
        }

        /*
         * Build the center page list with the current page in the center
         */
        $current = $current - floor($params['show_pages'] / 2);

        /*
         * Unless we fall over the <1 limit
         */
        if($current < 1){
            $current = 1;
        }

        for($current; $current <= $page_count; $current++){
            $line_url = str_replace('%page%', ((($current == 1) and $params['hide_first']) ? '' : $current), $url);
            $line     = str_replace('%page%', $current, str_replace('%url%', $line_url, $params['page']));

            if($current == $params['current']){
                $line = str_replace('%active%', ' '.$params['active'].' ', $line);

            }else{
                $line = str_replace('%active%', ''                       , $line);
            }

            $list .= $line;

        }

        /*
         * Add the next button
         */
        if($params['prev_next'] and ($params['current'] < $page_count)){
            $list .= str_replace('%page%', $params['current'] + 1, str_replace('%url%', $url, $params['next']));
        }

        /*
         * Add the last button
         */
        if($params['first_last'] and ($params['current'] < $page_count)){
            $list .= str_replace('%page%', $page_count, str_replace('%url%', $url, $params['last']));
        }

        $html = str_replace('%list%', $list, $html);

        return $html;

// :DELETE: This is the old paging code, which was crap and no longer supported. Delete ASAP
        //if($GLOBALS['page_is_mobile']){
        //    $pages_ba = 0;
        //
        //}else{
        //    $pages_ba = 3;
        //}
        //
        //$prev_page  = $current_page - 1;
        //$next_page  = $current_page + 1;
        //$first_page = 0;
        //$last_page  = $total_pages;
        //
        //$html= '<div class="hPaging">
        //        <div class="Paging">';
        //
        ////Previous Page
        //if($prev_page >= 0) {
        //    $html .= '<a class="PagingPrev" href="'.eval('return '.str_replace('###PAGE###', $prev_page, $url_function)).'">'.tr('Previous').'</a>';
        //}
        //
        ////Next page
        //if($next_page < $total_pages) {
        //    $html .= '<a class="PagingNext" href="'.eval('return '.str_replace('###PAGE###', $next_page, $url_function)).'">'.tr('Next').'</a>';
        //}
        //
        //$html .= '<p class="PagingContent">';
        //
        ////first page
        //if($current_page > 3) {
        //    $html .= '<a href="'.eval('return '.str_replace('###PAGE###', 0, $url_function)).'">1</a>
        //              <span>...</span>';
        //}
        //
        ////3 pages before this one
        //$a = 0;
        //while($a < $pages_ba) {
        //    $a++;
        //
        //    $page = $current_page - 4 + $a;
        //
        //    if($page >= 0) {
        //        $html .= '<a href="'.eval('return '.str_replace('###PAGE###', $page, $url_function)).'">'.($page + 1).'</a>';
        //    }
        //}
        //
        ////current page
        //$html .= '<span>'.cfi($current_page + 1).'</span>';
        //
        ////3 pages after this one
        //$a = 0;
        //while($a < $pages_ba) {
        //    $a++;
        //
        //    $page = $current_page + $a;
        //
        //    if($page<$total_pages) {
        //        $html .= '<a href="'.eval('return '.str_replace('###PAGE###', $page, $url_function)).'">'.($page + 1).'</a>';
        //    }
        //}
        //
        ////Last page if its not already covered by next or 3 pages after (Not on mobile)
        //if(!$GLOBALS['page_is_mobile']){
        //    if(($total_pages > $page) and ($total_pages != $next_page)) {
        //        $html .= '<span>...</span>
        //                  <a href="'.eval('return '.str_replace('###PAGE###', $total_pages, $url_function)).'">'.($total_pages + 1).'</a>';
        //    }
        //}
        //
        //$html .= '</p>
        //        </div>
        //    </div>';
        //
        //return $html;

    }catch(Exception $e){
        throw new bException('html_paging(): Failed', $e);
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
            if($file == 'style') continue;

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

        if($GLOBALS['page_is_admin']){
            /*
             * Use normal admin CSS
             */
            $GLOBALS['css']['admin'] = array('media' => null);

        }elseif($GLOBALS['page_is_mobile'] or empty($_CONFIG['bootstrap']['enabled'])){
            /*
             * Use normal, default CSS
             */
            $GLOBALS['css']['style'] = array('media' => null);

        }else{
            /*
             * Use bootstrap CSS
             */
            $GLOBALS['css'][$_CONFIG['bootstrap']['css']] = array('media' => null);
            $GLOBALS['css']['style']                      = array('media' => null);
//            $GLOBALS['css'][''bootstrap-theme']           => array('media' => null),
        }

        if(!empty($_CONFIG['cdn']['css']['post']) and !$GLOBALS['page_is_admin']){
            $GLOBALS['css']['post'] = array('min' => $_CONFIG['cdn']['min'], 'media' => (is_string($_CONFIG['cdn']['css']['post']) ? $_CONFIG['cdn']['css']['post'] : ''));
        }

        $retval = '';
        $min    = $_CONFIG['cdn']['min'];

        foreach($GLOBALS['css'] as $file => $meta) {
            if(!$file) continue;

            $html = '<link rel="stylesheet" type="text/css" href="'.$_CONFIG['root'].'/pub/css/'.((SUBENVIRONMENT and (substr($file, 0, 5) != 'base/')) ? SUBENVIRONMENT.'/' : '').(!empty($GLOBALS['page_is_mobile']) ? 'mobile/' : '').$file.($min ? '.min.css' : '.css').'"'.($meta['media'] ? ' media="'.$meta['media'].'"' : '').'>';

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
        if(empty($GLOBALS['js'])){
            return '';
        }

        /*
         * Shortcut to JS configuration
         */
        $js     = $_CONFIG['cdn']['js'];
        $min    = ($_CONFIG['cdn']['min'] ? '.min' : '');

        $libs   = array();
        $retval = '';

        /*
         * Set to load default JS libraries
         */
        foreach($js['default_libs'] as $lib){
            if($lib == 'base/jquery'){
                $lib .= $js['jquery_version'];
            }

            $libs[$lib] = array();
        }

        /*
         * Load JS libraries
         */
        foreach($GLOBALS['js'] = array_merge($libs, $GLOBALS['js']) as $file => $data) {
            if(!$file) continue;

            $check = str_rfrom(str_starts($file, '/'), '/');

            if($check == 'jquery')    continue; // jQuery js is always loaded in the header
            if($check == 'bootstrap') continue; // bootstrap js is always loaded in the header

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

                $html = '<script'.(!empty($data['option']) ? ' '.$data['option'] : '').' type="text/javascript" src="'.$_CONFIG['root'].'/pub/js/'.$file.$min.'.js"></script>';
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

        /*
         * Should all JS scripts be loaded at the end (right before the </body> tag)?
         * This may be useful for site startup speedups
         */
        if(!empty($js['load_delayed'])){
            $GLOBALS['footer'] = $retval.isset_get($GLOBALS['footer'], '').isset_get($GLOBALS['script_delayed'], '');
            $retval            = '';
        }

        /*
         * Always load jQuery!
         * Always load jQuery in the HEAD so that in site <script> that use jQuery will work
         */
        $jquery = '<script'.(!empty($data['option']) ? ' '.$data['option'] : '').' type="text/javascript" src="'.$_CONFIG['root'].'/pub/js/base/jquery'.$min.".js\"></script>\n";

        if(!$GLOBALS['page_is_mobile'] and !empty($_CONFIG['bootstrap']['enabled'])){
            /*
             * Use bootstrap JS
             */
            $jquery .= '<script'.(!empty($data['option']) ? ' '.$data['option'] : '').' type="text/javascript" src="'.$_CONFIG['root'].'/pub/js/'.$_CONFIG['bootstrap']['js'].$min.".js\"></script>\n";
        }

        return $jquery.$retval;

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
        array_params($params, 'title');
        array_default($params, 'http'     , 'html');
        array_default($params, 'doctype'  , 'html');
        array_default($params, 'html'     , 'html');
        array_default($params, 'body'     , '<body>');
        array_default($params, 'title'    , null);
        array_default($params, 'meta'     , $meta);
        array_default($params, 'link'     , array());
        array_default($params, 'extra'    , '');
        array_default($params, 'favicon'  , true);

        if(!empty($params['js'])){
            html_load_js($params['js']);
        }

        if(!empty($params['css'])){
            html_load_css($params['css']);
        }

        if(empty($params['meta']['description'])){
            throw new bException('html_header(): No header meta description specified (SEO!)');
        }

        if(empty($params['meta']['keywords'])){
            throw new bException('html_header(): No header meta keywords specified (SEO!)');
        }

        if(!empty($params['meta']['noindex'])){
            $params['meta']['robots'] = 'noindex';
            unset($params['meta']['noindex']);
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

        $retval = http_headers($params).
                  "<!DOCTYPE ".$params['doctype'].">\n".
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
            if((ENVIRONMENT == 'production') or (empty($_CONFIG['cdn']['production_fonts']))){
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
function html_flash($messages = '', $type = 'info', $basicmessage = null){
    global $_CONFIG;

    try{
        if(PLATFORM != 'apache'){
            throw new bException('html_flash(): This function can only be executed on a webserver!');
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
                                 'message' => $message->getMessage(),
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
                    throw new bException('html_flash(): Unknown flash type "'.str_log($usetype).'" specified. Please specify one of "info" or "success" or "attention" or "error"', 'flash/unknown');
            }

            if(!debug()){
                /*
                 * Don't show "function_name(): " part of message
                 */
                $message = trim(str_from($message, '():'));
            }

    //        $retval .= '<div class="sys_bg sys_'.$usetype.'"></div><div class="'.$_CONFIG['flash']['css_name'].' sys_'.$usetype.'">'.$message.'</div>';
            $retval .= '<div class="'.$_CONFIG['flash']['css_name'].' '.$_CONFIG['flash']['prefix'].$usetype.'">'.$_CONFIG['flash']['button'].$message.'</div>';
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('html_flash(): Failed', $e);
    }
}



/*
 * Show a flash message with the specified message
 */
function html_flash_set($messages, $type = 'info', $basicmessage = null){
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
                $type     = 'error';
                $messages = $messages->getMessage();
                $messages = (strstr($messages, '():') ? trim(str_from($messages, '():')) : $messages);
                $basic    = $basicmessage;
            }

            if(is_string($messages) and (strpos($messages, "\n") !== false)){
                $messages = explode("\n", $messages);

            }else{
                $messages = array($messages);
            }
        }

        foreach($messages as $message){
            $_SESSION['flash'][] = array('type'    => $type,
                                         'basic'   => $basicmessage,
                                         'message' => $message);
        }

    }catch(Exception $e){
        throw new bException('html_flash_set(): Failed', $e);
    }
}




/*
 * Return an HTML <select> list
 */
function html_select($params, $selected = null, $name = '', $none = '', $class = '', $option_class = '', $disabled = false) {
    static $count = 0;

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
        array_default($params, 'hide_empty'  , false);
        array_default($params, 'autofocus'   , false);

        array_default($params, $params['id_column'], $params['name']);

        if(!$params['name']){
            throw new bException('html_select(): No name specified');
        }

        if(!$params['resource']){
            if($params['hide_empty']){
                return '';
            }

            if(is_numeric($params['disabled'])){
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

        if(substr($params['id'], -2, 2) == '[]'){
            $params['id'] = substr($params['id'], 0, -2).$count++;
        }

        if($params['disabled']){
            /*
             * Add a hidden element with the name to ensure that multiple selects with [] will not show holes
             */
            return '<select'.($params[$params['id_column']] ? ' id="'.$params['id'].'_disabled"' : '').' name="'.$params['name'].'" '.($class ? ' class="'.$class.'"' : '').' readonly disabled>'.
                    $body.'</select><input type="hidden" name="'.$params['name'].'" >';
        }else{
            $retval = '<select'.($params[$params['id_column']] ? ' id="'.$params['id'].'"' : '').' name="'.$params['name'].'" '.($class ? ' class="'.$class.'"' : '').($params['disabled'] ? ' disabled' : '').($params['autofocus'] ? ' autofocus' : '').'>'.
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
        return $retval.html_script('$("'.$params['autosubmit'].'").change(function(){ $(this).closest("form").submit(); });');

    }catch(Exception $e){
        throw new bException('html_select(): Failed', $e);
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
                    $notempty = true;
                    $retval  .= '<option'.($params['class'] ? ' class="'.$params['class'].'"' : '').''.((($params['selected'] !== null) and ($key == $params['selected'])) ? ' selected' : '').' value="'.$key.'">'.$value.'</option>';
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
                throw new bException(tr('html_select_body(): Specified resource "'.str_log($params['resource']).'" is neither an array or resource'), 'invalidresource');
            }

        }elseif($params['resource'] !== false){
            throw new bException('html_select_body(): No valid resource specified');
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

        if(empty($_CONFIG['cdn']['js']['load_delayed'])){
            return $retval;
        }

        /*
         * SCRIPT tags are added all at the end of the page for faster loading
         * (and to avoid problems with jQuery not yet being available)
         */

        $GLOBALS['script_delayed'] = $retval;
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



/*
 * Create the page using the custom library c_page function and add content-length header and send HTML to client
 */
function html_send($params, $meta, $html){
    $html = c_page($params, $meta, $html);

    header('Content-Length: '.mb_strlen($html));
    echo $html;
    die();
}
?>
