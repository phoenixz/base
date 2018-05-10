<?php
/*
 * Basic BASE configuration file. DO NOT MODIFY THIS FILE! This file contains default values
 * that may be overwritten when you perform a system update!
 *
 * ALL CONFIGURATION ENTRIES ARE ORDERED ALPHABETICALLY, ONLY "debug" IS ON TOP FOR CONVENIENCE

 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>, Johan Geuze
 */
$_CONFIG['blogs']               = array('enabled'         => false,

// :DEPRECIATED: This information is now stored in the database with the blogs so that it can be defined on a per-blog basis
                                        //'images'          => array('resize'         => array('thumbs'        => array('x' => false,
                                        //                                                                              'y' => false)),
                                        //
                                        //                                                     'images'        => array('x' => false,
                                        //                                                                              'y' => false)),
                                        'url'             => '/%category1%/%date%/%seoname%.html',

                                        'images'          => array('thumb'  => array('method'           => 'thumb',
                                                                                     'strip'            => true,
                                                                                     'blur'             => 0.01,
                                                                                     'interlace'        => 'auto-plane',
                                                                                     'sampling_factor'  => '4:2:0',
                                                                                     'quality'          => 70,
                                                                                     'keep_aspectratio' => true,
                                                                                     'format'           => 'jpg',
                                                                                     'defines'          => array('jpeg:dct-method=float')),

                                                                   'small'  => array('method'           => 'thumb',
                                                                                     'strip'            => true,
                                                                                     'blur'             => 0.01,
                                                                                     'interlace'        => 'auto-plane',
                                                                                     'sampling_factor'  => '4:2:0',
                                                                                     'quality'          => 70,
                                                                                     'keep_aspectratio' => true,
                                                                                     'format'           => 'jpg',
                                                                                     'defines'          => array('jpeg:dct-method=float')),

                                                                   'medium' => array('method'           => 'thumb',
                                                                                     'strip'            => true,
                                                                                     'blur'             => 0.01,
                                                                                     'interlace'        => 'auto-plane',
                                                                                     'sampling_factor'  => '4:2:0',
                                                                                     'quality'          => 70,
                                                                                     'keep_aspectratio' => true,
                                                                                     'format'           => 'jpg',
                                                                                     'defines'          => array('jpeg:dct-method=float')),

                                                                   'large'  => array('method'           => 'thumb',
                                                                                     'strip'            => true,
                                                                                     'blur'             => 0.01,
                                                                                     'interlace'        => 'auto-plane',
                                                                                     'sampling_factor'  => '4:2:0',
                                                                                     'quality'          => 70,
                                                                                     'keep_aspectratio' => true,
                                                                                     'format'           => 'jpg',
                                                                                     'defines'          => array('jpeg:dct-method=float')),

                                                                   'wide'   => array('method'           => 'thumb',
                                                                                     'strip'            => true,
                                                                                     'blur'             => 0.01,
                                                                                     'interlace'        => 'auto-plane',
                                                                                     'sampling_factor'  => '4:2:0',
                                                                                     'quality'          => 70,
                                                                                     'keep_aspectratio' => true,
                                                                                     'format'           => 'jpg',
                                                                                     'defines'          => array('jpeg:dct-method=float'))));
?>
