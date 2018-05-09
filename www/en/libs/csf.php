<?php
/*
 * CSF library
 *
 * This is a front-end library to the Config Server Firewall
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 */



/*
 * On the command line we can only run this as root user
 */
if(PLATFORM_CLI){
    cli_root_only();
}



/*
 * Return the absolute location of the CSF executable binary
 */
function csf_get_exec(){
    try{
        return trim(shell_exec('which csf 2> /dev/null'));

    }catch(Exception $e){
        throw new bException('csf_get_exec(): Failed', $e);
    }
}



/*
 * Install Config Server Firewall
 */
function csf_install(){
    try{
        if($csf = csf_get_exec()){
            throw new bException('csf_install(): CSF has already been installed and is available from "'.str_log($csf).'"', 'executablenotfound');
        }

        copy('http://configserver.com/free/csf.tgz', TMP.'csf.tgz');
        safe_exec('cd '.TMP.'; tar -xf '.TMP.'csf.tgz; cd '.TMP.'csf/; ./install.sh');

        if(!$csf = csf_get_exec()){
            throw new bException('csf_install(): The CSF executable could not be found after installation', 'executablenotfound');
        }

        /*
         * Cleanup
         */
        safe_exec('rm '.TMP.'csf/ -rf');

        return $csf;

    }catch(Exception $e){
        throw new bException('csf_install(): Failed', $e);
    }
}
?>
