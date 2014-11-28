<?php
require_once(dirname(__FILE__).'/../libs/startup.php');

$limit = 50;

right_or_redirect('admin,users');


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
                 'selected'   => isset_get($_POST['view']),
                 'resource'   => array('deleted' => tr('Deleted users'),
                                       'all'     => tr('All users'),
                                       'empty'   => tr('Empty users')));

$roles   = array('name'       => 'role',
                 'class'      => 'filter form-control mb-xs mt-xs mr-xs btn btn-default dropdown-toggle',
                 'none'       => tr('Show all roles'),
                 'autosubmit' => true,
                 'selected'   => isset_get($_POST['role']),
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
        $query           .= ' WHERE `users`.`type` IS NULL';

    }else{
        $query           .= ' WHERE `users`.`type` = :type';
        $execute[':type'] = $_CONFIG['users']['type_filter'];
    }

}else{
    /*
     * Don't filter on type
     */
    $query  .= ' WHERE `users`.`type` = `users`.`type`';
}


/*
 * Select sections dependant on the view
 */
switch(isset_get($_POST['view'])){
    case '':
    case 'normal':
        $query  .= ' AND `users`.`status` IS NULL';

        $actions = array('name'       => 'action',
                         'none'       => tr('Action'),
                         'autosubmit' => true,
                         'resource'   => array('create'   => tr('Create new user'),
                                               'delete'   => tr('Delete selected users')));

        break;

    case 'all':
        $actions = array('name'       => 'action',
                         'none'       => tr('Action'),
                         'autosubmit' => true,
                         'resource'   => array('create'   => tr('Create new user'),
                                               'delete'   => tr('Delete selected users'),
                                               'undelete' => tr('Undelete selected users'),
                                               'erase'    => tr('Erase selected users')));
        break;

    case 'empty':
        $query  .= ' AND `users`.`status` = "empty"';

        $actions = array('name'       => 'action',
                         'none'       => tr('Action'),
                         'autosubmit' => true,
                         'resource'   => array('create'   => tr('Create new user'),
                                               'delete'   => tr('Delete selected users')));
        break;

    case 'deleted':
        $query .= ' AND `users`.`status` = "deleted"';

        $actions = array('name'       => 'action',
                         'none'       => tr('Action'),
                         'autosubmit' => true,
                         'resource'   => array('undelete' => tr('Undelete selected users'),
                                               'erase'    => tr('Erase selected users')));
        break;

    default:
        html_flash_set('Unknown view filter "'.str_log($_POST['view']).'" specified', 'error');
        redirect(true);
}


/*
 * Apply role filter
 */
if(isset_get($_POST['role'])){
    if($_POST['role'] == 'none'){
        $query           .= ' AND `users`.`role` IS NULL';

    }else{
        $query           .= ' AND `users`.`role` = :role';
        $execute[':role'] = cfm($_POST['role']);
    }
}


/*
 * Execute query
 */
if($limit){
    $query .= ' ORDER BY `users`.`name` LIMIT '.$limit;
}

$r = sql_query($query, $execute);


/*
 * Build HTML
 */
$html = '   <form action="'.domain(true).'" method="post">
                <div class="row">
                    <div class="col-md-12">
                        <section class="panel">
                            <header class="panel-heading">
                                <h2 class="panel-title">'.tr('Deleted users').'</h2>
                                <p>
                                    '.html_flash().'
                                    <div class="row">
                                        <div class="col-sm-3">
                                            '.html_select($views).'
                                        </div>
                                        <div class="col-sm-3">
                                            '.html_select($roles).'
                                        </div>
                                    </div>
                                </p>
                            </header>
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
                            '.((isset_get($_POST['view']) == 'all') ? '<th>'.tr('Status').'</th>' : '').'
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
                        '.((isset_get($_POST['view']) == 'all') ? '<td>'.status($user['status']).'</a></td>' : '').'
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
                </section>
            </div>
        </div>
    </form>';

log_database('Viewed users', 'users_viewed');

$params = array('icon'        => 'fa-users',
                'title'       => tr('Users'),
                'breadcrumbs' => array(tr('Users'), tr('Manage')));

echo ca_page($html, $params);
?>
