<?php
/*
 * Audio configuration, there are classes of audio that can be played
 * and also there are different commands to play different types of audio.
 */
$_CONFIG['audio']                                                               = array('classes' => array('alarm'     => 'alarm.mp3',
                                                                                                           'exception' => 'critical.mp3',
                                                                                                           'notify'    => 'ping.mp3',
                                                                                                           'default'   => 'alarm.mp3'),

                                                                                        'quiet'   => false,
                                                                                        'command' => 'mplayer',
                                                                                        'default' => 'exception');

?>
