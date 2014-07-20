<?php
require_once(dirname(__FILE__).'/../libs/startup.php');

right_or_redirect('admin,users');
load_libs('admin,user,validate');

try{
    if(isset_get($_POST['doadd'])){
        /*
         * Add a new user
         */
        user_signup(s_validate_post());
        log_database('Added user with data "'.json_encode_custom($_POST).'"', 'adduser');
        html_flash_set('The user "'.str_log(isset_get($_POST['name'])).'" was added successfully', 'success');
        redirect('users.php');

    }elseif(isset_get($_POST['doupdate'])){
        /*
         * Update this user
         */
        user_update($_POST = s_validate_post());

        if(!empty($_POST['password'])){
            user_update_password($_POST);
        }

        log_database('Updated user with data "'.json_encode_custom($_POST).'"', 'updateuser');
        html_flash_set('The user "'.str_log(isset_get($_POST['name'])).'" was updated successfully', 'success');
        redirect('users.php');
        break;
    }

}catch(Exception $e){
    $flash = $e->getMessage();
    $type  = 'error';
    $user  = $_POST;

    if(!empty($_GET['user'])){
        $user['id'] = sql_get('SELECT `id` FROM `users` WHERE `username` = :username', 'id', array('username' => cfm($_GET['user'])));
    }
}

if(empty($user)){
    if(!empty($_GET['user'])){
        $user = sql_get('SELECT `id`,
                                `name`,
                                `username`,
                                `email`

                         FROM   `users`

                         WHERE  `username` = :username', array(':username' => $_GET['user']));

        if(!$user){
            html_flash_set('Specified user "'.str_log($_GET['user']).'" does not exist', 'error');
        }
    }
}

$html = '   <form name="user" id="user" action="user.php'.(empty($_GET['user']) ? '' : '?user='.$_GET['user']).'" method="post">
                <fieldset>
                    <legend>'.(isset_get($user['id']) ? tr('Edit user') : tr('Add user')).'</legend>
                    '.html_flash().'
                    <ul class="form">
                        <li><label for="name">'.tr('User').'</label><input type="text" name="username" id="username" value="'.isset_get($user['username']).'"></li>
                        <li><label for="email">'.tr('Email').'</label><input type="text" name="email" id="email" value="'.isset_get($user['email']).'"></li>
                        <li><label for="realname">'.tr('Real name').'</label><input type="text" name="name" id="name" value="'.isset_get($user['name']).'"></li>
                        '.(empty($user['id']) ? '' : '<li><p class="form skip">'.tr('If you wish to update the users password, then specify it below').'</p></li>').'
                        <li><label for="password">'.tr('Password').'</label><input type="password" name="password" id="password"></li>
                        <li><label for="password2">'.tr('Password (verify)').'</label><input type="password" name="password2" id="password2"></li>
                    </ul>'.
                    (isset_get($user['id']) ? '<input type="submit" name="doupdate" id="doupdate" value="'.tr('Update').'"> <a class="button submit" href="'.domain('/admin/rights.php').'">'.tr('Manage rights').'</a>'
                                            : '<input type="submit" name="doadd" id="doadd" value="'.tr('Add').'"> <a class="button submit" href="'.domain('/admin/rights.php').'">'.tr('Manage rights').'</a>').
               '</fieldset>
            </form>';

/*
 * Add JS validation
 */
$vj = new validate_jquery();

$vj->validate('username' , 'required' , 'true'     , '<span class="FcbErrorTail"></span>'.tr('Please provide a user name'));
$vj->validate('username' , 'minlength', '4'        , '<span class="FcbErrorTail"></span>'.tr('Please ensure that the username has at least 4 characters'));
$vj->validate('name'     , 'required' , 'true'     , '<span class="FcbErrorTail"></span>'.tr('Please provide a real name'));
$vj->validate('name'     , 'minlength', '4'        , '<span class="FcbErrorTail"></span>'.tr('Please ensure that the real name has at least 4 characters'));
$vj->validate('email'    , 'required' , 'true'     , '<span class="FcbErrorTail"></span>'.tr('Please provide your email address'));
$vj->validate('email'    , 'email'    , 'true'     , '<span class="FcbErrorTail"></span>'.tr('Please provide a valid email address'));
$vj->validate('password2', 'equalTo'  , '#password', '<span class="FcbErrorTail"></span>'.tr('The password fields need to be equal'));

if(empty($user['id'])){
    /*
     * While adding a user, we ALWAYS must specify a password, while editing, its optional
     */
    $vj->validate('password', 'required' , 'true', '<span class="FcbErrorTail"></span>'.tr('Please provide a password'));
    $vj->validate('password', 'minlength', '8'   , '<span class="FcbErrorTail"></span>'.tr('Please ensure that the password has at least 8 characters'));
}

$params = array('id'   => 'user',
				'json' => false);

$html .= $vj->output_validation($params);



/*
 *
 */
function s_validate_post(){
    try{
        if(empty($_GET['user'])){
            throw new lsException('No user specified', 'notspecified');
        }

        array_ensure($_POST, 'name,username,email,password,password2');

        $_POST['email2']   = $_POST['email'];
        $_POST['terms']    = true;
        $_POST['id']       = sql_get('SELECT `id` FROM `users` WHERE `username` = :username', 'id', array('username' => cfm($_GET['user'])));

        // Validate input
        $v = new validate_form($_POST, 'name,username,email,password,password2');

        $v->isValidEmail($_POST['email']   , tr('Please provide a valid email address'));
        $v->isNotEmpty  ($_POST['email']   , tr('Please provide an email'));
        $v->hasMinChars ($_POST['username'], 4, tr('Please ensure that the username has a minimum of 8 characters'));
        $v->hasMinChars ($_POST['name']    , 4, tr('Please ensure that the real name has a minimum of 8 characters'));

        if(empty($_POST['id']) or $_POST['password']){
            /*
             * While adding a user, we ALWAYS must specify a password, while editing, its optional
             * IF, however, the password field has been set, then ensure that it follows the rules!
             */
            $v->hasMinChars ($_POST['password'], 8, tr('Please ensure that the password has a minimum of 8 characters'));
            $v->isEqual     ($_POST['password'], $_POST['password2'], tr('Please ensure that the password and validation password match'));
        }

        if(!$v->is_valid()) {
            throw new lsException(implode(', ', $v->get_errors()), 'invalid');
        }

        return $_POST;

    }catch(Exception $e){
        throw new lsException('s_validate_post(): Failed', $e);
    }
}

echo admin_start((isset_get($_GET['action']) == 'create' ? tr('Create new user') : tr('Edit user')), isset_get($flash), isset_get($type, 'success')).
	 $html.
	 admin_end();
?>