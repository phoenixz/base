<?php
require_once(dirname(__FILE__).'/../libs/startup.php');

rights_or_redirect('admin');
load_libs('paging');


/*
 * Process requested actions
 */
try{
    switch(isset_get($_POST['action'])){
        case '':
            break;

        case 'submit':
                foreach($_POST as $key => $value) {
                    if((substr($key,0,3) == 'tr-')) {
                        $id    = str_replace('tr-', '', $key);

                        if(empty($value) and empty($_POST['alttrans-'.$id])){
                            continue;
                        }

                        if(empty($value))
                            $value = $_POST['alttrans-'.$id];

                        $entry = sql_get('SELECT * FROM `dictionary`
                                          WHERE id = :id',
                                          array(':id' => $id));
                        if(!$entry){
                            throw new bException('Error getting entry from dictionary', 'data_not_found');
                        }
                        if($entry['translation'] != $value){
                            sql_query('UPDATE `dictionary`
                                       SET `translation` = :translation,
                                                `status` = "translated"

                                       WHERE id = :id',
                                       array(':id'          => $id,
                                             ':translation' => $value));
                        }
                    }
                }
            break;

        case 'mark_as_translated':

            if(empty($_POST['id'])){
                throw new bException('Cannot erase users, no users selected', 'notspecified');
            }

            if(!is_array($_POST['id'])){
                throw new bException('Cannot erase users, invalid data specified', 'invalid');
            }

            foreach ($_POST['id'] as $id) {
                sql_query('UPDATE `dictionary`
                           SET `status` = "translated"
                           WHERE   `id` = :id',
                           array(':id' => $id));
            }
            break;

        case 'mark_as_untranslated':
            if(empty($_POST['id'])){
                throw new bException('Cannot erase users, no users selected', 'notspecified');
            }

            if(!is_array($_POST['id'])){
                throw new bException('Cannot erase users, invalid data specified', 'invalid');
            }

            foreach ($_POST['id'] as $id) {
                sql_query('UPDATE `dictionary`
                           SET `status` = NULL
                           WHERE   `id` = :id',
                           array(':id' => $id));
            }
            break;

        case 'delete':
            if(empty($_POST['id'])){
                throw new bException('Cannot erase users, no users selected', 'notspecified');
            }

            if(!is_array($_POST['id'])){
                throw new bException('Cannot erase users, invalid data specified', 'invalid');
            }

            foreach ($_POST['id'] as $id) {
                sql_query('UPDATE `dictionary`
                           SET `status` = "deleted"
                           WHERE   `id` = :id',
                           array(':id' => $id));
            }
            break;

        case 'undelete':
            if(empty($_POST['id'])){
                throw new bException('Cannot erase users, no users selected', 'notspecified');
            }

            if(!is_array($_POST['id'])){
                throw new bException('Cannot erase users, invalid data specified', 'invalid');
            }

            foreach ($_POST['id'] as $id) {
                sql_query('UPDATE `dictionary`
                           SET `status` = NULL
                           WHERE   `id` = :id',
                           array(':id' => $id));
            }
            break;

        case 'erase':

            if(empty($_POST['id'])){
                throw new bException('Cannot erase users, no users selected', 'notspecified');
            }

            if(!is_array($_POST['id'])){
                throw new bException('Cannot erase users, invalid data specified', 'invalid');
            }

            foreach ($_POST['id'] as $id) {
                sql_query('DELETE FROM `dictionary`
                           WHERE   `id` = :id',
                           array(':id' => $id));
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
$projects   = array('name'       => 'project',
                    'class'      => 'filter form-control mb-xs mt-xs mr-xs btn btn-default dropdown-toggle',
                    'none'       => tr('All projects'),
                    'autosubmit' => true,
                    'selected'   => isset_get($_GET['project']),
                    'resource'   => sql_list('SELECT `name` AS `id`, `name` FROM `projects` ORDER BY `name`'));

$status   = array('name'        => 'status',
                  'class'       => 'filter form-control mb-xs mt-xs mr-xs btn btn-default dropdown-toggle',
                  'none'        => tr('Active'),
                  'autosubmit'  => true,
                  'selected'    => isset_get($_GET['status']),
                  'resource'    => array('translated' => 'Translated', 'deleted' => 'Deleted', 'all' => 'All'));

$languages   = array('name'       => 'language',
                     'class'      => 'filter form-control mb-xs mt-xs mr-xs btn btn-default dropdown-toggle',
                     'none'       => tr('All languages'),
                     'autosubmit' => true,
                     'selected'   => isset_get($_GET['language']),
                     'resource'   => sql_list('SELECT `language` AS `id`, `language` FROM `dictionary` GROUP BY `language`'));


/*
 * Build query
 */
$execute = array();

$query   = 'SELECT `dictionary`.`id`,
                   `dictionary`.`code`,
                   `dictionary`.`string`,
                   `dictionary`.`translation`,
                   `dictionary`.`language`,
                   `dictionary`.`status`,
                   `dictionary`.`file`,
                   `projects`.`name`,
                   `projects`.`id` AS `projects_id`

            FROM `dictionary`

            LEFT JOIN `projects`
            ON `projects_id` = `projects`.`id`';

$paging  = 'SELECT COUNT(`dictionary`.`id`) AS `count`,
                   `dictionary`.`string`,
                   `dictionary`.`translation`,
                   `projects`.`name`

            FROM   `dictionary`

            LEFT JOIN `projects`
            ON `projects_id` = `projects`.`id`';


/*
 * Apply project filter
 */
if(isset_get($_GET['project'])){
    if($_GET['project'] != 'none'){
        $where[]             = ' `projects`.`name` = :project';
        $execute[':project'] = cfm($_GET['project']);
    }
}



/*
 * Apply status filter
 */
$default_actions = array('submit'                    => tr('Submit translations'),
                         'mark_as_translated'        => tr('Mark translations as translated'),
                         'mark_as_untranslated'      => tr('Mark translations as untranslated'),
                         'delete'                    => tr('Delete selected translations'),
                         'undelete'                  => tr('Undelete selected translations'),
                         'erase'                     => tr('Permantly delete translations'));

switch(isset_get($_GET['status'])){
    case '':

    case 'active':
        $where[] = '`dictionary`.`status` IS NULL';
        $actions = array('name'       => 'action',
                         'class'      => 'form-action input-sm',
                         'none'       => tr('Action'),
                         'autosubmit' => true,
                         'resource'   => array('submit'             => tr('Submit translations'),
                                               'mark_as_translated' => tr('Mark translations as translated'),
                                               'delete'             => tr('Delete selected translations'),));
        break;

    case 'all':
        $actions = array('name'       => 'action',
                         'class'      => 'form-action input-sm',
                         'none'       => tr('Action'),
                         'autosubmit' => true,
                         'resource'   => array('submit'               => tr('Submit translations'),
                                               'mark_as_translated'   => tr('Mark translations as translated'),
                                               'mark_as_untranslated' => tr('Mark translations as untranslated'),
                                               'delete'               => tr('Delete selected translations'),
                                               'undelete'             => tr('Undelete selected translations')));
        break;

    case 'translated':
        $where[] = '`dictionary`.`status` = "translated"';
        $actions = array('name'       => 'action',
                         'class'      => 'form-action input-sm',
                         'none'       => tr('Action'),
                         'autosubmit' => true,
                         'resource'   => array('submit'               => tr('Submit translations'),
                                               'mark_as_untranslated' => tr('Mark translations as untranslated'),
                                               'delete'               => tr('Delete selected translations')));
        $where[] = '`dictionary`.`status` = "translated"';
        break;

    case 'untranslated':
        $where[] = '`dictionary`.`status` = "untranslated"';
        $actions = array('name'       => 'action',
                         'class'      => 'form-action input-sm',
                         'none'       => tr('Action'),
                         'autosubmit' => true,
                         'resource'   => array('submit'               => tr('Submit translations'),
                                               'mark_as_translated'   => tr('Mark translations as translated'),
                                               'delete'               => tr('Delete selected translations')));
        break;

    case 'deleted':
        $where[] = '`dictionary`.`status` = "deleted"';
        $actions = array('name'       => 'action',
                         'class'      => 'form-action input-sm',
                         'none'       => tr('Action'),
                         'autosubmit' => true,
                         'resource'   => array('submit'               => tr('Submit translations'),
                                               'undelete'             => tr('Undelete selected translations'),
                                               'erase'                => tr('Permantly delete translations')));
        break;

    default:
        html_flash_set('Unknown status filter "'.str_log($_GET['status']).'" specified', 'error');
        redirect(true);
}

/*
 * Apply language filter
 */
if(isset_get($_GET['language'])){
    if($_GET['language'] != 'none'){
        $where[]              = ' `dictionary`.`language` = :language';
        $execute[':language'] = cfm($_GET['language']);
    }
}


/*
 * Apply generic filter
 */
if(!empty($_GET['filter'])){
    $where[]                 = ' (`dictionary`.`string` LIKE :filter OR `dictionary`.`language` LIKE :filter OR `dictionary`.`translation` LIKE :filter OR `dictionary`.`file` LIKE :filter OR `projects`.`name` LIKE :filter)';
    $execute[':filter']      = '%'.$_GET['filter'].'%';
}


/*
 * Execute query
 */
if(!empty($where)){
    $query  .= ' WHERE '.implode(' AND ', $where);
    $paging .= ' WHERE '.implode(' AND ', $where);
}

$paging = paging_data(isset_get($_GET['page']), isset_get($_GET['limit']), sql_get($paging, 'count', isset_get($execute)));

$query .= ' ORDER BY `dictionary`.`projects_id`,`dictionary`.`file`'.$paging['query'];

$r      = sql_query($query, $execute);


/*
 * Build HTML
 */
$html = '   <div class="row">
                <div class="col-md-12">
                    <section class="panel">
                        <header class="panel-heading">
                            <h2 class="panel-title">'.tr('Manage translations').'</h2>
                            <p>
                                '.html_flash().'
                                <form action="'.domain(true).'" method="get">
                                    <div class="row">
                                        <div class="col-sm-2">
                                            '.html_select($projects).'
                                        </div>
                                        <div class="visible-xs mb-md"></div>
                                        <div class="col-sm-2">
                                            '.html_select($status).'
                                        </div>
                                        <div class="visible-xs mb-md"></div>
                                        <div class="col-sm-2">
                                            '.html_select($languages).'
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
    $html .= '<p>'.tr('No translations were found with the current filter').'</p>';

}else{
    $html .= '  <div class="table-responsive">
                    <table class="select table mb-none table-striped table-hover">
                        <thead>
                            <th class="select">
                                <input type="checkbox" name="id[]" class="all"></th>
                            '.((isset_get($_GET['project'])  == '')    ? '<th>'.tr('Project').'</th>'  : '').'
                            '.((isset_get($_GET['status'])   == 'all') ? '<th>'.tr('Status').'</th>'   : '').'
                            '.((isset_get($_GET['language']) == '')    ? '<th>'.tr('Language').'</th>' : '').'
                            <th>'.tr('File').'</th>
                            <th>'.tr('String').'</th>
                            <th>'.tr('Translation').'</th>
                            <th>'.tr('Alternative translation').'</th>
                        </thead>';

    while($entry = sql_fetch($r)){

        $alt_trans = sql_query('SELECT DISTINCT `translation`
                                FROM   `dictionary`

                                WHERE        `code` = :code
                                AND        `status` = "translated"
                                AND           `id` != :id
                                AND  `translation` != :translation',

                                array(':code'        => cfm($entry['code']),
                                      ':id'          => $entry['id'],
                                      ':translation' => isset_get($entry['translation'], "")));
        $html .= '  <tr>
                        <td class="select"><input type="checkbox" name="id[]" value="'.$entry['id'].'"'.(in_array($entry['id'], (array) isset_get($_POST['id'])) ? ' checked' : '').'></td>
                        <input type="hidden" name="lang-'.$entry['id'].'" value="'.$entry['language'].'">
                        '.((isset_get($_GET['project']) == '')   ? '<td>'.$entry['name'].'</td>'           : '').'
                        '.((isset_get($_GET['status']) == 'all') ? '<td>'.status($entry['status']).'</td>' : '').'
                        '.((isset_get($_GET['language']) == '')  ? '<td>'.$entry['language'].'</td>'       : '').'
                        <td>'.$entry['file'].'</td>
                        <td>'.$entry['string'].'</td>
                        <td>
                            <textarea name="tr-'.$entry['id'].'">'.$entry['translation'].'</textarea>
                        </td>
                        <td>';

        if(sql_num_rows($alt_trans)){
            $html .= '      <select style="width:200px;" class="alttrans" name="alttrans-'.$entry['id'].'">
                                 <option value="0">'.tr('Select alternative translation').'</option>';

            while($alt = sql_fetch($alt_trans)) {
                $html .= '       <option value="'.addslashes($alt['translation']).'">'.addslashes($alt['translation']).'</option>';
            }

            $html .= '      </select>';
        }

        $html .= '</tr>';
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

log_database('Viewed translations', 'translations_viewed');

$params = array('icon'        => 'fa-language',
                'title'       => tr('Translation'),
                'breadcrumbs' => array(tr('Translation')));

echo ca_page($html, $params);
?>
