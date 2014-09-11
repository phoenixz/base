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
            $branch = safe_exec('cd '.ROOT.'; git branch');
showdie($branch);
        }

    }catch(Exception $e){
        throw new bException('git_branch(): Failed', $e);
    }
}
?>
