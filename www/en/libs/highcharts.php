<?php
// :OBSOLETE: This library is obsolete, see the library graph-highcharts
/*
 * highcharts library
 *
 * This library contains highchart functions that can be used to generate charts
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 *
 * $params['name']  => Name of the chart, this is unique
 * $params['url' ]  => Url in ajax call
 * $params['data']  => Data to send at server in call ajax
 * $params['class'] => Aditional css classes to chart
 */
// :OBSOLETE: This library is obsolete, see the library graph-highcharts
function highchart_line($params){
    try{
        $params['type'] = 'line';
        load_libs('graph-highcharts');
        obsolete();
        return graph_highcharts($params);

    }catch(Exception $e){
        throw new bException('highchart_line(): Failed', $e);
    }
}
?>