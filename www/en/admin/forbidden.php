<?php
require_once(dirname(__FILE__).'/../libs/startup.php');
header('HTTP/1.0 403 Forbidden');

if(empty($_GET['page'])){
    page_404();
}

$html = '   <div class="row">
                <div class="col-md-12">
                    <section class="panel panel-secondary">
                        <header class="panel-heading">
                            <h2>'.tr('403 - Forbidden').'</h2>
                            <p>'.tr('You do not have the right to access the page "<a href="'.domain(urldecode($_GET['page'])).'">'.str_log(urldecode($_GET['page'])).'<a>"').'</p>
                        </header>
                        <div class="panel-body">
                            <img src="/pub/img/404.png" alt="403 - Doh!" class="center">
                        </div>
                    </section>
                </div>
            </div>';

$params = array('title'       => '403 - Forbidden | refer.vegas',
                'icon'        => 'fa-home',
                'breadcrumbs' => array(tr('Dashboard')));

$meta   = array('description' => '403 - Page not found | refer.vegas',
                'keywords'    => '403, page not found,refer');

echo ca_page($html, $params);
?>
