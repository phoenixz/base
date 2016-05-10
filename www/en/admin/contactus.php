<?php
require_once(dirname(__FILE__).'/../libs/startup.php');

page_404();

rights_or_redirect('admin');

$selected = isset_get($_GET['id']);

/*
 * We have to do something?
 */
switch(isset_get($_POST['doaction'])){
    case tr('Delete'):
        try{
            /*
             * Delete the specified contact messages
             */
            if(empty($_POST['id'])){
                throw new bException('No contact messages selected to delete', 'notspecified');
            }

            $list = array_prefix(array_force($_POST['id']), ':id', true);

            $r = sql_query('UPDATE `contactus`
                            SET    `status` = "deleted"
                            WHERE  `status` IS NULL AND `id` IN ('.implode(', ', array_keys($list)).')',

                       $list);

            if($r->rowCount()){
                html_flash_set(tr('Deleted %count% contact messages', '%count%', $r->rowCount()), 'success');

            }else{
                throw new bException(tr('Found no contact messages to delete'), 'notfound');
            }

        }catch(Exception $e){
            html_flash_set(tr('Failed to delete contact messages because "'.$e->getMessage().'"'), 'error');
        }

        break;

    case tr('Undelete'):
        try{
            /*
             * Delete the specified contact messages
             */
            if(empty($_POST['id'])){
                throw new bException('No contact messages selected to undelete', 'notspecified');
            }

            $list = array_prefix(array_force($_POST['id']), ':id', true);

            $r = sql_query('UPDATE `contactus`
                            SET    `status` = NULL
                            WHERE  `status` = "deleted" AND `id` IN ('.implode(', ', array_keys($list)).')',

                       $list);

            if($r->rowCount()){
                html_flash_set(tr('Undeleted %count% contact messages', '%count%', $r->rowCount()), 'success');

            }else{
                throw new bException(tr('Found no contact messages to undelete'), 'notfound');
            }

        }catch(Exception $e){
            html_flash_set(tr('Failed to undelete contact messages because "'.$e->getMessage().'"'), 'error');
        }

        break;

    case tr('Erase'):
        try{
            /*
             * Delete the specified contact messages
             */
            if(empty($_POST['id'])){
                throw new bException('No contact messages selected to erase', 'notspecified');
            }

            $list = array_prefix(array_force($_POST['id']), ':id', true);

            $r = sql_query('DELETE FROM `contactus` WHERE `status` = "deleted" AND `id` IN ('.implode(', ', array_keys($list)).')', $list);

            if($r->rowCount()){
                html_flash_set(tr('Erased %count% contact messages', '%count%', $r->rowCount()), 'success');

            }else{
                throw new bException(tr('Found no contact messages to erase'), 'notfound');
            }

        }catch(Exception $e){
            html_flash_set(tr('Failed to erase contact messages because "'.$e->getMessage().'"'), 'error');
        }
}

$html = '<h2>'.tr('Available contact messages').'</h2>
<div class="display">
    <form action="'.domain('/admin/contactus.php').'" method="post">
        <table class="link select">';

$r = sql_query('SELECT `contactus`.`id`,
                       `contactus`.`createdon`,
                       `contactus`.`status`,
                       `contactus`.`name`,
                       `contactus`.`email`,
                       `contactus`.`message`

                FROM   `contactus`');

if(!$r->rowCount()){
    $html .= '<tr><td>'.tr('There are no contact messages yet').'</td></tr>';

}else{
    $html .= '<thead><td class="select"><input type="checkbox" name="id[]" class="all"></td><td>'.tr('Created on').'</td><td>'.tr('Name').'</td><td>'.tr('Email').'</td><td>'.tr('message').'</td><td>'.tr('Status').'</td></thead>';

    while($contact = sql_fetch($r)){
        $html .= '<tr'.($selected == $contact['id'] ? ' class="selected"' : '').'>
                      <td class="select"><input type="checkbox" name="id[]" value="'.$contact['id'].'"></td>
                      <td>'.$contact['createdon'].'</td>
                      <td>'.$contact['name'].'</td>
                      <td>'.$contact['email'].'</td>
                      <td>'.$contact['message'].'</td>
                      <td>'.($contact['status'] ? $contact['status'] : 'unread').'</td>
                  </tr>';
    }
}

$html .= '</table>';

if($r->rowCount()){
    $html .= '<input type="submit" name="doaction" value="'.tr('Delete').'">
              <input type="submit" name="doaction" value="'.tr('Undelete').'">
              <input type="submit" name="doaction" value="'.tr('Erase').'">';
}

$html .= '</form>
        </div>';

echo admin_page($html, array('title'  => tr('Contact messages management'),
                             'script' => 'contactus.php'));
?>
