<?php
/*
 * Ingiga toolkit custom admin library template
 *
 * This library can be used to add project specific functionalities
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 */
//showdie(SCRIPT);



load_libs('atlant');
atlant_force_my_profile('HERE BE THE HASH OF THE DEFAULT PASSWORD');



/*
 * Custom page loader. Will add header and footer to the given HTML, then send
 * HTTP headers, and then HTML to client
 */
function c_page($params, $meta, $html){
    try{
        return atlant_page($params, $meta, $html);

    }catch(Exception $e){
        throw new bException('c_page(): Failed', $e);
    }
}



/*
 * Create and return the page header
 */
function c_html_header($params = null, $meta = null, $links = null){
    try{
        return atlant_html_header($params, $meta, $links);

    }catch(Exception $e){
        throw new bException('c_html_header(): Failed', $e);
    }
}



/*
 * Create and return the page header
 */
function c_page_header($params){
    try{
        return atlant_page_header($params);

    }catch(Exception $e){
        throw new bException('c_page_header(): Failed', $e);
    }
}



/*
 * Create and return the page footer
 */
function c_html_footer($params){
    try{
        return atlant_html_footer($params);

    }catch(Exception $e){
        throw new bException('c_html_footer(): Failed', $e);
    }
}



/*
 *
 */
function c_menu(){
    try{
        $html = '   <li>
                        <a href="'.domain('/').'"><span class="fa fa-dashboard"></span> <span class="xn-text">'.tr('Dashboard').'</span></a>
                    </li>
                    <li class="xn-openable">
                        <a href="#"><span class="fa fa-user"></span> <span class="xn-text">'.tr('My Account').'</span></a>
                        <ul>
                            <li><a href="'.domain('/my-profile.html').'"><span class="fa fa-pencil"></span> <span class="xn-text">'.tr('My Profile').'</span></a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="https://google.com" target="_blank"><span class="fa fa-book"></span> <span class="xn-text">'.tr('External link').'</span></a>
                    </li>
                    '.return_with_rights('blogs', '
                    <li>
                        <a href="'.domain('/blogs.html').'"><span class="fa fa-book"></span> <span class="xn-text">'.tr('Blogs').'</span></a>
                    </li>').'
                    '.return_with_rights('admin', '
                    <li class="xn-openable">
                        <a href="#"><span class="fa fa-cog"></span> <span class="xn-text">'.tr('System').'</span></a>
                        <ul>
                            '.return_with_rights('accounts', '
                            <li class="xn-openable">
                                <a href="#"><span class="fa fa-users"></span> <span class="xn-text">'.tr('Accounts').'</span></a>
                                <ul>
                                    <li><a href="'.domain('/users.html').'"><span class="fa fa-users"></span> <span class="xn-text">'.tr('Users').'</span></a></li>
                                    <li><a href="'.domain('/roles.html').'"><span class="fa fa-cog"></span> <span class="xn-text">'.tr('Roles').'</span></a></li>
                                    <li><a href="'.domain('/rights.html').'"><span class="fa fa-lock"></span> <span class="xn-text">'.tr('Rights').'</span></a></li>
                                    <li><a href="'.domain('/user-switches.html').'"><span class="fa fa-exchange"></span> <span class="xn-text">'.tr('User switches').'</span></a></li>
                                </ul>
                            </li>').'
                            <li><a href="'.domain('/configuration.html').'"><span class="fa fa-cogs"></span> <span class="xn-text">'.tr('Configuration').'</span></a></li>
                            <li><a href="'.domain('/activity-log.html').'"><span class="fa fa-edit"></span> <span class="xn-text">'.tr('Activity log').'</span></a></li>
                            <li><a href="'.domain('/statistics.html').'"><span class="fa fa-line-chart"></span> <span class="xn-text">'.tr('Statistics').'</span></a></li>
                            <li><a href="'.domain('/ip-locking.html').'"><span class="fa fa-lock"></span> <span class="xn-text">'.tr('IP locking').'</span></a></li>
                            '.return_with_rights('cache', '
                                <li class="xn-openable">
                                    <a href="#"><span class="fa fa-database"></span> <span class="xn-text">'.tr('Cache').'</span></a>
                                    <ul>
                                        <li><a href="'.domain('/cache-manager.html').'"><span class="fa fa-database"></span> <span class="xn-text">'.tr('Manager').'</span></a></li>
                                        <li><a href="'.domain('/cache-viewer.html').'"><span class="fa fa-database"></span> <span class="xn-text">'.tr('Viewer').'</span></a></li>
                                    </ul>
                                </li>').'
                        </ul>
                    </li>');

        return $html;

    }catch(Exception $e){
        throw new bException('c_menu(): Failed', $e);
    }
}
?>
