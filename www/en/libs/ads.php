<?php
/*
 * Ads library
 *
 * This is the ads library file, it contains ads functions
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */



/*
 *
 */
function ads_validate_campaign($campaign, $old_campaign = null){
    try{
        load_libs('validate');

        if($old_campaign){
            $campaign = array_merge($old_campaign, $campaign);
        }

        $v = new validate_form($campaign, 'name,from,until,image_ttl,class,animation');
        $v->isNotEmpty ($campaign['name']    , tr('No campaign name specified'));
        $v->hasMinChars($campaign['name'],  2, tr('Please ensure the campaign\'s name has at least 2 characters'));
        $v->hasMaxChars($campaign['name'], 32, tr('Please ensure the campaign\'s name has less than 32 characters'));
        $v->isRegex    ($campaign['name'], '/^[a-z-]{2,32}$/', tr('Please ensure the campaign\'s name contains only lower case letters, and dashes'));

        $v->isNotEmpty ($campaign['from'],  tr('No date from is specified'));
        $v->isDate     ($campaign['from'],  tr('Please ensure that the from data is a valid date'));

        $v->isNotEmpty ($campaign['until'],  tr('No date until is specified'));
        $v->isDate     ($campaign['until'],  tr('Please ensure that the until data is a valid date'));

        $v->isNotEmpty ($campaign['image_ttl'],  tr('No image ttl specified'));
        $v->isNumeric  ($campaign['image_ttl'],  tr('Please ensure that the image ttl is numeric'));

        $v->isNotEmpty ($campaign['class'],      tr('No class is specified'));
        $v->isNotEmpty ($campaign['animation'],  tr('No animation is specified'));

        if(is_numeric(substr($campaign['name'], 0, 1))){
            $v->setError(tr('Please ensure that the campaign\'s name does not start with a number'));
        }

        /*
         * Does the campaign already exist?
         */
        if(empty($campaign['id'])){
            if($id = sql_get('SELECT `id` FROM `ads_campaigns` WHERE `name` = :name', array(':name' => $campaign['name']))){
                $v->setError(tr('The right ":campaign" already exists with id ":id"', array(':campaign' => $campaign['name'], ':id' => $id)));
            }

        }else{
            if($id = sql_get('SELECT `id` FROM `ads_campaigns` WHERE `name` = :name AND `id` != :id', array(':name' => $campaign['name'], ':id' => $campaign['id']))){
                $v->setError(tr('The right ":campaign" already exists with id ":id"', array(':campaign' => $campaign['name'], ':id' => $id)));
            }

        }

        $v->isValid();

        return $campaign;

    }catch(Exception $e){
        throw new bException(tr('ads_validate_campaign(): Failed'), $e);
    }
}



/*
 *
 */
function ads_validate_image($image, $old_image = null){
    try{
        load_libs('validate');

        if($old_image){
            $image = array_merge($old_image, $image);
        }

        $v = new validate_form($image, 'campaigns_id,file,description');
        $v->isNotEmpty ($image['campaigns_id'],  tr('No campaign id specified'));
        $v->isNumeric  ($image['campaigns_id'],  tr('Please ensure that the campaign id is numeric'));
        $v->isNotEmpty ($image['description']      , tr('No image\'s description specified'));
        $v->hasMinChars($image['description'],    2, tr('Please ensure the image\'s description has at least 2 characters'));
        $v->hasMaxChars($image['description'], 2047, tr('Please ensure the image\'s description has less than 2047 characters'));

        switch($image['platform']){
            case 'unknown':
            case 'android':
            case 'ios':
            case 'mobile':
            case 'windows':
            case 'mac':
            case 'linux':
            case 'desktop':
                break;

            default:
                $v->setError(tr('Please specify a valid platform, must be one of "unknown", "android", "ios", "mobile", "linux", "mac", "windows", or "desktop"'));
        }

        if(is_numeric(substr($image['file'], 0, 1))){
            $v->setError(tr('Please ensure that the file\'s name does not start with a number'));
        }

        $v->isValid();

        return $image;

    }catch(Exception $e){
        throw new bException(tr('ads_validate_image(): Failed'), $e);
    }
}



/*
 * Return requested campaign. If no campaign was requested, create one now
 */
function ads_campaign_get($campaign = null, $columns = null){
    try{
        if(!$campaign){

            /*
             * Is there already a post available for this user?
             * If so, use that one
             */
            sql_query('INSERT INTO `ads_campaigns` (`status`, `createdby`)
                       VALUES                      ("_new"  , :createdby )',

                       array(':createdby' => isset_get($_SESSION['user']['id'])));

            $campaign = sql_insert_id();

        }

        if(!$columns){
            /*
             * Select default columns
             */
            $columns = '`ads_campaigns`.`id`,
                        `ads_campaigns`.`createdon`,
                        `ads_campaigns`.`createdby`,
                        `ads_campaigns`.`modifiedon`,
                        `ads_campaigns`.`modifiedby`,
                        `ads_campaigns`.`status`,
                        `ads_campaigns`.`from`,
                        `ads_campaigns`.`until`,
                        `ads_campaigns`.`name`,
                        `ads_campaigns`.`seoname`,
                        `ads_campaigns`.`description`,
                        `ads_campaigns`.`image_ttl`,
                        `ads_campaigns`.`class`,
                        `ads_campaigns`.`animation`,

                        `createdby`.`name`   AS `createdby_name`,
                        `createdby`.`email`  AS `createdby_email`,
                        `modifiedby`.`name`  AS `modifiedby_name`,
                        `modifiedby`.`email` AS `modifiedby_email`';
        }

        if(is_numeric($campaign)){
            $where = ' WHERE `ads_campaigns`.`id`   = :campaign';

        }else{
            $where = ' WHERE `ads_campaigns`.`name` = :campaign';
        }

        $execute = array(':campaign' => $campaign);

        $retval  = sql_get('SELECT    '.$columns.'

                            FROM      `ads_campaigns`

                            LEFT JOIN `users` AS `createdby`
                            ON        `ads_campaigns`.`createdby`  = `createdby`.`id`

                            LEFT JOIN `users` AS `modifiedby`
                            ON        `ads_campaigns`.`modifiedby` = `modifiedby`.`id`

                            LEFT JOIN `users`
                            ON        `users`.`id` = `ads_campaigns`.`createdby`'.$where, $execute);

        return $retval;

    }catch(Exception $e){
        throw new bException('ads_post_get(): Failed', $e);
    }
}



/*
 * Return requested data for specified rights
 */
function ads_image_get($image){
    try{
        if(!$image){
            throw new bException(tr('ads_image_get(): No image specified'), 'not-specified');
        }

        if(!is_scalar($image)){
            throw new bException(tr('ads_image_get(): Specified image ":image" is not scalar', array(':image' => $image)), 'invalid');
        }

        $retval = sql_get('SELECT    `ads_images`.`id`,
                                     `ads_images`.`campaigns_id`,
                                     `ads_images`.`file`,
                                     `ads_images`.`description`,
                                     `ads_images`.`clusters_id`,

                                     `createdby`.`name`  AS `createdby_name`,
                                     `createdby`.`email` AS `createdby_email`

                           FROM      `ads_images`

                           LEFT JOIN `users` AS `createdby`
                           ON        `ads_images`.`createdby`  = `createdby`.`id`

                           WHERE     `ads_images`.`id`   = :image
                           OR        `ads_images`.`file` = :image',

                           array(':image' => $image));

        return $retval;

    }catch(Exception $e){
        throw new bException('ads_image_get(): Failed', $e);
    }
}



/*
 * Process uploaded image
 */
function ads_image_upload($files, $ad){
    global $_CONFIG;

    try{
        /*
         * Check for upload errors
         */
        load_libs('file,upload');

        upload_check_files(1);

        if(!empty($_FILES['files'][0]['error'])){
            throw new bException(isset_get($_FILES['files'][0]['error_message'], $_FILES['files'][0]['error']), 'uploaderror');
        }

        $file     = $files;
        $original = $file['name'][0];
        $file     = file_get_local($file['tmp_name'][0]);

        return ads_image_process($ad, $file, $original);

    }catch(Exception $e){
        throw new bException('ads_image_upload(): Failed', $e);
    }
}



/*
 * Process ads image file
 */
function ads_image_process($ad, $file, $original = null){
    global $_CONFIG;

    try{
        load_libs('file');

        if(empty($ad['campaign'])) {
            throw new bException('ads_image_process(): No ad image specified', 'not-specified');
        }

        $campaign = sql_get('SELECT `ads_campaigns`.`id`,
                                    `ads_campaigns`.`createdby`

                             FROM   `ads_campaigns`

                             WHERE  `ads_campaigns`.`id` = '.cfi($ad['campaign']));

        if(!$campaign){
            throw new bException(tr('ads_image_process(): Unknown ad campaign ":campaign" specified', array(':campaign' => $ad['campaign'])), 'unknown');
        }

        if((PLATFORM == 'http') and ($campaign['createdby'] != $_SESSION['user']['id']) and !has_rights('god')){
            throw new bException('ads_image_process(): Cannot upload images, this campaign is not yours', 'accessdenied');
        }



        /*
         *
         */
        $prefix = ROOT.'data/content/photos/';
        $file   = $campaign['id'].'/'.file_move_to_target($file, $prefix.$campaign['id'].'/', '-original.jpg', false, 4);
        $media  = str_runtil($file, '-');
        //$types  = $_CONFIG['blogs']['images'];



        /*
         * If no priority has been specified then get the highest one
         */
        $priority = sql_get('SELECT (COALESCE(MAX(`priority`), 0) + 1) AS `priority`

                             FROM   `ads_images`

                             WHERE  `campaigns_id` = :campaigns_id',

                             'priority', array(':campaigns_id' => $campaign['id']));

        /*
         * Store blog post photo in database
         */
        $res  = sql_query('INSERT INTO `ads_images` (`createdby`, `campaigns_id`, `file`, `platform`, `priority`)
                           VALUES                   (:createdby , :campaigns_id , :file , :platform , :priority )',

                          array(':createdby'      => isset_get($_SESSION['user']['id']),
                                ':campaigns_id'   => $campaign['id'],
                                ':file'           => $media,
                                ':platform'       => $ad['platform'],
                                ':priority'       => $priority));

        $id   = sql_insert_id();

// :DELETE: This block is replaced by the code below. Only left here in case it contains something usefull still
//    $html = '<li style="display:none;" id="photo'.$id.'" class="myclub photo">
//                <img style="width:219px;height:130px;" src="/photos/'.$media.'-small.jpg" />
//                <a class="myclub photo delete">'.tr('Delete this photo').'</a>
//                <textarea placeholder="'.tr('Description of this photo').'" class="myclub photo description"></textarea>
//            </li>';

        return array('id'          => $id,
                     'file'        => $media,
                     'description' => '');

    }catch(Exception $e){
        throw new bException('ads_image_process(): Failed', $e);
    }
}



/*
 * Update image description
 */
function ads_update_image_description($user, $image_id, $description){
    try{
        if(!is_numeric($image_id)){
            $image_id = str_from($image_id, 'photo');
        }

        $image = sql_get('SELECT `ads_images`.`id`,
                                 `ads_images`.`createdby`

                          FROM   `ads_images`

                          JOIN   `ads_campaigns`

                          WHERE  `ads_images`.`campaigns_id` = `ads_campaigns`.`id`
                          AND    `ads_images`.`id`           = '.cfi($image_id));

        if(empty($image['id'])) {
            throw new bException('ads_update_image_description(): Unknown image specified', 'unknown');
        }

        if(($image['createdby'] != $_SESSION['user']['id']) and !has_rights('god')){
            throw new bException('ads_update_image_description(): Cannot upload images, this campaign is not yours', 'accessdenied');
        }

        sql_query('UPDATE `ads_images`

                   SET    `description` = :description

                   WHERE  `id`          = :id',

                   array(':description' => cfm($description),
                         ':id'          => cfi($image['id'])));

    }catch(Exception $e){
        throw new bException('ads_update_image_description(): Failed', $e);
    }
}



/*
 * Image cluster
 */
function ads_update_image_cluster($user, $cluster, $image){
    try{
        if(!is_numeric($image)){
            $image = str_from($image, 'photo');
        }

        $clusters = sql_get('SELECT `forwarder_clusters`.`id`,
                                    `forwarder_clusters`.`createdby`

                             FROM   `forwarder_clusters`

                             WHERE  `forwarder_clusters`.`id` = '.cfi($cluster));

        if(empty($clusters['id'])) {
            throw new bException('ads_update_image_cluster(): Unknown cluster specified', 'unknown');
        }

        if(($clusters['createdby'] != $_SESSION['user']['id']) and !has_rights('god')){
            throw new bException('ads_update_image_cluster(): Cannot upload images, this cluster is not yours', 'accessdenied');
        }

        sql_query('UPDATE `ads_images`

                   SET    `clusters_id` = :clusters_id

                   WHERE  `id`          = :id',

                   array(':clusters_id' => cfi($clusters['id']),
                         ':id'          => cfi($image)));

    }catch(Exception $e){
        throw new bException('ads_update_image_cluster(): Failed', $e);
    }
}



///*
// * Get a full URL of the photo
// */
//function ads_photo_url($media, $size){
//    try{
//        switch($size){
//            case 'large':
//                // FALLTHROUGH
//            case 'medium':
//                // FALLTHROUGH
//            case 'small':
//                // FALLTHROUGH
//            case 'wide':
//                // FALLTHROUGH
//            case 'thumb':
//                /*
//                 * Valid
//                 */
//                //return current_domain('/photos/'.$media.'-'.$size.'.jpg', null, '');
//                return current_domain('/photos/'.$media.'-original.jpg', null, '');
//
//            default:
//                throw new bException(tr('ads_photo_url(): Unknown size ":size" specified', array(':size' => $size)), 'unknown');
//        }
//
//    }catch(Exception $e){
//        throw new bException('ads_photo_url(): Failed', $e);
//    }
//}



/*
 * Return the ad HTML for be inserted
 */
function ads_get(){
    global $_CONFIG;

    try{
        html_load_js('unslider/unslider');
        html_load_css('unslider/unslider');

        $userdata  = inet_get_client_data();
        $campaigns = sql_get('SELECT   `id`,
                                       `image_ttl`,
                                       `class`,
                                       `animation`

                              FROM     `ads_campaigns`

                              WHERE    `from`   <= NOW()
                              AND      `until`  >= NOW()
                              AND      `status` IS NULL

                              ORDER BY RAND()

                              LIMIT 1');

        if(empty($campaigns)){
            /*
             * We have no ad campaigns
             */
            return '';
        }

        $campaigns['image_ttl'] = $campaigns['image_ttl'] * 1000;

        switch($userdata['os']){
            case 'android':
                // FALLTHROUGH
            case 'ios':
                // FALLTHROUGH
            case 'mobile':
                $userdata['os1'] = 'mobile';
                $userdata['os2'] = $userdata['os'];
                break;

            case 'linux':
                // FALLTHROUGH
            case 'mac':
                // FALLTHROUGH
            case 'windows':
                // FALLTHROUGH
            case 'desktop':
                $userdata['os1'] = 'desktop';
                $userdata['os2'] = $userdata['os'];
                break;

            default:
                // FALLTHROUGH
            case 'unknown':
                $userdata['os1'] = 'unknown';
                $userdata['os2'] = 'unknown';
                break;
        }

        $images = sql_query('SELECT    `ads_images`.`id`,
                                       `ads_images`.`file`,
                                       `ads_images`.`description`,

                                       `forwarder_clusters`.`keyword`

                             FROM      `ads_images`

                             LEFT JOIN `forwarder_clusters`
                             ON        `forwarder_clusters`.`id` = `ads_images`.`clusters_id`

                             WHERE     `campaigns_id` = :campaigns_id
                             AND       `description` != ""
                             AND      (`platform`     = :platform1
                                OR     `platform`     = :platform2)

                             ORDER BY `priority` ASC',

                             array(':campaigns_id' => $campaigns['id'],
                                   ':platform1'    => $userdata['os1'],
                                   ':platform2'    => $userdata['os2']));

        if(!$images->rowCount()){
            /*
             * This campaign have no images
             */
            return '';
        }

        $url  = $_CONFIG['ads']['url'];
        $html = '   <div class="ads '.$campaigns['class'].'">
                        <ul>';

        while($image = sql_fetch($images)){
            if($image['description']){
                $images_list[] = $image['id'];

                if($image['keyword']){
                    $html .= '  <li>
                                    <a href="'.str_replace(':keyword', $image['file'], $url).'">'.html_img(current_domain('/photos/'.$image['file'].'-original.jpg', null), $image['description']).'</a>
                                </li>';
                }else{
                    $html .= '  <li>
                                    '.html_img(current_domain('/photos/'.$image['file'].'-original.jpg', null), $image['description']).'
                                </li>';
                }
            }
        }

        $html .= '      </ul>
                    </div>';

        $html .= html_script('  $(\'.ads.'.$campaigns['class'].' \').unslider({
                                    animation: \''.$campaigns['animation'].'\',
                                    autoplay: true,
                                    infinite: true,
                                    keys: false,
                                    arrows: false,
                                    nav: false,
                                    delay: '.$campaigns['image_ttl'].'
                                });');

        ads_insert_view($campaigns['id'], $images_list, $userdata);
        return $html;

    }catch(Exception $e){
        throw new bException('ads_get(): Failed', $e);
    }
}



/*
 * When the image of campaigns is clicked, get the data user
 */
function ads_insert_view($campaigns_id, $images_list, $userdata){
    try{

        if(empty($campaigns_id)){
            throw new bException('ads_insert_view(): No campaigns id specified', 'not-specified');
        }

        if(!is_numeric($campaigns_id)){
            throw new bException(tr('ads_insert_view(): Specified campaign ":campaign" is not numeric', array(':campaign' => $campaign)), 'invalid');
        }

        if(empty($images_list)){
            throw new bException('ads_insert_view(): No image id specified', 'not-specified');
        }

        if(empty($userdata)) {
            throw new bException('ads_insert_view(): No userdata specified', 'not-specified');
        }

        $insert = sql_prepare('INSERT INTO `ads_views` (`createdby`, `campaigns_id`, `images_id`, `ip`, `platform`, `reverse_host`, `latitude`, `longitude`, `referrer`, `user_agent`, `browser`)
                               VALUES                  (:createdby , :campaigns_id , :images_id , :ip , :platform , :reverse_host , :latitude , :longitude , :referrer , :user_agent , :browser )');

        foreach($images_list as $images_id){
            if(!is_numeric($images_id)){
                throw new bException(tr('ads_insert_view(): Specified image ":image" is not numeric', array(':image' => $images_id)), 'invalid');
            }

            $insert->execute(array(':createdby'    => isset_get($_SESSION['user']['id']),
                                   ':campaigns_id' => $campaigns_id,
                                   ':images_id'    => $images_id,
                                   ':ip'           => $userdata['ip'],
                                   ':platform'     => $userdata['platform'],
                                   ':reverse_host' => $userdata['reverse_host'],
                                   ':latitude'     => $userdata['latitude'],
                                   ':longitude'    => $userdata['longitude'],
                                   ':referrer'     => $userdata['referrer'],
                                   ':user_agent'   => $userdata['user_agent'],
                                   ':browser'      => $userdata['browser']));
        }

    }catch(Exception $e){
        throw new bException('ads_insert_view(): Failed', $e);
    }
}
?>
