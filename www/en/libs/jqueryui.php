<?php
/*
 * jQueryUI library
 *
 * This library contains functions to easily apply jQueryUI functionalities
 *
 * Written and Copyright by Sven Oostenbrink
 */



/*
 * Load the required jquery-ui JS libraries
 * Set the jQuery UI theme
 */
load_config('jqueryui');

if(empty($_CONFIG['jquery-ui']['theme'])){
    throw new bException(tr('jqueryui(): No jquery-ui theme specified, please check $_CONFIG[jquery-ui][theme]'), 'not-exist');
}

html_load_js('jquery-ui/jquery-ui');
html_load_css('jquery/jquery-ui,base/jquery-ui/themes/'.$_CONFIG['jquery-ui']['theme'].'/jquery-ui');



/*
 * Creates HTML for a jquery-ui accordeon function
 */
function jqueryui_accordeon($selector, $options = 'collapsible: true,heightStyle: "content"'){
    try{
        if($options){
            $options = str_ends(str_starts(str_force($options), '{'), '}');

        }else{
            $options = '';
        }

        return html_script('$(function() {
                                $("'.$selector.'").accordion('.$options.');
                            });');

    }catch(Exception $e){
        throw new bException('jqueryui_accordeon(): Failed', $e);
    }
}



/*
 * Creates HTML for a jquery-ui date object
 */
function jqueryui_date($selector, $params = null){
    global $_CONFIG;

    try{
        html_load_css('jquery.ui/jquery.ui.datepicker');

        array_params($params);
        array_default($params, 'placeholder'     , tr('Select a date'));
        array_default($params, 'number_of_months', 1);
        array_default($params, 'change_month'    , true);
        array_default($params, 'default_date'    , '+1w');
        array_default($params, 'auto_submit'     , true);
        array_default($params, 'extra'           , '');
        array_default($params, 'date_format'     , $_CONFIG['jqueryui']['date_format']);
        array_default($params, 'jq_date_format'  , $_CONFIG['jqueryui']['jq_date_format']);

        if($params['auto_submit']){
            array_default($params, 'on_select', '   function (date) {
                                                        $(this).closest("form").submit();
                                                    }');
        }

        if(isset_get($params['value'])){
            $params['value'] = date_convert($params['value'], $params['date_format']);
        }

        $html = '<input type="text" class="'.$params['class'].' date" id="'.$selector.'" name="'.$selector.'" placeholder="'.$params['placeholder'].'" value="'.isset_get($params['value']).'"'.($params['extra'] ? ' '.$params['extra'] : '').'>';

        return $html.html_script('$(function() {
            $( "#'.$selector.'" ).datepicker({
                defaultDate: "'.$params['default_date'].'",
                changeMonth: '.($params['change_month'] ? 'true' : 'false').',
                numberOfMonths: '.$params['number_of_months'].',
                '.(isset_get($params['jq_date_format']) ? 'dateFormat: "'.$params['jq_date_format'].'",' : '').'
                '.(isset_get($params['from'])           ? 'minDate:    "'.$params['from'].'",'           : '').'
                '.(isset_get($params['until'])          ? 'maxDate:    "'.$params['until'].'",'          : '').'
                '.(isset_get($params['on_close'])       ? 'onClose:     '.$params['on_close'].','        : '').'
                '.(isset_get($params['on_select'])      ? 'onSelect:    '.$params['on_select'].','       : '').'
            });
        });');

    }catch(Exception $e){
        throw new bException('jqueryui_date(): Failed', $e);
    }
}



/*
 * Creates HTML for a jquery-ui time object
 */
function jqueryui_time($selector, $params = null){
    global $_CONFIG;

    try{
        array_params($params);
        array_default($params, 'placeholder'        , '');
        array_default($params, 'default_time'       , '');
        array_default($params, 'class'              , '');
        array_default($params, 'auto_submit'        , true);
        array_default($params, 'scroll_default'     , 'now');
        array_default($params, 'show_duration'      , false);
        array_default($params, 'disable_time_ranges', false);
        array_default($params, 'time_format'        , $_CONFIG['formats']['human_time']);
        array_default($params, 'step'               , 60);
        array_default($params, 'extra'              , '');
        array_default($params, 'force_round_time'   , false);
        array_default($params, 'use_select'         , false);

        //if($params['auto_submit']){
        //    array_default($params, 'on_select', '   function (time) {
        //                                                $(this).closest("form").submit();
        //                                            }');
        //}

        $html    = '<input type="text" class="'.$params['class'].' time" id="'.$selector.'" name="'.$selector.'" placeholder="'.$params['placeholder'].'"'.($params['default_time'] ? ' value="'.$params['default_time'].'"' : '').($params['extra'] ? ' '.$params['extra'] : '').'>';

        $script  = '$(function() {
                        $( "#'.$selector.'" ).timepicker({
                            step: "'.$params['step'].'",
                            timeFormat: "'.$params['time_format'].'",
                            showDuration: '.($params['show_duration'] ? 'true' : 'false').',
                            forceRoundTime: '.($params['force_round_time'] ? 'true' : 'false').',
                            '.(isset_get($params['scroll_default']) ? 'scrollDefault: "'.$params['scroll_default'].'",' : '').'
                            '.(isset_get($params['min_time'])       ? 'minTime:       "'.$params['min_time'].'",'       : '').'
                            '.(isset_get($params['max_time'])       ? 'maxTime:       "'.$params['max_time'].'",'       : '').'
                            '.(isset_get($params['on_close'])       ? 'onClose:       "'.$params['on_close'].'",'       : '').'
                            useSelect: '.($params['use_select'] ? 'true' : 'false');

        if($params['disable_time_ranges']){
            if(!is_array($params['disable_time_ranges'])){
                throw new bException('jqueryui_time(): $params[disable_time_ranges] should be either false or an array containing sub arrays', 'invalid');
            }

            $script  = '"disableTimeRanges": [';

            foreach($params['disable_time_ranges'] as $range){
                if(!is_array($range)){
                    throw new bException(tr('jqueryui_time(): All $params[disable_time_ranges] entries should be arrays, ":range" is not', array(':range' => $range)), 'invalid');
                }

                $script  = '["'.isset_get($range[0]).'", "'.isset_get($range[1]).'"]';
            }

            $script  = str_force($entries, ",\n").']';
        }

        $script .= '    });
                    });';

        html_load_js('jquery-timepicker/jquery.timepicker');
        html_load_css('jquery.timepicker');

        return $html.html_script($script);

    }catch(Exception $e){
        throw new bException('jqueryui_time(): Failed', $e);
    }
}



/*
 * Creates HTML for a jquery-ui datepair object
 * See http://jonthornton.github.io/Datepair.js/
 */
function jqueryui_datepair($selector, $params = null){
// :IMPLEMENT: Still not finished
throw new bException('jqueryui_datepair(): This function is not yet implemented', 'not_implemented');
    //global $_CONFIG;
    //
    //try{
    //    array_params($params);
    //    array_default($params, 'placeholder'        , '');
    //    array_default($params, 'default_datepair'       , '');
    //    array_default($params, 'auto_submit'        , true);
    //    array_default($params, 'scroll_default'     , 'now');
    //    array_default($params, 'show_duration'      , false);
    //    array_default($params, 'disable_datepair_ranges', false);
    //    array_default($params, 'datepair_format'        , $_CONFIG['formats']['human_datepair']);
    //    array_default($params, 'step'               , 60);
    //    array_default($params, 'force_round_datepair'   , true);
    //    array_default($params, 'use_select'         , false);
    //
    //
    //
    //    if($params['auto_submit']){
    //        array_default($params, 'on_select', '   function (datepair) {
    //                                                    $(this).closest("form").submit();
    //                                                }');
    //    }
    //
    //    $html = '<input type="text" id="'.$selector.'" name="'.$selector.'" placeholder="'.$params['placeholder'].'">';
    //
    //    $script  = '$(function() {
    //                    $( "#'.$selector.'" ).datepairpicker({
    //                        step: "'.$params['step'].'",
    //                        datepairFormat: "'.$params['datepair_format'].'",
    //                        showDuration: '.$params['show_duration'].',
    //                        forceRounddatepair: '.$params['force_round_datepair'].',
    //                        useSelect: '.$params['use_select'].',
    //                        '.(isset_get($params['scroll_default']) ? 'scrollDefault: "'.$params['scroll_default'].'",' : '').'
    //                        '.(isset_get($params['min_datepair'])       ? 'mindatepair:       "'.$params['min_datepair'].'",'       : '').'
    //                        '.(isset_get($params['max_datepair'])       ? 'maxdatepair:       "'.$params['max_datepair'].'",'       : '').'
    //                        '.(isset_get($params['on_close'])       ? 'onClose:       "'.$params['on_close'].'",'       : '').'
    //                        '.(isset_get($params['on_select'])      ? 'onSelect:      "'.$params['on_select'].'",'      : '').'
    //                        numberOfMonths: '.$params['numberofmonths'];
    //
    //    if($params['disable_datepair_ranges']){
    //        if(!is_array($params['disable_datepair_ranges'])){
    //            throw new bException('jqueryui_datepair(): $params[disable_datepair_ranges] should be either false or an array containing sub arrays', 'invalid');
    //        }
    //
    //        $script  = '"disabledatepairRanges": [';
    //
    //        foreach($params['disable_datepair_ranges'] as $range){
    //            if(!is_array($range)){
    //                throw new bException('jqueryui_datepair(): All $params[disable_datepair_ranges] entries should be arrays, "'.str_log($range).'" is not', 'invalid');
    //            }
    //
    //            $script  = '["'.isset_get($range[0]).'", "'.isset_get($range[1]).'"]';
    //        }
    //
    //        $script  = str_force($entries, ",\n").']';
    //    }
    //
    //    $script .= '    });
    //                });';
    //
    //    return $html.html_script($script);
    //
    //}catch(Exception $e){
    //    throw new bException('jqueryui_datepair(): Failed', $e);
    //}
}



/*
 * Creates HTML for a jquery-ui date object
 */
function jqueryui_date_range($from_selector, $to_selector, $params = null){
    global $_CONFIG;

    try{
        array_params($params);
        array_default($params, 'labels'          , array('from'    => tr('From'),
                                                         'until'   => tr('Until')));

        array_default($params, 'placeholders'    , array('from'    => tr('From'),
                                                         'until'   => tr('Until'),
                                                         't_from'  => tr('From'),
                                                         't_until' => tr('Until')));
        array_default($params, 'number_of_months', 1);
        array_default($params, 'change_month'    , true);
        array_default($params, 'change_year'     , true);
        array_default($params, 'default_date'    , '+1w');
        array_default($params, 'auto_submit'     , true);
        array_default($params, 'separator'       , '');
        array_default($params, 'extra'           , '');
        array_default($params, 'class'           , '');
        array_default($params, 'label_class'     , '');
        array_default($params, 'time'            , false);
        array_default($params, 'date_format'     , $_CONFIG['jqueryui']['date_format']);
        array_default($params, 'jq_date_format'  , $_CONFIG['jqueryui']['jq_date_format']);

        if($params['auto_submit']){
            array_default($params, 'on_select', '   function (date) {
                                                        $(this).closest("form").submit();
                                                    }');

        }else{
            array_default($params, 'on_select', '   function (date) {
                                                    }');
        }

        html_load_css('base/jquery-ui/jquery.ui.datepicker');

        if(empty($params['options'])){
            $params['options'] = '';

        }else{
            $params['options'] = str_ends(str_starts(str_force($params['options']), '{'), '}');
        }

        if($params['time']){
            $from_t  = $params;
            $until_t = $params;

            $from_t['default_time']  = isset_get($params['from_t']);
            $until_t['default_time'] = isset_get($params['until_t']);

            if($params['labels']){
                $html = '   <label class="'.$params['label_class'].'" for="'.$from_selector.'">'.$params['labels']['from'].'</label>
                            <input class="'.$params['class'].'" type="text" id="'.$from_selector.'" name="'.$from_selector.'" value="'.substr(cfm(isset_get($params['from'], '')), 0, 10).'" placeholder="'.isset_get($params['placeholders']['from']).'"'.($params['extra'] ? ' '.$params['extra'] : '').'>
                            '.jqueryui_time($from_selector.'_t', $from_t).' '.$params['separator'].'
                            <label class="'.$params['label_class'].'" for="'.$to_selector.'">'.$params['labels']['until'].'</label>
                            <input class="'.$params['class'].'" type="text" id="'.$to_selector.'" name="'.$to_selector.'" value="'.substr(cfm(isset_get($params['until'], '')), 0, 10).'" placeholder="'.isset_get($params['placeholders']['until']).'"'.($params['extra'] ? ' '.$params['extra'] : '').'>
                            '.jqueryui_time($to_selector.'_t', $until_t);

            }else{
                $html = '   <input class="'.$params['class'].'" type="text" id="'.$from_selector.'" name="'.$from_selector.'" value="'.substr(cfm(isset_get($params['from'], '')), 0, 10).'" placeholder="'.isset_get($params['placeholders']['from']).'"'.($params['extra'] ? ' '.$params['extra'] : '').'>
                            '.jqueryui_time($from_selector.'_t', $from_t).' '.$params['separator'].'
                            <input class="'.$params['class'].'" type="text" id="'.$to_selector.'" name="'.$to_selector.'" value="'.substr(cfm(isset_get($params['until'], '')), 0, 10).'" placeholder="'.isset_get($params['placeholders']['until']).'"'.($params['extra'] ? ' '.$params['extra'] : '').'>
                            '.jqueryui_time($to_selector.'_t', $until_t);
            }

        }else{
            if($params['labels']){
                $html = '   <label class="'.$params['label_class'].'" for="'.$from_selector.'">'.$params['labels']['from'].'</label>
                            <input class="'.$params['class'].'" type="text" id="'.$from_selector.'" name="'.$from_selector.'" value="'.date_convert(cfm(isset_get($params['from'], '')), $params['date_format']).'" placeholder="'.isset_get($params['placeholders']['from']).'"'.($params['extra'] ? ' '.$params['extra'] : '').'>
                            '.$params['separator'].'
                            <label class="'.$params['label_class'].'" for="'.$to_selector.'">'.$params['labels']['until'].'</label>
                            <input class="'.$params['class'].'" type="text" id="'.$to_selector.'" name="'.$to_selector.'" value="'.date_convert(cfm(isset_get($params['until'], '')), $params['date_format']).'" placeholder="'.isset_get($params['placeholders']['until']).'"'.($params['extra'] ? ' '.$params['extra'] : '').'>';

            }else{
                $html = '   <input class="'.$params['class'].'" type="text" id="'.$from_selector.'" name="'.$from_selector.'" value="'.date_convert(cfm(isset_get($params['from'], '')), $params['date_format']).'" placeholder="'.isset_get($params['placeholders']['from']).'"'.($params['extra'] ? ' '.$params['extra'] : '').'>
                            '.$params['separator'].'
                            <input class="'.$params['class'].'" type="text" id="'.$to_selector.'" name="'.$to_selector.'" value="'.date_convert(cfm(isset_get($params['until'], '')), $params['date_format']).'" placeholder="'.isset_get($params['placeholders']['until']).'"'.($params['extra'] ? ' '.$params['extra'] : '').'>';
            }
        }

        return $html.html_script('$(function() {
            $( "#'.$from_selector.'" ).datepicker({
                defaultDate: "'.date_convert(isset_get($params['until'], $params['default_date']), $params['date_format']).'",
                changeMonth: '.($params['change_month'] ? 'true' : 'false').',
                changeYear : '.($params['change_year']  ? 'true' : 'false').',
                numberOfMonths: '.$params['number_of_months'].',
                '.(isset_get($params['until'])          ? 'maxDate   : "'.date_convert($params['until'], $params['date_format']).'",' : '').'
                '.(isset_get($params['jq_date_format']) ? 'dateFormat: "'.$params['jq_date_format'].'",'                              : '').'
                onClose: function( selectedDate ) {
                    $("#'.$to_selector.'").datepicker( "option", "minDate", selectedDate );
                },
                onSelect: '.$params['on_select'].'
            });

            $( "#'.$to_selector.'" ).datepicker({
                defaultDate: "'.date_convert(isset_get($params['from'], $params['default_date']), $params['date_format']).'",
                changeMonth: '.($params['change_month'] ? 'true' : 'false').',
                numberOfMonths: '.$params['number_of_months'].',
                '.(isset_get($params['from'])           ? 'minDate   : "'.date_convert($params['from'], $params['date_format']).'",' : '').'
                '.(isset_get($params['jq_date_format']) ? 'dateFormat: "'.$params['jq_date_format'].'",'                             : '').'
                onClose: function( selectedDate ) {
                    $("#'.$from_selector.'").datepicker( "option", "maxDate", selectedDate );
                },
                onSelect: '.$params['on_select'].'
            });
        });');

    }catch(Exception $e){
        throw new bException('jqueryui_date_range(): Failed', $e);
    }
}



/*
 * Returns HTML and Javascript for a complete fancybox system
 * See http://fancyapps.com/fancybox/#docs for more information
 *
 * @$params['resource']         All images that should be displayed. Each image should contain at least 'url', 'src', 'alt', and 'title'
 * @$params['item_template']    Template HTML for all images in $params['resource']. Each item will duplicate this template HTML. The HTML should at least contain :image
 * @$params['gallery_template'] Template HTML that will contain all item_template data. If empty, will be ignored. If used, it should contain :item_template where the items will be inserted
 */
function jqueryui_fancybox($params){
    try{
        array_params($params);
        array_default($params, 'gallery_id'      , 'fancybox');
        array_default($params, 'gallery_template', '');
        array_default($params, 'item_template'   , '');
        array_default($params, 'selector'        , '.fancybox');
        array_default($params, 'open_effect'     , 'none');
        array_default($params, 'close_effect'    , 'none');
        array_default($params, 'auto_size'       , true);
        array_default($params, 'auto_resize'     , true);
        array_default($params, 'auto_center'     , true);
        array_default($params, 'fit_to_view'     , true);
        array_default($params, 'aspect_ratio'    , true);
        array_default($params, 'close_click'     , false);
        array_default($params, 'next_click'      , true);
        array_default($params, 'arrows'          , true);
        array_default($params, 'close_button'    , true);
        array_default($params, 'pre_load'        , 3);
        array_default($params, 'scrolling'       , 'no'); // yes | no | auto | visible
        array_default($params, 'load_css'        , 'jquery.fancybox');
        array_default($params, 'resource'        , array());

        html_load_js('fancybox/jquery.fancybox');

        if($params['close_click'] and $params['next_click']){
            throw new bException(tr('jqueryui_fancybox(): Both $params["close_click"] and $params["next_click"] have been set to true, but these options are mutually exclusive. Please set one (or both) to false.'), 'not-specified');
        }

        if($params['load_css']){
            html_load_css($params['load_css']);
        }

        if(!strstr($params['item_template'], ':image')){
            throw new bException(tr('jqueryui_fancybox(): Parameter $params["item_template"] does not contain :image to add the images in the template HTML'), 'not-specified');
        }

        $items = '';

        foreach($params['resource']as $data){
            $html   = $params['item_template'];
            $image  = html_img($data['src'], $data['alt'], isset_get($data['width']), isset_get($data['height']));

            $html   = str_replace(':image', $image        , $html);
            $html   = str_replace(':url'  , $data['url']  , $html);
            $html   = str_replace(':title', $data['title'], $html);

            $items .= $html;
        }

        if($params['gallery_template']){
            if(!strstr($params['gallery_template'], ':item_template')){
                throw new bException(tr('jqueryui_fancybox(): Parameter $params["gallery_template"] contains a template, but does not contain :item_template to add the items in there'), 'not-specified');
            }

            $retval = str_replace(':item_template', $items, $params['gallery_template']);

        }else{
            $retval = $items;
        }

        unset($items);

        $retval .= html_script('
            $("'.$params['selector'].'").fancybox({
                openEffect  : "'.$params['open_effect'].'",
                closeEffect : "'.$params['close_effect'].'",
                autoSize    : '.str_boolean($params['auto_size']).',
                autoResize  : '.str_boolean($params['auto_resize']).',
                autoCenter  : '.str_boolean($params['auto_center']).',
                fitToView   : '.str_boolean($params['fit_to_view']).',
                aspectRatio : '.str_boolean($params['aspect_ratio']).',
                scrolling   : '.str_boolean($params['scrolling']).',
                closeClick  : '.str_boolean($params['close_click']).',
                nextClick   : '.str_boolean($params['next_click']).',
                arrows      : '.str_boolean($params['arrows']).',
                closeButton : '.str_boolean($params['close_button']).',
                preLoad     : '.str_boolean($params['pre_load']).',
            });
        ');

        array_default($params, 'close_click'     , false);
        array_default($params, 'next_click'      , true);
        array_default($params, 'arrows'          , true);
        array_default($params, 'close_button'    , true);


        return $retval;

    }catch(Exception $e){
        throw new bException('jqueryui_fancybox(): Failed', $e);
    }
}



/*
 * Install jquery fancybox automatically
 */
function jqueryui_fancybox_install(){
    try{
throw new bException(tr('jqueryui_fancybox_install(): This function has not yet been implemented'), 'not_implemented');
    }catch(Exception $e){
        throw new bException('jqueryui_fancybox_install(): Failed', $e);
    }
}
?>
