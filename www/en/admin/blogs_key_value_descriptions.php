<?php
require_once(dirname(__FILE__).'/../libs/startup.php');

rights_or_redirect('admin');
load_libs('validate');

/*
 * Ensure we have an existing blog with access!
 */
if(empty($_GET['blog'])){
    html_flash_set(tr('Please select %object%', array('%object%' => $params['object'])), 'error');
    redirect($params['redirects']['blogs']);
}

if(!$blog = sql_get('SELECT `id`, `name`, `createdby`, `seoname` FROM `blogs` WHERE `seoname` = :seoname', array(':seoname' => $_GET['blog']))){
    html_flash_set(tr($params['noblog'], array('%object%' => $params['object'])), 'error');
    redirect($params['redirects']['blogs']);
}

if(($blog['createdby'] != $_SESSION['user']['id']) and !has_rights('god')) {
    html_flash_set(tr('You do not have access to the %object% "'.$blog['name'].'"', array('%object%' => $params['object'])), 'error');
    redirect($params['redirects']['blogs_posts']);
}



array_default($params, 'label_description1'      , tr('Title'));
array_default($params, 'label_description2'      , tr('Description'));
array_default($params, 'placeholder_description1', tr('The title you want for this key / value combination'));
array_default($params, 'placeholder_description2', tr('The description you want for this key / value combination'));
array_default($params, 'validation_description'  , tr('Specified data is missing both a title and a description'));



/*
 * Update the key-value descriptions
 */
try{
    switch(isset_get($_POST['formaction'])){
        case 'Update':
            load_libs('seo,blogs');

            /*
             * Update the specified blog
             */
            s_validate_data($_POST['data']);

            $p = sql_prepare('INSERT INTO `blogs_key_value_descriptions` (`blogs_id`, `key`, `seovalue`, `description1`, `description2`)
                              VALUES                                     (:blogs_id , :key , :seovalue , :description1 , :description2 )');

            sql_query('DELETE FROM `blogs_key_value_descriptions` WHERE `blogs_id` = :blogs_id', array(':blogs_id' => $blog['id']));

            foreach($_POST['data'] as $key => $value){
                $p->execute(array(':blogs_id'     => $blog['id'],
                                  ':key'          => $value['key'],
                                  ':seovalue'     => seo_create_string($value['value']),
                                  ':description1' => isset_get($value['description1']),
                                  ':description2' => isset_get($value['description2'])));
            }

            /*
             * Due to the update, the name might have changed.
             * Redirect to ensure that the name in the URL is correct
             */
            html_flash_set(tr('The key-value descriptions for blog "%blog%" have been updated', '%blog%', str_log($blog['name'])), 'success');
            redirect(true);
    }

}catch(Exception $e){
    html_flash_set($e);
}



/*
 *
 */
$count = 1;

$html  = '  <form id="blog" name="blog" action="'.domain(true).'" method="post">
                <div class="row">
                    <div class="col-md-'.(empty($blog['id']) ? '12' : '6').'">
                        <section class="panel">
                            <header class="panel-heading">
                                <h2 class="panel-title">'.tr('Manage blog key-value descriptions').'</h2>
                                <p>'.html_flash().'</p>
                            </header>
                            <div class="panel-body">
                                <p>
                                    '.tr('Here you can modify the descriptions that are linked to certain key-value combinations. Just write down the key and value, and add a description').'
                                </p>
                                <hr>
                                <div class="form-group">
                                    <label class="col-md-3 control-label" for="key_0">'.tr('Key / Value').'</label>
                                    <div class="col-md-3">
                                        <input type="text" name="data[0][key]" id="key_0" class="form-control" value="'.isset_get($_POST['data'][0]['key']).'" maxlength="16" placeholder="'.tr('Key...').'">
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" name="data[0][value]" id="value_0" class="form-control" value="'.isset_get($_POST['data'][0]['value']).'" maxlength="128" placeholder="'.tr('Value...').'">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-md-3 control-label" for="description1_0">'.$params['label_description1'].'</label>
                                    <div class="col-md-9">
                                        <textarea class="form-control" id="description1_0" name="data[0][description1]" maxlength="2048" placeholder="'.$params['placeholder_description1'].'">'.isset_get($_POST['data'][0]['description1']).'</textarea>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-md-3 control-label" for="description2_0">'.$params['label_description2'].'</label>
                                    <div class="col-md-9">
                                        <textarea class="form-control" id="description2_0" name="data[0][description2]" maxlength="2048" placeholder="'.$params['placeholder_description2'].'">'.isset_get($_POST['data'][0]['description2']).'</textarea>
                                    </div>
                                </div>';

$r = sql_query('SELECT `key`,
                       `seovalue`,
                       `description1`,
                       `description2`

                FROM   `blogs_key_value_descriptions`

                WHERE  `blogs_id` = :blogs_id',

                array(':blogs_id' => $blog['id']));

if($r->rowCount()){

    while($row = sql_fetch($r)){
        $html .= '  <hr>
                    <div class="form-group">
                        <label class="col-md-3 control-label" for="key'.$count.'">'.tr('Key / Value').'</label>
                        <div class="col-md-3">
                            <input type="text" name="data['.$count.'][key]" id="key_'.$count.'" class="form-control" value="'.isset_get($_POST[$count]['key'], $row['key']).'" maxlength="16">
                        </div>
                        <div class="col-md-6">
                            <input type="text" name="data['.$count.'][value]" id="value_'.$count.'" class="form-control" value="'.isset_get($_POST[$count]['value'], $row['seovalue']).'" maxlength="128">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label" for="description1_'.$count.'">'.$params['label_description2'].'</label>
                        <div class="col-md-9">
                            <textarea class="form-control" id="description1_'.$count.'" name="data['.$count.'][description1]" maxlength="2048">'.isset_get($_POST[$count]['description1'], $row['description1']).'</textarea>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label" for="description2_'.$count.'">'.$params['label_description2'].'</label>
                        <div class="col-md-9">
                            <textarea class="form-control" id="description2_'.$count.'" name="data['.$count.'][description2]" maxlength="2048">'.isset_get($_POST[$count]['description2'], $row['description2']).'</textarea>
                        </div>
                    </div>';

        $count++;
    }
}

$html .= '                      <input type="submit" class="mb-xs mt-xs mr-xs btn btn-primary" name="formaction" id="formaction" value="'.tr('Update').'">
                                <a class="mb-xs mt-xs mr-xs btn btn-primary" href="'.domain('/admin/blogs.php'.((empty($blog['seoname'])) ? '' : '?blog='.$blog['seoname'])).'">'.tr('Return').'</a>
                            </div>
                        </section>
                    </div>
                </div>
            </form>';

//                    <li><label for="name">'.tr('Name').'</label><input type="text" name="name" id="name" value="'.isset_get($blog['name']).'" maxlength="64"></li>
//                    <li><label for="slogan">'.tr('Slogan').'</label><input type="text" name="slogan" id="slogan" value="'.isset_get($blog['slogan']).'" maxlength="255"></li>
//                    <li><label for="keywords">'.tr('Keywords').'</label><input type="text" name="keywords" id="keywords" value="'.isset_get($blog['keywords']).'" maxlength="255"></li>
//                    <li><label for="description">'.tr('Description').'</label><input type="text" name="description" id="description" value="'.isset_get($blog['description']).'" maxlength="160"></li>
//                </ul>
//                <input type="hidden" name="id" id="id" value="'.isset_get($blog['id']).'">'.
//                (($mode == 'create') ? '<input type="submit" name="docreate" id="docreate" value="'.tr('Create').'"> <a class="button submit" href="'.domain('/admin/blogs.php'.(empty($blog['seoname']) ? '' : '?blog='.$blog['seoname'])).'">'.tr('Back').'</a>'
//                                     : '<input type="submit" name="doupdate" id="doupdate" value="'.tr('Update').'"> <a class="button submit" href="'.domain('/admin/blogs.php'.(empty($blog['seoname']) ? '' : '?blog='.$blog['seoname'])).'">'.tr('Back').'</a>').

/*
 * Add JS validation
 */
//$vj = new validate_jquery();
//
//$vj->validate('name'       , 'required' ,              'true', '<span class="FcbErrorTail"></span>'.tr('Please provide the name of your blog'));
//$vj->validate('slogan'     , 'required' ,              'true', '<span class="FcbErrorTail"></span>'.tr('Please provide a description of your blog'));
//$vj->validate('keywords'   , 'required' ,              'true', '<span class="FcbErrorTail"></span>'.tr('Please provide keywords for your blog'));
//$vj->validate('description', 'required' ,              'true', '<span class="FcbErrorTail"></span>'.tr('Please provide a description of your blog'));
//
//$vj->validate('name'       , 'minlength',                 '4', '<span class="FcbErrorTail"></span>'.tr('Please ensure that the name has at least 4 characters'));
//$vj->validate('slogan'     , 'minlength',                 '6', '<span class="FcbErrorTail"></span>'.tr('Please ensure that the slogan has at least 6 characters'));
//$vj->validate('keywords'   , 'minlength',                 '8', '<span class="FcbErrorTail"></span>'.tr('Please ensure that the keywords have at least 8 characters'));
//
//$vj->validate('description', 'minlength',                '16', '<span class="FcbErrorTail"></span>'.tr('Please ensure that the description has at least 16 characters'));
//$vj->validate('description', 'maxlength',               '160', '<span class="FcbErrorTail"></span>'.tr('Please ensure that the description has at least 160 characters'));
//
//$vj->validate('thumbs_x'   , 'regex'    , '^[0-9]{0,3}(px)?$', '<span class="FcbErrorTail"></span>'.tr('Please specify a valid thumbs X value between 10 - 500'));
//$vj->validate('thumbs_y'   , 'regex'    , '^[0-9]{0,3}(px)?$', '<span class="FcbErrorTail"></span>'.tr('Please specify a valid thumbs Y value between 10 - 500'));
//$vj->validate('images_x'   , 'regex'    , '^[0-9]{0,4}(px)?$', '<span class="FcbErrorTail"></span>'.tr('Please specify a valid thumbs X value between 50 - 5000'));
//$vj->validate('images_y'   , 'regex'    , '^[0-9]{0,4}(px)?$', '<span class="FcbErrorTail"></span>'.tr('Please specify a valid thumbs Y value between 50 - 5000'));
//
//$html .= $vj->output_validation(array('id'   => 'blog',
//                                      'json' => false));

$params = array('title'       => tr('Manage blog key-value descriptions'),
                'icon'        => 'fa-user',
                'breadcrumbs' => array(tr('Blog'), tr('Key / value descriptions')));

echo ca_page($html, $params);


/*
 *
 */
function s_validate_data(&$data){
    global $params;

    try{
        // Validate input
        $v = new validate_form($data);

        if(!is_array($data)){
            throw new bException('Specified data is invalid', 'invalid');
        }

        foreach($data as $key => $value){
            if($key === 'id'){
                unset($data[$key]);
                continue;
            }

            if(!is_array($value)){
                $v->setError('Specified data value is invalid');
            }

            if(empty($value['key']) and empty($value['value']) and empty($value['description1']) and empty($value['description2'])){
                /*
                 * This set is completely empty, drop it
                 */
                unset($data[$key]);
                continue;
            }

            if(empty($value['key'])){
                $v->setError('Specified data is missing a key');
            }

            if(empty($value['value'])){
                $v->setError('Specified data is missing a value');
            }

            if(empty($value['description1']) and empty($value['description2'])){
                $v->setError($params['validation_description']);
            }
        }

        if(!$v->isValid()) {
           throw new bException($v->getErrors(), 'validation');
        }

        return $data;

    }catch(Exception $e){
        throw new bException('s_validate_data(): Failed', $e);
    }
}
?>
