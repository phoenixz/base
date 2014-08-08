<?php
/*
 * CSF library
 *
 * This is a front-end library to the Config Server Firewall
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>
 */



/*
 * On the command line we can only run this as root user
 */
if(PLATFORM == 'shell'){
    cli_root_only();
}



/*
 * Return the absolute location of the CSF executable binary
 */
function csf_get_exec(){
    try{
        return safe_exec('which csf 2> /dev/null');

    }catch(Exception $e){
        throw new lsException('csf_get_exec(): Failed', $e);
    }
}



/*
 * Install Config Server Firewall
 */
function csf_install(){
    try{
        if(!$csf = csf_get_exec()){
            throw new lsException('csf_install(): CSF has already been installed', 'executablenotfound');
        }

        file_copy_progress('http://configserver.com/free/csf.tgz', TMP.'csf.tgz');
        safe_exec('tar -xf '.TMP.'csf.tgz');
        safe_exec('cd '.TMP.'csf/; ./install.sh');

        if(!$csf = csf_get_exec()){
            throw new lsException('csf_install(): The CSF executable could not be found after installation', 'executablenotfound');
        }

        return $csf;

    }catch(Exception $e){
        throw new lsException('csf_install(): Failed', $e);
    }
}
?>
