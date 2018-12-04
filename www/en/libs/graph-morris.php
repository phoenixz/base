<?php
/*
 * graph morris library
 *
 * This is an graph generator using morris JS
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Camilo Antonio Rodriguez Cruz <crodriguez@capmega.com>
 * @category Function reference
 * @package graph-morris
 */



/*
 * Initialize the library, automatically executed by libs_load()
 *
 * NOTE: This function is executed automatically by the load_libs() function and does not need to be called manually
 *
 * @author Camilo Antonio Rodriguez Cruz <crodriguez@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package graph-morris
 *
 * @return void
 */
function graph_morris_library_init(){
    try{
        html_load_js('plugins/icheck/icheck.min.js,js/plugins/mcustomscrollbar/jquery.mCustomScrollbar.min,plugins/morris/raphael-min,plugins/morris/morris.min');

    }catch(Exception $e){
        throw new bException('empty_library_init(): Failed', $e);
    }
}



/*
 * Generate and return morris chart
 *
 * @author Camilo Rodriguez <crodriguez@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package atlant-dashboard
 *
 * @param string $params[type] for chart valid values are Line, Area, Bar and Donut
 * @param string $element the html element where static must be added
 * @param array  $items for generate chart
 * @param array  $labels for each item must be a label
 * @param array  $colors for each item must be a color
 * @param array  $param optional options for morris chart for more information plese check http://morrisjs.github.io/morris.js/#getting-started
 * @return string The HTML for the top widget
 */
function graph_morris_generate(array $params){
    try{
        array_params($params);
        array_default($params, 'type'   , 'bar');
        array_default($params, 'labels' , 'label1');
        array_default($params, 'colors' , '#33414E');
        array_default($params, 'options', array());

        $script = 'var morrisCharts = function() {
                          Morris.'.$params['type'].'({
                              element: \''.$params['element'].'\',
                              data: [';

        foreach($params['items'] as $key => $item){
            if($params['type'] == 'Donut'){
                $script .= '{label: "'.$key.'", value: '.$item.'},';

            }else{
                if(is_array($item)){
                    // :TODO: add validation for multiples values
                    //
                }else{
                    $script .= '    { y: \''.$key.'\', a: '.$item.'},';
                }
            }
        }

        /*
         * process opcional params
         */
        $optional_params = '';

        foreach($params['options'] as $key => $value){
            if(is_numeric($value)){
                $optional_params .= $key.': '.$value.','.PHP_EOL;

            }else{
                $optional_params .= $key.': \''.$value.'\','.PHP_EOL;
            }
        }

        $script .=           '],
                              '.$optional_params.'
                              xkey: \'y\',
                              ykeys: [\'a\'],
                              labels: [\''.implode('\',\'', $params['labels']).'\'],
                              barColors: [\''.implode('\',\'', $params['colors']).'\'],
                              lineColors: [\''.implode('\',\'', $params['colors']).'\'],
                              colors: [\''.implode('\',\'', $params['colors']).'\'],
                          });
                      }();';

        return html_script($script);

     }catch(Exception $e){
        throw new bException('graph_morris_generate(): Failed', $e);
    }
}
?>
