<?php
/*
 * Slack library
 *
 * This library is a front-end slack extension library
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 *
 * See https://www.twilio.com/blog/2017/02/how-to-build-a-slack-bot-using-php.html
 * See https://stackoverflow.com/questions/21133/simplest-way-to-profile-a-php-script
 */



/*
 * Initialize the library
 * Automatically executed by libs_load()
 */
function slack_library_init(){
    try{
        ensure_installed(array('name'      => 'slack',
                               'project'   => 'slack',
                               'callback'  => 'slack_install',
                               'checks'    => array()));

        load_config('slack');

    }catch(Exception $e){
        throw new bException('slack_library_init(): Failed', $e);
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
