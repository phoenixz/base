<?php
include_once(dirname(__FILE__).'/../libs/startup.php');

load_libs('admin,user,validate');

if(isset($_POST['dosignin'])){
    try{
        // Validate input
        $v = new validate_form($_POST, 'username,password');

        $v->isNotEmpty ($_POST['username'], tr('Please provide your username or email address'));
        $v->isNotEmpty ($_POST['password'], tr('Please provide a password'));

        $v->hasMinChars($_POST['username'], 4, tr('Please ensure that your username or email address has a minimum of 4 characters'));
        $v->hasMinChars($_POST['password'], 8, tr('Please ensure that the password has a minimum of 8 characters'));

        if(!$v->isValid()) {
            throw new lsException('Signin failed with "'.$v->getErrors(', ').'"', 'validation');
        }

        $user = user_authenticate($_POST['username'], $_POST['password']);

        if(!user_has_right($user, 'admin')){
            throw new lsException('signin: User "'.user_name($user).'" is not an administrator', 'accessdenied');
        }

        log_database('Admin authenticated: "'.$_POST['username'].'"', 'ADMIN');
        user_signin($user, false, '/admin/index.php');

    }catch(Exception $e){
        log_database('Sign in failed for user "'.str_log($_POST['username']).'" with code "'.$e->getCode().'" and message "'.$e->getMessage().'"', 'ADMIN');

        switch($e->getCode()){
            case 'notfound':
                // FALLTHROUGH
            case 'password':
                // FALLTHROUGH
            case 'accessdenied':
                html_flash_set($e->getMessage(), 'error', tr('Invalid credentials'));
                break;

            default:
                html_flash_set($e->getMessage(), 'error', tr('Something went wrong, please try again'));
        }
    }
}

$html = html_flash().'<form id="signin" name="signin" method="post" action="'.$_SERVER['REQUEST_URI'].'">
    <label>'.tr('Username').'</label><br>
    <input name="username" type="text" value="'.isset_get($_POST['username'], '').'" placeholder="'.tr('Your username').'"><br>
    <label>'.tr('Password').'</label><br>
    <input name="password" type="password" placeholder="'.tr('Your password').'"><br>
    <input type="submit" name="dosignin" value="'.tr('Sign in').'">
</form>';

/*
 * Add JS validation
 */
$vj = new validate_jquery();

$vj->validate('username', 'required' , 'true', '<span class="FcbErrorTail"></span>'.tr('Please provide your username or email address'));
$vj->validate('password', 'required' , 'true', '<span class="FcbErrorTail"></span>'.tr('Please provide a password'));

$vj->validate('username', 'minlength', '4', '<span class="FcbErrorTail"></span>'.tr('Please ensure that your username or email has at least 4 characters'));
$vj->validate('password', 'minlength', '8', '<span class="FcbErrorTail"></span>'.tr('Please ensure that the password has at least 8 characters'));

$params = array('id'   => 'signin',
                'json' => false);

$html .= $vj->output_validation($params);

echo admin_page($html, tr('Sign in'));
?>
