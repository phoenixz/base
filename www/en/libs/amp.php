<?php
/*
 * AMP library
 *
 * This library adds support for Google AMP pages to websites. The library uses
 * template pages and fills in data from the specified resource. AMP pages will
 * be stored in cache to keep server load low
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 */



/*
 * Expects a list of images and returns an AMP carousel component
 */
function amp_component_carousel($params){
    try {
        array_params($params);
        array_default($params, 'height', 300);
        array_default($params, 'width' , 400);
        array_default($params, 'type'  , 'carousel');
        array_default($params, 'layout', 'fixed-height');
        array_default($params, 'images', array());

        if(!is_array($params['images'])){
            throw new bException(tr('amp_component_carousel(): Expected array as parameters'), 'invalid');
        }

        $carousel = '<amp-carousel height="'.$params['height'].'" layout="'.$params['layout'].'" type="'.$params['type'].'">';

        foreach($params['images'] as $image => $alt){
            $carousel .= '<amp-img src="'.$image.'" width="'.$params['width'].'" height="'.$params['height'].'" alt="'.$alt.'"></amp-img>';
        }

        $carousel .= '</amp-carousel>';
        return $carousel;

    }catch(Exception $e){
        throw new bException(tr('amp_component_carousel(): Component failed'), $e);
    }
}



/*
 * Show the AMP verion of the specified page
 */
function amp_page_cache(){
    try{
        load_libs('cache');

        $data = cache_read($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'], 'amp');

        if($data){
           echo $data;
           die();
        }

        return false;

    }catch(Exception $e){
        throw new bException('amp_page_cache(): Failed', $e);
    }
}



/*
 * Show the AMP verion of the specified page
 */
function amp_page($params){
    try{
        array_params($params);
        array_default($params, 'template'  , null);
        array_default($params, 'canonical' , str_replace('/amp/', '/', domain(true)));
        array_default($params, 'resource'  , null);
        array_default($params, 'components', null);

        load_libs('cache');

        if(!$params['template']){
            throw new bException(tr('amp_page(): No template page specified'), 'not-specified');
        }

        if(!$params['canonical']){
            throw new bException(tr('amp_page(): No canonical url specified'), 'not-specified');
        }

        if(!$params['resource']){
            throw new bException(tr('amp_page(): No resource specified'), 'not-specified');
        }

        $file = ROOT.'data/content/amp/'.$params['template'].'.amp';

        if(!file_exists($file)){
            throw new bException(tr('amp_page(): Specified template ":template" does not exist', array(':template' => $params['template'])), 'not-exist');
        }

        $data = file_get_contents($file);

        if(!$data){
            throw new bException(tr('amp_page(): Specified template ":template" is empty', array(':template' => $params['template'])), 'not-exist');
        }

        $data = str_replace(':canonical', $params['canonical'], $data);

        /*
         * Lets replace resouces on our template
         */
        if($params['resource']){
            foreach($params['resource'] as $key => $value){
                $data = str_replace(':'.$key, $value, $data);
            }
        }

        /*
         * Lets add out components into the mix
         */
        if($params['components']){
            foreach($params['components'] as $key => $component_data){
                try{
                    $component      = str_replace(':', '', $key);
                    $component      = 'amp_component_'.$component;
                    $component_data = amp_component_carousel($component_data);
                    $data           = str_replace(':'.$key, $component_data, $data);

                }catch(Exception $e){
                    throw new bException(tr('amp_page(): Specified component failed or does not exist ":component"', array(':component' => $component)), $e);
                }
            }
        }

        $data = cache_write($data, $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'], 'amp');

        echo $data;
        die();

    }catch(Exception $e){
        throw new bException('amp_page(): Failed', $e);
    }
}



/*
 * Return <amp-img> tag, using <html_img>
 */
function amp_img($src, $alt, $width = null, $height = null, $more = 'layout="responsive"'){
    try{
        $img = html_img($src, $alt, $width, $height, $more);
        $img = '<amp-img'.substr($img, 4);
        $img .= '</amp-img>';

        return $img;

    }catch(Exception $e){
        throw new bException('amp_img(): Failed', $e);
    }
}



/*
 * Returns <amp-youtube> componet
 */
function amp_youtube(array $attributes){
    try{
        if(empty($attributes['hashtag'])) return '';

        array_default($attributes, 'width' , '480');
        array_default($attributes, 'height', '385');
        array_default($attributes, 'class' , '');

        $attributes['class'] .= ' amp_base_youtube';

        $amp_youtube    = '<amp-youtube width="'.$attributes['width'].'"
                                height="'.$attributes['height'].'"
                                layout="responsive"
                                class="'.trim($attributes['class']).'"
                                data-videoid="'.$attributes['hashtag'].'">
                            </amp-youtube>';

        return $amp_youtube;

    }catch(Exception $e){
        throw new bException('amp_youtube(): Failed', $e);
    }
}



/*
 * Returns <amp-video> componet
 */
function amp_video(array $attributes){
    try{
        $format_amp_video = '<amp-video width="'.$attributes['width'].'"
                                height="'.$attributes['height'].'"
                                src="'.$attributes['src'].'"
                                poster="'.$attributes['poster'].'"
                                layout="responsive"
                                class="amp_base_video"
                                controls>
                                <div fallback>
                                <p>'.tr('Your browser doesn\'\t support HTML5 video.').'</p>
                                </div>
                                <source type="'.$attributes['type'].'" src="'.$attributes['src'].'">
                            </amp-video>';

        return $format_amp_video;

    }catch(Exception $e){
        throw new bException('amp_video(): Failed', $e);
    }
}



/*
 * Convert the specified URL in an AMP url
 */
function amp_url($url){
    try{
        /*
         * Strip out protocol and domain from url
         */
        $url         = str_from($url, '//');
        $first_slash = strpos( $url, '/');
        $path_length = strlen($url) - $first_slash;
        $path        = substr($url, $first_slash, $path_length);

        return domain('/amp'.$path);

    }catch(Exception $e){
        throw new bException('amp_url(): Failed', $e);
    }
}



/*
 * Convert HTML to AMP HTML
 */
function amp_content($html){
    try{
        $search  = array();
        $replace = array();
// :TODO: Add caching support!
        /*
         * Do we have this content cached?
         */

        /*
         * First we make sure we don't have forbbiden html tags in our html
         */
        $html = amp_html_cleanup($html);
        /*
         * Turn video tags into amp-video tags
         */
        if(strstr($html, '<video')){
            preg_match_all('/<video.+?>[ ?\n?\r?].*<\/video>/', $html, $video_match);

            $attributes = array('class', 'width', 'height', 'poster', 'src', 'type');
            $videos     = $video_match[0];

            if(count($videos)){
                foreach($videos as $video ){
                    $search[] = $video;

                    foreach($attributes as $attribute){
                        $value_matches = array();
                        preg_match('/'.$attribute.'=(["\'][:\/\/a-zA-Z0-9 -\/.]+["\'])/', $video, $value_matches);

                        $string = isset_get($value_matches[1]);
                        $values[$attribute] = trim($string, '"');
                    }

                    $replace[] = amp_video($values);
                }
            }
        }

        /*
         * Turn iframes into their target components
         */
        if(strstr($html, '<iframe')){
            preg_match_all('/<iframe.*>.*<\/iframe>/s', $html, $iframe_match);

            $attributes = array('class', 'width', 'height');
            $iframes    = $iframe_match[0];

            if(count($iframes)){
                foreach($iframes as $iframe ){
                    if(!strstr($iframe, 'youtube')) continue;

                    $search[] = $iframe;

                    foreach($attributes as $attribute){
                        $value_matches = array();
                        preg_match('/'.$attribute.'=(["\'][:\/\/a-zA-Z0-9 -\/.]+["\'])/', $iframe, $value_matches);

                        $string = isset_get($value_matches[1]);
                        $attributes[$attribute] = trim($string, '"');
                    }

                    preg_match('/(?:(youtube\.com\/|youtube-nocookie\.com\/)\S*(?:(?:\/e(?:embed))?\/|watch\?(?:\S*?&?v\=))|youtu\.be\/)([a-zA-Z0-9_-]{6,11})/', $iframe, $hashtag);
                    $attributes['hashtag'] = $hashtag[2];

                    $replace[] = amp_youtube($attributes);
                }
            }
        }

        /*
         * Turn img tags into amp-img tags
         */
        if(strstr($html, '<img')){
            preg_match_all('/<img.+?>/', $html, $img_match);

            $attributes = array('src', 'alt', 'width', 'height', 'class');
            $images     = $img_match[0];

            if(count($images)){
                foreach($images as $image){
                    $search[] = $image;

                    foreach($attributes as $attribute){
                        $value_match = array();
                        preg_match('/'.$attribute.'=(["\'][:\/\/a-zA-Z0-9 -\/.]+["\'])/', $image, $value_match);

                        $string             = isset_get($value_match[1]);
                        $values[$attribute] = trim($string, '"');
                    }

                    /*
                     * If our src is empty we should check to see if it is an inline image
                     * Ej. base64, this is useful when sending blog post body into amp content
                     * function
                     */
                    if(empty($values['src'])){
// :TODO: remove continue and implement, from this point on html_img will complain about the base64 String
$replace[] = '';
continue;

                        preg_match('/src=(["\'][a-z:\/;a-z64,0-9A-Z\+=]+["\'])/', $image, $base64_match);
                        $string        = isset_get($base64_match[1]);
                        $values['src'] = trim($string, '"');

                    }elseif(empty($values['src'])){
                        continue;
                    }

                    $values['width']  = (empty($values['width'])  ? null                  : $values['width']);
                    $values['height'] = (empty($values['height']) ? null                  : $values['height']);
                    $values['class']  = (empty($values['class'])  ? 'layout="responsive"' : 'class="'.$values['class'].'"');
                    $values['alt']    = (empty($values['alt'])    ? 'image'               : $values['alt']);

                    $replace[] = amp_img($values['src'], $values['alt'], $values['width'], $values['height'], $values['class']);
                }
            }
        }

        return str_replace($search, $replace, $html);

    }catch(Exception $e){
        throw new bException('amp_content(): Failed', $e);
    }
}



/*
 * Removes unallowed HTML tags for AMP
 */
function amp_html_cleanup($html){
    try{
        /*
         * List of things that need to be handled, populate list as needed
         */
        $keep_content_tags = array('font');
        $forbidden_tags    = array('frame', 'frameset', 'object', 'param', 'applet', 'embed');
        $empty_attributes  = array('target' => '_blank');
        $remove_attributes = array('style');
        $search            = array();
        $replace           = array();

        /*
         * Remove tags that are
         */
        foreach($keep_content_tags as $tag){
            $search [] = '/<'.$tag.'.*>(.*)<\/'.$tag.'>/s';
            $replace[] = '$1';
        }
        /*
         * Populate empty attributes with defaults
         */
        foreach($empty_attributes as $attribute => $value){
            $search [] = '/'.$attribute.'=(["\']["\'])/';
            $replace[] = $attribute.'="'.$value.'"';
        }

        /*
         * Remove forbidden attributes
         */
        foreach($remove_attributes as $attribute){
            $search [] = '/'.$attribute.'=(["\']([:; \-\(\)\!a-zA-Z0-9\/.]+|)["\'])/';
            $replace[] = '';
        }

        /*
         * Just remove
         */
        foreach($forbidden_tags as $tag){
            $search [] = '/<'.$tag.'.*>.*<\/'.$tag.'>/s';
            $replace[] = '';
        }

        return preg_replace($search, $replace, $html);

    }catch(Exception $e){
        throw new bException('amp_html_cleanup(): Failed', $e);
    }
}
?>