<?php
/*
 * Rsync library
 *
 * This library is an rsync frontend and contains functions to
 * sync directories from local server with remote server
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 */


/*
 * Front end for the command line rsync command. Performs rsync with the specified parameters
 *
 * @auhthor Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package rsync
 *
 * @param array $params
 * @params string source
 * @params string target
 * @params boolean passthru passes output of the rsync command straight to the console or client. Defaults to true on CLI mode, and false on HTTP mode
 * @params boolean archive archive mode (-a or --archive in rsync)
 * @params boolean checksum skip based on checksum, not mod-time & size (--checksum in rsync)
 * @params boolean compression compress file data during the transfer (-z or --compress in rsync)
 * @params boolean delete
 * @params boolean force
 * @params boolean group
 * @params boolean links copy symbolic links as symbolic links and not their targets
 * @params boolean owner
 * @params boolean permissions
 * @params boolean recursive
 * @params boolean super
 * @params boolean time
 * @params string ssh_string The command used to execute SSH. Defaults to "ssh", but can be used as (for example) "ssh -e 6375" to have SSH connect to a port different than the default 22
 * @params string remote_rsync How to run rsync on the remote server (if aplicable). Example: 'sudo rsync' to run rsync on the remote server as root
 * @return void
 *
 * @example
 * rsync(array('source' => ROOT
 *             'target' => server:/))
 * rsync -acz --progress -p --delete -e "ssh -p '.$_CONFIG['deploy']['target_port'].'" '.ROOT.' '.$_CONFIG['deploy']['target_user'].'@'.$_CONFIG['deploy']['target_server'].':'.$_CONFIG['deploy']['target_dir'].' '.$exclude
 */
function rsync($params){
    load_libs('ssh');

    try{
        array_params($params, 'source');
        array_default($params, 'source'      , '');
        array_default($params, 'target'      , '');
        array_default($params, 'passthru'    , PLATFORM_CLI);
        array_default($params, 'archive'     , true);
        array_default($params, 'checksum'    , true);
        array_default($params, 'compression' , true);
        array_default($params, 'delete'      , true);
        array_default($params, 'force'       , true);
        array_default($params, 'group'       , true);
        array_default($params, 'links'       , true);
        array_default($params, 'owner'       , true);
        array_default($params, 'permissions' , true);
        array_default($params, 'recursive'   , true);
        array_default($params, 'super'       , false);
        array_default($params, 'time'        , true);
        array_default($params, 'port'        , null);
        array_default($params, 'ssh_options' , null);
        array_default($params, 'remote_rsync', false);

        if(!$params['source']){
            throw new bException(tr('rsync(): No source specified'), 'not-specified');
        }

        if(!$params['target']){
            throw new bException(tr('rsync(): No target specified'), 'not-specified');
        }

        if($params['source'] == $params['target']){
            throw new bException(tr('rsync(): Specified source and target are the same'), 'not-specified');
        }

        $command    = 'rsync';
        $ssh_string = ssh_get_options_string($params['ssh_options']);

        if($ssh_string){
            $command = ' -e "ssh '.$ssh_string.'"';
        }

        if($params['archive']){
            $command .= ' -a';
        }

        if($params['checksum']){
            $command .= ' -c';
        }

        if($params['compression']){
            $command .= ' -z';
        }

        if($params['delete']){
            $command .= ' --delete';
        }

        if($params['force']){
            $command .= ' --force';
        }

        if($params['group']){
            $command .= ' -g';
        }

        if($params['links']){
            $command .= ' -l';
        }

        if($params['owner']){
            $command .= ' -o';
        }

        if($params['permissions']){
            $command .= ' -p';
        }

        if($params['recursive']){
            $command .= ' -r';
        }

        if($params['remote_rsync']){
            $command .= ' --rsync-path="'.$params['remote_rsync'].'"';
        }

        if($params['ssh_string']){
            $command .= ' -e "'.$params['shell'].'"';
        }

        if($params['super']){
            $command .= ' --super';
        }

        if($params['time']){
            $command .= ' -t';
        }

        $command .= ' '.$params['source'].' '.$params['destination'];

        log_file($command, 'rsync');

        if($params['passthru']){
            passthru($command);

        }else{
            safe_exec($command);
        }

    }catch(Exception $e){
        throw new bException('rsync(): Failed', $e);
    }
}
?>
