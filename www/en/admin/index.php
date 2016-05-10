<?php
require_once(dirname(__FILE__).'/../libs/startup.php');

rights_or_redirect('admin');

html_load_js('flot/jquery.flot,flot-tooltip/jquery.flot.tooltip,flot/jquery.flot.pie,flot/jquery.flot.time');
html_load_js('flot/jquery.flot.categories,flot/jquery.flot.resize,ui-elements/charts.js');

$days         = 14;

$messages_rec = sql_list('SELECT DATE(`createdon`) AS `date`,
                             COUNT(*) AS `count`

                          FROM   `messages`

                          WHERE  `direction` = "received"

                          AND    `createdon` > DATE_SUB(NOW(), INTERVAL '.$days.' DAY)

                          GROUP BY `date`;');

$messages_ref = sql_list('SELECT DATE(`messages`.`createdon`) AS `date`,
                                COUNT(`messages`.`id`) AS `count`

                          FROM   `messages`

                          JOIN   `referrals`
                          ON     `referrals`.`messages_id` = `messages`.`id`

                          WHERE  `direction` = "received"

                          AND    `referrals`.`createdon` > DATE_SUB(NOW(), INTERVAL '.$days.' DAY)

                          GROUP BY `date`;');

$messages_snt = sql_list('SELECT DATE(`createdon`) AS `date`,
                                COUNT(*) AS `count`

                          FROM   `messages`

                          WHERE  `direction` = "sent"

                          AND    `createdon` > DATE_SUB(NOW(), INTERVAL '.$days.' DAY)

                          GROUP BY `date`;');

$referrals_all = sql_list('SELECT DATE(`createdon`) AS `date`,
                                COUNT(*) AS `count`

                           FROM   `referrals`

                           WHERE  `createdon` > DATE_SUB(NOW(), INTERVAL '.$days.' DAY)

                           GROUP BY `date`;');

$referrals_voi = sql_list('SELECT DATE(`createdon`) AS `date`,
                                COUNT(*) AS `count`

                           FROM   `referrals`

                           WHERE  `status` = "voided"

                           AND    `createdon` > DATE_SUB(NOW(), INTERVAL '.$days.' DAY)

                           GROUP BY `date`;');

$referrals_dis = sql_list('SELECT DATE(`createdon`) AS `date`,
                                COUNT(*) AS `count`

                           FROM   `referrals`

                           WHERE  `status` = "disputed"

                           AND    `createdon` > DATE_SUB(NOW(), INTERVAL '.$days.' DAY)

                           GROUP BY `date`;');

$referrals_con = sql_list('SELECT DATE(`createdon`) AS `date`,
                                COUNT(*) AS `count`

                           FROM   `referrals`

                           WHERE  `status` = "confirmed"

                           AND    `createdon` > DATE_SUB(NOW(), INTERVAL '.$days.' DAY)

                           GROUP BY `date`;');

/*
 * Messages graph
 */
$html = '   <div class="row">
                '.html_flash().'
                <div class="col-md-6">
                    <section class="panel">
                        <header class="panel-heading">
                            <div class="panel-actions">
                                <a href="#" class="fa fa-caret-down"></a>
                                <a href="#" class="fa fa-times"></a>
                            </div>
                            <h2 class="panel-title">Messages Chart</h2>
                            <p class="panel-subtitle">'.tr('This chart contains information about the last two weeks of messages').'</p>
                        </header>
                        <div class="panel-body">
                            <div class="chart chart-md" id="flotMessages" style="padding: 0px; position: relative;"><canvas class="flot-base" width="441" height="350" style="direction: ltr; position: absolute; left: 0px; top: 0px; width: 441px; height: 350px;"></canvas><div class="flot-text" style="position: absolute; top: 0px; left: 0px; bottom: 0px; right: 0px; font-size: smaller; color: rgb(84, 84, 84);"><div class="flot-x-axis flot-x1-axis xAxis x1Axis" style="position: absolute; top: 0px; left: 0px; bottom: 0px; right: 0px; display: block;"><div class="flot-tick-label tickLabel" style="position: absolute; max-width: 73px; top: 327px; left: 31px; text-align: center;">0</div><div class="flot-tick-label tickLabel" style="position: absolute; max-width: 73px; top: 327px; left: 111px; text-align: center;">2</div><div class="flot-tick-label tickLabel" style="position: absolute; max-width: 73px; top: 327px; left: 191px; text-align: center;">4</div><div class="flot-tick-label tickLabel" style="position: absolute; max-width: 73px; top: 327px; left: 270px; text-align: center;">6</div><div class="flot-tick-label tickLabel" style="position: absolute; max-width: 73px; top: 327px; left: 350px; text-align: center;">8</div><div class="flot-tick-label tickLabel" style="position: absolute; max-width: 73px; top: 327px; left: 427px; text-align: center;">10</div></div><div class="flot-y-axis flot-y1-axis yAxis y1Axis" style="position: absolute; top: 0px; left: 0px; bottom: 0px; right: 0px; display: block;"><div class="flot-tick-label tickLabel" style="position: absolute; top: 301px; left: 13px; text-align: right;">0</div><div class="flot-tick-label tickLabel" style="position: absolute; top: 226px; left: 7px; text-align: right;">50</div><div class="flot-tick-label tickLabel" style="position: absolute; top: 151px; left: 1px; text-align: right;">100</div><div class="flot-tick-label tickLabel" style="position: absolute; top: 75px; left: 1px; text-align: right;">150</div><div class="flot-tick-label tickLabel" style="position: absolute; top: 0px; left: 1px; text-align: right;">200</div></div></div><canvas class="flot-overlay" width="441" height="350" style="direction: ltr; position: absolute; left: 0px; top: 0px; width: 441px; height: 350px;"></canvas><div class="legend"><div style="position: absolute; width: 54px; height: 66px; top: 16px; right: 13px; opacity: 0.85; background-color: rgb(255, 255, 255);"> </div><table style="position:absolute;top:16px;right:13px;;font-size:smaller;color:#545454"><tbody><tr><td class="legendColorBox"><div style="border:1px solid #ccc;padding:1px"><div style="width:4px;height:0;border:5px solid #0088cc;overflow:hidden"></div></div></td><td class="legendLabel">Series 1</td></tr><tr><td class="legendColorBox"><div style="border:1px solid #ccc;padding:1px"><div style="width:4px;height:0;border:5px solid #2baab1;overflow:hidden"></div></div></td><td class="legendLabel">Series 2</td></tr><tr><td class="legendColorBox"><div style="border:1px solid #ccc;padding:1px"><div style="width:4px;height:0;border:5px solid #734ba9;overflow:hidden"></div></div></td><td class="legendLabel">Series 3</td></tr></tbody></table></div></div>
                            <script type="text/javascript">
                                var flotMessagesData = [{
                                    data: [';

$list = array();

foreach($messages_rec as $date => $count){
    $date = new DateTime($date);
    $date = $date->format('U') * 1000;
    $list[] = '['.$date.', '.$count.']';
}

$html .= str_force($list, ',');
unset($messages_rec);

$html .= '                          ],
                                    label: "Messages received",
                                    color: "#0088cc"
                                }, {
                                    data: [';

$list = array();

foreach($messages_snt as $date => $count){
    $date = new DateTime($date);
    $date = $date->format('U') * 1000;
    $list[] = '['.$date.', '.$count.']';
}

$html .= str_force($list, ',');
unset($messages_snt);

$html .= '                          ],
                                    label: "Messages sent",
                                    color: "#734ba9"
                                }, {
                                    data: [';

$list = array();

foreach($messages_ref as $date => $count){
    $date = new DateTime($date);
    $date = $date->format('U') * 1000;
    $list[] = '['.$date.', '.$count.']';
}

$html .= str_force($list, ',');
unset($messages_ref);

$html .= '                          ],
                                    label: "Messages with referrals",
                                    color: "#26C949"
                                }];
                            </script>
                        </div>
                    </section>
                </div>';



/*
 * Referrals graph
 */
$html .= '      <div class="col-md-6">
                    <section class="panel">
                        <header class="panel-heading">
                            <div class="panel-actions">
                                <a href="#" class="fa fa-caret-down"></a>
                                <a href="#" class="fa fa-times"></a>
                            </div>
                            <h2 class="panel-title">'.tr('Referrals Chart').'</h2>
                            <p class="panel-subtitle">'.tr('This chart contains the last two weeks of referrals').'</p>
                        </header>
                        <div class="panel-body">
                            <div class="chart chart-md" id="flotReferrals" style="padding: 0px; position: relative;"><canvas class="flot-base" width="441" height="350" style="direction: ltr; position: absolute; left: 0px; top: 0px; width: 441px; height: 350px;"></canvas><div class="flot-text" style="position: absolute; top: 0px; left: 0px; bottom: 0px; right: 0px; font-size: smaller; color: rgb(84, 84, 84);"><div class="flot-x-axis flot-x1-axis xAxis x1Axis" style="position: absolute; top: 0px; left: 0px; bottom: 0px; right: 0px; display: block;"><div class="flot-tick-label tickLabel" style="position: absolute; max-width: 73px; top: 327px; left: 31px; text-align: center;">0</div><div class="flot-tick-label tickLabel" style="position: absolute; max-width: 73px; top: 327px; left: 111px; text-align: center;">2</div><div class="flot-tick-label tickLabel" style="position: absolute; max-width: 73px; top: 327px; left: 191px; text-align: center;">4</div><div class="flot-tick-label tickLabel" style="position: absolute; max-width: 73px; top: 327px; left: 270px; text-align: center;">6</div><div class="flot-tick-label tickLabel" style="position: absolute; max-width: 73px; top: 327px; left: 350px; text-align: center;">8</div><div class="flot-tick-label tickLabel" style="position: absolute; max-width: 73px; top: 327px; left: 427px; text-align: center;">10</div></div><div class="flot-y-axis flot-y1-axis yAxis y1Axis" style="position: absolute; top: 0px; left: 0px; bottom: 0px; right: 0px; display: block;"><div class="flot-tick-label tickLabel" style="position: absolute; top: 301px; left: 13px; text-align: right;">0</div><div class="flot-tick-label tickLabel" style="position: absolute; top: 226px; left: 7px; text-align: right;">50</div><div class="flot-tick-label tickLabel" style="position: absolute; top: 151px; left: 1px; text-align: right;">100</div><div class="flot-tick-label tickLabel" style="position: absolute; top: 75px; left: 1px; text-align: right;">150</div><div class="flot-tick-label tickLabel" style="position: absolute; top: 0px; left: 1px; text-align: right;">200</div></div></div><canvas class="flot-overlay" width="441" height="350" style="direction: ltr; position: absolute; left: 0px; top: 0px; width: 441px; height: 350px;"></canvas><div class="legend"><div style="position: absolute; width: 54px; height: 66px; top: 16px; right: 13px; opacity: 0.85; background-color: rgb(255, 255, 255);"> </div><table style="position:absolute;top:16px;right:13px;;font-size:smaller;color:#545454"><tbody><tr><td class="legendColorBox"><div style="border:1px solid #ccc;padding:1px"><div style="width:4px;height:0;border:5px solid #0088cc;overflow:hidden"></div></div></td><td class="legendLabel">Series 1</td></tr><tr><td class="legendColorBox"><div style="border:1px solid #ccc;padding:1px"><div style="width:4px;height:0;border:5px solid #2baab1;overflow:hidden"></div></div></td><td class="legendLabel">Series 2</td></tr><tr><td class="legendColorBox"><div style="border:1px solid #ccc;padding:1px"><div style="width:4px;height:0;border:5px solid #734ba9;overflow:hidden"></div></div></td><td class="legendLabel">Series 3</td></tr></tbody></table></div></div>
                            <script type="text/javascript">
                                var flotReferralsData = [{
                                    data: [';

$list = array();

foreach($referrals_all as $date => $count){
    $date = new DateTime($date);
    $date = $date->format('U') * 1000;
    $list[] = '['.$date.', '.$count.']';
}

$html .= str_force($list, ',');
unset($referrals_all);

$html .= '                          ],
                                    label: "All referrals",
                                    color: "#0088cc"
                                }, {
                                    data: [';

$list = array();

foreach($referrals_con as $date => $count){
    $date = new DateTime($date);
    $date = $date->format('U') * 1000;
    $list[] = '['.$date.', '.$count.']';
}

$html .= str_force($list, ',');
unset($referrals_con);

$html .= '                          ],
                                    label: "Confirmed referrals",
                                    color: "#26C949"
                                }, {
                                    data: [';

$list = array();

foreach($referrals_voi as $date => $count){
    $date = new DateTime($date);
    $date = $date->format('U') * 1000;
    $list[] = '['.$date.', '.$count.']';
}

$html .= str_force($list, ',');
unset($referrals_voi);

$html .= '                          ],
                                    label: "Voided referrals",
                                    color: "#CE2F2F"
                                }, {
                                    data: [';

$list = array();

foreach($referrals_dis as $date => $count){
    $date = new DateTime($date);
    $date = $date->format('U') * 1000;
    $list[] = '['.$date.', '.$count.']';
}

$html .= str_force($list, ',');
unset($referrals_dis);

$html .= '                          ],
                                    label: "Disputed referrals",
                                    color: "#EAAE20"
                                }];
                            </script>

                        </div>
                    </section>
                </div>
            </div>';

$params = array('title'       => tr('Dashboard'),
                'icon'        => 'fa-home',
                'breadcrumbs' => array(tr('Dashboard')));

echo ca_page($html, $params);
?>