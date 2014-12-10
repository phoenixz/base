<?php
require_once(dirname(__FILE__).'/../libs/startup.php');

rights_or_redirect('admin');
load_libs('validate');

if(!$user = sql_get('SELECT * FROM `users` WHERE `id` = :id', array(':id' => isset_get($_POST['id'], isset_get($_GET['id']))))){
    html_flash_set('Can not update password, no user specified', 'error');
    redirect('/admin/users.php');
}

switch(strtolower(isset_get($_POST['doaction']))){
    case 'update':
        /*
         * Update this user
         */
        try{
            array_ensure($_POST, 'name,email,password,terms');

            // Validate input
            $v = new validate_form();
            $v->isValid_password($_POST['password'], tr('Please provide a password with minimum 8 characters'));
            $v->is_equal         (isset_get($_POST['password']), isset_get($_POST['password2']), tr('Please ensure that the password and validation password are equal'));

            if(!$v->isValid()) {
                throw new bException($v->getErrors(), 'invalid');
            }

            sql_query('UPDATE `users` SET `password` = :password WHERE `id` = :id',

                      array(':id'       => $user['id'],
                            ':password' => password($_POST['password'])));

            html_flash_set('The users password was updated successfully', 'success');
            redirect('user.php?id='.$user['id']);

        }catch(Exception $e){
            $flash = $e->getMessage();
            $type  = 'error';
            $user  = $_POST;
        }

        break;
}

$html = '<form name="passwordUpdate" id="passwordUpdate" target="password.php" method="post">
            <table class="admin user">
                <tr><td>New Password</td><td><input type="password" name="password" value="" /></td></tr>
                <tr><td>Validate pasword</td><td><input type="password" name="password2" value="" /></td></tr>
            </table>
            <input type="hidden" name="id" value="'.isset_get($user['id']).'" />
            <input type="submit" class="button" name="doaction" value="Update" />
            <a class="button" href="'.domain('/admin/user.php?id='.$user['id']).'">Back</a>
        </form>';

/*
 * Add JS validation
 */
$vj = new validate_jquery();

$vj->validate('password' , 'required' , 'true'    , '<span class="FcbErrorTail"></span>'.tr('Please enter a password'));
$vj->validate('password' , 'minlength', '8'       , '<span class="FcbErrorTail"></span>'.tr('Your password needs to have at least 8 characters'));
//$vj->validate('password2', 'equalTo'  , 'password', '<span class="FcbErrorTail"></span>'.tr('The password fields need to be equal'));

$params = array('id'   => 'passwordUpdate',
				'json' => false);

$html .= $vj->output_validation($params);

echo admin_start('Update password for user "'.str_log(user_name($user)).'"', isset_get($flash), isset_get($type, 'success')).
	 $html.
	 admin_end();
?>