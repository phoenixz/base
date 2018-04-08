<?php
/*
 * Google maps library
 *
 * This library contains google maps functions
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Johan Geuze, Sven Oostenbrink <support@ingiga.com>
 */



/*
 * Get streetview image from coords
 */
function googlemaps_get_streetview_image($lat, $long, $x = 640, $y = 480) {
    global $_CONFIG;

    try{
        /*
         * Validate sizes
         */
        if(($x > 640) or ($x < 0)) {
            $x = 640;
        }

        if(($y > 640) or ($y < 0)) {
            $y = 640;
        }

        load_libs('curl');

        $data = curl_get('http://maps.googleapis.com/maps/api/streetview?size='.$x.'x'.$y.'&location='.$lat.',%20'.$long.'&sensor=true&key='.$_CONFIG['google-map-api-key'],'http://'.$_SESSION['domain']);

        if(isset($data['status']['http_code']) and ($data['status']['http_code'] == 200)) {
            if(strlen($data['data'])<10000) {
                return false;

            } else {
                return $data['data'];
            }

        } else {
            throw new bException('googlemap_get_streetview_image() googleapi failed : '.show($data['status']));
        }

    }catch(Exception $e){
        throw new bException('googlemaps_get_streetview_image(): Failed', $e);
    }
}



/*
 * Cache streetview images
 */
function googlemaps_cache_streetmaps($lat, $long, $x = 640, $y = 480) {
    try{
        load_libs('file');

        $cache_md5  = md5($lat.$long);
        $cache_dir  = ROOT.'www/streetview_cache/';
        $cache_file = str_interleave($cache_md5, '/', 4).'.jpg';

        if(!file_exists($cache_dir.$cache_file)) {
            $filedata = googlemaps_get_streetview_image($lat, $long, $x, $y);

            if(empty($filedata)) {
                return false;

            } else {
                file_ensure_path(dirname($cache_dir.$cache_file));
                file_put_contents($cache_dir.$cache_file,$filedata);
            }
        }

        return '/streetview_cache/'.$cache_file;

    }catch(Exception $e){
        throw new bException('googlemaps_cache_streetmaps(): Failed', $e);
    }
}



/*
 * Display a goole map with markers
 */
function googlemaps_map_with_markers($markers = array(), $divid = 'map-canvas') {
    global $_CONFIG;

    try{
        //load external library
        html_load_js('https://maps.googleapis.com/maps/api/js?key='.$_CONFIG['google-map-api-key']);

        //google maps
        $html='<script>
        $(document).on("ready", function(){

            var map;
            var directionDisplay;
            var directionsService = new google.maps.DirectionsService();
            function gmap_initialize() {
                var mapOptions = {
                    mapTypeId: google.maps.MapTypeId.ROADMAP
                };
                map = new google.maps.Map(document.getElementById(\''.$divid.'\'),
                mapOptions);

                var locations = [';

            if(empty($markers)){
                throw new bException('googlemaps_map_with_markers(): Failed to place any markers', isset_get($e, 'markerfailed'));
            }
            foreach($markers as $key => $data) {
                try{
                    if(empty($data['lat'])){
                        throw new bException('googlemaps_map_with_markers(): No latitute specified for marker "'.$key.'"', isset_get($e, 'markerfailed'));
                    }

                    if(empty($data['lng'])){
                        throw new bException('googlemaps_map_with_markers(): No longitude specified for marker "'.$key.'"', isset_get($e, 'markerfailed'));
                    }

                    if(!isset($first)) {
                        $first=$data;
                    }

                    if(empty($data['icon'])) {
                        $data['icon']='/pub/img/googlemaps/a.png';
                    }

                    $list[] = '[\''.$data['html'].'\', '.$data['lat'].', '.$data['lng'].', \''.$data['icon'].'\']';

                }catch(Exception $e){
                    /*
                     * Marker failed. Ignore it and continue.
                     */
                    unset($markers[$key]);
                }
            }

            $html .= implode(',', $list).'];
                var infowindow = new google.maps.InfoWindow();

                var marker, i;
                var markers = new Array();
                var bounds = new google.maps.LatLngBounds ();

                for (i = 0; i < locations.length; i++) {
                    marker = new google.maps.Marker({
                    position: new google.maps.LatLng(locations[i][1], locations[i][2]),
                    map: map,
                    icon: locations[i][3]
                    });

                    bounds.extend (new google.maps.LatLng(locations[i][1], locations[i][2]));

                    google.maps.event.addListener(marker, \'click\', (function(marker, i) {
                        return function() {
                            infowindow.setContent(locations[i][0]);
                            infowindow.open(map, marker);
                        }
                    })(marker, i));
                }

                map.fitBounds (bounds);
                //fix zoom if the zoomlevel is too high
                var listener = google.maps.event.addListener(map, "idle", function() {
                    if (map.getZoom() > 16) map.setZoom(16);
                    google.maps.event.removeListener(listener);
                });

                //routeplanner options
                var route_start = null;

                var latlng = new google.maps.LatLng(locations[0][1], locations[0][2]);
                directionsDisplay = new google.maps.DirectionsRenderer();
                var myOptions = {
                    zoom: 14,
                    center: latlng,
                    mapTypeId: google.maps.MapTypeId.ROADMAP,
                    mapTypeControl: false
                };
                directionsDisplay.setMap(map);
                directionsDisplay.setPanel(document.getElementById("directionsPanel"));
            }

            google.maps.event.addDomListener(window, \'load\', gmap_initialize);

            $(document).ready(function() {
                $(document).on("click",".gmap_pan", function(event){
                    var coords =$(this).prop(\'id\').split(\',\');
                    map.panTo(new google.maps.LatLng(coords[0],coords[1]));
                    map.setZoom(15);
                });
            });

            function calcRoute() {
                var route_end = "'.$first['lat'].','.$first['lng'].'";
                var request = {
                    origin:route_start,
                    destination:route_end,
                    travelMode: google.maps.DirectionsTravelMode.DRIVING
                };
                directionsService.route(request, function(response, status) {
                    if (status == google.maps.DirectionsStatus.OK) {
                        directionsDisplay.setDirections(response);
                        $("#directionsPanelWrap").show();
                    } else {
                        $.flashMessage("'.tr('Unable to calculate a route between your location and this company').'", "error");
                    }
                });
            }

        });
        </script>';

        return $html;

    }catch(Exception $e){
        throw new bException('googlemaps_map_with_markers(): Failed', $e);
    }
}



/*
 * Get coords from address
 */
function googlemaps_geocoding($street, $city, $state, $country) {
    global $_CONFIG;

    try{
        load_libs('curl,json');

        $raw  = curl_get('http://maps.googleapis.com/maps/api/geocode/json?address='.urlencode($street).','.urlencode($city).','.urlencode($state).','.urlencode($country).'&sensor=false','http://'.$_SESSION['domain']);
        $data = json_decode_custom($raw['data'], true);

        if(!empty($data['results'][0]['geometry']['location']['lat'])) {
            return $data['results'][0]['geometry']['location'];
        }

        return false;

    }catch(Exception $e){
        throw new bException('googlemaps_geocoding(): Failed', $e);
    }
}



/*
 * Get coords from address
 */
function googlemaps_reverse_geocoding($latitude, $longitude, $sensor = null) {
    global $_CONFIG;

    try{
        if(empty($latitude) or empty($longitude)){
            throw new bException('googlemaps_reverse_geocoding(): Latitude or Longitude empty', 'invalid');
        }

        if($sensor === null){
            /*
             * Guess if the customer has a sensor or not
             */
            $sensor = !empty($_SESSION['device']['mobile']);
        }

        load_libs('curl,json');

        $raw  = curl_get(array('url'        => 'http://maps.googleapis.com/maps/api/geocode/json?latlng='.$latitude.','.$longitude.'&sensor='.($sensor ? 'true' : 'false'),
                               'getheaders' => false));

        $data = json_decode_custom($raw['data'], true);

        if(!empty($data['results'])) {
            return $data['results'];
        }

        return false;

    }catch(Exception $e){
        throw new bException('googlemaps_reverse_geocoding(): Failed', $e);
    }
}



/*
 *
 */
function googlemaps_markers($locations, $longitude = null){
    global $_CONFIG;

    try{
        load_config('googlemaps');

        if(!is_array($locations)){
            $locations = array(array('latitude'  => $locations,
                                     'longitude' => $longitude));
        }

        $retval = array();

         foreach($locations as $location){
            $retval[] = array('lat'  => $location['latitude'],
                              'lng'  => $location['longitude'],
                              'html' => '',
                              'icon' => $_CONFIG['googlemaps']['markers']['icon']);
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('googlemaps_markers(): Failed', $e);
    }
}
?>
