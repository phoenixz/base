<?php
require_once(dirname(__FILE__).'/../libs/startup.php');

rights_or_access_denied('admin,rights,modify');
load_libs('validate');


$right = array();


/*
 * Are we editing a right?
 * If so then get the right data from the DB
 */
if(!empty($_GET['right'])){
    $db = sql_get('SELECT    `rights`.`id`,
                             `rights`.`createdon`,
                             `rights`.`modifiedon`,
                             `rights`.`name`,
                             `rights`.`description`,
                             `createdby`.`name`  AS `createdby`,
                             `modifiedby`.`name` AS `modifiedby`

                   FROM      `rights`

                   LEFT JOIN `users` as `createdby`
                   ON        `rights`.`createdby`    = `createdby`.`id`

                   LEFT JOIN `users` as `modifiedby`
                   ON        `rights`.`modifiedby`   = `modifiedby`.`id`

                   WHERE     `rights`.`name`         = :right',

                   array(':right' => $_GET['right']));

    if(!$db){
        html_flash_set(log_database(tr('Specified right "'.str_log($_GET['right']).'" does not exist'), 'right_not_exist'), 'error');
        redirect(domain('/admin/rights.php'));
    }

    log_database(tr('View right "'.str_log($_GET['right']).'"'), 'right_view');

    $right = array_merge($db, $right);
    unset($db);

    if($right['createdon']){
        $right['createdon']  = new DateTime($right['createdon']);
        $right['createdon']  = $right['createdon']->format($_CONFIG['formats']['human_datetime']);
    }

    if($right['modifiedon']){
        $right['modifiedon'] = new DateTime($right['modifiedon']);
        $right['modifiedon'] = $right['modifiedon']->format($_CONFIG['formats']['human_datetime']);
    }
}


/*
 * Was right data submitted?
 */
if(!empty($_POST['dosubmit'])){
    $right = array_merge($right, $_POST);

    if(!empty($right['id'])){
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
        s_validate_right($right);

        /*
         * This right does not exist yet?
         */
        if(sql_get('SELECT `id` FROM `rights` WHERE `name` = :name', 'id', array(':name' => $right['name']))){
            throw new bException(tr('The right "%name%" already exists', '%name%', str_log($right['name'])), 'exists');
        }

        sql_query('INSERT INTO `rights` (`createdby`, `name`, `description`)
                   VALUES               (:createdby , :name , :description )',

                   array(':createdby'   => $_SESSION['user']['id'],
                         ':name'        => $right['name'],
                         ':description' => $right['description']));

        html_flash_set(log_database('Created right "'.str_log($right['name']).'"', 'right_create'), 'success');

        $right = array();

    }elseif(isset_get($_POST['doupdate'])){
        if(empty($right['id'])){
            throw new bException('Cannot update, no right specified', 'notspecified');
        }

        /*
         * Validate data
         */
        s_validate_right($right);

        /*
         * This right does not exist yet?
         */
        if(sql_get('SELECT `name` FROM `rights` WHERE `name` = :name AND `id` != :id', 'id', array(':name' => $right['name'], ':id' => $right['id']))){
            throw new bException(tr('The right "%name%" already exists', '%name%', str_log($right['name'])), 'exists');
        }

        sql_query('UPDATE `rights`

                   SET    `modifiedby`  = :modifiedby,
                          `modifiedon`  = NOW(),
                          `name`        = :name,
                          `description` = :description

                   WHERE  `id`          = :id',

                   array(':id'          => $right['id'],
                         ':modifiedby'  => $_SESSION['user']['id'],
                         ':name'        => $right['name'],
                         ':description' => $right['description']));

        html_flash_set(log_database('Updated right "'.str_log($right['name']).'"', 'right_update'), 'success');
        redirect(domain('/admin/right.php?right='.$right['name']));
    }

}catch(Exception $e){
    html_flash_set($e);
}


/*
 * Build page HTML
 */
$html   = ' <form id="right" name="right" action="'.domain(true).'" method="post">
                '.html_form().'
                '.html_hidden($right).'
                <div class="row">
                    <div class="col-md-12">
                        <section class="panel">
                            <header class="panel-heading">
                                <h2 class="panel-title">'.(empty($right['id']) ? tr('Create new right') : tr('Modify right')).'</h2>
                                <p>'.html_flash().'</p>
                            </header>
                            <div class="panel-body">';

if(!empty($right['id'])){
    $html .= '                  <div class="form-group">
                                    <label class="col-md-3 control-label" for="createdon">'.tr('Created on').'</label>
                                    <div class="col-md-6">
                                        <input type="text" name="createdon" id="createdon" class="form-control" value="'.isset_get($right['createdon']).'" disabled>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-md-3 control-label" for="createdby">'.tr('Created by').'</label>
                                    <div class="col-md-6">
                                        <input type="text" name="createdby" id="createdby" class="form-control" value="'.isset_get($right['createdby']).'" disabled>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-md-3 control-label" for="modifiedon">'.tr('Modified on').'</label>
                                    <div class="col-md-6">
                                        <input type="text" name="modifiedon" id="modifiedon" class="form-control" value="'.isset_get($right['modifiedon']).'" disabled>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-md-3 control-label" for="modifiedby">'.tr('Modified by').'</label>
                                    <div class="col-md-6">
                                        <input type="text" name="modifiedby" id="modifiedby" class="form-control" value="'.isset_get($right['modifiedby']).'" disabled>
                                    </div>
                                </div>';
}

$html .= '                      <div class="form-group">
                                    <label class="col-md-3 control-label" for="name">'.tr('Name').'</label>
                                    <div class="col-md-6">
                                        <input type="text" name="name" id="name" class="form-control" value="'.isset_get($right['name']).'" maxlength="16"'.((empty($right['name']) and empty($right['description'])) ? ' autofocus' : '').'>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-md-3 control-label" for="description">'.tr('Description').'</label>
                                    <div class="col-md-6">
                                        <textarea name="description" id="description" class="form-control" maxlength="255"'.((!empty($right['name']) and empty($right['description'])) ? ' autofocus' : '').'>'.isset_get($right['description']).'</textarea>
                                    </div>
                                </div>
                                '.(empty($right['id']) ? '<input type="submit" class="mb-xs mt-xs mr-xs btn btn-primary" name="docreate" id="docreate" value="'.tr('Create').'">'
                                                      : '<input type="submit" class="mb-xs mt-xs mr-xs btn btn-primary" name="doupdate" id="doupdate" value="'.tr('Update').'">').'
                                <a class="mb-xs mt-xs mr-xs btn btn-primary" href="'.domain('/admin/rights.php'.(empty($_POST['right']) ? '' : '?right='.$_POST['right'])).'">'.tr('Manage rights').'</a>
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
$vj->validate('name'       , 'minlength', '3'   , '<span class="FcbErrorTail"></span>'.tr('Please ensure that the name has at least 3 characters'));
$vj->validate('description', 'required' , 'true', '<span class="FcbErrorTail"></span>'.tr('Please provide a description'));
$vj->validate('description', 'minlength', '16'  , '<span class="FcbErrorTail"></span>'.tr('Please ensure that the description has at least 16 characters'));

$html .= $vj->output_validation(array('id'   => 'right',
				                      'json' => false));


$params = array('title'       => tr('rights'),
                'icon'        => 'fa-lock',
                'breadcrumbs' => array(tr('right'), tr('Modify')));

echo ca_page($html, $params);



/*
 * Validate the data of the specified right
 */
function s_validate_right(&$right){
    try{
        $v = new validate_form($right, 'name,description,rights');

        $v->isNotEmpty  ($right['name']     , tr('Please provide a name'));
        $v->hasMinChars ($right['name'],   4, tr('Please ensure that the name has a minimum of 4 characters'));
        $v->hasMaxChars ($right['name'],  16, tr('Please ensure that the name has a maximum of 16 characters'));

        if(strpos($right['name'], ' ') !== false){
            $v->setError(tr('Please ensure that the rights name contains no spaces'));
        }

        $v->isNotEmpty  ($right['description']     , tr('Please provide a description'));
        $v->hasMinChars ($right['description'],  16, tr('Please ensure that the description has a minimum of 16 characters'));
        $v->hasMaxChars ($right['description'], 255, tr('Please ensure that the description has a maximum of 255 characters'));

        if(!is_array(isset_get($right['rights']))){
            if(!empty($right['rights'])){
                $v->setError(tr('Specified rights list is invalid'));
            }

            $right['rights'] = array();
        }

        /*
         * Ensure that all rights are unique and ordered by name
         */
        $right['rights'] = array_unique($right['rights']);
        sort($right['rights']);

        if(!$v->isValid()) {
            throw new bException($v->getErrors(), 'invalid');
        }

    }catch(Exception $e){
        throw new bException('s_validate_right(): Failed', $e);
    }
}
?>