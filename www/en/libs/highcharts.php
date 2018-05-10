<?php
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
function highchart_line($params){
    try{
        array_params($params);
        array_default($params, 'name' , 'container');
        array_default($params, 'class', 'actual-chart');
        array_default($params, 'url'  , '/ajax/charts/atlant_chart_demo.php');
        array_default($params, 'data' , 'x : true');

        html_load_js(array('plugins/morris/raphael-min',
                           'plugins/owl/owl.carousel',
                           'plugins/morris/morris',
                           'plugins/nvd3/lib/d3.v3',
                           'plugins/nvd3/nv.d3',
                           'plugins/sparkline/jquery.sparkline',
                           'plugins',
                           'actions',
                           'hasoffers_charts'));

        $html  = '  <div class="'.$params['class'].'">
                        <div id="'.$params['name'].'" name="'.$params['name'].'"></div>
                    </div>';

        $html .= html_script('
            function load_charts(data){
                charts["'.$params['name'].'"] = Morris.Line({element: data["element"],
                                                             data   : data["data"],
                                                             xkey   : data["xkey"],
                                                             ykeys  : data["ykeys"],
                                                             labels : data["labels"],
                                                             resize : data["resize"]});
            }

            var charts = [];

            $.ajax({
                url: "'.$params['url'].'",
                type: "GET",
                dataType: "JSON",
                data: {'.$params['data'].'}})
            .done(function(data) {
              load_charts(data);
            })
            .fail(function(e) {
                console.log(e);
            });');

        return $html;

    }catch(Exception $e){
showdie($e);
        throw new bException('highchart_line(): Failed', $e);
    }
}
?>