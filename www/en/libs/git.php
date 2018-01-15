<?php
/*
 * GIT library
 *
 * This library contains functions to manage GIT
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */



/*
 * Clone the specified git repository to the specified path
 */
function git_clone($repository, $path){
    try{
        /*
         * Clone the repository
         */
        $retval = safe_exec('cd '.$path.'; git clone '.$repository);

    }catch(Exception $e){
        throw new bException('git_clone(): Failed', $e);
    }
}



/*
 * Get or set the current GIT branch
 */
function git_branch($branch = null, $path = ROOT){
    try{
        if($branch){
            /*
             * Set the branch
             */
            safe_exec('cd '.$path.'; git branch '.$branch);

        }else{
            /*
             * Get and return the branch
             */
            foreach(safe_exec('cd '.$path.'; git branch') as $branch){
                if(substr(trim($branch), 0, 1) == '*'){
                    return trim(substr(trim($branch), 1));
                }
            }

            throw new bException(tr('git_branch(): Could not find current branch for ":path"', array(':path' => $path)), 'branchnotfound');
        }

    }catch(Exception $e){
        throw new bException('git_branch(): Failed', $e);
    }
}



/*
 *
 */
function git_has_changes($path = ROOT){
    try{
        if(!$path){
            throw new bException('git_has_changes(): No path specified');
        }

        if(!file_exists($path)){
            throw new bException('git_has_changes(): Specified path ":path" does not exist', array(':path' => $path));
        }

        /*
         * Check if we dont have any changes that should be committed first
         */
        if(trim(shell_exec('cd '.$path.'; git status | grep "nothing to commit" | wc -l'))){
            return false;
        }

        return true;

    }catch(Exception $e){
        throw new bException('git_has_changes(): Failed', $e);
    }
}



/*
 *
 */
function git_status($path = ROOT){
    try{
        if(!$path){
            throw new bException('git_status(): No path specified');
        }

        if(!file_exists($path)){
            throw new bException('git_status(): Specified path ":path" does not exist', array(':path' => $path));
        }

        /*
         * Check if we dont have any changes that should be committed first
         */
        $results = safe_exec('cd '.$path.'; git status | grep "   "');

        foreach($results as &$result){
            $result = trim(str_from($result, ':'));
        }

        return $results;

    }catch(Exception $e){
        throw new bException('git_status(): Failed', $e);
    }
}



/*
 *
 */
function git_fetch($params = null){
    try{
        array_params($params, 'path');
        array_default($params, 'all' , true);
        array_default($params, 'path', ROOT);

        if(!$params['path']){
            throw new bException('git_fetch(): No path specified');
        }

        if(!file_exists($params['path'])){
            throw new bException('git_fetch(): Specified path ":path" does not exist', array(':path' => $path));
        }

        $options = array();

        if($params['all']){
            $options[] = '--all';
        }

        /*
         * Do git fetch
         */
        shell_exec('cd '.$params['path'].'; git fetch '.implode(' ', $options));

    }catch(Exception $e){
        throw new bException('git_fetch(): Failed', $e);
    }
}
?>
