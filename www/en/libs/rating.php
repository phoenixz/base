<?php
/*
 * Rating library
 *
 * Library to manage HTML star ratings, google ratings, etc
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 */



/*
 * Initialize the library
 * Automatically executed by libs_load()
 */
function rating_library_init(){
    try{
        ensure_installed(array('name'      => 'rating',
                               'project'   => 'rating',
                               'callback'  => 'rating_install',
                               'checks'    => array(ROOT.'pub/js/rating/rating.js',
                                                    ROOT.'pub/css/rating/rating.css')));

//        load_config('rating');
        html_load_js('rating/rating');
        html_load_css('rating/rating');

    }catch(Exception $e){
        throw new bException('rating_library_init(): Failed', $e);
    }
}



/*
 * Install the rating library
 */
function rating_install($params){
    try{
        $params['methods'] = array('bower'    => array('commands'  => 'npm install rating2',
                                                       'locations' => array('rating-master/lib/rating.js' => ROOT.'pub/js/rating/rating.js',
                                                                            'rating-master/lib/modules'   => ROOT.'pub/js/rating/modules',
                                                                            'rating-master/themes'        => ROOT.'pub/css/rating/themes',
                                                                            '@themes/google/google.css'   => ROOT.'pub/css/rating/rating.css')),

                                   'bower'    => array('commands'  => 'bower install rating2',
                                                       'locations' => array('rating-master/lib/rating.js' => ROOT.'pub/js/rating/rating.js',
                                                                            'rating-master/lib/modules'   => ROOT.'pub/js/rating/modules',
                                                                            'rating-master/themes'        => ROOT.'pub/css/rating/themes',
                                                                            '@themes/google/google.css'   => ROOT.'pub/css/rating/rating.css')),

                                   'download' => array('urls'      => array('https://cdn.jsdelivr.net/rating2/6.6.0/rating2.css',
                                                                            'https://cdn.jsdelivr.net/rating2/6.6.0/rating2.js'),

                                                       'locations' => array('rating2.js'  => ROOT.'pub/js/rating/rating.js',
                                                                            'rating2.css' => ROOT.'pub/css/rating/rating.css')));

        return install($params);

    }catch(Exception $e){
        throw new bException('rating_install(): Failed', $e);
    }
}



/*
 * Show specified rating
 */
function rating($stars){
    try{
//    $(".star").raty({
//        starOff: "pub/img/base/raty/star-off.png",
//        starOn : "pub/img/base/raty/star-on.png"
//    });


    }catch(Exception $e){
        throw new bException('rating(): Failed', $e);
    }
}



/*
 * Recalculate and update the value for the specified rating
 */
function rating_calculate($rating){
    try{
        $average = sql_get('SELECT AVG(`ratings_votes`.`rating`) FROM `ratings_votes` WHERE `ratings_id` = :ratings_id', array(':ratings_id' => $rating['id']));
        return $average;

    }catch(Exception $e){
        throw new bException('rating_calculate(): Failed', $e);
    }
}



/*
 * Update the value for the specified rating with the specified value
 */
function rating_update($ratings_id, $value){
    try{
        if(!is_numeric($value) or ($value > 5) or ($value < 0)){
            throw new bException(tr('rating_calculate(): Specified value ":value" is invalid, it should be in between 0 and 5', array(':value' => $value)), $e);
        }

        sql_query('UPDATE `ratings`

                   SET    `value` = :value

                   WHERE  `id`    = :id',

                   array(':id'    => $ratings_id,
                         ':value' => $value));

    }catch(Exception $e){
        throw new bException('rating_calculate(): Failed', $e);
    }
}
?>
