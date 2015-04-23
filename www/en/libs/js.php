<?php
/*
 * JS library (Javascript library)
 *
 * This library contains loader functions for various javascript libraries
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>
 */



/*
 * Add hidden javascript flash HTML that can be activated by javascript
 */
function js_flash($id = 'jsFlashMessage'){
	try{
		if(PLATFORM != 'http'){
			throw new bException('js_flash(): This function can only be executed on a webserver!');
		}

		html_load_js('base/flash');

		return '<div '.($id ? 'id="'.$id.'" ' : '').'class="sys_msg" style="display:none;"></div>';

	}catch(Exception $e){
		throw new bException('js_flash(): Failed', $e);
	}
}



/*
 * Load the js zclip library (Used for copy to clipboard functionality) and return required functionality to use it
 * See http://steamdev.com/zclip/#download
 * See /data/doc/zclip.txt for more documentation!
 *
 * Usage example
 *
	$('a#copy-description').zclip({
		path:'js/ZeroClipboard.swf',
		copy:$('p#description').text()
	});
 *
 */
function js_zclip_copy($click_selector, $copy, $add_script_tag = false, $params = null){
    try{
		if(is_array($add_script_tag)){
			$params         = $add_script_tag;
			$add_script_tag = false;
		}

		if(!$params){
			$params = "afterCopy : function(){ $.flashMessage(\"".tr('The information has been copied to your clipboard')."\", 'info'); }\n";

		}else{
			if(!is_array($params)){
				throw new bException('js_zclip_copy(): $params should be specified as an array but is an "'.gettype($params).'"');
			}

			/*
			 * Use script tags may be set in params
			 */
			if(isset($params['add_script_tag'])){
				$add_script_tag = $params['add_script_tag'];
				unset($params['add_script_tag']);
			}

			/*
			 * Convert to JS string
			 */
			$params = array_implode_with_keys($params, ",\n");
		}

		load_libs('html');
		html_load_js('base/jquery.zclip');

		$retval = '$("'.cfm($click_selector).'").zclip({
	path : "/pub/js/base/ZeroClipboard.swf",
	copy : '.$copy.",\n".$params.'
});';

		if(!$add_script_tag){
			return $retval;
		}

		return html_script($retval);

    }catch(Exception $e){
        throw new bException('js_zclip_copy(): Failed', $e);
    }
}
?>
