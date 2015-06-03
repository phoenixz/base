<?php
/*
 * Empty library
 *
 * This is an empty template library file
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>
 */


/*
 * Pagination function, can create any type of HTML paging structure
 *
 * Example usage:
 * $html .= paging_generate(array('html'    => '<div class="center mbottom50">
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
function paging_generate($params){
    global $_CONFIG;

    try{
        array_params($params);

        array_default($params, 'current'       , isset_get($_GET['page']));
        array_default($params, 'prev_next'     , isset_get($_CONFIG['paging']['prev_next']));
        array_default($params, 'first_last'    , isset_get($_CONFIG['paging']['first_last']));
        array_default($params, 'show_pages'    , $_CONFIG['paging']['show_pages']);
        array_default($params, 'limit'         , $_CONFIG['paging']['limit']);
        array_default($params, 'hide_single'   , $_CONFIG['paging']['hide_single']);
        array_default($params, 'hide_ends'     , $_CONFIG['paging']['hide_ends']);
        array_default($params, 'disabled'      , '');

        array_key_check($params, 'show_pages,count,html,page,url'.($params['prev_next'] ? ',prev,next' : '').($params['first_last'] ? ',first,last' : ''));

        $params['current'] = force_natural_number($params['current']);
        $page_count        = ceil($params['count'] / $params['limit']);
        $html              = $params['html'];
        $url               = $params['url'];
        $current           = $params['current'];
        $list              = '';

        if(!$params['hide_ends']){
            $params['disabled'] = '';
        }

        if(($page_count <= 1) and $params['hide_single']){
            /*
             * There is only one page and we don't want to see a single page pager
             */
            return '';
        }

        if(!fmod($params['show_pages'], 2)){
            throw new bException('paging_generate(): show_pages should always be an odd number (1, 3, 5, etc)', 'invalid');
        }

        if($page_count < $params['show_pages']){
            $params['show_pages'] = $page_count;
        }

        /*
         * Add the first button
         */
        if($params['first_last']){
            if($current > 1){
                $disabled = '';

            }else{
                $disabled = $params['disabled'];
            }

            $line_url = str_replace('%page%', ($params['hide_ends'] ? '' : 1), $url);
            $list    .= str_replace('%disabled%', $disabled, str_replace('%page%', 1, str_replace('%url%', $line_url, $params['first'])));
        }

        /*
         * Add the previous button
         */
        if($params['prev_next']){
            if($current > 1){
                $disabled = '';

            }else{
                $disabled = $params['disabled'];
            }

            $line_url = str_replace('%page%', ((($current == 2) and $params['hide_ends']) ? '' : (($current - $params['show_pages'] < 1) ? 1 : $current - $params['show_pages'])), $url);
            $list    .= str_replace('%disabled%', $disabled, str_replace('%page%', 1, str_replace('%url%', $line_url, $params['prev'])));
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

        /*
         * Unless we fall over the max_pages limit
         */
        if($current > $page_count){
            $current = $page_count;
        }

        if($current > ($page_count - $params['show_pages'])){
            $current = $page_count - $params['show_pages'] + 1;
        }

        $display_count = $current + $params['show_pages'];

        for($current; $current < $display_count; $current++){
            $line_url = str_replace('%page%', ((($current == 1) and $params['hide_ends']) ? '' : $current), $url);
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
        if($params['prev_next']){
            if($params['current'] < $page_count){
                $disabled = '';

            }else{
                $disabled = $params['disabled'];
            }

            $list .= str_replace('%disabled%', $disabled, str_replace('%page%', $params['current'] + 1, str_replace('%url%', $url, $params['next'])));
        }

        /*
         * Add the last button
         */
        if($params['first_last']){
            if($params['current'] < $page_count){
                $disabled = '';

            }else{
                $disabled = $params['disabled'];
            }

            $list .= str_replace('%disabled%', $disabled, str_replace('%page%', $page_count, str_replace('%url%', $url, $params['last'])));
        }

        $html = str_replace('%list%', $list, $html);

        return $html.'<input type="hidden" name="page" id="page" value="'.$current.'">';

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
        throw new bException('paging_generate(): Failed', $e);
    }
}



/*
 * Ensure that the requested page is valid
 * Must be a number
 * 1 or larger
 * Lesser than the specified max value
 *
 * Default to $default which by default is 1
 */
function paging_check_page($page, $page_max){
    global $_CONFIG;

    try{
        $checked_page = force_natural_number($page, 1);

        if(($page and ($checked_page != $page)) or ($page > $page_max)){
            page_404();
        }

        return $page;

    }catch(Exception $e){
        throw new bException('paging_check_page(): Failed', $e);
    }
}



/*
 *
 */
function paging_data($page, $limit, $rows){
    global $_CONFIG;

    try{
        $retval['default_limit'] = $_CONFIG['paging']['limit'];
        $retval['limit']         = paging_limit($limit, $retval['default_limit']);
        $retval['display_limit'] = (($_CONFIG['paging']['limit'] == $retval['limit']) ? '' : $retval['limit']);
        $retval['pages']         = ceil($rows / $retval['limit']);
        $retval['page']          = paging_check_page($page, $retval['pages']);
        $retval['count']         = $rows;
        $retval['start']         = (force_natural_number($retval['page']) - 1) * $retval['limit'] + 1;
        $retval['stop']          = $retval['start'] + $retval['limit'] - 1;

        if($retval['stop'] > $retval['count']){
            /*
             * The stop value overpassed the count by a bit, so we might show "showing entry 305 of 301 entries".. Fix this here
             */
            $retval['stop'] = $retval['count'];
        }

        if($retval['limit']){
            $retval['query'] = ' LIMIT '.($retval['start'] - 1).', '.$retval['limit'];

        }else{
            $retval['query'] = '';
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('paging_data(): Failed', $e);
    }
}



/*
 *
 */
function paging_limit($limit, $default_limit = null){
    global $_CONFIG;

    try{
        return sql_valid_limit(not_empty($limit, $default_limit, $_CONFIG['paging']['limit']));

    }catch(Exception $e){
        throw new bException('paging_limit(): Failed', $e);
    }
}
?>
