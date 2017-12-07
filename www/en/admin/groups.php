<?php
require_once(dirname(__FILE__).'/../libs/startup.php');

rights_or_access_denied('admin,groups');
load_libs('paging');


/*
 * Process requested actions
 */
try{
    switch(isset_get($_POST['action'])){
        case '':
            break;

        case 'create':
            redirect(domain('/admin/group.php'));

        case 'delete':
            /*
             * Erase the specified users
             */
            if(empty($_POST['id'])){
                throw new bException('Cannot erase groups, no groups selected', 'notspecified');
            }

            if(!is_array($_POST['id'])){
                throw new bException('Cannot erase groups, invalid data specified', 'invalid');
            }

            if(in_array($_SESSION['user']['id'], $_POST['id'])){
                throw new bException('You cannot delete yourself', 'invalid');
            }

            $in = sql_in($_POST['id'], ':id');
            $r  = sql_query('UPDATE `groups` SET `status` = "deleted" WHERE `id` IN ('.implode(',', array_keys($in)).')', $in);

            if(!$r->rowCount()){
                html_flash_set('No groups have been deleted', 'warning');

            }else{
                html_flash_set(log_database('Deleted "'.$r->rowCount().'" groups "', 'users_deleted'), 'success');
            }

            break;

        case 'undelete':
            /*
             * Erase the specified groups
             */
            if(empty($_POST['id'])){
                throw new bException('Cannot undelete groups, no groups selected', 'notspecified');
            }

            if(!is_array($_POST['id'])){
                throw new bException('Cannot undelete groups, invalid data specified', 'invalid');
            }

            if(in_array($_SESSION['user']['id'], $_POST['id'])){
                throw new bException('You cannot undelete yourself', 'invalid');
            }

            $in = sql_in($_POST['id'], ':id');
            $r  = sql_query('UPDATE `groups` SET `status` = NULL WHERE `id` IN ('.implode(',', array_keys($in)).')', $in);

            if(!$r->rowCount()){
                html_flash_set('No user groups have been undeleted', 'warning');

            }else{
                html_flash_set(log_database('Undeleted "'.$r->rowCount().'" groups "', 'users_undeleted'), 'success');
            }

            break;

        case 'erase':
            /*
             * Erase the specified groups
             */
            if(empty($_POST['id'])){
                throw new bException('Cannot erase groups, no groups selected', 'notspecified');
            }

            if(!is_array($_POST['id'])){
                throw new bException('Cannot erase groups, invalid data specified', 'invalid');
            }

            if(in_array($_SESSION['user']['id'], $_POST['id'])){
                throw new bException('You cannot erase yourself', 'invalid');
            }

            $in = sql_in($_POST['id'], ':id');
            $r  = sql_query('DELETE FROM `groups` WHERE `id` IN ('.implode(',', array_keys($in)).')', $in);

            if(!$r->rowCount()){
                html_flash_set('No user groups have been erased', 'warning');

            }else{
                html_flash_set(log_database('Erased "'.$r->rowCount().'" groups "', 'users_erased'), 'success');
            }

            break;

        default:
            /*
             * Unknown action specified
             */
            html_flash_set(tr('Unknown action "%action%" specified', '%action%', str_log($_POST['action'])), 'error');
    }

}catch(Exception $e){
    html_flash_set($e);
}


/*
 * Setup filters
 */
$views   = array('name'       => 'view',
                 'class'      => 'filter form-control mb-xs mt-xs mr-xs btn btn-default dropdown-toggle',
                 'none'       => tr('Normal groups'),
                 'autosubmit' => true,
                 'selected'   => isset_get($_GET['view']),
                 'resource'   => array('deleted' => tr('Deleted groups')));

/*
 * Build query
 */
$execute = array();

$query   = 'SELECT `id`,
                   `name`,
                   `seoname`,
                   `description`

            FROM   `groups`';

$paging  = 'SELECT COUNT(`id`) AS `count`

            FROM   `groups`';


/*
 * Consider only groups with a specific type as real groups?
 */
////if($_CONFIG['groups']['type_filter'] !== false){
////    if($_CONFIG['groups']['type_filter'] === null){
//        //$where[]          = ' `groups`.`type` IS NULL';
//
//    }else{
//        //$where[]          = ' `groups`.`type` = :type';
//        //$execute[':type'] = $_CONFIG['groups']['type_filter'];
//    }
//
//}else{
//    /*
//     * Don't filter on type
//     */
//    //$where[] = ' `groups`.`type` = `groups`.`type`';
//}


/*
 * Select sections dependant on the view
 */
switch(isset_get($_GET['view'])){
    case '':
    case 'normal':
        $where[] = ' `groups`.`status` IS NULL';

        $actions = array('name'       => 'action',
                         'class'      => 'form-action input-sm',
                         'none'       => tr('Action'),
                         'autosubmit' => true,
                         'resource'   => array('create'   => tr('Create new group'),
                                               'delete'   => tr('Delete selected groups')));

        break;

    case 'all':
        $actions = array('name'       => 'action',
                         'class'      => 'form-action input-sm',
                         'none'       => tr('Action'),
                         'autosubmit' => true,
                         'resource'   => array('create'   => tr('Create new group'),
                                               'delete'   => tr('Delete selected groups'),
                                               'undelete' => tr('Undelete selected groups'),
                                               'erase'    => tr('Erase selected groups')));
        break;

    case 'empty':
        $where[] = ' `groups`.`status` = "empty"';

        $actions = array('name'       => 'action',
                         'class'      => 'form-action input-sm',
                         'none'       => tr('Action'),
                         'autosubmit' => true,
                         'resource'   => array('create'   => tr('Create new group'),
                                               'delete'   => tr('Delete selected groups')));
        break;

    case 'deleted':
        $where[] = ' `groups`.`status` = "deleted"';

        $actions = array('name'       => 'action',
                         'class'      => 'form-action input-sm',
                         'none'       => tr('Action'),
                         'autosubmit' => true,
                         'resource'   => array('undelete' => tr('Undelete selected groups'),
                                               'erase'    => tr('Erase selected groups')));
        break;

    default:
        html_flash_set('Unknown view filter "'.str_log($_GET['view']).'" specified', 'error');
        redirect(true);
}


/*
 * Apply role filter
 */
if(isset_get($_GET['role'])){
    if($_GET['role'] == 'none'){
        //$where[]          = ' `groups`.`role` IS NULL';

    }else{
        //$where[]          = ' `groups`.`role` = :role';
        //$execute[':role'] = cfm($_GET['role']);
    }
}


/*
 * Apply generic filter
 */
if(!empty($_GET['filter'])){
    //$where[]              = ' (`groups`.`name` LIKE :name OR `groups`.`email` LIKE :email OR `groups`.`username` LIKE :username)';
    $execute[':name']     = '%'.$_GET['filter'].'%';
    $execute[':email']    = '%'.$_GET['filter'].'%';
    $execute[':username'] = '%'.$_GET['filter'].'%';
}


/*
 * Execute query
 */
if(!empty($where)){
    $query  .= ' WHERE '.implode(' AND ', $where);
    $paging .= ' WHERE '.implode(' AND ', $where);
}

$paging = paging_data(isset_get($_GET['page']), isset_get($_GET['limit']), sql_get($paging, 'count', isset_get($execute)));

$query .= ' ORDER BY `groups`.`name`'.$paging['query'];

$r      = sql_query($query, $execute);


/*
 * Build HTML
 */
$html = '   <div class="row">
                <div class="col-md-12">
                    <section class="panel">
                        <header class="panel-heading">
                            <h2 class="panel-title">'.tr('Manage groups').'</h2>
                            <p>
                                '.html_flash().'
                                <form action="'.domain(true).'" method="get">
                                    <div class="row">
                                        <div class="col-sm-2">
                                            '.html_select($views).'
                                        </div>
                                        <div class="visible-xs mb-md"></div>
                                        <div class="visible-xs mb-md"></div>
                                        <div class="col-sm-2">
                                            <div class="input-group input-group-icon">
                                                <input type="text" class="form-control col-md-3" name="filter" id="filter" value="'.str_log(isset_get($_GET['filter'], '')).'" placeholder="General filter">
                                                <span class="input-group-addon">
                                                    <span class="icon"><i class="fa fa-search"></i></span>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="visible-xs mb-md"></div>
                                        <div class="col-sm-2">
                                            <input type="text" class="form-control col-md-3" name="limit" id="limit" value="'.str_log(isset_get($paging['display_limit'], '')).'" placeholder="'.tr('Row limit (default %entries% entries)', array('%entries%' => str_log($paging['default_limit']))).'">
                                        </div>
                                        <div class="visible-xs mb-md"></div>
                                        <div class="col-sm-2">
                                            <input type="submit" class="mb-xs mr-xs btn btn-sm btn-primary" name="reload" id="reload" value="'.tr('Reload').'">
                                        </div>
                                        <div class="visible-xs mb-md"></div>
                                    </div>
                                </form>
                            </p>
                        </header>
                        <form action="'.domain(true).'" method="post">
                            <div class="panel-body">
                                <div class="dataTables_wrapper no-footer">';

if(!$r->rowCount()){
    $html .= '<p>'.tr('No groups were found with the current filter').'</p>';

}else{
    $html .= '  <div class="table-responsive">
                    <table class="link select table mb-none table-striped table-hover">
                        <thead>
                            <th class="select">
                                <input type="checkbox" name="id[]" class="all"></th><th>'.tr('Name').'
                            </th>
                            '.((isset_get($_GET['view']) == 'all') ? '<th>'.tr('Status').'</th>' : '').'
                            <th>'.tr('# of Memebers').'</th>
                            <th>'.tr('Description').'</th>
                        </thead>';

    while($group = sql_fetch($r)){
        $a      = '<a href="'.domain('/admin/group.php?group='.$group['seoname']).'">';

        $number = sql_get('SELECT COUNT(`id`) AS `count`

                           FROM   `users_groups`

                           WHERE  `groups_id` = :groups_id',

                           array(':groups_id' => $group['id']));

        $html  .= ' <tr>
                        <td class="select"><input type="checkbox" name="id[]" value="'.$group['id'].'"'.(in_array($group['id'], (array) isset_get($_POST['id'])) ? ' checked' : '').'></td>
                        <td>'.$a.$group['name'].'</a></td>
                        '.((isset_get($_GET['view']) == 'all') ? '<td>'.status($group['status']).'</a></td>' : '').'
                        <td>'.$a.$number['count'].'</a></td>
                        <td>'.$a.$group['description'].'</a></td>
                    </tr>';
    }

    $html .= '      </table>
                </div>';
}

$html .= '                  <div class="row datatables-footer">
                                <div class="col-sm-12 col-md-6">
                                    <div class="dataTables_info" id="datatable-default_info" role="status" aria-live="polite">
                                        '.tr('Showing %start% to %stop% of %count% entries', array('%count%' => $paging['count'], '%start%' => $paging['start'], '%stop%' => $paging['stop'])).'
                                    </div>
                                </div>
                                <div class="col-sm-12 col-md-6">
                                    <div class="dataTables_paginate paging_bs_normal">
                                        <div class="dataTables_paginate paging_bs_normal" id="datatable-default_paginate">'.
                                            paging_generate(array('html'     => '   <ul class="pagination">
                                                                                        %list%
                                                                                    </ul>',
                                                                  'current'  => $paging['page'],
                                                                  'count'    => $paging['count'],
                                                                  'limit'    => $paging['limit'],
                                                                  'active'   => 'active',
                                                                  'disabled' => 'disabled',
                                                                  'url'      => domain(true, 'page=%page%'),
                                                                  'page'     => '<li class="%active%"><a href="%url%">%page%</a></li>',
                                                                  'prev'     => '<li class="%disabled%"><a href="%url%">'.tr('<').'</a></li>',
                                                                  'next'     => '<li class="%disabled%"><a href="%url%">'.tr('>').'</a></li>',
                                                                  'first'    => '<li class="prev %disabled%"><a href="%url%">'.tr('<<').'</a></li>',
                                                                  'last'     => '<li class="next %disabled%"><a href="%url%">'.tr('>>').'</a></li>')).'
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        '.html_select($actions).'
                    </div>
                </form>
            </section>
        </div>
    </div>';

log_database('Viewed groups', 'groups_viewed');

$params = array('icon'        => 'fa-groups',
                'title'       => tr('Groups'),
                'breadcrumbs' => array(tr('Groups'), tr('Manage')));

echo ca_page($html, $params);
?>
