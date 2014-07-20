<?php
//admin possible rights
$_CONFIG['admin']['pages'] = array('blog'        => array('script' => 'blogs.php',
                                                          'title'  => tr('Blogs'),

                                                          'subs'   => array(array('script' => 'blogs_posts.php',
                                                                                  'title'  => tr('Posts')))),

                                   'stats'       => array('script' => 'stats.php',
                                                          'title'  => tr('Statistics')),

                                   'users'       => array('script' => 'users.php',
                                                          'title'  => tr('Users')),

                                   'rights'      => array('script' => 'rights.php',
                                                          'title'  => tr('Rights')),

                                   'activitylog' => array('script' => 'activitylog.php',
                                                          'title'  => tr('Activity log')));

?>
