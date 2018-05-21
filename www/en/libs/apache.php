<?php
/*
 * Apache library
 *
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
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
 *
 *<VirtualHost *:80>
 *   ServerName domain.com
 *   ServerAlias *.domain.org
 *   ProxyPreserveHost On

 *   ProxyPass / http://255.255.255.255:8080/
 *   ProxyPassReverse / http://255.255.255.255:8081/
</VirtualHost>
 */
function apache_set_vhost($hostname, $vhost_name, $params, $port){
    try{
        $params     = apache_validate_params($params);

        if(substr($vhost_name, -5, 5) != '.conf'){
            $vhost_name .= '.conf';
        }

        $server_os  = servers_detect_os($hostname);
        $full_path  = apache_get_path_vhosts($server_os).$vhost_name;

        /*
         *
         *Cleaning content of file in case already exist
         */
        $command  = '> '.$full_path.';';
        $command .= 'echo "<VirtualHost *:'.$port.'>" >> '.$full_path.';';

        foreach($params as $key => $value){
            $command .= 'echo  "  '.$key.' '.$value.'" >> '.$full_path.';';
        }
        $command .= 'echo "</VirtualHost>" >> '.$full_path.';';

        $result = servers_exec($hostname, $command);

        return $result;

    }catch(Exception $e){
        throw new bException('apache_set_vhost(): Failed', $e);
    }
}



/*
 *ServerName
 *ServerAdmin
 *ServerSignature
 *ServerTokens
 *UseCanonicalName
 *UseCanonicalPhysicalPort
 */
function apache_set_identification($hostname, $params){
    try{
        $command     = '';
        $config_path = apache_get_path_config($hostname);;

        foreach($params as $key => $value){
            $command .= 'if grep "'.$key.'" "'.$config_path.'"; then sed -i "s/'.$key.'[[:space:]]*.*/'.$key.' '.$value.'/g" "'.$config_path.'"; else echo "'.$key.' '.$value.'" >> '.$config_path.'; fi;';
        }

        $result      = servers_exec($hostname, $command);

        return $result;

    }catch(Exception $e){
        throw new bException('apache_set_identification(): Failed', $e);
    }
}



/*
 *
 */
function apache_get_path_vhosts($server_os){
    try{
        if(empty($server_os['id'])){
            throw new bException(tr('apache_get_path_vhosts(): No operating system specified'), 'not-specified');
        }
        switch($server_os['id']){
            case 'linuxmint':
                //FALL THROUGH
            case 'ubuntu':
                $vhost_path = '/etc/apache2/sites-available/';
                break;

            case 'centos':
                $vhost_path = '/etc/httpd/conf.d/';
                break;

            default:
                throw new bException(tr('apache_get_path_vhosts(): Invalid linux version value ":linuxversion" specified', array(':linuxversion' => $linux_version)), 'invalid');
        }

        return $vhost_path;

    } catch(Exception $e){
        throw new bException('apache_get_path_vhosts(): Failed', $e);
    }
}



/*
 *
 */
function apache_get_path_config($hostname){
    try{
        $server_os = servers_detect_os($hostname);

        switch($server_os['id']){
            case 'linuxmint':
                //FALL THROUGH
            case 'ubuntu':
                $path = '/etc/apache2/apache2.conf';
                break;

            case 'centos':
                $path = '/etc/httpd/conf/httpd.conf';
                break;

            default:
                throw new bException(tr('apache_get_path_config(): Invalid operating system ":os" specified', array(':os' => $server_os['id'])), 'invalid');
        }

        return $path;

    }catch(Exception $e){
        throw new bException('apache_get_config(): Failed', $e);
    }
}



/*
 *
 */
function apache_validate_params($params){
    try{
        if(empty($params)){
            throw new bException(tr('apache_validate_params(): No linux version'), 'not-specified');
        }

        if(!is_array($params)){
            throw new bException(tr('apache_validate_params(): Params are not valid. No correct format. Must be an array key=>value'), 'invalid');
        }

        return $params;

    }catch(Exception $e){
        throw new bException('apache_validate_params(): Failed', $e);
    }
}
?>