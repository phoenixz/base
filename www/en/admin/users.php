<?php
require_once(dirname(__FILE__).'/../libs/startup.php');

right_or_redirect('admin,users');
load_libs('admin');

try{
    switch(isset_get($_POST['action'])){
        case 'add':
            redirect(domain('/admin/user.php'));
            break;

        case 'delete':
            /*
             * Delete the specified users
             */
            if(empty($_POST['id'])){
                throw new bException('Cannot delete users, no users selected', 'notspecified');
            }

            if(!is_array($_POST['id'])){
                throw new bException('Cannot delete users, invalid data specified', 'invalid');
            }

            $in = sql_in($_POST['id'], ':id');
            $r  = sql_query('UPDATE `users` SET `status` = "deleted" WHERE `id` IN ('.implode(',', array_keys($in)).')', $in);

            if(!$r->rowCount()){
                html_flash_set('No users have been deleted', 'error');

            }else{
                log_database('Deleted users with data "'.json_encode_custom($_POST).'"', 'deleteusers');
                html_flash_set(tr('"%count%" user(s) have been deleted', '%count%', $r->rowCount()), 'success');
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

            $in = sql_in($_POST['id'], ':id');
            $r  = sql_query('DELETE FROM `users` WHERE `id` IN ('.implode(',', array_keys($in)).')', $in);

            if(!$r->rowCount()){
                html_flash_set('No users have been erased', 'error');

            }else{
                log_database('Erased users with data "'.json_encode_custom($_POST).'"', 'eraseusers');
                html_flash_set(tr('"%count%" user(s) have been erased', '%count%', $r->rowCount()), 'success');
            }

            break;

        case 'undelete':
            /*
             * Undelete the specified users
             */
            if(empty($_POST['id'])){
                throw new bException('Cannot undelete users, no users selected', 'notspecified');
            }

            if(!is_array($_POST['id'])){
                throw new bException('Cannot undelete users, invalid data specified', 'invalid');
            }

            $in = sql_in($_POST['id'], ':id');
            $r  = sql_query('UPDATE `users` SET `status` = NULL WHERE `id` IN ('.implode(',', array_keys($in)).')', $in);

            if(!$r->rowCount()){
                html_flash_set('No users have been undeleted', 'error');

            }else{
                log_database('Undeleted users with data "'.json_encode_custom($_POST).'"', 'undeleteusers');
                html_flash_set(tr('"%count%" user(s) have been undeleted', '%count%', $r->rowCount()), 'success');
            }

            break;
    }

}catch(Exception $e){
    html_flash_set($e);
}

$limit  = 50;

$action = array('name'       => 'action',
                'none'       => tr('Action'),
                'autosubmit' => true,
                'resource'   => array('add'     => tr('Add new user'),
                                      'delete'  => tr('Delete selected users')));

$filter = array('name'       => 'view',
                'class'      => 'filter',
                'none'       => tr('Active users'),
                'autosubmit' => true,
                'selected'   => isset_get($_POST['view']),
                'resource'   => array('deleted' => tr('Deleted users'),
                                      'all'     => tr('All users'),
                                      'empty'   => tr('Empty users')));

$query  = 'SELECT `id`,
                  `name`,
                  `admin`,
                  `username`,
                  `email`,
                  `status`

           FROM   `users`';

switch(isset_get($_POST['view'])){
    case 'all':
        $html   = '<h2>'.tr('All users').'</h2>';
        break;

    case 'empty':
        $query .= ' WHERE `users`.`status` = "empty"';
        $html   = '<h2>'.tr('Empty users').'</h2>';
        break;

    case 'deleted':
        $query .= ' WHERE `users`.`status` = "deleted"';
        $html   = '<h2>'.tr('Deleted users').'</h2>';

        $action = array('name'       => 'action',
                        'none'       => tr('Action'),
                        'autosubmit' => true,
                        'resource'   => array('erase'    => tr('Erase selected users'),
                                              'undelete' => tr('Undelete selected users')));

        break;

    default:
        $query .= ' WHERE `users`.`status` IS NULL';
        $html   = '<h2>'.tr('Active users').'</h2>';
}

if($limit){
    $query .= ' LIMIT '.$limit;
}

load_libs('user');

$r = sql_query($query);

$html .= '   <form action="'.domain(true).'" method="post">'.
               html_select($filter);

if(!$r->rowCount()){
    $html .= '<p>'.tr('There are currently no users registered').'</p>';

}else{
    $html .= '  <table class="link select">
                    <thead>
                        <td class="select">
                            <input type="checkbox" name="id[]" class="all"></td><td>'.tr('Username').'
                        </td>'.
                        ((isset_get($_POST['view']) == 'all') ? '<td>'.tr('Status').'</td>' : '').
                       '<td>'.tr('Admin').'</td>
                        <td>'.tr('Real name').'</td>
                        <td>'.tr('Email').'</td>
                    </thead>';

    while($user = sql_fetch($r)){
        $a = '<a href="'.domain('/admin/user.php?user='.$user['username']).'">';

        $html .= '  <tr>
                        <td class="select"><input type="checkbox" name="id[]" value="'.$user['id'].'"></td>
                        <td>'.$a.$user['username'].'</a></td>'.
                        ((isset_get($_POST['view']) == 'all') ? '<td>'.status($user['status']).'</a></td>' : '').
                       '<td>'.$a.($user['admin'] ? tr('Yes') : tr('No')).'</a></td>
                        <td>'.$a.$user['name'].'</a></td>
                        <td>'.$a.$user['email'].'</a></td>
                    </tr>';
    }

    $html .= '</table>';
}

$html .=        html_select($action).'
            </form>';

echo admin_start(tr('Admin Dashboard')).
    $html.
    admin_end();
?>
