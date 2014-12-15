<?php
require_once(dirname(__FILE__).'/../libs/startup.php');

rights_or_redirect('admin,activity');

$std_limit = 500;

$limit     = isset_get($_GET['limit'], $std_limit);

$users     = array('name'       => 'user',
                   'class'      => 'filter form-control mb-md',
                   'none'       => 'Filter for user',
                   'autosubmit' => true,
                   'selected'   => isset_get($_GET['user']),
                   'resource'   => sql_query('SELECT `id`, `name` FROM `users` WHERE `status` IS NULL AND `type` IS NULL'));

$execute   = array();


/*
 * Process filters
 */
if(!empty($_GET['user'])){
    $where[]              = ' `log`.`createdby` = :users_id ';
    $execute[':users_id'] = cfi($_GET['user']);
}

if(!empty($_GET['filter'])){
    $where[]          = ' (`log`.`type` LIKE :type OR `log`.`ip` LIKE :ip OR `users`.`name` LIKE :name)';
    $execute[':ip']   = '%'.$_GET['filter'].'%';
    $execute[':type'] = '%'.$_GET['filter'].'%';
    $execute[':name'] = '%'.$_GET['filter'].'%';
}

if(!empty($where)){
    $where = ' WHERE '.implode(' AND ', $where);
}


/*
 * Build query
 */
$query  = 'SELECT    `log`.`createdon`,
                     `log`.`type`,
                     `log`.`ip`,
                     `log`.`message`,
                     `users`.`name`

           FROM      `log`

           LEFT JOIN `users`
           ON        `users`.`id` = `log`.`createdby` '.

           isset_get($where, '').

           'ORDER BY  `log`.`createdon` DESC '.

           ($limit ? ' LIMIT '.$limit : '');

$r      = sql_query($query, $execute);

$html   = ' <div class="row">
                <div class="col-md-12">
                    <section class="panel">
                        <header class="panel-heading">
                            <h2 class="panel-title">'.tr('Activity log').'</h2>
                            <p>
                                '.html_flash().'
                                <div class="form-group">
                                    <div class="col-sm-8">
                                        <form action="'.domain(true).'" method="get">
                                            <div class="row">
                                                <div class="col-sm-3">
                                                    '.html_select($users).'
                                                </div>
                                                <div class="visible-xs mb-md"></div>
                                                <div class="col-sm-3">
                                                    <div class="input-group input-group-icon">
                                                        <input type="text" class="form-control col-md-3" name="filter" id="filter" value="'.str_log(isset_get($_GET['filter'], '')).'" placeholder="General filter">
                                                        <span class="input-group-addon">
                                                            <span class="icon"><i class="fa fa-search"></i></span>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="visible-xs mb-md"></div>
                                                <div class="col-sm-3">
                                                    <input type="text" class="form-control col-md-3" name="limit" id="limit" value="'.str_log(isset_get($_GET['limit'], '')).'" placeholder="'.tr('Row limit (default %entries% entries)', array('%entries%' => str_log($std_limit))).'">
                                                </div>
                                                <div class="visible-xs mb-md"></div>
                                                <div class="col-sm-3">
                                                    <input type="submit" class="mb-xs mr-xs btn btn-sm btn-primary" name="reload" id="reload" value="'.tr('Reload').'">
                                                </div>
                                                <div class="visible-xs mb-md"></div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </p>
                        </header>
                        <form action="'.domain(true).'" method="post">
                           <div class="panel-body">';

if(!$r->rowCount()){
    $html .= '<p>'.tr('No log activities found').'</p>';

}else{
    $html .= '  <div class="table-responsive">
                    <table class="select table mb-none table-striped">
                        <thead>
                            <th>'.tr('Date').'</th>
                            <th>'.tr('IP').'</th>
                            <th>'.tr('User').'</th>
                            <th>'.tr('Type').'</th>
                            <th>'.tr('Message').'</th>
                        </thead>';

    while($log = sql_fetch($r)){
        $html .= '  <tr>
                        <td style="white-space: nowrap;">'.$log['createdon'].'</a></td>
                        <td>'.$log['ip'].'</a></td>
                        <td>'.$log['name'].'</a></td>
                        <td>'.$log['type'].'</a></td>
                        <td>'.$log['message'].'</a></td>
                    </tr>';
    }

    $html .= '  </table>
            </div>';
}

$html .= '              </div>
                    </form>
                </section>
            </div>
        </div>';

log_database('Viewed activity log with filters "<a href="'.str_log(domain(true)).'">'.str_log(isset_get($_GET)).'</a>"', 'activity_log_viewed');

$params = array('icon'        => 'fa-file-text-o',
                'title'       => tr('Activity log'),
                'breadcrumbs' => array(tr('Activitylog')));

echo ca_page($html, $params);
?>
