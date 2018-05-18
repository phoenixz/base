<?php
/*
 * Apache library
 *
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 */



/*
 *
 */
function apache_get_proxy_template($hostname){
    try{

    }catch(Exception $e){
        throw new bException('apache_set_proxy_configuration(): Failed', $e);
    }
}



/*
 *
 */
function apache_get_proxy_configuration_ssl($hostname){
    try{

    }catch(Exception $e){
        throw new bException('apache_set_proxy_configuration(): Failed', $e);
    }
}



/*
 *
 */
function apache_turn_off_signature($hostname, $linux_version='ubuntu'){
    try{
        $config_path = apache_get_apache_config_path($linux_version);
        $command     = 'if ! grep "ServerSignature" '.$config_path.'; then echo "ServerSignature off" >> '.$config_path.'; fi;if ! grep "ServerTokens" '.$config_path.'; then echo "ServerTokens Prod" >> '.$config_path.'; fi;';
        $result      = servers_exec($hostname, $command);

        return $result;

    }catch(Exception $e){
        throw new bException('apache_turn_off_signature(): Failed', $e);
    }
}



/*
 *
 */
function apache_get_apache_config_path($linux_version){
    try{
        if(empty($linux_version)){
            throw new bException(tr('No linux version'), 'not-specified');
        }
        switch($linux_version){
            case 'ubuntu':
                $config_path = '/etc/apache2/apache2.conf';
                break;
            case 'centos6':
                // FALLTROUGH
            case 'centos7':
                $config_path = '/etc/httpd/conf/httpd.conf';
                break;
            default:
                throw new bException(tr('apache_get_apache_config_path(): Invalid linux version value ":linuxversion" specified', array(':linuxversion' => $linux_version)), 'invalid');
        }

        return $config_path;

    }catch(Exception $e){
        throw new bException('empty(): Failed', $e);
    }
}
?>