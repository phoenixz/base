<?php
/*
 * Wordpress library
 *
 * This library contains various functions to work with wordpress
 *
 * See doc/wordpress.txt for more information and a (possible) problem fix on wordpress hanging
 * after 10.000+ pages!
 *
 * @copyright Sven Oostenbrink
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */



/*
 * Signin into the word press admin console
 *
 * @params array $params ['url']        [REQUIRED]
 * @params array $params ['username']   [REQUIRED]
 * @params array $params ['password']   [REQUIRED]
 * @params array $params ['rememberme'] [OPTIONAL]
 * @params array $params ['simulation'] [OPTIONAL]
 * @params array $params ['redirect']   [OPTIONAL]
 *
 * @return array an associative array containing a cURL object.
 */
function wp_admin_signin($params){
    try{
        array_params($params);
        array_default($params, 'rememberme', true);                      // Check the "remember me" box in the admin login screen
        array_default($params, 'simulation', false);                     // false, partial, or full. "partial" will sign in, but not really post, full will not sign in and not post at all. False will just sign in and post normally.
        array_default($params, 'redirect'  , isset_get($params['url'])); //

        if(empty($params['url'])){
            throw new bException('wp_signin_admin(): No URL specified', 'not-specified');
        }

        if(strpos($params['url'], 'wp-admin') !== false){
            throw new bException('wp_signin_admin(): The specified URL contains "wp-admin" section. Please specify the URL without this part', 'not-specified');
        }

        if(empty($params['username'])){
            throw new bException('wp_signin_admin(): No username specified', 'not-specified');
        }

        if(empty($params['password'])){
            throw new bException('wp_signin_admin(): No password specified', 'not-specified');
        }

        load_libs('curl');

        $simulation = $params['simulation'];

        if($params['simulation'] == 'partial'){
            $params['simulation'] = false;
        }

        /*
         * First open the admin page to get the cookie required for wp-login.php
         */
        $curl = curl_get(array('url'        => slash($params['url']).'wp-admin/',
                               'simulation' => $params['simulation'],
                               'close'      => false));

        /*
         * Now login
         */
        $curl = curl_get(array('url'        => slash($params['url']).'wp-login.php',
                               'redirect'   => slash($params['url']).'wp-admin/',
                               'curl'       => $curl,
                               'post'       => array('log'         => $params['username'],
                                                     'pwd'         => $params['password'],
                                                     'rememberme'  => ($params['rememberme'] ? 'forever' : ''),
                                                     'wp-submit'   => 'Log In',
                                                     'redirect_to' => slash($params['url']).'wp-admin/',
                                                     'testcookie'  => 1)));

        if($failed = str_from($curl['data'], '<div id="login_error">')){
            /*
             * Oops, login failed
             */
            $failed = str_until($failed, '</div>');

            if(strpos($failed, 'The password you entered for the username') !== false){
                throw new bException('wp_admin_signin(): Signin on site "'.str_log($params['url']).'" failed because the specified password for user "'.$params['username'].'" is incorrect', 'passwordincorrect');
            }

            throw new bException('wp_admin_signin(): Signin on site "'.str_log($params['url']).'" failed with "'.str_log($failed).'"', 'signinfailed');
        }

        if(!$curl['user_id'] = str_cut($curl['data'], '"uid":"', '"')){
            if(!$params['simulation']){
                throw new bException('wp_signin_admin(): Failed to find user id', 'failed');
            }

            $curl['user_id'] = -1;
        }

        $curl['type']       = 'wordpress-admin';
        $curl['url']        = slash($params['url']);
        $curl['baseurl']    = slash($params['url']);
        $curl['simulation'] = $simulation;

        unset($curl['data']);
        return $curl;

    }catch(Exception $e){
        throw new bException('wp_signin_admin(): Failed', $e);
    }
}



/*
 * Submit a new page
 */
function wp_admin_post($params, $force_new = false){
    static $retry;

    try{
        array_params($params);
        array_default($params, 'sleep'  , 15);    // Sleep howmany seconds between retries
        array_default($params, 'retries',  5);    // Retry howmany time on postid failures

        if(empty($params['curl'])){
            throw new bException('wp_admin_post(): No wordpress cURL connection ($params[curl]) specified', 'not-specified');
        }

        if(empty($params['title'])){
            throw new bException('wp_admin_post(): No title specified', 'not-specified');
        }

        if(empty($params['type'])){
            if(empty($params['post_type'])){
                throw new bException('wp_admin_post(): No type specified (typically one of page or post)', 'not-specified');
            }

            $params['type'] = $params['post_type'];
        }

        if(empty($params['user_id'])){
            if(empty($params['curl']['user_id'])){
                throw new bException('wp_admin_post(): No user_id specified', 'not-specified');
            }

            /*
             * Take the user_id from the curl data
             */
            $params['user_id'] = $params['curl']['user_id'];
        }

        array_default($params, 'content', '');
        array_default($params, 'author' , $params['user_id']);

        load_libs('curl');

        /*
         * New document? or update existing document?
         */
        if(empty($params['post_id']) or $force_new){
            $params['curl'] = curl_get(array('url'      => slash($params['curl']['baseurl']).'wp-admin/post-new.php?post_type='.$params['type'],
                                             'redirect' => slash($params['curl']['baseurl']).'wp-admin/',
                                             'curl'     => $params['curl']));

            $keywords = array('post_ID',
                              '_wpnonce',
                              'autosavenonce',
                              'meta-box-order-nonce',
                              'closedpostboxesnonce',
                              'samplepermalinknonce',
                              '_ajax_nonce-add-meta');

            foreach($keywords as $keyword){
                $lower = strtolower($keyword);

                if(!$params[$lower] = str_until(str_from($params['curl']['data'], "input type='hidden' id='".$keyword."' name='".$keyword."' value='"), "' />")){
                    $params[$lower] = str_until(str_from($params['curl']["data"], 'input type="hidden" id="'.$keyword.'" name="'.$keyword.'" value="'), '" />');
                }

                $retval[$lower] = $params[$lower];
            }

            if(empty($params['post_id']) or !is_numeric($params['post_id'])){
                if(empty($params['curl']['simulation'])){
                    throw new bException('wp_admin_post(): Unable to find a valid post id from the wordpress post-new.php page', 'postid');
                }

                $params['post_id'] = -1;
            }
        }

        $date   = new DateTime();
        $retval = $params;

        /*
         * Post the page
         */
        $retval['curl'] = curl_get(array('url'      => slash($params['curl']['baseurl']).'wp-admin/post.php',
                                         'redirect' => slash($params['curl']['baseurl']).'wp-admin/post-new.php?post_type='.$params['type'],
                                         'curl'     => $params['curl'],
                                         'post'     => array('_wpnonce'                  => $params['_wpnonce'],
                                                             '_wp_http_referer'          => slash($params['curl']['url']).'/wp-admin/post-new.php?post_type='.$params['type'],
                                                             'user_ID'                   => $params['user_id'],
                                                             'action'                    => 'editpost',
                                                             'originalaction'            => 'editpost',
                                                             'post_author'               => $params['author'],
                                                             'post_type'                 => $params['type'],
                                                             'original_post_status'      => isset_get($params['original_post_status'], 'auto-draft'),
                                                             'referredby'                => slash($params['curl']['url']).'wp-admin/post-new.php',
                                                             '_wp_original_http_referer' => slash($params['curl']['url']).'wp-admin/post-new.php',
                                                             'auto_draft'                => '0',
                                                             'post_ID'                   => $params['post_id'],
                                                             'autosavenonce'             => $params['autosavenonce'],
                                                             'meta-box-order-nonce'      => $params['meta-box-order-nonce'],
                                                             'closedpostboxesnonce'      => $params['closedpostboxesnonce'],
                                                             'post_title'                => $params['title'],
                                                             'samplepermalinknonce'      => $params['samplepermalinknonce'],
                                                             'content'                   => $params['content'],
                                                             'wp-preview'                => '',
                                                             'hidden_post_status'        => 'draft',
                                                             'post_status'               => 'draft',
                                                             'hidden_post_password'      => '',
                                                             'hidden_post_visibility'    => isset_get($params['hidden_post_visibility'], 'public'),
                                                             'visibility'                => isset_get($params['visibility'], 'public'),
                                                             'post_password'             => '',
                                                             'mm'                        => $date->format('m'),
                                                             'jj'                        => $date->format('d'),
                                                             'aa'                        => $date->format('Y'),
                                                             'hh'                        => $date->format('H'),
                                                             'mn'                        => $date->format('i'),
                                                             'ss'                        => $date->format('s'),
                                                             'hidden_mm'                 => $date->format('m'),
                                                             'cur_mm'                    => $date->format('m'),
                                                             'hidden_jj'                 => $date->format('d'),
                                                             'cur_jj'                    => $date->format('d'),
                                                             'hidden_aa'                 => $date->format('Y'),
                                                             'cur_aa'                    => $date->format('Y'),
                                                             'hidden_hh'                 => $date->format('H'),
                                                             'cur_hh'                    => $date->format('H'),
                                                             'hidden_mn'                 => $date->format('i'),
                                                             'cur_mn'                    => $date->format('i'),
                                                             'original_publish'          => 'Publish',
                                                             'publish'                   => 'Publish',
                                                             'parent_id'                 => isset_get($params['parent_id'], ''),
                                                             'page_template'             => isset_get($params['parent_id'], 'default'),
                                                             'menu_order'                => '0',
                                                             'metakeyinput'              => '',
                                                             'metavalue'                 => '',
                                                             '_ajax_nonce-add-meta'      => $params['_ajax_nonce-add-meta'],
                                                             'advanced_view'             => '1',
                                                             'comment_status'            => isset_get($params['comment_status'], 'open'),
                                                             'ping_status'               => isset_get($params['comment_status'], 'open'),
                                                             'post_name'                 => '',
                                                             'post_author_override'      => $params['author'])));

        /*
         * Get the new page URL
         */
        if(!$retval['post_url'] = str_cut($retval['curl']['data'], '<div id="message" class="updated">', '</div>')){
            /*
             * Looks like the page was not updated successfully
             */
            if(empty($retval['curl']['simulation'])){
load_libs('debug');
show($retval['curl']['data']);
               throw new bException('wp_admin_post(): Failed to find post URL', 'posturl');
            }

            $retval['post_url'] = 'simulation_'.uniqid();

        }else{
            $retval['post_url'] = str_cut($retval['post_url'], '<a href="', '">');
            unset($retval['curl']['data']);
        }

        $retry = 0;
        return $retval;

    }catch(Exception $e){
        if((($e->getCode() == 'postid') or ($e->getCode() == 'posturl')) and (++$retry <= $params['retries'])){
            /*
             * For whatever reason, connection gave HTTP code 0 which probably
             * means that the server died off completely. This again may mean
             * that the server overloaded. Wait for a few seconds, and try again
             * for a limited number of times
             *
             */
            sleep($params['sleep']);
            log_error('wp_admin_post(): Got postid error for post "'.str_log($params['title']).'", retry "'.str_log($retry).'"', 'postid');
            return wp_admin_post($params, $force_new);
        }

        throw new bException('wp_admin_post(): Failed', $e);
    }
}



/*
 * Trash a page
 */
function wp_admin_trash($params){
    try{
        array_params($params);

        if(empty($params['curl'])){
            throw new bException('wp_admin_trash(): No wordpress cURL connection ($params[curl]) specified', 'not-specified');
        }

        if(empty($params['type'])){
            if(empty($params['delete_type'])){
                throw new bException('wp_admin_trash(): No type specified (typically one of page or delete)', 'not-specified');
            }

            $params['type'] = $params['delete_type'];
        }

        if(empty($params['post_id'])){
            throw new bException('wp_admin_trash(): No post_id specified', 'not-specified');
        }

        /*
         * Get the nonce required to do the delete
         * Build up the delete URL
         */
//        $params['curl'] = curl_get(array('url'  => slash($params['curl']['baseurl']).'wp-admin/edit.php?post_type=page',
        $params['curl'] = curl_get(array('url'  => slash($params['curl']['baseurl']).'wp-admin/edit.php',
                                         'curl' => $params['curl']));

        $nonce = str_until(str_from($params['curl']['data'], '_wpnonce" value="'), '"');
        $url   = slash($params['curl']['baseurl']).'wp-admin/edit.php?s=&post_status=all&post_type=page&_wpnonce='.$nonce.'&_wp_http_referer=%2Fwp-admin%2Fedit.php&action=trash&m=0&paged=1';
//        $url   = slash($params['curl']['baseurl']).'wp-admin/edit.php?s=&post_status=all&post_type=page&_wpnonce='.$nonce.'&_wp_http_referer=%2Fwp-admin%2Fedit.php%3Fpost_type%3Dpage&action=trash&m=0&paged=1';

        foreach(array_force($params['post_id']) as $post_id){
            $url .= '&post%5B%5D='.cfi($post_id);
        }

        /*
         * Delete the document
         * Example: http://server.com/wp-admin/edit.php?s=&post_status=all&post_type=page&_wpnonce=60f1e909a9&_wp_http_referer=%2Fwp-admin%2Fedit.php%3Fpost_type%3Dpage&action=trash&m=0&paged=1&post%5B%5D=4630&action2=-1
         */
        $retval['curl'] = curl_get(array('url'  => $url.'&action2=-1',
                                         'curl' => $params['curl']));

        return $retval;

    }catch(Exception $e){
        throw new bException('wp_admin_trash(): Failed', $e);
    }
}



/*
 * Restore a page (undelete)
 */
function wp_admin_restore($params){
    try{
        array_params($params);

        if(empty($params['curl'])){
            throw new bException('wp_admin_restore(): No wordpress cURL connection ($params[curl]) specified', 'not-specified');
        }

        if(empty($params['type'])){
            if(empty($params['delete_type'])){
                throw new bException('wp_admin_restore(): No type specified (typically one of page or delete)', 'not-specified');
            }

            $params['type'] = $params['delete_type'];
        }

        if(empty($params['post_id'])){
            throw new bException('wp_admin_restore(): No post_id specified', 'not-specified');
        }

        /*
         * Get the nonce required to do the delete
         * Build up the delete URL
         */
        $params['curl'] = curl_get(array('url'  => slash($params['curl']['baseurl']).'wp-admin/edit.php?post_status=trash&post_type=page',
                                         'curl' => $params['curl']));

        $nonce = str_until(str_from($params['curl']['data'], '_wpnonce" value="'), '"');
        $url   = slash($params['curl']['baseurl']).'wp-admin/edit.php?s=&post_status=trash&post_type=page&_wpnonce='.$nonce.'&_wp_http_referer=%2Fwp-admin%2Fedit.php%3Fpost_status%3Dtrash%26post_type%3Dpage&action=delete&m=0&paged=1';

        foreach(array_force($params['post_id']) as $post_id){
            $url .= '&post%5B%5D='.cfi($post_id);
        }

        /*
         * Remove the document permanently
         * Example: http://server.com/wp-admin/edit.php?s=&post_status=trash&post_type=page&_wpnonce=61f24a93f9&_wp_http_referer=%2Fwp-admin%2Fedit.php%3Fpost_status%3Dtrash%26post_type%3Dpage&action=delete&m=0&paged=1&post%5B%5D=4633&post%5B%5D=4635&post%5B%5D=4630&action2=-1
         */
        $retval['curl'] = curl_get(array('url'  => $url.'&action2=-1',
                                         'curl' => $params['curl']));

        return $retval;

    }catch(Exception $e){
        throw new bException('wp_admin_restore(): Failed', $e);
    }
}



/*
 * Remove a page permanently (erase)
 */
function wp_admin_remove_permanently($params){
    try{
        array_params($params);

        if(empty($params['curl'])){
            throw new bException('wp_admin_remove_permanently(): No wordpress cURL connection ($params[curl]) specified', 'not-specified');
        }

        if(empty($params['type'])){
            if(empty($params['delete_type'])){
                throw new bException('wp_admin_remove_permanently(): No type specified (typically one of page or delete)', 'not-specified');
            }

            $params['type'] = $params['delete_type'];
        }

        if(empty($params['post_id'])){
            throw new bException('wp_admin_remove_permanently(): No post_id specified', 'not-specified');
        }

        /*
         * Get the nonce required to do the delete
         * Build up the delete URL
         */
        $params['curl'] = curl_get(array('url'  => slash($params['curl']['baseurl']).'wp-admin/edit.php?post_status=trash&post_type=page',
                                         'curl' => $params['curl']));

        $nonce = str_until(str_from($params['curl']['data'], '_wpnonce" value="'), '"');
        $url   = slash($params['curl']['baseurl']).'wp-admin/edit.php?s=&post_status=trash&post_type=page&_wpnonce='.$nonce.'&_wp_http_referer=%2Fwp-admin%2Fedit.php%3Fpost_status%3Dtrash%26post_type%3Dpage&action=untrash&m=0&paged=1';

        foreach(array_force($params['post_id']) as $post_id){
            $url .= '&post%5B%5D='.cfi($post_id);
        }

        /*
         * Remove the document permanently
         * Example: http://server.com/wp-admin/edit.php?s=&post_status=trash&post_type=page&_wpnonce=61f24a93f9&_wp_http_referer=%2Fwp-admin%2Fedit.php%3Fpost_status%3Dtrash%26post_type%3Dpage&action=untrash&m=0&paged=1&post%5B%5D=4637&post%5B%5D=4639&post%5B%5D=4641&action2=-1
         */
        $retval['curl'] = curl_get(array('url'  => $url.'&action2=-1',
                                         'curl' => $params['curl']));

        return $retval;

    }catch(Exception $e){
        throw new bException('wp_admin_remove_permanently(): Failed', $e);
    }
}



/*
 * Get a new page or post
 */
function wp_admin_get($post_id, $curl){
    try{
        array_params($params);

        if(!is_array($curl)){
            throw new bException('wp_admin_get(): No wordpress cURL connection ($params[curl]) specified', 'not-specified');
        }

        if(empty($post_id)){
            throw new bException('wp_admin_get(): No post_id specified', 'not-specified');
        }

        if(!is_numeric($post_id)){
            throw new bException('wp_admin_get(): Invalid post_id specified', 'invalid');
        }

        load_libs('curl');

        /*
         * New document? or update existing document?
         */
        $retval                     = curl_get(array('url'  => slash($curl['baseurl']).'wp-admin/post.php?post='.$post_id.'&action=edit',
                                                     'curl' => $curl));

        $retval['data']             = array('raw' => $retval['data']);
        $retval['data']['content']  = html_entity_decode(str_until(str_from(str_from($retval['data']['raw'], 'textarea class="wp-editor-area"'), '>'), '</textarea>'));
        $retval['data']['title']    = str_rfrom(str_until($retval['data']['raw'], '" id="title"'), 'value="');
        $retval['data']['curl']     = $curl;

        $keywords = array('post_ID',
                          'post_type',
                          'post_author',
                          '_wpnonce',
                          'autosavenonce',
                          'meta-box-order-nonce',
                          'closedpostboxesnonce',
                          'samplepermalinknonce',
                          '_ajax_nonce-add-meta');

        foreach($keywords as $keyword){
            $lower = strtolower($keyword);

            if(!$retval['data'][$lower] = str_until(str_from($retval['data']['raw'], "input type='hidden' id='".$keyword."' name='".$keyword."' value='"), "' />")){
                $retval['data'][$lower] = str_until(str_from($retval['data']['raw'], 'input type="hidden" id="'.$keyword.'" name="'.$keyword.'" value="'), '" />');
            }

            $retval['data'][$lower] = $retval['data'][$lower];
        }

        if(empty($retval['data']['post_id']) or !is_numeric($retval['data']['post_id'])){
            if(empty($retval['curl']['simulation'])){
                throw new bException('wp_admin_post(): Unable to find a valid post id from the wordpress post-new.php page', 'postid');
            }

            $retval['data']['post_id'] = -1;
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('wp_admin_get(): Failed', $e);
    }
}



/*
 * Make a post using the xmlrpc interface
 */
function wp_xmlrpc_post($params){
    try{
        array_params($params);
        array_default($params, 'encoding'  , 'UTF-8');
        array_default($params, 'keywords'  , '');
        array_default($params, 'categories', '');

        if(empty($params['url'])){
            throw new bException('wp_xmlrpc_post(): No URL specified', 'not-specified');
        }

        if(strpos($params['url'], 'wp-admin') !== false){
            throw new bException('wp_xmlrpc_post(): The specified URL contains "wp-admin" section. Please specify the URL without this part', 'not-specified');
        }

        if(empty($params['username'])){
            throw new bException('wp_xmlrpc_post(): No username specified', 'not-specified');
        }

        if(empty($params['password'])){
            throw new bException('wp_xmlrpc_post(): No password specified', 'not-specified');
        }

        if(empty($params['title'])){
            throw new bException('wp_xmlrpc_post(): No title specified', 'not-specified');
        }

        if(empty($params['content'])){
            throw new bException('wp_xmlrpc_post(): No content specified', 'not-specified');
        }

        if(empty($params['type'])){
            throw new bException('wp_xmlrpc_post(): No type specified (typically one of page or post)', 'not-specified');
        }

        if(empty($params['author'])){
            throw new bException('wp_xmlrpc_post(): No author specified', 'not-specified');
        }

        if(empty($params['status'])){
            throw new bException('wp_xmlrpc_post(): No status specified', 'not-specified');
        }

        load_libs('curl');

        $params['title']    = htmlentities($params['title']   , ENT_NOQUOTES, $params['encoding']);

        $content = array('post_author'       => $params['author'],
                         'post_status'       => $params['status'],
                         'title'             => $params['title'],
                         'post_content'      => $params['content'],
                         'post_type'         => $params['type'],
                         'mt_allow_comments' => (isset_get($params['allow_comments']) ? 1 : 0),
                         'mt_allow_pings'    => (isset_get($params['allow_pings'])    ? 1 : 0));

        if(isset_get($params['keywords'])){
            $content['mt_keywords'] = htmlentities($params['keywords'], ENT_NOQUOTES, $params['encoding']);
        }

        if(isset_get($params['categories'])){
            $content['categories'] = array_force($params['categories']);
        }

        if(isset_get($params['parents_id'])){
            $content['parents_id']  = (integer) $params['parents_id'];
            $content['post_parent'] = (integer) $params['parents_id'];
        }

        $rpc     = array(0, $params['username'], $params['password'], $content, true);
        $request = xmlrpc_encode_request('metaWeblog.newPost', $rpc);

        return curl_get(array('url'  => slash($params['url']).'xmlrpc.php',
                              'post' => $request));

    }catch(Exception $e){
        throw new bException('wp_xmlrpc_post(): Failed', $e);
    }
}



/*
 * Wrapper for wp_admin_trash() since under base we usually use delete, undelete, and erase
 */
function wp_admin_delete($params){
    try{
        return wp_admin_trash($params);

    }catch(Exception $e){
        throw new bException('wp_admin_delete(): Failed', $e);
    }
}



/*
 * Wrapper for wp_admin_restore() since under base we usually use delete, undelete, and erase
 */
function wp_admin_undelete($params){
    try{
        return wp_admin_restore($params);

    }catch(Exception $e){
        throw new bException('wp_admin_undelete(): Failed', $e);
    }
}



/*
 * Wrapper for wp_admin_remove_permanently() since under base we usually use delete, undelete, and erase
 */
function wp_admin_erase($params){
    try{
        return wp_admin_remove_permanently($params);

    }catch(Exception $e){
        throw new bException('wp_admin_erase(): Failed', $e);
    }
}
?>
