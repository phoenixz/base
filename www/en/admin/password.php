<?php
require_once(dirname(__FILE__).'/../libs/startup.php');

rights_or_redirect('admin,profile');
load_libs('user,validate');


/*
 * Process user actions
 */
try{
    if(isset_get($_POST['doupdate'])){
        /*
         * Validate data
         */
        s_validate_password($user);

        user_update_password(array('id'        => $_SESSION['user']['id'],
                                   'password'  => isset_get($_POST['password']),
                                   'password2' => isset_get($_POST['password'])));

        html_flash_set(log_database('Updated password for user "'.str_log(isset_get($_POST['username'])).'"', 'user_update'), 'success');
        redirect(domain('/admin/profile.php'));
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
                                <h2 class="panel-title">'.tr('Update your password').'</h2>
                                <p>'.html_flash().'</p>
                            </header>
                            <div class="panel-body">
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
                                </div>
                                <input type="submit" class="mb-xs mt-xs mr-xs btn btn-primary" name="doupdate" id="doupdate" value="'.tr('Update').'">
                            </div>
                        </section>
                    </div>
                </div>
            </form>';

/*
 * AddJS validation
 */
$vj = new validate_jquery();

$vj->validate('password', 'required' , 'true'     , '<span class="FcbErrorTail"></span>'.tr('Please provide a password'));
$vj->validate('password', 'minlength', '8'        , '<span class="FcbErrorTail"></span>'.tr('Please ensure that the password has at least 8 characters'));
$vj->validate('password2', 'equalTo' , '#password', '<span class="FcbErrorTail"></span>'.tr('The password fields need to be equal'));

$html .= $vj->output_validation(array('id'   => 'user',
                                      'json' => false));

$params = array('title'       => tr('Update password'),
                'icon'        => 'fa-user',
                'breadcrumbs' => array(tr('Users'), tr('Profile'), tr('Password')));

echo ca_page($html, $params);


/*
 *
 */
function s_validate_password(&$user, $id = null){
    try{
        // Validate input
        $v = new validate_form($user, 'password,password2');

        $v->hasMinChars ($user['password'],                  8, tr('Please ensure that the password has a minimum of 8 characters'));
        $v->isEqual     ($user['password'], $user['password2'], tr('Please ensure that the password and validation password match'));

    }catch(Exception $e){
        throw new bException('s_validate_password(): Failed', $e);
    }
}
?>