<?php
require_once(dirname(__FILE__).'/../libs/startup.php');

array_ensure($_GET, 'blog', 'projects');
array_ensure($_GET, 'post', null);

$params['back']              = '/admin/projects.php';
$params['bodymin']           = 20;
$params['namemax']           = 255;
$params['categories_none']   = tr('Select a project');
$params['categories_parent'] = 'projects';
$params['groups_none']       = tr('Select a tracker');
$params['groups_parent']     = 'trackers';
$params['flash_created']     = tr('The ticket "%post%" has been created');
$params['flash_updated']     = tr('The ticket "%post%" has been updated');
$params['form_action']       = '/admin/project.php'.($_GET['post'] ? '?post='.$_GET['post'] : '');
$params['label_group']       = tr('Tracker');
$params['label_category']    = tr('Project');
$params['label_photos']      = tr('Save this ticket to be able to add separate photos');
$params['redirect']          = '/admin/projects.php?';
$params['script']            = 'projects.php';
$params['status_default']    = 'new';

$params['status_list']       = array('new'         => tr('New'),
                                     'verified'    => tr('Verified'),
                                     'inprogress'  => tr('In progress'),
                                     'testing'     => tr('Testing'),
                                     'solved'      => tr('Solved'),
                                     'wontfix'     => tr('Won\'t fix'),
                                     'closed'      => tr('Closed'));

$params['status_select']     = array('selected' => isset_get($_POST['status'], ''),
                                     'resource' => $params['status_list']);

$params['subtitle']          = (!empty($_GET['post']) ? tr('Edit project ticket') : tr('Create a new project ticket'));
//$params['subtitle']          = (!empty($_GET['post']) ? tr('Edit ticket for project "%category%"', '%category%', $category['name']) : tr('Create a new ticket in project "%category%"', '%category%', $category['name']));
$params['title']             = tr('Project tickets');
$params['use_description']   = false;
$params['use_keywords']      = false;
$params['use_status']        = true;
$params['use_url']           = true;
$params['use_groups']        = true;
$params['use_priorities']    = true;

$params['errors']['name_required']     = tr('Please provide the name of your ticket');
$params['errors']['category_required'] = tr('Please select a project for your ticket');
$params['errors']['body_required']     = tr('Please provide the body text of your ticket');
$params['errors']['status_required']   = tr('Please select the status of your ticket');
$params['errors']['group_required']    = tr('Please select a tracker for your ticket');

require_once(dirname(__FILE__).'/blogs_post.php');
?>
