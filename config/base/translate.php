<?php
/*
 * Translation system configuration
 *
 * mode defines what to do when no translations are found
 * possible values : strict               (all strings must be translated, fails if thats not possible)
                     full                 (search for alternative translations on other projects
                                           if they are not available on the current one (fails if not found))
                     most                 (same as above but doesnt fail if not translations are found)
                     none                 (leave original strings if not translations are found)
 */
$_CONFIG['translate'] = array('url'          => 'https://toolkit.ingiga.com/api/translate.php',
                              'mode'         => 'full',
                              'passphrase'   => 'translate',
                              'api_key'      => '',
                              'allowed_tags' => '<br><a><strong><span><small><b><i><u>');

?>
