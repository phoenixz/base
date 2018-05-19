<?php
/*
 * This is the standard PHP apache frontend library
 *
 * This library contains functions to manage apache and its configuration on remove or local servers
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Capmega <copyright@capmega.com>
 */



/*
 * Initialize the library
 * Automatically executed by libs_load()
 */
function apache_library_init(){
    try{
        load_libs('servers');

    }catch(Exception $e){
        throw new bException('apache_library_init(): Failed', $e);
    }
}



/*
 * Example:
 *
 * <VirtualHost *:80>
 *   ServerName servername.org
 *   ServerAlias *.servername.org
 *   ProxyPreserveHost On
 *
 *   ProxyPass / http://255.255.255.255:9999/
 *   ProxyPassReverse / http://1.1.1.1:9999/
 * </VirtualHost>
 */
function apache_write_vhost($hostname, $vhost_name, $params, $port, $linux_version='ubuntu'){
    try{
        $os = servers_get_os($hostname);

        switch($os['name']){
            case 'debian':
                // FALLTHROUGH
            case 'ubuntu':
                // FALLTHROUGH
            case 'mint':
                $params    = array_ensure($params);
                $full_path = apache_get_vhosts_path($os['name']).$vhost_name.'.conf';

                /*
                 * Cleaning content of file in case already exist
                 */
                $command  = '> '.$full_path.';';
                $command .= 'echo "<VirtualHost *:'.$port.'>" >> '.$full_path.';';

                foreach($params as $key => $value){
                    $command .= 'echo  "  '.$key.' '.$value.'" >> '.$full_path.';';
                }

                $command .= 'echo "</VirtualHost>" >> '.$full_path.';';
                $result   = servers_exec($hostname, $command);

                return $result;

            case 'redhat':
                // FALLTHROUGH
            case 'fedora':
                // FALLTHROUGH
            case 'centos':
// :TODO: Implement
                break;

            default:
                throw new bException(tr('apache_write_vhost(): Unknown operating system ":os" detected', array(':os' => $os['name'])), 'unknown');
        }

    }catch(Exception $e){
        throw new bException('apache_write_vhost(): Failed', $e);
    }
}



/*
 *
 */
function apache_set_signature($hostname, $type){
    try{
        $os = servers_get_os($hostname);

        switch($os['name']){
            case 'debian':
                // FALLTHROUGH
            case 'ubuntu':
                // FALLTHROUGH
            case 'mint':
                $config_path = apache_get_config_path($os['name']);
                $command     = 'if ! grep "ServerSignature" '.$config_path.'; then echo "ServerSignature off" >> '.$config_path.'; fi;if ! grep "ServerTokens" '.$config_path.'; then echo "ServerTokens Prod" >> '.$config_path.'; fi;';
                $result      = servers_exec($hostname, $command);
                break;

            case 'redhat':
                // FALLTHROUGH
            case 'fedora':
                // FALLTHROUGH
            case 'centos':
                //$config_path = apache_get_config_path($os['name']);
                //$command     = 'if ! grep "ServerSignature" '.$config_path.'; then echo "ServerSignature off" >> '.$config_path.'; fi;if ! grep "ServerTokens" '.$config_path.'; then echo "ServerTokens Prod" >> '.$config_path.'; fi;';
                //$result      = servers_exec($hostname, $command);
                break;

            default:
                throw new bException(tr('apache_set_signature(): Unknown operating system ":os" detected', array(':os' => $os['name'])), 'unknown');
        }

        return $result;

    }catch(Exception $e){
        throw new bException('apache_turn_off_signature(): Failed', $e);
    }
}



/*
 *
 */
function apache_get_config_path($os_name){
    try{
        switch($os_name){
            case 'debian':
                // FALLTHROUGH
            case 'ubuntu':
                // FALLTHROUGH
            case 'mint':
                $config_path = '/etc/apache2/apache2.conf';
                break;

            case 'redhat':
                // FALLTHROUGH
            case 'fedora':
                // FALLTHROUGH
            case 'centos':
                $config_path = '/etc/httpd/conf/httpd.conf';
                break;

            default:
                throw new bException(tr('apache_get_config_path(): Unknown operating system ":os" detected', array(':os' => $os_name)), 'unknown');
        }

        return $config_path;

    }catch(Exception $e){
        throw new bException('apache_get_config_path(): Failed', $e);
    }
}



/*
 *
 */
function apache_get_vhosts_path($os_name){
    try{
        switch($os_name){
            case 'debian':
                // FALLTHROUGH
            case 'ubuntu':
                // FALLTHROUGH
            case 'mint':
                $vhost_path = '/etc/apache2/sites-available/';
                break;

            case 'redhat':
                // FALLTHROUGH
            case 'fedora':
                // FALLTHROUGH
            case 'centos':
                $vhost_path = '/etc/httpd/conf.d/';
                break;

            default:
                throw new bException(tr('apache_get_vhosts_path(): Unknown operating system ":os" detected', array(':os' => $os_name)), 'unknown');
        }

        return $vhost_path;

    } catch(Exception $e){
        throw new bException('apache_get_vhosts_path(): Failed', $e);
    }
}
?>