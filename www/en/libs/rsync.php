<?php
/*
 * Rsync library
 *
 * This library is an rsync frontend and contains functions to
 * sync directories from local server with remote server
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */


/*
 * Perform rsync
 *  rsync -acz --progress -p --delete -e "ssh -p '.$_CONFIG['deploy']['target_port'].'" '.ROOT.' '.$_CONFIG['deploy']['target_user'].'@'.$_CONFIG['deploy']['target_server'].':'.$_CONFIG['deploy']['target_dir'].' '.$exclude
 */
function rsync($params, $target = ''){
    try{
        array_params($params, 'source');
        array_default($params, 'source'     , '');
        array_default($params, 'destination', $target);

        array_default($params, 'passthru'   , true);
        array_default($params, 'archive'    , true);
        array_default($params, 'checksum'   , true);
        array_default($params, 'compression', true);
        array_default($params, 'delete'     , true);
        array_default($params, 'force'      , true);
        array_default($params, 'group'      , true);
        array_default($params, 'links'      , true);
        array_default($params, 'owner'      , true);
        array_default($params, 'permissions', true);
        array_default($params, 'recursive'  , true);
        array_default($params, 'super'      , false);
        array_default($params, 'time'       , true);
        array_default($params, 'shell'      , 'ssh');

        if(!$params['source']){
            throw new bException('rsync(): No source specified', 'not-specified');
        }

        if(!$params['destination']){
            throw new bException('rsync(): No destination specified', 'not-specified');
        }

        if($params['source'] == $params['destination']){
            throw new bException('rsync(): No destination specified', 'not-specified');
        }

        $command = 'rsync';

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

        if($params['shell']){
            $command .= ' -e "'.$params['shell'].'"';
        }

        if($params['super']){
            $command .= ' --super';
        }

        if($params['time']){
            $command .= ' -t';
        }

        $command .= ' '.$params['source'].' '.$params['destination'];

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
