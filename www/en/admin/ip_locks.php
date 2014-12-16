<?php
require_once(dirname(__FILE__).'/../libs/startup.php');

$limit = 50;

rights_or_redirect('admin,ip_lock');


/*
 * Process requested actions
 */
if(empty($_CONFIG['security']['signin']['ip_lock'])){
    /*
     * Build HTML
     */
    $html    = '<div class="row">
                    <div class="col-md-12">
                        <section class="panel">
                            <header class="panel-heading">
                                <h2 class="panel-title">'.tr('IP Locks').'</h2>
                            </header>
                            <div class="panel-body">
                                <p>'.tr('IP locking has been disabled in the configration, see $_CONFIG[security][signin][ip_lock]').'</p>
                            </div>
                        </section>
                    </div>
                </div>';

}elseif($_CONFIG['security']['signin']['ip_lock'] and !is_numeric($_CONFIG['security']['signin']['ip_lock'])){
    /*
     * Use a static IP
     */
    $html    = '<div class="row">
                    <div class="col-md-12">
                        <section class="panel">
                            <header class="panel-heading">
                                <h2 class="panel-title">'.tr('IP Locks').'</h2>
                            </header>
                            <div class="panel-body">
                                <p>'.tr('IP locking has been configured to use the static IP "'.str_log($_CONFIG['security']['signin']['ip_lock']).'", see $_CONFIG[security][signin][ip_lock]').'</p>
                            </div>
                        </section>
                    </div>
                </div>';

}else{
    /*
     * Use a dynamic IP, updated by users with ip_lcok right
     */
    try{
        switch(isset_get($_POST['action'])){
            case '':
                break;

            case 'specified':
                /*
                 * Add the new IP
                 */
                if(empty($_POST['ip'])){
                    throw new bException(tr('No IP specified'), 'not_specified');
                }

                load_libs('validate');

                if(!filter_var($_POST['ip'], FILTER_VALIDATE_IP)) {
                    throw new bException(tr('The specified IP "'.str_log($_POST['ip']).'" is not valid'), 'invalid');
                }

                /*
                 * This user can reset the iplock by simply logging in
                 */
                sql_query('INSERT INTO `ip_locks` (`createdby`, `ip`)
                           VALUES                 (:createdby , :ip )',

                           array(':createdby' => $_SESSION['user']['id'],
                                 ':ip'        => $_POST['ip']));

                html_flash_set(log_database('Updated IP lock to specified IP "'.str_log($_POST['ip']).'"', 'ip_locks_updated'), 'success');
                redirect(true);

            case 'current':
                /*
                 * Set the current IP
                 */
                sql_query('INSERT INTO `ip_locks` (`createdby`, `ip`)
                           VALUES                 (:createdby , :ip )',

                           array(':createdby' => $_SESSION['user']['id'],
                                 ':ip'        => $_SERVER['REMOTE_ADDR']));

                html_flash_set(log_database('Updated IP lock to current IP "'.str_log($_SERVER['REMOTE_ADDR']).'"', 'ip_locks_updated'), 'success');
                redirect(true);

            case 'erase':
                /*
                 * Erase the specified ip_locks
                 */
                if(empty($_POST['id'])){
                    throw new bException('Cannot erase IP locks, no IP locks selected', 'notspecified');
                }

                if(!is_array($_POST['id'])){
                    throw new bException('Cannot erase IP locks, invalid data specified', 'invalid');
                }

                $in = sql_in($_POST['id'], ':id');
                $r  = sql_query('DELETE FROM `ip_locks` WHERE `id` IN ('.implode(',', array_keys($in)).')', $in);

                if(!$r->rowCount()){
                    html_flash_set('No user IP locks have been erased', 'warning');

                }else{
                    html_flash_set(log_database('Erased "'.$r->rowCount().'" IP locks "', 'ip_locks_erased'), 'success');
                }

                redirect(true);

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
     * Setup the action button
     */
    $actions = array('name'       => 'action',
                     'none'       => tr('Action'),
                     'class'      => 'col-md-1 control-label',
                     'autosubmit' => true,
                     'resource'   => array('specified' => tr('Set specified IP'),
                                           'current'   => tr('Set current IP'),
                                           'erase'     => tr('Erase selected IP\'s')));


    /*
     * Setup filters
     */
    $users    = array('name'       => 'user',
                      'none'       => 'Filter by IP Lock enabled user',
                      'class'      => 'filter form-control mb-xs mt-xs mr-xs btn btn-default dropdown-toggle',
                      'autosubmit' => true,
                      'selected'   => isset_get($_POST['view']),
                      'resource'   => sql_query('SELECT   `users`.`username` AS `id`,
                                                          `users`.`name`

                                                 FROM     `users`

                                                 JOIN     `users_rights`
                                                 ON      (`users_rights`.`name`     = "ip_lock"
                                                 OR       `users_rights`.`name`     = "god")
                                                 AND      `users_rights`.`users_id` = `users`.`id`

                                                 ORDER BY `users`.`name`'));


    /*
     * Build and execute query
     */
    $execute = array();

    $query   = 'SELECT    `ip_locks`.`id`,
                          `ip_locks`.`createdon`,
                          `ip_locks`.`ip`,
                          `users`.`username`,
                          `users`.`name` AS `createdby`

                FROM      `ip_locks`

                LEFT JOIN `users`
                ON        `users`.`id`   = `ip_locks`.`createdby`';

    if(!empty($_POST['user'])){
        $query  .= ' AND `users`.`name` = :user';
        $execute = array(':user' => $_POST['user']);
    }

    $r = sql_query($query.' ORDER BY  `ip_locks`.`id` DESC LIMIT 100', $execute);


    /*
     * Build HTML
     */
    $html    = '<form action="'.domain(true).'" method="post">
                    <div class="row">
                        <div class="col-md-12">
                            <section class="panel">
                                <header class="panel-heading">
                                    <h2 class="panel-title">'.tr('IP Locks').'</h2>
                                    <p>
                                        '.html_flash().'
                                        <div class="form-group">
                                            <div class="col-sm-8">
                                                <div class="row">
                                                    <div class="col-sm-4">
                                                        '.html_select($users).'
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </p>
                                    <p>
                                        '.tr('There are up to "%count%" IP\'s locked for sign in. All green entries are locked and available', array('%count%' => $_CONFIG['security']['signin']['ip_lock'])).'
                                    </p>
                                </header>
                                <div class="panel-body">';

    if(!$r->rowCount()){
        $html .= '<p>'.tr('No IP locks with the current filter').'</p>';

    }else{
        $count = $_CONFIG['security']['signin']['ip_lock'];

        $html .= '  <div class="table-responsive">
                        <table class="select link table mb-none table-striped table-hover table-striped">
                            <thead>
                                <th class="select"><input type="checkbox" name="id[]" class="all"></th>
                                <th>'.tr('Created by').'</th>
                                <th>'.tr('Created on').'</th>
                                <th>'.tr('IP').'</th>
                            </thead>';

        while($ip = sql_fetch($r)){
            if($count-- > 0){
                $class = ' class="confirmed"';

            }else{
                $class = '';
            }

            $a                 = '<a href="'.domain('/admin/user.php?user='.$ip['username']).'">';

            $ip['createdon'] = new DateTime($ip['createdon']);
            $ip['createdon'] = $ip['createdon']->format($_CONFIG['formats']['human_datetime']);

            if(!empty($_POST['right'])){
                /*
                 * Filter by the specified right.
                 */
                if(!in_array($_POST['right'], $ip['rights'])){
                    continue;
                }
            }

            $html .= '  <tr'.$class.'>
                            <td class="select"><input type="checkbox" name="id[]" value="'.$ip['id'].'"></td>
                            <td>'.$a.$ip['createdby'].'</a></td>
                            <td>'.$a.$ip['createdon'].'</a></td>
                            <td>'.$a.$ip['ip'].'</a></td>
                        </tr>';
        }

        $html .= '  </table>
                </div>';
    }

    $html .=                    html_select($actions).'
                                <div class="col-md-2">
                                    <input type="text" name="ip" id="ip" class="form-control" value="'.isset_get($club['address']).'" maxlength="15">
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </form>';

    log_database('Viewed IP locks', 'ip_locks_viewed');
}

$params = array('icon'        => 'fa-lock',
                'title'       => tr('IP Locks'),
                'breadcrumbs' => array(tr('Security'), tr('IP Locks')));

echo ca_page($html, $params);
?>
