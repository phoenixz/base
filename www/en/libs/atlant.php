<?php
/*
 * Atlant template library
 *
 * This library contains functions specific to the atlant template
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */



if(PLATFORM_HTTP){
    html_load_js('plugins/jquery/jquery,plugins/jquery/jquery-ui,plugins/bootstrap/bootstrap,base/base,base/strings');
}



/*
 *
 */
function atlant_page($params, $meta, $html){
    try{
        array_params($params);
        array_default($params, 'cache_namespace', 'htmlpage');
        array_default($params, 'cache_key'      , null);
        array_default($params, 'links'          , array());

        $params['links'][] = array('rel'  => 'icon',
                                   'href' => '/pub/images/favicon.ico');

        if(empty($_SESSION['user']['id'])){
            $params['page_type'] = 'simple';
        }

        $page = c_html_header($params, $meta).$html.c_html_footer($params);

        http_headers($params, strlen($page));

        return cache_write($page, $params['cache_key'], $params['cache_namespace']);

    }catch(Exception $e){
        throw new bException('atlant_page(): Failed', $e);
    }
}



/*
 *
 */
function atlant_html_header($params = null, $meta = null, $links = null){
    global $_CONFIG;

    try{
        array_params($params);
        array_default($params, 'html'       , '<html lang="en">');
        array_default($params, 'default_css', true);
        array_default($params, 'default_js' , true);
        array_default($params, 'carousel'   , false);

        array_params($meta);
        array_default($meta, 'X-UA-Compatible', 'IE=edge');

        array_params($links);
        array_default($links, 'icon', 'favicon.ico');

        if($params['default_css']){
            /*
             * Manually load bootstrap file MINIFIED as the theme-default CSS
             * will include minified version of bootstrap using an @include,
             * causing bootstrap.min.css to be loaded AFTER atlant. Forcibly
             * loading it here will force it to be loaded before atlant.css
             */
            html_load_css('bootstrap/bootstrap.min,theme-default,atlant,style');
        }

        if($params['default_js']){
            html_load_js('plugins/icheck/icheck,plugins/mcustomscrollbar/jquery.mCustomScrollbar');
        }

        if($params['carousel']){
            html_load_js('plugins/owl/owl.carousel');
        }

//html_load_js('plugins/scrolltotop/scrolltopcontrol');
//html_load_js('plugins/morris/raphael-min');
//html_load_js('plugins/morris/morris');
//html_load_js('plugins/rickshaw/d3.v3');
//html_load_js('plugins/rickshaw/rickshaw');
//html_load_js('plugins/jvectormap/jquery-jvectormap-1.2.2');
//html_load_js('plugins/jvectormap/jquery-jvectormap-world-mill-en');
//html_load_js('plugins/bootstrap/bootstrap-datepicker');
//html_load_js('plugins/moment');
//html_load_js('plugins/daterangepicker/daterangepicker');
//html_load_js('settings');
//html_load_js('plugins');
html_load_js('actions');
//html_load_js('demo_dashboard');


//        <link rel="icon" href="favicon.ico" type="image/x-icon" />

        /*
         * Load required javascript libraries
         * Load optional javascript libraries
         */
        $js  = '';

        /*
         * Required (at end)
         */
        $js .= '';

        html_load_js($js);

        $html = c_page_header($params);

        return html_header($params, $meta).$html;

    }catch(Exception $e){
        throw new bException('atlant_html_header(): Failed', $e);
    }
}



/*
 * Create and return the page header
 */
function atlant_page_header($params){
    global $_CONFIG;

    try{
        array_params($params);
        array_default($params, 'breadcrumbs', array());
        array_default($params, 'page_type'  , 'full');

        switch($params['page_type']){
            case 'simple':
                $html = ''.html_flash('system');
                break;

            case 'full':
                $html = '   <div class="page-container">
                                <div class="page-sidebar">
                                    <ul class="x-navigation">
                                        <li class="xn-logo">
                                            <a href="index.html">'.$_SESSION['domain'].'</a>
                                            <a href="#" class="x-navigation-control"></a>
                                        </li>
                                        <li class="xn-profile">';

                if(empty($_SESSION['user']['id'])){
                    $html .= '              <a href="#" class="profile-mini">
                                                <img src="/pub/img/default-user.png" alt="" src="">
                                            </a>
                                            <div class="profile">
                                                <div class="profile-image">
                                                    <img src="/pub/img/default-user.png" alt="'.tr('Guest user').'">
                                                </div>
                                                <div class="profile-data">
                                                    <div class="profile-data-name">'.tr('Guest user').'</div>
                                                    <div class="profile-data-title">'.tr('Welcome to :domain!', array(':domain' => $_SESSION['domain'])).'</div>
                                                </div>
                                            </div>';

                }else{
                    $html .= '              <a href="'.domain('/my-profile.html').'" class="profile-mini">
                                                <img src="'.$_SESSION['user']['avatar'].'" ="'.name($_SESSION['user']).'">
                                            </a>
                                            <div class="profile">
                                                <div class="profile-image">
                                                    <a href="'.domain('/my-profile.html').'">
                                                        <img src="'.$_SESSION['user']['avatar'].'" alt="'.name($_SESSION['user']).'">
                                                    </a>
                                                </div>
                                                <div class="profile-data">
                                                    <div class="profile-data-name">'.name($_SESSION['user']).'</div>
                                                    <div class="profile-data-title">'.isset_get($_SESSION['user']['title']).'</div>
                                                </div>
                                                <div class="profile-controls">
                                                    <a href="'.domain('/my-tasks.html').'" class="profile-control-left"><span class="fa fa-tasks"></span></a>
                                                    <a href="'.domain('/my-messages.html').'" class="profile-control-right"><span class="fa fa-envelope"></span></a>
                                                </div>
                                            </div>';
                }


                $html .= '              </li>
                                        <li class="xn-title">'.tr('Navigation').'</li>
                                        '.c_menu();

                //$html .= '                      <li class="xn-openable active">
                //                                    <a href="#"><span class="fa fa-dashboard"></span> <span class="xn-text">Dashboards</span></a>
                //                                    <ul>
                //                                        <li class="active"><a href="index.html"><span class="xn-text">Dashboard 1</span></a></li>
                //                                        <li><a href="dashboard.html"><span class="xn-text">Dashboard 2</span></a><div class="informer informer-danger">New!</div></li>
                //                                        <li><a href="dashboard-dark.html"><span class="xn-text">Dashboard 3</span></a><div class="informer informer-danger">New!</div></li>
                //                                    </ul>
                //                                </li>
                //                                <li class="xn-openable">
                //                                    <a href="#"><span class="fa fa-files-o"></span> <span class="xn-text">Pages</span></a>
                //                                    <ul>
                //                                        <li><a href="pages-gallery.html"><span class="fa fa-image"></span> Gallery</a></li>
                //                                        <li><a href="pages-invoice.html"><span class="fa fa-dollar"></span> Invoice</a></li>
                //                                        <li><a href="pages-edit-profile.html"><span class="fa fa-wrench"></span> Edit Profile</a></li>
                //                                        <li><a href="pages-profile.html"><span class="fa fa-user"></span> Profile</a></li>
                //                                        <li><a href="pages-address-book.html"><span class="fa fa-users"></span> Address Book</a></li>
                //                                        <li class="xn-openable">
                //                                            <a href="#"><span class="fa fa-clock-o"></span> Timeline</a>
                //                                            <ul>
                //                                                <li><a href="pages-timeline.html"><span class="fa fa-align-center"></span> Default</a></li>
                //                                                <li><a href="pages-timeline-simple.html"><span class="fa fa-align-justify"></span> Full Width</a></li>
                //                                            </ul>
                //                                        </li>
                //                                        <li class="xn-openable">
                //                                            <a href="#"><span class="fa fa-envelope"></span> Mailbox</a>
                //                                            <ul>
                //                                                <li><a href="pages-mailbox-inbox.html"><span class="fa fa-inbox"></span> Inbox</a></li>
                //                                                <li><a href="pages-mailbox-message.html"><span class="fa fa-file-text"></span> Message</a></li>
                //                                                <li><a href="pages-mailbox-compose.html"><span class="fa fa-pencil"></span> Compose</a></li>
                //                                            </ul>
                //                                        </li>
                //                                        <li><a href="pages-messages.html"><span class="fa fa-comments"></span> Messages</a></li>
                //                                        <li><a href="pages-calendar.html"><span class="fa fa-calendar"></span> Calendar</a></li>
                //                                        <li><a href="pages-tasks.html"><span class="fa fa-edit"></span> Tasks</a></li>
                //                                        <li><a href="pages-content-table.html"><span class="fa fa-columns"></span> Content Table</a></li>
                //                                        <li><a href="pages-faq.html"><span class="fa fa-question-circle"></span> FAQ</a></li>
                //                                        <li><a href="pages-search.html"><span class="fa fa-search"></span> Search</a></li>
                //                                        <li class="xn-openable">
                //                                            <a href="#"><span class="fa fa-file"></span> Blog</a>
                //
                //                                            <ul>
                //                                                <li><a href="pages-blog-list.html"><span class="fa fa-copy"></span> List of Posts</a></li>
                //                                                <li><a href="pages-blog-post.html"><span class="fa fa-file-o"></span>Single Post</a></li>
                //                                            </ul>
                //                                        </li>
                //                                        <li><a href="pages-lock-screen.html"><span class="fa fa-lock"></span> Lock Screen</a></li>
                //                                        <li class="xn-openable">
                //                                            <a href="#"><span class="fa fa-sign-in"></span> Login</a>
                //                                            <ul>
                //                                                <li><a href="pages-login.html">Login v1</a></li>
                //                                                <li><a href="pages-login-v2.html">Login v2</a></li>
                //                                                <li><a href="pages-login-inside.html">Login v2 Inside</a></li>
                //                                                <li><a href="pages-login-website.html">Website Login</a></li>
                //                                                <li><a href="pages-login-website-light.html"> Website Login Light</a></li>
                //                                            </ul>
                //                                        </li>
                //                                        <li class="xn-openable">
                //                                            <a href="#"><span class="fa fa-plus"></span> Registration</a>
                //                                            <ul>
                //                                                <li><a href="pages-registration.html">Default</a></li>
                //                                                <li><a href="pages-registration-login.html">With Login</a></li>
                //                                            </ul>
                //                                        </li>
                //                                        <li><a href="pages-forgot-password.html"><span class="fa fa-question"></span> Forgot Password</a></li>
                //                                        <li class="xn-openable">
                //                                            <a href="#"><span class="fa fa-warning"></span> Error Pages</a>
                //                                            <ul>
                //                                                <li><a href="pages-error-404.html">Error 404 Sample 1</a></li>
                //                                                <li><a href="pages-error-404-2.html">Error 404 Sample 2</a></li>
                //                                                <li><a href="pages-error-500.html"> Error 500</a></li>
                //                                            </ul>
                //                                        </li>
                //                                    </ul>
                //                                </li>
                //                                <li class="xn-openable">
                //                                    <a href="#"><span class="fa fa-file-text-o"></span> <span class="xn-text">Layouts</span></a>
                //                                    <ul>
                //                                        <li><a href="layout-boxed.html">Boxed</a></li>
                //                                        <li><a href="layout-nav-toggled.html">Navigation Toggled</a></li>
                //                                        <li><a href="layout-nav-toggled-hover.html">Nav Toggled (Hover)</a></li>
                //                                        <li><a href="layout-nav-toggled-item-hover.html">Nav Toggled (Item Hover)</a></li>
                //                                        <li><a href="layout-nav-top.html">Navigation Top</a></li>
                //                                        <li><a href="layout-nav-right.html">Navigation Right</a></li>
                //                                        <li><a href="layout-nav-top-fixed.html">Top Navigation Fixed</a></li>
                //                                        <li><a href="layout-nav-custom.html">Custom Navigation</a></li>
                //                                        <li><a href="layout-nav-top-custom.html">Custom Top Navigation</a></li>
                //                                        <li><a href="layout-frame-left.html">Frame Left Column</a></li>
                //                                        <li><a href="layout-frame-right.html">Frame Right Column</a></li>
                //                                        <li><a href="layout-search-left.html">Search Left Side</a></li>
                //                                        <li><a href="layout-page-sidebar.html">Page Sidebar</a></li>
                //                                        <li><a href="layout-page-loading.html">Page Loading</a></li>
                //                                        <li><a href="layout-rtl.html">Layout RTL</a></li>
                //                                        <li><a href="layout-tabbed.html">Page Tabbed</a></li>
                //                                        <li><a href="layout-custom-header.html">Custom Header</a></li>
                //                                        <li><a href="layout-adaptive-panels.html">Adaptive Panels</a></li>
                //                                        <li><a href="blank.html">Blank Page</a></li>
                //                                    </ul>
                //                                </li>
                //                                <li class="xn-title">Components</li>
                //                                <li class="xn-openable">
                //                                    <a href="#"><span class="fa fa-cogs"></span> <span class="xn-text">UI Kits</span></a>
                //                                    <ul>
                //                                        <li><a href="ui-widgets.html"><span class="fa fa-heart"></span> Widgets</a></li>
                //                                        <li><a href="ui-elements.html"><span class="fa fa-cogs"></span> Elements</a></li>
                //                                        <li><a href="ui-buttons.html"><span class="fa fa-square-o"></span> Buttons</a></li>
                //                                        <li><a href="ui-panels.html"><span class="fa fa-pencil-square-o"></span> Panels</a></li>
                //                                        <li><a href="ui-icons.html"><span class="fa fa-magic"></span> Icons</a><div class="informer informer-warning">+679</div></li>
                //                                        <li><a href="ui-typography.html"><span class="fa fa-pencil"></span> Typography</a></li>
                //                                        <li><a href="ui-portlet.html"><span class="fa fa-th"></span> Portlet</a></li>
                //                                        <li><a href="ui-sliders.html"><span class="fa fa-arrows-h"></span> Sliders</a></li>
                //                                        <li><a href="ui-alerts-popups.html"><span class="fa fa-warning"></span> Alerts & Popups</a></li>
                //                                        <li><a href="ui-lists.html"><span class="fa fa-list-ul"></span> Lists</a></li>
                //                                        <li><a href="ui-tour.html"><span class="fa fa-random"></span> Tour</a></li>
                //                                        <li><a href="ui-nestable.html"><span class="fa fa-sitemap"></span> Nestable List</a></li>
                //                                        <li><a href="ui-autocomplete.html"><span class="fa fa-search-plus"></span> Autocomplete</a></li>
                //                                        <li><a href="ui-slide-menu.html"><span class="fa fa-angle-right"></span> Slide Menu</a></li>
                //                                    </ul>
                //                                </li>
                //                                <li class="xn-openable">
                //                                    <a href="#"><span class="fa fa-pencil"></span> <span class="xn-text">Forms</span></a>
                //                                    <ul>
                //                                        <li class="xn-openable">
                //                                            <a href="form-layouts-two-column.html"><span class="fa fa-tasks"></span> Form Layouts</a>
                //                                            <ul>
                //                                                <li><a href="form-layouts-one-column.html"><span class="fa fa-align-justify"></span> One Column</a></li>
                //                                                <li><a href="form-layouts-two-column.html"><span class="fa fa-th-large"></span> Two Column</a></li>
                //                                                <li><a href="form-layouts-tabbed.html"><span class="fa fa-table"></span> Tabbed</a></li>
                //                                                <li><a href="form-layouts-separated.html"><span class="fa fa-th-list"></span> Separated Rows</a></li>
                //                                            </ul>
                //                                        </li>
                //                                        <li><a href="form-elements.html"><span class="fa fa-file-text-o"></span> Elements</a><div class="informer informer-danger">New!</div></li>
                //                                        <li><a href="form-validation.html"><span class="fa fa-list-alt"></span> Validation</a></li>
                //                                        <li><a href="form-wizards.html"><span class="fa fa-arrow-right"></span> Wizards</a></li>
                //                                        <li><a href="form-editors.html"><span class="fa fa-text-width"></span> WYSIWYG Editors</a></li>
                //                                        <li><a href="form-file-handling.html"><span class="fa fa-floppy-o"></span> File Handling</a></li>
                //                                    </ul>
                //                                </li>
                //                                <li class="xn-openable">
                //                                    <a href="tables.html"><span class="fa fa-table"></span> <span class="xn-text">Tables</span></a>
                //                                    <ul>
                //                                        <li><a href="table-basic.html"><span class="fa fa-align-justify"></span> Basic</a></li>
                //                                        <li><a href="table-datatables.html"><span class="fa fa-sort-alpha-desc"></span> Data Tables</a></li>
                //                                        <li><a href="table-export.html"><span class="fa fa-download"></span> Export Tables</a></li>
                //                                    </ul>
                //                                </li>
                //                                <li class="xn-openable">
                //                                    <a href="#"><span class="fa fa-bar-chart-o"></span> <span class="xn-text">Charts</span></a>
                //                                    <ul>
                //                                        <li><a href="charts-morris.html">Morris</a></li>
                //                                        <li><a href="charts-nvd3.html">NVD3</a></li>
                //                                        <li><a href="charts-rickshaw.html">Rickshaw</a></li>
                //                                        <li><a href="charts-other.html">Other</a></li>
                //                                    </ul>
                //                                </li>
                //                                <li>
                //                                    <a href="maps.html"><span class="fa fa-map-marker"></span> <span class="xn-text">Maps</span></a>
                //                                </li>
                //                                <li class="xn-openable">
                //                                    <a href="#"><span class="fa fa-sitemap"></span> <span class="xn-text">Navigation Levels</span></a>
                //                                    <ul>
                //                                        <li class="xn-openable">
                //                                            <a href="#">Second Level</a>
                //                                            <ul>
                //                                                <li class="xn-openable">
                //                                                    <a href="#">Third Level</a>
                //                                                    <ul>
                //                                                        <li class="xn-openable">
                //                                                            <a href="#">Fourth Level</a>
                //                                                            <ul>
                //                                                                <li><a href="#">Fifth Level</a></li>
                //                                                            </ul>
                //                                                        </li>
                //                                                    </ul>
                //                                                </li>
                //                                            </ul>
                //                                        </li>
                //                                    </ul>
                //                                </li>';

                $html .= '          </ul>
                                    <!-- END X-NAVIGATION -->
                                </div>
                                <!-- END PAGE SIDEBAR -->
                                <!-- PAGE CONTENT -->
                                <div class="page-content">
                                    <!-- START X-NAVIGATION VERTICAL -->
                                    <ul class="x-navigation x-navigation-horizontal x-navigation-panel">';

                /*
                 * Menu toggle button
                 */
                $html .= '              <li class="xn-icon-button">
                                            <a href="#" class="x-navigation-minimize"><span class="fa fa-dedent"></span></a>
                                        </li>';

                if(!empty($_SESSION['user']['id'])){
                    /*
                     * Search bar
                     */
                    $html .= '          <li class="xn-search">
                                            <form role="form">
                                                <input type="text" name="search" placeholder="'.tr('Search...').'">
                                            </form>
                                        </li>';

                    /*
                     * Power / session button
                     */
                    $html .= '          <li class="xn-icon-button pull-right last">
                                            <a href="#"><span class="fa fa-power-off"></span></a>
                                            <ul class="xn-drop-left animated zoomIn">
                                                <li><a href="/lock.html"><span class="fa fa-lock"></span> '.tr('Lock Screen').'</a></li>
                                                <li><a href="/signout.html" class="mb-control" data-box="#mb-signout"><span class="fa fa-sign-out"></span> '.tr('Sign Out').'</a></li>
                                            </ul>
                                        </li>';
                }

// :IMPLEMENT:

///*
// * Messages button
// */
//$html .= '                              <li class="xn-icon-button pull-right">
//                                            <a href="#"><span class="fa fa-comments"></span></a>
//                                            <div class="informer informer-danger">4</div>
//                                            <div class="panel panel-primary animated zoomIn xn-drop-left xn-panel-dragging">
//                                                <div class="panel-heading">
//                                                    <h3 class="panel-title"><span class="fa fa-comments"></span> Messages</h3>
//                                                    <div class="pull-right">
//                                                        <span class="label label-danger">4 new</span>
//                                                    </div>
//                                                </div>
//                                                <div class="panel-body list-group list-group-contacts scroll" style="height: 200px;">
//                                                    <a href="#" class="list-group-item">';
//
//                if(empty($_SESSION['user']['id'])){
//                    $html .= '                          <div class="list-group-status status-online"></div>
//                                                        <img src="/pub/img/default-avatar.png" class="pull-left" alt="'.tr('Guest user').'">
//                                                        <span class="contacts-title">'.tr('Guest user').'</span>
//                                                        <p>'.tr('Welcome to :domain!', array(':domain' => $_SESSION['domain'])).'</p>';
//                }else{
//                    $html .= '                          <div class="list-group-status status-online"></div>
//                                                        <img src="'.$_SESSION['user']['avatar'].'" class="pull-left" alt="'.name($_SESSION['user']['avatar']).'">
//                                                        <span class="contacts-title">'.name($_SESSION['user']['avatar']).'</span>
//                                                        <p>'.isset_get($_SESSION['user']['title']).'</p>';
//                }
//
//                $html .= '                                  </a>
//                                                    <a href="#" class="list-group-item">
//                                                        <div class="list-group-status status-away"></div>
//                                                        <img src="assets/images/users/user.jpg" class="pull-left" alt="Dmitry Ivaniuk">
//                                                        <span class="contacts-title">Dmitry Ivaniuk</span>
//                                                        <p>Donec risus sapien, sagittis et magna quis</p>
//                                                    </a>
//                                                    <a href="#" class="list-group-item">
//                                                        <div class="list-group-status status-away"></div>
//                                                        <img src="assets/images/users/user3.jpg" class="pull-left" alt="Nadia Ali">
//                                                        <span class="contacts-title">Nadia Ali</span>
//                                                        <p>Mauris vel eros ut nunc rhoncus cursus sed</p>
//                                                    </a>
//                                                    <a href="#" class="list-group-item">
//                                                        <div class="list-group-status status-offline"></div>
//                                                        <img src="assets/images/users/user6.jpg" class="pull-left" alt="Darth Vader">
//                                                        <span class="contacts-title">Darth Vader</span>
//                                                        <p>I want my money back!</p>
//                                                    </a>
//                                                </div>
//                                                <div class="panel-footer text-center">
//                                                    <a href="pages-messages.html">Show all messages</a>
//                                                </div>
//                                            </div>
//                                        </li>';

// :IMPLEMENT:

///*
// * Tasks button
// */
//$html .= '                              <li class="xn-icon-button pull-right">
//                                            <a href="#"><span class="fa fa-tasks"></span></a>
//                                            <div class="informer informer-warning">3</div>
//                                            <div class="panel panel-primary animated zoomIn xn-drop-left xn-panel-dragging">
//                                                <div class="panel-heading">
//                                                    <h3 class="panel-title"><span class="fa fa-tasks"></span> Tasks</h3>
//                                                    <div class="pull-right">
//                                                        <span class="label label-warning">3 active</span>
//                                                    </div>
//                                                </div>
//                                                <div class="panel-body list-group scroll" style="height: 200px;">
//                                                    <a class="list-group-item" href="#">
//                                                        <strong>Phasellus augue arcu, elementum</strong>
//                                                        <div class="progress progress-small progress-striped active">
//                                                            <div class="progress-bar progress-bar-danger" role="progressbar" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100" style="width: 50%;">50%</div>
//                                                        </div>
//                                                        <small class="text-muted">John Doe, 25 Sep 2015 / 50%</small>
//                                                    </a>
//                                                    <a class="list-group-item" href="#">
//                                                        <strong>Aenean ac cursus</strong>
//                                                        <div class="progress progress-small progress-striped active">
//                                                            <div class="progress-bar progress-bar-warning" role="progressbar" aria-valuenow="80" aria-valuemin="0" aria-valuemax="100" style="width: 80%;">80%</div>
//                                                        </div>
//                                                        <small class="text-muted">Dmitry Ivaniuk, 24 Sep 2015 / 80%</small>
//                                                    </a>
//                                                    <a class="list-group-item" href="#">
//                                                        <strong>Lorem ipsum dolor</strong>
//                                                        <div class="progress progress-small progress-striped active">
//                                                            <div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="95" aria-valuemin="0" aria-valuemax="100" style="width: 95%;">95%</div>
//                                                        </div>
//                                                        <small class="text-muted">John Doe, 23 Sep 2015 / 95%</small>
//                                                    </a>
//                                                    <a class="list-group-item" href="#">
//                                                        <strong>Cras suscipit ac quam at tincidunt.</strong>
//                                                        <div class="progress progress-small">
//                                                            <div class="progress-bar" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%;">100%</div>
//                                                        </div>
//                                                        <small class="text-muted">John Doe, 21 Sep 2015 /</small><small class="text-success"> Done</small>
//                                                    </a>
//                                                </div>
//                                                <div class="panel-footer text-center">
//                                                    <a href="pages-tasks.html">Show all tasks</a>
//                                                </div>
//                                            </div>
//                                        </li>';

                /*
                 * Language button
                 */
                $html .= '              <li class="xn-icon-button pull-right">
                                            <a href="#"><span class="flag flag-'.atlant_flag().'"></span></a>
                                            <ul class="xn-drop-left xn-drop-white animated zoomIn">';

                foreach($_CONFIG['language']['supported'] as $code => $language){
                    $html .= '<li><a href="'.domain('/switch-language.html?language='.$code).'"><span class="flag flag-'.atlant_flag($code).'"></span> '.$language.'</a></li>';
                }

                $html .= '                  </ul>
                                        </li>';

                $html .= '          </ul>
                                    <ul class="breadcrumb">';

                foreach($params['breadcrumbs'] as $label => $url){
                    $html .= '<li><a href="'.$url.'">'.$label.'</a></li>';
                }

                $html .= '          </ul>
                                    <!-- END BREADCRUMB -->

                                    <!-- PAGE CONTENT WRAPPER -->
                                    <div class="page-content-wrap">'.html_flash('system');
                break;

            default:
                throw new bException(tr('atlant_page_header(): Unknown page_type ":type" specified', array(':type' => $params['page_type'])), 'unknown');
        }

        return $html;

    }catch(Exception $e){
        throw new bException('atlant_page_header(): Failed', $e);
    }
}



/*
 * Create and return the page footer
 */
function atlant_html_footer($params){
    try{
        array_params($params);
        array_default($params, 'page_type', 'full');

        switch($params['page_type']){
            case 'simple':
                $html = '';
                break;

            case 'full':
                $html = '           </div>
                                    <!-- END PAGE CONTENT WRAPPER -->
                                </div>
                                <!-- END PAGE CONTENT -->
                            </div>
                            <!-- END PAGE CONTAINER -->

                            <!-- MESSAGE BOX-->
                            <div class="message-box animated fadeIn" data-sound="alert" id="mb-signout">
                                <div class="mb-container">
                                    <div class="mb-middle">
                                        <div class="mb-title"><span class="fa fa-sign-out"></span> '.tr('Sign <strong>Out</strong> ?').'</div>
                                        <div class="mb-content">
                                            <p>'.tr('Are you sure you want to sign out?').'</p>
                                            <p>'.tr('Press "No" if you want to continue work. Press "Yes" to sign out current user.').'</p>
                                        </div>
                                        <div class="mb-footer">
                                            <div class="pull-right">
                                                <a href="'.domain('signout.html?r='.rand(0, 1000000000)).'" class="btn btn-success btn-lg">'.tr('Yes').'</a>
                                                <button class="btn btn-default btn-lg mb-control-close">'.tr('No').'</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- END MESSAGE BOX-->

                            <!-- START PRELOADS -->
                            <audio id="audio-alert" src="/pub/audio/alert.mp3" preload="auto"></audio>
                            <audio id="audio-fail" src="/pub/audio/fail.mp3" preload="auto"></audio>
                            <!-- END PRELOADS -->';
                break;

            default:
                throw new bException(tr('atlant_html_footer(): Unknown page_type ":type" specified', array(':type' => $params['page_type'])), 'unknown');
        }

        return $html.html_footer();

    }catch(Exception $e){
        throw new bException('atlant_html_footer(): Failed', $e);
    }
}



/*
 *
 */
function atlant_form_header($object, $force = false){
    try{
        if((empty($object['id']) or (isset_get($object['status']) === '_new')) and !$force){
            return '';
        }

        $html = '       <h3>'.tr('Meta information').'</h3>';

        if(!empty($object['createdon'])){
            $html .= '  <div class="form-group">
                            <label class="col-md-3 col-xs-12 control-label">'.tr('Created on').'</label>
                            <div class="col-md-6 col-xs-12">
                                <input type="text" class="form-control" value="'.$object['createdon'].'" disabled>
                            </div>
                        </div>';
        }

        $name = name($object, 'createdby', tr('Unknown'));

        if(!empty($name)){
            $html .= '  <div class="form-group">
                            <label class="col-md-3 col-xs-12 control-label">'.tr('Created by').'</label>
                            <div class="col-md-6 col-xs-12">
                                <input type="text" class="form-control" value="'.$name.'" disabled>
                            </div>
                        </div>';
        }

        if(!empty($object['modifiedon'])){
            $html .= '  <div class="form-group">
                            <label class="col-md-3 col-xs-12 control-label">'.tr('Modified on').'</label>
                            <div class="col-md-6 col-xs-12">
                                <input type="text" class="form-control" value="'.$object['modifiedon'].'" disabled>
                            </div>
                        </div>';
        }

        $name = name($object, 'modifiedby', '');

        if(!empty($name)){
            $html .= '  <div class="form-group">
                            <label class="col-md-3 col-xs-12 control-label">'.tr('Modified by').'</label>
                            <div class="col-md-6 col-xs-12">
                                <input type="text" class="form-control" value="'.$name.'" disabled>
                            </div>
                        </div>';
        }

        $name = status(isset_get($object['status']));

        if(!empty($name)){
            $html .= '  <div class="form-group">
                            <label class="col-md-3 col-xs-12 control-label">'.tr('Status').'</label>
                            <div class="col-md-6 col-xs-12">
                                <input type="text" class="form-control" value="'.$name.'" disabled>
                            </div>
                        </div>';
        }

        $html .= '      <div class="form-group">
                            <label class="col-md-3 col-xs-12 control-label">'.tr('ID').'</label>
                            <div class="col-md-6 col-xs-12">
                                <input type="text" class="form-control numeric" value="'.$object['id'].'" disabled>
                            </div>
                        </div>
                        <hr>';

        return $html;

    }catch(Exception $e){
        throw new bException('atlant_form_header(): Failed', $e);
    }
}



/*
 *
 */
function atlant_panel_controls($controls){
    try{
        if(!$controls){
            return '';
        }

        $html = '   <ul class="panel-controls" style="margin-top: 2px;">';

        foreach($controls as $control){
            if(is_string($control)){
                switch($control){
                    case 'expand':
                        $html .= '<li><a href="#" class="panel-fullscreen"><span class="fa fa-expand"></span></a></li>';
                        break;

                    case 'refresh':
                        $html .= '<li><a href="#" class="panel-refresh"><span class="fa fa-refresh"></span></a></li>';
                        break;

                    default:
                        throw new bException('atlant_panel_controls(): Unknown control ":control" specified', array(':control' => $control), 'unknown');
                }

            }else{
                switch($control['type']){
                    case 'help':
// :IMPLEMENT:
// sort-desc sort-up
                        $html .= '<li><a href="#" class="panel-help"><span class="fa fa-sort-desc"></span></a></li>';
                        break;

                    case 'dropdown':
// :IMPLEMENT:
                        $html .= '  <li class="dropdown">
                                        <a href="#" class="dropdown-toggle" data-toggle="dropdown"><span class="fa fa-cog"></span></a>
                                        <ul class="dropdown-menu">
                                            <li><a href="#" class="panel-collapse"><span class="fa fa-angle-down"></span> Collapse</a></li>
                                            <li><a href="#" class="panel-remove"><span class="fa fa-times"></span> Remove</a></li>
                                        </ul>
                                    </li>';
                        break;

                    default:
                        throw new bException('atlant_panel_controls(): Unknown control ":control" specified', array(':control' => $control), 'unknown');
                }
            }
        }

        $html .= '  </ul>';

        return $html;

    }catch(Exception $e){
        throw new bException('atlant_panel_controls(): Failed', $e);
    }
}



/*
 * Quick info panel
 */
function atlant_panel_quick_info($user = null){
    try{
        if(!$user){
            $user = $_SESSION['user'];
        }

        $html = '       <div class="col-md-3">
                            <div class="panel panel-default form-horizontal">
                                <div class="panel-body">
                                    <h3><span class="fa fa-info-circle"></span> '.tr('Quick Info').'</h3>
                                    <p>'.tr('Some quick info about you').'</p>
                                </div>
                                <div class="panel-body form-group-separated">
                                    <div class="form-group">
                                        <label class="col-md-4 col-xs-5 control-label">'.tr('Amount of visits').'</label>
                                        <div class="col-md-8 col-xs-7 line-height-30">'.$user['signin_count'].'</div>
                                    </div>';

        if(!empty($user['last_signin'])){
            $html .= '              <div class="form-group">
                                        <label class="col-md-4 col-xs-5 control-label">'.tr('Last visit').'</label>
                                        <div class="col-md-8 col-xs-7 line-height-30">'.date_convert($user['last_signin'], 'human_date').' at '.date_convert($user['last_signin'], 'human_time').'</div>
                                    </div>';
        }

        $html .= '                  <div class="form-group">
                                        <label class="col-md-4 col-xs-5 control-label">'.tr('Registration').'</label>
                                        <div class="col-md-8 col-xs-7 line-height-30">'.date_convert($user['createdon'], 'human_date').' at '.date_convert($user['createdon'], 'human_time').'</div>
                                    </div>';

                                    //<div class="form-group">
                                    //    <label class="col-md-4 col-xs-5 control-label">'.tr('Groups').'</label>
                                    //    <div class="col-md-8 col-xs-7">administrators, managers, team-leaders, developers</div>
                                    //</div>';

                                    //<div class="form-group">
                                    //    <label class="col-md-4 col-xs-5 control-label">'.tr('Birthday').'</label>
                                    //    <div class="col-md-8 col-xs-7 line-height-30">14.02.1989</div>
                                    //</div>

        $html .= '              </div>
                            </div>';

        return $html;

    }catch(Exception $e){
        throw new bException('atlant_panel_quick_info(): Failed', $e);
    }
}



/*
 *
 */
function atlant_force_my_profile($hash){
    global $_CONFIG;

    try{
        /*
         * Force user to my-profile page?
         */
        load_config('atlant');

        if(empty($_SESSION['atlant_init'])){
            $_SESSION['atlant_init'] = true;

            if(!empty($_SESSION['user']['id'])){
                if(SCRIPT !== 'signout'){
                    if($_CONFIG['atlant']['username_required'] and empty($_SESSION['user']['username'])){
                        $redirect_reasons[] = tr('<strong>Important!</strong> You must first set a username for your account before you can continue! <strong>SEE THE "UPDATE YOUR PASSWORD" PANEL FOR MORE INFORMATION</strong>');
                    }

                    if(empty($_SESSION['user']['password'])){
                        $_SESSION['user']['password'] = sql_get('SELECT `password` FROM `users` WHERE `id` = :id', 'password', array(':id' => $_SESSION['user']['id']));
                    }

                    if(str_from(substr($_SESSION['user']['password'], 1), '*') === $hash){
                        /*
                         * This is the password "ingigamexico"
                         */
                        $redirect_reasons[] = tr('<strong>Important!</strong> You must first update the password of your account before you can continue! <strong>SEE THE "YOUR PROFILE" PANEL FOR MORE INFORMATION</strong>');
                    }

                    if(!empty($redirect_reasons)){
                        if(SCRIPT !== 'my-profile'){
                            /*
                             * First go to the my-profile page
                             */
                            redirect(domain('/my-profile.html'));
                        }

                        /*
                         * Show the user what the issues are on their profile page
                         */
                        foreach($redirect_reasons as $redirect){
                            html_flash_set($redirect, 'danger');
                        }
                    }
                }

                unset($redirect_reasons);
            }
        }

    }catch(Exception $e){
        throw new bException('atlant_force_my_profile(): Failed', $e);
    }
}



/*
 *
 */
function atlant_flag($language = null){
    try{
        if(!$language){
            $language = LANGUAGE;
        }

        switch($language){
            case 'en':
                return 'us';

            case 'es':
                return 'mx';

            case 'nl':
                return $language;

            default:
                throw new bException(tr('atlant_flag(): Unknown language ":language" specified', array(':language' => $language)), 'unknown');
        }

    }catch(Exception $e){
        throw new bException('atlant_flag(): Failed', $e);
    }
}


/*
 * Create an atlant navigation button
 */
function atlant_navigate_button($text, $type, $url, $params = null){
    try{
        return ' <a href="'.domain($url).'" class="mb-xs mt-xs mr-xs btn btn-'.$type.'" onclick="history.go(-1);">'.$text.'</a>';

    }catch(Exception $e){
        throw new bException('atlant_navigate_button(): Failed', $e);
    }
}



/*
 * Create an atlant submit button of the specified type
 */
function atlant_submit_button($text, $type, $id){
    try{
        return ' <input type="submit" class="mb-xs mt-xs mr-xs btn btn-'.$type.'" name="'.$id.'" id="'.$id.'" value="'.$text.'">';

    }catch(Exception $e){
        throw new bException('atlant_submit_button(): Failed', $e);
    }
}



/*
 * Create an atlant javascript back button
 */
function atlant_back_button($text, $type = 'primary'){
    try{
        return atlant_navigate_button($text, $type, 'javascript:history.back()');

    }catch(Exception $e){
        throw new bException('atlant_back_button(): Failed', $e);
    }
}



/*
 * Returns HTML for a standard atlant table filter input box
 */
function atlant_table_filter($selected){
    try{
        return '<input type="text" class="form-control" name="filter" value="'.$selected.'" placeholder="'.tr('Filter...').'" aria-controls="DataTables_Table_0" autofocus>';

    }catch(Exception $e){
        throw new bException('atlant_table_filter(): Failed', $e);
    }
}
?>
