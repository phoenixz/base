<?php
require_once(dirname(__FILE__).'/../libs/startup.php');

$limit = 50;

rights_or_redirect('admin,rights');


/*
 * Process requested actions
 */
try{
    switch(isset_get($_POST['action'])){
        case '':
            break;

        case 'create':
            redirect(domain('/admin/right.php'));

        case 'delete':
            /*
             * Erase the specified rights
             */
            if(empty($_POST['id'])){
                throw new bException('Cannot erase rights, no rights selected', 'notspecified');
            }

            if(!is_array($_POST['id'])){
                throw new bException('Cannot erase rights, invalid data specified', 'invalid');
            }

            $in = sql_in($_POST['id'], ':id');
            $r  = sql_query('UPDATE `rights` SET `status` = "deleted" WHERE `id` IN ('.implode(',', array_keys($in)).')', $in);

            if(!$r->rowCount()){
                html_flash_set('No user rights have been deleted', 'warning');

            }else{
                html_flash_set(log_database('Deleted "'.$r->rowCount().'" rights "', 'rights_deleted'), 'success');
            }

            break;

        case 'undelete':
            /*
             * Erase the specified rights
             */
            if(empty($_POST['id'])){
                throw new bException('Cannot undelete rights, no rights selected', 'notspecified');
            }

            if(!is_array($_POST['id'])){
                throw new bException('Cannot undelete rights, invalid data specified', 'invalid');
            }

            $in = sql_in($_POST['id'], ':id');
            $r  = sql_query('UPDATE `rights` SET `status` = NULL WHERE `id` IN ('.implode(',', array_keys($in)).')', $in);

            if(!$r->rowCount()){
                html_flash_set('No user rights have been undeleted', 'warning');

            }else{
                html_flash_set(log_database('Undeleted "'.$r->rowCount().'" rights "', 'rights_undeleted'), 'success');
            }

            break;

        case 'erase':
            /*
             * Erase the specified rights
             */
            if(empty($_POST['id'])){
                throw new bException('Cannot erase rights, no rights selected', 'notspecified');
            }

            if(!is_array($_POST['id'])){
                throw new bException('Cannot erase rights, invalid data specified', 'invalid');
            }

            $in = sql_in($_POST['id'], ':id');
            $r  = sql_query('DELETE FROM `rights` WHERE `id` IN ('.implode(',', array_keys($in)).')', $in);

            if(!$r->rowCount()){
                html_flash_set('No user rights have been erased', 'warning');

            }else{
                html_flash_set(log_database('Erased "'.$r->rowCount().'" rights "', 'rights_erased'), 'success');
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
 * Select sections dependant on the view
 */
switch(isset_get($_POST['view'])){
    case '':
        // FALLTHROUGH
    case 'normal':
        $where   = ' WHERE `rights`.`status` IS NULL';

        $actions = array('name'       => 'action',
                         'none'       => tr('Action'),
                         'autosubmit' => true,
                         'resource'   => array('create' => tr('Create new right'),
                                               'delete' => tr('Delete selected rights')));

        break;

    case 'deleted':
        $where   = ' WHERE `rights`.`status` = "deleted"';

        $actions = array('name'       => 'action',
                         'none'       => tr('Action'),
                         'autosubmit' => true,
                         'resource'   => array('undelete' => tr('Undelete selected rights'),
                                               'erase'    => tr('Erase selected rights')));

    default:
        html_flash_set('Unknown view filter "'.str_log($_POST['view']).'" specified', 'error');
        redirect(true);
}


/*
 * Setup filters
 */
$views    = array('name'       => 'view',
                  'none'       => false,
                  'class'      => 'filter form-control mb-xs mt-xs mr-xs btn btn-default dropdown-toggle',
                  'autosubmit' => true,
                  'selected'   => isset_get($_POST['view']),
                  'resource'   => array('normal'  => tr('View normal rights'),
                                        'deleted' => tr('View deleted rights')));

/*
 * Build and execute query
 */
$execute = array();

$query   = 'SELECT    `rights`.`id`,
                      `rights`.`name`,
                      `rights`.`description`,
                      `rights`.`createdon`,
                      `users`.`name` AS `createdby`

            FROM      `rights`

            LEFT JOIN `users`
            ON        `users`.`id`   = `rights`.`createdby`'.$where;

if(!empty($_POST['right'])){
    $query  .= ' AND `rights`.`name` = :right';
    $execute = array(':right' => $_POST['right']);
}

$r = sql_query($query.' ORDER BY  `rights`.`name` ASC', $execute);


/*
 * Build HTML
 */
$html    = '<form action="'.domain(true).'" method="post">
                <div class="row">
                    <div class="col-md-12">
                        <section class="panel">
                            <header class="panel-heading">
                                <h2 class="panel-title">'.tr('User rights').'</h2>
                                <p>
                                    '.html_flash().'
                                    <div class="form-group">
                                        <div class="col-sm-8">
                                            <div class="row">
                                                <div class="col-sm-4">
                                                    '.html_select($views).'
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </p>
                            </header>
                            <div class="panel-body">';

if(!$r->rowCount()){
    $html .= '<p>'.tr('No rights were found with the current filter').'</p>';

}else{
    $html .= '  <div class="table-responsive">
                    <table class="select link table mb-none table-striped table-hover">
                        <thead>
                            <th class="select"><input type="checkbox" name="id[]" class="all"></th>
                            <th>'.tr('Name').'</th>
                            <th>'.tr('Created by').'</th>
                            <th>'.tr('Created on').'</th>
                            <th>'.tr('Description').'</th>
                            <th>'.tr('Rights').'</th>
                        </thead>';

    while($right = sql_fetch($r)){
        $a                 = '<a href="'.domain('/admin/right.php?right='.$right['name']).'">';

        $right['createdon'] = new DateTime($right['createdon']);
        $right['createdon'] = $right['createdon']->format($_CONFIG['formats']['human_datetime']);

        $html .= '  <tr>
                        <td class="select"><input type="checkbox" name="id[]" value="'.$right['id'].'"></td>
                        <td>'.$a.$right['name'].'</a></td>
                        <td>'.$a.$right['createdby'].'</a></td>
                        <td>'.$a.$right['createdon'].'</a></td>
                        <td>'.$a.$right['description'].'</a></td>
                    </tr>';
    }

    $html .= '  </table>
            </div>';
}

$html .=                    html_select($actions).'
                        </div>
                    </section>
                </div>
            </div>
        </form>';

log_database('Viewed user rights', 'rights_viewed');

$params = array('icon'        => 'fa-lock',
                'title'       => tr('rights'),
                'breadcrumbs' => array(tr('rights'), tr('Manage')));

echo ca_page($html, $params);
?>
