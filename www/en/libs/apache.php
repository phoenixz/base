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
function apache_write_vhost($hostname, $vhost_name, $params, $port){
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
                servers_exec($hostname, $command);
                break;

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
 * ....
 *
 * ServerName
 * ServerAdmin
 * ServerSignature
 * ServerTokens
 * UseCanonicalName
 * UseCanonicalPhysicalPort
 */
function apache_set_identification($hostname, $params){
    try{
        $command     = '';
        $config_path = apache_get_config_path($hostname);;

        foreach($params as $key => $value){
            $command .= 'if grep "'.$key.'" "'.$config_path.'"; then sed -i "s/'.$key.'[[:space:]]*.*/'.$key.' '.$value.'/g" "'.$config_path.'"; else echo "'.$key.' '.$value.'" >> '.$config_path.'; fi;';
        }

        servers_exec($hostname, $command);

    }catch(Exception $e){
        throw new bException('apache_set_identification(): Failed', $e);
    }
}



/*
 * .......
 */
function apache_get_vhosts_path($server_os){
    try{
        if(empty($server_os['name'])){
            throw new bException(tr('apache_get_vhosts_path(): No operating system specified'), 'not-specified');
        }
        switch($server_os['name']){
            case 'linuxmint':
                //FALL THROUGH
            case 'ubuntu':
                $vhost_path = '/etc/apache2/sites-available/';
                break;

            case 'centos':
                $vhost_path = '/etc/httpd/conf.d/';
                break;

            default:
                throw new bException(tr('apache_get_vhosts_path(): Invalid operating system ":os" specified', array(':os' => $server_os['name'])), 'invalid');
        }

        return $vhost_path;

    } catch(Exception $e){
        throw new bException('apache_get_vhosts_path(): Failed', $e);
    }
}



/*
 *
 */
function apache_get_config_path($hostname){
    try{
        $server_os = servers_detect_os($hostname);

        switch($server_os['name']){
            case 'linuxmint':
                //FALL THROUGH
            case 'ubuntu':
                $path = '/etc/apache2/apache2.conf';
                break;

            case 'centos':
                $path = '/etc/httpd/conf/httpd.conf';
                break;

            default:
                throw new bException(tr('apache_get_config_path(): Invalid operating system ":os" specified', array(':os' => $server_os['name'])), 'invalid');
        }

        return $path;

    }catch(Exception $e){
        throw new bException('apache_get_config_path(): Failed', $e);
    }
}
?>