<?php
require_once(dirname(__FILE__).'/../libs/startup.php');

page_404();

array_ensure($_GET, 'blog', 'projects');

$params['blogs_post']      = 'project.php?';
$params['categories_none'] = tr('Filter by project');
$params['groups_none']     = tr('Filter by tracker');

$params['columns']         = array('id'        => 'id',
                                   '#id'       => tr('Id'),
                                   'group'     => tr('Tracker'),
                                   'name'      => tr('Name'),
                                   'category'  => tr('Project'),
                                   'priority'  => tr('Priority'),
                                   'status'    => tr('Status'),
                                   'createdby' => tr('Created by'),
                                   'createdon' => tr('Created on'));

$params['filter_category'] = true;
$params['filter_group']    = true;
$params['form_action']     = '/admin/projects.php';
$params['object_name']     = 'ticket';
$params['script']          = 'projects.php';
$params['show_groups']     = 'trackers';
$params['show_categories'] = 'projects';
$params['status_default']  = 'unknown';

$params['status_list']     = array('new'         => tr('New'),
                                   'verified'    => tr('Verified'),
                                   'inprogress'  => tr('In progress'),
                                   'testing'     => tr('Testing'),
                                   'solved'      => tr('Solved'),
                                   'wontfix'     => tr('Won\'t fix'),
                                   'closed'      => tr('Closed'),
                                   'erased'      => tr('Erased'));

$params['status_set']      = html_status_select(array('selected'   => isset_get($_POST['status'], ''),
                                                      'none'       => tr('Set status'),
                                                      'autosubmit' => true,
                                                      'resource'   => $params['status_list']));

$params['subtitle']        = tr('Project tickets');
$params['title']           = tr('Project management');
$params['class']           = 'project';

require_once(dirname(__FILE__).'/blogs_posts.php');
?>
