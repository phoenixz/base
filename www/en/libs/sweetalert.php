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

//        load_config('sweetalert');
        html_load_js('sweetalert/sweetalert');
        html_load_css('sweetalert/sweetalert');

    }catch(Exception $e){
        throw new bException('sweetalert_load(): Failed', $e);
    }
}



/*
 * Install the sweetalert library
 */
function sweetalert_install($params){
    try{
        $params['methods'] = array('bower'    => array('commands'  => 'npm install sweetalert2',
                                                       'locations' => array('sweetalert-master/lib/sweetalert.js' => ROOT.'pub/js/sweetalert/sweetalert.js',
                                                                            'sweetalert-master/lib/modules'       => ROOT.'pub/js/sweetalert/modules',
                                                                            'sweetalert-master/themes'            => ROOT.'pub/css/sweetalert/themes',
                                                                            '@themes/google/google.css'           => ROOT.'pub/css/sweetalert/sweetalert.css')),

                                   'bower'    => array('commands'  => 'bower install sweetalert2',
                                                       'locations' => array('sweetalert-master/lib/sweetalert.js' => ROOT.'pub/js/sweetalert/sweetalert.js',
                                                                            'sweetalert-master/lib/modules'       => ROOT.'pub/js/sweetalert/modules',
                                                                            'sweetalert-master/themes'            => ROOT.'pub/css/sweetalert/themes',
                                                                            '@themes/google/google.css'           => ROOT.'pub/css/sweetalert/sweetalert.css')),

                                   'download' => array('urls'      => array('https://cdn.jsdelivr.net/sweetalert2/6.6.0/sweetalert2.css',
                                                                            'https://cdn.jsdelivr.net/sweetalert2/6.6.0/sweetalert2.js'),
                                                       'locations' => array('sweetalert2.js'  => ROOT.'pub/js/sweetalert/sweetalert.js',
                                                                            'sweetalert2.css' => ROOT.'pub/css/sweetalert/sweetalert.css')));

        return install($params);

    }catch(Exception $e){
        throw new bException('sweetalert_install(): Failed', $e);
    }
}



/*
 * Return the required javascript code to show a sweet alert
 */
function sweetalert($params, $text = '', $type = '', $options = array()){
    try{
        array_params ($params, 'title');
        array_default($params, 'title'  , '');
        array_default($params, 'text'   , $text);
        array_default($params, 'type'   , $type);
        array_default($params, 'class'  , null);
        array_default($params, 'options', $options);

        array_default($params['options'], 'allow_outside_click', null);
        array_default($params['options'], 'allow_escape_key'   , null);
        array_default($params['options'], 'class'              , $params['class']);

        load_libs('json');

        $options['title'] = $params['title'];
        $options['text']  = $params['text'];
        $options['type']  = $params['type'];

        foreach($params['options'] as $key => $value){
            if($value === null) continue;
            $options[$key] = $value;
        }

        return 'swal('.json_encode_custom($options).')';

    }catch(Exception $e){
        throw new bException('sweetalert(): Failed', $e);
    }
}



/*
 * Show a sweet alert directly
 */
function sweetalert_queue($params, $text, $type = ''){
    try{
        return html_script(sweetalert($params, $text, $type));

    }catch(Exception $e){
        throw new bException('sweetalert_queue(): Failed', $e);
    }
}
?>
