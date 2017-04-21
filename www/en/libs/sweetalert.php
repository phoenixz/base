<?php
/*
 * Sweetalerts library
 *
 * This library is a front end functions library for the javascript sweetalert
 * library
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */


sweetalert_init();


/*
 * Load the sweetalert library and its CSS requirements
 */
function sweetalert_init(){
    try{
        ensure_installed(array('name'      => 'sweetalert',
                               'project'   => 'sweetalert',
                               'callback'  => 'sweetalert_install',
                               'checks'    => array(ROOT.'pub/js/sweetalert/sweetalert.js',
                                                    ROOT.'pub/css/sweetalert/sweetalert.css')));

        load_config('sweetalert');
        html_load_js('sweetalert/sweetalert.js');
        html_load_css('sweetalert/sweetalert.css');

    }catch(Exception $e){
        throw new bException('sweetalert_load(): Failed', $e);
    }
}



/*
 * Install the sweetalert library
 */
function sweetalert_install($params){
    try{
        $params['methods'] = array('bower'    => array('command'   => 'npm install sweetalert',
                                                       'locations' => array('sweetalert-master/lib/sweetalert.js' => ROOT.'pub/js/sweetalert/sweetalert.js',
                                                                            'sweetalert-master/lib/modules'       => ROOT.'pub/js/sweetalert/modules',
                                                                            'sweetalert-master/themes'            => ROOT.'pub/css/sweetalert/themes',
                                                                            '@themes/google/google.css'           => ROOT.'pub/css/sweetalert/sweetalert.css')),

                                   'bower'    => array('command'   => 'bower install sweetalert',
                                                       'locations' => array('sweetalert-master/lib/sweetalert.js' => ROOT.'pub/js/sweetalert/sweetalert.js',
                                                                            'sweetalert-master/lib/modules'       => ROOT.'pub/js/sweetalert/modules',
                                                                            'sweetalert-master/themes'            => ROOT.'pub/css/sweetalert/themes',
                                                                            '@themes/google/google.css'           => ROOT.'pub/css/sweetalert/sweetalert.css')),

                                   'download' => array('url'       => 'https://github.com/t4t5/sweetalert/archive/master.zip',
                                                       'command'   => 'unzip master.zip',
                                                       'locations' => array('sweetalert-master/lib/sweetalert.js' => ROOT.'pub/js/sweetalert/sweetalert.js',
                                                                            'sweetalert-master/lib/modules'       => ROOT.'pub/js/sweetalert/modules',
                                                                            'sweetalert-master/themes'            => ROOT.'pub/css/sweetalert/themes',
                                                                            '@themes/google/google.css'           => ROOT.'pub/css/sweetalert/sweetalert.css')));

        return install($params);

    }catch(Exception $e){
        throw new bException('sweetalert_install(): Failed', $e);
    }
}



/*
 * Show a sweet alert directly
 */
function sweetalert($params, $body, $type = ''){
    try{
        array_params($params, 'title');
        array_params($params, 'title', '');
        array_params($params, 'body' , $body);
        array_params($params, 'type' , $type);

        array_params($params, 'type' , $type);

        return 'swal("'.$params['title'].'", "'.$params['body'].'", "'.$params['type'].'")';

    }catch(Exception $e){
        throw new bException('sweetalert(): Failed', $e);
    }
}
?>
