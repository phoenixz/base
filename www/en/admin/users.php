<?php
require_once(dirname(__FILE__).'/../libs/startup.php');

rights_or_redirect('admin,users');

$std_limit = 500;

$limit     = sql_valid_limit(isset_get($_GET['limit']), $std_limit);


/*
 * Process requested actions
 */
try{
    switch(isset_get($_POST['action'])){
        case '':
            break;

        case 'create':
            redirect(domain('/admin/user.php'));

        case 'delete':
            /*
             * Erase the specified users
             */
            if(empty($_POST['id'])){
                throw new bException('Cannot erase users, no users selected', 'notspecified');
            }

            if(!is_array($_POST['id'])){
                throw new bException('Cannot erase users, invalid data specified', 'invalid');
            }

            if(in_array($_SESSION['user']['id'], $_POST['id'])){
                throw new bException('You cannot delete yourself', 'invalid');
            }

            $in = sql_in($_POST['id'], ':id');
            $r  = sql_query('UPDATE `users` SET `status` = "deleted" WHERE `id` IN ('.implode(',', array_keys($in)).')', $in);

            if(!$r->rowCount()){
                html_flash_set('No user users have been deleted', 'warning');

            }else{
                html_flash_set(log_database('Deleted "'.$r->rowCount().'" users "', 'users_deleted'), 'success');
            }

            break;

        case 'undelete':
            /*
             * Erase the specified users
             */
            if(empty($_POST['id'])){
                throw new bException('Cannot undelete users, no users selected', 'notspecified');
            }

            if(!is_array($_POST['id'])){
                throw new bException('Cannot undelete users, invalid data specified', 'invalid');
            }

            if(in_array($_SESSION['user']['id'], $_POST['id'])){
                throw new bException('You cannot undelete yourself', 'invalid');
            }

            $in = sql_in($_POST['id'], ':id');
            $r  = sql_query('UPDATE `users` SET `status` = NULL WHERE `id` IN ('.implode(',', array_keys($in)).')', $in);

            if(!$r->rowCount()){
                html_flash_set('No user users have been undeleted', 'warning');

            }else{
                html_flash_set(log_database('Undeleted "'.$r->rowCount().'" users "', 'users_undeleted'), 'success');
            }

            break;

        case 'erase':
            /*
             * Erase the specified users
             */
            if(empty($_POST['id'])){
                throw new bException('Cannot erase users, no users selected', 'notspecified');
            }

            if(!is_array($_POST['id'])){
                throw new bException('Cannot erase users, invalid data specified', 'invalid');
            }

            if(in_array($_SESSION['user']['id'], $_POST['id'])){
                throw new bException('You cannot erase yourself', 'invalid');
            }

            $in = sql_in($_POST['id'], ':id');
            $r  = sql_query('DELETE FROM `users` WHERE `id` IN ('.implode(',', array_keys($in)).')', $in);

            if(!$r->rowCount()){
                html_flash_set('No user users have been erased', 'warning');

            }else{
                html_flash_set(log_database('Erased "'.$r->rowCount().'" users "', 'users_erased'), 'success');
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
                 'none'       => tr('Normal users'),
                 'autosubmit' => true,
                 'selected'   => isset_get($_GET['view']),
                 'resource'   => array('deleted' => tr('Deleted users'),
                                       'all'     => tr('All users'),
                                       'empty'   => tr('Empty users')));

$roles   = array('name'       => 'role',
                 'class'      => 'filter form-control mb-xs mt-xs mr-xs btn btn-default dropdown-toggle',
                 'none'       => tr('Show all roles'),
                 'autosubmit' => true,
                 'selected'   => isset_get($_GET['role']),
                 'resource'   => array_merge(array('none' => tr('None')), sql_list('SELECT `name` AS `id`, `name` FROM `roles` ORDER BY `name`')));


/*
 * Build query
 */
$execute = array();

$query   = 'SELECT `users`.`id`,
                   `users`.`name`,
                   `users`.`username`,
                   `users`.`email`,
                   `users`.`status`,
                   `users`.`role`,
                   `users`.`commentary`,
                   `users_admin`.`name` IS NOT NULL AS `admin`,
                   `users_god`.`name`   IS NOT NULL AS `god`

            FROM   `users`

            LEFT JOIN `users_rights` AS `users_admin`
            ON        `users_admin`.`name`     = "admin"
            AND       `users_admin`.`users_id` = `users`.`id`

            LEFT JOIN `users_rights` AS `users_god`
            ON        `users_god`.`name`       = "admin"
            AND       `users_god`.`users_id`   = `users`.`id`';


/*
 * Consider only users with a specific type as real users?
 */
if($_CONFIG['users']['type_filter'] !== false){
    if($_CONFIG['users']['type_filter'] === null){
        $where[]          = ' `users`.`type` IS NULL';

    }else{
        $where[]          = ' `users`.`type` = :type';
        $execute[':type'] = $_CONFIG['users']['type_filter'];
    }

}else{
    /*
     * Don't filter on type
     */
    $where[] = ' `users`.`type` = `users`.`type`';
}


/*
 * Select sections dependant on the view
 */
switch(isset_get($_GET['view'])){
    case '':
    case 'normal':
        $where[] = ' `users`.`status` IS NULL';

        $actions = array('name'       => 'action',
                         'class'      => 'form-action input-sm',
                         'none'       => tr('Action'),
                         'autosubmit' => true,
                         'resource'   => array('create'   => tr('Create new user'),
                                               'delete'   => tr('Delete selected users')));

        break;

    case 'all':
        $actions = array('name'       => 'action',
                         'class'      => 'form-action input-sm',
                         'none'       => tr('Action'),
                         'autosubmit' => true,
                         'resource'   => array('create'   => tr('Create new user'),
                                               'delete'   => tr('Delete selected users'),
                                               'undelete' => tr('Undelete selected users'),
                                               'erase'    => tr('Erase selected users')));
        break;

    case 'empty':
        $where[] = ' `users`.`status` = "empty"';

        $actions = array('name'       => 'action',
                         'class'      => 'form-action input-sm',
                         'none'       => tr('Action'),
                         'autosubmit' => true,
                         'resource'   => array('create'   => tr('Create new user'),
                                               'delete'   => tr('Delete selected users')));
        break;

    case 'deleted':
        $where[] = ' `users`.`status` = "deleted"';

        $actions = array('name'       => 'action',
                         'class'      => 'form-action input-sm',
                         'none'       => tr('Action'),
                         'autosubmit' => true,
                         'resource'   => array('undelete' => tr('Undelete selected users'),
                                               'erase'    => tr('Erase selected users')));
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
        $where[]          = ' `users`.`role` IS NULL';

    }else{
        $where[]          = ' `users`.`role` = :role';
        $execute[':role'] = cfm($_GET['role']);
    }
}


/*
 * Apply generic filter
 */
if(!empty($_GET['filter'])){
    $where[]              = ' (`users`.`name` LIKE :name OR `users`.`email` LIKE :email OR `users`.`username` LIKE :username)';
    $execute[':name']     = '%'.$_GET['filter'].'%';
    $execute[':email']    = '%'.$_GET['filter'].'%';
    $execute[':username'] = '%'.$_GET['filter'].'%';
}


/*
 * Execute query
 */
if(!empty($where)){
    $query .= ' WHERE '.implode(' AND ', $where);
}

$query .= ' ORDER BY `users`.`name`';

if($limit){
    $query .= ' LIMIT '.$limit;
}

$r = sql_query($query, $execute);


/*
 * Build HTML
 */
$html = '   <div class="row">
                <div class="col-md-12">
                    <section class="panel">
                        <header class="panel-heading">
                            <h2 class="panel-title">'.tr('Manage users').'</h2>
                            <p>
                                '.html_flash().'
                                <form action="'.domain(true).'" method="get">
                                    <div class="row">
                                        <div class="col-sm-2">
                                            '.html_select($views).'
                                        </div>
                                        <div class="visible-xs mb-md"></div>
                                        <div class="col-sm-2">
                                            '.html_select($roles).'
                                        </div>
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
                                            <input type="text" class="form-control col-md-3" name="limit" id="limit" value="'.str_log(isset_get($_GET['limit'], '')).'" placeholder="'.tr('Row limit (default %entries% entries)', array('%entries%' => str_log($std_limit))).'">
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
                            <div class="panel-body">';

if(!$r->rowCount()){
    $html .= '<p>'.tr('No users were found with the current filter').'</p>';

}else{
    $html .= '  <div class="table-responsive">
                    <table class="link select table mb-none table-striped table-hover">
                        <thead>
                            <th class="select">
                                <input type="checkbox" name="id[]" class="all"></th><th>'.tr('Username').'
                            </th>
                            '.((isset_get($_GET['view']) == 'all') ? '<th>'.tr('Status').'</th>' : '').'
                            <th>'.tr('Role').'</th>
                            <th>'.tr('Admin').'</th>
                            <th>'.tr('God').'</th>
                            <th>'.tr('Real name').'</th>
                            <th>'.tr('Email').'</th>
                            <th>'.tr('Commentary').'</th>
                        </thead>';

    while($user = sql_fetch($r)){
        $a = '<a href="'.domain('/admin/user.php?user='.$user['username']).'">';

        $html .= '  <tr>
                        <td class="select"><input type="checkbox" name="id[]" value="'.$user['id'].'"'.(in_array($user['id'], (array) isset_get($_POST['id'])) ? ' checked' : '').'></td>
                        <td>'.$a.$user['username'].'</a></td>
                        '.((isset_get($_GET['view']) == 'all') ? '<td>'.status($user['status']).'</a></td>' : '').'
                        <td>'.$a.($user['role'] ? $user['role'] : tr('None')).'</a></td>
                        <td>'.$a.($user['admin'] ? tr('Yes') : tr('No')).'</a></td>
                        <td>'.$a.($user['god']   ? tr('Yes') : tr('No')).'</a></td>
                        <td>'.$a.$user['name'].'</a></td>
                        <td>'.$a.$user['email'].'</a></td>
                        <td>'.$a.$user['commentary'].'</a></td>
                    </tr>';
    }

    $html .= '      </table>
                </div>';
}

$html .=                html_select($actions).'
                    </div>
                </form>
            </section>
        </div>
    </div>';

log_database('Viewed users', 'users_viewed');

$params = array('icon'        => 'fa-users',
                'title'       => tr('Users'),
                'breadcrumbs' => array(tr('Users'), tr('Manage')));

echo ca_page($html, $params);
?>
