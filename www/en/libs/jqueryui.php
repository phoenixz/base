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
        throw new lsException('jqueryui_accordeon(): Failed', $e);
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

        if($params['options']){
            $params['options'] = str_ends(str_starts(str_force($params['options']), '{'), '}');

        }else{
            $params['options'] = '';
        }

        $html = '<input type="text" id="'.$selector.'" name="'.$selector.'" placeholder="'.$params['placeholder'].'">';

        return html_script('$(function() {
            $( "#'.$selector.'" ).datepicker({
                defaultDate: "+1w",
                changeMonth: true,
                numberOfMonths: '.$params['numberofmonths'].',
            });
        });');

    }catch(Exception $e){
        throw new lsException('jqueryui_date(): Failed', $e);
    }
}



/*
 * Creates HTML for a jquery-ui date object
 */
function jqueryui_date_range($from_selector, $to_selector, $params = null){
    try{
        array_params($params);
        array_default($params, 'labels'          , array('from' => tr('From'), 'to' => tr('To')));
        array_default($params, 'placeholders'    , array('from' => tr('From'), 'to' => tr('To')));
        array_default($params, 'number_of_months', 1);
        array_default($params, 'change_month'    , true);
        array_default($params, 'default_date'    , '+1w');
        array_default($params, 'auto_submit'     , true);

        if($params['auto_submit']){
            array_default($params, 'on_select'       , 'function (date) {
                                                            $(this).closest("form").submit();
                                                        }');

        }else{
            array_default($params, 'on_select'       , 'function (date) {
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
                        <input class="'.$params['class'].'" type="text" id="'.$from_selector.'" name="'.$from_selector.'" placeholder="'.isset_get($params['placeholders']['from']).'">
                        <label class="'.$params['class'].'" for="'.$to_selector.'">'.$params['labels']['to'].'</label>
                        <input class="'.$params['class'].'" type="text" id="'.$to_selector.'" name="'.$to_selector.'" placeholder="'.isset_get($params['placeholders']['to']).'">';

        }else{
            $html = '   <input class="'.$params['class'].'" type="text" id="'.$from_selector.'" name="'.$from_selector.'" placeholder="'.isset_get($params['placeholders']['from']).'">
                        <input class="'.$params['class'].'" type="text" id="'.$to_selector.'" name="'.$to_selector.'" placeholder="'.isset_get($params['placeholders']['to']).'">';
        }

        return $html.html_script('$(function() {
            $( "#'.$from_selector.'" ).datepicker({
                defaultDate: "'.$params['default_date'].'",
                changeMonth: '.($params['change_month'] ? 'true' : 'false').',
                numberOfMonths: '.$params['number_of_months'].',
                onClose: function( selectedDate ) {
                    $( "#'.$to_selector.'" ).datepicker( "option", "minDate", selectedDate );
                }
                onSelect: '.$params['on_select'].'
            });

            $( "#'.$to_selector.'" ).datepicker({
                defaultDate: "'.$params['default_date'].'",
                changeMonth: '.($params['change_month'] ? 'true' : 'false').',
                numberOfMonths: '.$params['number_of_months'].',
                onClose: function( selectedDate ) {
                    $( "#'.$from_selector.'" ).datepicker( "option", "maxDate", selectedDate );
                },
                onSelect: '.$params['on_select'].'
            });
        });');

    }catch(Exception $e){
        throw new lsException('jqueryui_date_range(): Failed', $e);
    }
}
?>
