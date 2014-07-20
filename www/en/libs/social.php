<?php
/*
 * Social library
 *
 * This library contains social media functionalities
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>
 */


/*
 * Empty function
 */
function social_links($params = false, $returnas = 'string', $separator = ' | '){
    global $_CONFIG;

    try{
        $retval = array();

        if(!$params){
            $params = $_CONFIG['social']['links'];
        }

        foreach($params as $key => $value){
            switch($key){
                case 'youtube';
                    if($value){
                        $retval[] = '<a href="http://www.youtube.com/user/'.$value.'" class="social youtube"'.(empty($params['target']) ? '' : ' target="'.$params['target'].'"').'>Youtube</a>';
                    }

                    break;

                case 'facebook';
                    if($value){
                        $retval[] = '<a href="https://www.facebook.com/'.$value.'" class="social facebook"'.(empty($params['target']) ? '' : ' target="'.$params['target'].'"').'>Facebook</a>';
                    }

                    break;

                case 'twitter';
                    if($value){
                        $retval[] = '<a href="https://twitter.com/'.$value.'" class="social twitter"'.(empty($params['target']) ? '' : ' target="'.$params['target'].'"').'>Twitter</a>';
                    }

                    break;
            }
        }

        if($retval){
            html_load_css('social');
        }

        switch($returnas){
            case 'array':
                return $retval;

            case 'string':
                return implode($separator, $retval);

            default:
                throw new lsException('social_links(): Unknown returnas "'.str_log($returnas).'" specified', 'unknown');
        }

    }catch(Exception $e){
        throw new lsException('social_links(): Failed', $e);
    }
}
?>
