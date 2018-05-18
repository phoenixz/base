<?php
/*
 * Apache library
 *
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 */



/*
 *
 *<VirtualHost *:80>
    ServerName strategyq.org
    ServerAlias *.strategyq.org
    ProxyPreserveHost On

    ProxyPass / http://189.210.119.175:40001/
    ProxyPassReverse / http://189.210.119.175:40001/
</VirtualHost>
 */
function apache_set_vhost($hostname, $vhost_name, $params, $port, $linux_version='ubuntu'){
    try{
        $params     = apache_validate_params($params);
        $vhost_name = apache_validate_vhostname($vhost_name);
        $full_path  = apache_get_paht_vhosts($linux_version).$vhost_name;

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
function apache_get_apache_config_path($linux_version='ubuntu'){
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



/*
 *
 */
function apache_get_paht_vhosts($linux_version='ubuntu'){
    try{
        if(empty($linux_version)){
            throw new bException(tr('No linux version'), 'not-specified');
        }
        switch($linux_version){
            case 'ubuntu':
                $vhost_path = '/etc/apache2/sites-available/';
                break;
            case 'centos6':
                // FALLTROUGH
            case 'centos7':
                $vhost_path = '/etc/httpd/conf.d/';
                break;
            default:
                throw new bException(tr('apache_get_paht_vhosts(): Invalid linux version value ":linuxversion" specified', array(':linuxversion' => $linux_version)), 'invalid');
        }

        return $vhost_path;

    } catch(Exception $e){
        throw new bException('empty(): Failed', $e);
    }
}



/*
 *
 */
function apache_validate_params($params){
    try{
        if(empty($params)){
            throw new bException(tr('No linux version'), 'not-specified');
        }

        if(!is_array($params)){
            throw new bException(tr('Params are not valid. No correct format. Must be an array key=>value'), 'invalid');
        }

        return $params;

    }catch(Exception $e){
        throw new bException('apache_validate_params(): Failed', $e);
    }
}



/*
 *
 */
function apache_validate_vhostname($vhost_name){
    try{
        if(empty($vhost_name)){
            throw new bException(tr('No vhost name specified'), 'not-specified');
        }

        preg_match("/.conf\z/", $vhost_name, $matches);

        if(empty($matches)){
            $vhost_name = rtrim($vhost_name,'.').'.conf';
        }

        return $vhost_name;

    }catch(Exception $e){
        throw new bException('apache_validate_vhostname(): Failed', $e);
    }
}
?>