<?php
require_once(dirname(__FILE__).'/../libs/startup.php');

rights_or_access_denied('admin,roles,modify');
load_libs('validate');


$role = array();


/*
 * Are we editing a role?
 * If so then get the role data from the DB
 */
if(!empty($_GET['role'])){
    $db = sql_get('SELECT    `roles`.`id`,
                             `roles`.`createdon`,
                             `roles`.`modifiedon`,
                             `roles`.`name`,
                             `roles`.`description`,
                             `createdby`.`name`  AS `createdby`,
                             `modifiedby`.`name` AS `modifiedby`

                   FROM      `roles`

                   LEFT JOIN `users` as `createdby`
                   ON        `roles`.`createdby`     = `createdby`.`id`

                   LEFT JOIN `users` as `modifiedby`
                   ON        `roles`.`modifiedby`    = `modifiedby`.`id`

                   WHERE     `roles`.`name`          = :role',

                   array(':role' => $_GET['role']));


    if(!$db){
        html_flash_set(log_database(tr('Specified role "'.str_log($_GET['role']).'" does not exist'), 'role_not_exist'), 'error');
        redirect(domain('/admin/roles.php'));
    }

    log_database(tr('View role "'.str_log($_GET['role']).'"'), 'role_view');

    $role = array_merge($db, $role);
    unset($db);

    if($role['createdon']){
        $role['createdon']  = new DateTime($role['createdon']);
        $role['createdon']  = $role['createdon']->format($_CONFIG['formats']['human_datetime']);
    }

    if($role['modifiedon']){
        $role['modifiedon'] = new DateTime($role['modifiedon']);
        $role['modifiedon'] = $role['modifiedon']->format($_CONFIG['formats']['human_datetime']);
    }

    $role['rights'] = sql_list('SELECT   `rights`.`name`

                                FROM     `roles_rights`

                                JOIN     `rights`
                                ON       `roles_rights`.`rights_id` = `rights`.`id`

                                WHERE    `roles_rights`.`roles_id`  = :roles_id

                                ORDER BY `roles_rights`.`id` DESC',

                                array(':roles_id' => $role['id']));
}


/*
 * Was role data submitted?
 */
if(!empty($_POST['dosubmit'])){
    $role = array_merge($role, $_POST);

    if(!empty($role['id'])){
        /*
         * Auto update
         */
        $_POST['doupdate'] = 1;
    }
}


try{
    if(isset_get($_POST['docreate'])){
        /*
         * Validate data
         */
        s_validate_role($role);

        /*
         * This role does not exist yet?
         */
        if(sql_get('SELECT `id` FROM `roles` WHERE `name` = :name', 'id', array(':name' => $role['name']))){
            throw new bException(tr('The role "%name%" already exists', '%name%', str_log($role['name'])), 'exists');
        }

        sql_query('INSERT INTO `roles` (`createdby`, `name`, `description`)
                   VALUES              (:createdby , :name , :description )',

                   array(':createdby'   => $_SESSION['user']['id'],
                         ':name'        => $role['name'],
                         ':description' => $role['description']));

        $role['id']     = sql_insert_id();
        $role['rights'] = s_update_rights($role);

        html_flash_set(log_database(tr('Created role "%role%" with rights "%rights%"', array('%role%' => str_log($role['name']), '%rights%' => str_log(str_force($role['rights'])))), 'role_create'), 'success');

        $role = array();

    }elseif(isset_get($_POST['doupdate'])){
        if(empty($role['id'])){
            throw new bException('Cannot update, no role specified', 'notspecified');
        }

        /*
         * Validate data
         */
        s_validate_role($role);


        /*
         * This role does not exist yet?
         */
        if(sql_get('SELECT `name` FROM `roles` WHERE `name` = :name AND `id` != :id', 'name', array(':name' => $role['name'], ':id' => $role['id']))){
            throw new bException(tr('The role "%name%" already exists', '%name%', str_log($role['name'])), 'exists');
        }


        /*
         * Update the role
         */
        sql_query('UPDATE `roles`

                   SET    `modifiedby`  = :modifiedby,
                          `modifiedon`  = NOW(),
                          `name`        = :name,
                          `description` = :description

                   WHERE  `id`          = :id',

                   array(':id'          => $role['id'],
                         ':modifiedby'  => $_SESSION['user']['id'],
                         ':name'        => $role['name'],
                         ':description' => $role['description']));


        /*
         * Update the role name in the users table
         */
        sql_query('UPDATE `users`

                   SET    `role`        = :role

                   WHERE  `roles_id`    = :roles_id',

                   array(':roles_id'    => $role['id'],
                         ':role'        => $role['name']));


        /*
         * Update the available rights for this role
         */
        $role['rights'] = s_update_rights($role);


        /*
         * Update the rights for all users that have this role
         */
        $r = sql_query('SELECT `id` FROM `users` WHERE `roles_id` = :roles_id', array(':roles_id' => $role['id']));

        while($user = sql_fetch($r)){
            $user['roles_id'] = $role['id'];
            user_update_rights($user);
        }


        /*
         * Done!
         */
        html_flash_set(log_database('Updated role "'.str_log($role['name']).'" with rights "'.str_log(str_force($role['rights'])).'"', 'role_update'), 'success');

        if($r->rowCount()){
            html_flash_set(tr('Updated rights for "%count%" users with the role "%role%"', array('%count%' => $r->rowCount(), '%role%' => str_log($role['name']))), 'success');
        }

        redirect(domain('/admin/role.php?role='.$role['name']));
    }

}catch(Exception $e){
    html_flash_set($e);
}


/*
 * Build the rights HTML combos
 */
$rights_html    = '';

$rights         = array('name'       => 'rights[]',
                        'class'      => 'filter form-control',
                        'selected'   => null,
                        'autosubmit' => true,
                        'onchange'   => '$("#role").validate().settings.rules = {};',
                        'resource'   => sql_list('SELECT `name` AS `id`, `name` FROM `rights` ORDER BY `name`'));

/*
 * Ensure that all rights are unique and ordered by name. Do this separately from the s_validate_role() because
 * this data may not be validated (in case of new role that got form reloaded)
 */
$role['rights'] = array_force(isset_get($role['rights']));
$role['rights'] = array_unique($role['rights']);
sort($role['rights']);

foreach($role['rights'] as $right){
    if(!$right) continue;

    $rights['selected'] = $right;

    $rights_html .= '   <div class="form-group">
                            <div class="col-md-3 control-label"> </div>
                            <div class="col-md-6">
                                '.html_select($rights).'
                            </div>
                        </div>';
}

unset($rights['selected']);
$rights['autofocus'] = (!empty($role['name']) and !empty($role['description']));


/*
 * Build page HTML
 */
$html   = ' <form id="role" name="role" action="'.domain(true).'" method="post">
                '.html_form().'
                '.html_hidden($role).'
                <div class="row">
                    <div class="col-md-12">
                        <section class="panel">
                            <header class="panel-heading">
                                <h2 class="panel-title">'.(empty($role['id']) ? tr('Create new role') : tr('Modify role')).'</h2>
                                <p>'.html_flash().'</p>
                            </header>
                            <div class="panel-body">';

if(!empty($role['id'])){
    $html .= '                  <div class="form-group">
                                    <label class="col-md-3 control-label" for="createdon">'.tr('Created on').'</label>
                                    <div class="col-md-6">
                                        <input type="text" name="createdon" id="createdon" class="form-control" value="'.isset_get($role['createdon']).'" disabled>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-md-3 control-label" for="createdby">'.tr('Created by').'</label>
                                    <div class="col-md-6">
                                        <input type="text" name="createdby" id="createdby" class="form-control" value="'.isset_get($role['createdby']).'" disabled>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-md-3 control-label" for="modifiedon">'.tr('Modified on').'</label>
                                    <div class="col-md-6">
                                        <input type="text" name="modifiedon" id="modifiedon" class="form-control" value="'.isset_get($role['modifiedon']).'" disabled>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-md-3 control-label" for="modifiedby">'.tr('Modified by').'</label>
                                    <div class="col-md-6">
                                        <input type="text" name="modifiedby" id="modifiedby" class="form-control" value="'.isset_get($role['modifiedby']).'" disabled>
                                    </div>
                                </div>';
}

$html .= '                      <div class="form-group">
                                    <label class="col-md-3 control-label" for="name">'.tr('Name').'</label>
                                    <div class="col-md-6">
                                        <input type="text" name="name" id="name" class="form-control" value="'.isset_get($role['name']).'" maxlength="32"'.((empty($role['name']) and empty($role['description'])) ? ' autofocus' : '').'>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-md-3 control-label" for="description">'.tr('Description').'</label>
                                    <div class="col-md-6">
                                        <textarea name="description" id="description" class="form-control" maxlength="255"'.((!empty($role['name']) and empty($role['description'])) ? ' autofocus' : '').'>'.isset_get($role['description']).'</textarea>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-md-3 control-label" for="rights">'.tr('Rights').'</label>
                                    <div class="col-md-6">
                                        '.html_select($rights).'
                                    </div>
                                </div>
                                '.$rights_html.'
                                '.(empty($role['id']) ? '<input type="submit" class="mb-xs mt-xs mr-xs btn btn-primary" name="docreate" id="docreate" value="'.tr('Create').'">'
                                                      : '<input type="submit" class="mb-xs mt-xs mr-xs btn btn-primary" name="doupdate" id="doupdate" value="'.tr('Update').'">').'
                                <a class="mb-xs mt-xs mr-xs btn btn-primary" href="'.domain('/admin/roles.php'.(empty($_POST['right']) ? '' : '?right='.$_POST['right'])).'">'.tr('Manage roles').'</a>
                            </div>
                        </section>
                    </div>
                </div>
            </form>';


/*
 * Add JS validation
 */
$vj = new validate_jquery();

$vj->validate('name'       , 'required' , 'true', '<span class="FcbErrorTail"></span>'.tr('Please provide a name'));
$vj->validate('name'       , 'minlength',    '2', '<span class="FcbErrorTail"></span>'.tr('Please ensure that the name has at least 2 characters'));
$vj->validate('description', 'required' , 'true', '<span class="FcbErrorTail"></span>'.tr('Please provide a description'));
$vj->validate('description', 'minlength',   '16', '<span class="FcbErrorTail"></span>'.tr('Please ensure that the description has at least 16 characters'));

$html .= $vj->output_validation(array('id'   => 'role',
				                      'json' => false));


$params = array('title'       => tr('roles'),
                'icon'        => 'fa-lock',
                'breadcrumbs' => array(tr('role'), tr('Modify')));

echo ca_page($html, $params);



/*
 * Validate the data of the specified role
 */
function s_validate_role(&$role){
    try{
        $v = new validate_form($role, 'name,description,rights');

        $v->isNotEmpty  ($role['name']     , tr('Please provide a name'));
        $v->hasMinChars ($role['name'],   2, tr('Please ensure that the name has a minimum of 2 characters'));
        $v->hasMaxChars ($role['name'],  16, tr('Please ensure that the name has a maximum of 16 characters'));

        if(strpos($role['name'], ' ') !== false){
            $v->setError(tr('Please ensure that the role name contains no spaces'));
        }

        if($role['name'] == 'none'){
            $v->setError(tr('The name "none" is not allowed for a role'));
        }

        $v->isNotEmpty  ($role['description']     , tr('Please provide a description'));
        $v->hasMinChars ($role['description'],  32, tr('Please ensure that the description has a minimum of 32 characters'));
        $v->hasMaxChars ($role['description'], 255, tr('Please ensure that the description has a maximum of 255 characters'));

        if(!is_array(isset_get($role['rights']))){
            if(!empty($role['rights'])){
                $v->setError(tr('Specified rights list is invalid'));
            }

            $role['rights'] = array();
        }

        /*
         * Ensure that all rights are unique and ordered by name
         */
        $role['rights'] = array_unique($role['rights']);
        sort($role['rights']);

        if(!$v->isValid()) {
            throw new bException($v->getErrors(), 'invalid');
        }

    }catch(Exception $e){
        throw new bException('s_validate_role(): Failed', $e);
    }
}



/*
 *
 */
function s_update_rights($role){
    try{
        if(empty($role['id'])){
            throw new bException('s_update_rights(): Cannot update rights, no role specified', 'not_specified');
        }

        if(isset_get($role['rights']) and !is_array($role['rights'])){
            throw new bException('s_update_rights(): The specified rights list is invalid', 'invalid');
        }

        sql_query('DELETE FROM `roles_rights` WHERE `roles_id` = :roles_id', array(':roles_id' => $role['id']));

        $p = sql_prepare('INSERT INTO `roles_rights` (`roles_id`, `rights_id`)
                          VALUES                     (:roles_id , :rights_id )');

        $role_right = array(':roles_id' => $role['id']);

        foreach(isset_get($role['rights']) as $key => $right){
            if(!$right){
                unset($role['rights'][$key]);
                continue;
            }

            $role_right[':rights_id'] = sql_get('SELECT `id` FROM `rights` WHERE `name` = :name', 'id', array(':name' => $right));

            if(!$role_right[':rights_id']){
                /*
                 * This right does not exist! Skip it!
                 */
                log_database('Tried adding non existing right "'.str_log($right).'" to role "'.str_log($role['name']).'", ignoring', 'role_invalid');
                continue;
            }

            $p->execute($role_right);
        }

        return $role['rights'];

    }catch(Exception $e){
        throw new bException('s_update_rights(): Failed', $e);
    }
}
?>