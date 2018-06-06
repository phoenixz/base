<?php
/*
 * jquery-blueimp library
 *
 * This is a front-end for the jquery blueimp gallery
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 */



/*
 *
 */
function jquery_blueimp($area_selector, $link_selector, $params = null, $options = null){
    try{
        array_ensure($params);
        array_default($params, 'title'     , '');
        array_default($params, 'prev'      , '‹');
        array_default($params, 'next'      , '›');
        array_default($params, 'close'     , '×');
        array_default($params, 'play_pause', '');
        array_default($params, 'indicator' , '');

        array_ensure($options);
        array_default($options, 'index'   , 'link');
        array_default($options, 'event'   , 'event');
        array_default($options, 'onclosed', '   function(){
                                                    setTimeout(function(){
                                                        $("body").css("overflow","");
                                                    },200);
                                                }');


        html_load_js('plugins/blueimp/jquery.blueimp-gallery');

        $script = '
            $("'.$area_selector.'").on("click", "'.$link_selector.'", function (event) {
                event = event || window.event;
                var target = event.target || event.srcElement;
                var link = target.src ? target.parentNode : target;
                var options = {'.array_implode_with_keys($options, ',', ':').'};
                var links = this.getElementsByTagName("a");
                blueimp.Gallery(links, options);
            };';

        $html = '   <div id="blueimp-gallery" class="blueimp-gallery blueimp-gallery-controls" style="display: none;">
                        <div class="slides" style="width: 30480px;"></div>
                        <h3 class="title">'.$params['title'].'</h3>
                        <a class="prev">'.$params['prev'].'</a>
                        <a class="next">'.$params['next'].'</a>
                        <a class="close">'.$params['close'].'</a>
                        <a class="play-pause">'.$params['play_pause'].'</a>
                        <ol class="indicator">'.$params['indicator'].'</ol>
                    </div>';

        return $html.html_script($script);

    }catch(Exception $e){
        throw new bException('jquery_blueimp(): Failed', $e);
    }
}
?>
