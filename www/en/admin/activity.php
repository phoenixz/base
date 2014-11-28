<?php
require_once(dirname(__FILE__).'/../libs/startup.php');

right_or_redirect('admin,activity');

$users  = array('name'       => 'user',
                'class'      => 'filter form-control mb-md',
                'none'       => 'Filter for user',
                'autosubmit' => true,
                'selected'   => isset_get($_POST['user']),
                'resource'   => sql_query('SELECT `id`, `name` FROM `users` WHERE `status` IS NULL AND `type` IS NULL'));

$limit  = 500;

$query  = 'SELECT    `log`.*,
                               `users`.`name`

                     FROM      `log`

                     LEFT JOIN `users`
                     ON        `users`.`id` = `log`.`users_id` '.

                     (!empty($_POST['user']) ? ' WHERE `log`.`users_id` = :users_id ' : '').

                     'ORDER BY  `log`.`added` DESC '.

                     ($limit ? ' LIMIT '.$limit : '');

$r      = sql_query($query, (!empty($_POST['user']) ? array(':users_id' => $_POST['user']) : null));

$html   = ' <form action="'.domain(true).'" method="post">
                <div class="row">
                    <div class="col-md-12">
                        <section class="panel">
                            <header class="panel-heading">
                                <h2 class="panel-title">'.tr('Activity log').'</h2>
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
                            </header>
                            <div class="panel-body">';

if(!$r->rowCount()){
    $html .= '<p>'.tr('No log activities found').'</p>';

}else{
    $html .= '  <div class="table-responsive">
                    <table class="select table mb-none table-striped">
                        <thead>
                            <th>'.tr('Date').'</th>
                            <th>'.tr('Name').'</th>
                            <th>'.tr('Type').'</th>
                            <th>'.tr('Message').'</th>
                        </thead>';

    while($log = sql_fetch($r)){
        $a = '<a href="'.domain('/admin/club.php?referral='.$log['id']).'">';

        $html .= '  <tr>
                        <td style="white-space: nowrap;">'.$log['added'].'</a></td>
                        <td>'.$log['name'].'</a></td>
                        <td>'.$log['type'].'</a></td>
                        <td>'.$log['message'].'</a></td>
                    </tr>';
    }

    $html .= '  </table>
            </div>';
}

$html .= '              </div>
                    </section>
                </div>
            </div>
        </form>';

log_database('Viewed activity log', 'viewactivitylog');

$params = array('icon'        => 'fa-file-text-o',
                'title'       => tr('Activity log'),
                'breadcrumbs' => array(tr('Activitylog')));

echo ca_page($html, $params);
?>
