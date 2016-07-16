<?php
    array_default($params, 'locations', null);
    array_default($params, 'url'      , null);

    log_database(tr('Library ":name" not found, auto installing now', array(':name' => $params['name'])), 'auto/install');
    load_libs('file');

    /*
     * Install the required library and continue
     */
    if(!$params['url']){
        throw new bException(tr('ensure_installed(): No install directory specified for library ":name"', array(':name' => $params['name'])), 'not-specified');
    }

    if(preg_match('/^https?:\/\/github\.com\/.+?\/.+?\.git$/i', $params['url'])){
        /*
         * This is a github install file. Clone it, and install from there
         */
        $project = str_rfrom($params['url'], '/');
        $project = str_until($project, '.git');

        log_database(tr('Cloning GIT project ":project"', array(':project' => $project)), 'git/clone');

        load_libs('git');
        file_delete(TMP.$project);
        git_clone($params['url'], TMP);

    }elseif(preg_match('/^https?:\/\/.+?\.zip/i', $params['url'])){
        file_move_to_target($params['url'], TMP.$project);

    }else{
        /*
         * Errr, unknown install link, don't know how to process this..
         */
        throw new bException(tr('ensure_installed(): Unknown install URL type ":url" specified, it is not known how to process this URL', array(':url' => $params['url'])), 'not-specified');
    }

    /*
     * Okay, we should have raw install path available in TMP now
     */
    foreach($params['locations'] as $source => $target){
        rename(TMP.$project.'/'.$source, $target);
    }

    /*
     * Install done! Remove temporary crap
     */
    file_tree_clean(TMP.$project);
?>
