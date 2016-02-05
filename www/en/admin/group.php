<?php
require_once(dirname(__FILE__).'/../libs/startup.php');

rights_or_redirect('admin,groups,modify');
load_libs('user,validate');

$group = array();


/*
 * Are we editing a group?
 * If so then get the group data from the DB
 */
if(!empty($_GET['group'])){


    if(is_numeric($_GET['group'])){
        /*
         * We are using an id
         */
        $db = sql_get('SELECT `groups`.`id`,
                              `groups`.`createdon`,
                              `groups`.`modifiedon`,
                              `groups`.`name`,
                              `groups`.`seoname`,
                              `groups`.`description`,
                              `groups`.`status`

                       FROM   `groups`

                       WHERE  `groups`.`id` = :group',

                       array(':group' => $_GET['group']));

    }else{
        /*
         * We are using the name
         */
        $db = sql_get('SELECT `groups`.`id`,
                              `groups`.`createdon`,
                              `groups`.`modifiedon`,
                              `groups`.`name`,
                              `groups`.`description`,
                              `groups`.`status`

                       FROM   `groups`

                       WHERE  `groups`.`seoname` = :group',

                       array(':group' => $_GET['group']));
    }

    if(!$db){
        html_flash_set(log_database(tr('Specified group "'.str_log($_GET['group']).'" does not exist'), 'group_not_exist'), 'error');
        redirect(domain('/admin/groups.php'));
    }

    log_database(tr('View group "'.str_log($_GET['group']).'"'), 'group_view');

    $group = array_merge($db, $group);
    unset($db);

    if($group['createdon']){
        $group['createdon']  = new DateTime($group['createdon']);
        $group['createdon']  = $group['createdon']->format($_CONFIG['formats']['human_datetime']);
    }

    if($group['modifiedon']){
        $group['modifiedon'] = new DateTime($group['modifiedon']);
        $group['modifiedon'] = $group['modifiedon']->format($_CONFIG['formats']['human_datetime']);
    }
}


/*
 * Was group data submitted?
 */
if(!empty($_POST['dosubmit'])){
    $group = array_merge($group, $_POST);
}


/*
 * Process group actions
 */
try{
    load_libs('seo');

    if(isset_get($_POST['docreate'])){
        /*
         * Validate data
         */
        s_validate_group($group);

        /*
         * Insert new group
         */
        sql_query('INSERT INTO `groups` (`createdby`, `name`, `seoname`, `description`)
                   VALUES               (:createdby , :name , :seoname , :description )',

                   array(':createdby'   => $_SESSION['user']['id'],
                         ':name'        => $group['name'],
                         ':seoname'     => seo_string($group['name']),
                         ':description' => $group['description']));

        html_flash_set(log_database('Created group "'.str_log(isset_get($group['name'])).'"', 'group_create'), 'success');
        redirect(domain('/admin/groups.php'));
    }

    if(isset_get($_POST['doupdate'])){
        if(empty($group['id'])){
            throw new bException('Cannot update, no group specified', 'notspecified');
        }

        /*
         * Update the group
         */
        $r = sql_query('UPDATE `groups`

                        SET    `modifiedby`  = :modifiedby,
                               `modifiedon`  = NOW(),
                               `status`      = :status,
                               `name`        = :name,
                               `description` = :description

                        WHERE  `id`          = :id',

                        array(':modifiedby'  => isset_get($_SESSION['user']['id']),
                              ':id'          => $group['id'],
                              ':name'        => $group['name'],
                              ':description' => $group['description']));

        /*
         * This update might have been done because of a create group action
         */
        if(!isset_get($_POST['docreate'])){
            html_flash_set(log_database('Updated group "'.str_log(isset_get($_POST['name'])).'"', 'group_update'), 'success');
            redirect(domain('/admin/group.php?group='.$group['name']));
        }

        $group = array();
    }

    if(isset_get($_POST['newmembers'])){
        if(empty($group['id'])){
            throw new bException('Cannot update, no group specified', 'notspecified');
        }

        $members = explode(',' , $_POST['members']);

        foreach($members as $member){
            /*
             * Get the user id for each reference number
             * and insert the user into users_groups table
             */
            $user = sql_get('SELECT `id`

                             FROM   `users`

                             WHERE  `reference_number` = :reference_number',

                             array(':reference_number' => $member), 'id');

            if(!empty($user)){
                try{
                    /*
                     * Insert the user and the group
                     */
                    sql_query('INSERT INTO `users_groups` (`users_id`, `groups_id`, `name`)
                               VALUES                     (:users_id , :groups_id , :name )',

                               array(':users_id'  => $user,
                                     ':groups_id' => $group['id'],
                                     ':name'      => $group['name']));

                    html_flash_set(tr('Added user with reference number ":number" to this group', array(':number' => $member)), 'success');

                }catch(Exception $e){
                    html_flash_set(tr('User with reference number ":number" is already in this group', array(':number' => $member)), 'error');
                }

            }else{
                html_flash_set(tr('Could not find user with reference number ":number"', array(':number' => $member)), 'error');
            }
        }
    }

    /*
     * Remove contacts from group
     */
    if(!empty($_POST['action']) and !empty($_POST['contact_remove'])){
        foreach($_POST['contact_remove'] as $contact){
            if($contact != 'on'){
                s_delete_user_group($contact, $group);
            }
        }
    }

}catch(Exception $e){
    html_flash_set($e);
}


$readonly = '';

if(!empty($group['id'])){
    if($group['status']){
        $readonly = 'readonly';
    }
}

/*
 * Build page HTML
 */
$html = '   <form name="group" id="group" action="'.domain(true).'" method="post">
                '.html_form().'
                '.html_hidden($group).'
                <div class="col-md-6">
                    <section class="panel">
                        <header class="panel-heading">
                            <h2 class="panel-title">'.(isset_get($group['id']) ? (empty($profile) ? tr('Update group') : tr('Manage your profile')) : tr('Create group')).'</h2>
                            <p>'.html_flash().'</p>
                        </header>
                        <div class="panel-body">';

if(!empty($group['id'])){
    $html .= '              <div class="form-group">
                                <label class="col-md-3 control-label" for="createdon">'.tr('Created on').'</label>
                                <div class="col-md-12">
                                    <input type="text" name="createdon" id="createdon" class="form-control" value="'.isset_get($group['createdon']).'" disabled>
                                </div>
                            </div>';
}

$html .= '                  <div class="form-group">
                                <label class="col-md-6 control-label" for="name">'.tr('Group name').'</label>
                                <div class="col-md-12">
                                    <input type="text" name="name" id="name" class="form-control" value="'.isset_get($group['name']).'" maxlength="255" autofocus '.$readonly.'>
                                </div>
                            </div>';

if(empty($profile)){
    $html .= '              <div class="form-group">
                                <label class="col-md-3 control-label" for="commentary">'.tr('Description').'</label>
                                <div class="col-md-12">
                                    <textarea name="description" id="description" class="form-control" maxlength="2047" rows="5" '.$readonly.'>'.isset_get($group['description']).'</textarea>
                                </div>
                            </div>';
}

$html .= '                  <div class="form-group">'.
                            (isset_get($group['id']) ? '<input type="submit" class="mb-xs mt-xs mr-xs btn btn-primary" name="doupdate" id="doupdate" value="'.tr('Update').'">'.
                                                       (empty($profile) ? '' : '')

                                                    : '<input type="submit" class="mb-xs mt-xs mr-xs btn btn-primary" name="docreate" id="docreate" value="'.tr('Create').'">').'
                            </div>
                        </div>
                    </section>
                </div>
            </form>';


if(!empty($group['id'])){
    /*
     * Build page HTML
     */
    $html .= '  <form name="add-member" id="add-member" action="'.domain(true).'" method="post">
                    '.html_form().'
                    '.html_hidden($group).'
                    <div class="col-md-6">
                        <section class="panel">
                            <header class="panel-heading">
                                <h2 class="panel-title">'.tr('Add members to this group').'</h2>
                                <p>'.html_flash().'</p>
                            </header>
                            <div class="panel-body">
                                <div class="form-group">
                                    <p>'.tr('You can add new members by typing the "Reference Number"').'</p>
                                    <p>'.tr('Also you can add multiple members by separating each one by a comma').'</p>
                                    <p>'.tr('Example: 120,30,70').'</p>
                                    <hr>
                                    <label class="col-md-8 control-label" for="members">'.tr('New Members').'</label>
                                    <div class="col-md-12">
                                        <input type="text" name="members" id="members" class="form-control" '.$readonly.'>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <input type="submit" class="mb-xs mt-xs mr-xs btn btn-primary" name="newmembers" id="newmembers" value="'.tr('Add').'">
                                </div>
                            </div>
                        </section>
                    </div>
                </form>';
}


if(!empty($group['id'])){
    $html .= ca_group_members($group);
}

/*
 * AddJS validation
 */
$vj = new validate_jquery();

$vj->validate('groupname' , 'minlength', '2'        , '<span class="FcbErrorTail"></span>'.tr('Please ensure that the groupname has at least 2 characters'));
$vj->validate('name'     , 'required' , 'true'     , '<span class="FcbErrorTail"></span>'.tr('Please provide a real name'));
$vj->validate('name'     , 'minlength', '2'        , '<span class="FcbErrorTail"></span>'.tr('Please ensure that the real name has at least 2 characters'));
$vj->validate('email'    , 'required' , 'true'     , '<span class="FcbErrorTail"></span>'.tr('Please provide your email address'));
$vj->validate('email'    , 'email'    , 'true'     , '<span class="FcbErrorTail"></span>'.tr('Please provide a valid email address'));
$vj->validate('password2', 'equalTo'  , '#password', '<span class="FcbErrorTail"></span>'.tr('The password fields need to be equal'));

$html .= $vj->output_validation(array('id'   => 'group',
                                      'json' => false));

$params = array('title'       => tr('Group'),
                'icon'        => 'fa-group',
                'breadcrumbs' => array(tr('Groups'), tr('Modify')));

echo ca_page($html, $params);


/*
 *
 */
function s_validate_group(&$group){
    try{
        $v = new validate_form($group, 'name,description');

        $v->isNotEmpty  ($group['name']     , tr('Please provide a name'));
        $v->hasMinChars ($group['name'],   4, tr('Please ensure that the name has a minimum of 4 characters'));
        $v->hasMaxChars ($group['name'],  16, tr('Please ensure that the name has a maximum of 16 characters'));

        if(!$v->isValid()) {
            throw new bException($v->getErrors(), 'invalid');
        }

    }catch(Exception $e){
        throw new bException('s_validate_group(): Failed', $e);
    }
}



/*
 *
 */
function s_delete_user_group($user_id, $group){
    /*
     * Get user data
     */
    $user = sql_get('SELECT `id`,
                            `name`,
                            `username`

                     FROM   `users`

                     WHERE  `id` = :id',

                     array(':id' => $user_id));

    if(empty($user)){
        throw new bException(tr('This user does not exists'));
    }

    // :DELETE: We already loaded the group information
    //$group = sql_get('SELECT `id`,
    //                         `name`
    //
    //                  FROM   `groups`
    //
    //                  WHERE  `id` = :id',
    //
    //                  array(':id' => $_GET['group']));
    //
    //if(empty($group)){
    //    throw new bException(tr('This group does not exists'));
    //}

    $user_group = sql_get('SELECT `id`

                           FROM   `users_groups`

                           WHERE  `users_id`  = :users_id
                           AND    `groups_id` = :groups_id',

                           array(':users_id'  => $user['id'],
                                 ':groups_id' => $group['id']));

    if(empty($user_group)){
        throw new bException(tr('The specified user ":user" is not on group ":group"', array(':user' => $user['name'], ':group' => $group['name'])));
    }

    sql_query('DELETE FROM `users_groups`

               WHERE       `id` = :id',

               array(':id' => $user_group['id']));

    html_flash_set(tr('Deleted user ":user" from group ":group"', array(':user' => $user['name'], ':group' => $group['name'])), 'error');
}
?>
