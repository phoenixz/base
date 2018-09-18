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
$_CONFIG['translate']                                                           = array('url'          => 'https://toolkit.capmega.com/api/translate',
                                                                                        'mode'         => 'full',
                                                                                        'api_key'      => '',
                                                                                        'allowed_tags' => '<br><a><strong><span><small><b><i><u>',
                                                                                        'default'      => 'es',
                                                                                        'supported'    => array('en'  => 'English',
                                                                                                                'es'  => 'Español',
                                                                                                                'fr'  => 'Français',
                                                                                                                'fy'  => 'Frysk',
                                                                                                                'it'  => 'italiano',
                                                                                                                'de'  => 'Deutsch',
                                                                                                                'da'  => 'Dansk',
                                                                                                                'nah' => 'Nahuatl',
                                                                                                                'nl'  => 'Nederlands',
                                                                                                                'pr'  => 'Português',
                                                                                                                'zh'  => '中国',
                                                                                                                'ja'  => '日本'));

?>
