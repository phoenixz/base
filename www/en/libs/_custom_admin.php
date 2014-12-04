<?php
/*
 * Empty custom Admin library
 *
 * This are functions only used for the admin section
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>, Johan Geuze
 */



/*
 * Always automatically load the admin library
 */
global $_CONFIG;
include(ROOT.'config/admin.php');



/*
 *
 */
function admin_page($html, $params = '', $flash = null, $flash_type = null) {
    return admin_start($params).$html.admin_end();
}


/*
 *
 */
function admin_start($params = '', $flash = null, $flash_type = null) {
    global $_CONFIG;

    try{
        //if(!has_rights('admin')){
        //    /*
        //     * This is not a user or not an admin user. either way, don't show admin menu
        //     */
        //    load_libs('user');
        //    throw new bException('admin_menu($params): User "'.str_log(user_name(isset_get($_SESSION['user']))).'" is not an admin', 'notadmin');
        //}

        array_params($params, 'title');
        array_default($params, 'title', tr('Admin section'));

        html_load_js('base/base,base/flash');

        $meta   = array('keywords'    => tr('Admin'),
                        'description' => tr('This is the admin section!'));

        return html_header($params, $meta).'<body>
    <div class="top">
        <a href="/index.php" class="toplogo" target="_blank">
            <span class="center logo"></span>
            <img class="logo" src="'.domain('/pub/img/logo.png').'" alt="Admin section logo" />
        </a>
        <h1 class="toptitle">'.$params['title'].'</h1>
        <span class="topuser">'.isset_get($_SESSION['user']['name'], '').'</span>
    </div>
    <div>
        <div class="menu">
            '.admin_menu($params).'
        </div>
        <div class="content">'.
            html_flash($flash, $flash_type);

////                <script type="text/javascript" src="/pub/js/base/admin.js"></script>
//        return '<html>
//            <head>
//                <title>'.$_SERVER['SERVER_NAME'].' '.$title.'</title>
//                <link rel="stylesheet" type="text/css" href="/pub/css/'.(SUBENVIRONMENT ? SUBENVIRONMENT.'/' : '').'admin.css"/>
//                <script type="text/javascript" src="/pub/js/base/jquery1.js"></script>
//                <script type="text/javascript" src="/pub/js/base/base.js"></script>
//                <script type="text/javascript" src="/pub/js/base/flash.js"></script>
//            </head>
//            <body>
//            <div class="top">
//                <a href="index.php" class="toplogo"></a>
//                <div class="topuser">'.isset_get($_SESSION['user']['name'], '').'</div>
//                <div class="toptitle">'.$title.'</div>
//            </div>
//            <div>
//                <div class="menu">
//                    '.admin_menu($params).'
//                </div>
//                <div class="content">'.
//                    html_flash($flash, $flash_type);

    }catch(Exception $e){
        throw new bException('admin_start(): Failed', $e);
    }
}



/*
 *
 */
function admin_end() {
    return '</div></div></body></html>';
}



/*
 *
 */
function admin_menu($params) {
    global $_CONFIG;

    if(!has_rights('admin')){
        return '';
    }

    load_libs('user');

    array_default($params, 'script', SCRIPT.'.php');

    $html = '<ul>';

    foreach($_CONFIG['admin']['pages'] as $right => $menu) {
        if(has_rights($right)) {
            $html .= '<li'.(($menu['script'] == $params['script']) ? ' class="selected"' : '').'><a'.(empty($menu['target']) ? '' : ' target="'.$menu['target'].'"').' href="'.$menu['script'].'">'.$menu['title'].'</a></li>';
        }

        if(!empty($menu['subs'])){
            foreach($menu['subs'] as $right => $menu) {
                if(has_rights($right)) {
                    $html .= '<li class="sub'.(($menu['script'] == $params['script']) ? ' selected' : '').'"><a'.(empty($menu['target']) ? '' : ' target="'.$menu['target'].'"').' href="'.$menu['script'].'">'.$menu['title'].'</a></li>';
                }
            }
        }
    }

    return $html.'<li><a href="/admin/signout.php">'.tr('Sign out').'</a></li></ul>';
}
?>
