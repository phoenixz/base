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
        array_default($params, 'placeholder'   , '');
        array_default($params, 'numberofmonths', 1);
        array_default($params, 'change_month'  , true);
        array_default($params, 'default_date'  , '+1w');
        array_default($params, 'auto_submit'   , true);

        if($params['auto_submit']){
            array_default($params, 'on_select', '   function (date) {
                                                        $(this).closest("form").submit();
                                                    }');
        }

        $html = '<input type="text" id="'.$selector.'" name="'.$selector.'" placeholder="'.$params['placeholder'].'">';

        return html_script('$(function() {
            $( "#'.$selector.'" ).datepicker({
                defaultDate: "'.$params['default_date'].'",
                changeMonth: '.($params['change_month'] ? 'true' : 'false').',
                numberOfMonths: '.$params['number_of_months'].',
                '.(isset_get($params['from'])      ? 'minDate:  "'.$params['from'].'",'      : '').'
                '.(isset_get($params['until'])     ? 'maxDate:  "'.$params['until'].'",'     : '').'
                '.(isset_get($params['on_close'])  ? 'onClose:  "'.$params['on_close'].'",'  : '').'
                '.(isset_get($params['on_select']) ? 'onSelect: "'.$params['on_select'].'",' : '').'
                numberOfMonths: '.$params['numberofmonths'].'
            });
        });');

    }catch(Exception $e){
        throw new bException('jqueryui_date(): Failed', $e);
    }
}



/*
 * Creates HTML for a jquery-ui date object
 */
function jqueryui_date_range($from_selector, $to_selector, $params = null){
    try{
        array_params($params);
        array_default($params, 'labels'          , array('from' => tr('From'), 'until' => tr('Until')));
        array_default($params, 'placeholders'    , array('from' => tr('From'), 'until' => tr('Until')));
        array_default($params, 'number_of_months', 1);
        array_default($params, 'change_month'    , true);
        array_default($params, 'default_date'    , '+1w');
        array_default($params, 'auto_submit'     , true);
        array_default($params, 'separator'       , '');

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
