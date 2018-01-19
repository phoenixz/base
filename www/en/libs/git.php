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
 *
 */
function git_am($file, $patch_file){
    try{
        $path = dirname($file);
        git_check_path($path);

        $result = safe_exec('cd '.$path.'; git apply '.basename($file),' <');

        return $result;

    }catch(Exception $e){
        throw new bException('git_am(): Failed', $e);
    }
}



/*
 * Apply a git patch file
 */
function git_apply($file){
    try{
        $path = dirname($file);
        git_check_path($path);

        $result = safe_exec('cd '.$path.'; git apply '.basename($file));

        return $result;

    }catch(Exception $e){
        $data = $e->getData();
        $data = array_pop($data);

        if(strstr($data, 'patch does not apply')){
            throw new bException(tr('git_apply(): Failed to apply patch ":file"', array(':file' => $file)), 'failed');
        }

        throw new bException('git_apply(): Failed', $e);
    }
}



/*
 * Get or set the current GIT branch
 */
function git_branch($branch = null, $path = ROOT, $create = false){
    try{
        git_check_path($path);

        if($branch){
            /*
             * Set the branch
             */
            safe_exec('cd '.$path.'; git branch '.($create ? ' -B ' : '').$branch);

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
 * Ensure the path is specified and exists
 */
function git_check_path($path){
    try{
        if(!$path){
            throw new bException('git_check_path(): No path specified');
        }

        if(!file_exists($path)){
            throw new bException('git_check_path(): Specified path ":path" does not exist', array(':path' => $path));
        }

    }catch(Exception $e){
        throw new bException('git_check_path(): Failed', $e);
    }
}



/*
 * Checkout the specified file, resetting its changes
 */
function git_checkout($file){
    try{
        if(is_dir($file)){
            $path = $file;

        }else{
            $path = dirname($file);
        }

        git_check_path($path);

        $retval = safe_exec('cd '.$path.'; git checkout -- '.basename($file));

    }catch(Exception $e){
        throw new bException('git_checkout(): Failed', $e);
    }
}



/*
 * Clean the specified git repository
 */
function git_clean($path, $directories = false, $force = false){
    try{
        $retval = safe_exec('cd '.$path.'; git clean'.($directories ? ' -d' : '').($force ? ' -f' : ''));

    }catch(Exception $e){
        throw new bException('git_clean(): Failed', $e);
    }
}



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
 * Make a patch for the specified file
 */
function git_diff($file, $color = false){
    try{
        $path = dirname($file);
        git_check_path($path);

        $result = shell_exec('cd '.$path.'; git diff '.($color ? '' : '--no-color ').' -- '.basename($file));

        return $result;

    }catch(Exception $e){
        throw new bException('git_diff(): Failed', $e);
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

        git_check_path($params['path']);

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



/*
 * Make a patch for the specified file
 */
function git_format_patch($file){
    try{
        $path = dirname($file);
        git_check_path($path);
under_construction();
        $result = safe_exec('cd '.$path.'; git format-patch ');

        return $result;

    }catch(Exception $e){
        throw new bException('git_format_patch(): Failed', $e);
    }
}



/*
 *
 */
function git_reset($file){
    try{
        if(is_dir($file)){
            $path = $file;

        }else{
            $path = dirname($file);
        }

        git_check_path($path);

        $retval = safe_exec('cd '.$path.'; git reset '.$file);

    }catch(Exception $e){
        throw new bException('git_reset(): Failed', $e);
    }
}



/*
 * Return an associative array with as key => value $file => $status
 */
function git_status($path = ROOT, $filters = false){
    try{
        git_check_path($path);

        /*
         * Check if we dont have any changes that should be committed first
         */
        $retval  = array();
        $results = shell_exec('cd '.$path.'; git status --porcelain');
        $results = explode("\n", $results);

        foreach($results as $line){
            if(!$line) continue;

            $status = substr($line, 0, 2);

            if($filters){
                /*
                 * Only allow files that have status in the filter
                 */
                $skip = true;

                foreach($filters as $filter){
                    if($status == $filter){
                        $skip = false;
                    }
                }

                if($skip) continue;
            }

            switch($status){
                case 'D ':
                    $status = 'deleted';
                    break;

                case ' D':
                    $status = 'deleted';
                    break;

                case 'AM':
                    $status = 'new file';
                    break;

                case ' M':
                    $status = 'modified';
                    break;

                case '??':
                    $status = 'not tracked';
                    break;

                default:
                    throw new bException(tr('git_status(): Unknown git status ":status" encountered', array(':status' => $status)), 'unknown');
            }

            $retval[substr($line, 3)] = $status;
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('git_status(): Failed', $e);
    }
}



/*
 *
 */
function git_stash($path = ROOT){
    try{
        git_check_path($path);

        $result = safe_exec('cd '.$path.'; git stash');

        return $result;

    }catch(Exception $e){
        throw new bException('git_stash(): Failed', $e);
    }
}



/*
 *
 */
function git_stash_pop($path = ROOT){
    try{
        git_check_path($path);

        $result = safe_exec('cd '.$path.'; git stash pop');

        return $result;

    }catch(Exception $e){
        throw new bException('git_stash_pop(): Failed', $e);
    }
}
?>
