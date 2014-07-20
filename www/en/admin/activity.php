<?php
require_once(dirname(__FILE__).'/../libs/startup.php');

right_or_redirect('admin');
load_libs('admin');

$users  = array('name'       => 'user',
                'class'      => 'filter',
                'none'       => 'Filter for user',
                'autosubmit' => true,
                'selected'   => isset_get($_POST['user']),
                'resource'   => sql_query('SELECT `id`, `name` FROM `users` WHERE `status` IS NULL'));

$limit  = 500;

$html   = ' <h2>'.tr('Activity log').'</h2>
                <form action="'.domain(true).'" method="post">'.
                    html_select($users);

$query  = 'SELECT    `log`.*,
                               `users`.`name`

                     FROM      `log`

                     LEFT JOIN `users`
                     ON        `users`.`id` = `log`.`users_id` '.

                     (!empty($_POST['user']) ? ' WHERE `log`.`users_id` = :users_id ' : '').

                     'ORDER BY  `log`.`added` DESC '.

                     ($limit ? ' LIMIT '.$limit : '');

$r      = sql_query($query, (!empty($_POST['user']) ? array(':users_id' => $_POST['user']) : null));

if(!$r->rowCount()){
    $html .= '<p>'.tr('No log activities found').'</p>';

}else{
    $html .= '<table class="link select"><thead><td>'.tr('Date').'</td><td>'.tr('Name').'</td><td>'.tr('Type').'</td><td>'.tr('Message').'</td></thead>';

    while($log = sql_fetch($r)){
        $a = '<a href="'.domain('/admin/club.php?referral='.$log['id']).'">';

        $html .= '  <tr>
                        <td style="white-space: nowrap;">'.$log['added'].'</a></td>
                        <td>'.$log['name'].'</a></td>
                        <td>'.$log['type'].'</a></td>
                        <td>'.$log['message'].'</a></td>
                    </tr>';
    }

    $html .= '</table>';
}

$html .= '</form>';

log_database('Viewed activity log', 'viewactivitylog');

echo admin_start(tr('Club management')).
    $html.
    admin_end();
?>
