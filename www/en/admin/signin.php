<?php
include_once(dirname(__FILE__).'/../libs/startup.php');

load_libs('validate');

if(isset($_POST['dosignin'])){
    try{
        $_POST = user_process_signin_fields($_POST);

        // Validate input
        $v = new validate_form($_POST, 'username,password');

        $v->isNotEmpty ($_POST['username'], tr('Please provide your username or email address'));
        $v->isNotEmpty ($_POST['password'], tr('Please provide a password'));

        $v->hasMinChars($_POST['username'], 4, tr('Please ensure that your username or email address has a minimum of 4 characters'));
        $v->hasMinChars($_POST['password'], 8, tr('Please ensure that the password has a minimum of 8 characters'));

        if(!$v->isValid()) {
            throw new bException('Signin failed with "'.$v->getErrors(', ').'"', 'validation');
        }

        $user = user_authenticate($_POST['username'], $_POST['password']);

        if(!has_rights('admin', $user)){
            throw new bException('signin: User "'.user_name($user).'" is not an administrator', 'accessdenied');
        }

        log_database('Admin authenticated: "'.$_POST['username'].'"', 'ADMIN');
        user_signin($user, false, '/admin/index.php');

    }catch(Exception $e){
        log_database('Sign in failed for user "'.str_log($_POST['username']).'" with code "'.$e->getCode().'" and message "'.$e->getMessage().'"', 'ADMIN');

        switch($e->getCode()){
            case 'notfound':
                // FALLTHROUGH
            case 'accessdenied':
                // FALLTHROUGH
            case 'password':
                html_flash_set($e->getMessage(), 'error', tr('Invalid credentials'));
                break;

            default:
                html_flash_set($e->getMessage(), 'error', tr('Something went wrong, please try again'));
        }
    }
}

/*
 * Get the form name for the password field in case save password option is not allowed
 */
if(empty($_CONFIG['security']['signin']['save_password'])){
    $username = 'username'.str_random(8);
    $password = 'password'.str_random(8);

    $username = '<input type="text" class="form-control" placeholder="'.tr('Your email or username').'" name="'.$username.'" id="'.$username.'" value="'.isset_get($_POST['username']).'">';
    $password = '<input type="password" style="display:none"/>
                 <input type="password" class="form-control" placeholder="'.tr('Your password').'" name="'.$password.'" id="'.$password.'">';

}else{
    $username = '<input type="text" class="form-control" placeholder="'.tr('Your email or username').'" name="username" id="username" value="'.isset_get($_POST['username']).'">';
    $password = '<input type="password" class="form-control" placeholder="'.tr('Your password').'" name="password" id="password">';
}

$html = '   <div class="row">
                <div class="col-md-12">
                    <section class="panel">
                        <header class="panel-heading">
                            <h2 class="panel-title">'.tr('Please sign in to continue...').'</h2>
                            <p>'.html_flash().'</p>
                        </header>
                        <div class="panel-body">
                            <form id="signin" name="signin" method="post" action="'.$_SERVER['REQUEST_URI'].'">
                                <div class="col-md-6">
                                    <div class="input-group mb-md">
                                        <span class="input-group-addon">
                                            <i class="fa fa-user"></i>
                                        </span>
                                        '.$username.'
                                    </div>
                                    <div class="input-group mb-md">
                                        <span class="input-group-addon">
                                            <i class="fa fa-key"></i>
                                        </span>
                                        '.$password.'
                                    </div>
                                    <div class="input-group mb-md">
                                        <input type="submit" class="mb-xs mt-xs mr-xs btn btn-primary" name="dosignin" value="'.tr('Sign in').'">
                                    </div>
                                </div>
                            </form>
                        </div>
                    </section>
                </div>
            </div>';

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

$params = array('title'       => tr('Sign in'),
                'icon'        => 'fa-sign-in',
                'breadcrumbs' => array(tr('Sign in')));

echo ca_page($html, $params);
?>
