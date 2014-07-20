<?php
include_once(dirname(__FILE__).'/libs/startup.php');
header('HTTP/1.0 500 Maintenance');

/*
 * Maintenance page may indicate a severe problem in the system that stops it from showing the normal user interface
 * It is because of this that the maintenance page is very simple, and will NOT use the normal website's interface
 */
$html = '<html>
        <head>
            <title>'.$_CONFIG['name'].' | '.tr('Maintenance').'</title>
        </head>
        <body>
            <section>
                <header>
                   <h1 style="text-align:center;">'.tr('Maintenance').'</h1>
                </header>
                <div class="row">
                    <img src="/pub/img/'.SUBENVIRONMENTNAME.'maintenance.png" style="width:400px;margin:0 auto 0 auto"/>
                    <p>'.tr('Sorry, but the site is currently under maintenance, please check back in about 5 minutes!.').'</p>
                </div>
            </section>
        </body>
    </html>';

die($html);
?>
