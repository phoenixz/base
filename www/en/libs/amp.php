<?php
/*
 * AMP library
 *
 * This library adds support for Google AMP pages to websites. The library uses
 * template pages and fills in data from the specified resource. AMP pages will
 * be stored in cache to keep server load low
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
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
        throw new bException('amp_page(): Failed', $e);
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
 * Returns <amp-video> componet
 */
function amp_video(array $attributes ){
    try{
        $dont_support = tr('Your browser doesn\'\t support HTML5 video.');
        $format_amp_video = '<amp-video width="%d"
                                height="%d"
                                src="%s"
                                poster="%s"
                                layout="responsive"
                                class="amp_base_video"
                                controls>
                                <div fallback>
                                <p>%s</p>
                                </div>
                                <source type="%s" src="%s">
                            </amp-video>';

        return sprintf($format_amp_video,
            $attributes['width'],
            $attributes['height'],
            $attributes['src'],
            $attributes['poster'],
            $dont_support,
            $attributes['type'],
            $attributes['src']
        );

    }catch(Exception $e){
        throw new bException('amp_video(): Failed', $e);
    }
}



/*
 * Convert the specified URL in an AMP url
 */
function amp_url($url){
    try{
        return domain('/amp'.str_from($url, domain()));

    }catch(Exception $e){
        throw new bException('amp_url(): Failed', $e);
    }
}



/*
 * Convert HTML to AMP HTML
 */
function amp_content($html){
    try{

        /*
         * Turn video tags into amp-video tags
         */
        if ( stripos($html, '<video') !== false){
            preg_match_all('/<video.+?>[ ?\n?\r?].*<\/video>/', $html, $video_match);

            $attributes = ['class','width','height','poster','src','type'];
            $videos     = $video_match[0];

            foreach($videos as $video ){
                $search[] = $video;
                foreach($attributes as $attribute){
                    $value_matches = [];
                    preg_match('/'.$attribute.'=(["\'][:\/\/a-zA-Z0-9 -\/.]+["\'])/',$video,$value_matches);
                    $string = isset_get($value_matches[1]);
                    $values[$attribute] = trim($string,'"');

                }
                $replace[] = amp_video($values);
            }
        }

        /*
         * Turn img tags into amp-img tags
         */
        $amp_imgs = [];
        if (stripos($html, '<img') !== false){
            preg_match_all('/<img.+?>/', $html, $img_match);

            $attributes = ['src','alt','width','height','class'];
            $images     = $img_match[0];

            if (count($images) > 0){
                foreach ($images as $image) {
                    $search[] = $image;
                    foreach ($attributes as $attribute) {
                        $value_match = [];
                        preg_match('/'.$attribute.'=(["\'][:\/\/a-zA-Z0-9 -\/.]+["\'])/',$image,$value_match);
                        $string = isset_get($value_match[1]);
                        $values[$attribute] = trim($string,'"');

                    }
                    $replace[] = amp_img(
                        $values['src'],
                        $values['alt'],
                        (empty($values['width']) ? null : $values['width']),
                        (empty($values['height']) ? null : $values['height']),
                        (empty($values['class']) ? 'layout="responsive"' : 'class="'.$values['class'].'"')
                    );
                }
            }
        }

        return str_replace($search, $replace, $html);

    }catch(Exception $e){
        throw new bException('amp_content(): Failed', $e);
    }
}

?>
