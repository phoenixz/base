<?php
require_once(dirname(__FILE__).'/../libs/startup.php');

right_or_redirect('admin,users');
load_libs('admin');

try{
    switch(isset_get($_POST['action'])){
        case 'add':
            redirect(domain('/admin/right.php'));
            break;

        case 'erase':
            /*
             * Erase the specified rights
             */
            if(empty($_POST['id'])){
                throw new lsException('Cannot erase rights, no rights selected', 'notspecified');
            }

            if(!is_array($_POST['id'])){
                throw new lsException('Cannot erase rights, invalid data specified', 'invalid');
            }

            $in = sql_in($_POST['id'], ':id');
            $r  = sql_query('DELETE FROM `users_rights` WHERE `id` IN ('.implode(',', array_keys($in)).')', $in);

            if(!$r->rowCount()){
                html_flash_set('No user rights have been removed', 'error');

            }else{
                log_database('Removed rights "'.json_encode_custom($_POST['id']).'"', 'removeuserright');
                html_flash_set('"'.$r->rowCount().'" user rights have been erased', 'error');
            }

            break;
    }

}catch(Exception $e){
    html_flash_set($e);
}

$action  = array('name'       => 'action',
                 'none'       => tr('Action'),
                 'autosubmit' => true,
                 'resource'   => array('add'   => tr('Add right'),
                                      'erase' => tr('Erase rights')));

$users   = array('name'       => 'user',
                 'class'      => 'filter',
                 'none'       => tr('All users'),
                 'autosubmit' => true,
                 'selected'   => isset_get($_POST['user']),
                 'resource'   => sql_query('SELECT `name` AS `id`, `name` FROM `users` WHERE `status` IS NULL'));

$rights  = array('name'       => 'right',
                 'class'      => 'filter',
                 'none'       => tr('All rights'),
                 'autosubmit' => true,
                 'selected'   => isset_get($_POST['right']),
                 'resource'   => sql_query('SELECT `name` AS `id`, `name` FROM `rights`'));

$execute = array();

$limit   = 50;

$html    = '<h2>'.tr('User rights').'</h2>
                <form action="'.domain(true).'" method="post">
                                    '.html_select($users).'
                                    '.html_select($rights);

$query   = 'SELECT    `users_rights`.`id`,
                      `users_rights`.`addedby`,
                      `users_rights`.`addedon`,
                      `users_rights`.`name` AS `right`,
                      `users`.`name`        AS `user`,
                      `addedby`.`name`      AS `addedby`

            FROM      `users_rights`

            LEFT JOIN `users`
            ON        `users`.`id`   = `users_rights`.`users_id`

            LEFT JOIN `users` AS addedby
            ON        `addedby`.`id` = `users_rights`.`addedby`

            ORDER BY  `users`.`name` ASC, `right` ASC';

if(!empty($_POST['user'])){
    $query  .= ' WHERE `users`.`name` = :user';
    $execute = array(':user' => $_POST['user']);
}

if(!empty($_POST['right'])){
    $query  .= ' WHERE `users_rights`.`name` = :right';
    $execute = array(':right' => $_POST['right']);
}

$r = sql_query($query, $execute);

if(!$r->rowCount()){
    $html .= '<p>'.tr('There are currently no user registered').'</p>';

}else{
    $html .= '<table class="link select"><thead><td class="select"><input type="checkbox" name="id[]" class="all"></td><td>'.tr('Username').'</td><td>'.tr('Right').'</td><td>'.tr('Given by').'</td><td>'.tr('Given on').'</td></thead>';

    while($right = sql_fetch($r)){
        $html .= '  <tr>
                        <td class="select"><input type="checkbox" name="id[]" value="'.$right['id'].'"></td>
                        <td>'.$right['user'].'</td>
                        <td>'.$right['right'].'</td>
                        <td>'.$right['addedby'].'</td>
                        <td>'.$right['addedon'].'</td>
                    </tr>';
    }

    $html .= '</table>';
}

$html .= '  </table>'.
                html_select($action);

log_database('Viewed user rights', 'viewuserrights');

echo admin_start(tr('Admin Dashboard')).
    $html.
    admin_end();
?>
