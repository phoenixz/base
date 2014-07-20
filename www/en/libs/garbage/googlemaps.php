<?php
/*
 * Google library
 *
 * This file contains various google related functions
 *
 * Written and Copyright by Sven Oostenbrink
 */


/*
 * Empty function
 */
function google_map_head(){
    return array('meta'   => array(array('name'    => 'viewport',
                                         'content' => 'initial-scale=1.0, user-scalable=no"')),

                 'script' => array(array('src'     => 'http://maps.google.com/maps/api/js?sensor=false')));
}



/*
 * Empty function
 */
function google_map_body($params){
    try{
        if(!is_array($params)){
            throw new lsException('google_get_map(): Specified params is not an array');
        }

        array_default($params            , 'settings', array());
        array_default($params['settings'], 'zoom'    , 15);
        array_default($params['settings'], 'center'  , 'latlng');



        return '<script type="text/javascript">
			function google_map_init() {
				var latlng = new google.maps.LatLng(57.0442, 9.9116);
				var settings = {
					zoom: '.$params['settings']['zoom'].',
					center: '.$params['settings']['center'].',
					mapTypeControl: true,
					mapTypeControlOptions: {style: google.maps.MapTypeControlStyle.DROPDOWN_MENU},
					navigationControl: true,
					navigationControlOptions: {style: google.maps.NavigationControlStyle.SMALL},
					mapTypeId: google.maps.MapTypeId.ROADMAP};

				var map = new google.maps.Map(document.getElementById("map_canvas"), settings);

				var contentString = \'<div id="content">\'+
					\'<div id="siteNotice">\'+
					\'</div>\'+
					\'<h1 id="firstHeading" class="firstHeading">Høgenhaug</h1>\'+
					\'<div id="bodyContent">\'+
					\'<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>\'+
					\'</div>\'+
					\'</div>\';

				var infowindow = new google.maps.InfoWindow({
					content: contentString
				});

				var companyImage = new google.maps.MarkerImage("images/logo.png",
					new google.maps.Size(100,50),
					new google.maps.Point(0,0),
					new google.maps.Point(50,50)
				);

				var companyShadow = new google.maps.MarkerImage("images/logo_shadow.png",
					new google.maps.Size(130,50),
					new google.maps.Point(0,0),
					new google.maps.Point(65, 50));

				var companyPos = new google.maps.LatLng(57.0442, 9.9116);

				var companyMarker = new google.maps.Marker({
					position: companyPos,
					map: map,
					icon: companyImage,
					shadow: companyShadow,
					title:"Høgenhaug",
					zIndex: 3});

				var trainImage = new google.maps.MarkerImage("images/train.png",
					new google.maps.Size(50,50),
					new google.maps.Point(0,0),
					new google.maps.Point(50,50)
				);

				var trainShadow = new google.maps.MarkerImage("images/train_shadow.png",
					new google.maps.Size(70,50),
					new google.maps.Point(0,0),
					new google.maps.Point(60, 50)
				);

				var trainPos = new google.maps.LatLng(57.0429, 9.9173);

				var trainMarker = new google.maps.Marker({
					position: trainPos,
					map: map,
					icon: trainImage,
					shadow: trainShadow,
					title:"Train Station",
					zIndex: 2
				});

				var parkingImage = new google.maps.MarkerImage("images/parking.png",
					new google.maps.Size(50,50),
					new google.maps.Point(0,0),
					new google.maps.Point(50,50)
				);

				var parkingShadow = new google.maps.MarkerImage("images/parking_shadow.png",
					new google.maps.Size(70,50),
					new google.maps.Point(0,0),
					new google.maps.Point(60, 50)
				);

				var parkingPos = new google.maps.LatLng(57.0437, 9.9147);

				var parkingMarker = new google.maps.Marker({
					position: parkingPos,
					map: map,
					icon: parkingImage,
					shadow: parkingShadow,
					title:"Parking Lot",
					zIndex: 1
				});

				google.maps.event.addListener(companyMarker, "click", function() {
					infowindow.open(map,companyMarker);
				});
			}';

    }catch(Exception $e){
        throw new lsException('google_get_map(): Failed', $e);
    }
}
?>
