<?php
require_once(dirname(__FILE__).'/../libs/startup.php');

right_or_redirect('admin,users');
load_libs('admin,user');

if(isset_get($_POST['doadd'])){
    try{
        /*
         * Add a new user right
         *
         * First validate data
         */
        if(empty($_POST['user'])){
            throw new lsException('No user specified', 'notspecified');
        }

        if(empty($_POST['right'])){
            throw new lsException('No right specified', 'notspecified');
        }

        if(!is_numeric($_POST['user'])){
            throw new lsException('Invalid user specified', 'invalid');
        }

        if(!is_numeric($_POST['right'])){
            throw new lsException('Invalid right specified', 'invalid');
        }

        /*
         * User and right exist?
         */
        if(!$user = sql_get('SELECT `name` FROM `users` WHERE `id` = :id AND (`status` IS NULL)', 'name', array('id' => $_POST['user']))){
            throw new lsException('Specified user "'.str_log($_POST['user']).'" does not exist', 'notexist');
        }

        if(!$right = sql_get('SELECT `name` FROM `rights` WHERE `id` = :id', 'name', array('id' => $_POST['right']))){
            throw new lsException('Specified right "'.str_log($_POST['right']).'" does not exist', 'notexist');
        }

        /*
         * This user does not already have this right?
         */
        if(sql_get('SELECT `id` FROM `users_rights` WHERE `users_id` = :users_id AND `rights_id` = :rights_id', 'id', array(':users_id' => $_POST['user'], ':rights_id' => $_POST['right']))){
            throw new lsException('User "'.str_log($user).'" already has the right "'.str_log($right).'"', 'alreadyadded');
        }

        sql_query('INSERT INTO `users_rights` (`addedby`, `users_id`, `rights_id`, `name`)
                   VALUES                     (:addedby , :users_id , :rights_id , :name )',

                   array(':addedby'   => $_SESSION['user']['id'],
                         ':users_id'  => $_POST['user'],
                         ':rights_id' => $_POST['right'],
                         ':name'      => $right));

        log_database('Added new right "'.str_log($right).'" for user "'.str_log($user).'"', 'adduserright');
        html_flash_set('Added new right "'.str_log($right).'" for user "'.str_log($user).'"', 'success');

    }catch(Exception $e){
        html_flash_set($e);
    }
}

$users  = array('name'     => 'user',
                'selected' => isset_get($_POST['user']),
                'resource' => sql_query('SELECT `id`, `name` FROM `users` WHERE `status` IS NULL'));

$rights = array('name'     => 'right',
                'selected' => isset_get($_POST['right']),
                'resource' => sql_query('SELECT `id`, `name` FROM `rights`'));

$html   = ' <form id="userright" name="userright" action="'.domain(true).'" method="post">
                <fieldset>
                    <legend>'.tr('Add right for user').'</legend>
                    '.html_flash().'
                    <ul class="form">
                        <li><label for="users">'.tr('User').'</label>'.html_select($users).'</li>
                        <li><label for="rights">'.tr('Right').'</label>'.html_select($rights).'</li>
                    </ul>
                    <input type="submit" name="doadd" id="doadd" value="'.tr('Add').'"> <a class="button submit" href="'.domain('/admin/rights.php').'">'.tr('Manage rights').'</a>
                </fieldset>
            </form>';

echo admin_start((isset_get($_GET['action']) == 'create' ? tr('Create new user') : tr('Edit user')), isset_get($flash), isset_get($type, 'success')).
	 $html.
	 admin_end();
?>