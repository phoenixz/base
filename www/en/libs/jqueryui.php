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
html_load_js('base/jquery-ui/jquery-ui');

if(!empty($_CONFIG['jquery-ui']['theme'])){
    html_load_css('base/jquery-ui/themes/'.$_CONFIG['jquery-ui']['theme'].'/jquery-ui');
}



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
    try{
        array_params($params);
        array_default($params, 'placeholder'     , tr('Select a date'));
        array_default($params, 'number_of_months', 1);
        array_default($params, 'change_month'    , true);
        array_default($params, 'default_date'    , '+1w');
        array_default($params, 'auto_submit'     , true);

        if($params['auto_submit']){
            array_default($params, 'on_select', '   function (date) {
                                                        $(this).closest("form").submit();
                                                    }');
        }

        if(isset_get($params['value'])){
            $params['value'] = system_date_format($params['value'], 'human_date');
        }

        $html = '<input type="text" class="'.$params['class'].' date" id="'.$selector.'" name="'.$selector.'" placeholder="'.$params['placeholder'].'" value="'.isset_get($params['value']).'">';

        return $html.html_script('$(function() {
            $( "#'.$selector.'" ).datepicker({
                defaultDate: "'.$params['default_date'].'",
                changeMonth: '.($params['change_month'] ? 'true' : 'false').',
                numberOfMonths: '.$params['number_of_months'].',
                '.(isset_get($params['from'])      ? 'minDate:  "'.$params['from'].'",'     : '').'
                '.(isset_get($params['until'])     ? 'maxDate:  "'.$params['until'].'",'    : '').'
                '.(isset_get($params['on_close'])  ? 'onClose:   '.$params['on_close'].','  : '').'
                '.(isset_get($params['on_select']) ? 'onSelect:  '.$params['on_select'].',' : '').'
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
        array_default($params, 'force_round_time'   , false);
        array_default($params, 'use_select'         , false);

        //if($params['auto_submit']){
        //    array_default($params, 'on_select', '   function (time) {
        //                                                $(this).closest("form").submit();
        //                                            }');
        //}

        $html    = '<input type="text" class="'.$params['class'].' time" id="'.$selector.'" name="'.$selector.'" placeholder="'.$params['placeholder'].'"'.($params['default_time'] ? ' value="'.$params['default_time'].'"' : '').'>';

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
                    throw new bException('jqueryui_time(): All $params[disable_time_ranges] entries should be arrays, "'.str_log($range).'" is not', 'invalid');
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
    try{
        array_params($params);
        array_default($params, 'labels'          , array('from' => tr('From'), 'until' => tr('Until')));
        array_default($params, 'placeholders'    , array('from' => tr('From'), 'until' => tr('Until'), 't_from' => tr('From'), 't_until' => tr('Until')));
        array_default($params, 'number_of_months', 1);
        array_default($params, 'change_month'    , true);
        array_default($params, 'default_date'    , '+1w');
        array_default($params, 'auto_submit'     , true);
        array_default($params, 'separator'       , '');
        array_default($params, 'time'            , false);

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
                $html = '   <label class="'.$params['class'].'" for="'.$from_selector.'">'.$params['labels']['from'].'</label>
                            <input class="'.$params['class'].'" type="text" id="'.$from_selector.'" name="'.$from_selector.'" value="'.substr(cfm(isset_get($params['from'], '')), 0, 10).'" placeholder="'.isset_get($params['placeholders']['from']).'">
                            '.jqueryui_time($from_selector.'_t', $from_t).' '.$params['separator'].'
                            <label class="'.$params['class'].'" for="'.$to_selector.'">'.$params['labels']['until'].'</label>
                            <input class="'.$params['class'].'" type="text" id="'.$to_selector.'" name="'.$to_selector.'" value="'.substr(cfm(isset_get($params['until'], '')), 0, 10).'" placeholder="'.isset_get($params['placeholders']['until']).'">
                            '.jqueryui_time($to_selector.'_t', $until_t);

            }else{
                $html = '   <input class="'.$params['class'].'" type="text" id="'.$from_selector.'" name="'.$from_selector.'" value="'.substr(cfm(isset_get($params['from'], '')), 0, 10).'" placeholder="'.isset_get($params['placeholders']['from']).'">
                            '.jqueryui_time($from_selector.'_t', $from_t).' '.$params['separator'].'
                            <input class="'.$params['class'].'" type="text" id="'.$to_selector.'" name="'.$to_selector.'" value="'.substr(cfm(isset_get($params['until'], '')), 0, 10).'" placeholder="'.isset_get($params['placeholders']['until']).'">
                            '.jqueryui_time($to_selector.'_t', $until_t);
            }

        }else{
            if($params['labels']){
                $html = '   <label class="'.$params['class'].'" for="'.$from_selector.'">'.$params['labels']['from'].'</label>
                            <input class="'.$params['class'].'" type="text" id="'.$from_selector.'" name="'.$from_selector.'" value="'.substr(cfm(isset_get($params['from'], '')), 0, 10).'" placeholder="'.isset_get($params['placeholders']['from']).'">
                            '.$params['separator'].'
                            <label class="'.$params['class'].'" for="'.$to_selector.'">'.$params['labels']['until'].'</label>
                            <input class="'.$params['class'].'" type="text" id="'.$to_selector.'" name="'.$to_selector.'" value="'.substr(cfm(isset_get($params['until'], '')), 0, 10).'" placeholder="'.isset_get($params['placeholders']['until']).'">';

            }else{
                $html = '   <input class="'.$params['class'].'" type="text" id="'.$from_selector.'" name="'.$from_selector.'" value="'.substr(cfm(isset_get($params['from'], '')), 0, 10).'" placeholder="'.isset_get($params['placeholders']['from']).'">
                            '.$params['separator'].'
                            <input class="'.$params['class'].'" type="text" id="'.$to_selector.'" name="'.$to_selector.'" value="'.substr(cfm(isset_get($params['until'], '')), 0, 10).'" placeholder="'.isset_get($params['placeholders']['until']).'">';
            }
        }


        return $html.html_script('$(function() {
            $( "#'.$from_selector.'" ).datepicker({
                defaultDate: "'.(isset_get($params['until']) ? $params['until'] : $params['default_date']).'",
                changeMonth: '.($params['change_month'] ? 'true' : 'false').',
                numberOfMonths: '.$params['number_of_months'].',
                '.(isset_get($params['until']) ? 'maxDate: "'.$params['until'].'",' : '').'
                onClose: function( selectedDate ) {
                    $("#'.$to_selector.'").datepicker( "option", "minDate", selectedDate );
                },
                onSelect: '.$params['on_select'].'
            });

            $( "#'.$to_selector.'" ).datepicker({
                defaultDate: "'.(isset_get($params['from']) ? $params['from'] : $params['default_date']).'",
                changeMonth: '.($params['change_month'] ? 'true' : 'false').',
                numberOfMonths: '.$params['number_of_months'].',
                '.(isset_get($params['from']) ? 'minDate: "'.$params['from'].'",' : '').'
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
?>
