<?php
try{
    global $_CONFIG;

    if(PLATFORM == 'shell'){
        /*
         * This is a shell, there is no client
         */
        throw new bException(tr('detect_location(): This function cannot be run from a cli shell'), 'invalid');
    }

    if(!$_CONFIG['language']['detect']){
        $_SESSION['language'] = $_CONFIG['language']['default'];
        return $_SESSION['language'];
    }

    try{
        if(empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])){
            if(empty($_CONFIG['location'])){
                /*
                 * Location information is not available, detect location information
                 * first
                 */
                detect_location();
            }

            if(empty($_CONFIG['location'])){
                /*
                 * Location could not be detected, so language cannot be detected either!
                 */
                notify('language-detect-failed', tr('Failed to detect langugage because the clients location could not be detected. This might be a configuration issue prohibiting the detection of the client location', 'developers'));
                $_SESSION['language'] = $_CONFIG['language']['default'];
                return $_SESSION['language'];
            }

           $language = sql_get(' SELECT `languages` FROM `geo_countries` WHERE `id` = :id', true, array(':id' => $_SESSION['location']['country']['id']));

        }else{
            $language = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        }

        $language = str_until($language, ',');
        $language = trim(str_until($language, '-'));

        /*
         * Is the requested language available?
         */
        if(empty($_CONFIG['language']['supported'][$language])){
            $language = $_CONFIG['language']['default'];

            if(empty($_CONFIG['language']['supported'][$language])){
                throw new bException(tr('Invalid language ":language" specified as default language, see $_CONFIG[language][default]', array(':language' => $language)), 'invalid');
            }
        }

        $_SESSION['language'] = $language;

    }catch(Exception $e){
        notify($e);
    }

    return $_SESSION['language'];

}catch(Exception $e){
    throw new bException('detect_location(): Failed', $e);
}
?>
