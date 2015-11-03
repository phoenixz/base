<?php

/*
 *
*/
include_once (dirname(__FILE__) . '/../libs/startup.php');

$available_modes = array('strict', 'almost', 'almost-nontranslate', 'nontranslate');

if (!empty($_POST['data'])) {
    try {
        //unpack data
        $data         = json_decode(str_decrypt($_POST['data'], 'translateplease'), true);
        $language     = $data['target_language'];
        $project_name = trim($data['project']);
        $strings      = $data['translations'];
        $auth_key     = $data['auth_key'];
        $timestamp    = $data['timestamp'];
        $options      = $data['options'];

        array_default($options, 'mode', 'strict');

        if(!in_array($options['mode'], $available_modes)){
            header('HTTP/1.0 400 Bad Request', true, 400);
            die(tr('Unknown mode %mode%', array('%mode%' => $options['mode'])));
        }

        if (empty($project_name)) {
            header('HTTP/1.0 400 Bad Request', true, 400);
            die(tr('Missing project name'));
        }

        $project = sql_get('SELECT `id`,
                                   `api_key`,
                                   `last_login`

                            FROM   `projects`

                            WHERE  `name` = :project_name',

                            array(':project_name' => cfm($project_name)));

        if (empty($project['id']) or sha1($project_name.$project['api_key'].$timestamp) != $auth_key or $timestamp < $project['last_login']) {
            header('HTTP/1.0 401 Unauthorized', true, 401);
            die(tr('Cant access to server with the given credentials'));
        }

        sql_query('UPDATE `projects`

                   SET    `last_login` = NOW()

                   WHERE  `id` = :id',

                   array(':id' => $project['id']));


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

                if($translation['status'] == 'translated' and !empty($translation['translation'])){
                    $translations[$file][$string] = $translation['translation'];
                    $stats['translations_done']++;

                }else if ($options['mode'] == 'almost' or $options['mode'] == 'almost-nontranslate'){
                    //no translation found
                    //check for translation on another site
                    $alt_project_trans = sql_get('SELECT `translation`

                                                  FROM   `dictionary`

                                                  WHERE  `code`   = :code
                                                  AND    `status` = "translated"

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
                        if($options['mode'] == 'almost'){
                            header('HTTP/1.0 404 Not Found', true, 404);
                            die(tr('Missing translations'));
                        }

                        $translations[$file][$string] = $string;
                        $stats['translations_missing']++;
                    }

                }else if($options['mode'] == 'nontranslate') {
                    $translations[$file][$string] = $string;
                    $stats['translations_missing']++;

                }else if($options['mode'] == 'strict'){
                    header('HTTP/1.0 404 Not Found', true, 404);
                    die(tr('Missing translations'));
                }

            }
        }

        $ret = array('status'       => 'success',
                     'translations' => $translations,
                     'stats'        => $stats);

    } catch(Exception $e) {
        header('HTTP/1.0 500 Internal Server Error', true, 500);
        $ret = array('status' => 'fatal error');
    }

}else{
    header('HTTP/1.0 400 Bad Request', true, 400);
    $ret = array('status' => 'failed');
}

echo str_encrypt(json_encode($ret), $_CONFIG['translator']['passphrase']);
?>