<?php
/*
 * PHP Composer library
 *
 * This library contains all required functions to work with PHP composer
 *
 * @url https://getcomposer.org/
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */


/*
 * Do a version check so we're sure this stuff is supported
 */
if(version_compare(PHP_VERSION, '5.3.2') < 0){
    throw new bException('composer library: PHP composer requires PHP 5.3.2+', 'notsupported');
}



composer_init();



/*
 * Load the composer library and its CSS requirements
 */
function composer_init(){
    try{
        ensure_installed(array('name'      => 'composer',
                               'project'   => 'composer',
                               'callback'  => 'composer_install',
                               'checks'    => array(ROOT.'www/en/libs/external/composer.phar')));

        if(!file_exists(ROOT.'/composer.json')){
            composer_init_file();
        }

    }catch(Exception $e){
        throw new bException('composer_load(): Failed', $e);
    }
}



/*
 *
 */
function composer_init_file(){
    try{
        file_put_contents(ROOT.'composer.json', "{\n}");

    }catch(Exception $e){
        throw new bException('composer_init_file(): Failed', $e);
    }
}



/*
 * Install the composer library
 */
function composer_install($params){
    try{
        $params['methods'] = array('download' => array('commands'  => function($hash){
                                                                        load_libs('file');
                                                                        file_ensure_path(TMP.'composer');
                                                                        safe_exec('wget -O '.TMP.'composer-setup.php https://getcomposer.org/installer');

                                                                        $file_hash     = hash_file('SHA384', TMP.'composer-setup.php');
                                                                        $required_hash = safe_exec('wget -q -O - https://composer.github.io/installer.sig');
                                                                        $required_hash = $required_hash[0];

                                                                        if($file_hash != $required_hash){
                                                                            throw new bException(tr('composer_install(): File hash check failed for composer-setup.php'), 'hash-fail');
                                                                        }

                                                                        chmod(ROOT.'www/en/libs/external', 0770);
                                                                        safe_exec('php '.TMP.'composer-setup.php --install-dir '.ROOT.'www/en/libs/external/'.(VERBOSE ? '' : ' --quiet'));
                                                                        chmod(ROOT.'www/en/libs/external', 0550);
                                                                        file_delete(TMP.'composer-setup.php');
                                                                      }));

        return install($params);

    }catch(Exception $e){
        throw new bException('composer_install(): Failed', $e);
    }
}
?>
