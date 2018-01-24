<?php
/*
 * Slack library
 *
 * This library is a front-end slack extension library
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */






slack_init();



/*
 * Load the slack library and its CSS requirements
 */
function slack_init(){
    try{
        ensure_installed(array('name'      => 'slack',
                               'project'   => 'slack',
                               'callback'  => 'slack_install',
                               'checks'    => array()));

        load_config('slack');

    }catch(Exception $e){
        throw new bException('slack_init(): Failed', $e);
    }
}



/*
 * Install the slack library
 */
function slack_install($params){
    try{
        $params['methods'] = array('composer' => array('commands'  => 'composer install slack-client',

                                                       'locations' => array()));

        return install($params);

    }catch(Exception $e){
        throw new bException('slack_install(): Failed', $e);
    }
}



/*
 * Send a message to slack
 */
function slack_send(){
    try{

    }catch(Exception $e){
        throw new bException('slack_send(): Failed', $e);
    }
}
?>
