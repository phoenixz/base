<?php
/*
 * Sweetalerts library
 *
 * This library is a front end functions library for the javascript sweetalert
 * library
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 */



/*
 * Initialize the library
 * Automatically executed by libs_load()
 */
function sweetalert_library_init(){
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
        throw new bException('sweetalert_library_init(): Failed', $e);
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
function sweetalert($params, $html = '', $type = '', $options = array()){
    try{
        array_params ($params, 'title');
        array_default($params, 'title'  , '');
        array_default($params, 'html'   , $html);
        array_default($params, 'type'   , $type);
        array_default($params, 'class'  , null);
        array_default($params, 'options', $options);

        array_default($params, 'confirm_action', null);
        array_default($params, 'cancel_action' , null);

        array_default($params['options'], 'confirmAction'      , null);
        array_default($params['options'], 'cancelAction'       , null);

        array_default($params['options'], 'input'              , null);
        array_default($params['options'], 'allowOutsideClick'  , null);
        array_default($params['options'], 'allowEscapeKey'     , null);
        array_default($params['options'], 'class'              , $params['class']);
        array_default($params['options'], 'buttonsStyling'     , null);
        array_default($params['options'], 'showLoaderOnConfirm', null);
        array_default($params['options'], 'preConfirm'         , null);

        array_default($params['options'], 'confirmButtonColor', null);
        array_default($params['options'], 'confirmButtonText' , null);
        array_default($params['options'], 'confirmButtonClass', null);

        array_default($params['options'], 'showCancelButton'  , null);
        array_default($params['options'], 'cancelButtonColor' , null);
        array_default($params['options'], 'cancelButtonText'  , null);
        array_default($params['options'], 'cancelButtonClass' , null);

        load_libs('json');

        $options['title'] = $params['title'];
        $options['html']  = $params['html'];
        $options['type']  = $params['type'];

        if($params['options']['confirmAction']){
            $then['confirm'] = $params['options']['confirmAction'];
            unset($params['options']['confirmAction']);
        }

        if($params['options']['cancelAction']){
            $then['cancel'] = $params['options']['cancelAction'];
            unset($params['options']['cancelAction']);
        }

        foreach($params['options'] as $key => $value){
            if($value === null) continue;
            $options[$key] = $value;
        }

        $retval = 'swal('.json_encode_custom($options).')';

        if(!empty($then)){
            $retval .= '.then('.isset_get($then['confirm'], 'undefined').', '.isset_get($then['cancel'], 'undefined').')';
        }

        return $retval.';';

    }catch(Exception $e){
        throw new bException('sweetalert(): Failed', $e);
    }
}



/*
 * Show a sweet alert directly
 */
function sweetalert_queue($params){
    try{
        array_params ($params);
        array_default($params, 'modals'             , null);
        array_default($params, 'show_cancel_button' , false);
        array_default($params, 'confirm_button_text', 'Ok &rarr;');
        array_default($params, 'animation'          , false);
        array_default($params, 'progress_steps'     , true);

        load_libs('json');

        if(empty($params['modals'])){
            throw new bException('sweetalert_queue(): No modals specified', 'not-specified');
        }

        if(!is_array($params['modals'])){
            throw new bException('sweetalert_queue(): Specified modals list should be an array', 'invalid');
        }

        /*
         * Translate options
         */
        foreach($params as $key => $value){
            switch($key){
                case 'modals':
                    break;

                case 'confirm_button_text':
                    $options['confirmButtonText'] = $value;
                    break;

                case 'show_cancel_button':
                    $options['showCancelButton']  = $value;
                    break;

                case 'animation':
                    $options['animation']         = $value;
                    break;

                case 'progress_steps':
                    if($value){
                        if($value === true){
                            $value = array_keys($params['modals']);

                        }else{
                            $options['progressSteps'] = $value;
                        }
                    }

                    break;

                default:
                    throw new bException(tr('sweetalert_queue(): Unknown option ":option" specified', array(':option' => $key)), 'unknown');
            }
        }
//show($params);

        $javascript = 'swal.setDefaults('.json_encode_custom($options).');
                       var steps = '.json_encode_custom($params['modals']).';
                       swal.queue(steps);';

        return $javascript;

    }catch(Exception $e){
        throw new bException('sweetalert_queue(): Failed', $e);
    }
}
?>
