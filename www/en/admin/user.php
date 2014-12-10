<?php
require_once(dirname(__FILE__).'/../libs/startup.php');

rights_or_redirect('admin,users,modify');
load_libs('user,validate');


$user = array();


/*
 * Are we editing a user?
 * If so then get the user data from the DB
 */
if(!empty($_GET['user'])){
    $db = sql_get('SELECT    `users`.`id`,
                             `users`.`createdon`,
                             `users`.`modifiedon`,
                             `users`.`name`,
                             `users`.`email`,
                             `users`.`username`,
                             `users`.`phones`,
                             `users`.`roles_id`,
                             `users`.`commentary`,
                             `createdby`.`name`  AS `createdby`,
                             `modifiedby`.`name` AS `modifiedby`

                   FROM      `users`

                   LEFT JOIN `users` as `createdby`
                   ON        `users`.`createdby`     = `createdby`.`id`

                   LEFT JOIN `users` as `modifiedby`
                   ON        `users`.`modifiedby`    = `modifiedby`.`id`

                   WHERE     `users`.`username`      = :user',

                   array(':user' => $_GET['user']));

    if(!$db){
        html_flash_set(log_database(tr('Specified user "'.str_log($_GET['user']).'" does not exist'), 'user_not_exist'), 'error');
        redirect(domain('/admin/users.php'));
    }

    log_database(tr('View user "'.str_log($_GET['user']).'"'), 'user_view');

    $user = array_merge($db, $user);
    unset($db);

    if($user['createdon']){
        $user['createdon']  = new DateTime($user['createdon']);
        $user['createdon']  = $user['createdon']->format($_CONFIG['formats']['human_datetime']);
    }

    if($user['modifiedon']){
        $user['modifiedon'] = new DateTime($user['modifiedon']);
        $user['modifiedon'] = $user['modifiedon']->format($_CONFIG['formats']['human_datetime']);
    }

    $user['rights'] = sql_list('SELECT   `rights`.`name`

                                FROM     `users_rights`

                                JOIN     `rights`
                                ON       `users_rights`.`rights_id` = `rights`.`id`

                                WHERE    `users_rights`.`users_id`  = :users_id

                                ORDER BY `users_rights`.`id` DESC',

                                array(':users_id' => $user['id']));
}


/*
 * Was user data submitted?
 */
if(!empty($_POST['dosubmit'])){
    $user = array_merge($user, $_POST);
}


/*
 * Process user actions
 */
try{
    if(isset_get($_POST['docreate'])){
        /*
         * Create a new user
         */
        s_validate_user($user);

        $user['id'] = user_signup($user);

        html_flash_set(log_database('Created user "'.str_log(isset_get($user['name'])).'"', 'user_create'), 'success');

        /*
         * Now that the user has had a simple registration, contiue with updating the user
         */
        $_POST['doupdate'] = 1;

        unset($user['password']);
        unset($user['password2']);
    }

    if(isset_get($_POST['doupdate'])){
        if(empty($user['id'])){
            throw new bException('Cannot update, no user specified', 'notspecified');
        }

        /*
         * Validate data
         */
        s_validate_user($user);

        /*
         * Update the user
         */
        $r = sql_query('UPDATE `users`

                        SET    `modifiedby`              = :modifiedby,
                               `modifiedon`              = NOW(),
                               `status`                  = :status,
                               `username`                = :username,
                               `name`                    = :name,
                               `email`                   = :email,
                               `language`                = :language,
                               `gender`                  = :gender,
                               `latitude`                = :latitude,
                               `longitude`               = :longitude,
                               `roles_id`                = :roles_id,
                               `role`                    = :role,
                               `phones`                  = :phones,
                               `country`                 = :country,
                               `commentary`              = :commentary,
                               `avatar`                  = :avatar,
                               `validated`               = :validated,
                               `fb_id`                   = :fb_id,
                               `fb_token`                = :fb_token,
                               `gp_id`                   = :gp_id,
                               `gp_token`                = :gp_token,
                               `ms_id`                   = :ms_id,
                               `ms_token_authentication` = :ms_token_authentication,
                               `ms_token_access`         = :ms_token_access,
                               `tw_id`                   = :tw_id,
                               `tw_token`                = :tw_token,
                               `yh_id`                   = :yh_id,
                               `yh_token`                = :yh_token

                        WHERE  `id`                      = :id',

                        array(':modifiedby'              => isset_get($_SESSION['user']['id']),
                              ':id'                      => $user['id'],
                              ':username'                => $user['username'],
                              ':name'                    => $user['name'],
                              ':email'                   => $user['email'],
                              ':language'                => $user['language'],
                              ':gender'                  => $user['gender'],
                              ':latitude'                => $user['latitude'],
                              ':longitude'               => $user['longitude'],
                              ':roles_id'                => $user['roles_id'],
                              ':role'                    => $user['role'],
                              ':phones'                  => $user['phones'],
                              ':status'                  => $user['status'],
                              ':avatar'                  => $user['avatar'],
                              ':validated'               => ($user['validated'] ? str_random(32) : null),
                              ':commentary'              => $user['commentary'],
                              ':fb_id'                   => $user['fb_id'],
                              ':fb_token'                => $user['fb_token'],
                              ':gp_id'                   => $user['gp_id'],
                              ':gp_token'                => $user['gp_token'],
                              ':ms_id'                   => $user['ms_id'],
                              ':ms_token_authentication' => $user['ms_token_authentication'],
                              ':ms_token_access'         => $user['ms_token_access'],
                              ':tw_id'                   => $user['tw_id'],
                              ':tw_token'                => $user['tw_token'],
                              ':yh_id'                   => $user['yh_id'],
                              ':yh_token'                => $user['yh_token'],
                              ':country'                 => $user['country']));

        if(!empty($user['password'])){
            user_update_password($user);
        }

        user_update_rights($user);

        /*
         * This update might have been done because of a create user action
         */
        if(!isset_get($_POST['docreate'])){
            html_flash_set(log_database('Updated user "'.str_log(isset_get($_POST['username'])).'"', 'user_update'), 'success');
            redirect(domain('/admin/user.php?user='.$user['username']));
        }

        $user = array();

    }elseif(isset_get($_POST['dobecome'])){
        if(!$user){
            throw new bException('Cannot become user, no user available', 'nouseravailable');
        }

        user_switch($user['name']);

        html_flash_set('You are now the user "'.$user['name'].'"', 'success');
        html_flash_set('NOTICE: You will now be limited to the access level of user "'.$user['name'].'"', 'warning');

        redirect(true);
    }

}catch(Exception $e){
    html_flash_set($e);
}


/*
 *
 */
$users = array('name'       => 'roles_id',
               'class'      => 'filter form-control',
               'selected'   => isset_get($user['roles_id']),
               'autosubmit' => true,
               'onchange'   => '$("#user").validate().settings.rules = {};',
               'resource'   => sql_list('SELECT `id`, `name` FROM `roles` WHERE `status` IS NULL ORDER BY `name`'));


/*
 * Build page HTML
 */
$html = '   <form name="user" id="user" action="'.domain(true).'" method="post">
                '.html_form().'
                '.html_hidden($user).'
                <div class="row">
                    <div class="col-md-12">
                        <section class="panel">
                            <header class="panel-heading">
                                <h2 class="panel-title">'.(isset_get($user['id']) ? tr('Update user') : tr('Create user')).'</h2>
                                <p>'.html_flash().'</p>
                            </header>
                            <div class="panel-body">';

if(!empty($user['id'])){
    $html .= '                  <div class="form-group">
                                    <label class="col-md-3 control-label" for="createdon">'.tr('Created on').'</label>
                                    <div class="col-md-6">
                                        <input type="text" name="createdon" id="createdon" class="form-control" value="'.isset_get($user['createdon']).'" disabled>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-md-3 control-label" for="createdby">'.tr('Created by').'</label>
                                    <div class="col-md-6">
                                        <input type="text" name="createdby" id="createdby" class="form-control" value="'.isset_get($user['createdby']).'" disabled>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-md-3 control-label" for="modifiedon">'.tr('Modified on').'</label>
                                    <div class="col-md-6">
                                        <input type="text" name="modifiedon" id="modifiedon" class="form-control" value="'.isset_get($user['modifiedon']).'" disabled>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-md-3 control-label" for="modifiedby">'.tr('Modified by').'</label>
                                    <div class="col-md-6">
                                        <input type="text" name="modifiedby" id="modifiedby" class="form-control" value="'.isset_get($user['modifiedby']).'" disabled>
                                    </div>
                                </div>';
}

$html .= '                      <div class="form-group">
                                    <label class="col-md-3 control-label" for="username">'.tr('User name').'</label>
                                    <div class="col-md-6">
                                        <input type="text" name="username" id="username" class="form-control" value="'.isset_get($user['username']).'" maxlength="255" autofocus>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-md-3 control-label" for="email">'.tr('Email').'</label>
                                    <div class="col-md-6">
                                        <input type="text" name="email" id="email" class="form-control" value="'.isset_get($user['email']).'" maxlength="255">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-md-3 control-label" for="name">'.tr('Real name').'</label>
                                    <div class="col-md-6">
                                        <input type="text" name="name" id="name" class="form-control" value="'.isset_get($user['name']).'" maxlength="255">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-md-3 control-label" for="phones">'.tr('Phones').'</label>
                                    <div class="col-md-6">
                                        <input type="text" name="phones" id="phones" class="form-control" value="'.isset_get($user['phones']).'" maxlength="255">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-md-3 control-label" for="commentary">'.tr('Commentary').'</label>
                                    <div class="col-md-6">
                                        <textarea name="commentary" id="commentary" class="form-control" maxlength="2047" rows="5">'.isset_get($user['commentary']).'</textarea>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-md-3 control-label" for="role">'.tr('Role and rights').'</label>
                                    <div class="col-md-6">
                                        '.html_select($users).'
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-md-3 control-label" for="role"> </label>
                                    <div class="col-md-6">
                                        <p>
                                            '.($user['roles_id'] ? sql_get('SELECT `description` FROM `roles` WHERE `id` = :id', 'description', array(':id' => $user['roles_id'])) : tr('No role description available, no role has been selected yet')).'
                                        </p>
                                    </div>
                                </div>';

if(!empty($user['roles_id'])){
    $html .= '                  <div class="form-group">
                                    <label class="col-md-3 control-label" for="rights"> </label>
                                    <div class="col-md-6">
                                        <h4>'.tr('Rights associated with this role').'</h4>
                                        <table class="link select table mb-none table-striped table-hover">
                                            <thead>
                                                <th>'.tr('Name').'</th>
                                                <th>'.tr('Description').'</th>
                                            </thead>';

    $r = sql_query('SELECT    `users_rights`.`name` AS `id`,
                              `users_rights`.`name`,
                              `rights`.`description`

                    FROM      `users_rights`

                    LEFT JOIN `rights`
                    ON        `rights`.`id` = `users_rights`.`rights_id`

                    WHERE     `users_id` = :users_id',

                    array(':users_id' => $user['id']));

    while($user_right = sql_fetch($r)){
        $a     = '<a href="'.domain('/admin/right.php?right='.$user_right['id']).'">';
        $html .= '<tr><td>'.$a.$user_right['name'].'</a></td><td>'.$a.$user_right['description'].'</a></td></tr>';
    }

    $html .= '                          </table>
                                    </div>
                                </div>';
}

$html .= '                      <hr>
                                <div class="form-group">
                                    <label class="col-md-3 control-label" for="password">'.tr('Password').'</label>
                                    <div class="col-md-6">
                                        <input type="password" name="password" id="password" class="form-control" value="" maxlength="32">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-md-3 control-label" for="password2">'.tr('Validate password').'</label>
                                    <div class="col-md-6">
                                        <input type="password" name="password2" id="password2" class="form-control" value="" maxlength="32">
                                    </div>
                                </div>'.
                                (isset_get($user['id']) ? '<input type="submit" class="mb-xs mt-xs mr-xs btn btn-primary" name="doupdate" id="doupdate" value="'.tr('Update').'">
                                                           <input type="submit" class="mb-xs mt-xs mr-xs btn btn-warning" name="dobecome" id="dobecome" value="'.tr('Become user').'">'

                                                        : '<input type="submit" class="mb-xs mt-xs mr-xs btn btn-primary" name="docreate" id="docreate" value="'.tr('Create').'">').'
                            </div>
                        </section>
                    </div>
                </div>
            </form>';

/*
 * AddJS validation
 */
$vj = new validate_jquery();

$vj->validate('username' , 'minlength', '2'        , '<span class="FcbErrorTail"></span>'.tr('Please ensure that the username has at least 2 characters'));
$vj->validate('name'     , 'required' , 'true'     , '<span class="FcbErrorTail"></span>'.tr('Please provide a real name'));
$vj->validate('name'     , 'minlength', '2'        , '<span class="FcbErrorTail"></span>'.tr('Please ensure that the real name has at least 2 characters'));
$vj->validate('email'    , 'required' , 'true'     , '<span class="FcbErrorTail"></span>'.tr('Please provide your email address'));
$vj->validate('email'    , 'email'    , 'true'     , '<span class="FcbErrorTail"></span>'.tr('Please provide a valid email address'));
$vj->validate('roles_id' , 'required' , 'true'     , '<span class="FcbErrorTail"></span>'.tr('Please provide a role'));
$vj->validate('password2', 'equalTo'  , '#password', '<span class="FcbErrorTail"></span>'.tr('The password fields need to be equal'));

if(empty($user['id'])){
    /*
     * While adding a user, we ALWAYS must specify a password, while editing, its optional
     */
    $vj->validate('password', 'required' , 'true', '<span class="FcbErrorTail"></span>'.tr('Please provide a password'));
    $vj->validate('password', 'minlength', '8'   , '<span class="FcbErrorTail"></span>'.tr('Please ensure that the password has at least 8 characters'));
}

$html .= $vj->output_validation(array('id'   => 'user',
                                      'json' => false));

$params = array('title'       => tr('User'),
                'icon'        => 'fa-user',
                'breadcrumbs' => array(tr('Users'), tr('Modify')));

echo ca_page($html, $params);


/*
 *
 */
function s_validate_user(&$user, $id = null){
    try{
        // Validate input
        $v = new validate_form($user, 'name,username,email,password,password2,role,commentary,gender,latitude,longitude,language,country,fb_id,fb_token,gp_id,gp_token,ms_id,ms_token_authentication,ms_token_access,tw_id,tw_token,yh_id,yh_token,status,validated,avatar,phones');

        $user['email2'] = $user['email'];
        $user['terms']  = true;

        $v->isNotEmpty  ($user['email']      , tr('Please provide an email'));
        $v->isValidEmail($user['email']      , tr('Please provide a valid email address'));

        $v->hasMinChars ($user['username'], 2, tr('Please ensure that the user name has a minimum of 2 characters'));

        $v->isNotEmpty  ($user['name']       , tr('Please provide a real name'));
        $v->hasMinChars ($user['name']    , 2, tr('Please ensure that the real name has a minimum of 2 characters'));

        $v->isNotEmpty  ($user['roles_id']   , tr('Please provide a role'));

        if($user['roles_id']){
            if(!$role = sql_get('SELECT `id`, `name` FROM `roles` WHERE `id` = :id AND `status` IS NULL', array(':id' => $user['roles_id']))){
                $v->setError(tr('The specified role does not exist'));
                $user['role'] = null;

            }else{
                $user['roles_id'] = $role['id'];
                $user['role']     = $role['name'];
            }
        }

        if(!empty($user['password'])){
            $v->hasMinChars ($user['password'],                  8, tr('Please ensure that the password has a minimum of 8 characters'));
            $v->isEqual     ($user['password'], $user['password2'], tr('Please ensure that the password and validation password match'));
        }

        /*
         * Ensure that the username and email are not in use
         */
        $query = 'SELECT `email`,
                         `username`

                  FROM   `users`';

        if(empty($user['id'])){
            $where   = ' WHERE (`email`    = :email
                         OR     `username` = :username )';

            $execute = array(':email'      => $user['email'],
                             ':username'   => $user['username']);

        }else{
            $where   = ' WHERE  `id`      != :id
                         AND   (`email`    = :email
                         OR     `username` = :username )';

            $execute = array(':id'         => $user['id'],
                             ':email'      => $user['email'],
                             ':username'   => $user['username']);
        }

        $exists = sql_get($query.$where, $execute);

        if($exists){
            if($exists['username'] == $user['username']){
                $v->setError(tr('The username "%username%" is already in use by another user', array('%username%' => str_log($user['username']))));

            }else{
                $v->setError(tr('The email "%email%" is already in use by another user', array('%email%' => str_log($user['email']))));
            }
        }

        /*
         * Ensure that the phones are not in use
         */
        if(!empty($user['phones'])){
            $user['phones'] = explode(',', $user['phones']);

            foreach($user['phones'] as &$phone){
                $phone = trim($phone);
            }

            unset($phone);

            $user['phones'] = implode(',', $user['phones']);
            $execute        = sql_in($user['phones'], ':phone');

            foreach($execute as &$phone){
                if($v->isValidPhonenumber($phone, tr('The phone number "%phone%" is not valid', array('%phone%' => $phone)))){
                    $phone = '%'.$phone.'%';
                }
            }

            unset($phone);

            $where   = array();

            $query   = 'SELECT `id`,
                               `phones`,
                               `username`

                        FROM   `users` WHERE';

            foreach($execute as $key => $value){
                $where[] = '`phones` LIKE '.$key;
            }

            $query .= ' ('.implode(' OR ', $where).')';

            if(!empty($user['id'])){
                $query         .= ' AND `users`.`id` != :id';
                $execute[':id'] = $user['id'];
            }

            $exists = sql_list($query, $execute);

            if($exists){
                /*
                 * One or more phone numbers already exist with one or multiple users. Cross check and
                 * create a list of where the number was found
                 */
                foreach(array_force($user['phones']) as $value){
                    foreach($exists as $exist){
                        $key = array_search($value, array_force($exist['phones']));

                        if($key !== false){
                            /*
                             * The current phone number is already in use by another user
                             */
                            $v->setError(tr('The phone "%phone%" is already in use by user "%user%"', array('%phone%' => str_log($value), '%user%' => '<a target="_blank" href="'.domain('/admin/user.php?user='.$exist['username']).'">'.$exist['username'].'</a>')));
                        }
                    }
                }
            }
        }

        if(!$v->isValid()) {
            throw new bException($v->getErrors(), 'error');
        }

    }catch(Exception $e){
        throw new bException('s_validate_user(): Failed', $e);
    }
}
?>