<?php
/*
 * GIT library
 *
 * This library contains functions to manage GIT
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>
 */


/*
 * Get or set the current GIT branch
 */
function git_branch($branch = null){
    try{
        if($branch){
            /*
             * Set the branch
             */
            safe_exec('cd '.ROOT.'; git branch '.$branch);

        }else{
            /*
             * Get and return the branch
             */
            foreach(safe_exec('cd '.ROOT.'; git branch') as $branch){
                if(substr(trim($branch), 0, 1) == '*'){
                    return trim(substr(trim($branch), 1));
                }
            }

            throw new bException(tr('git_branch(): Could not find current branch for "'.ROOT.'"'), 'branchnotfound');
        }

    }catch(Exception $e){
        throw new bException('git_branch(): Failed', $e);
    }
}
?>
