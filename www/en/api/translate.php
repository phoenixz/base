<?php

/*
 *
*/
include_once (dirname(__FILE__) . '/../libs/startup.php');
load_libs('crypt');

if(empty($_POST['data'])){
    header('HTTP/1.0 400 Bad Request', true, 400);
    die(tr('Missing data'));
}


try {
    //unpack data
    $data = json_decode(decrypt($_POST['data'], $_CONFIG['translator']['passphrase']), true);
    $data = array_ensure($data, 'target_language,project,translations,auth_key,timestamp,options');

    $language     = $data['target_language'];
    $project_name = trim($data['project']);
    $strings      = $data['translations'];
    $auth_key     = $data['auth_key'];
    $timestamp    = $data['timestamp'];
    $options      = $data['options'];

    array_params($options, 'mode');
    array_default($options, 'mode', 'strict');


    /*
     * Data validation
     */
    if(empty($project_name) or !is_string($project_name)){
        $error = tr('Missing or invalid project name');
    }

    if(empty($language) or !preg_match('/^[a-zA-Z]{2}$/', $language)){
        $error = tr('No valid language specified');
    }

    if(!is_array($strings)){
        $error = tr('No valid strings list to translate specified.');
    }

    if(empty($auth_key) or !is_string($auth_key)){
        $error = tr('No valid auth key specified.');
    }

    $client_time = new DateTime($timestamp);
    $server_time = new DateTime(date('c'));

    if(empty($timestamp) or ($client_time > $server_time)) {
        $error = tr('Time validation error. Server time : %time%. Client time : %time2%.',
        array('%time%' => $server_time->format('U'), '%time2%' => $client_time->format('U')) );
    }

    if(empty($options['mode'])){
        $error = tr('Translation mode is misssing. %array% ', array('%array%' => print_r($options, TRUE)));

    } else if(!in_array($options['mode'], array('strict', 'full', 'most', 'none'))){
        $error = tr('Unknown mode "%mode%"', array('%mode%' => $options['mode']));
    }

    if(isset($error)){
        header('HTTP/1.0 400 Bad Request', true, 400);
        die($error);
    }


    $project = sql_get('SELECT `id`,
                               `api_key`,
                               `last_login`

                        FROM   `projects`

                        WHERE  `name` = :project_name',

                        array(':project_name' => cfm($project_name)));

    if (empty($project['id']) or sha1($project_name.$project['api_key'].$timestamp) != $auth_key or (!empty($project['last_login']) && $client_time < new Datetime($project['last_login']) )) {
        header('HTTP/1.0 401 Unauthorized', true, 401);
        die(tr('Cant access to server with the given credentials'));
    }

    $last_login = $project['last_login'];

    sql_query('UPDATE `projects`

               SET    `last_login` = :time

               WHERE  `id` = :id',

               array(':id'   => $project['id'],
                     ':time' => $client_time->format('c')));


    // remove untranslated stuff for this project
    // sql_query('DELETE FROM `dictionary`

    //            WHERE `projects_id` = :project_id
    //            AND     `language` = :language
    //            AND TRANSLATION IS NULL',

    //            array(':project_id' => cfi($project['id']), ':language' => $language));

    $translations = array();
    $stats        = array('translations_missing' => 0,
                          'translations_done'    => 0);

    //get and store translations from the database
    foreach($strings as $file  => $strings_list) {
        foreach($strings_list as $string => $void) {
            $code = sha1($language.'|'.$string);

            //check if there is a translation in the current project
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
                                 ':language'    => cfm($language),
                                 ':string'      => $string,
                                 ':file'        => $file,
                                 ':code'        => $code));

                $translation = array('id'          => sql_insert_id(),
                                     'translation' => '',
                                     'status'      => '');

            }

            if(!empty($translation['translation']) and $options['mode'] != 'none'){
                $translations[$file][$string] = $translation['translation'];
                $stats['translations_done']++;

            }else if ($options['mode'] == 'full' or $options['mode'] == 'most'){
                //no translation found
                //check for translation on another site
                $alt_project_trans = sql_get('SELECT `translation`

                                              FROM   `dictionary`

                                              WHERE  `code`   = :code

                                              LIMIT 0,1',

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
                    if($options['mode'] == 'full'){
                        $error = 406;
                    }

                    $translations[$file][$string] = $string;
                    $stats['translations_missing']++;
                }

            }else if($options['mode'] == 'strict'){
                $error = 406;
            }

        }
    }

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
                                        ':last_login'  => $last_login));

    foreach($new_translations as $new_translation){
        $translations[$new_translation['file']][$new_translation['string']] = $new_translation['translation'];
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


echo encrypt(json_encode($ret), $_CONFIG['translator']['passphrase']);
?>
