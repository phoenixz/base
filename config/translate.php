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
$_CONFIG['translator']         = array('url'                 => 'https://toolkit.ingiga.com/api/translate.php',
                                       'max_difference_time' => 3,
                                       'mode'                => 'full',
                                       'passphrase'          => 'translateplease',
                                       'api_key'             => 'something',
                                       'allowed_tags'        => '<a><strong><small><b><i><u>');
?>
