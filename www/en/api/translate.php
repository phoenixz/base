<?php
/*
 *
 */
include_once (dirname(__FILE__) . '/../libs/startup.php');
load_libs('crypt,json,validate');
load_config('translate');

if(empty($_POST['data'])){
    header('HTTP/1.0 400 Bad Request', true, 400);
    die(tr('Missing data'));
}


try {
    /*
     * Unpack data
     */
    $data = decrypt($_POST['data'], $_CONFIG['translate']['passphrase']);
    $v    = new validate_form($data, 'target_language,project,translations,api_key,timestamp,options,method');

    array_params ($data['options'], 'mode');
    array_default($data['options'], 'mode', 'strict');

    /*
     * Data validation
     */
    switch($data['method']){
        case 'get':
            if(empty($data['target_language']) or !preg_match('/^[a-zA-Z]{2}$/', $data['target_language'])){
                $v->setError(tr('No or invalid language ":language" specified', array(':language' => $data['target_language'])));
            }
            // FALLTHROUGH
        case 'post':
            break;

        default:
            $v->setError(tr('Missing or invalid method ":method"', array(':method' => $data['method'])));
    }

    if(empty($data['project']) or !is_string($data['project'])){
        $v->setError(tr('Missing or invalid project name'));
    }

    if(!is_array($data['translations'])){
        $v->setError(tr('No valid strings list to translate specified.'));
    }

    if(empty($data['api_key']) or !is_string($data['api_key'])){
        $v->setError(tr('No valid auth key specified.'));
    }

    if(empty($data['options']['mode'])){
        $v->setError(tr('Translation mode is misssing. :array', array(':array' => print_r($data['options'], true))));

    }elseif(!in_array($data['options']['mode'], array('strict', 'full', 'most', 'none'))){
        $v->setError(tr('Unknown mode ":mode"', array(':mode' => $data['options']['mode'])));
    }

    try{
        $v->isValid();

    }catch(Exception $e){
        header('HTTP/1.0 400 Bad Request', true, 400);
        die(implode($e->getMessages(), '\n'));
    }

    $project = sql_get('SELECT `id`,
                               `api_key`,
                               `last_login`

                        FROM   `projects`

                        WHERE  `name` = :name',

                        array(':name' => cfm($data['project'])));

    if(empty($project['id']) or $project['api_key'] != $data['api_key']){
        header('HTTP/1.0 401 Unauthorized', true, 401);
        die(tr('Access denied'));
    }

    sql_query('UPDATE `projects` SET `last_login` = NOW() WHERE `id` = :id', array(':id' => $project['id']));
    $project['last_login'] = sql_get('SELECT `last_login` FROM `projects` WHERE `id` = :id', 'last_login', array(':id' => $project['id']));

    // remove untranslated stuff for this project
    // sql_query('DELETE FROM `dictionary`

    //            WHERE `projects_id` = :project_id
    //            AND     `language` = :language
    //            AND TRANSLATION IS NULL',

    //            array(':project_id' => cfi($project['id']), ':language' => $data['target_language']));

    $translations = array();
    $stats        = array('translations_missing' => 0,
                          'translations_done'    => 0);

    /*
     * Get and store translations from the database
     */
    if(!is_array($data['translations'])){
        throw new bException(tr('Specified translation list is invalid'), 'invalid');
    }

    foreach($data['translations'] as $file => $list){
        if(!is_array($list)){
            throw new bException(tr('Specified translation list for file ":file" is invalid', array(':file' => $file)), 'invalid');
        }

        foreach($list as $string => $void){
            $code        = sha1($data['target_language'].'|'.$string);
            $translation = sql_get('SELECT `id`,
                                           `translation`,
                                           `status`

                                    FROM   `dictionary`

                                    WHERE  `code`        = :code
                                    AND    `projects_id` = :project_id
                                    AND    `file`        = :file',

                                    array(':code'        => $code,
                                          ':project_id'  => cfi($project['id']),
                                          ':file'        => $file));

            if(empty($translation)){
                sql_query('INSERT INTO `dictionary` (`projects_id`, `language`, `string`, `file`, `code`)
                           VALUES                   (:projects_id , :language , :string , :file , :code )',

                           array(':projects_id' => cfi($project['id']),
                                 ':language'    => 'en',
                                 ':string'      => $string,
                                 ':file'        => $file,
                                 ':code'        => $code));

                $translation = array('id'          => sql_insert_id(),
                                     'translation' => '',
                                     'status'      => '');
            }

            if($data['method'] == 'post'){
                unset($translation);
                continue;
            }

            if(!empty($translation['translation']) and $data['options']['mode'] != 'none'){
                $translations[$file][$string] = $translation['translation'];
                $stats['translations_done']++;

            }elseif($data['options']['mode'] == 'full' or $data['options']['mode'] == 'most'){
                //no translation found
                //check for translation on another site
                $alt_project_trans = sql_get('SELECT `translation`

                                              FROM   `dictionary`

                                              WHERE  `code`   = :code

                                              LIMIT 0, 1',

                                              array(':code' => $code));

                if(!empty($alt_project_trans['translation'])) {
                    sql_query('UPDATE `dictionary`

                               SET    `translation` = :translation,
                                      `status`      = "translated"

                               WHERE  `id`          = :id',

                               array(':translation' => addslashes($alt_project_trans['translation']),
                                     ':id'          => $translation['id']));

                    $translations[$file][$string] = $alt_project_trans['translation'];
                    $stats['translations_done']++;

                }else{
                    /*
                     * No translation available
                     */
                    if($data['options']['mode'] == 'full'){
                        $error = 406;
                    }

                    $translations[$file][$string] = $string;
                    $stats['translations_missing']++;
                }

            }elseif($data['options']['mode'] == 'strict'){
                $error = 406;
            }
        }
    }

    if($data['method'] == 'post'){
        /*
         * We also return the translations that were made
         * since last login
         */
        $new_translations = sql_list('SELECT `id`,
                                             `file`,
                                             `string`,
                                             `translation`

                                      FROM   `dictionary`

                                      WHERE  `projects_id` = :project_id
                                      AND    `translation` IS NOT NULL
                                      AND    `modifiedon`  > :last_login',

                                      array(':project_id'  => cfi($project['id']),
                                            ':last_login'  => $project['last_login']));

        foreach($new_translations as $new_translation){
            $translations[$new_translation['file']][$new_translation['string']] = $new_translation['translation'];
        }
    }

    switch(isset_get($error)){
        case '':
            // No error
            break;

        case 406:
            header('HTTP/1.0 406 Not Acceptable', true, 406);
            die(tr('Missing translations'));

        default:
            //  Unknow error
            //  Fallthrough to 500

        case 500:
            header('HTTP/1.0 500 Internal Server Error', true, 500);
            die('Server error');
            break;
    }

    $ret = array('status'       => 'success',
                 'translations' => $translations,
                 'stats'        => $stats);

} catch(Exception $e) {
    log_database($e->getMessage(), 'server_error');
    header('HTTP/1.0 500 Internal Server Error', true, 500);
    die('Server error');
}


echo encrypt($ret, $_CONFIG['translate']['passphrase']);
?>
