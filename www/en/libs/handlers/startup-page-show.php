<?php
/*
 * Implementation of page_show()
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package startup
 */
global $_CONFIG, $core;

try{
    array_params($params, 'message');
    array_default($params, 'exists', false);

    if($get){
        if(!is_array($get)){
            throw new bException(tr('page_show(): Specified $get MUST be an array, but is an ":type"', array(':type' => gettype($get))), 'invalid');
        }

        $_GET = $get;
    }

    if(defined('LANGUAGE')){
        $language = LANGUAGE;

    }else{
        $language = 'en';
    }

    $params['page'] = $pagename;

    if(is_numeric($pagename)){
        /*
         * This is a system page, HTTP code. Use the page code as http code as well
         */
        $core->register['http_code'] = $pagename;
    }

    if(!empty($core->callType('ajax'))){
        if($params['exists']){
            return file_exists(ROOT.'www/'.$language.'/ajax/'.$pagename.'.php');
        }

        /*
         * Execute ajax page
         */
        return include(ROOT.'www/'.$language.'/ajax/'.$pagename.'.php');

    }elseif(!empty($core->callType('api'))){
        if($params['exists']){
            return file_exists(ROOT.'www/api/'.$pagename.'.php');
        }

        /*
         * Execute ajax page
         */
        return include(ROOT.'www/api/'.$pagename.'.php');

    }elseif(!empty($core->callType('admin'))){
        $prefix = 'admin/';

    }else{
        $prefix = '';
    }

    if($params['exists']){
        return file_exists(ROOT.'www/'.$language.'/'.$prefix.$pagename.'.php');
    }

    $result = include(ROOT.'www/'.$language.'/'.$prefix.$pagename.'.php');

    if(isset_get($params['return'])){
        return $result;
    }

    die();

}catch(Exception $e){
    throw new bException(tr('page_show(): Failed to show page ":page"', array(':page' => $pagename)), $e);
}
?>